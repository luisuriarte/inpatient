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
 
// ===================================================================== // 
// Check if the user enabled WP-CLI integration. 
 
function cli_nscan_is_enabled( $exp = 0 ) { 
 
	if ( empty( $exp ) ) { 
		$res = nscan_is_valid(); 
		if ( $res < 1 ) { 
			exit( __('Error: Your must have a valid Premium license to perform this operation.', 'ninjascanner') ."\n" ); 
		} 
	} 
 
	$nscan_options = get_option( 'nscan_options' ); 
	if ( empty( $nscan_options['scan_enable_wpcli'] ) ) { 
		exit( __('Warning: NinjaScanner integration with WP-CLI is disabled.', 'ninjascanner') ."\n" ); 
	} 
 
} 
 
// ===================================================================== 
// WP-CLI commands: 
 
class nscan_WPCLI extends WP_CLI_Command { 
 
	// ------------------------------------------------------------------ 
	/* 
	 * Run a scan. 
	 * 
	 */ 
 
	function start() { 
 
		cli_nscan_is_enabled(); 
 
		// Make sure we don't have a scanning process already running: 
		$lock_status = json_decode( nscan_is_scan_running(), true ); 
		if ( $lock_status['status'] == 'success' ) { 
			$message = __('A scanning process is running. Please wait or stop it.',	'ninjascanner' ); 
			WP_CLI::error( $message ); 
			exit; 
		} 
 
		// Generate a temporary scan key: 
		$_POST['nscan_key'] = nscan_generate_key(); 
 
		$return = array(); 
		$_POST['first_run'] = 1; 
		$return = json_decode( nscan_ajax_startscan(), true ); 
		if ( $return['status'] != 'success' ) { 
			$message = __('Cannot start the scan! More details may be available in the scanner log.', 'ninjascanner' ); 
			WP_CLI::error( $message ); 
			exit; 
 
		} else { 
			// All done, it's running: 
			WP_CLI::success( __('A scanning process has started.', 'ninjascanner') ); 
 
			$nscan_options = get_option( 'nscan_options' ); 
			if (! empty( $nscan_options['admin_email'] ) ) { 
				WP_CLI::log( sprintf( 
					__('A report will be sent to %s as soon as the scan has finished.', 'ninjascanner' ), 
					$nscan_options['admin_email'] 
				)); 
			} 
		} 
	} 
 
	// ------------------------------------------------------------------ 
	/* 
	 * Stop a running scan. 
	 * 
	 */ 
 
	function stop() { 
 
		cli_nscan_is_enabled(); 
 
		// Make sure we have a running scan: 
		$lock_status = json_decode( nscan_is_scan_running(), true ); 
		if ( $lock_status['status'] != 'success' ) { 
			$message = __('No scan is running!',	'ninjascanner' ); 
			WP_CLI::error( $message ); 
			exit; 
		} 
 
		// Cancel it: 
		nscan_log_info( __('Cancelling scanning process', 'ninjascanner'), false ); 
		touch( NSCAN_CANCEL ); 
 
		if ( file_exists( NSCAN_LOCKFILE ) ) { 
			if ( unlink( NSCAN_LOCKFILE ) === false ) { 
				// Cannot delete lock file: 
				$message = sprintf( 
					__('Cannot delete the lock file (%s).', 'ninjascanner' ) .' ' . 
					__('Is your filesystem read-only?', 'ninjascanner'), 
					NSCAN_LOCKFILE 
				); 
				WP_CLI::error( $message ); 
 
			} else { 
 
				// Successfully cancelled: 
				WP_CLI::success( __('The scanning process was cancelled.', 'ninjascanner' ) ); 
			} 
 
		} else { 
			// Missing lock file: 
			$message = sprintf( 
				__('Cannot find the lock file (%s).', 'ninjascanner' ) .' ' . 
				__('Is NinjaScanner running?', 'ninjascanner'), 
				NSCAN_LOCKFILE 
			); 
			WP_CLI::error( $message ); 
		} 
 
		// If there is the scanning process transient set, delete it: 
		if ( get_transient( 'nscan_ajax_start' ) !== false ) { 
			delete_transient( 'nscan_ajax_start' ); 
		} 
 
		exit; 
	} 
 
	// ------------------------------------------------------------------ 
	/* 
	 * Display the scan report. 
	 * 
	 */ 
 
	function report() { 
 
		cli_nscan_is_enabled(); 
 
		// Make sure we don't have a scanning process already running: 
		$lock_status = json_decode( nscan_is_scan_running(), true ); 
		if ( $lock_status['status'] == 'success' ) { 
			$message = __('A scanning process is running. Please wait or stop it.',	'ninjascanner' ); 
			WP_CLI::error( $message ); 
			exit; 
		} 
 
		// Populate plain text report: 
		require_once __DIR__ . '/report_text.php'; 
		$report = text_report(); 
 
		// Error? 
		if (! empty( $report['error'] ) ) { 
			WP_CLI::error( $report['error'] ); 
			exit; 
		} 
 
		WP_CLI::log( $report['body']  ); 
		exit; 
 
	} 
 
