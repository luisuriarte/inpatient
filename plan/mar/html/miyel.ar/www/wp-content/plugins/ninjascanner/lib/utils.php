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

// =====================================================================
// We don't want to be bothered by other themes/plugins admin notices.

add_action('admin_head', 'nscan_hide_admin_notices');

function nscan_hide_admin_notices() {
	if ( isset( $_GET['page'] ) && $_GET['page'] == 'NinjaScanner' ) {
		remove_all_actions('admin_notices');
		remove_all_actions('all_admin_notices');
	}
}

// ===================================================================== 2023-06-07
// Clean-up the scan temp files.

function nscan_cleanup_tempfiles() {

	global $nscan_temp_files;

	foreach( $nscan_temp_files as $file ) {
		if ( file_exists( $file ) ) {
			unlink( $file );
		}
	}
}

// ===================================================================== 2023-06-07
// Disable PHP display_errors so that notice, warning and error messages
// don't show up in the AJAX response.

function nscan_hide_errors() {

	ini_set('display_errors', 0 );
}

// ===================================================================== 2023-06-07
// Recursively delete all files and directories. Used to delete
// extracted ZIP files (plugins and themes) in the cache folder
// after file integrity check:

function nscan_remove_dir( $dir ) {

	// Play safe: make sure that whatever we delete,
	// it's located inside our cache folder:
	$dir = realpath( $dir );
	if ( strpos( $dir, NSCAN_CACHEDIR ) === false ) {
		nscan_log_error( sprintf(
			__('Directory path does not match NSCAN_CACHEDIR: %s',
			'ninjascanner'),
			$dir
		));
	}

	if ( is_dir( $dir ) ) {
		$files = scandir( $dir );
		foreach ( $files as $file ) {
			if ( $file == '.' || $file == '..') {
				continue;
			}
			if ( is_dir("$dir/$file" ) ) {
				nscan_remove_dir( "$dir/$file");
			} else {
				unlink("$dir/$file");
			}
     }
     rmdir( $dir );
   }
}

// ===================================================================== 2023-06-07
// Read file content from the ZIP file.

function nscan_read_zipped_file( $zip, $file ) {

	// By default we use ZipArchive, but if it's not available,
	// we fall back to the built-in PclZip library:
	if ( class_exists('ZipArchive') ) {
		return file_get_contents("zip://{$zip}#{$file}");

	} else {
		// PclZip
		require_once ABSPATH .'wp-admin/includes/class-pclzip.php';
		$extract = new PclZip( $zip );
		if ( $extract->extract( NSCAN_CACHEDIR .'/tmp') !== 0 ) {
			$content = file_get_contents( NSCAN_CACHEDIR ."/tmp/$file");
			nscan_remove_dir( NSCAN_CACHEDIR .'/tmp');
			return $content;
		}
	}
}
// =====================================================================
// Retrieve the current scan's status
// (error|success|notfound|cancelled|stopped).

function nscan_get_lock_status() {

	global $nscan_steps;

	$lock_status = array(
		'current_step'	=> 0,
		'status'			=> 'error',
		'message'		=> __('Unknown error.', 'ninjascanner'),
		'last'			=>	'',
		'total_steps'	=>	count( $nscan_steps )
	);

	if ( file_exists( NSCAN_CANCEL ) ) {
		$lock_status['message'] = __('Scan was cancelled.', 'ninjascanner');
		$lock_status['status'] = 'cancelled';
	}

	if (! file_exists( NSCAN_LOCKFILE ) ) {
		$lock_status['message'] = __('Missing lock file.', 'ninjascanner');
		$lock_status['status'] = 'notfound';
		return $lock_status;
	}

	$status = json_decode( file_get_contents( NSCAN_LOCKFILE ), true );

	if (! empty( $status['current_step'] ) ) {
		$lock_status['current_step'] = (int) $status['current_step'];
	}
	if (! empty( $status['status'] ) ) {
		$lock_status['status'] = $status['status'];
	}
	if (! empty( $status['message'] ) ) {
		$lock_status['message'] = $status['message'];
	}
	if (! empty( $status['last'] ) ) {
		$lock_status['last'] = $status['last'];
	}

	return $lock_status;
}

// ===================================================================== 2023-06-07
// Set the current scan's status
// (error|success|notfound|cancelled|stopped).

function nscan_set_lock_status( $step, $status, $message, $last = '') {

	global $nscan_steps;

	$lock_status = array(
		'current_step'	=> $step,
		'status'			=> $status,
		'message'		=> $message,
		'last'			=>	$last,
		'total_steps'	=>	count( $nscan_steps )
	);

	file_put_contents( NSCAN_LOCKFILE, json_encode( $lock_status ) );
}

// ===================================================================== 2023-06-07
// Stop the scanning process.

function nscan_stop_scan() {

	nscan_cleanup_tempfiles();

	exit( json_encode( ['status' => 'success'] ) );
}

