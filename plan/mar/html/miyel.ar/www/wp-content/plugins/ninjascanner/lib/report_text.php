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
// Display the scan report in plain text format. Used to send the report 
// via email (text/plain) or to display it from WP-CLI. 
 
function text_report( $snapshot = array() ) { 
 
	$NSCAN_SEP = "\n=======================================================================\n"; 
 
	$report = array(); 
 
	// Blog domain name: 
	if ( is_multisite() ) { 
		$report['blog'] = network_home_url('/'); 
	} else { 
		$report['blog'] = home_url('/'); 
	} 
 
	// Make sure we don't already have a fatal error triggered: 
	if (! empty( $snapshot['error'] ) ) { 
		// Stop here: 
		$report['error'] = $snapshot['error']; 
		return $report; 
	} 
 
	// Make sure we have a snapshot: 
	if ( empty( $snapshot ) && ! file_exists( NSCAN_SNAPSHOT ) ) { 
		$report['error'] = __("Cannot find the snapshot file, scan report cannot be created.", 'ninjascanner'); 
		return $report; 
	} 
 
	if ( empty( $snapshot ) ) { 
		$snapshot = unserialize( file_get_contents( NSCAN_SNAPSHOT ) ); 
	} 
	if ( empty( $snapshot['abspath'] ) ) { 
		$report['error'] = __("Snapshot seems corrupted (missing 'abspath' field), scan report cannot be created.", 'ninjascanner'); 
		return $report; 
	} 
 
	$report['body'] = "$NSCAN_SEP\n"; 
 
	if (! empty( $snapshot['locale'] ) ) { 
		$wordpress = "{$snapshot['version']} ({$snapshot['locale']})"; 
	} else { 
		$wordpress = "{$snapshot['version']}"; 
	} 
 
	// Scan date: 
	nscan_get_blogtimezone(); 
	$scan_date = ucfirst( date( 'F d, Y @ g:i A', filemtime( NSCAN_SNAPSHOT ) ) ); 
	$report['body'] .= __('Date', 'ninjascanner') .": $scan_date\n". 
		__('Home URL', 'ninjascanner') .": {$report['blog']}\n". 
		__('Blog directory', 'ninjascanner') .": ". ABSPATH ."\n". 
		__('WordPress Version', 'ninjascanner') .": $wordpress\n". 
		__('Total files', 'ninjascanner') .": ". number_format_i18n( count( $snapshot['abspath'] ) ) ."\n"; 
 
	$report['body'] .= $NSCAN_SEP; 
	// ========================================================================== 
	// WordPress core files integrity 
	// Mismatched checksums: 
	$report['body'] .= mb_convert_case( __('WordPress core files integrity', 'ninjascanner'), MB_CASE_UPPER, "UTF-8") ."\n\n"; 
 
	// Error? 
	if (! empty( $snapshot['step_error']['nscan_check_wordpress'] ) ) { 
		$report['body'] .= '> '. __('Warning, a critical error occurred. This test was cancelled.', 'ninjascanner') ."\n"; 
 
	// Skipped? 
	} elseif (! empty( $snapshot['skip']['scan_wpcoreintegrity'] ) ) { 
			$report['body'] .= '> '. __('This test was skipped', 'ninjascanner') ."\n"; 
 
	} else { 
		if (! empty( $snapshot['core_failed_checksum'] ) ) { 
			$report['critical'] = 1; 
			$count = count( $snapshot['core_failed_checksum'] ); 
			$report['body'] .= '-'. sprintf( __('Modified core files: %s', 'ninjascanner'), $count ) ."\n"; 
			$report['body'] .= __('The following files do not match the original WordPress core files.', 'ninjascanner'); 
			// Inform users that they can repair their broken WP with a one-click button: 
			if ( is_multisite() ) {	$net = 'network/'; } else { $net = '';	} 
			$report['body'] .= ' '. __('If they are damaged or corrupted, you can easily repair them by selecting a file and clicking on the "Restore file" button', 'ninjascanner' ) .":\n\n"; 
			foreach( $snapshot['core_failed_checksum'] as $file => $null ) { 
				$relative = str_replace( ABSPATH, '', $file ); 
				// Populate list: 
				$report['body'] .= "./$relative\n"; 
			} 
		} else { 
			$report['body'] .= "-WordPress core\n"; 
			$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n"; 
		} 
 
		$report['body'] .= "\n"; 
 
		// Unknown and suspicious files (Critical level): 
		if (! empty( $snapshot['core_unknown'] ) ) { 
			$report['critical'] = 1; 
			$count = count( $snapshot['core_unknown'] ); 
			$report['body'] .= '-'. sprintf( __('Additional/suspicious files: %s', 'ninjascanner'), $count ) ."\n"; 
			$report['body'] .= __('The following files are not part of the original WordPress package and may have been uploaded by someone else:', 'ninjascanner') ."\n\n"; 
			foreach( $snapshot['core_unknown'] as $file => $null ) { 
				$relative = str_replace( ABSPATH, '', $file ); 
				// Populate list: 
				$report['body'] .= "./$relative\n"; 
			} 
		} 
 
		$report['body'] .= "\n"; 
 
		// Unknown (and suspicious) files (Important level): 
		if (! empty( $snapshot['core_unknown_root'] ) ) { 
			$report['important'] = 1; 
			$count = count( $snapshot['core_unknown_root'] ); 
			$report['body'] .= '-'. sprintf( __('Unknown files: %s', 'ninjascanner'), $count ) ."\n"; 
			$report['body'] .= __('The following files are not part of the original WordPress package and may have been uploaded by someone else:', 'ninjascanner') ."\n\n"; 
			foreach( $snapshot['core_unknown_root'] as $file => $null ) { 
				$relative = str_replace( ABSPATH, '', $file ); 
				// Populate list: 
				$report['body'] .= "./$relative\n"; 
			} 
		} 
	} 
 
	$report['body'] .= $NSCAN_SEP; 
	// ========================================================================== 
	// Plugin files integrity 
	$total = 0; 
	$report['body'] .= mb_convert_case( __('Plugin files integrity', 'ninjascanner'), MB_CASE_UPPER, "UTF-8" ) ."\n\n"; 
	if (! empty( $snapshot['skip']['scan_pluginsintegrity'] ) ) { 
		$report['body'] .= '> '. __('This test was skipped', 'ninjascanner') ."\n"; 
 
	} else { 
		// Build the lists of modified or added files, and unknown plugins: 
		if (! empty( $snapshot['plugins'] ) ) { 
			$ok_plugin = array(); 
			$additional_plugin = array(); 
			$mismatched_plugin = array(); 
			foreach( $snapshot['plugins'] as $slug => $arr ) { 
				if (! empty( $snapshot['plugins'][$slug] ) ) { 
					foreach( $arr as $k => $v ) { 
						if ( $v == 1 ) { 
							$mismatched_plugin[] = "$slug/$k"; 
						} else { 
							$additional_plugin[] = "$slug/$k"; 
						} 
					} 
				} else { 
					// OK plugins: 
					$ok_plugin[] = $slug; 
				} 
			} 
			// OK plugin files: 
			if (! empty( $ok_plugin ) ) { 
				if ( empty( $mismatched_plugin ) && empty( $additional_plugin ) && 
					empty( $snapshot['plugins_unknown'] ) && empty( $snapshot['plugins_not_found'] ) && 
					empty( $snapshot['mu_plugins'] ) ) { 
					// Plugins are all OK: 
					$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n"; 
				} else { 
					$report['body'] .= '-'. __('The following plugin files match the original files:', 'ninjascanner' ) ."\n\n"; 
					sort( $ok_plugin ); 
					foreach( $ok_plugin as $slug ) { 
						$report['body'] .= $slug ."\n"; 
					} 
					$report['body'] .= "\n"; 
				} 
			} 
 
			// Modified plugin files: 
			if (! empty( $mismatched_plugin ) ) { 
				$report['critical'] = 1; 
				$count = count( $mismatched_plugin ); 
				$report['body'] .= '-'. sprintf( __('Modified plugin files: %s', 'ninjascanner'), $count ) ."\n"; 
				$report['body'] .= __('The following files do not match the original plugin files and may have been tampered with.', 'ninjascanner') .' '. __('If they are damaged or corrupted, you can easily repair them by selecting a file and clicking on the "Restore file" button', 'ninjascanner' ) .":\n\n"; 
				foreach( $mismatched_plugin as $k => $v ) { 
					$file = WP_PLUGIN_DIR . "/$v"; 
					$relative = str_replace( ABSPATH , '', $file ); 
					// Populate list: 
					$report['body'] .= "./$relative\n"; 
				} 
				$report['body'] .= "\n"; 
			} 
 
			// Unknown plugins dropped inside the /plugins/ folder: 
			if (! empty( $snapshot['plugins_unknown'] ) ) { 
				foreach( $snapshot['plugins_unknown'] as $slug => $arr ) { 
					$additional_plugin[] = $slug; 
				} 
			} 
 
			// Additional/suspicious plugin files: 
			if (! empty( $additional_plugin ) ) { 
				$report['critical'] = 1; 
				$count = count( $additional_plugin ); 
				$report['body'] .= '-'. sprintf( __('Additional/suspicious files: %s', 'ninjascanner'), $count ) ."\n"; 
				$report['body'] .= __('The following files are not part of any plugin package and may have been uploaded by someone else:', 'ninjascanner') ."\n\n"; 
				foreach( $additional_plugin as $k => $v ) { 
					$file = WP_PLUGIN_DIR . "/$v"; 
					$relative = str_replace( ABSPATH , '', $file ); 
					// Populate list: 
					$report['body'] .= "./$relative\n"; 
				} 
				$report['body'] .= "\n"; 
			} 
		} 
 
		if (! empty( $snapshot['plugins_not_found'] ) ) { 
			// Warn about premium or unknown plugins that couldn't be verified 
			// because they aren't available in the wordpress.org repo: 
			$report['body'] .= '-'. __('The following plugin packages could not be compared to the original ones for some reason (more information may be available in the scanner log). If they are premium plugins, consult the documentation to learn how you can include them in the file integrity checker. Make sure those plugins were not tampered with or installed by someone else:', 'ninjascanner') ."\n\n"; 
			ksort( $snapshot['plugins_not_found'] ); 
			foreach( $snapshot['plugins_not_found'] as $slug => $version ) { 
				$report['body'] .= $slug .' '. $version ."\n"; 
			} 
			$report['body'] .= "\n"; 
		} 
 
		if (! empty( $snapshot['mu_plugins'] ) ) { 
			// Warn about MU plugins: 
			$report['important'] = 1; 
			$count = count( $snapshot['mu_plugins'] ); 
			$report['body'] .= '-'. sprintf( __('Must-Use plugins: %s', 'ninjascanner'), $count ) ."\n"; 
			$report['body'] .= __('The following files are "Must-Use" plugins (plugins that are automatically enabled and loaded before normal plugins) and could not be compared to the original ones. Make sure those plugins were not tampered with or installed by someone else:', 'ninjascanner') ."\n\n"; 
			foreach( $snapshot['mu_plugins'] as $slug => $version ) { 
				$file = str_replace( ABSPATH, '', WP_CONTENT_DIR ."/mu-plugins/$slug" ); 
				$report['body'] .= "./$file\n"; 
			} 
			$report['body'] .= "\n"; 
		} 
 
		if (! empty( $snapshot['plugins_dropins'] ) ) { 
			// Warn about drop-ins plugins: 
			$report['important'] = 1; 
			$count = count( $snapshot['plugins_dropins'] ); 
			$report['body'] .= '-'. sprintf( __('Drop-Ins plugins: %s', 'ninjascanner'), $count ) ."\n"; 
			$report['body'] .= __('The following files are "Drop-Ins" plugins (plugins that can be used to replace some core functionality of WordPress) and could not be compared to the original ones. Make sure those plugins were not tampered with or installed by someone else:', 'ninjascanner') ."\n\n"; 
			foreach( $snapshot['plugins_dropins'] as $k => $v ) { 
				$file = WP_CONTENT_DIR . "/$k"; 
				$relative = str_replace( ABSPATH , '', $file ); 
				// Populate list: 
				$report['body'] .= "./$relative\n"; 
			} 
		} 
 
		// No plugin found! 
		if ( empty( $snapshot['plugins'] ) && empty( $snapshot['plugins_not_found'] ) && 
		empty( $snapshot['plugins_unknown'] ) && empty( $snapshot['plugins_dropins'] ) ) { 
			$report['body'] .= '> '. __('No plugins were found!', 'ninjascanner') ."\n"; 
		} 
	} 
 
	$report['body'] .= $NSCAN_SEP; 
	// ========================================================================== 
	// Theme files integrity 
	$total = 0; 
	$report['body'] .= mb_convert_case( __('Theme files integrity', 'ninjascanner'), MB_CASE_UPPER, "UTF-8" ) ."\n\n"; 
	if (! empty( $snapshot['skip']['scan_themeseintegrity'] ) ) { 
		$report['body'] .= '> '. __('This test was skipped', 'ninjascanner') ."\n"; 
 
	} else { 
		// Build the lists of modified or added theme files: 
		if (! empty( $snapshot['themes'] ) ) { 
			$ok_theme = array(); 
			$additional_theme = array(); 
			$mismatched_theme = array(); 
			foreach( $snapshot['themes'] as $slug => $arr ) { 
				if (! empty( $snapshot['themes'][$slug] ) ) { 
					foreach( $arr as $k => $v ) { 
 
						if ( $v == 1 ) { 
							$mismatched_theme[] = "$slug/$k"; 
						} else { 
							$additional_theme[] = "$slug/$k"; 
						} 
					} 
				} else { 
					// OK themes: 
					$ok_theme[] = $slug; 
				} 
			} 
			// OK theme files: 
			if (! empty( $ok_theme ) ) { 
				if ( empty( $mismatched_theme ) && empty( $additional_theme ) && empty( $snapshot['themes_not_found'] ) ) { 
					// Themes are all OK: 
					$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n"; 
				} else { 
					$report['body'] .= __('The following theme files match the original files:', 'ninjascanner' ) ."\n\n"; 
 
 
 
 
 
					sort( $ok_theme ); 
					foreach( $ok_theme as $slug ) { 
						$report['body'] .= $slug ."\n"; 
					} 
					$report['body'] .= "\n"; 
				} 
			} 
 
			// Modified theme files: 
			if (! empty( $mismatched_theme ) ) { 
				$report['critical'] = 1; 
				$count = count( $mismatched_theme ); 
				$report['body'] .= '-'. sprintf( __('Modified theme files: %s', 'ninjascanner'), $count ) ."\n"; 
				$report['body'] .= __('The following files do not match the original theme files and may have been tampered with.', 'ninjascanner') .' '. __('If they are damaged or corrupted, you can easily repair them by selecting a file and clicking on the "Restore file" button', 'ninjascanner' ) .":\n\n"; 
				foreach( $mismatched_theme as $k => $v ) { 
					$file = WP_CONTENT_DIR . "/themes/$v"; 
					$relative = str_replace( ABSPATH , '', $file ); 
					// Populate list: 
					$report['body'] .= "./$relative\n"; 
				} 
				$report['body'] .= "\n"; 
			} 
			// Additional/suspicious theme files: 
			if (! empty( $additional_theme ) ) { 
				$report['critical'] = 1; 
				$count = count( $additional_theme ); 
				$report['body'] .= '-'. sprintf( __('Additional/suspicious files: %s', 'ninjascanner'), $count ) ."\n"; 
				$report['body'] .= __('The following files are not part of any theme package and may have been uploaded by someone else:', 'ninjascanner') ."\n\n"; 
				foreach( $additional_theme as $k => $v ) { 
					$file = WP_CONTENT_DIR . "/themes/$v"; 
					$relative = str_replace( ABSPATH , '', $file ); 
					// Populate list: 
					$report['body'] .= "./$relative\n"; 
				} 
				$report['body'] .= "\n"; 
			} 
		} 
 
		if (! empty( $snapshot['themes_not_found'] ) ) { 
			// Warn about premium or unknown themes that couldn't be verified 
			// because they aren't available in the wordpress.org repo: 
			$report['body'] .= '-'. __('The following theme packages could not be compared to the original ones for some reason (more information may be available in the scanner log). If they are premium themes, consult the documentation to learn how you can include them in the file integrity checker. Make sure those themes were not tampered with or installed by someone else:', 'ninjascanner') ."\n\n"; 
			ksort( $snapshot['themes_not_found'] ); 
			foreach( $snapshot['themes_not_found'] as $slug => $version ) { 
				$report['body'] .= $slug .' '. $version ."\n"; 
			} 
		} 
 
		// No themes found (highly unlikely): 
		if( empty( $snapshot['themes_not_found'] ) && empty( $snapshot['themes'] ) ) { 
			$report['body'] .= '> '. __('No themes were found!', 'ninjascanner') ."\n"; 
		} 
	} 
 
 
	$report['body'] .= $NSCAN_SEP; 
	// ========================================================================== 
	// Files & folders 
	$report['body'] .= mb_convert_case( __('Files and folders', 'ninjascanner'), MB_CASE_UPPER, "UTF-8" ) ."\n\n"; 
 
	// Hidden PHP scripts: 
	if (! empty( $snapshot['skip']['core_hidden'] ) ) { 
		$report['body'] .= '-'. __('Hidden scripts', 'ninjascanner') ."\n"; 
		$report['body'] .= '> '. __('This test was skipped', 'ninjascanner') ."\n"; 
 
	} else { 
		if (! empty( $snapshot['core_hidden'] ) ) { 
			$report['critical'] = 1; 
			$count = count( $snapshot['core_hidden'] ); 
			$report['body'] .= '-'. sprintf( __('Hidden scripts: %s', 'ninjascanner'), $count ) ."\n"; 
			$report['body'] .= __('The following files are hidden PHP scripts:', 'ninjascanner') ."\n\n"; 
			foreach( $snapshot['core_hidden'] as $file => $null ) { 
				$relative = str_replace( ABSPATH, '', $file ); 
				// Populate list: 
				$report['body'] .= "./$relative\n"; 
			} 
		} else { 
			$report['body'] .= '-'. __('Hidden scripts', 'ninjascanner') ."\n"; 
			$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n"; 
		} 
	} 
	$report['body'] .= "\n"; 
 
	// Binary files 
	if (! empty( $snapshot['skip']['core_binary'] ) ) { 
		$report['body'] .= '-'. __('Executable files', 'ninjascanner') ."\n"; 
		$report['body'] .= '> '. __('This test was skipped', 'ninjascanner') ."\n"; 
 
	} else { 
		if (! empty( $snapshot['core_binary'] ) ) { 
			$report['important'] = 1; 
			$count = count( $snapshot['core_binary'] ); 
			$report['body'] .= '-'. sprintf( __('Executable files: %s', 'ninjascanner'), $count ) ."\n"; 
			$report['body'] .= __('The following files are executable files:', 'ninjascanner') ."\n\n"; 
			foreach( $snapshot['core_binary'] as $file => $null ) { 
				$relative = str_replace( ABSPATH, '', $file ); 
				$fh = fopen( $file, 'r' ); 
				if ( ( $data = fread( $fh, 4 ) ) !== false ) { 
					if ( preg_match('`^\x7F\x45\x4C\x46`', $data ) ) { 
						$header = 'ELF executable format'; 
					} elseif( preg_match('`^\x4D\x5A`', $data ) ) { 
						$header = 'Microsoft MZ executable format'; 
					} 
				} 
				fclose( $fh ); 
				if ( empty( $header ) ) { 
					$header = 'Unknown format'; 
				} 
				// Populate list: 
				$report['body'] .= "./$relative ($header)\n"; 
			} 
		} else { 
			$report['body'] .= '-'. __('Executable files', 'ninjascanner') ."\n"; 
			$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n"; 
		} 
	} 
	$report['body'] .= "\n"; 
 
	// Symlink files 
	if (! empty( $snapshot['skip']['core_symlink'] ) ) { 
		$report['body'] .= '-'. __('Symbolic links', 'ninjascanner') ."\n"; 
		$report['body'] .= '> '. __('This test was skipped', 'ninjascanner') ."\n"; 
 
	} else { 
		if (! empty( $snapshot['core_symlink'] ) ) { 
			$report['important'] = 1; 
			$count = count( $snapshot['core_symlink'] ); 
			$report['body'] .= '-'. sprintf( __('Symbolink links: %s', 'ninjascanner'), $count ) ."\n"; 
			$report['body'] .= __('The following files or folders are symbolic links:', 'ninjascanner') ."\n\n"; 
			foreach( $snapshot['core_symlink'] as $file => $null ) { 
				$relative = str_replace( ABSPATH, '', $file ); 
				// Populate list: 
				$report['body'] .= "./$relative (". __('Target:', 'ninjascanner') .' '. readlink( $file ) .")\n"; 
			} 
		} else { 
			$report['body'] .= '-'. __('Symbolic links', 'ninjascanner') ."\n"; 
			$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n"; 
		} 
	} 
	$report['body'] .= "\n"; 
 
 
	// Unreadable files/folders 
	if (! empty( $snapshot['skip']['core_unreadable'] ) ) { 
		$report['body'] .= '-'. __('Unreadable files/folders', 'ninjascanner') ."\n"; 
		$report['body'] .= '> '. __('This test was skipped', 'ninjascanner') ."\n\n"; 
 
	} else { 
		if (! empty( $snapshot['core_unreadable'] ) ) { 
			$report['important'] = 1; 
			$count = count( $snapshot['core_unreadable'] ); 
			$report['body'] .= '-'. sprintf( __('Unreadable files/folders: %s', 'ninjascanner'), $count ) ."\n"; 
			$report['body'] .= __("The following files or folders are unreadable, NinjaScanner couldn't scan them:", 'ninjascanner') ."\n\n"; 
			foreach( $snapshot['core_unreadable'] as $file => $null ) { 
				$relative = str_replace( ABSPATH, '', $file ); 
				// Populate list: 
				$report['body'] .= "./$relative\n"; 
			} 
		} else { 
			$report['body'] .= '-'. __('Unreadable files/folders', 'ninjascanner') ."\n"; 
			$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n"; 
		} 
	} 
 
 
	$report['body'] .= $NSCAN_SEP; 
	// ========================================================================== 
	// Google Safe Browsing 
	$report['body'] .= mb_convert_case( __('Google Safe Browsing', 'ninjascanner'), MB_CASE_UPPER, "UTF-8" ) ."\n\n"; 
 
	// Error? 
	if (! empty( $snapshot['step_error']['nscan_check_gsb'] ) ) { 
		$report['body'] .= '> '. __('Warning, a critical error occurred. This test was cancelled.', 'ninjascanner') ."\n"; 
 
	} elseif (! empty( $snapshot['skip']['scan_gsb'] ) ) { 
		$report['body'] .= '> '. __('This test was skipped', 'ninjascanner') ."\n"; 
 
	} else { 
		// Infected URLs: 
		if (! empty( $snapshot['scan_gsb'] ) ) { 
			$report['critical'] = 1; 
			$count = count(  $snapshot['scan_gsb'] ); 
			$report['body'] .=  '-'. sprintf( __('Total websites listed on Google Safe Browsing blacklist: %s', 'ninjascanner'), $count ) ."\n"; 
			foreach( $snapshot['scan_gsb'] as $site => $v ) { 
				$report['body'] .= "* $site\n"; 
			} 
		} else { 
			$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n"; 
		} 
	} 
 
 
	$report['body'] .= $NSCAN_SEP; 
	// ========================================================================== 
	// Anti-malware 
	$report['body'] .= mb_convert_case( __('Anti-malware', 'ninjascanner'), MB_CASE_UPPER, "UTF-8" ) ."\n\n"; 
 
	// Error? 
	if (! empty( $snapshot['step_error']['nscan_setup_antimalware'] ) ) { 
		$report['body'] .= '> '. __('Warning, a critical error occurred. This test was cancelled.', 'ninjascanner') ."\n"; 
 
	} elseif (! empty( $snapshot['skip']['scan_antimalware'] ) ) { 
		$report['body'] .= '> '. __('This test was skipped', 'ninjascanner') ."\n"; 
 
	} else { 
		// Infected files: 
		if (! empty( $snapshot['infected_files'] ) ) { 
			$count = count( $snapshot['infected_files'] ); 
			$report['body'] .= sprintf( __('Suspicious files: %s', 'ninjascanner'), $count ) ."\n"; 
			$report['body'] .= __("The following files may be infected with malicious code:", 'ninjascanner') ."\n\n"; 
			foreach( $snapshot['infected_files'] as $file => $v ) { 
				$relative = str_replace( ABSPATH, '', $file ); 
				// Severity level depends on the rule: 
				if ( strpos( $v, '{REX}' ) !== false ) { 
					$report['important'] = 1; 
				} else { 
					$report['critical'] = 1; 
				} 
				$v = str_replace( array( '{REX}' , '{HEX}' ), '', $v ); 
				// Populate list: 
				$report['body'] .= "./$relative ($v)\n"; 
			} 
 
		} else { 
			$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n"; 
		} 
	} 
 
 
	$report['body'] .= $NSCAN_SEP; 
	// ========================================================================== 
	// File snapshot 
	$report['body'] .= mb_convert_case( __('File snapshot', 'ninjascanner'), MB_CASE_UPPER, "UTF-8" ) ."\n\n"; 
	if (! empty( $snapshot['skip']['scan_warnfilechanged'] ) ) { 
		$report['body'] .= '> '. __('This test was skipped', 'ninjascanner') ."\n"; 
 
	} else { 
		if (! empty( $snapshot['snapshot']['mismatched_files'] ) || 
			! empty( $snapshot['snapshot']['added_files'] ) || 
			! empty( $snapshot['snapshot']['deleted_files'] ) ) { 
 
			// Files modified since last scan: 
			if (! empty( $snapshot['snapshot']['mismatched_files'] ) ) { 
				$report['important'] = 1; 
				$count = count( $snapshot['snapshot']['mismatched_files'] ); 
				$report['body'] .= '-'. sprintf( __('Modified files: %s', 'ninjascanner'), $count ) ."\n"; 
				$report['body'] .= __("The following files have been modified since last scan:", 'ninjascanner') ."\n\n"; 
				foreach( $snapshot['snapshot']['mismatched_files'] as $file => $v ) { 
					$relative = str_replace( ABSPATH, '', $file ); 
					// Populate list: 
					$report['body'] .= "./$relative\n"; 
				} 
				$report['body'] .= "\n"; 
 
			} else { 
				$report['body'] .= '-'. __('Modified files since last scan', 'ninjascanner') ."\n"; 
				$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n\n"; 
			} 
 
			// Files added since last scan: 
			if (! empty( $snapshot['snapshot']['added_files'] ) ) { 
				$report['important'] = 1; 
				$count = count( $snapshot['snapshot']['added_files'] ); 
				$report['body'] .= '-'. sprintf( __('New files: %s', 'ninjascanner'), $count ) ."\n"; 
				$report['body'] .= __("The following files have been added since last scan:", 'ninjascanner') ."\n\n"; 
				foreach( $snapshot['snapshot']['added_files'] as $file => $v ) { 
					$relative = str_replace( ABSPATH, '', $file ); 
					// Populate list: 
					$report['body'] .= "./$relative\n"; 
				} 
				$report['body'] .= "\n"; 
			} else { 
				$report['body'] .= '-'. __('Added files since last scan', 'ninjascanner') ."\n"; 
				$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n\n"; 
			} 
 
 
			// Files deleted since last scan: 
			if (! empty( $snapshot['snapshot']['deleted_files'] ) ) { 
				$report['important'] = 1; 
				$count = count( $snapshot['snapshot']['deleted_files'] ); 
				$report['body'] .= '-'. sprintf( __('Deleted files: %s', 'ninjascanner'), $count ) ."\n"; 
				$report['body'] .= __("The following files have been deleted since last scan:", 'ninjascanner') ."\n\n"; 
				foreach( $snapshot['snapshot']['deleted_files'] as $file => $v ) { 
					$relative = str_replace( ABSPATH, '', $file ); 
					// Populate list: 
					$report['body'] .= "./$relative\n"; 
				} 
				$report['body'] .= "\n"; 
 
			} else { 
				$report['body'] .= '-'. __('Deleted files since last scan', 'ninjascanner') ."\n"; 
				$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n\n"; 
			} 
 
		} else { 
			if (! file_exists( NSCAN_OLD_SNAPSHOT ) ) { 
				$report['body'] .= '> '. __('Skipping snapshots comparison, no older file snapshot found', 'ninjascanner') ."\n"; 
 
			} else { 
				$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n"; 
			} 
		} 
	} 
 
	// ========================================================================== 
	// Database snapshot 
 
	// Posts: 
	$report['body'] .= $NSCAN_SEP; 
	$report['body'] .= mb_convert_case( __('Database snapshot (posts)', 'ninjascanner'), MB_CASE_UPPER, "UTF-8" ) ."\n\n"; 
	if (! empty( $snapshot['skip']['scan_warndbchanged'] ) ) { 
		$report['body'] .= '> '. __('This test was skipped', 'ninjascanner') ."\n"; 
 
	} else { 
 
		if (! empty( $snapshot['snapshot']['mismatched_posts'] ) || 
			! empty( $snapshot['snapshot']['added_posts'] ) || 
			! empty( $snapshot['snapshot']['deleted_posts'] ) ) { 
 
			// Posts modified since last scan: 
			if (! empty( $snapshot['snapshot']['mismatched_posts'] ) ) { 
				$report['important'] = 1; 
				$count = count( $snapshot['snapshot']['mismatched_posts'] ); 
				$report['body'] .= '-'. sprintf( __('Modified posts: %s', 'ninjascanner'), $count ) ."\n"; 
				$report['body'] .= __("The following posts have been modified since last scan:", 'ninjascanner') ."\n\n"; 
				foreach( $snapshot['snapshot']['mismatched_posts'] as $id => $val ) { 
					// Populate list: 
					$report['body'] .= "* {$val} (ID #{$id})\n"; 
				} 
				$report['body'] .= "\n"; 
 
			} else { 
				$report['body'] .= '-'. __('Modified posts since last scan', 'ninjascanner') ."\n"; 
				$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n\n"; 
			} 
 
			// Posts added since last scan: 
			if (! empty( $snapshot['snapshot']['added_posts'] ) ) { 
				$report['important'] = 1; 
				$count = count( $snapshot['snapshot']['added_posts'] ); 
				$report['body'] .= '-'. sprintf( __('New posts: %s', 'ninjascanner'), $count ) ."\n"; 
				$report['body'] .= __("The following posts have been added since last scan:", 'ninjascanner') ."\n\n"; 
				foreach( $snapshot['snapshot']['added_posts'] as $id => $val ) { 
					// Populate list: 
					$report['body'] .= "* {$val} (ID #{$id})\n"; 
				} 
				$report['body'] .= "\n"; 
 
			} else { 
				$report['body'] .= '-'. __('Added posts since last scan', 'ninjascanner') ."\n"; 
				$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n\n"; 
			} 
 
			// Posts deleted since last scan: 
			if (! empty( $snapshot['snapshot']['deleted_posts'] ) ) { 
				$report['important'] = 1; 
				$count = count( $snapshot['snapshot']['deleted_posts'] ); 
				$report['body'] .= '-'. sprintf( __('Deleted posts: %s', 'ninjascanner'), $count ) ."\n"; 
				$report['body'] .= __("The following posts have been deleted since last scan:", 'ninjascanner') ."\n\n"; 
				foreach( $snapshot['snapshot']['deleted_posts'] as $id => $val ) { 
					// Populate list: 
					$report['body'] .= "* {$val} (ID #{$id})\n"; 
				} 
				$report['body'] .= "\n"; 
 
			} else { 
				$report['body'] .= '-'. __('Deleted posts since last scan', 'ninjascanner') ."\n"; 
				$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n\n"; 
			} 
 
		} else { 
			if (! file_exists( NSCAN_OLD_SNAPSHOT ) ) { 
				$report['body'] .= '> '. __('Skipping snapshots comparison, no older database snapshot found', 'ninjascanner') ."\n"; 
 
			} else { 
				$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n"; 
			} 
		} 
	} 
 
	// Pages: 
	$report['body'] .= $NSCAN_SEP; 
	$report['body'] .= mb_convert_case( __('Database snapshot (pages)', 'ninjascanner'), MB_CASE_UPPER, "UTF-8" ) ."\n\n"; 
	if (! empty( $snapshot['skip']['scan_warndbchanged'] ) ) { 
		$report['body'] .= '> '. __('This test was skipped', 'ninjascanner') ."\n"; 
 
	} else { 
 
		if (! empty( $snapshot['snapshot']['mismatched_pages'] ) || 
			! empty( $snapshot['snapshot']['added_pages'] ) || 
			! empty( $snapshot['snapshot']['deleted_pages'] ) ) { 
 
			// Pages modified since last scan: 
			if (! empty( $snapshot['snapshot']['mismatched_pages'] ) ) { 
				$report['important'] = 1; 
				$count = count( $snapshot['snapshot']['mismatched_pages'] ); 
				$report['body'] .= '-'. sprintf( __('Modified pages: %s', 'ninjascanner'), $count ) ."\n"; 
				$report['body'] .= __("The following pages have been modified since last scan:", 'ninjascanner') ."\n\n"; 
				foreach( $snapshot['snapshot']['mismatched_pages'] as $id => $val ) { 
					// Populate list: 
					$report['body'] .= "* {$val} (ID #{$id})\n"; 
				} 
				$report['body'] .= "\n"; 
 
			} else { 
				$report['body'] .= '-'. __('Modified pages since last scan', 'ninjascanner') ."\n"; 
				$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n\n"; 
			} 
 
			// Pages added since last scan: 
			if (! empty( $snapshot['snapshot']['added_pages'] ) ) { 
				$report['important'] = 1; 
				$count = count( $snapshot['snapshot']['added_pages'] ); 
				$report['body'] .= '-'. sprintf( __('New pages: %s', 'ninjascanner'), $count ) ."\n"; 
				$report['body'] .= __("The following pages have been added since last scan:", 'ninjascanner') ."\n\n"; 
				foreach( $snapshot['snapshot']['added_pages'] as $id => $val ) { 
					// Populate list: 
					$report['body'] .= "* {$val} (ID #{$id})\n"; 
				} 
				$report['body'] .= "\n"; 
 
			} else { 
				$report['body'] .= '-'. __('Added pages since last scan', 'ninjascanner') ."\n"; 
				$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n\n"; 
			} 
 
			// Pages deleted since last scan: 
			if (! empty( $snapshot['snapshot']['deleted_pages'] ) ) { 
				$report['important'] = 1; 
				$count = count( $snapshot['snapshot']['deleted_pages'] ); 
				$report['body'] .= '-'. sprintf( __('Deleted pages: %s', 'ninjascanner'), $count ) ."\n"; 
				$report['body'] .= __("The following pages have been deleted since last scan:", 'ninjascanner') ."\n\n"; 
				foreach( $snapshot['snapshot']['deleted_pages'] as $id => $val ) { 
					// Populate list: 
					$report['body'] .= "* {$val} (ID #{$id})\n"; 
				} 
				$report['body'] .= "\n"; 
 
			} else { 
				$report['body'] .= '-'. __('Deleted pages since last scan', 'ninjascanner') ."\n"; 
				$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n\n"; 
			} 
 
		} else { 
			if (! file_exists( NSCAN_OLD_SNAPSHOT ) ) { 
				$report['body'] .= '> '. __('Skipping snapshots comparison, no older database snapshot found', 'ninjascanner') ."\n"; 
 
			} else { 
				$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n"; 
			} 
		} 
	} 
 
	// ========================================================================== 
	// Various tests 
	$report['body'] .= $NSCAN_SEP; 
	$report['body'] .= mb_convert_case( __('Various', 'ninjascanner'), MB_CASE_UPPER, "UTF-8" ) ."\n\n"; 
 
	if (! is_plugin_active( 'ninjafirewall/ninjafirewall.php' ) && ! is_plugin_active( 'nfwplus/nfwplus.php' ) ) { 
		$snapshot['various']['waf'] = 0; 
	} 
 
	if (! empty( $snapshot['various'] ) ) { 
 
		if (! empty( $snapshot['various']['ssh_key'] ) ) { 
			$report['important'] = 1; 
			$count = count( $snapshot['various']['ssh_key'] ); 
			$report['body'] .= '-'. __('SSH key:', 'ninjascanner' ) ."\n"; 
			$report['body'] .= sprintf( 
				_n( 
					'%s SSH key was found and allows a user to connect to your site over SSH. Make sure that file was not uploaded by someone else:', 
					'%s SSH keys were found and allow a user to connect to your site over SSH. Make sure those files were not uploaded by someone else:', 
					$count, 
					'ninjascanner' 
				), 
				$count 
			) ."\n\n"; 
			foreach( $snapshot['various']['ssh_key'] as $k => $v ) { 
				// Populate list: 
				$report['body'] .= "* {$k}\n"; 
			} 
		} 
 
		if (! empty( $snapshot['various']['membership'] ) ) { 
			$report['body'] .= '-'. __('Membership:', 'ninjascanner' ) ."\n"; 
			if ( $snapshot['various']['membership'] == 1 ) { 
				$report['important'] = 1; 
				$report['body'] .= __('Although user registration is disabled, the "New User Default Role" option is set to "administrator".', 'ninjascanner'); 
			} else { 
				$report['important'] = 2; 
				$report['body'] .= __('User registration is enabled and the "New User Default Role" option is set to "administrator"!', 'ninjascanner'); 
			} 
			$report['body'] .= "\n"; 
		} 
 
		if (! empty( $snapshot['various']['ghost_admin'] ) ) { 
			$report['body'] .= '-'. __('Ghost admin:', 'ninjascanner' ) ."\n"; 
			$report['important'] = 2; 
			$report['body'] .= __('NinjaScanner has detected hidden administrators on your blog:', 'ninjascanner') ."\n\n"; 
			$ghosts = json_decode( $snapshot['various']['ghost_admin'], true ); 
			foreach( $ghosts as $user ) { 
				$report['body'] .= "ID: {$user['ID']} (". translate_user_role('Administrator') .")\nuser_login: {$user['user_login']}\nuser_nicename: {$user['user_nicename']}\nuser_email: {$user['user_email']}\nuser_registered: {$user['user_registered']}\ndisplay_name: {$user['display_name']}\n\n"; 
			} 
		} 
 
		if (! empty( $snapshot['various']['user_roles'] ) ) { 
			$report['important'] = 1; 
			$report['body'] .= '-'. __('User roles:', 'ninjascanner' ) ."\n"; 
			$report['body'] .= __('NinjaScanner has detected that the following user roles have been given capabilities that, by default, are only assigned to an administrator:', 'ninjascanner') ."\n\n"; 
 
			foreach( $snapshot['various']['user_roles'] as $user => $cap   ) { 
				$report['body'] .= "* Role: {$user} - Capabilities: "; 
				foreach( $cap as $k => $v ) { 
					$report['body'] .="[$v] "; 
				} 
				$report['body'] .= "\n"; 
			} 
		} 
 
		if ( isset( $snapshot['various']['waf'] ) ) { 
			$report['body'] .= '-'. __('Firewall:', 'ninjascanner' ) ." "; 
			$report['body'] .= __('No firewall detected', 'ninjascanner') .".\n"; 
			$report['body'] .= __('Consider installing a Web Application Firewall such as NinjaFirewall (https://wordpress.org/plugins/ninjafirewall/) to make sure your site is well protected against web attacks.', 'ninjascanner') ."\n\n"; 
 
		} 
	} else { 
		$report['body'] .= '> '. __('No problem detected', 'ninjascanner') ."\n"; 
	} 
 
	// ========================================================================== 
 
	$report['body'] .= "{$NSCAN_SEP}- ". __('End of report', 'ninjascanner') ." -\n"; 
	return $report; 
} 
// ===================================================================== 
// EOF 
