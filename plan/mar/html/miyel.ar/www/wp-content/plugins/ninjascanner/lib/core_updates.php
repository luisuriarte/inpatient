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
 
$nscan_options = get_option( 'nscan_options' ); 
 
// --------------------------------------------------------------------- 
// Version 1.2 introduces the quarantine sandbox 
if (! isset( $nscan_options['sandbox'] ) ) { 
	$nscan_options['sandbox'] = 1; 
	$update_needed = 1; 
} 
// Version 2.0.5 introduces optional syntax highlighting 
if (! isset( $nscan_options['highlight'] ) ) { 
	$nscan_options['highlight'] = 1; 
	$update_needed = 1; 
} 
// Version 3.0 
if (! isset( $nscan_options['scan_root_folders'] ) ) { 
	$nscan_options['scan_root_folders'] = ''; 
	$nscan_options['mutipagereport'] = 0; 
	unset( $nscan_options['scan_incremental'] ); 
	unset( $nscan_options['scan_incremental_forced'] ); 
	unset( $nscan_options['scan_zipcrc'] ); 
	$update_needed = 1; 
} 
 
// --------------------------------------------------------------------- 
 
if (! empty( $update_needed ) ) { 
	// Update options: 
	update_option( 'nscan_options', $nscan_options ); 
} 
 
// ===================================================================== 
// EOF 