// ===================================================================== 2023-06-07
// Cancel a running scan.

function nscan_cancel_scan() {

	if ( empty( $_POST['message'] ) ) {
		$_POST['message'] = '';
	}
	nscan_log_info(
		sprintf(
			__('Cancelling scanning process (%s)', 'ninjascanner'),
			$_POST['message']
		), false
	);

	touch( NSCAN_CANCEL );
	if ( file_exists( NSCAN_LOCKFILE ) ) {
		unlink( NSCAN_LOCKFILE );
	}

	wp_send_json( [
		'status' => 'success',
		'message' => __('Scan cancelled', 'ninjascanner')
	] );
}

// ===================================================================== 2023-06-07
// Check if a scan is running.

function nscan_is_scan_running() {

	return json_encode( nscan_get_lock_status() );
}

// ===================================================================== 2023-06-07
// Check if a scan process was cancelled.

function nscan_is_scan_cancelled() {

	if ( file_exists( NSCAN_CANCEL ) ) {
		nscan_log_error( __('Scan was cancelled.', 'ninjascanner') );
		exit;
	}
}

// ===================================================================== 2023-06-07
// Write message to the log. Log level can be a combination of INFO (1),
// WARN (2), ERROR (4) and DEBUG (8) and can be adjusted while viewing
// the log. Check also if the scanning process was cancelled (missing
// lock file) and exit.

function nscan_log( $string, $level = 1, $exit = true ) {

	if ( $exit == true ) {
		$lock_status = nscan_get_lock_status();
		if ( in_array( $lock_status['status'], ['notfound', 'cancelled'] ) ) {
			file_put_contents(
				NSCAN_DEBUGLOG,
				time() . "~~8~~{$lock_status['message']}\n",
				FILE_APPEND
			);
			nscan_stop_scan();
		}
	}
	file_put_contents(
		NSCAN_DEBUGLOG,
		time() ."~~$level~~$string\n",
		FILE_APPEND
	);
}

function nscan_log_info(  $string, $exit = true ) {
	nscan_log( $string, 1, $exit );
}
function nscan_log_warn(  $string, $exit = true ) {
	nscan_log( $string, 2, $exit );
}
function nscan_log_error( $string, $exit = true ) {
	nscan_log( $string, 4, $exit );
}
function nscan_log_debug( $string, $exit = true ) {
	nscan_log( $string, 8, $exit );
}

// ===================================================================== 2023-06-07
// Generate a nonce key.

function nscan_generate_key() {

	$key = bin2hex( openssl_random_pseudo_bytes(40) );
	set_transient(
		'nscan_ajax_start',
		hash('sha256', $key ),
		60 * NSCAN_KEYTIMEOUT
	);
	return $key;
}

// ===================================================================== 2023-06-07
// Verify nonce for on-demand scan.

function nscan_check_nonce() {

	if ( empty( $_POST['nscan_key'] ) ||
		! wp_verify_nonce( $_POST['nscan_key'], 'nscan_on_demand_nonce') ) {

		$return['status'] = 'error';
		$return['message'] = __('Security nonces do not match.', 'ninjascanner');
		nscan_log_error( $return['message'], false );
		nscan_set_lock_status(
			1,
			$return['status'],
			$return['message'],
			null
		);
		wp_send_json( $return );
	}
}
// =====================================================================
// Make sure we have a Linux or Windows absolute path.

function ns_win_or_linux( $file ) {

	if (! preg_match( '`^(?i:[a-z]:|/)`', $file ) || preg_match( '`\.\.\B`', $file ) ) {
		wp_die( sprintf(
			__('File does not seem valid: %s', 'ninjascanner' ),
			htmlentities( $file )
		) );
	}
}

// =====================================================================
// Verify the security key.

function nscan_check_key() {

	$success = array(
		'status' 	=> 'success',
		'message' 	=> __('Keys match.', 'ninjascanner')
	);
	$error = array(
		'status' 	=> 'error'
	);
	$error_msg = __('Security keys do not match (#%s). Try to reload this page.', 'ninjascanner');

	if ( empty( $_POST['nscan_key'] ) ) {
		$error['message'] = sprintf( $error_msg, 1 );
		return $error;
	}

	$key = get_transient( 'nscan_ajax_start' );
	if ( $key === false ) {
		$error['message'] = sprintf( $error_msg, 2 );
		return $error;
	}

	if ( hash( 'sha256', $_POST['nscan_key'] ) !== $key ) {
		delete_transient( 'nscan_ajax_start' );
		$error['message'] = sprintf( $error_msg, 3 );
		return $error;
	}

	return $success;
}

// =====================================================================
// Get the blog timezone.

function nscan_get_blogtimezone() {

	$tzstring = get_option( 'timezone_string' );
	if (! $tzstring ) {
		$tzstring = ini_get( 'date.timezone' );
		if (! $tzstring ) {
			$tzstring = 'UTC';
		}
	}
	date_default_timezone_set( $tzstring );
}

