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
 +=====================================================================+ // sa+i18n
*/

if (! defined( 'ABSPATH' ) ) { die( 'Forbidden' ); }

if (! is_dir( NSCAN_QUARANTINE ) ) {
	mkdir( NSCAN_QUARANTINE, 0755, true );
}

// Destination file:
$pathinfo = pathinfo( $file );
$quarantine_name = md5( $pathinfo['dirname'] ) ."_". $pathinfo['basename'];

// Make sure the source is writable:
if (! is_writable( $file ) ) {
	_e('Error: This file is not writable and therefore it cannot be moved.', 'ninjascanner' );
	wp_die();
}

$nscan_options = get_option( 'nscan_options' );
// Sandbox: make sure the front-end and back-end pages don't
// throw a fatal error:
if (! empty( $nscan_options['sandbox'] ) ) {
	// Clear opcode cache (PHP >=5.5), brefore removing the file:
	if ( function_exists( 'opcache_invalidate' )  ) {
		@opcache_invalidate( $file, true );
	}
	@session_write_close();

	// Move the file to the quarantine folder:
	rename( $file, NSCAN_QUARANTINE ."/{$quarantine_name}" );

	// Authentication cookies:
	$headers['Cache-Control']	= 'no-cache';
	// Devs must be allowed to use it on localhost over TLS too
	$headers['sslverify']		= apply_filters( 'https_local_ssl_verify', false );
	$headers['cookies']			= wp_unslash( $_COOKIE );
	// Error message template:
	$sandbox_error = __('NinjaScanner detected that removing this file seemed '.
			'to crash your blog. %s', 'ninjascanner') ."\n".
		 __('If you want to ignore this message and quarantine the file anyway, '.
		 'please disable the "Sandbox" option from the "Settings > Advanced Users '.
		 'Settings > Nerds Settings" page.', 'ninjascanner' );

	// Get NinjaScanner admin URL:
	if ( is_multisite() ) {
		$url = network_admin_url() .'admin.php?page=NinjaScanner';
	} else {
		$url = admin_url() .'admin.php?page=NinjaScanner';
	}

	if (! empty( $nscan_options['username'] ) && ! empty( $nscan_options['password'] ) ) {
		$headers['Authorization'] = 'Basic '. base64_encode( $nscan_options['username'] .':'. $nscan_options['password'] );
	}

	// Back-end connection attempt (authenticated):
	$res = wp_remote_get( $url, $headers );
	if (! is_wp_error( $res ) ) {

		// Look for HTTP error:
		if ( $res['response']['code'] >= 400 ) {
			$error_msg = sprintf(
				$sandbox_error,
				sprintf(
					__('The website back-end returned: HTTP %s %s.', 'ninjascanner'),
					(int) $res['response']['code'],
					$res['response']['message']
				)
			);
			cancel_quarantine( $error_msg, $file, NSCAN_QUARANTINE ."/{$quarantine_name}" );

		// Search our placeholder in the body:
		} else {
			if ( strpos( $res['body'], '<!-- NinjaScanner Quarantine -->' ) === false ) {
				// Something went wrong!
				$error_msg = sprintf(
					$sandbox_error,
					__('The website back-end returned: Cannot find the placeholder in the page.',
					'ninjascanner') );
				cancel_quarantine( $error_msg, $file, NSCAN_QUARANTINE ."/{$quarantine_name}" );
			}
		}
	} else {
			$error_msg = sprintf(
				$sandbox_error,
				sprintf( __('The website back-end returned a fatal error: %s.', 'ninjascanner'),
				$res->get_error_message()
			) );
			cancel_quarantine( $error_msg, $file, NSCAN_QUARANTINE ."/{$quarantine_name}" );
	}

	// Front-end connection attempt (unauthenticated):
	$url = home_url( '/' ) .'?'. time();

	$headers = array();
	// Devs must be allowed to use it on localhost over TLS too
	$headers['sslverify'] = apply_filters( 'https_local_ssl_verify', false );
	if (! empty( $nscan_options['username'] ) && ! empty( $nscan_options['password'] ) ) {
		$headers['Authorization'] = 'Basic '. base64_encode( $nscan_options['username'] .':'. $nscan_options['password'] );
	}

	$res = wp_remote_get( $url, $headers );
	$blogname = get_option( 'blogname' );
	if (! is_wp_error( $res ) ) {

		// Look for HTTP error:
		if ( $res['response']['code'] >= 400 ) {
			$error_msg = sprintf(
				$sandbox_error,
				sprintf(
					__('The website front-end returned: HTTP %s %s.', 'ninjascanner'),
					(int) $res['response']['code'],
					$res['response']['message']
				)
			);
			cancel_quarantine( $error_msg, $file, NSCAN_QUARANTINE ."/{$quarantine_name}" );

		}
		if ( strpos( $res['body'], $blogname ) === false ) {
			$error_msg = sprintf(
				$sandbox_error,
				__('The website front-end did not return the expected page.', 'ninjascanner')
			);
			cancel_quarantine( $error_msg, $file, NSCAN_QUARANTINE ."/{$quarantine_name}" );
		}

	} else {
			$error_msg = sprintf(
				$sandbox_error,
				sprintf(
					__('The website front-end returned a fatal error: %s.', 'ninjascanner'),
					$res->get_error_message()
				)
			);
			cancel_quarantine( $error_msg, $file, NSCAN_QUARANTINE ."/{$quarantine_name}" );
	}
} else {
	// Move the file to the quarantine folder:
	rename( $file, NSCAN_QUARANTINE ."/{$quarantine_name}" );
}

// Check if we have a quarantined files log:
$quarantined_files = array();
if ( file_exists( NSCAN_QUARANTINE .'/quarantine.php' ) ) {
	$quarantined_files = unserialize( file_get_contents( NSCAN_QUARANTINE .'/quarantine.php' ) );
}

// Save its new location and name to the quarantined files log:
$quarantined_files[$file] = $quarantine_name;
file_put_contents( NSCAN_QUARANTINE .'/quarantine.php', serialize( $quarantined_files ) );

// =====================================================================
// Restore the quarantined file if the site crashes, and return an error.

function cancel_quarantine( $message, $original, $quarantined ) {

	rename( $quarantined, $original );
	echo $message;
	wp_die();

}

// =====================================================================
// EOF
