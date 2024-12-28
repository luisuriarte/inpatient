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
// Display the scan report in HTML format.

function html_report() {

	$nscan_options = get_option( 'nscan_options' );

	$section = 0;

	if (! empty( $nscan_options['mutipagereport'] ) ) {
		$section_list = array(
			1 => __('WordPress Core', 'ninjascanner'),
			2 => __('Plugins', 'ninjascanner'),
			3 => __('Themes', 'ninjascanner'),
			4 => __('Files & Folders', 'ninjascanner'),
			5 => __('Google Safe Browsing', 'ninjascanner'),
			6 => __('Anti-Malware', 'ninjascanner'),
			7 => __('Snapshots (Files & DB)', 'ninjascanner'),
			8 => __('Various tests', 'ninjascanner')
		);
		if (! empty( $_GET['section'] ) ) {
			$section = (int) $_GET['section'];
			if ( empty( $section_list[ $section ] ) ) {
				$section = 1;
			}
		} else {
			$section = 1;
		}
	}

	$report = array();
	$snapshot = array();

	// Make sure we have a snapshot:
	if (! file_exists( NSCAN_SNAPSHOT ) ) {
		$report['error'] = __("Cannot find the snapshot file, scan report cannot be created.", 'ninjascanner');
		return $report;
	}

	$snapshot = unserialize( file_get_contents( NSCAN_SNAPSHOT ) );

	if ( empty( $snapshot['abspath'] ) ) {
		$report['error'] = __("Snapshot seems corrupted (missing 'abspath' field), scan report cannot be created.", 'ninjascanner');
		return $report;
	}

	$report['body'] = '';

	// Make sure the snapshot was created from the same WP version:
	global $wp_version;
	if ( $snapshot['version'] != $wp_version ) {
		$message = sprintf(
			__("This report was created for WordPress %s, but your current version is %s. ".
				"If you upgraded WordPress lately, don't forget to run a new scan.", 'ninjascanner'),
			htmlspecialchars( $snapshot['version'] ),
			htmlspecialchars( $wp_version )
		);
		echo '<div class="notice-warning notice is-dismissible"><p>' . $message . '</p></div>';
	}

	// Make sure it was created with the same version of NinjaScanner
	// (newer updates may bring new features - or remove some):
	if ( empty( $snapshot['nscan_version'] ) || $snapshot['nscan_version'] !== NSCAN_VERSION ) {
		$message = __("This report was created with a different version of NinjaScanner. ".
					"Don't forget to run a new scan to make sure it is up to date.", 'ninjascanner');
		echo '<div class="notice-warning notice is-dismissible"><p>' . $message . '</p></div>';
	}

	// Blog domain name:
	if ( is_multisite() ) {
		$blog = network_home_url('/');
	} else {
		$blog = home_url('/');
	}

	// Scan date:
	nscan_get_blogtimezone();
	$scan_date = ucfirst( date( 'F d, Y @ g:i A', filemtime( NSCAN_SNAPSHOT ) ) );

	if (! empty( $snapshot['locale'] ) ) {
		$wordpress = "{$snapshot['version']} ({$snapshot['locale']})";
	} else {
		$wordpress = "{$snapshot['version']}";
	}
	$name = 'ninjascanner';
	$report['body'] .= '
	<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. htmlspecialchars( $name ) .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
		<tr class="ns-table-header-tr">
			<td class="ns-table-header-td-icon">
				<span><img src="' . plugins_url() .'/ninjascanner/static/logo_ns_40.png"></span>
			</td>
			<td class="ns-table-header-td-file">
				<strong>'. __( 'NinjaScanner report', 'ninjascanner' ) .'</strong>
			</td>
		</tr>
	</table>
	<div id="table-report-'. htmlspecialchars( $name ) .'">
		<table class="widefat">
			<tr>
				<td class="r1">'. __('Date', 'ninjascanner') .'</td>
				<td class="r2">'. htmlentities( $scan_date ) .'</td>
			</tr>
			<tr>
				<td class="r1">'. __('Home URL', 'ninjascanner') .'</td>
				<td class="r2">'. htmlentities( $blog ) .'</td>
			</tr>
			<tr>
				<td class="r1">'. __('Blog folder', 'ninjascanner') .' (ABSPATH)</td>
				<td class="r2">'. htmlentities( ABSPATH ) .'</td>
			</tr>
			<tr>
				<td class="r1">'. __('WordPress Version', 'ninjascanner') .'</td>
				<td class="r2">'. htmlentities( $wordpress ) .'</td>
			</tr>
			<tr>
				<td class="r1">'. __('Total files', 'ninjascanner') .'</td>
				<td class="r2">'. number_format_i18n( count( $snapshot['abspath'] ) ) .'</td>
			</tr>
		</table>
	</div>
	<br />
	<br />
	';

	// User interface settings:
	if ( empty( $nscan_options['row_action'] ) ) {
		define( 'NSCAN_ROW_ACTIONS', 'row-actions' );
	} else {
		define( 'NSCAN_ROW_ACTIONS', 'row-actions visible' );
	}
	if ( empty( $nscan_options['table_rows'] ) || ! preg_match( '/^\d+$/', $nscan_options['table_rows'] ) ) {
		define( 'NSCAN_MAX_ROWS', 6 );
	} else {
		define( 'NSCAN_MAX_ROWS', (int)$nscan_options['table_rows'] );
	}
	if ( empty( $nscan_options['show_path'] ) ) {
		define( 'NSCAN_ABSOLUTE_PATH', true );
	} else {
		define( 'NSCAN_ABSOLUTE_PATH', false );
	}


	// ==================================================================
	// Fetch ignored and quarantine lists so that we can exclude their
	// files from our report (the user may have moved some files to those
	// lists without running a new scan to refresh the current snapshot).
	$ignored_quarantined = array();
	$ignored_quarantined = nscan_retrieve_excluded_files();


	if ( empty( $nscan_options['mutipagereport'] ) || $section == 1 ) {

	// ==================================================================
	// WordPress core files integrity
	$name = 'wordpress';
	$report['body'] .= '
	<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. htmlspecialchars( $name ) .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
		<tr class="ns-table-header-tr">
			<td class="ns-table-header-td-icon">
				<span class="dashicons dashicons-wordpress ns-dashicons-header"></span>
			</td>
			<td class="ns-table-header-td-file">
				<strong>'. __( 'WordPress core files', 'ninjascanner' ) .'</strong>
			</td>
		</tr>
	</table>
	<div id="table-report-'. htmlspecialchars( $name ) .'">
	';

	// Error:
	if (! empty( $snapshot['step_error']['nscan_check_wordpress'] ) ) {
		$report['body'] .= ns_step_error();

	// Skipped test:
	} elseif (! empty( $snapshot['skip']['scan_wpcoreintegrity'] ) ) {
		$report['body'] .= ns_skipped_test();

	} else {
		$files_list = array();
		// Modified core files:
		if (! empty( $snapshot['core_failed_checksum'] ) ) {
			foreach( $snapshot['core_failed_checksum'] as $file => $null ) {
				$files_list[$file] = 'core_mismatch';
			}
		}
		// Unknown and suspicious files:
		if (! empty( $snapshot['core_unknown'] ) ) {
			foreach( $snapshot['core_unknown'] as $file => $null ) {
				$files_list[$file] = 'core_unknown';
			}
		}
		// Unknown and suspicious files:
		if (! empty( $snapshot['core_unknown_root'] ) ) {
			foreach( $snapshot['core_unknown_root'] as $file => $null ) {
				$files_list[$file] = 'core_unknown';
			}
		}
		// Remove ignored files:
		$files_list = ns_remove_ignored( $files_list, $ignored_quarantined );
		// Remove quatantined files:
		$files_list = ns_remove_quarantined( $files_list, $ignored_quarantined );

		if (! empty( $files_list ) ) {
//			ksort( $files_list );
			$report['body'] .= ns_build_rows( $files_list, 'wordpress', $name );

		} else {
			$report['body'] .= ns_no_problem();
		}
	}
	$report['body'] .= '
	</div>
	<br />
	<br />
	';

	}

	if ( empty( $nscan_options['mutipagereport'] ) || $section == 2 ) {

	// ==================================================================
	// Plugin files integrity

	// Skipped test:
	if (! empty( $snapshot['skip']['scan_pluginsintegrity'] ) ) {
		$name = 'plugins';
		$report['body'] .= '
		<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. htmlspecialchars( $name ) .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
			<tr class="ns-table-header-tr">
				<td class="ns-table-header-td-icon">
					<span class="dashicons dashicons-admin-plugins ns-dashicons-header"></span>
				</td>
				<td class="ns-table-header-td-file">
					<strong>'. __( 'Plugin files integrity', 'ninjascanner' ) .'</strong>
				</td>
			</tr>
		</table>
		<div id="table-report-'. htmlspecialchars( $name ) .'">';
		$report['body'] .= ns_skipped_test();
		$report['body'] .= '
		</div>
		<br />
		<br />
		';
	// Fetch plugins list
	} else {

		$files_list = array();

		if (! empty( $snapshot['plugins'] ) ) {
			// Loop through the list (ok, modified or added files):
			foreach( $snapshot['plugins'] as $slug => $arr ) {

				// Plugin matches original one:
				if ( empty( $snapshot['plugins'][$slug] ) ) {
					$files_list['plugins'][$slug] = array();

				// Plugin does not match:
				} else {
					// Sort array
//					ksort( $arr );

					foreach( $arr as $k => $v ) {
						// 0 == Unknown file found inside a plugin folder.
						// 1 == File does not match the original plugin file.
						if ( $v == 1 ) {
							$files_list['plugins'][$slug][WP_PLUGIN_DIR ."/$slug/$k"] = 1;
						} else {
							$files_list['plugins'][$slug][WP_PLUGIN_DIR ."/$slug/$k"] = $v;
						}
					}
				}
			}
		}

		// 2 == Unknown plugin (maybe a premium plugin not available in wp.org repo).
		if (! empty( $snapshot['plugins_not_found'] ) ) {
			foreach( $snapshot['plugins_not_found'] as $slug => $arr ) {

				// Check if its a folder or a file:
				if ( is_dir( WP_PLUGIN_DIR ."/$slug" ) ) {
					$files_list['plugins'][$slug] =	nscan_find_files( WP_PLUGIN_DIR ."/$slug" , 2);

				// Its a lone file:
				} else {
					$files_list['plugins'][$slug][WP_PLUGIN_DIR ."/$slug"] = 2;
				}
			}
		}

		if (! empty( $snapshot['plugins_unknown'] ) ) {
			foreach( $snapshot['plugins_unknown'] as $slug => $arr ) {

				// Check if its a folder or a file:
				if ( is_dir( WP_PLUGIN_DIR ."/$slug" ) ) {
					$files_list['plugins'][$slug]	= nscan_find_files( WP_PLUGIN_DIR ."/$slug" , 2);

				// Its a lone file:
				} else {
					$files_list['plugins'][$slug][WP_PLUGIN_DIR ."/$slug"] = 2;
				}
			}
		}

		// 3 == MU plugin
		if (! empty( $snapshot['mu_plugins'] ) ) {
			foreach( $snapshot['mu_plugins'] as $slug => $arr ) {

				// Check if its a folder or a file:
				if ( is_dir( WPMU_PLUGIN_DIR ."/$slug" ) ) {
					$files_list['plugins'][$slug]	= nscan_find_files( WPMU_PLUGIN_DIR ."/$slug" , 3);

				// Its a lone file:
				} else {
					$files_list['plugins'][$slug][WPMU_PLUGIN_DIR ."/$slug"] = 3;
				}
			}
		}

		// 4 == Drop-ins plugin
		if (! empty( $snapshot['plugins_dropins'] ) ) {
			foreach( $snapshot['plugins_dropins'] as $slug => $arr ) {

				// Check if its a folder or a file:
				if ( is_dir( WP_CONTENT_DIR ."/$slug" ) ) {
					$files_list['plugins'][$slug]	= nscan_find_files( WP_CONTENT_DIR ."/$slug" , 4);

				// Its a lone file:
				} else {
					$files_list['plugins'][$slug][WP_CONTENT_DIR ."/$slug"] = 4;
				}
			}
		}

		if (! empty( $files_list['plugins'] ) ) {

//			ksort( $files_list['plugins'] );

			// Parse and display each plugin found:
			foreach( $files_list['plugins'] as $slug => $arr ) {
				$name = uniqid( 'plugin-' );
				$report['body'] .= '
				<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. $name .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
					<tr class="ns-table-header-tr">
						<td class="ns-table-header-td-icon">
							<span class="dashicons dashicons-admin-plugins ns-dashicons-header"></span>
						</td>
						<td class="ns-table-header-td-file">
							<strong>'. sprintf( __( 'Plugin:  %s', 'ninjascanner' ), htmlspecialchars( $slug ) ) .'</strong>
						</td>
					</tr>
				</table>
				<div id="table-report-'. $name .'">
				';
				if (! empty( $arr ) ) {
					// Remove ignored files:
					$arr = ns_remove_ignored( $arr, $ignored_quarantined );
					// Remove quatantined files:
					$arr = ns_remove_quarantined( $arr, $ignored_quarantined );
				}

				// Plugin is OK:
				if ( empty( $arr ) ) {
					$report['body'] .= ns_no_problem();

				// Plugin has issue:
				} else {
					$report['body'] .= ns_build_rows( $arr, 'plugin', $name );

				}
				$report['body'] .= '
				</div>
				<br />
				<br />
				';
			}
		}
	}


	}
	if ( empty( $nscan_options['mutipagereport'] ) || $section == 3 ) {

	// ==================================================================
	// Theme files integrity

	// Skipped test:
	if (! empty( $snapshot['skip']['scan_themeseintegrity'] ) ) {

		$name = 'themes';
		$report['body'] .= '
		<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. htmlspecialchars( $name ) .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
			<tr class="ns-table-header-tr">
				<td class="ns-table-header-td-icon">
					<span class="dashicons dashicons-admin-appearance ns-dashicons-header"></span>
				</td>
				<td class="ns-table-header-td-file">
					<strong>'. __( 'Theme files integrity', 'ninjascanner' ) .'</strong>
				</td>
			</tr>
		</table>
		<div id="table-report-'. htmlspecialchars( $name ) .'">';
		$report['body'] .= ns_skipped_test();
		$report['body'] .= '
		</div>
		<br />
		<br />
		';
	// Fetch themes list
	} else {

		$files_list = array();

		// Build the lists of modified or added theme files:
		if (! empty( $snapshot['themes'] ) ) {
			// Loop through the list (ok, modified or added files):
			foreach( $snapshot['themes'] as $slug => $arr ) {

				// Theme matches original one:
				if ( empty( $snapshot['themes'][$slug] ) ) {
					$files_list['themes'][$slug] = array();

				// Theme does not match:
				} else {
					// Sort array
//					ksort( $arr );
					foreach( $arr as $k => $v ) {
						// 0 == Unknown file found inside a theme folder.
						// 1 == File does not match the original theme file.
						if ( $v == 1 ) {
							$files_list['themes'][$slug][WP_CONTENT_DIR . "/themes/$slug/$k"] = 1;
						} else {
							$files_list['themes'][$slug][WP_CONTENT_DIR . "/themes/$slug/$k"] = $v;
						}
					}
				}
			}
		}

		// 2 == Unknown theme (maybe a premium theme not available in wp.org repo).
		if (! empty( $snapshot['themes_not_found'] ) ) {
			foreach( $snapshot['themes_not_found'] as $slug => $arr ) {

				// Check if its a folder or a file:
				if ( is_dir( WP_CONTENT_DIR . "/themes/$slug" ) ) {
					$files_list['themes'][$slug] = nscan_find_files( WP_CONTENT_DIR . "/themes/$slug" , 2);

				// Its a lone file:
				} else {
					$files_list['themes'][$slug][WP_CONTENT_DIR . "/themes/$slug"] = 2;
				}
			}
		}

		if (! empty( $files_list['themes'] ) ) {

//			ksort( $files_list['themes'] );

			// Parse and display each theme found:
			foreach( $files_list['themes'] as $slug => $arr ) {
				$name = uniqid( 'theme-' );
				$report['body'] .= '
				<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. $name .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
					<tr class="ns-table-header-tr">
						<td class="ns-table-header-td-icon">
							<span class="dashicons dashicons-admin-appearance ns-dashicons-header"></span>
						</td>
						<td class="ns-table-header-td-file">
							<strong>'. sprintf( __( 'Theme:  %s', 'ninjascanner' ), htmlspecialchars( $slug ) ) .'</strong>
						</td>
					</tr>
				</table>
				<div id="table-report-'. $name .'">
				';
				if (! empty( $arr ) ) {
					// Remove ignored files:
					$arr = ns_remove_ignored( $arr, $ignored_quarantined );
					// Remove quatantined files:
					$arr = ns_remove_quarantined( $arr, $ignored_quarantined );
				}
				// Theme is OK:
				if ( empty( $arr ) ) {
					$report['body'] .= ns_no_problem();

				// Theme has issue:
				} else {
					$report['body'] .= ns_build_rows( $arr, 'theme', $name );

				}
				$report['body'] .= '
				</div>
				<br />
				<br />
				';
			}
		}
	}

	}
	if ( empty( $nscan_options['mutipagereport'] ) || $section == 4 ) {

	// ==================================================================
	// Files & folders

	$files_list = array();

	if ( empty( $snapshot['skip']['core_hidden'] ) ||  empty( $snapshot['skip']['core_binary'] ) ||
		empty( $snapshot['skip']['core_symlink'] ) || empty( $snapshot['skip']['core_unreadable'] ) ) {

		$name = 'filesfolders';
		$report['body'] .= '
		<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. htmlspecialchars( $name ) .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
			<tr class="ns-table-header-tr">
				<td class="ns-table-header-td-icon">
					<span class="dashicons dashicons-portfolio ns-dashicons-header"></span>
				</td>
				<td class="ns-table-header-td-file">
					<strong>'. __( 'Files and folders', 'ninjascanner' ) .'</strong>
				</td>
			</tr>
		</table>
		<div id="table-report-'. htmlspecialchars( $name ) .'">
		';
		if (! empty( $snapshot['core_hidden'] ) ) {
			foreach( $snapshot['core_hidden'] as $file => $null ) {
				$files_list[$file] = 1;
			}
		}
		if (! empty( $snapshot['core_binary'] ) ) {
			foreach( $snapshot['core_binary'] as $file => $null ) {
				$files_list[$file] = 2;
			}
		}
		if (! empty( $snapshot['core_symlink'] ) ) {
			foreach( $snapshot['core_symlink'] as $file => $null ) {
				$files_list[$file] = 3;
			}
		}
		if (! empty( $snapshot['core_unreadable'] ) ) {
			foreach( $snapshot['core_unreadable'] as $file => $null ) {
				$files_list[$file] = 4;
			}
		}
		// Remove ignored files:
		$files_list = ns_remove_ignored( $files_list, $ignored_quarantined );
		// Remove quatantined files:
		$files_list = ns_remove_quarantined( $files_list, $ignored_quarantined );

		if (! empty( $files_list ) ) {
//			ksort( $files_list );
			$report['body'] .= ns_build_rows( $files_list, 'filesfolders', $name );

		} else {
			$report['body'] .= ns_no_problem();
		}

		$report['body'] .= '
		</div>
		<br />
		<br />
		';
	}

	}
	if ( empty( $nscan_options['mutipagereport'] ) || $section == 5 ) {

	// ==================================================================
	// Google Safe Browsing
	$files_list = array();

	$name = 'googlesb';
	$report['body'] .= '
	<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. htmlspecialchars( $name ) .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
		<tr class="ns-table-header-tr">
			<td class="ns-table-header-td-icon">
				<span class="dashicons dashicons-shield-alt ns-dashicons-header"></span>
			</td>
			<td class="ns-table-header-td-file">
				<strong>'. __( 'Google Safe Browsing', 'ninjascanner' ) .'</strong>
			</td>
		</tr>
	</table>
	<div id="table-report-'. htmlspecialchars( $name ) .'">
	';

	// Error?
	if (! empty( $snapshot['step_error']['nscan_check_gsb'] ) ) {
		$report['body'] .= ns_step_error();

	} elseif (! empty( $snapshot['skip']['scan_gsb'] ) ) {
		$report['body'] .= ns_skipped_test();

	} else {
		if (! empty( $snapshot['scan_gsb'] ) ) {
			ksort( $snapshot['scan_gsb'] );
			$report['body'] .= ns_build_rows( $snapshot['scan_gsb'], 'googlesb', $name );

		} else {
			$report['body'] .= ns_no_problem();
		}
	}
	$report['body'] .= '
	</div>
	<br />
	<br />
	';

	}
	if ( empty( $nscan_options['mutipagereport'] ) || $section == 6 ) {

	// ==================================================================
	// Anti-malware
	$files_list = array();

	$name = 'antimalware';
	$report['body'] .= '
	<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. htmlspecialchars( $name ) .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
		<tr class="ns-table-header-tr">
			<td class="ns-table-header-td-icon">
				<span class="dashicons dashicons-shield ns-dashicons-header"></span>
			</td>
			<td class="ns-table-header-td-file">
				<strong>'. __( 'Anti-malware', 'ninjascanner' ) .'</strong>
			</td>
		</tr>
	</table>
	<div id="table-report-'. htmlspecialchars( $name ) .'">
	';
	// Error?
	if (! empty( $snapshot['step_error']['nscan_setup_antimalware'] ) ) {
		$report['body'] .= ns_step_error();

	} elseif (! empty( $snapshot['skip']['scan_antimalware'] ) ) {
		$report['body'] .= ns_skipped_test();

	} else {
		if (! empty( $snapshot['infected_files'] ) ) {
			// Remove ignored files:
			$snapshot['infected_files'] = ns_remove_ignored( $snapshot['infected_files'], $ignored_quarantined );
			// Remove quatantined files:
			$snapshot['infected_files'] = ns_remove_quarantined( $snapshot['infected_files'], $ignored_quarantined );
		}

		if (! empty( $snapshot['infected_files'] ) ) {
			ksort( $snapshot['infected_files'] );
			$report['body'] .= ns_build_rows( $snapshot['infected_files'], 'antimalware', $name );

		} else {
			$report['body'] .= ns_no_problem();
		}
	}
	$report['body'] .= '
	</div>
	<br />
	<br />
	';

	}
	if ( empty( $nscan_options['mutipagereport'] ) || $section == 7 ) {

	// ==================================================================
	// File snapshot
	$files_list = array();
	$name = 'filesnapshot';

	// Skipped test:
	if (! empty( $snapshot['skip']['scan_warnfilechanged'] ) )  {

		$report['body'] .= '
		<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. htmlspecialchars( $name ) .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
			<tr class="ns-table-header-tr">
				<td class="ns-table-header-td-icon">
					<span class="dashicons dashicons-camera ns-dashicons-header"></span>
				</td>
				<td class="ns-table-header-td-file">
					<strong>'. __( 'File snapshot', 'ninjascanner' ) .'</strong>
				</td>
			</tr>
		</table>
		<div id="table-report-'. htmlspecialchars( $name ) .'">';
		$report['body'] .= ns_skipped_test();
		$report['body'] .= '
		</div>
		<br />
		<br />
		';

	// Fetch themes list
	} else {
		// Make sure we have an older snapshot:
		if ( file_exists( NSCAN_OLD_SNAPSHOT ) ) {

			$report['body'] .= '
			<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. htmlspecialchars( $name ) .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
				<tr class="ns-table-header-tr">
					<td class="ns-table-header-td-icon">
						<span class="dashicons dashicons-camera ns-dashicons-header"></span>
					</td>
					<td class="ns-table-header-td-file">
						<strong>'. __( 'File snapshot', 'ninjascanner' ) .'</strong>
					</td>
				</tr>
			</table>
			<div id="table-report-'. htmlspecialchars( $name ) .'">
			';
			if (! empty( $snapshot['snapshot']['mismatched_files'] ) || ! empty( $snapshot['snapshot']['added_files'] ) ||
				! empty( $snapshot['snapshot']['deleted_files'] ) ) {

				if (! empty( $snapshot['snapshot']['added_files'] ) ) {
					foreach( $snapshot['snapshot']['added_files'] as $file => $null ) {
						$files_list[$file] = 1;
					}
				}
				if (! empty( $snapshot['snapshot']['mismatched_files'] ) ) {
					foreach( $snapshot['snapshot']['mismatched_files'] as $file => $null ) {
						$files_list[$file] = 2;
					}
				}
				if (! empty( $snapshot['snapshot']['deleted_files'] ) ) {
					foreach( $snapshot['snapshot']['deleted_files'] as $file => $null ) {
						$files_list[$file] = 3;
					}
				}
				// Remove quatantined files (we don't check ignored files list since
				// those files were modified):
				$files_list = ns_remove_quarantined( $files_list, $ignored_quarantined );
			}

			if (! empty( $files_list ) ) {
				// don't sort file by name
				$report['body'] .= ns_build_rows( $files_list, 'filesnapshot', $name );

			} else {
				$report['body'] .= ns_no_problem();
			}

			$report['body'] .= '
			</div>
			<br />
			<br />
			';
		}
	}

	// ==================================================================
	// Database snapshot (posts)
	$files_list = array();
	$name = 'postsnapshot';

	// Skipped test:
	if (! empty( $snapshot['skip']['scan_warndbchanged'] ) )  {

		$report['body'] .= '
		<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. htmlspecialchars( $name ) .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
			<tr class="ns-table-header-tr">
				<td class="ns-table-header-td-icon">
					<span class="dashicons dashicons-admin-post ns-dashicons-header"></span>
				</td>
				<td class="ns-table-header-td-file">
					<strong>'. __( 'Database snapshot (posts)', 'ninjascanner' ) .'</strong>
				</td>
			</tr>
		</table>
		<div id="table-report-'. htmlspecialchars( $name ) .'">';
		$report['body'] .= ns_skipped_test();
		$report['body'] .= '
		</div>
		<br />
		<br />
		';

	} else {
		// Make sure we have an older snapshot:
		if ( file_exists( NSCAN_OLD_SNAPSHOT ) ) {

			$report['body'] .= '
			<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. htmlspecialchars( $name ) .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
				<tr class="ns-table-header-tr">
					<td class="ns-table-header-td-icon">
						<span class="dashicons dashicons-admin-post ns-dashicons-header"></span>
					</td>
					<td class="ns-table-header-td-file">
						<strong>'. __( 'Database snapshot (posts)', 'ninjascanner' ) .'</strong>
					</td>
				</tr>
			</table>
			<div id="table-report-'. htmlspecialchars( $name ) .'">
			';
			if (! empty( $snapshot['snapshot']['mismatched_posts'] ) || ! empty( $snapshot['snapshot']['added_posts'] ) ||
				! empty( $snapshot['snapshot']['deleted_posts'] ) ) {

				if (! empty( $snapshot['snapshot']['added_posts'] ) ) {
					foreach( $snapshot['snapshot']['added_posts'] as $id => $path ) {
						$files_list[$path] = "$id:1";
					}
				}
				if (! empty( $snapshot['snapshot']['mismatched_posts'] ) ) {
					foreach( $snapshot['snapshot']['mismatched_posts'] as $id => $path ) {
						$files_list[$path] = "$id:2";
					}
				}
				if (! empty( $snapshot['snapshot']['deleted_posts'] ) ) {
					foreach( $snapshot['snapshot']['deleted_posts'] as $id => $path ) {
						$files_list[$path] = "$id:3";
					}
				}
			}

			if (! empty( $files_list ) ) {
				$report['body'] .= ns_build_db_rows( $files_list, 'post', $name );

			} else {
				$report['body'] .= ns_no_problem();
			}

			$report['body'] .= '
			</div>
			<br />
			<br />
			';
		}
	}

	// ==================================================================
	// Database snapshot (pages)
	$files_list = array();
	$name = 'pagesnapshot';

	// Skipped test:
	if (! empty( $snapshot['skip']['scan_warndbchanged'] ) )  {

		$report['body'] .= '
		<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. htmlspecialchars( $name ) .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
			<tr class="ns-table-header-tr">
				<td class="ns-table-header-td-icon">
					<span class="dashicons dashicons-admin-page ns-dashicons-header"></span>
				</td>
				<td class="ns-table-header-td-file">
					<strong>'. __( 'Database snapshot (pages)', 'ninjascanner' ) .'</strong>
				</td>
			</tr>
		</table>
		<div id="table-report-'. htmlspecialchars( $name ) .'">';
		$report['body'] .= ns_skipped_test();
		$report['body'] .= '
		</div>
		<br />
		<br />
		';

	} else {
		// Make sure we have an older snapshot:
		if ( file_exists( NSCAN_OLD_SNAPSHOT ) ) {

			$report['body'] .= '
			<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. htmlspecialchars( $name ) .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
				<tr class="ns-table-header-tr">
					<td class="ns-table-header-td-icon">
						<span class="dashicons dashicons-admin-page ns-dashicons-header"></span>
					</td>
					<td class="ns-table-header-td-file">
						<strong>'. __( 'Database snapshot (pages)', 'ninjascanner' ) .'</strong>
					</td>
				</tr>
			</table>
			<div id="table-report-'. htmlspecialchars( $name ) .'">
			';
			if (! empty( $snapshot['snapshot']['mismatched_pages'] ) || ! empty( $snapshot['snapshot']['added_pages'] ) ||
				! empty( $snapshot['snapshot']['deleted_pages'] ) ) {

				if (! empty( $snapshot['snapshot']['added_pages'] ) ) {
					foreach( $snapshot['snapshot']['added_pages'] as $id => $path ) {
						$files_list[$path] = "$id:1";
					}
				}
				if (! empty( $snapshot['snapshot']['mismatched_pages'] ) ) {
					foreach( $snapshot['snapshot']['mismatched_pages'] as $id => $path ) {
						$files_list[$path] = "$id:2";
					}
				}
				if (! empty( $snapshot['snapshot']['deleted_pages'] ) ) {
					foreach( $snapshot['snapshot']['deleted_pages'] as $id => $path ) {
						$files_list[$path] = "$id:3";
					}
				}
			}

			if (! empty( $files_list ) ) {
				$report['body'] .= ns_build_db_rows( $files_list, 'pages', $name );

			} else {
				$report['body'] .= ns_no_problem();
			}

			$report['body'] .= '
			</div>
			<br />
			<br />
			';
		}
	}


	if ( empty( $snapshot['skip']['scan_warnfilechanged'] ) &&
		 empty( $snapshot['skip']['scan_warndbchanged'] ) &&
		 empty( $snapshot['skip']['scan_warndbchanged'] ) &&
		! file_exists( NSCAN_OLD_SNAPSHOT ) )  {
		$report['body'] .= '
		<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. htmlspecialchars( $name ) .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
			<tr class="ns-table-header-tr">
				<td class="ns-table-header-td-icon">
					<span class="dashicons dashicons-camera ns-dashicons-header"></span>
				</td>
				<td class="ns-table-header-td-file">
					<strong>'. __( 'Snapshots', 'ninjascanner' ) .'</strong>
				</td>
			</tr>
		</table>
		<div id="table-report-'. htmlspecialchars( $name ) .'">';
		$report['body'] .= ns_noprevioussnapshots();
		$report['body'] .= '
		</div>
		<br />
		<br />
		';
	}


	}
	if ( empty( $nscan_options['mutipagereport'] ) || $section == 8 ) {

	// ==================================================================
	// Various tests
	$files_list = array();

	if (! is_plugin_active( 'ninjafirewall/ninjafirewall.php' ) && ! is_plugin_active( 'nfwplus/nfwplus.php' ) ) {
		$snapshot['various']['waf'] = 0;
	}

	if (! empty( $snapshot['various'] ) ) {

		if (! empty( $snapshot['various']['ssh_key'] ) ) {
			$name = 'ssh';
			foreach( $snapshot['various']['ssh_key'] as $key => $v ) {
				$files_list[$key] = 1;
			}
			// Remove ignored files:
			$files_list = ns_remove_ignored( $files_list, $ignored_quarantined );
			// Remove quatantined files:
			$files_list = ns_remove_quarantined( $files_list, $ignored_quarantined );

			if (! empty( $files_list ) ) {
				$report['body'] .= '
					<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. $name .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
						<tr class="ns-table-header-tr">
							<td class="ns-table-header-td-icon">
								<span class="dashicons dashicons-admin-network ns-dashicons-header"></span>
							</td>
							<td class="ns-table-header-td-file">
								<strong>'. __( 'SSH key', 'ninjascanner' ) .'</strong>
							</td>
						</tr>
					</table>
					<div id="table-report-'. $name .'">
				';
				ksort( $files_list );
				$report['body'] .= ns_build_rows( $files_list, 'ssh', $name );
				$report['body'] .= '
					</div>
				<br />
				<br />
				';
			}
		}

		if (! empty( $snapshot['various']['membership'] ) ) {

			if ( $snapshot['various']['membership'] == 1 ) {
				$message = sprintf( __('Although <a href="%s">user registration</a> is disabled, the "New User Default Role" option is set to "administrator".', 'ninjascanner'), get_admin_url( null, 'options-general.php' ) );
				$icon = 'dashicons-info ns-unknown-file-icon';

			} else {
				$message = sprintf( __('<a href="%s">User registration</a> is enabled and the "New User Default Role" option is set to "administrator".', 'ninjascanner'), get_admin_url( null, 'options-general.php' ) );
				$icon = 'dashicons-dismiss ns-modified-file-icon';
			}
			$name = 'member';
			$report['body'] .= '
				<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. $name .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
					<tr class="ns-table-header-tr">
						<td class="ns-table-header-td-icon">
							<span class="dashicons dashicons-admin-settings ns-dashicons-header"></span>
						</td>
						<td class="ns-table-header-td-file">
							<strong>'. __( 'Settings', 'ninjascanner' ) .'</strong>
						</td>
					</tr>
				</table>
				<div id="table-report-'. $name .'">
					<div id="div-all-rows-'. $name .'" class="ns-sub" style="height:72px;resize:vertical;">
						<table id="table-all-rows-'. $name .'" class="widefat fixed">
							<tr class="ns-grey">
								<td class="ns-icon">
									<span class="dashicons '. $icon .'" title="'. __('User registration', 'ninjascanner' ) .'"></span>
								</td>
								<td class="ns-file">'. $message .'</td>
							</tr>
						</table>
					</div>
				</div>
			<br />
			<br />
			';
		}

		if (! empty( $snapshot['various']['ghost_admin'] ) ) {
			$name = 'ghost';
			$message = __('NinjaScanner has detected hidden administrators on your blog:', 'ninjascanner') .'<br /><br />';

			$ghosts = json_decode( $snapshot['various']['ghost_admin'], true );
			foreach( $ghosts as $user ) {
				$message .= "<p style='font-size:15px;'><strong>ID</strong>: ". htmlspecialchars( $user['ID'] ) ." (". translate_user_role('Administrator') .")<br /><strong>user_login</strong>: ". htmlspecialchars( $user['user_login'] ) ."<br /><strong>user_nicename</strong>: ". htmlspecialchars( $user['user_nicename'] ) ."<br /><strong>user_email</strong>: ". htmlspecialchars( $user['user_email'] ) ."<br /><strong>user_registered</strong>: ". htmlspecialchars( $user['user_registered'] ) ."<br /><strong>display_name</strong>: ". htmlspecialchars( $user['display_name'] ) ."</p>";
			}
			$height = ns_max_rows( $ghosts );
			$report['body'] .= '
				<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. $name .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
					<tr class="ns-table-header-tr">
						<td class="ns-table-header-td-icon">
							<span class="dashicons dashicons-admin-users ns-dashicons-header"></span>
						</td>
						<td class="ns-table-header-td-file">
							<strong>'. __( 'Ghost admin', 'ninjascanner' ) .'</strong>
						</td>
					</tr>
				</table>
				<div id="table-report-'. $name .'">
					<div id="div-all-rows-'. $name .'" class="ns-sub" style="height:'. $height .';resize:vertical;">
						<table id="table-all-rows-'. $name .'" class="widefat fixed">
							<tr class="ns-grey">
								<td class="ns-icon">
									<span class="dashicons dashicons-warning ns-modified-file-icon"></span>
								</td>
								<td class="ns-file">'. $message .'</td>
							</tr>
						</table>
					</div>
				</div>
			<br />
			<br />
			';
		}

		if (! empty( $snapshot['various']['user_roles'] ) ) {

			$message = sprintf( __('NinjaScanner has detected that the following user roles have been given <a href="%s">capabilities</a> that, by default, are only assigned to an administrator:', 'ninjascanner'), 'https://codex.wordpress.org/Roles_and_Capabilities#Capability_vs._Role_Table' );

			foreach( $snapshot['various']['user_roles'] as $user => $cap   ) {
				$message .= "<p style='font-size:15px;'>Role: <code>$user</code> - Capabilities: ";
				foreach( $cap as $k => $v ) {
					$message .= "<code>$v</code>, ";
				}
				$message = rtrim( $message, ', ' );
				$message .= "</p>";
			}

			$name = 'roles';
			$height = ns_max_rows( $snapshot['various']['user_roles'] );
			$report['body'] .= '
				<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. $name .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
					<tr class="ns-table-header-tr">
						<td class="ns-table-header-td-icon">
							<span class="dashicons dashicons-admin-users ns-dashicons-header"></span>
						</td>
						<td class="ns-table-header-td-file">
							<strong>'. __( 'Users', 'ninjascanner' ) .'</strong>
						</td>
					</tr>
				</table>
				<div id="table-report-'. $name .'">
					<div id="div-all-rows-'. $name .'" class="ns-sub" style="height:'. $height .'px;resize:vertical;">
						<table id="table-all-rows-'. $name .'" class="widefat fixed">
							<tr class="ns-grey">
								<td class="ns-icon">
									<span class="dashicons dashicons-info ns-unknown-file-icon" title="'. __('User roles', 'ninjascanner' ) .'"></span>
								</td>
								<td class="ns-file">'. $message .'</td>
							</tr>
						</table>
					</div>
				</div>
			<br />
			<br />
			';
		}

		if ( isset( $snapshot['various']['waf'] ) ) {

			$name = 'waf';
			$message = __('No firewall detected.', 'ninjascanner');
			$message .= ' ' . sprintf( __('Consider installing a Web Application Firewall such as <a href="%s">NinjaFirewall (WP Edition)</a> to make sure that your site is well protected against web attacks.', 'ninjascanner'), 'https://wordpress.org/plugins/ninjafirewall/' );
			$report['body'] .= '
				<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. $name .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
					<tr class="ns-table-header-tr">
						<td class="ns-table-header-td-icon">
							<span class="dashicons dashicons-admin-generic ns-dashicons-header"></span>
						</td>
						<td class="ns-table-header-td-file">
							<strong>'. __( 'Web Application Firewall', 'ninjascanner' ) .'</strong>
						</td>
					</tr>
				</table>
				<div id="table-report-'. $name .'">
					<div id="div-all-rows-'. $name .'" class="ns-sub" style="height:72px;">
						<table id="table-all-rows-'. $name .'" class="widefat fixed">
							<tr class="ns-grey">
								<td class="ns-icon">
									<span class="dashicons dashicons-info ns-unknown-file-icon" title="'. __('Web Application Firewall', 'ninjascanner' ) .'"></span>
								</td>
								<td class="ns-file">'. $message .'</td>
							</tr>
						</table>
					</div>
				</div>
			<br />
			<br />
			';
		}
	}

	if ( empty( $files_list ) ) {
		$name = 'various';

		$report['body'] .= '
			<table class="widefat fixed" onClick="nscanjs_roll_unroll(\''. htmlspecialchars( $name ) .'\')" style="cursor: pointer;" title="'. __('Toggle Shade', 'ninjascanner') .'">
				<tr class="ns-table-header-tr">
					<td class="ns-table-header-td-icon">
						<span class="dashicons dashicons-admin-generic ns-dashicons-header"></span>
					</td>
					<td class="ns-table-header-td-file">
						<strong>'. __( 'Various checks', 'ninjascanner' ) .'</strong>
					</td>
				</tr>
			</table>
			<div id="table-report-'. htmlspecialchars( $name ) .'">
		';
		$report['body'] .= ns_no_problem();
		$report['body'] .= '
			</div>
		<br />
		<br />
	';
	}


	}

	if (! empty( $nscan_options['mutipagereport'] ) ) {

		if ( $section > 1 ) {
			$report['body'] .= '<a class="button-secondary" href="?page=NinjaScanner&view-report=1&section='. ( $section - 1 ) .'">&#171; '. __('Previous Page', 'ninjascanner') .'</a>';
		} else {
			$report['body'] .= '<input type="button" class="button-secondary" value="&#171; '. __('Previous Page', 'ninjascanner') .'" disabled />';
		}
		$report['body'] .= '&nbsp;&nbsp;&nbsp;';
		if ( $section < count( $section_list ) ) {
			$report['body'] .= '<a class="button-secondary" href="?page=NinjaScanner&view-report=1&section='. ( $section + 1 ) .'">'. __('Next Page', 'ninjascanner') .' &#187;</a>';
		} else {
			$report['body'] .= '<input type="button" class="button-secondary" value="'. __('Next Page', 'ninjascanner') .' &#187;" disabled />';
		}
		$report['body'] .= '&nbsp;&nbsp;&nbsp; '. __('Quick access:', 'ninjascanner') .' <select onchange=\'window.location="?page=NinjaScanner&view-report=1&section=" + this.value;\'>';
		foreach( $section_list as $num => $section_name ) {
			$page = sprintf( __('Page %s: %s', 'ninjascanner'), $num, htmlspecialchars( $section_name ) );
			if ( $num == $section ) {
				$report['body'] .= "<option value='$num' selected>$page</option>";
			} else {
				$report['body'] .= "<option value='$num'>$page</option>";
			}
		}
		$report['body'] .= '</select>';
	}

	return $report;
}

