<?php
/*
Plugin Name: NinjaScanner
Plugin URI: https://nintechnet.com/ninjascanner/
Description: A lightweight, fast and powerful virus scanner for WordPress.
Author: The Ninja Technologies Network
Author URI: https://nintechnet.com/
Version: 3.2.4
License: GPLv3 or later
Network: true
Text Domain: ninjascanner
Domain Path: /languages
*/

define('NSCAN_VERSION', '3.2.4');

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

if (! defined('ABSPATH') ) {
	die('Forbidden');
}

// ===================================================================== 2023-06-07
// Both constants are used by NinjaFirewall:
define('NSCAN_NAME', 'NinjaScanner');
define('NSCAN_SLUG', 'ninjascanner');

// Constants & variables
require_once __DIR__ .'/lib/constants.php';

// ===================================================================== 2023-06-07
// Load (force) our translation files.

$ns_locale = ['fr_FR'];
$this_locale = get_locale();
if ( in_array( $this_locale, $ns_locale ) ) {
	if ( file_exists( __DIR__ ."/languages/ninjascanner-{$this_locale}.mo") ) {
		unload_textdomain('ninjascanner');
		load_textdomain(
			'ninjascanner',
			__DIR__ ."/languages/ninjascanner-{$this_locale}.mo"
		);
	}
}
// ===================================================================== 2023-06-07
// Helpers
require __DIR__ .'/lib/utils.php';
// AJAX hooks
require __DIR__ .'/lib/ajax_hooks.php';
// User interface functions
require __DIR__ .'/lib/ui.php';

// ===================================================================== 2023-06-07
// Activation: make sure the blog meets the requirements.

function nscan_activate() {

	if (! defined('WP_CLI') && ! current_user_can('activate_plugins') ) {
		exit( esc_html__('Your are not allowed to activate or deactivate '.
			'plugins.', 'ninjascanner') );
	}

	global $wp_version;
	if ( version_compare( $wp_version, '4.7.0', '<') ) {
		exit( sprintf(
			esc_html__('NinjaScanner requires WordPress %s or greater but '.
			'your current version is %s.', 'ninjascanner'),
			'4.7.0',
			esc_html( $wp_version )
		) );
	}

	if ( version_compare( PHP_VERSION, '7.1', '<') ) {
		exit( sprintf(
			esc_html__('NinjaScanner requires PHP %s or greater but your '.
			'current version is %s.', 'ninjascanner'),
			'7.1',
			esc_html( PHP_VERSION )
		) );
	}

	if ( PATH_SEPARATOR == ';') {
		exit( esc_html__('NinjaScanner is not compatible with Microsoft '.
		'Windows.', 'ninjascanner') );
	}


	$nscan_options = get_option('nscan_options');
	require_once __DIR__ .'/lib/install.php';

	// This is not the first time we run:
	if ( isset( $nscan_options['scan_scheduled'] ) ) {
		// Restore the cron jobs:
		nscan_default_gc( $nscan_options['scan_garbage_collector'] );
		nscan_default_sc( $nscan_options['scan_scheduled'] );
		return;
	}

	// First run: get and save default settings:
	$nscan_options = [];
	$nscan_options = nscan_default_settings();
	update_option('nscan_options', $nscan_options );

	// Setup the garbage collector (via WP-cron):
	nscan_default_gc( $nscan_options['scan_garbage_collector'] );

}

register_activation_hook( __FILE__, 'nscan_activate');

// ===================================================================== 2023-06-07
// Deactivation: stop any cron.

function nscan_deactivate() {

	if (! defined('WP_CLI') && ! current_user_can('activate_plugins') ) {
		exit( esc_html__('Your are not allowed to activate or deactivate '.
		'plugins.', 'ninjascanner') );
	}

	$nscan_options = get_option('nscan_options');
	require_once 'lib/install.php';
	nscan_default_gc(0);
	nscan_default_sc(0);
}

register_deactivation_hook( __FILE__, 'nscan_deactivate');

// ===================================================================== 2023-06-07
// Run the garbage collector to clean-up the cached folder.

require_once __DIR__ .'/lib/gc.php';
add_action('nscan_garbage_collector', 'nscan_gc');

// ===================================================================== 2023-06-07
// View the file or compare it to the original one. Applies to WordPress
// core files or to themes/plugins available in the wordpress.org repo.
// Additionally, adjust options if we just update the plugin to a newer
// version.