	// ------------------------------------------------------------------ 
	/* 
	 * View the debugging log. 
	 * 
	 */ 
 
	function log() { 
 
		cli_nscan_is_enabled(); 
 
		if (! file_exists( NSCAN_DEBUGLOG ) ) { 
			WP_CLI::warning( __("NinjaScanner's log is empty.", 'ninjascanner') ); 
			exit; 
		} 
 
		// Get the blog timezone: 
		nscan_get_blogtimezone(); 
 
		$lines = file( NSCAN_DEBUGLOG, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES ); 
		$error = __('Error', 'ninjascanner'); 
		$warning = __('Warning', 'ninjascanner'); 
		$facility = array( 
			1 => '', 
			2 => WP_CLI::colorize("%B$warning%n "), 
			4 => WP_CLI::colorize("%R$error%n "), 
			8 => '' 
		); 
		WP_CLI::log( sprintf( __('Viewing %s', 'ninjascanner' ), NSCAN_DEBUGLOG ) ); 
		foreach( $lines as $line ) { 
			list( $date, $level, $string ) = explode( '~~', $line, 3 ); 
			$date = date( 'd-M-y H:i:s', $date ); 
			WP_CLI::log( "$date ". $facility[$level] ."$string" ) ; 
		} 
		exit; 
	} 
 
	// ------------------------------------------------------------------ 
	/* 
	 * Check/save license. 
	 * 
	 */ 
 
	function license() { 
 
		cli_nscan_is_enabled(1); 
 
		_e('Enter your license:', 'ninjascanner' ); 
		echo ' '; 
		$input = trim( stream_get_line( STDIN, 200, PHP_EOL) ); 
 
		if ( is_multisite() ) { 
			$site_url = rtrim( strtolower( network_site_url('') ), '/' ); 
		} else { 
			$site_url = rtrim( strtolower(site_url('') ), '/' ); 
		} 
		$_SERVER['HTTP_HOST'] = parse_url( $site_url, PHP_URL_HOST); 
 
		$nscan_options = get_option( 'nscan_options' ); 
		$res = nscan_check_license( $nscan_options, $input ); 
 
		if ( empty( $res['nscan_err'] ) ) { 
			$nscan_options['key'] = $input; 
			$nscan_options['exp'] = $res['nscan_exp']; 
			update_option( 'nscan_options', $nscan_options ); 
			WP_CLI::success( __('Your license has been accepted and saved.', 'ninjascanner') ); 
		} else { 
			WP_CLI::error( $res['nscan_err'] ); 
		} 
		exit; 
	} 
 
	// ------------------------------------------------------------------ 
	/* 
	 * Display NinjaScanner status. 
	 * 
	 */ 
 
	function status() { 
 
		cli_nscan_is_enabled(); 
 
		$lock_status = json_decode( nscan_is_scan_running(), true ); 
		if ( $lock_status['status'] == 'success' ) { 
			$message = sprintf( 
				__('NinjaScanner is currently running (step %s/%s).', 'ninjascanner'), 
				$lock_status['current_step'], 
				$lock_status['total_steps'] 
			); 
		} else { 
			$message = __('NinjaScanner is not running.', 'ninjascanner'); 
		} 
		WP_CLI::log( $message ); 
		exit; 
	} 
 
	// ------------------------------------------------------------------ 
	/* 
	 * Display help screen and quit. 
	 * 
	 */ 
 
	function help() { 
 
		WP_CLI::log( "\nNinjaScanner v". NSCAN_VERSION . 
			" (c)". date('Y') ." NinTechNet ~ https://nintechnet.com/\n\n". 
			__('Available commands:', 'ninjascanner') ."\n". 
			"\twp ninjascanner help         ". __('Display this help screen', 'ninjascanner') ."\n". 
			"\twp ninjascanner start        ". __('Start a scan', 'ninjascanner') ."\n". 
			"\twp ninjascanner stop         ". __('Stop the scanning process', 'ninjascanner') ."\n". 
			"\twp ninjascanner status       ". __('Show scan status', 'ninjascanner') ."\n". 
			"\twp ninjascanner report       ". __('View the last scan report', 'ninjascanner') ."\n". 
			"\twp ninjascanner log          ". __('View the debugging log', 'ninjascanner') ."\n". 
			"\twp ninjascanner license      ". __('Enter your Premium license key', 'ninjascanner') ."\n" ); 
		exit; 
	} 
	// ------------------------------------------------------------------ 
} 
 
WP_CLI::add_command( 'ninjascanner', 'nscan_WPCLI', ['shortdesc' => 'Scan website for malware using NinjaScanner.'] ); 
 
 
// ===================================================================== 
// EOF 