// =====================================================================
// Scan a theme or plugin folder and return all items.

function nscan_find_files( $target, $flag, &$ret = array() ) {

	$files = scandir( $target );

	foreach( $files as $key => $value ) {
		$path = realpath( "{$target}/{$value}" );
		if (! is_dir( $path ) ) {
			$ret[$path] = $flag;
		} elseif ( $value != '.' && $value != '..' ) {
			nscan_find_files( $path, $flag, $ret );
		}
	}

	return $ret;
}

// =====================================================================
// Display a one row error if test wasn't performed as expected.

function ns_step_error() {

	$ret = '
		<div class="ns-sub" style="height:41px;">
			<table class="widefat fixed">
				<tr style="background-color:#F9F9F9;height:30px">
					<td class="ns-icon">
						<span class="dashicons dashicons-no ns-modified-file-icon" title="'. __('Error', 'ninjascanner' ) .'"></span>
					</td>
					<td class="ns-file-centered">'. __('Warning, a critical error occurred. This test was cancelled.', 'ninjascanner') .'</td>
				</tr>
			</table>
		</div>
	';
	return $ret;
}

// =====================================================================
// Skipped test.

function ns_skipped_test() {

	$ret = '
		<div class="ns-sub" style="height:41px;">
			<table class="widefat fixed">
				<tr style="background-color:#F9F9F9;height:30px">
					<td class="ns-icon">
						<span class="dashicons dashicons-minus ns-skip-file-icon" title="'. __('This test was skipped.', 'ninjascanner') .'"></span>
					</td>
					<td class="ns-file-centered">'. __('This test was skipped.', 'ninjascanner') .'</td>
				</tr>
			</table>
		</div>
	';
	return $ret;
}
// =====================================================================
// No previous snapshots.