function nscan_init() {

	// Admin/Superadmin only:
	if (! current_user_can('manage_options') ) {
		return;
	}

	// Load the selected file(s) in the pop-up window:
	if ( isset( $_GET['page'] ) &&
			$_GET['page'] == 'NinjaScanner' &&
			isset( $_GET['nscanop'] ) &&
			in_array( $_GET['nscanop'], ['view', 'compare'] ) ) {

		// Verify security nonce:
		if ( empty( $_GET['nscanop_nonce'] ) ||
			! wp_verify_nonce( $_GET['nscanop_nonce'], 'nscan_file_op') ) {

			wp_nonce_ays('nscan_file_op');
		}

		if ( empty( $_GET['file'] ) ) {
			wp_die( sprintf(
				esc_html__('Missing or incorrect parameter: %s', 'ninjascanner'),
				'file'
			) );
		}
		$file = base64_decode( $_GET['file'] );
		ns_win_or_linux( $file );

		// File must exist:
		if (! file_exists( $file ) ) {
			wp_die( sprintf(
				esc_html__('File does not exist: %s', 'ninjascanner'),
				esc_html( $file )
			) );
		}
		// File must be readable:
		if (! is_readable( $file ) ) {
			wp_die( sprintf(
				esc_html__('File cannot be read: %s', 'ninjascanner'),
				esc_html( $file )
			) );
		}

		// File must be in the ABSPATH or DOCUMENT_ROOT (or the folder above them):
		if ( empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
			$path_docroot = dirname( realpath( ABSPATH ) );
		} else {
			$path_docroot = dirname( realpath( $_SERVER['DOCUMENT_ROOT'] ) );
		}
		$path_abs = dirname( realpath( ABSPATH ) );
		if (! preg_match("`^($path_abs|$path_docroot)`", realpath( $file ) ) ) {
			wp_die( sprintf(
				esc_html__(
					'File is not in the ABSPATH or DOCUMENT_ROOT: %s',
					'ninjascanner'
				),
				esc_html( $file )
			) );
		}

		// View file:
		if ( $_GET['nscanop'] == 'view') {
			require __DIR__ .'/lib/file_view.php';

		// Compare the file to the original one:
		} else {
			require __DIR__ .'/lib/file_compare.php';
		}

		exit;
	}
	// Updates may require to adjust the current configuration:
	require __DIR__ .'/lib/core_updates.php';
}

add_action('admin_init', 'nscan_init');

// ===================================================================== 2023-06-07
// Display settings link in the "Plugins" page.

function nscan_settings_link( $links ) {

	if ( is_multisite() ) {
		$net = 'network/';
	} else {
		$net = '';
	}
	$get_admin_url = get_admin_url(null, "{$net}admin.php?page=NinjaScanner");

	// If a scanning process is running, we remove
	// the "Deactivate" link and add a warning instead:
	$lock_status = json_decode( nscan_is_scan_running(), true );
	if ( $lock_status['status'] == 'success') {
		$links[] = "<a href='{$get_admin_url}'>".
					esc_html__('A scan is running...', 'ninjascanner') .'</a>';
		unset( $links['edit'] );
		unset( $links['deactivate'] );
		return $links;
	}

	$links[] = "<a href='{$get_admin_url}'>".
					esc_html__('Settings', 'ninjascanner') .'</a>';
	$links[] = '<a href="https://wordpress.org/support/view/plugin-reviews/ninjascanner?'.
					'rate=5#postform" target="_blank">'.
					esc_html__('Rate it!', 'ninjascanner') .'</a>';
	unset( $links['edit'] );
	return $links;
}

if ( is_multisite() ) {
	add_filter(
		'network_admin_plugin_action_links_'. plugin_basename(__FILE__),
		'nscan_settings_link'
	);
} else {
	add_filter(
		'plugin_action_links_'. plugin_basename(__FILE__),
		'nscan_settings_link'
	);
}

// ===================================================================== 2023-06-07
// WP CLI commands.

if ( defined('WP_CLI') && WP_CLI ) {
	require_once __DIR__ .'/lib/cli.php';
}

// ===================================================================== 2023-06-07
// Create NinjaScanner menu. It can be, however, integrated into
// NinjaFirewall own menu too.

function nscan_admin_menu() {

	if (! is_main_site() ) {
		return;
	}

	// If NinjaFirewall (>3.5.4) is installed and enabled and the user
	// activated the "NinjaFirewall menu integration" option, we don't
	// display any menu item:
	$nscan_options = get_option('nscan_options');
	if ( ( is_plugin_active('ninjafirewall/ninjafirewall.php')
		|| is_plugin_active('nfwplus/nfwplus.php') ) &&
		! empty( $nscan_options['scan_nfwpintegration'] ) &&
		defined('NFW_ENGINE_VERSION') && version_compare( NFW_ENGINE_VERSION, '3.5.4', '>') ) {

		define('NSCAN_NFWP', true );
		return;
	}

	$menuhook = add_menu_page(
		'NinjaScanner',
		'NinjaScanner',
		// In a multisite environment, only the superadmin can run it
		'manage_options',
		'NinjaScanner',
		'nscan_main_menu'
	);
	// Load contextual help
	require_once plugin_dir_path( __FILE__ ) .'lib/help.php';
	add_action("load-{$menuhook}", 'nscan_help');

}
// Must load after NinjaFirewall (10)
if (! is_multisite() )  {
	add_action('admin_menu', 'nscan_admin_menu', 11 );
} else {
	add_action('network_admin_menu', 'nscan_admin_menu', 11 );
}

