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
 +=====================================================================+ /sa+i18n
*/

if (! defined( 'ABSPATH' ) ) { die( 'Forbidden' ); }

// =====================================================================
// Set up (or restore) default settings.

function nscan_default_settings( $key = null, $exp = null ) {

	$excluded_folders    = array( '/ninjascanner/nscan' );
	// If NinjaFirewall is installed, add its cache folder to the exlusion list
	if (defined( 'NFW_LOG_DIR') ) {
		$excluded_folders[] = '/'. basename( NFW_LOG_DIR ) .'/nfwlog/';
	}

	$signatures = array ( "lmd" );

	$nscan_options = array(
		'scan_size'						=>	1024,
		'scan_extensions'				=>	'',
		'scan_folders'					=>	json_encode( $excluded_folders ),
		'scan_root_folders'			=> '',
		'scan_folders_fic'			=>	0,
		'admin_email'					=>	get_option('admin_email'),
		'admin_email_report'			=>	0,
		'scan_ninjaintegrity'		=>	0,
		'scan_wpcoreintegrity'		=>	1,
		'scan_themeseintegrity'		=>	1,
		'scan_pluginsintegrity'		=>	1,
		'scan_warnfilechanged'		=>	1,
		'scan_warndbchanged'			=>	1,
		'scan_signatures'				=>	json_encode( $signatures ),
		'scan_gsb'						=>	'',
		'scan_nosymlink'				=>	1,
		'scan_warnsymlink'			=>	1,
		'scan_warnbinary'				=>	0,
		'scan_warnhiddenphp'			=>	1,
		'scan_warnunreadable'		=>	1,
		'scan_toolbarintegration'	=>	1,
		'scan_nfwpintegration'		=>	0,
		'scan_fork_method'			=>	2,
		'mutipagereport'				=> 1,
		'row_action'					=> 0,
		'table_rows'					=>	6,
		'highlight'						=>	1,
		'show_path'						=>	0,
		'scan_checksum'				=>	2,
		'scan_debug_log'				=>	1,
		'scan_garbage_collector'	=>	1,
		'sandbox'						=>	1,
		'scan_enable_wpcli'			=> 1,
		'scan_scheduled'				=>	0,
		'dont_delete_cache'			=>	0
	);

	if (! empty( $key ) ) {
		$nscan_options['key'] = $key;
	}
	if (! empty( $exp ) ) {
		$nscan_options['exp'] = $exp;
	}

	return $nscan_options;
}

// =====================================================================
// Set up NinjaScanner's garbage collector.

function nscan_default_gc( $value = 3 ) {

	if ( $value == 1 ) {
		$value = 'hourly';
	} elseif ( $value == 2 ) {
		$value = 'twicedaily';
	} elseif ( $value == 3 ) {
		$value = 'daily'; // Default
	}

	if ( wp_next_scheduled( 'nscan_garbage_collector' ) ) {
		wp_clear_scheduled_hook( 'nscan_garbage_collector' );
	}

	// Don't shedule it?
	if ( $value == 4 ) { return; }

	wp_schedule_event( time() + 3600, $value, 'nscan_garbage_collector' );
}

// =====================================================================
// Set up NinjaScanner's scheduled scan.

function nscan_default_sc( $value = 0 ) {

	if ( $value == 1 ) {
		$value = 'hourly';
	} elseif ( $value == 2 ) {
		$value = 'twicedaily';
	} elseif ( $value == 3 ) {
		$value = 'daily';
	} else {
		$value = 0; // Default
	}

	if ( wp_next_scheduled( 'nscan_scheduled_scan' ) ) {
		wp_clear_scheduled_hook( 'nscan_scheduled_scan' );
	}

	if ( $value ) {
		wp_schedule_event( time() + 3600, $value, 'nscan_scheduled_scan' );
	}
}

// =====================================================================
// Create NinjaScanner's cache folder.

function nscan_cache_folder( $uniqid ) {

	if (! is_dir( WP_CONTENT_DIR . "/ninjascanner/{$uniqid}/cache" ) ) {
		if ( @mkdir( WP_CONTENT_DIR . "/ninjascanner/{$uniqid}/cache", 0755, true ) === false ) {
			// Error, stop here:
			return false;
		}
	}
	if (! is_dir( WP_CONTENT_DIR . "/ninjascanner/{$uniqid}/quarantine" ) ) {
		if ( @mkdir( WP_CONTENT_DIR . "/ninjascanner/{$uniqid}/quarantine", 0755, true ) === false ) {
			// Error, stop here:
			return false;
		}
	}

	$htaccess = '<Files "*">
	<IfModule mod_version.c>
		<IfVersion < 2.4>
			Order Deny,Allow
			Deny from All
		</IfVersion>
		<IfVersion >= 2.4>
			Require all denied
		</IfVersion>
	</IfModule>
	<IfModule !mod_version.c>
		<IfModule !mod_authz_core.c>
			Order Deny,Allow
			Deny from All
		</IfModule>
		<IfModule mod_authz_core.c>
			Require all denied
		</IfModule>
	</IfModule>
</Files>';
	$html = '<html></html>';
	file_put_contents( WP_CONTENT_DIR . "/ninjascanner/index.html", $html );
	file_put_contents( WP_CONTENT_DIR . "/ninjascanner/{$uniqid}/index.html", $html );
	file_put_contents( WP_CONTENT_DIR . "/ninjascanner/{$uniqid}/cache/index.html", $html );
	file_put_contents( WP_CONTENT_DIR . "/ninjascanner/{$uniqid}/quarantine/index.html", $html );

	file_put_contents( WP_CONTENT_DIR . "/ninjascanner/.htaccess", $htaccess );
	file_put_contents( WP_CONTENT_DIR . "/ninjascanner/{$uniqid}/.htaccess", $htaccess );
	file_put_contents( WP_CONTENT_DIR . "/ninjascanner/{$uniqid}/cache/.htaccess", $htaccess );
	file_put_contents( WP_CONTENT_DIR . "/ninjascanner/{$uniqid}/quarantine/.htaccess", $htaccess );
	// NSCAN_LOCAL can be defined in the wp-config.php:
	if (! is_dir( NSCAN_LOCAL ) ) {
		@mkdir( NSCAN_LOCAL, 0755 );
	}
	file_put_contents( NSCAN_LOCAL . '/index.html', $html );
	file_put_contents( NSCAN_LOCAL . '/.htaccess', $htaccess );

	return true;
}

// =====================================================================
// EOF
