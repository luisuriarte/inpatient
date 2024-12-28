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
// Show the selected tab and page. 
 
function nscan_main_menu() { 
 
	$tab = array ( 'summary', 'settings', 'quarantine', 
						'log', 'premium', 'about', 'ignored' ); 
	// Make sure $_GET['nscantab']'s value is okay, 
	// otherwise set it to its default 'summary' value: 
	if (! isset( $_GET['nscantab'] ) || ! in_array( $_GET['nscantab'], $tab ) ) { 
		$_GET['nscantab'] = 'summary'; 
	} 
	$nscan_menu = "nscan_menu_{$_GET['nscantab']}"; 
	$nscan_menu(); 
 
} 
 
// ===================================================================== 
// Display (in)active tabs. 
 
function nscan_display_tabs( $which ) { 
 
	$t1 = ''; $t2 = ''; $t3 = ''; $t4 = ''; $t5 = ''; $t6 = ''; $t7 = ''; 
 
	if ( $which == 1 ) { 
		$t1 = ' nav-tab-active'; 
	} elseif ( $which == 2 ) { 
		$t2 = ' nav-tab-active'; 
	} elseif ( $which == 3 ) { 
		$t3 = ' nav-tab-active'; 
	} elseif ( $which == 4 ) { 
		$t4 = ' nav-tab-active'; 
	} elseif ( $which == 5 ) { 
		$t5 = ' nav-tab-active'; 
	} elseif ( $which == 6 ) { 
		$t6 = ' nav-tab-active'; 
	} elseif ( $which == 7 ) { 
		$t7 = ' nav-tab-active'; 
	} 
	?> 
	<h1>NinjaScanner</h1> 
 
	<h2 class="nav-tab-wrapper wp-clearfix"> 
		<a href="?page=NinjaScanner&nscantab=summary" class="nav-tab<?php 
			echo $t1 ?>"><?php _e( 'Summary', 'ninjascanner' ) ?></a> 
		<a href="?page=NinjaScanner&nscantab=settings" class="nav-tab<?php 
			echo $t2 ?>"><?php _e( 'Settings', 'ninjascanner' ) ?></a> 
		<a href="?page=NinjaScanner&nscantab=quarantine" class="nav-tab<?php 
			echo $t6 ?>"><?php _e( 'Quarantine', 'ninjascanner' ) ?></a> 
		<a href="?page=NinjaScanner&nscantab=ignored" class="nav-tab<?php 
			echo $t7 ?>"><?php _e( 'Ignored', 'ninjascanner' ) ?></a> 
		<?php 
 
		$nscan_options = get_option( 'nscan_options' ); 
		// Show debugging log? 
		if (! empty( $nscan_options['scan_debug_log'] ) ) { 
		?> 
			<a href="?page=NinjaScanner&nscantab=log" class="nav-tab<?php 
			echo $t3 ?>"><?php _e( 'Log', 'ninjascanner' ) ?></a> 
		<?php 
		} 
		?> 
		<a href="?page=NinjaScanner&nscantab=premium" class="nav-tab<?php 
			echo $t4 ?>"><?php _e( 'Premium', 'ninjascanner' ) ?></a> 
 
		<a href="?page=NinjaScanner&nscantab=about" class="nav-tab<?php 
			echo $t5 ?>"><?php _e( 'About', 'ninjascanner' ) ?></a> 
 
		<div style="text-align:center;font-weight:normal;"> 
			<span class="description" style="color:#808080;vertical-align:text-bottom;"><?php 
			_e('Click on the above "Help" tab for help.', 'ninjascanner') ?></span></div> 
	</h2> 
	<?php 
} 
// ===================================================================== 
// Summary/report page. 
 
function nscan_menu_summary() { 
 
	echo '<div class="wrap">'; 
	require_once __DIR__ . '/tab_summary.php'; 
	echo '</div>'; 
} 
 
// ===================================================================== 
// Settings page. 
 
function nscan_menu_settings() { 
 
	echo '<div class="wrap">'; 
	require_once __DIR__ . '/tab_settings.php'; 
	echo '</div>'; 
} 
 
// ===================================================================== 
// Quarantined files. 
 
function nscan_menu_quarantine() { 
 
	echo '<div class="wrap">'; 
	require_once __DIR__ . '/tab_quarantine.php'; 
	echo '</div>'; 
} 
 
// ===================================================================== 
// Scanner's debugging log page. 
 
function nscan_menu_log() { 
 
	echo '<div class="wrap">'; 
	require_once __DIR__ . '/tab_log.php'; 
	echo '</div>'; 
} 
 
// ===================================================================== 
// Ignored list. 
 
function nscan_menu_ignored() { 
 
	echo '<div class="wrap">'; 
	require_once __DIR__ . '/tab_ignored.php'; 
	echo '</div>'; 
} 
 
// ===================================================================== 
 
function nscan_menu_premium() { 
 
	echo '<div class="wrap">'; 
	require_once __DIR__ . '/tab_premium.php'; 
	echo '</div>'; 
} 
 
// ===================================================================== 
// Copyright/about page. 
 
function nscan_menu_about() { 
 
	echo '<div class="wrap">'; 
	require_once __DIR__ . '/tab_about.php'; 
	echo '</div>'; 
 
} 
 
 
// ===================================================================== 
// EOF 