function ns_noprevioussnapshots() {

	$ret = '
		<div class="ns-sub" style="height:41px;">
			<table class="widefat fixed">
				<tr style="background-color:#F9F9F9;height:30px">
					<td class="ns-icon">
						<span class="dashicons dashicons-minus ns-skip-file-icon" title="'. __('This test was skipped because no previous snapshots were found.', 'ninjascanner') .'"></span>
					</td>
					<td class="ns-file-centered">'. __('This test was skipped because no previous snapshots were found.', 'ninjascanner') .'</td>
				</tr>
			</table>
		</div>
	';
	return $ret;
}

// =====================================================================
// No problem detected.

function ns_no_problem() {

	$ret = '
		<div class="ns-sub" style="height:41px;">
			<table class="widefat fixed">
				<tr style="background-color:#F9F9F9;height:30px">
					<td class="ns-icon">
						<span class="dashicons dashicons-marker ns-ok-file-icon" title="'. __('No problem detected.', 'ninjascanner') .'"></span>
					</td>
					<td class="ns-file-top">'. __('No problem detected.', 'ninjascanner') .'</td>
				</tr>
			</table>
		</div>
	';
	return $ret;
}

// =====================================================================
// Fetch ignored and quarantine lists so that we can exclude their
// files from our report (the user may have moved some files to those
// lists without running a new scan to refresh the current snapshot).

