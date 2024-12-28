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
 
// ===================================================================== 
// Send scan report by email. 
 
function nscan_send_email_report( $snapshot, $nscan_options ) { 
 
	// Populate plain text report: 
	require_once __DIR__ .'/report_text.php'; 
	$report = text_report( $snapshot ); 
 
	$signature = "\nNinjaScanner - https://nintechnet.com/\n" . 
					__('Help Desk (Premium customers only):', 'ninjascanner') . 
					" https://secure.nintechnet.com/login/\n"; 
 
	if (! empty( $report['error'] ) ) { 
		// Scan failed, inform the user: 
		$subject = sprintf( 
			__('[NinjaScanner] SCAN ERROR (%s)', 'ninjascanner'), 
			$report['blog'] 
		); 
		$message = __('Hi,', 'ninjascanner') ."\n\n"; 
		$message .= __('This is the NinjaScanner scan report.', 'ninjascanner') .' '; 
		$message .= sprintf( 
			__('A fatal error occurred while attempting to generate the report: "%s"', 'ninjascanner'), 
			$report['error'] 
		); 
		$message .= "\n\n". __('More details may be available in the scanner log.', 'ninjascanner' ) ."\n"; 
		wp_mail( $nscan_options['admin_email'], $subject, $message . $signature ); 
 
	} else { 
 
		if ( empty( $nscan_options['admin_email_report'] ) || 
			( $nscan_options['admin_email_report'] == 1 && (! empty( $report['critical'] ) || ! empty( $report['important'] ) ) ) || 
			( $nscan_options['admin_email_report'] == 2 && ! empty( $report['critical'] ) ) 
		) { 
			nscan_log_debug( __('Sending email report', 'ninjascanner') ); 
 
			$attachment = ''; 
			$subject = sprintf( 
				__('[NinjaScanner] Scan report for %s', 'ninjascanner'), 
				$report['blog'] 
			); 
			$message = __('Hi,', 'ninjascanner') ."\n\n"; 
			$message .= __('This is the NinjaScanner scan report.', 'ninjascanner') .' '; 
 
			// If the report is too big (>10,000 bytes), we save it to a file 
			// and attached it, otherwise we send it inline: 
			if ( strlen( $report['body'] ) > 10000 ) { 
				$message .= __('Due to the large number of lines, it was attached '. 
						'to this email for your convenience.', 'ninjascanner' ) ."\n"; 
				$attachment = NSCAN_CACHEDIR. '/ninjascanner_report.txt'; 
				file_put_contents( $attachment, $report['body'] ); 
			} 
			$message .= __('A more detailed report can be viewed from your WordPress '. 
				'Dashboard by clicking on "NinjaScanner > Summary > View Scan Report".', 'ninjascanner' ) . 
				"\n"; 
			if ( $attachment ) { 
				wp_mail( $nscan_options['admin_email'], $subject, $message . $signature, '', $attachment ); 
				// Clear attachment file: 
				unlink( $attachment ); 
			} else { 
				// Inline: 
				wp_mail( $nscan_options['admin_email'], $subject, $message . $report['body'] . $signature, '' ); 
			} 
		} 
	} 
} 
// ===================================================================== 
// EOF 
