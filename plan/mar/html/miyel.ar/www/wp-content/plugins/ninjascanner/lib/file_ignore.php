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
 
// Add to ignore list: 
$ignored_list = array(); 
if ( file_exists( NSCAN_IGNORED_LOG ) ) { 
	$ignored_list = unserialize( file_get_contents( NSCAN_IGNORED_LOG ) ); 
} 
$ctime = filectime( $file ); 
$ignored_list[$file] = $ctime; 
file_put_contents( NSCAN_IGNORED_LOG, serialize( $ignored_list ) ); 
 
// ===================================================================== 
// EOF 