function nscan_retrieve_excluded_files() {

	$ignored_quarantined = array();

	// Quarantined files list:
	if ( file_exists( NSCAN_QUARANTINE .'/quarantine.php' ) ) {
		$ignored_quarantined['quarantined'] = unserialize( file_get_contents( NSCAN_QUARANTINE .'/quarantine.php' ) );
	}
	// Ignored files list:
	if ( file_exists( NSCAN_IGNORED_LOG ) ) {
		$ignored_quarantined['ignored'] = unserialize( file_get_contents( NSCAN_IGNORED_LOG ) );
	}

	return $ignored_quarantined;
}

// =====================================================================
// Remove ignored files from the list (files may have been marked
// as ignored since last scan).

function ns_remove_ignored( $files_list, $ignored_quarantined ) {

	if ( empty( $files_list ) || empty( $ignored_quarantined['ignored'] ) ) {
		return $files_list;
	}

	foreach( $files_list as $file => $null ) {
		if (! empty( $file ) && isset( $ignored_quarantined['ignored'][$file] ) ) {
			// Remove it from our list if it is in the ingored list:
			unset( $files_list[$file] );
		}
	}
	return $files_list;
}

// =====================================================================
// Removed quarantined files from the list (files may have been marked
// as quarantined since last scan).

function ns_remove_quarantined( $files_list, $ignored_quarantined ) {

	if ( empty( $files_list ) || empty( $ignored_quarantined['quarantined'] ) ) {
		return $files_list;
	}

	foreach( $files_list as $file => $null ) {
		if (! empty( $file ) && isset( $ignored_quarantined['quarantined'][$file] ) ) {
			// Remove it from our list if it is in the ingored list:
			unset( $files_list[$file] );
		}
	}
	return $files_list;
}