// =====================================================================

function nscan_is_valid() {

	$nscan_options = get_option( 'nscan_options' );
	nscan_get_blogtimezone();
	if ( empty( $nscan_options['key'] ) ) { return -1; }
	if (! empty( $nscan_options['exp'] ) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $nscan_options['exp'] ) ) {
		if ( $nscan_options['exp'] < date( 'Y-m-d', strtotime( '-1 day' ) ) ) {
			return -1;
		} elseif ( $nscan_options['exp'] < date( 'Y-m-d', strtotime( '+30 day' ) ) ) {
			return 30;
		}
		return 1;
	}
	return 0;
}

// =====================================================================

function nscan_check_license( $nscan_options, $key = '' ) {

	if ( is_multisite() ) {
		$site_url = rtrim( strtolower( network_site_url('','http') ), '/' );
	} else {
		$site_url = rtrim( strtolower(site_url('','http') ), '/' );
	}

	global $wp_version;
	$opt_update = 0;
	$res = array();

	if ( empty( $key ) && ! empty( $nscan_options['key'] ) ) {
		$key = $nscan_options['key'];
	}

	if ( empty( $key ) ) {
		$res['nscan_err'] = __('Error: You do not have a Premium license.', 'ninjascanner');
		return $res;
	}

	$request_string = array(
		'body' => array(
			'action' => 'check_license',
			'key'	=>	$key,
			'cache_id' => sha1( home_url() ),
			'host' => @strtolower( $_SERVER['HTTP_HOST'] )
		),
		'user-agent' => 'Mozilla/5.0 (compatible; NinjaScanner/'. NSCAN_VERSION ."; WordPress/{$wp_version})",
		'timeout' => NSCAN_CURL_TIMEOUT,
		'httpversion' => '1.1' ,
		'sslverify' => true
	);
	// POST the request:
	$res = wp_remote_post( NSCAN_SIGNATURES_URL, $request_string );

	if (! is_wp_error($res) ) {

		if ( $res['response']['code'] == 200 ) {

			// Fetch the array:
			$data = json_decode( $res['body'], true );
			// Verify its content:
			if ( empty( $data['checked'] ) ) {
				$res['nscan_err'] = __('An unknown error occurred while connecting to NinjaScanner API server. Please try again in a few minutes.', 'ninjascanner');
				return $res;
			}
			if (! empty( $data['exp'] ) ) {
				$nscan_options['exp'] = $data['exp'];
				$res['nscan_exp'] = $data['exp'];
				update_option( 'nscan_options', $nscan_options );
			}

			if (! empty( $data['err'] ) ) {
				$res['nscan_err'] = sprintf(
					__('Error: Your license is not valid (#%s).', 'ninjascanner'),
					(int)$data['err']
				);
				return $res;
			}

			$res['nscan_msg'] = __('You have a valid license', 'ninjascanner');
			return $res;

		} else {
			// HTTP error:
			$res['nscan_err'] = sprintf(
				__('HTTP Error (%s): Cannot connect to the API server. Try again later', 'ninjascanner'),
				(int)$res['response']['code']
			);
			return $res;
		}
	} else {
		// Unknown error:
		$res['nscan_err']  = __('Error: Cannot connect to the API server. Try again later', 'ninjascanner');
		return $res;
	}
}

// =====================================================================

function nscan_save_license( $nscan_options ) {

	$res = array();
	$key = trim( $_POST['key'] );
	$res = nscan_check_license( $nscan_options, $key );
	if ( empty( $res['nscan_err'] ) ) {
		$nscan_options['key'] = $key;
		$nscan_options['exp'] = $res['nscan_exp'];
		update_option( 'nscan_options', $nscan_options );
		$res['nscan_msg'] = __('Your license has been accepted and saved.', 'ninjascanner');
	}
	return $res;

}
// =====================================================================
// Send an email to the admin if there were an error.

function nscan_error_email( $error ) {

	$nscan_options = get_option( 'nscan_options' );
	if ( empty( $nscan_options['admin_email'] ) ) {
		return;
	}

	$message = sprintf(
		__('Cannot start the scan! More details may be available in the scanner log: %s', 'ninjascanner',
		$error
	) );

	if ( is_multisite() ) {
		$blog = network_home_url('/');
	} else {
		$blog = home_url('/');
	}
	$subject = __('[NinjaScanner] Scan error', 'ninjascanner');
	$message = sprintf( __('A fatal error occurred while running NinjaScanner: %s.', 'ninjascanner'), $error );
	$message .= "\n\n". __('More details may be available in the scanner log.', 'ninjascanner' ) ."\n";
	$signature = "\nNinjaScanner - https://nintechnet.com/\n" .
			__('Help Desk (Premium customers only):', 'ninjascanner') . " https://secure.nintechnet.com/login/\n";
	wp_mail( $nscan_options['admin_email'], $subject, $message . $signature );

}


// =====================================================================
// EOF
