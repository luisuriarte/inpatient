<?php
/*
 +=====================================================================+
 |     _   _ _        _       ____                                     |
 |    | \ | (_)_ __  (_) __ _/ ___|  ___ __ _ _ __  _ __   ___ _ __    |
 |    |  \| | | '_ \ | |/ _` \___ \ / __/ _` | '_ \| '_ \ / _ \ '__|   |
 |    | |\  | | | | || | (_| |___) | (_| (_| | | | | | | |  __/ |      |
 |    |_| \_|_|_| |_|/ |\__,_|____/ \___\__,_|_| |_|_| |_|\___|_|      |
 |                 |__/                                                |
 |                                                                     |
 | (c) NinTechNet ~ https://nintechnet.com/                            |
 +=====================================================================+
*/

if (! defined( 'ABSPATH' ) ) { die( 'Forbidden' ); }

// In a multi-site environment, we load the code from the main site only.
if (! is_main_site() ) { return; }

// =====================================================================
// Start a scan. Called either from AJAX (by an admin only) or
// directly by the scanner.

add_action( 'wp_ajax_nscan_startscan', 'nscan_ajax_startscan' );

function nscan_ajax_startscan() {

	nscan_hide_errors();

	$return = array(
		'status' 	=> 'success',
		'message' 	=> __('Keys match.', 'ninjascanner')
	);

	// If this is an AJAX call, make sure it comes from an admin/superadmin.
	// Note: we can't use DOING_AJAX as it would be triggered by nscan_fork.
	if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'nscan_startscan' ) {
		if (! current_user_can('manage_options') ) {
			$return['status'] = 'error';
			$return['message'] = __('Not allowed.', 'ninjascanner');
			nscan_log_error( $return['message'], false );
			nscan_set_lock_status(
				1,
				$return['status'],
				$return['message'],
				null
			);
			wp_send_json( $return );
		}
		// Verify security nonce
		nscan_check_nonce();

		// Generate a temporary scan key:
		$_POST['nscan_key'] = nscan_generate_key();

	} else {
		// Make sure we have a valid temp key
		$return = nscan_check_key();
		if ( $return['status'] == 'error' ) {
			nscan_log_error( $return['message'], false );
			nscan_set_lock_status(
				1,
				'error',
				 __('Nonce keys do not match. Try to reload this page.', 'ninjascanner'),
				null
			);
			wp_send_json( $return ); // Nobody will receive this anyway.
		}
	}

	$nscan_options = get_option( 'nscan_options' );

	// First run, we need to clean up temp files
	if (! empty( $_POST['first_run'] ) ) {
		nscan_cleanup_tempfiles();
		// and the debug log
		if ( file_exists( NSCAN_DEBUGLOG ) ) {
			unlink( NSCAN_DEBUGLOG );
		}
		// and create the lock file
		nscan_set_lock_status(
			1,
			'success',
			__('Initialising...', 'ninjascanner' ),
			// Used to catch error on init (401 etc)
			'init'
		);
	}

	// Fork method (non-blocking socket): WP-CRON or AJAX API
	if (! empty( $nscan_options['scan_fork_method'] ) && $nscan_options['scan_fork_method'] == 2 ) {
		$fork = 'AJAX API';
		$url = admin_url( 'admin-ajax.php' );
		$request = array(
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			'body' => array(
				'nscan_key' => $_POST['nscan_key'],
				'action' => 'nscan_fork'
			)
		);

		// Don't use a blocking socket unless this is the very first request:
		if ( empty( $_POST['first_run'] ) ) {
			$request['timeout'] = 0.01;
			$request['blocking'] = false;
		} else {
			// WP default timeout is 5s, let's increase it:
			$request['timeout'] = NSCAN_CURL_TIMEOUT;
		}

		if (! empty( $nscan_options['username'] ) && ! empty( $nscan_options['password'] ) ) {
			$request['headers'] = array(
				'Authorization' => 'Basic '. base64_encode( "{$nscan_options['username']}:{$nscan_options['password']}" )
			);
		}
		$request['headers']['Accept-Language'] = 'en-US,en;q=0.5';
		$request['headers']['User-Agent'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:60.0)';
		$res = wp_remote_post( $url, $request );

	} else {
		// Fork with a WP cron, even if WP-CRON is disabled
		$fork = 'WP-CRON';
		wp_schedule_single_event( time() - 1, 'wp_ajax_nopriv_nscan_fork', array ( $_POST['nscan_key'] ) );
		$doing_wp_cron = sprintf( '%.22F', microtime( true ) );
		set_transient( 'doing_cron', $doing_wp_cron );
		$request = apply_filters( 'cron_request', array(
			'url'  => add_query_arg( 'doing_wp_cron', $doing_wp_cron, site_url('wp-cron.php') ),
			'key'  => $doing_wp_cron,
			'args' => array(
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
				'headers' => array(
					'Accept-Language' => 'en-US,en;q=0.5',
					'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:60.0)'
				)
			)
		) );

		// Don't use a blocking socket unless this is the very first request:
		if ( empty( $_POST['first_run'] ) ) {
			$request['args']['timeout'] = 0.01;
			$request['args']['blocking'] = false;
		} else {
			$request['args']['timeout'] = NSCAN_CURL_TIMEOUT;
		}
		if (! empty( $nscan_options['username'] ) && ! empty( $nscan_options['password'] ) ) {
			$request['args']['headers']['Authorization'] = 'Basic '. base64_encode( "{$nscan_options['username']}:{$nscan_options['password']}" );
		}
		$res = wp_remote_post( $request['url'], $request['args'] );
	}

	if ( is_wp_error( $res ) ) {
		nscan_log_error( sprintf(
			__('Fatal error: forking process failed (%s: %s). Aborting', 'ninjascanner'),
			$res->get_error_message(),
			$fork
		));
		nscan_cancel_scan();
	}
	// Check for error upon initialization
	if (! empty( $_POST['first_run'] ) ) {
		if (! empty( $res['response']['code'] ) && $res['response']['code'] >= 400 ) {
			$msg = sprintf(
				__('Fatal error: the HTTP server returns a [%s %s] HTTP code. Aborting', 'ninjascanner'),
				(int)$res['response']['code'],
				$res['response']['message']
			);
			nscan_log_error( $msg );
			wp_send_json( array( 'status' => 'error', 'message' => $msg ) );
			nscan_cancel_scan();
		}
	}

	if ( defined('NSCAN_STARTSCAN_USLEEP') ) {
		usleep( NSCAN_STARTSCAN_USLEEP );
	}

	if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'nscan_startscan' ) {
		wp_send_json( $return );

	} else {
		return( json_encode( $return ) );
	}
}