// =====================================================================
// Build table rows with the list of posts/pages.

function ns_build_db_rows( $files_list, $id, $table_name ) {

	$nonce = wp_create_nonce( 'nscan_file_op' );

	$height = ns_max_rows( $files_list );
	$ret = '
		<div id="div-all-rows-'. $table_name .'" class="ns-sub" style="height:'. $height .'px;resize:vertical;">
			<table id="table-all-rows-'. $table_name .'" class="widefat fixed">
	';

	$row = 0;
	$items = 0;

	foreach( $files_list as $p_path => $tmp ) {

		list( $p_id, $what ) = explode( ':', $tmp );

		++$row;
		if ( $row % 2 == 0 ) {
			$r_color = 'ns-white';
		} else {
			$r_color = 'ns-grey';
		}

		$display_name = htmlspecialchars( $p_path );
		$unique_id = uniqid( "$id-" );
		$dashboard_url = htmlspecialchars( get_dashboard_url() );

		// ===============================================================
		$title_info_post = __('Display info about this post.', 'ninjascanner');
		$title_info_page = __('Display info about this page.', 'ninjascanner');

		$post_info = __('Post info', 'ninjascanner');
		$page_info = __('Page info', 'ninjascanner');

		$post_view = __('View post', 'ninjascanner' );
		$page_view = __('View page', 'ninjascanner' );

		$label_id = __('ID:', 'ninjascanner' );
		$label_type = __('Type:', 'ninjascanner' );
		$post = __('Post', 'ninjascanner' );
		$page = __('Page', 'ninjascanner' );

		// Pages
		if ( $id == 'page' ) {

			++$items;

			if ( $what == 1 ) { // Added
				$ff_icon = 'dashicons-welcome-add-page';
				$file_modified = 'Added page';

			} elseif ( $what == 2 ) { // Modified
				$ff_icon = 'dashicons-welcome-write-blog';
				$file_modified = 'Modified page';

			} else { // Deleted
				$ff_icon = 'dashicons-trash';
				$file_modified = 'Deleted page';
			}
			$ret .= '
			<tr id="hide-row-'. $unique_id .'" class="'. $r_color .'">
				<td class="ns-icon">
					<span class="dashicons '. $ff_icon .' ns-orange-file-icon" title="'. $file_modified .'"></span>
				</td>
				<td class="ns-file">
					'. $display_name .'
					<br />
					<label class="ns-label-menu '. NSCAN_ROW_ACTIONS .'">
						<a onClick="nscanjs_file_info('. "'{$unique_id}','{$table_name}'" .')" title="'. $title_info_page .'">'. $page_info .'</a> |';
			if ( $what != 3 ) {
				$ret .= '
						<a onClick="nscanjs_view_post('. $p_id . ", '{$dashboard_url}'" . ')" title="'. $page_view .'">'. $page_view .'</a>';
			} else {
				$ret = rtrim( $ret, '|' );
			}
			$ret .= '
					</label>
					<div id="file-info-'. $unique_id .'" style="display:none">
						<table style="width:100%;background-color:#F7F7F7;border:solid 1px #DFDFDF;">
							<tr>
								<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $label_id .'</td>
								<td style="width:92%;padding:1;">'. $p_id .'</td>
							</tr>
							<tr>
								<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $label_type .'</td>
								<td style="width:92%;padding:1;">'. $page .'</td>

							</tr>
						</table>
					</div>
				</td>
			</tr>
			';

		//Posts
		} else {

			++$items;

			if ( $what == 1 ) { // Added
				$ff_icon = 'dashicons-welcome-add-page';
				$file_modified = 'Added post';

			} elseif ( $what == 2 ) { // Modified
				$ff_icon = 'dashicons-welcome-write-blog';
				$file_modified = 'Modified post';

			} else { // Deleted
				$ff_icon = 'dashicons-trash';
				$file_modified = 'Deleted post';
			}
			$ret .= '
			<tr id="hide-row-'. $unique_id .'" class="'. $r_color .'">
				<td class="ns-icon">
					<span class="dashicons '. $ff_icon .' ns-orange-file-icon" title="'. $file_modified .'"></span>
				</td>
				<td class="ns-file">
					'. $display_name .'
					<br />
					<label class="ns-label-menu '. NSCAN_ROW_ACTIONS .'">
						<a onClick="nscanjs_file_info('. "'{$unique_id}','{$table_name}'" .')" title="'. $title_info_post .'">'. $post_info .'</a> |';
			if ( $what != 3 ) {
				$ret .= '
						<a onClick="nscanjs_view_post('. $p_id . ", '{$dashboard_url}'" . ')" title="'. $post_view .'">'. $post_view .'</a>';
			} else {
				$ret = rtrim( $ret, '|' );
			}
			$ret .= '
					</label>
					<div id="file-info-'. $unique_id .'" style="display:none">
						<table style="width:100%;background-color:#F7F7F7;border:solid 1px #DFDFDF;">
							<tr>
								<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $label_id .'</td>
								<td style="width:92%;padding:1;">'. $p_id .'</td>
							</tr>
							<tr>
								<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $label_type .'</td>
								<td style="width:92%;padding:1;">'. $post .'</td>

							</tr>
						</table>
					</div>
				</td>
			</tr>
			';
		}
	}
	$ret .= '
			</table>
		</div>
	';
	// Show total items (can be decremented by JS functions):
	$ret .= '<p>'. __( 'Total items:', 'ninjascanner' );
	$ret .= sprintf (' <font id="total-items-row-%s">%s</font></p>', $table_name, $items );

	return $ret;
}

