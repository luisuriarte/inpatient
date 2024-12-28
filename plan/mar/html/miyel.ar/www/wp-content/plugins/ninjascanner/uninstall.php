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
 
if (! defined('WP_UNINSTALL_PLUGIN') ) { exit( "Not allowed" ); } 
 
// ===================================================================== // 
// NinjaScanner uninstaller (database + files + cron jobs). 
 
$nscan_options = get_option( 'nscan_options' ); 
 
if ( empty( $nscan_options['dont_delete_cache'] ) ) { 
 
	// Remove database options: 
	delete_option( 'nscan_options' ); 
 
	// Find and recursively delete any files and folders 
	// located inside the cache directory: 
	nscan_remove_dir_uninstall( WP_CONTENT_DIR .'/ninjascanner' ); 
} 
 
// Remove any potential cron jobs: 
if ( wp_next_scheduled( 'nscan_garbage_collector' ) ) { 
	wp_clear_scheduled_hook( 'nscan_garbage_collector' ); 
} 
if ( wp_next_scheduled( 'nscan_scheduled_scan' ) ) { 
	wp_clear_scheduled_hook( 'nscan_scheduled_scan' ); 
} 
 
return; 
 
// ===================================================================== // 
 
function nscan_remove_dir_uninstall( $dir ) { 
 
	if ( is_dir( $dir ) ) { 
		$files = scandir( $dir ); 
		foreach ( $files as $file ) { 
			if ( $file == '.' || $file == '..' ) { continue; } 
			if ( is_dir( "$dir/$file" ) ) { 
				nscan_remove_dir_uninstall( "$dir/$file" ); 
			} else { 
				unlink( "$dir/$file" ); 
			} 
		} 
		rmdir( $dir ); 
	} 
} 
 
// ===================================================================== 
// EOF 