// =====================================================================
// Fork the scan process (this is called from a non-blocking socket).

add_action( 'wp_ajax_nopriv_nscan_fork', 'nscan_ajax_fork' );

function nscan_ajax_fork( $key = null ) {

	nscan_hide_errors();

	if (! empty( $key ) ) {
		$_POST['nscan_key'] = $key;
	}

	// Make sure we have a valid temp key to access this public AJAX call
	$return = nscan_check_key();
	if ( $return['status'] == 'error' ) {
		nscan_log_error( $return['message'], false );
		nscan_set_lock_status(
			1,
			'error',
			 __('Nonce keys do not match. Try to reload this page.', 'ninjascanner'),
			null
		);
		wp_send_json( $return ); // Nobody will receive this anyway.
	}

	require __DIR__ .'/scan.php';
	wp_die();
}

// =====================================================================
// Returns the scanning process status over AJAX.

add_action( 'wp_ajax_nscan_check_status', 'nscan_check_status_ajax' );

function nscan_check_status_ajax() {

	nscan_hide_errors();

	// Allow only the Admin/Superadmin
	if (! current_user_can('manage_options') ) {
		$return['status'] = 'error';
		$return['message'] = __('Not allowed.', 'ninjascanner');
		wp_send_json( $return );
	}

	// Verify security nonce
	nscan_check_nonce();
	echo nscan_is_scan_running();
	wp_die();
}

// =====================================================================
// Cancel a scanning process (AJAX call from the Summary page).

add_action( 'wp_ajax_nscan_cancel', 'nscan_cancel_ajax' );

function nscan_cancel_ajax() {

	nscan_hide_errors();

	// Allow only the Admin/Superadmin
	if (! current_user_can('manage_options') ) {
		$return['status'] = 'error';
		$return['message'] = __('Not allowed.', 'ninjascanner');
		wp_send_json( $return );
	}

	// Verify security nonce
	nscan_check_nonce();
	nscan_cancel_scan();
}

// =====================================================================
// Ajax processing: check user's Google Safe Browsing API key validity.

add_action( 'wp_ajax_nscan_checkapikey', 'nscan_checkapikey' );