// =====================================================================
// Display max rows.

function ns_max_rows( $list ) {

	// Div height: we allow max NSCAN_MAX_ROWS rows (70px):
	$count = count( $list );
	if ( $count > NSCAN_MAX_ROWS ) {
		$height = NSCAN_MAX_ROWS * 70 + 2;
	} elseif ( $count == 1 ) {
		$height =  70 + 2;
	} else {
		$height = $count * 70 + 2;
	}

	return $height;
}

// =====================================================================
// Build table rows with the list of files.

function ns_build_rows( $files_list, $id, $table_name ) {

	$nonce = wp_create_nonce( 'nscan_file_op' );

	$height = ns_max_rows( $files_list );
	$ret = '
		<div id="div-all-rows-'. $table_name .'" class="ns-sub" style="height:'. $height .'px;resize:vertical;">
			<table id="table-all-rows-'. $table_name .'" class="widefat fixed">
	';
	$row = 0;
	$items = 0;

	foreach( $files_list as $file => $what ) {

		++$row;
		if ( $row % 2 == 0 ) {
			$r_color = 'ns-white';
		} else {
			$r_color = 'ns-grey';
		}

		$display_name = htmlspecialchars( $file );
		if (! defined( 'NSCAN_ABSOLUTE_PATH' ) || NSCAN_ABSOLUTE_PATH === false ) {
			$display_name = str_replace( ABSPATH, '', $display_name );
		} else {
			$display_name = str_replace( ABSPATH, ABSPATH .'<strong>', $display_name .'</strong>' );
		}
		$encoded_name = base64_encode( $file );
		$unique_id = uniqid( "$id-" );

		// ===============================================================
		$title_info = __('Display info about this file.', 'ninjascanner');
		$title_view = __('View this file.', 'ninjascanner');
		$title_compare = __('Compare this file to the original one.', 'ninjascanner');
		$title_restore = __('Restore the original file.', 'ninjascanner' );
		$title_ignore = __('Move this file to the ignored files list (until it is modified again).', 'ninjascanner');
		$title_quarantine = __('Move this file to the quarantined files list.', 'ninjascanner');
		$title_google = __('View Google Safe Browsing report.', 'ninjascanner');

		$file_modified = __('Modified file', 'ninjascanner');
		$file_deleted = __('Deleted file', 'ninjascanner');
		$file_info = __('File info', 'ninjascanner');
		$file_unknown = __('Unknown file', 'ninjascanner');
		$file_view = __('View file', 'ninjascanner' );
		$file_changes = __('View changes', 'ninjascanner');
		$file_restore =__('Restore file', 'ninjascanner');
		$file_ignore = __('Ignore file', 'ninjascanner');
		$file_quarantine = __('Quarantine file', 'ninjascanner');
		$file_mu = __('Must-Use plugin', 'ninjascanner');
		$file_dropins = __('Drop-Ins plugin', 'ninjascanner');
		$file_suspicious = __('Suspicious file', 'ninjascanner');
		$site_google = __('Site is on Google Safe Browsing blacklist.', 'ninjascanner');

		$f_size = __('Size:', 'ninjascanner');
		$f_modify = __('Modify:', 'ninjascanner');
		$f_access = __('Permissions:', 'ninjascanner');
		$f_uidgid = __('UID / GID:', 'ninjascanner');
		$f_change = __('Change:', 'ninjascanner');
		$f_note = __('Info:', 'ninjascanner');
		$f_virus = __('Detection:', 'ninjascanner');

		// ===============================================================
		// Item may have been deleted since last scan:

		if ( $id == 'googlesb' || ( $id == 'filesnapshot' && $what == 3 ) ) {
			goto NO_STATS;
		}

		$file_stats = nscan_get_file_stats( $file );
		if (! empty( $file_stats['error'] ) ) {
			// The file was likely deleted since last scan:
			$ret .= '
			<tr class="'. $r_color .'">
				<td class="ns-icon">
					<span class="dashicons dashicons-trash ns-grey-file-icon" title="'. $file_deleted .'"></span>
				</td>
				<td class="ns-file">
					'. $display_name .'
					<br>
					<label class="ns-label-menu">
					'. $file_stats['error'] .'
					</label>
				</td>
			</tr>
			';
			continue;
		}
NO_STATS:

		// ===============================================================
		// WordPress core (core_mismatch, core_unknown)

		if ( $id == 'wordpress' ) {

			if ( $what == 'core_mismatch' ) {

				++$items;

				$ret .= '
				<tr id="hide-row-'. $unique_id .'" class="'. $r_color .'">
					<td class="ns-icon">
						<span class="dashicons dashicons-dismiss ns-modified-file-icon" title="'. $file_modified .'"></span>
					</td>
					<td class="ns-file">
						'. $display_name .'
						<br />
						<label class="ns-label-menu '. NSCAN_ROW_ACTIONS .'">
							<a onClick="nscanjs_file_info('. "'{$unique_id}','{$table_name}'" .')" title="'. $title_info .'">'. $file_info .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','view','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_view .'">'. $file_view .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','compare','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_compare .'">'. $file_changes .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','restore','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_restore .'">'. $file_restore .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','ignore','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_ignore .'">'. $file_ignore .'</a>
						</label>
						<div id="file-info-'. $unique_id .'" style="display:none">
							<table style="width:100%;background-color:#F7F7F7;border:solid 1px #DFDFDF;">
								<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_size .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[1] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_modify .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[2] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_access .'</td>
									<td style="width:46%;padding:1;">'. $file_stats[3] .'</td>
								</tr>
								<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_uidgid .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[4] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_change .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[5] .'</td>
									<td class="table-file-info">'. $f_note .'</td>
									<td style="width:46%;padding:1;">'. sprintf( __('This file does not match the original %s file and may have been damaged or infected.', 'ninjascanner' ), 'WordPress' ).'</td>
								</tr>
							</table>
						</div>
					</td>
				</tr>
				';

			} elseif ( $what == 'core_unknown' ) {

				++$items;

				$ret .= '
				<tr id="hide-row-'. $unique_id .'" class="'. $r_color .'">
					<td class="ns-icon">
						<span class="dashicons dashicons-editor-help ns-unknown-file-icon" title="'. $file_unknown .'"></span>
					</td>
					<td class="ns-file">
						'. $display_name .'
						<br />
						<label class="ns-label-menu '. NSCAN_ROW_ACTIONS .'">
							<a onClick="nscanjs_file_info('. "'{$unique_id}','{$table_name}'" .')" title="'. $title_info .'">'. $file_info .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','view','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_view .'">'. $file_view .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','ignore','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_ignore .'">'. $file_ignore .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','quarantine','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_quarantine .'">'. $file_quarantine .'</a>
						</label>
						<div id="file-info-'. $unique_id .'" style="display:none">
							<table style="width:100%;background-color:#F7F7F7;border:solid 1px #DFDFDF;">
							<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_size .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[1] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_modify .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[2] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_access .'</td>
									<td style="width:46%;padding:1;">'. $file_stats[3] .'</td>
								</tr>
								<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_uidgid .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[4] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_change .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[5] .'</td>
									<td class="table-file-info">'. $f_note .'</td>
									<td style="width:46%;padding:1;">'. sprintf( __('This file is not part of the original %s package and may have been uploaded by someone else.', 'ninjascanner' ), 'WordPress' ).'</td>
								</tr>
							</table>
						</div>
					</td>
				</tr>
				';
			}

		// ===============================================================
		// Plugins table:

		} elseif ( $id == 'plugin' ) {

			// 0 == Unknown file found inside a plugin folder.
			// 2 == Unknown plugin (maybe a premium plugin not available in wp.org repo).
			if ( $what == 0 || $what == 2 ) {

				++$items;

				$ret .= '
				<tr id="hide-row-'. $unique_id .'" class="'. $r_color .'">
					<td class="ns-icon">
						<span class="dashicons dashicons-editor-help ns-unknown-file-icon" title="'. $file_unknown .'"></span>
					</td>
					<td class="ns-file">
						'. $display_name .'
						<br />
						<label class="ns-label-menu '. NSCAN_ROW_ACTIONS .'">
							<a onClick="nscanjs_file_info('. "'{$unique_id}','{$table_name}'" .')" title="'. $title_info .'">'. $file_info .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','view','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_view .'">'. $file_view .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','ignore','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_ignore .'">'. $file_ignore .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','quarantine','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_quarantine .'">'. $file_quarantine .'</a>
						</label>
						<div id="file-info-'. $unique_id .'" style="display:none">
							<table style="width:100%;background-color:#F7F7F7;border:solid 1px #DFDFDF;">
							<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_size .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[1] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_modify .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[2] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_access .'</td>
									<td style="width:46%;padding:1;">'. $file_stats[3] .'</td>
								</tr>
								<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_uidgid .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[4] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_change .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[5] .'</td>
									<td class="table-file-info">'. $f_note .'</td>';

				if ( $what == 0 ) {
					$ret .= '
									<td style="width:46%;padding:1;">'. __('This file is unknown and may have been uploaded by someone else.', 'ninjascanner' ).'</td>';
				} else {
					$ret .= '<td style="width:46%;padding:1;">'. sprintf( __('This file is from an unknown package. If it is a premium plugin, <a href="%s">consult our blog</a> to learn how you can include it in the file integrity checker.', 'ninjascanner' ), 'https://blog.nintechnet.com/ninjascanner-powerful-antivirus-scanner-for-wordpress/#integrity' ).'</td>';
				}
				$ret .= '
								</tr>
							</table>
						</div>
					</td>
				</tr>
				';

			// 1 == File does not match the original plugin file.
			} elseif ( $what == 1 ) {

				++$items;

				$ret .= '
				<tr id="hide-row-'. $unique_id .'" class="'. $r_color .'">
					<td class="ns-icon">
						<span class="dashicons dashicons-dismiss ns-modified-file-icon" title="'. $file_modified .'"></span>
					</td>
					<td class="ns-file">
						'. $display_name .'
						<br />
						<label class="ns-label-menu '. NSCAN_ROW_ACTIONS .'">
							<a onClick="nscanjs_file_info('. "'{$unique_id}','{$table_name}'" .')" title="'. $title_info .'">'. $file_info .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','view','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_view .'">'. $file_view .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','compare','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_compare .'">'. $file_changes .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','restore','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_restore .'">'. $file_restore .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','ignore','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_ignore .'">'. $file_ignore .'</a>
						</label>
						<div id="file-info-'. $unique_id .'" style="display:none">
							<table style="width:100%;background-color:#F7F7F7;border:solid 1px #DFDFDF;">
								<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_size .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[1] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_modify .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[2] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_access .'</td>
									<td style="width:46%;padding:1;">'. $file_stats[3] .'</td>
								</tr>
								<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_uidgid .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[4] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_change .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[5] .'</td>
									<td class="table-file-info">'. $f_note .'</td>
									<td style="width:46%;padding:1;">'. sprintf( __('This file does not match the original %s file and may have been damaged or infected.', 'ninjascanner' ), htmlspecialchars( $id ) ).'</td>
								</tr>
							</table>
						</div>
					</td>
				</tr>
				';

			// 3 == MU plugin
			} elseif ( $what == 3 ) {

				++$items;

				$ret .= '
				<tr id="hide-row-'. $unique_id .'" class="'. $r_color .'">
					<td class="ns-icon">
						<span class="dashicons dashicons-sos ns-unknown-file-icon" title="'. $file_mu .'"></span>
					</td>
					<td class="ns-file">
						'. $display_name .'
						<br />
						<label class="ns-label-menu '. NSCAN_ROW_ACTIONS .'">
							<a onClick="nscanjs_file_info('. "'{$unique_id}','{$table_name}'" .')" title="'. $title_info .'">'. $file_info .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','view','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_view .'">'. $file_view .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','ignore','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_ignore .'">'. $file_ignore .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','quarantine','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_quarantine .'">'. $file_quarantine .'</a>
						</label>
						<div id="file-info-'. $unique_id .'" style="display:none">
							<table style="width:100%;background-color:#F7F7F7;border:solid 1px #DFDFDF;">
							<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_size .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[1] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_modify .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[2] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_access .'</td>
									<td style="width:46%;padding:1;">'. $file_stats[3] .'</td>
								</tr>
								<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_uidgid .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[4] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_change .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[5] .'</td>
									<td class="table-file-info">'. $f_note .'</td>
									<td style="width:46%;padding:1;">'. sprintf( __('This file is a <a href="%s">Must-Use plugin</a> and could not be compared to the original one. Make sure it was not tampered with or installed by someone else.', 'ninjascanner' ), 'https://codex.wordpress.org/Must_Use_Plugins' ).'</td>
								</tr>
							</table>
						</div>
					</td>
				</tr>
				';

			// 4 == Drop-ins plugin
			} elseif ( $what == 4 ) {

				++$items;

				$ret .= '
				<tr id="hide-row-'. $unique_id .'" class="'. $r_color .'">
					<td class="ns-icon">
						<span class="dashicons dashicons-sos ns-unknown-file-icon" title="'. $file_dropins .'"></span>
					</td>
					<td class="ns-file">
						'. $display_name .'
						<br />
						<label class="ns-label-menu '. NSCAN_ROW_ACTIONS .'">
							<a onClick="nscanjs_file_info('. "'{$unique_id}','{$table_name}'" .')" title="'. $title_info .'">'. $file_info .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','view','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_view .'">'. $file_view .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','ignore','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_ignore .'">'. $file_ignore .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','quarantine','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_quarantine .'">'. $file_quarantine .'</a>
						</label>
						<div id="file-info-'. $unique_id .'" style="display:none">
							<table style="width:100%;background-color:#F7F7F7;border:solid 1px #DFDFDF;">
							<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_size .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[1] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_modify .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[2] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_access .'</td>
									<td style="width:46%;padding:1;">'. $file_stats[3] .'</td>
								</tr>
								<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_uidgid .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[4] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_change .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[5] .'</td>
									<td class="table-file-info">'. $f_note .'</td>
									<td style="width:46%;padding:1;">'. __('This file is a Drop-Ins plugin (a plugin that can be used to replace some core functionality of WordPress) and could not be compared to the original one. Make sure it was not tampered with or installed by someone else.', 'ninjascanner' ) .'</td>
								</tr>
							</table>
						</div>
					</td>
				</tr>
				';
			}

		// ===============================================================
		// Themes table:

		} elseif ( $id == 'theme' ) {

			// 0 == Unknown file found inside a theme folder.
			// 2 == Unknown theme (maybe a premium theme not available in wp.org repo).
			if ( $what == 0 || $what == 2 ) {

				++$items;

				$ret .= '
				<tr id="hide-row-'. $unique_id .'" class="'. $r_color .'">
					<td class="ns-icon">
						<span class="dashicons dashicons-editor-help ns-unknown-file-icon" title="'. $file_unknown .'"></span>
					</td>
					<td class="ns-file">
						'. $display_name .'
						<br />
						<label class="ns-label-menu '. NSCAN_ROW_ACTIONS .'">
							<a onClick="nscanjs_file_info('. "'{$unique_id}','{$table_name}'" .')" title="'. $title_info .'">'. $file_info .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','view','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_view .'">'. $file_view .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','ignore','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_ignore .'">'. $file_ignore .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','quarantine','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_quarantine .'">'. $file_quarantine .'</a>
						</label>
						<div id="file-info-'. $unique_id .'" style="display:none">
							<table style="width:100%;background-color:#F7F7F7;border:solid 1px #DFDFDF;">
							<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_size .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[1] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_modify .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[2] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_access .'</td>
									<td style="width:46%;padding:1;">'. $file_stats[3] .'</td>
								</tr>
								<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_uidgid .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[4] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_change .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[5] .'</td>
									<td class="table-file-info">'. $f_note .'</td>';

				if ( $what == 0 ) {
					$ret .= '
									<td style="width:46%;padding:1;">'. __('This file is unknown and may have been uploaded by someone else.', 'ninjascanner' ).'</td>';
				} else {
					$ret .= '<td style="width:46%;padding:1;">'. sprintf( __('This file is from an unknown package. If it is a premium theme, <a href="%s">consult our blog</a> to learn how you can include it in the file integrity checker.', 'ninjascanner' ), 'https://blog.nintechnet.com/ninjascanner-powerful-antivirus-scanner-for-wordpress/#integrity' ).'</td>';
				}
				$ret .= '
								</tr>
							</table>
						</div>
					</td>
				</tr>
				';

			// 1 == File does not match the original theme file.
			} elseif ( $what == 1 ) {

				++$items;

				$ret .= '
				<tr id="hide-row-'. $unique_id .'" class="'. $r_color .'">
					<td class="ns-icon">
						<span class="dashicons dashicons-dismiss ns-modified-file-icon" title="'. $file_modified .'"></span>
					</td>
					<td class="ns-file">
						'. $display_name .'
						<br />
						<label class="ns-label-menu '. NSCAN_ROW_ACTIONS .'">
							<a onClick="nscanjs_file_info('. "'{$unique_id}','{$table_name}'" .')" title="'. $title_info .'">'. $file_info .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','view','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_view .'">'. $file_view .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','compare','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_compare .'">'. $file_changes .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','restore','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_restore .'">'. $file_restore .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','ignore','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_ignore .'">'. $file_ignore .'</a>
						</label>
						<div id="file-info-'. $unique_id .'" style="display:none">
							<table style="width:100%;background-color:#F7F7F7;border:solid 1px #DFDFDF;">
								<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_size .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[1] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_modify .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[2] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_access .'</td>
									<td style="width:46%;padding:1;">'. $file_stats[3] .'</td>
								</tr>
								<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_uidgid .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[4] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_change .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[5] .'</td>
									<td class="table-file-info">'. $f_note .'</td>
									<td style="width:46%;padding:1;">'. sprintf( __('This file does not match the original %s file and may have been damaged or infected.', 'ninjascanner' ), htmlspecialchars( $id ) ).'</td>
								</tr>
							</table>
						</div>
					</td>
				</tr>
				';
			}

		// ===============================================================
		// Files and folders table:
		} elseif ( $id == 'filesfolders' ) {

			++$items;

			if ( $what == 1 ) { // hidden
				$ff_icon = 'dashicons-hidden';
				$file_modified = 'Hidden script';
				$file_note = __('This file is a hidden PHP script.', 'ninjascanner');

			} elseif ( $what == 2 ) { // binary
				$ff_icon = 'dashicons-admin-generic';
				$file_modified = 'Binary file';
				$bin = nscan_get_bin_type( $file );
				$file_note = sprintf( __('This file is a %s executable file.', 'ninjascanner'), $bin );

			} elseif ( $what == 3 ) { // symlink
				$ff_icon = 'dashicons-admin-links';
				$file_modified = 'Symlink';
				$file_note = sprintf( __('This is a symbolic link poiting to %s', 'ninjascanner'), '<code>'. $file_stats[6] .'</code>');

			} else { // unreadable
				$ff_icon = 'dashicons-lock';
				$file_modified = 'Unreadable file/folder';
				$file_note = __('This file or folder is unreadable, NinjaScanner cannot scan it.', 'ninjascanner');

			}
			$ret .= '
				<tr id="hide-row-'. $unique_id .'" class="'. $r_color .'">
					<td class="ns-icon">
						<span class="dashicons '. $ff_icon .' ns-orange-file-icon" title="'. $file_modified .'"></span>
					</td>
					<td class="ns-file">
						'. $display_name .'
						<br />
						<label class="ns-label-menu '. NSCAN_ROW_ACTIONS .'">
							<a onClick="nscanjs_file_info('. "'{$unique_id}','{$table_name}'" .')" title="'. $title_info .'">'. $file_info .'</a> |
						';

			// We don't view binary or unreadable files:
			if ( $what != 2 && $what != 4 ) {
				$ret .= '
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','view','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_view .'">'. $file_view .'</a> |
						';
			}
			$ret .= '
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','ignore','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_ignore .'">'. $file_ignore .'</a> |';

			// We can't quarantine unreadable files:
			if ( $what != 4 ) {
				$ret .= '
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','quarantine','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_quarantine .'">'. $file_quarantine .'</a>
						';
			} else {
				$ret = rtrim( $ret, "|" );
			}
			$ret .= '
						</label>
						<div id="file-info-'. $unique_id .'" style="display:none">
							<table style="width:100%;background-color:#F7F7F7;border:solid 1px #DFDFDF;">
								<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_size .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[1] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_modify .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[2] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_access .'</td>
									<td style="width:46%;padding:1;">'. $file_stats[3] .'</td>
								</tr>
								<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_uidgid .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[4] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_change .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[5] .'</td>
									<td class="table-file-info">'. $f_note .'</td>
									<td style="width:46%;padding:1;">'. $file_note .'</td>
								</tr>
							</table>
						</div>
					</td>
				</tr>
			';

		// ===============================================================
		// Google Safe Browsing:
		} elseif ( $id == 'googlesb' ) {

			++$items;

			$ret .= '
			<tr id="hide-row-'. $unique_id .'" class="'. $r_color .'">
				<td class="ns-icon">
					<span class="dashicons dashicons-dismiss ns-modified-file-icon" title="'. $site_google .'"></span>
				</td>
				<td class="ns-file">
					'. $display_name .'
					<br />
					<label class="ns-label-menu '. NSCAN_ROW_ACTIONS .'">
						<a onClick="nscanjs_file_info('. "'{$unique_id}','{$table_name}'" .')" title="'. $title_info .'">'. $file_info .'</a> |
						<a target="_blank" href="https://transparencyreport.google.com/safe-browsing/search?url='. urlencode( $file ) .'" title="'. $title_google .'">'. $title_google .'</a>
					</label>
					<div id="file-info-'. $unique_id .'" style="display:none">
						<table style="width:100%;background-color:#F7F7F7;border:solid 1px #DFDFDF;">
							<tr>
								<td class="table-file-info">'. $f_note .'</td>
								<td style="padding:1;">'. $site_google .'</td>
							</tr>
						</table>
					</div>
				</td>
			</tr>
			';

		// ===============================================================
		// Anti-malware
		} elseif ( $id == 'antimalware' ) {

			++$items;

			$what = preg_replace( '/^{[HR]EX\d?}/', '', $what );

			$ret .= '
			<tr id="hide-row-'. $unique_id .'" class="'. $r_color .'">
				<td class="ns-icon">
					<span class="dashicons dashicons-dismiss ns-modified-file-icon" title="'. $file_suspicious .'"></span>
					</td>
					<td class="ns-file">
						'. $display_name .'
						<br />
						<label class="ns-label-menu '. NSCAN_ROW_ACTIONS .'">
							<a onClick="nscanjs_file_info('. "'{$unique_id}','{$table_name}'" .')" title="'. $title_info .'">'. $file_info .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','view','{$nonce}','{$unique_id}','{$table_name}','{$what}'" .')" title="'. $title_view .'">'. $file_view .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','ignore','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_ignore .'">'. $file_ignore .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','quarantine','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_quarantine .'">'. $file_quarantine .'</a>
						</label>
						<div id="file-info-'. $unique_id .'" style="display:none">
							<table style="width:100%;background-color:#F7F7F7;border:solid 1px #DFDFDF;">
							<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_size .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[1] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_modify .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[2] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_access .'</td>
									<td style="width:46%;padding:1;">'. $file_stats[3] .'</td>
								</tr>
								<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_uidgid .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[4] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_change .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[5] .'</td>
									<td class="table-file-info">'. $f_virus .'</td>
									<td style="width:46%;padding:1;">'. $what .'</td>
								</tr>
							</table>
						</div>
					</td>
				</tr>
			';

		// ===============================================================
		// File snapshot:
		} elseif ( $id == 'filesnapshot' ) {

			++$items;

			if ( $what == 1 ) { // New file
				$ff_icon = 'dashicons-welcome-add-page';
				$file_modified = 'New file';
				$file_note = __('This file was added since last scan.', 'ninjascanner');

			} elseif ( $what == 2 ) { // Modified file
				$ff_icon = 'dashicons-welcome-write-blog';
				$file_modified = 'Modified file';
				$file_note = __('This file was modified since last scan.', 'ninjascanner');

			} else { // Deleted file
				$ff_icon = 'dashicons-trash';
				$file_modified = 'Deleted file';
				$file_note = __('This file was either deleted, quarantined or ignored since last scan.', 'ninjascanner');
			}

			$ret .= '
			<tr id="hide-row-'. $unique_id .'" class="'. $r_color .'">
				<td class="ns-icon">
					<span class="dashicons '. $ff_icon .' ns-orange-file-icon" title="'. $file_modified .'"></span>
					</td>
					<td class="ns-file">
						'. $display_name .'
						<br />
			';
			if ( $what != 3 ) {
				$ret .= '
						<label class="ns-label-menu '. NSCAN_ROW_ACTIONS .'">
							<a onClick="nscanjs_file_info('. "'{$unique_id}','{$table_name}'" .')" title="'. $title_info .'">'. $file_info .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','view','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_view .'">'. $file_view .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','quarantine','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_quarantine .'">'. $file_quarantine .'</a>
						</label>
						<div id="file-info-'. $unique_id .'" style="display:none">
							<table style="width:100%;background-color:#F7F7F7;border:solid 1px #DFDFDF;">
								<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_size .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[1] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_modify .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[2] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_access .'</td>
									<td style="width:46%;padding:1;">'. $file_stats[3] .'</td>
								</tr>
								<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_uidgid .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[4] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_change .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[5] .'</td>
									<td class="table-file-info">'. $f_note .'</td>
									<td style="width:46%;padding:1;">'. $file_note .'</td>
								</tr>
				';
			// Deleted file:
			} else {
				$ret .= '
						<label class="ns-label-menu '. NSCAN_ROW_ACTIONS .'">
							<a onClick="nscanjs_file_info('. "'{$unique_id}','{$table_name}'" .')" title="'. $title_info .'">'. $file_info .'</a>
						</label>
						<div id="file-info-'. $unique_id .'" style="display:none">
							<table style="width:100%;background-color:#F7F7F7;border:solid 1px #DFDFDF;">
								<tr>
									<td class="table-file-info">'. $f_note .'</td>
									<td style="padding:1;">'. $file_note .'</td>
								</tr>
				';
			}

			$ret .= '
							</table>
						</div>
					</td>
				</tr>
			';

		// ===============================================================
		// SSH keys:

		} elseif ( $id == 'ssh' ) {

			++$items;

			$unknown = __('Unknown SSH key', 'ninjascanner');
			$file_note = __('This is a SSH key and allows a user to connect passwordless to your site over SSH. Make sure that this file was not uploaded by someone else.', 'ninjascanner');

			$ret .= '
				<tr id="hide-row-'. $unique_id .'" class="'. $r_color .'">
					<td class="ns-icon">
						<span class="dashicons dashicons-editor-help ns-unknown-file-icon" title="'. $unknown .'"></span>
					</td>
					<td class="ns-file">
						'. $display_name .'
						<br />
						<label class="ns-label-menu '. NSCAN_ROW_ACTIONS .'">
							<a onClick="nscanjs_file_info('. "'{$unique_id}','{$table_name}'" .')" title="'. $title_info .'">'. $file_info .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','view','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_view .'">'. $file_view .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','ignore','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_ignore .'">'. $file_ignore .'</a> |
							<a onClick="nscanjs_file_operation('. "'{$encoded_name}','quarantine','{$nonce}','{$unique_id}','{$table_name}'" .')" title="'. $title_quarantine .'">'. $file_quarantine .'</a>
						</label>
						<div id="file-info-'. $unique_id .'" style="display:none">
							<table style="width:100%;background-color:#F7F7F7;border:solid 1px #DFDFDF;">
								<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_size .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[1] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_modify .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[2] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_access .'</td>
									<td style="width:46%;padding:1;">'. $file_stats[3] .'</td>
								</tr>
								<tr>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_uidgid .'</td>
									<td style="width:10%;padding:1;">'. $file_stats[4] .'</td>
									<td style="width:8%;font-weight:bold;padding:1;text-align:right">'. $f_change .'</td>
									<td style="width:20%;padding:1;">'. $file_stats[5] .'</td>
									<td class="table-file-info">'. $f_note .'</td>
									<td style="width:46%;padding:1;">'. $file_note .'</td>
								</tr>
							</table>
						</div>
					</td>
				</tr>
			';
		}
	}

	$ret .= '
			</table>
		</div>
	';

	// Show total items (can be decremented by JS functions):
	$ret .= '<p>'. __( 'Total items:', 'ninjascanner' );
	$ret .= sprintf (' <font id="total-items-row-%s">%s</font></p>', $table_name, $items );

	return $ret;
}

