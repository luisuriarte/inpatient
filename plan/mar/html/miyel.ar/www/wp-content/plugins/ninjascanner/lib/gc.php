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
// Run the Garbage Collector, either from WP-Cron (and exits),
// or from the "Settings" page (and returns).

function nscan_gc( $return = false, $clear_snapshot = false ) {

	$nscan_options = get_option( 'nscan_options' );

	// If a scan is running, we delay the garbage collector for 10 minutes:
	$lock_status = json_decode( nscan_is_scan_running(), true );
	if ( $lock_status['status'] == 'success' ) {

		nscan_delay_gc( @$nscan_options['scan_garbage_collector'] );
		nscan_gclog(
			__('Delaying garbage collector for 10 minutes, a scan is running', 'ninjascanner' ),0
		);
		if ( empty( $return ) ) {
			exit;
		}
		return __('Delaying garbage collector for 10 minutes, a scan is running', 'ninjascanner' );
	}

	nscan_gclog( sprintf(
		__('Starting Garbage Collector (%s)', 'ninjascanner' ), NSCAN_CACHEDIR), 0
	);

	if (! is_dir( NSCAN_CACHEDIR ) ) {
		nscan_gclog( __('Fatal error: NSCAN_CACHEDIR does not exist. Aborting', 'ninjascanner') );
		if ( empty( $return ) ) {
			exit;
		}
		return __('Delaying garbage collector for 10 minutes, a scan is running', 'ninjascanner' );
	}

	// Build whitelisted files list:
	$wl = array( '.htaccess', 'index.html', 'snapshot.log', 'snapshot.old', 'error.txt' );

	global $count;
	$count = 0;
	nscan_gc_recursively( NSCAN_CACHEDIR, $wl );

	nscan_gclog( sprintf(
		__('Stopping Garbage Collector. Total files and folders deleted: %s', 'ninjascanner'),
		$count
	) );

	// Clear snapshot / scan report if requested
	if ( $clear_snapshot == true ) {
		if ( file_exists( NSCAN_SNAPSHOT ) ) {
			unlink( NSCAN_SNAPSHOT );
		}
		if ( file_exists( NSCAN_OLD_SNAPSHOT ) ) {
			unlink( NSCAN_OLD_SNAPSHOT );
		}
	}

	// Cleanup temporary files
	nscan_cleanup_tempfiles();

	if ( empty( $return ) ) {
		exit;
	}

	return;
}

// =====================================================================
// Recursively delete files and folders.

function nscan_gc_recursively( $dir, $wl ) {

	global $count;
	if ( is_dir( $dir ) ) {
		$files = scandir( $dir );
		foreach ( $files as $file ) {
			if ( $file == '.' || $file == '..' ) { continue; }
			if ( is_dir( "$dir/$file" ) ) {
				nscan_gc_recursively( "$dir/$file", $wl );
			} else {
				if ( in_array( $file, $wl ) ) {
					nscan_gclog( sprintf( __('Keeping file %s', 'ninjascanner'), $file ) );
					continue;
				}
				nscan_gclog( sprintf( __('Deleting file %s', 'ninjascanner'), $file ) );
				unlink( "$dir/$file" );
				++$count;
			}
		}
		// Don't delete the cache root folder:
		if ( $dir != NSCAN_CACHEDIR ) {
			rmdir( $dir );
			nscan_gclog( sprintf( __('Deleting folder %s', 'ninjascanner'), $dir ) );
			++$count;
		}
	}
}

// =====================================================================
// Delay GC for 10 minutes if a scan is running.

function nscan_delay_gc( $value = 3 ) {

	if ( $value == 1 ) {
		$value = 'hourly';
	} elseif ( $value == 2 ) {
		$value = 'twicedaily';
	} else {
		$value = 'daily';
	}

	if ( wp_next_scheduled( 'nscan_garbage_collector' ) ) {
		wp_clear_scheduled_hook( 'nscan_garbage_collector' );
	}
	wp_schedule_event( time() + 600, $value, 'nscan_garbage_collector' );
}

// =====================================================================
// GC log file.

function nscan_gclog( $msg, $append = FILE_APPEND ) {

	file_put_contents(
		NSCAN_SCANDIR .'/gc.log',
		date( 'd-M-y H:i:s ') .	"$msg\n",
		$append
	);
}

// =====================================================================
// EOF