function nscan_checkapikey() {

	nscan_hide_errors();

	// Allow only the Admin/Superadmin with a valid nonce
	if (! current_user_can('manage_options') ||
		! check_ajax_referer( 'nscan_gsbapikey', 'nscanop_nonce', false ) ) {
		_e('Error: Security nonces do not match. Reload the page and try again.', 'ninjascanner');
		wp_die();
	}

	if ( empty( $_POST['api_key'] ) ) {
		_e('Please enter your API key.', 'ninjascanner');
		wp_die();
	}
	global $wp_version;

	// Used for Google referrer restriction
	$referrer = get_site_url();

	$body = array(
		'body' => '{
			"threatInfo": {
				"threatTypes":      ["MALWARE", "SOCIAL_ENGINEERING"],
				"platformTypes":    ["ANY_PLATFORM"],
				"threatEntryTypes": ["URL"],
				"threatEntries": [
					{"url": "htt'.'p://malware.tes'.'ting.google.test/testing/malware/"},
				]
			}
		}',
		'headers' => array(
			'content-type' => 'application/json',
			'Referer' => $referrer
		),
		'data_format' => 'body',
		'user-agent' => 'Mozilla/5.0 (compatible; NinjaScanner/'.
							NSCAN_VERSION ."; WordPress/{$wp_version})",
		'timeout' => NSCAN_CURL_TIMEOUT,
		'httpversion' => '1.1' ,
		'sslverify' => true
	);
	$res = wp_remote_post( NSCAN_GSB . "?key={$_POST['api_key']}", $body);

	if (! is_wp_error($res) ) {
		$data = json_decode( $res['body'], true );
		// Invalid key
		if (! empty( $data['error']['message'] ) ) {
			printf( __('Error: %s', 'ninjascanner'), $data['error']['message'] );
			wp_die();
		}
		// OK
		if (! empty( $data['matches'][0]['threat']['url'] ) ) {
			echo "success";
			wp_die();
		}
	}

	// Something went wrong
	_e('Unknown error.', 'ninjascanner');
	wp_die();
}

// =====================================================================
// Ajax processing: quarantine the file.

add_action( 'wp_ajax_nscan_quarantine', 'nscan_ajax_quarantine' );

function nscan_ajax_quarantine() {

	nscan_hide_errors();

	// Allow only the Admin/Superadmin with a valid nonce:
	if (! current_user_can('manage_options') ||
		! check_ajax_referer( 'nscan_file_op', 'nscanop_nonce', false ) ) {
		_e('Error: Security nonces do not match. Reload the page and try again.', 'ninjascanner');
		wp_die();
	}

	$file = base64_decode( $_POST['file'] );
	ns_win_or_linux( $file );

	if (! file_exists( $file ) ) {
		echo '404';
		wp_die();
	}

	require __DIR__ .'/file_quarantine.php';

	// Make sure it was successfully quarantined:
	if (! file_exists( $file ) ) {
		echo "success";
	} else {
		_e('Error: Cannot quarantine the file.', 'ninjascanner');
	}

	wp_die();
}

// =====================================================================
// Ajax processing: ignore the file.

add_action( 'wp_ajax_nscan_ignore', 'nscan_ajax_ignore' );

function nscan_ajax_ignore() {

	nscan_hide_errors();

	// Allow only the Admin/Superadmin with a valid nonce:
	if (! current_user_can('manage_options') ||
		! check_ajax_referer( 'nscan_file_op', 'nscanop_nonce', false ) ) {
		_e('Error: Security nonces do not match. Reload the page and try again.', 'ninjascanner');
		wp_die();
	}

	$file = base64_decode( $_POST['file'] );
	ns_win_or_linux( $file );

	if (! file_exists( $file ) ) {
		echo '404';
		wp_die();
	}

	require __DIR__ .'/file_ignore.php';

	echo "success";
	wp_die();
}

// =====================================================================
// Ajax processing: restore the original file (core, plugin or theme).

add_action( 'wp_ajax_nscan_restore', 'nscan_ajax_restore' );

function nscan_ajax_restore() {

	nscan_hide_errors();

	// Allow only the Admin/Superadmin with a valid nonce:
	if (! current_user_can('manage_options') ||
		! check_ajax_referer( 'nscan_file_op', 'nscanop_nonce', false ) ) {
		_e('Error: Security nonces do not match. Reload the page and try again.', 'ninjascanner');
		wp_die();
	}

	$file = base64_decode( $_POST['file'] );
	ns_win_or_linux( $file );

	if (! file_exists( $file ) ) {
		_e('Error: File does not exist.', 'ninjascanner');
		wp_die();
	}

	require __DIR__ .'/file_restore.php';

	echo "success";
	wp_die();
}

// =====================================================================
// EOF