// =====================================================================
// Return the binay type of a file.

function nscan_get_bin_type( $file ) {

	$header = '';
	$fh = fopen( $file, 'r' );
	if ( ( $data = fread( $fh, 4 ) ) !== false ) {
		if ( preg_match('`^\x7F\x45\x4C\x46`', $data ) ) {
			$header = 'ELF';
		} elseif( preg_match('`^\x4D\x5A`', $data ) ) {
			$header = 'Microsoft MZ';
		}
	}
	fclose( $fh );
	if ( empty( $header ) ) {
		$header = 'unknown';
	}
	return $header;

}

// =====================================================================
// Return info about a file or folder.

function nscan_get_file_stats( $file ) {

	$file_stats = array();

	// Make sure the file was not deleted since last scan:
	if (! file_exists( $file ) ) {
		$file_stats['error'] = __('Missing file: it may have been deleted or quarantined since last scan. To refresh the list, run a new scan.', 'ninjascanner' );
		return $file_stats;
	}

	$stat = stat( $file );

	// Size:
	$file_stats[1] = number_format_i18n( $stat['size'] ) ." ". __('bytes', 'ninjascanner');

	// Modified:
	$file_stats[2] = date( 'M d, Y, H:i:s O', $stat['mtime'] );

	// Permissions:
	$p = sprintf ("%04o", $stat['mode'] & 0777);
	$flags = array( '---', '--x', '-w-', '-wx', 'r--', 'r-x', 'rw-', 'rwx' );
	if ( is_dir( $file ) ) {
		$permissions = 'd';
	} else {
		$permissions = '-';
	}
	$permissions .= $flags[$p[1]] . $flags[$p[2]] . $flags[$p[3]];
	$file_stats[3] = "{$p} {$permissions}";

	// UID/GID:
	$file_stats[4] = "{$stat['uid']} / {$stat['gid']}";

	// Changed:
	$file_stats[5] = date( 'M d, Y, H:i:s O', $stat['ctime'] );

	// Symlink ?
	if ( is_link( $file ) ) {
		$file_stats[6] = readlink( $file );
	}

	return $file_stats;
}

// =====================================================================
// EOF
