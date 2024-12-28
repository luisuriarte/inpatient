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
// Links pointing to misc. documentation mentioned in the settings page:

define( 'NSCAN_LINK_ADD_SIGS',
	'https://blog.nintechnet.com/ninjascanner-powerful-antivirus-scanner-for-wordpress/#signatures' );
define( 'NSCAN_LINK_INTEGRITY_CHECK',
	'https://blog.nintechnet.com/ninjascanner-powerful-antivirus-scanner-for-wordpress/#integrity' );

// =====================================================================
// URLs

// Signatures/checksums download URL:
define('NSCAN_SIGNATURES_URL', 'https://api.nintechnet.com/ninjascanner/signatures');

// WP plugins/themes URL:
define( 'NSCAN_PLUGINS_URL', 'https://downloads.wordpress.org/plugin/' );
define( 'NSCAN_THEMES_URL', 'https://downloads.wordpress.org/theme/' );

// WP SVN:
define( 'NSCAN_SVN_CORE', 'https://core.svn.wordpress.org/tags/%s' );
define( 'NSCAN_SVN_PLUGINS', 'https://plugins.svn.wordpress.org/%s/tags/%s' );
define( 'NSCAN_SVN_THEMES', 'https://themes.svn.wordpress.org/%s/%s' );

define( 'NSCAN_GSB', 'https://safebrowsing.googleapis.com/v4/threatMatches:find' );

// =====================================================================
// Steps.
// /!\ Any modification of an existing step's name will require
// to adjust ['step_error'] in the scan report (both text and HTML).
global $nscan_steps;
$nscan_steps = array (
	1	=> 'nscan_check_scanner_integrity',
	2	=> 'nscan_build_files_list',
	3	=> 'nscan_check_wordpress',
	4	=> 'nscan_get_plugins_list',
	5	=> 'nscan_check_plugins',
	6	=> 'nscan_get_themes_list',
	7	=> 'nscan_check_themes',
	8	=> 'nscan_compare_snapshots',
	9	=> 'nscan_compare_db_snapshots',
	10	=> 'nscan_check_gsb',
	11 => 'nscan_check_binaries',
	12 => 'nscan_setup_antimalware',
	13 => 'nscan_run_antimalware',
	14 => 'nscan_various_checks'
);

// =====================================================================
// Paths:

define( 'NSCAN_ROOTDIR', WP_CONTENT_DIR .'/ninjascanner' );

// Users can upload their own ZIP files (premium themes
// or plugins) into this folder:
if (! defined( 'NSCAN_LOCAL' ) ) {
	define( 'NSCAN_LOCAL', NSCAN_ROOTDIR .'/local' );
}

// Find (or create) the cache folder:
$glob = array();
$glob = glob( NSCAN_ROOTDIR .'/nscan*' );
if ( is_array( $glob ) ) {
	foreach( $glob as $file ) {
		// Must be a folder:
		if (! is_dir( "{$file}/cache" ) ) { continue; }
		// We found it:
		define( 'NSCAN_SCANDIR', $file );
		break;
	}
}
if (! defined( 'NSCAN_SCANDIR' ) ) {
	// Create it
	require_once __DIR__ .'/install.php';
	$uniqid = uniqid( 'nscan', true);
	nscan_cache_folder( $uniqid );
	define( 'NSCAN_SCANDIR', NSCAN_ROOTDIR  . "/{$uniqid}" );
}

define( 'NSCAN_CACHEDIR', NSCAN_SCANDIR  . '/cache' );
define( 'NSCAN_QUARANTINE', NSCAN_SCANDIR  . '/quarantine' );

// =====================================================================
// Files:
define( 'NSCAN_LOCKFILE', NSCAN_SCANDIR .'/.nscan.lock' );
define( 'NSCAN_CANCEL', NSCAN_SCANDIR .'/.cancel.lock' );
define( 'NSCAN_TMP_LIST', NSCAN_CACHEDIR .'/.list.tmp' );
define( 'NSCAN_FILES2CHECK', NSCAN_CACHEDIR .'/.files2check.tmp' );
define( 'NSCAN_TMP_SIGS', NSCAN_CACHEDIR .'/.sigs.tmp' ); // To delete end 2021

// Files that should be deleted upon starting/ending a scan
global $nscan_temp_files;
$nscan_temp_files = array(
	NSCAN_LOCKFILE,
	NSCAN_CANCEL,
	NSCAN_TMP_LIST,
	NSCAN_FILES2CHECK,
	NSCAN_TMP_SIGS
);
define( 'NSCAN_SNAPSHOT', NSCAN_CACHEDIR .'/snapshot.log' );
define( 'NSCAN_OLD_SNAPSHOT', NSCAN_CACHEDIR .'/snapshot.old' );
define( 'NSCAN_TMP_SNAPSHOT', NSCAN_CACHEDIR .'/snapshot.tmp' );
define( 'NSCAN_SIGNATURES', NSCAN_CACHEDIR .'/signatures.txt' );
define( 'NSCAN_DEBUGLOG', NSCAN_SCANDIR .'/debug.log' );
define( 'NSCAN_HASHFILE', NSCAN_SCANDIR . '/nscan.' . NSCAN_VERSION );
define( 'NSCAN_IGNORED_LOG', NSCAN_CACHEDIR .'/ignored.log' );

// =====================================================================
// Misc options that can be user-defined in the wp-config.php:

// Sleep time (microsecond) after spawning cron:
if (! defined( 'NSCAN_STARTSCAN_USLEEP' ) ) {
	define( 'NSCAN_STARTSCAN_USLEEP', 2000000 ); // 2s
}
// cURL timeout (seconds):
if (! defined( 'NSCAN_CURL_TIMEOUT' ) ) {
	define( 'NSCAN_CURL_TIMEOUT', 120 ); // 120s
}
// AJAX interval (milliseconds):
if (! defined( 'NSCAN_MILLISECONDS' ) ) {
	define( 'NSCAN_MILLISECONDS', 1500 ); // 1.5s
}
// Max execution time (seconds) for a PHP process
// during the antimalware scan:
if (! defined( 'NSCAN_PHPTIMEOUT' ) ) {
	define( 'NSCAN_PHPTIMEOUT', 15 ); // 15s
}
// Max validity of the scan key (minutes):
if (! defined( 'NSCAN_KEYTIMEOUT' ) ) {
	define( 'NSCAN_KEYTIMEOUT', 10 ); // 10mn
}
// =====================================================================
// EOF