// ===================================================================== 2023-06-07

function nscan_insert_jscss() {

	// Load the external JS script and CSS:
	// -Single site: to the admin only.
	// -Multisite: to the superadmin from the main network admin screen only.
	if (! current_user_can('manage_options') || ! is_main_site() ) {
		return;
	}

	wp_enqueue_script(
		'nscan_javascript',
		// force reload after update (some plugins remove the version from the URI)
		plugin_dir_url( __FILE__ ) .'static/ninjascanner.js?nsv='. NSCAN_VERSION,
		['jquery']
	);

	// JS i18n
	$nscan_js_array = [
		'cannot_start' =>
			esc_attr__('The scanning process doesn\'t seem to be able to '.
			'start.', 'ninjascanner'),
		'cancel_scan' =>
			esc_attr__('Cancel the scanning process?', 'ninjascanner'),
		'error' =>
			esc_attr__('Error:', 'ninjascanner'),
		'unknown_error' =>
			esc_attr__('An unknown error occurred.', 'ninjascanner'),
		'initialising'	=>
			esc_attr__('Initialising...', 'ninjascanner'),
		'step' =>
			esc_attr__('Step', 'ninjascanner'),
		'wait' =>
			esc_attr__('Please wait...', 'ninjascanner'),
		'http_error' =>
			esc_attr__('The HTTP server returned the following error:',
			'ninjascanner'),
		'http_auth' =>
			esc_attr__('If your website is password-protected using HTTP '.
			'basic authentication, you can enter your username and password '.
			'in the "Settings > Advanced Settings" section.', 'ninjascanner'),
		'no_problem' =>
			esc_attr__('No problem detected. To refresh the list, run a '.
			'new scan.', 'ninjascanner'),
		'slow_down_scan_enable' =>
			esc_attr__('Enabling this option could slow down the scanning '.
			'process on low resource servers. Continue?', 'ninjascanner'),
		'restore_settings' =>
			esc_attr__('All fields will be restored to their default values. '.
			'Continue?', 'ninjascanner'),
		'empty_log' =>
			esc_attr__('No records were found that match the specified '.
			'search criteria.', 'ninjascanner'),
		'clear_cache_now' =>
			esc_attr__('Run the garbage collector now to clear all cached '.
			'files?', 'ninjascanner'),
		'unknown_action' =>
			esc_attr__('Unknown action.', 'ninjascanner'),
		'select_elements' =>
			esc_attr__('No file selected.', 'ninjascanner'),
		'permanently_delete' =>
			esc_attr__('Permanently delete the selected files?',
			'ninjascanner'),
		'restore_file' =>
			esc_attr__('Restore the selected files to their original '.
			'folder?', 'ninjascanner'),
		'empty_apikey' =>
			esc_attr__('Please enter your API key.', 'ninjascanner'),
		'success_apikey' =>
			esc_attr__('Your API key is valid.', 'ninjascanner')
	];

	wp_localize_script('nscan_javascript', 'nscani18n', $nscan_js_array );

	// CSS
	wp_enqueue_style(
		'nscan_style',
		// force reload after update (some plugins remove the version from the URI)
		plugin_dir_url( __FILE__ ) .'static/ninjascanner.css?nsv='.NSCAN_VERSION
	);
}

add_action('admin_footer', 'nscan_insert_jscss');

// ===================================================================== 2023-06-07
// Load AJAX code depending on the requested page and the scan status.

if ( empty( $_GET['page'] ) || $_GET['page'] != 'NinjaScanner') {
	add_action('admin_footer', 'nscan_status_ajax_all');
}

function nscan_status_ajax_all() {

	if (! current_user_can('manage_options') || ! is_main_site() ) {
		return;
	}

	require_once 'lib/ajax_all.php';
}

// ===================================================================== 2023-06-07
// Run scheduled scan (WP-Cron).

function nscan_sched_cron() {

	// Make sure no scan is running:
	$lock_status = json_decode( nscan_is_scan_running(), true );
	if ( $lock_status['status'] == 'success') {
		// Append line, we don't want to clear the log:
		nscan_log_error(
			__('A scanning process is running. Please wait or stop it.',
			'ninjascanner')
		);
		exit;
	}

	if ( nscan_is_valid() < 1 ) {
		require( __DIR__ . '/lib/install.php');
		nscan_default_sc(0);
		$nscan_options['scan_scheduled'] = 0;
		update_option('nscan_options', $nscan_options );
		exit;
	}

	// Generate a temporary scan key:
	$_POST['nscan_key'] = nscan_generate_key();
	$_POST['first_run'] = 1;
	$return = nscan_ajax_startscan();
	if ( isset( $return['status'] ) && $return['status'] == 'error') {
		// Send an email to the admin
		nscan_error_email( $return['message'] );
	}
}

add_action('nscan_scheduled_scan', 'nscan_sched_cron');

// =====================================================================
// EOF
