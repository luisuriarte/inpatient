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

// If a scanning process is running, put the settings page in read-only mode:
$nscan_is_running = '';
$lock_status = json_decode( nscan_is_scan_running(), true );
if ( $lock_status['status'] == 'success' ) {
	$nscan_is_running = ' disabled="disabled"';
	$message = __('A scanning process is currently running in the background, settings cannot be changed. Wait until the process has finished, or cancel it from the "Summary" tab.', 'ninjascanner' );
	$warning = 1;
}

// Submitted form ?
if (! empty( $_POST ) && empty( $nscan_is_running ) ) {

	// Check nonce:
	if ( empty( $_POST['nscannonce'] ) || ! wp_verify_nonce($_POST['nscannonce'], 'nscan_settings') ) {
		wp_nonce_ays('nscan_settings');
	}

	// Run GC to clear the cache?
	if (! empty( $_POST['clear-cache'] ) ) {

		if ( isset( $_POST['clear-snapshot'] ) ) {
			$clear_snapshot = true;
		} else {
			$clear_snapshot = false;
		}

		require_once __DIR__ .'/gc.php';
		$ret = nscan_gc( true, $clear_snapshot );
		// Error?
		if ( $ret ) {
			$warning = 1;
			$message = $ret;
		} else {
			$message = __( "The cache was cleared.", "ninjascanner" );
		}

	// Restore default settings?
	} elseif (! empty( $_POST['restore-settings'] ) ) {
		nscan_restore_settings( $nscan_options );
		$message = __( "Default settings were successfully restored.", "ninjascanner" );

	// Diagnostics:
	} elseif (! empty($_POST['diagnostics'] ) ) {
		$res = nscan_run_diagnostics();
		if (! empty( $res['error'] ) ) {
			$warning = 1;
		}
		$message = $res['message'];

	//Save settings:
	} else {
		nscan_save_settings( $nscan_options );
		$message = __( "Your settings were successfully saved.", "ninjascanner" );
	}
	// Refresh options after changes:
	$nscan_options = get_option( 'nscan_options' );
}

if (! empty( $message ) ) {
	if ( isset( $warning ) ) {
		echo '<div class="notice-warning notice is-dismissible"><p>' . $message . '</p></div>';
	} else {
		echo '<div class="notice-success notice is-dismissible"><p>' . $message . '</p></div>';
	}
}

echo nscan_display_tabs( 2 );

// Disable <form> if a scanning process is running:
if ( empty( $nscan_is_running ) ) {
	echo '<form method="post">';
	wp_nonce_field('nscan_settings', 'nscannonce', 0);
}
if ( nscan_is_valid() > 0 ) {
	$premium = '';
} else {
	$premium = ' <sup><font color="#D60404">'.
		__('Premium only', 'ninjascanner'). '</font></sup>';
}
?>
	<h3><?php _e('Basic Settings', 'ninjascanner' ) ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php _e('Blog directory', 'ninjascanner') ?> (ABSPATH)</th>
			<td>
				<p><code><?php echo htmlentities( ABSPATH ) ?></code></p>
		<?php
		$scan_root_folders = array();
		if (! empty( $nscan_options['scan_root_folders'] ) ) {
			$scan_root_folders = json_decode( $nscan_options['scan_root_folders'], true );
		}
		$absfiles = scandir( ABSPATH );
		$list = '';
		$row = 0;
		foreach( $absfiles as $item ) {
			$checked = '';
			if ( $item != '.' &&  $item != '..' && is_dir( ABSPATH. $item ) ) {
				if (! in_array( $item, $scan_root_folders ) ) {
					$checked = ' checked';
				}
				++$row;
				if ( $row % 2 == 0 ) {
					$r_color = 'nff-white';
				} else {
					$r_color = 'nsf-grey';
				}
				$list .= '<tr class="'. $r_color .'"><td class="dir-list"><label><input type="checkbox" name="scan_root_folders['. htmlspecialchars( $item ) .']"'. $checked .' /> '. htmlspecialchars( $item ) .'/</label></td></tr>';
			}
		}
		?>
				<br />
				<?php _e('Folders to scan in the blog directory:', 'ninjascanner') ?>
				<div class="nsf-sub">
					<table class="form-table" style="margin-top:0">
						<?php echo $list ?>
					</table>
				</div>

			</td>
		</tr>

		<?php
		// Size:
		if (! isset( $nscan_options['scan_size'] ) || ! preg_match( '/^\d+$/', $nscan_options['scan_size'] ) ) {
			$scan_size = 1024;
		} else {
			$scan_size = $nscan_options['scan_size'];
		}
		?>
		<tr>
			<th scope="row"><?php _e('File size', 'ninjascanner') ?></th>
			<td>
				<?php printf( __('Scan only files smaller than %s KB', 'ninjascanner'), '<input name="nscan_options[scan_size]" step="1" min="0" value="'. $scan_size .'" class="small-text" type="number">'); ?>
				<br />
				<span class="description"><?php _e('Set this option to 0 to disable it.', 'ninjascanner') ?></span>
			</td>
		</tr>

		<?php
		// Ignore file extensions:
		$scan_extensions = '';
		if (! empty( $nscan_options['scan_extensions'] ) ) {
			$extensions = json_decode( $nscan_options['scan_extensions'] );
			if ( is_array( $extensions ) ) {
				foreach( $extensions as $extension ) {
					$scan_extensions .= trim( $extension ) . ',';
				}
				$scan_extensions = rtrim( $scan_extensions, ',' );
			}
		}
		?>
		<tr>
			<th scope="row"><?php _e('Ignore file extensions', 'ninjascanner') ?></th>
			<td>
				<input name="nscan_options[scan_extensions]" type="text" class="large-text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" value="<?php echo htmlentities( $scan_extensions ) ?>" placeholder="<?php _e('e.g.,', 'ninjascanner') ?> txt,jpg,css" />
				<br />
				<span class="description"><?php _e('Case-insensitive extensions. Multiple values must be comma-separated.', 'ninjascanner') ?></span>
			</td>
		</tr>

		<?php
		// Ignore files/folders:
		$scan_folders = '';
		if (! empty( $nscan_options['scan_folders'] ) ) {
			$folders = json_decode( $nscan_options['scan_folders'] );
			if ( is_array( $folders ) ) {
				foreach( $folders as $folder ) {
					$scan_folders .= trim( $folder ) . ',';
				}
				$scan_folders = rtrim( $scan_folders, ',' );
			}
		}
		if (! empty( $nscan_options['scan_folders_fic'] ) ) {
			$scan_folders_fic = 1;
		} else {
			$scan_folders_fic = 0;
		}
		?>
		<tr>
			<th scope="row"><?php _e('Ignore files/folders', 'ninjascanner') ?></th>
			<td>
				<input name="nscan_options[scan_folders]" type="text" class="large-text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" value="<?php echo htmlentities( $scan_folders ) ?>" placeholder="<?php _e('e.g.,', 'ninjascanner') ?> /foo/bar/,cache" />
				<br />
				<span class="description"><?php _e('Case-sensitive string. Multiple values must be comma-separated.', 'ninjascanner') ?></span>
				<p><label><input type="checkbox" name="nscan_options[scan_folders_fic]" value="1"<?php checked($scan_folders_fic, 1)?> />&nbsp;<?php _e('Apply the exclusion list to the file integrity checker (WordPress, themes and plugins)', 'ninjascanner') ?>.</label></p>
			</td>
		</tr>

		<?php
		// Email address (optional):
		if (! isset( $nscan_options['admin_email'] ) ) {
			$nscan_options['admin_email'] = get_option('admin_email');
		}
		// Conditional report:
		if (! isset( $nscan_options['admin_email_report'] ) || ! preg_match( '/^[12]$/', $nscan_options['admin_email_report'] ) ) {
			$admin_email_report = 0;
		} else {
			$admin_email_report = (int) $nscan_options['admin_email_report'];
		}
		?>
		<tr>
			<th scope="row"><?php _e('Send the scan report to', 'ninjascanner') ?></th>
			<td>
				<input class="large-text" type="text" name="nscan_options[admin_email]" value="<?php echo htmlspecialchars( $nscan_options['admin_email'] ); ?>" placeholder="<?php _e('e.g.,', 'ninjascanner') ?> <?php echo htmlspecialchars( get_option('admin_email') ) ?>" />
				<br />
				<span class="description"><?php _e('Multiple recipients must be comma-separated. Leave it blank if you do not want to send the report.', 'ninjascanner') ?></span>
				<p><label><input type="radio" name="nscan_options[admin_email_report]" value="0"<?php checked($admin_email_report, 0) ?> /><?php _e('Send the report even if no problems were detected.', 'ninjascanner') ?></label></p>
				<p><label><input type="radio" name="nscan_options[admin_email_report]" value="1"<?php checked($admin_email_report, 1) ?> /><?php _e('Send the report if a critical or important problem was detected.', 'ninjascanner') ?></label></p>
				<p><label><input type="radio" name="nscan_options[admin_email_report]" value="2"<?php checked($admin_email_report, 2) ?> /><?php _e('Send the report only if a critical problem was detected.', 'ninjascanner') ?></label></p>
			</td>
		</tr>

		<?php
		// scheduled scan:
		if ( empty( $nscan_options['scan_scheduled'] ) || ! preg_match( '/^[0-3]$/', $nscan_options['scan_scheduled'] ) ) {
			$scan_scheduled = 0;
		} else {
			$scan_scheduled = $nscan_options['scan_scheduled'];
		}
		?>
		<tr>
			<th scope="row"><?php _e('Run a scheduled scan', 'ninjascanner'); ?></th>
			<td>
				<select name="nscan_options[scan_scheduled]">
					<option value="0"<?php selected($scan_scheduled, 0) ?>><?php _e('Never', 'ninjascanner') ?></option>
					<option value="1"<?php selected($scan_scheduled, 1) ?>><?php _e('Hourly', 'ninjascanner') ?></option>
					<option value="2"<?php selected($scan_scheduled, 2) ?>><?php _e('Twicedaily', 'ninjascanner') ?></option>
					<option value="3"<?php selected($scan_scheduled, 3) ?>><?php _e('Daily', 'ninjascanner') ?></option>
				</select>
			<?php echo $premium; ?></td>
		</tr>

		<?php
		if (! isset($nscan_options['scan_enable_wpcli']) || ! preg_match( '/^[01]$/', $nscan_options['scan_enable_wpcli'] ) ) {
			$nscan_options['scan_enable_wpcli'] = 1;
		}
		?>
		<tr>
			<th scope="row"><?php _e('WP-CLI', 'ninjascanner') ?></th>
			<td>
				<label><input type="checkbox" name="nscan_options[scan_enable_wpcli]" value="1"<?php checked($nscan_options['scan_enable_wpcli'], 1); ?> /><?php printf( __('Enable <a href="%s">WP-CLI</a> integration', 'ninjascanner'), 'https://blog.nintechnet.com/ninjascanner-powerful-antivirus-scanner-for-wordpress/#wpcli' ); echo $premium; ?></label>
			</td>
		</tr>
	</table>

	<br />

	<div id="nscan-advanced-settings" style="display:none">
		<hr>
		<h3><?php _e('Advanced Users Settings', 'ninjascanner' ) ?></h3>
		<table class="form-table">
			<?php
			// File integrity
			if (! isset($nscan_options['scan_ninjaintegrity']) || ! preg_match( '/^[01]$/', $nscan_options['scan_ninjaintegrity'] ) ) {
				$nscan_options['scan_ninjaintegrity'] = 0;
			}
			if (! isset($nscan_options['scan_wpcoreintegrity']) || ! preg_match( '/^[01]$/', $nscan_options['scan_wpcoreintegrity'] ) ) {
				$nscan_options['scan_wpcoreintegrity'] = 1;
			}
			if (! isset($nscan_options['scan_themeseintegrity']) || ! preg_match( '/^[01]$/', $nscan_options['scan_themeseintegrity'] ) ) {
				$nscan_options['scan_themeseintegrity'] = 1;
			}
			if (! isset($nscan_options['scan_pluginsintegrity']) || ! preg_match( '/^[01]$/', $nscan_options['scan_pluginsintegrity'] ) ) {
				$nscan_options['scan_pluginsintegrity'] = 1;
			}
			if (! isset($nscan_options['scan_warnfilechanged']) || ! preg_match( '/^[01]$/', $nscan_options['scan_warnfilechanged'] ) ) {
				$nscan_options['scan_warnfilechanged'] = 1;
			}
			if (! isset($nscan_options['scan_warndbchanged']) || ! preg_match( '/^[01]$/', $nscan_options['scan_warndbchanged'] ) ) {
				// Don't enable it by defaut (i.e., after an undate):
				$nscan_options['scan_warndbchanged'] = 0;
			}
			?>
			<tr>
				<th scope="row"><?php _e('File integrity checker', 'ninjascanner') ?></th>
				<td>
					<p><label><input type="checkbox" name="nscan_options[scan_ninjaintegrity]" value="1"<?php checked($nscan_options['scan_ninjaintegrity'], 1) ?> /><?php _e("Always verify NinjaScanner's files integrity before starting a scan.", 'ninjascanner') ?></label></p>
					<p><label><input type="checkbox" name="nscan_options[scan_wpcoreintegrity]" value="1"<?php checked($nscan_options['scan_wpcoreintegrity'], 1) ?> /><?php _e("Compare WordPress core files to their original package.", 'ninjascanner') ?></label></p>
					<br />
					<p><label><input type="checkbox" name="nscan_options[scan_pluginsintegrity]" value="1"<?php checked($nscan_options['scan_pluginsintegrity'], 1) ?> /><?php _e("Compare plugin files to their original package.", 'ninjascanner') ?></label></p>
					<p><label><input type="checkbox" name="nscan_options[scan_themeseintegrity]" value="1"<?php checked($nscan_options['scan_themeseintegrity'], 1) ?> /><?php _e("Compare theme files to their original package.", 'ninjascanner') ?></label></p>
					<p><span class="description"><?php
						printf( __('By default, only themes and plugins available in the wordpress.org repo can be checked that way. If you want to include premium plugins or themes too, <a href="%s">consult our blog</a>.', "ninjascanner"), NSCAN_LINK_INTEGRITY_CHECK ) ?></span></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('File snapshot', 'ninjascanner') ?></th>
				<td>
					<p><label><input type="checkbox" name="nscan_options[scan_warnfilechanged]" value="1"<?php checked($nscan_options['scan_warnfilechanged'], 1) ?> /><?php _e('Report files that were changed, added or deleted since last scan.', 'ninjascanner') ?></label></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Database snapshot', 'ninjascanner') ?></th>
				<td>
					<p><label><input type="checkbox" name="nscan_options[scan_warndbchanged]" value="1"<?php checked($nscan_options['scan_warndbchanged'], 1) ?> /><?php _e('Report pages and posts that were changed, added or deleted in the database since last scan.', 'ninjascanner') ?></label></p>
				</td>
			</tr>

			<?php
			// Scanner signatures:
			$scan_signatures = '';
			$signatures = array();
			$lmd = '';
			if (! empty( $nscan_options['scan_signatures'] ) ) {
				$signatures = json_decode( $nscan_options['scan_signatures'] );
				foreach( $signatures as $signature ) {
					// LMD + NinjaScanner?
					if ( $signature == "lmd" ) {
						$lmd = ' checked="checked"';
						continue;
					}
					// User-defined signatures?
					if ( file_exists( $signature ) ) {
						$scan_signatures .= '<p><label><input type="checkbox" name="scan_signatures[]" value="'. sha1( $signature ) .'" checked="checked" />'. htmlspecialchars( basename( $signature ) ) .'</label></p>';
					}
				}
			}
			// Grab potential user-defined news signatures file from the cache folder:
			$glob = glob( NSCAN_LOCAL .'/*.sig' );
			if ( is_array( $glob ) ) {
				foreach( $glob as $signature ) {
					// Ignore already parsed signatures:
					if ( in_array( $signature, $signatures ) ) {
						continue;
					}
					$scan_signatures .= '<p><label><input type="checkbox" name="scan_signatures[]" value="'. sha1( $signature ) .'" />'. htmlspecialchars( basename( $signature ) ) .'</label></p>';
				}
			}
			?>
			<tr>
				<th scope="row"><?php _e('Anti-malware signatures', 'ninjascanner') ?></th>
				<td>
					<p><label><input type="checkbox" name="scan_signatures[]" value="lmd"<?php echo $lmd ?> />NinjaScanner</label></p>
					<?php echo $scan_signatures ?>
					<p><span class="description"><?php printf( __('<a href="%s">Consult our blog</a> if you want to add your own signatures.', 'ninjascanner'), NSCAN_LINK_ADD_SIGS ) ?></span></p>
				</td>
			</tr>

			<?php
			if ( ! empty( $nscan_options['scan_gsb'] ) ) {
				$scan_gsb = htmlentities( $nscan_options['scan_gsb'] );
			} else {
				$scan_gsb = '';
			}
			$nonce = wp_create_nonce( 'nscan_gsbapikey' );
			?>
			<tr>
				<th scope="row"><?php _e('Google Safe Browsing', 'ninjascanner') ?></th>
				<td>
					<p><?php printf( __('If you have <a href="%s">an API key</a>, enter it below to check your site with the Google Safe Browsing service', 'ninjascanner'), 'https://developers.google.com/safe-browsing/v4/get-started' ) ?></p>
					<p><input type="text" class="large-text" id="nsgsb" name="nscan_options[scan_gsb]" value="<?php echo $scan_gsb ?>" /></p>

					<p><input type="button" class="button button-small" id="nsgsb-button" value="<?php _e('Test API key', 'ninjascanner' )?>" onClick="nscanjs_gsb_check_key(document.getElementById('nsgsb').value, '<?php echo $nonce ?>');" /><img src="<?php echo plugins_url() ?>/ninjascanner/static/progress.gif" id="nsgsb-gif" style="display:none" /></p>
				</td>
			</tr>

			<?php
			// Symlinks:
			if (! isset($nscan_options['scan_nosymlink']) || ! preg_match( '/^[01]$/', $nscan_options['scan_nosymlink'] ) ) {
				$nscan_options['scan_nosymlink'] = 1;
			}
			if (! isset($nscan_options['scan_warnsymlink']) || ! preg_match( '/^[01]$/', $nscan_options['scan_warnsymlink'] ) ) {
				$nscan_options['scan_warnsymlink'] = 1;
			}
			// Binary executable files:
			if (! isset($nscan_options['scan_warnbinary']) || ! preg_match( '/^[01]$/', $nscan_options['scan_warnbinary'] ) ) {
				$nscan_options['scan_warnbinary'] = 0;
			}
			// Hidden PHP scripts:
			if (! isset($nscan_options['scan_warnhiddenphp']) || ! preg_match( '/^[01]$/', $nscan_options['scan_warnhiddenphp'] ) ) {
				$nscan_options['scan_warnhiddenphp'] = 1;
			}
			// Unreadable files/folders:
			if (! isset($nscan_options['scan_warnunreadable']) || ! preg_match( '/^[01]$/', $nscan_options['scan_warnunreadable'] ) ) {
				$nscan_options['scan_warnunreadable'] = 1;
			}
			?>
			<tr>
				<th scope="row"><?php _e('Files and folders', 'ninjascanner') ?></th>
				<td>
					<p><label><input type="checkbox" name="nscan_options[scan_nosymlink]" value="1"<?php checked($nscan_options['scan_nosymlink'], 1) ?> /><?php _e('Do not follow symbolic links.', 'ninjascanner') ?></label></p>
					<p><label><input type="checkbox" name="nscan_options[scan_warnsymlink]" value="1"<?php checked($nscan_options['scan_warnsymlink'], 1) ?> /><?php _e('Warn if symbolic links.', 'ninjascanner') ?></label></p>
					<p><label><input onClick="return nscanjs_slow_scan_enable('bin-scan')" id="bin-scan" type="checkbox" name="nscan_options[scan_warnbinary]" value="1"<?php checked($nscan_options['scan_warnbinary'], 1) ?> /><?php _e('Warn if executable files (MZ/PE/NE and ELF formats).', 'ninjascanner') ?></label></p>
					<p><label><input type="checkbox" name="nscan_options[scan_warnhiddenphp]" value="1"<?php checked($nscan_options['scan_warnhiddenphp'], 1) ?> /><?php _e('Warn if hidden PHP scripts.', 'ninjascanner') ?></label></p>
					<p><label><input type="checkbox" name="nscan_options[scan_warnunreadable]" value="1"<?php checked($nscan_options['scan_warnunreadable'], 1) ?> /><?php _e('Warn if unreadable files of folders.', 'ninjascanner') ?></label></p>
				</td>
			</tr>

			<?php
			// Scanning process fork method:
			if ( ! empty( $nscan_options['scan_fork_method'] ) && preg_match( '/^[12]$/', $nscan_options['scan_fork_method'] ) ) {
				$scan_fork_method = $nscan_options['scan_fork_method'];
			} else {
				$scan_fork_method = 1;
			}
			?>
			<tr>
				<th scope="row"><?php _e('Scanning process', 'ninjascanner') ?></th>
				<td>
					<p><label><input type="radio" name="nscan_options[scan_fork_method]" value="2"<?php checked( $scan_fork_method, 2 ); ?> /><?php _e('Fork process using WordPress built-in Ajax Process Execution.', 'ninjascanner'); ?></label></p>
					<p><label><input type="radio" name="nscan_options[scan_fork_method]" value="1"<?php checked( $scan_fork_method, 1 ); ?> /><?php _e('Fork process using WordPress built-in WP-CRON.', 'ninjascanner') ?></label></p>
					<p><span class="description"><?php _e('If the scanner does not start and throws an error, select a different fork method.', 'ninjascanner') ?></span></p>
				</td>
			</tr>

			<?php
			// Integration:
			if (! isset($nscan_options['scan_toolbarintegration']) || ! preg_match( '/^[01]$/', $nscan_options['scan_toolbarintegration'] ) ) {
				$nscan_options['scan_toolbarintegration'] = 1;
			}
			if (! isset($nscan_options['scan_nfwpintegration']) || ! preg_match( '/^[01]$/', $nscan_options['scan_nfwpintegration'] ) ) {
				$nscan_options['scan_nfwpintegration'] = 0;
			}
			if ( ( is_plugin_active('ninjafirewall/ninjafirewall.php') || is_plugin_active('nfwplus/nfwplus.php') ) && ( version_compare( NFW_ENGINE_VERSION, '3.5.4', '>' ) ) ) {
				$disabled = 0;
			} else {
				$disabled = 1;
			}
			?>
			<tr>
				<th scope="row"><?php _e('Integration', 'ninjascanner') ?></th>
				<td>
					<p><label><input type="checkbox" name="nscan_options[scan_toolbarintegration]" value="1"<?php checked($nscan_options['scan_toolbarintegration'], 1); ?> /><?php _e("Display the status of the running scan in the Toolbar.", 'ninjascanner') ?></label></p>
					<p><label><input type="checkbox" name="nscan_options[scan_nfwpintegration]" value="1"<?php checked($nscan_options['scan_nfwpintegration'], 1); ?> /><?php _e("Integrate NinjaScanner with NinjaFirewall.", 'ninjascanner');
					if ( $disabled ) {
						echo '*</label></p><p><span class="description">*' . sprintf( __( 'This feature requires the NinjaFirewall (version 3.6 at least) web application firewall plugin: <a href="%s">download it from wordpress.org</a>.', 'ninjascanner'), "https://wordpress.org/plugins/ninjafirewall/" ) . '</span></p>';
					} else {
						echo '</label></p>';
					}
					?>
				</td>
			</tr>

			<?php
			// User interface (report):
			if ( empty( $nscan_options['mutipagereport'] ) ) {
				$nscan_options['mutipagereport'] = 0;
			} else {
				$nscan_options['mutipagereport'] = 1;
			}
			if ( empty( $nscan_options['row_action'] ) ) {
				$nscan_options['row_action'] = 0;
			} else {
				$nscan_options['row_action'] = 1;
			}
			if (! isset($nscan_options['table_rows']) || ! preg_match( '/^\d+$/', $nscan_options['table_rows'] ) ) {
				$nscan_options['table_rows'] = 6;
			}
			if ( empty( $nscan_options['show_path'] ) ) {
				$nscan_options['show_path'] = 0;
			} else {
				$nscan_options['show_path'] = 1;
			}
			if ( empty( $nscan_options['highlight'] ) ) {
				$nscan_options['highlight'] = 0;
			} else {
				$nscan_options['highlight'] = 1;
			}
			?>
			<tr>
				<th scope="row"><?php _e('Scan report', 'ninjascanner') ?></th>
				<td>
					<?php _e('Display report:', 'ninjascanner') ?>
					<p><label><input type="radio" name="nscan_options[mutipagereport]" value="0"<?php checked( $nscan_options['mutipagereport'], 0 ); ?> /><?php _e('On one page.', 'ninjascanner') ?></label></p>
					<p><label><input type="radio" name="nscan_options[mutipagereport]" value="1"<?php checked( $nscan_options['mutipagereport'], 1 ); ?> /><?php _e('On multiple pages.', 'ninjascanner') ?></label></p>
					<br />
					<?php _e('Row action links:', 'ninjascanner') ?>
					<p><label><input type="radio" name="nscan_options[row_action]" value="0"<?php checked( $nscan_options['row_action'], 0 ); ?> /><?php _e('Show when hover on row.', 'ninjascanner') ?></label></p>
					<p><label><input type="radio" name="nscan_options[row_action]" value="1"<?php checked( $nscan_options['row_action'], 1 ); ?> /><?php _e('Always visible.', 'ninjascanner') ?></label></p>
					<br />
					<?php _e('Number of visible rows in table:', 'ninjascanner') ?>
					<p><?php printf( __('%s rows', 'ninjascanner'), '<input class="small-text" type="number" step="1" size="2" maxlength="2" min="1" name="nscan_options[table_rows]" value="'. $nscan_options['table_rows'] .'" />' ); ?></p>
					<br />
					<?php _e('File names:', 'ninjascanner') ?>
					<p><label><input type="radio" name="nscan_options[show_path]" value="0"<?php checked( $nscan_options['show_path'], 0 ); ?> /><?php _e('Show absolute path.', 'ninjascanner') ?></label></p>
					<p><label><input type="radio" name="nscan_options[show_path]" value="1"<?php checked( $nscan_options['show_path'], 1 ); ?> /><?php _e('Show relative path.', 'ninjascanner') ?></label></p>
					<br />
					<p><label><input type="checkbox" name="nscan_options[highlight]" value="1"<?php checked( $nscan_options['highlight'], 1 ); ?> /><?php _e('Highlight syntax when viewing a file.', 'ninjascanner') ?></label></p>
				</td>
			</tr>

			<?php
			// HTTP basic authentication
			if (! isset( $nscan_options['username'] ) || ! isset( $nscan_options['password'] ) ) {
				$nscan_options['username'] = '';
				$nscan_options['password'] = '';
			}
			?>
			<tr>
				<th scope="row"><?php _e('HTTP basic authentication (optional)', 'ninjascanner') ?></th>
				<td>
					<p><?php _e('Username:', 'ninjascanner' ) ?><br /><input type="text" value="<?php echo htmlspecialchars( $nscan_options['username'] ) ?>" class="regular-text" name="nscan_options[username]" /></p>
					<p><?php _e('Password:', 'ninjascanner' ) ?><br /><input type="password" value="<?php echo htmlspecialchars( $nscan_options['password'] ) ?>" class="regular-text" name="nscan_options[password]" /></p>
				</td>
			</tr>

		</table>
	</div>

	<br />

	<div id="nscan-nerds-settings" style="display:none">

		<hr>
		<h3><?php _e('Nerds Settings', 'ninjascanner' ) ?></h3>
		<table class="form-table">

			<?php
			// Checkums:
			if (! isset($nscan_options['scan_checksum']) || ! preg_match( '/^[123]$/', $nscan_options['scan_checksum'] ) ) {
				$nscan_options['scan_checksum'] = 1;
			}
			?>
			<tr>
				<th scope="row"><?php _e('File integrity checksum', 'ninjascanner') ?></th>
				<td>
					<p><label><input type="radio" name="nscan_options[scan_checksum]" value="1"<?php checked($nscan_options['scan_checksum'], 1) ?> /><?php printf( __('Use %s.', 'ninjascanner'), 'MD5' ) ?></label></p>
					<p><label><input type="radio" name="nscan_options[scan_checksum]" value="2"<?php checked($nscan_options['scan_checksum'], 2) ?> /><?php printf( __('Use %s.', 'ninjascanner'), 'SHA-1' ) ?></label></p>
					<p><label><input type="radio" name="nscan_options[scan_checksum]" value="3"<?php checked($nscan_options['scan_checksum'], 3) ?> /><?php printf( __('Use %s.', 'ninjascanner'), 'SHA-256' ) ?></label></p>
				</td>
			</tr>

			<?php
			// Debugging log:
			if (! isset( $nscan_options['scan_debug_log'] ) || ! preg_match( '/^[01]$/', $nscan_options['scan_debug_log'] ) ) {
				$scan_debug_log = 1;
			} else {
				$scan_debug_log = $nscan_options['scan_debug_log'];
			}
			?>
			<tr>
				<th scope="row"><?php _e('Debugging', 'ninjascanner') ?></th>
				<td>
					<p><label><input type="checkbox" name="nscan_options[scan_debug_log]" value="1"<?php checked($scan_debug_log, 1) ?> /><?php _e('Show the "Log" tab.', 'ninjascanner') ?></label></p>
				</td>
			</tr>

			<?php
			// Quarantine sanbox:
			if (! isset( $nscan_options['sandbox'] ) || ! preg_match( '/^[01]$/', $nscan_options['sandbox'] ) ) {
				$sandbox = 1;
			} else {
				$sandbox = $nscan_options['sandbox'];
			}
			?>
			<tr>
				<th scope="row"><?php _e('Sandbox', 'ninjascanner') ?></th>
				<td>
					<p><label><input type="checkbox" name="nscan_options[sandbox]" value="1"<?php checked($sandbox, 1) ?> /><?php _e('Enable the quarantine sandbox.', 'ninjascanner') ?></label></p>
				</td>
			</tr>

			<?php
			// Garbage collector:
			if ( empty( $nscan_options['scan_garbage_collector'] ) || ! preg_match( '/^[1-4]$/', $nscan_options['scan_garbage_collector'] ) ) {
				$scan_garbage_collector = 1;
			} else {
				$scan_garbage_collector = $nscan_options['scan_garbage_collector'];
			}
			?>
			<tr>
				<th scope="row"><?php _e('Run the garbage collector', 'ninjascanner') ?></th>
				<td>
					<select name="nscan_options[scan_garbage_collector]">
						<option value="1"<?php selected($scan_garbage_collector, 1)  ?>><?php _e('Hourly', 'ninjascanner')  ?></option>
						<option value="2"<?php selected($scan_garbage_collector, 2)  ?>><?php _e('Twicedaily', 'ninjascanner')  ?></option>
						<option value="3"<?php selected($scan_garbage_collector, 3)  ?>><?php _e('Daily', 'ninjascanner')  ?></option>
						<option value="4"<?php selected($scan_garbage_collector, 4)  ?>><?php _e('Never', 'ninjascanner')  ?></option>
					</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" class="button-secondary" value="<?php _e('Run it now!', 'ninjascanner') ?>" name="clear-cache" onClick="return nscanjs_clear_cache();" />
					<p><label><input type="checkbox" name="clear-snapshot" /><?php _e('Clear snapshot and scan report.', 'ninjascanner') ?></label></p>
				</td>
			</tr>

			<?php
			// Uninstall option:
			if (! isset( $nscan_options['dont_delete_cache'] ) || ! preg_match( '/^[01]$/', $nscan_options['dont_delete_cache'] ) ) {
				$dont_delete_cache = 0;
			} else {
				$dont_delete_cache = $nscan_options['dont_delete_cache'];
			}
			?>
			<tr>
				<th scope="row"><?php _e('Uninstall options', 'ninjascanner') ?></th>
				<td>
					<p><label><input type="checkbox" name="nscan_options[dont_delete_cache]" value="1"<?php checked( $dont_delete_cache, 1 ) ?> /><?php _e('Do not delete options and the cache folder when uninstalling NinjaScanner.', 'ninjascanner') ?></label></p>
				</td>
			</tr>
		</table>
	</div>

	<br />

	<input type="submit" name="save-settings" class="button-primary" value="<?php _e('Save Settings', 'ninjascanner') ?>"<?php echo $nscan_is_running ?> />
	&nbsp;&nbsp;&nbsp;&nbsp;
	<input type="submit" name="restore-settings" class="button-secondary" onclick="return nscanjs_restore_settings();" value="<?php _e('Restore Default Settings', 'ninjascanner') ?>"<?php echo $nscan_is_running ?> />
	&nbsp;&nbsp;&nbsp;&nbsp;
	<a id="nscan-show-advanced-settings"><input type="button" class="button-secondary" onclick="nscanjs_toggle_settings(1);" value="<?php _e('Advanced Users Settings', 'ninjascanner') ?>  »" />
	&nbsp;&nbsp;&nbsp;&nbsp;
	</a><input id="nscan-show-nerds-settings" style="display:none" type="button" class="button-secondary" onclick="nscanjs_toggle_settings(2);" value="<?php _e('Nerds Settings', 'ninjascanner') ?>  »" />

<?php
if ( empty( $nscan_is_running ) ) {
	echo '</form>';
}

// =====================================================================
// Restore NinjaScanner's default settings.

function nscan_restore_settings( $nscan_options ) {

	$key = null; $exp = null;
	if (! empty( $nscan_options['key'] ) ) {
		$key = $nscan_options['key'];
	}
	if (! empty( $nscan_options['exp'] ) ) {
		$exp = $nscan_options['exp'];
	}

	require( __DIR__ . '/install.php' );
	$nscan_options = array();
	$nscan_options = nscan_default_settings( $key, $exp );
	update_option( 'nscan_options', $nscan_options );

	// Garbage collector cron:
	nscan_default_gc( $nscan_options['scan_garbage_collector'] );
	// Scheduled scan cron:
	nscan_default_sc( $nscan_options['scan_scheduled'] );

}

// =====================================================================
// Save user's settings.

function nscan_save_settings( $nscan_options ) {

	// Directories to scan in the ABSPATH
	$nscan_options['scan_root_folders'] = '';
	$absfiles = scandir( ABSPATH );
	$dir = array();
	foreach( $absfiles as $item ) {
		if ( $item != '.' &&  $item != '..' && is_dir( ABSPATH. $item ) ) {
			if (! isset( $_POST['scan_root_folders'][$item] ) )  {
				$dir[] = $item;
			}
		}
	}
	if (! empty( $dir ) ) {
		$nscan_options['scan_root_folders'] = json_encode( $dir );
	}

	// Max file size (value:numeric characters only):
	if (! isset( $_POST['nscan_options']['scan_size'] ) || ! preg_match( '/^\d+$/', $_POST['nscan_options']['scan_size'] ) ) {
		$nscan_options['scan_size'] = 1024;
	} else {
		$nscan_options['scan_size'] = (int)$_POST['nscan_options']['scan_size'];
	}

	// Check extensions to exclude:
	$nscan_options['scan_extensions'] = '';
	if (! empty( $_POST['nscan_options']['scan_extensions'] ) ) {
		// Split the string:
		$tmp_array = explode( ',', $_POST['nscan_options']['scan_extensions'] );
		$tmp_extension = array();
		foreach( $tmp_array as $extension ) {
			// Remove space characters:
			$extension = trim( $extension );
			// Remove leading dots
			$extension = ltrim( $extension, '.' );
			if (! empty( $extension ) ) {
				// Convert to lower cases and save it:
				$tmp_extension[] = strtolower( $extension );
			}
		}
		if (! empty( $tmp_extension ) ) {
			$nscan_options['scan_extensions'] = json_encode( $tmp_extension );
		}
	}

	// Check folders to exclude:
	$nscan_options['scan_folders'] = '';
	if (! empty( $_POST['nscan_options']['scan_folders'] ) ) {
		// Split the string:
		$tmp_array = explode( ',', $_POST['nscan_options']['scan_folders'] );
		$tmp_folders = array();
		foreach( $tmp_array as $folder ) {
			// Remove space characters:
			$folder = trim( $folder );
			if (! empty( $folder ) ) {
				// Save it (case-sensitive):
				$tmp_folders[] = $folder;
			}
		}
		if (! empty( $tmp_folders ) ) {
			$nscan_options['scan_folders'] = json_encode( $tmp_folders );
		}
	}
	if (! empty( $nscan_options['scan_folders'] ) && ! empty( $_POST['nscan_options']['scan_folders_fic'] ) ) {
		$nscan_options['scan_folders_fic'] = 1;
	} else {
		$nscan_options['scan_folders_fic'] = 0;
	}

	// Check email address(es) where to send the scan report:
	$nscan_options['admin_email'] = '';
	if (! empty( $_POST['nscan_options']['admin_email']) ) {
		$nscan_options['admin_email'] = '';
		// Split the string:
		$tmp_email = explode( ',', $_POST['nscan_options']['admin_email'] );
		foreach ( $tmp_email as $admin_email ) {
			// Sanitize email address:
			$nscan_options['admin_email'] .= sanitize_email( $admin_email ) . ',';
		}
		$nscan_options['admin_email'] = rtrim( $nscan_options['admin_email'], ',' );
		if ( empty( $nscan_options['admin_email'] ) ) {
			// There was en error, use the default one:
			$nscan_options['admin_email'] = get_option('admin_email');
		}
	}
	// Conditional report:
	if ( empty( $_POST['nscan_options']['admin_email_report'] ) || ! preg_match( '/^[12]$/', $_POST['nscan_options']['admin_email_report'] ) ) {
		$nscan_options['admin_email_report'] = 0;
	} else {
		$nscan_options['admin_email_report'] = (int) $_POST['nscan_options']['admin_email_report'];
	}


	// Scheduled scan (value: 0-3 only):
	if ( nscan_is_valid() < 1 ) {
		$_POST['nscan_options']['scan_scheduled'] = 0;
	}
	if ( empty( $_POST['nscan_options']['scan_scheduled'] ) || ! preg_match( '/^[0-3]$/', $_POST['nscan_options']['scan_scheduled'] ) ) {
		$nscan_options['scan_scheduled'] = 0;
	} else {
		// We save the value only if it is different than the previous one:
		if ( $nscan_options['scan_scheduled'] != $_POST['nscan_options']['scan_scheduled'] ) {
			$nscan_options['scan_scheduled'] = (int)$_POST['nscan_options']['scan_scheduled'];
		} else{
			$keep_sc = 1;
		}
	}
	// WP-CLI (value: 0-1 only):
	if ( empty( $_POST['nscan_options']['scan_enable_wpcli']) ) {
		$nscan_options['scan_enable_wpcli'] = 0;
	} else {
		$nscan_options['scan_enable_wpcli'] = 1;
	}

	// File integrity options (value: 0-1 only):
	if ( empty( $_POST['nscan_options']['scan_ninjaintegrity'] ) ) {
		$nscan_options['scan_ninjaintegrity'] = 0;
	} else {
		$nscan_options['scan_ninjaintegrity'] = 1;
	}
	if ( empty( $_POST['nscan_options']['scan_wpcoreintegrity'] ) ) {
		$nscan_options['scan_wpcoreintegrity'] = 0;
	} else {
		$nscan_options['scan_wpcoreintegrity'] = 1;
	}
	if ( empty( $_POST['nscan_options']['scan_themeseintegrity'] ) ) {
		$nscan_options['scan_themeseintegrity'] = 0;
	} else {
		$nscan_options['scan_themeseintegrity'] = 1;
	}
	if ( empty( $_POST['nscan_options']['scan_pluginsintegrity'] ) ) {
		$nscan_options['scan_pluginsintegrity'] = 0;
	} else {
		$nscan_options['scan_pluginsintegrity'] = 1;
	}
	if ( empty( $_POST['nscan_options']['scan_warnfilechanged'] ) ) {
		$nscan_options['scan_warnfilechanged'] = 0;
	} else {
		$nscan_options['scan_warnfilechanged'] = 1;
	}
	if ( empty( $_POST['nscan_options']['scan_warndbchanged'] ) ) {
		$nscan_options['scan_warndbchanged'] = 0;
	} else {
		$nscan_options['scan_warndbchanged'] = 1;
	}

	if ( empty( $_POST['nscan_options']['scan_gsb'] ) ) {
		$nscan_options['scan_gsb'] = '';
	} else {
		$nscan_options['scan_gsb'] = htmlspecialchars( $_POST['nscan_options']['scan_gsb'] );
	}

	// Symlink options (value: 0-1 only):
	if ( empty( $_POST['nscan_options']['scan_nosymlink'] ) ) {
		$nscan_options['scan_nosymlink'] = 0;
	} else {
		$nscan_options['scan_nosymlink'] = 1;
	}
	if ( empty( $_POST['nscan_options']['scan_warnsymlink'] ) ) {
		$nscan_options['scan_warnsymlink'] = 0;
	} else {
		$nscan_options['scan_warnsymlink'] = 1;
	}

	// Binary executables options (value: 0-1 only):
	if ( empty( $_POST['nscan_options']['scan_warnbinary'] ) ) {
		$nscan_options['scan_warnbinary'] = 0;
	} else {
		$nscan_options['scan_warnbinary'] = 1;
	}

	// Hidden PHP scripts options (value: 0-1 only):
	if ( empty( $_POST['nscan_options']['scan_warnhiddenphp'] ) ) {
		$nscan_options['scan_warnhiddenphp'] = 0;
	} else {
		$nscan_options['scan_warnhiddenphp'] = 1;
	}

	// Unreadable /files/folders/ options (value: 0-1 only):
	if ( empty( $_POST['nscan_options']['scan_warnunreadable'] ) ) {
		$nscan_options['scan_warnunreadable'] = 0;
	} else {
		$nscan_options['scan_warnunreadable'] = 1;
	}

	// Integration to NinjaFirewall own menu (value: 0-1 only):
	if ( empty( $_POST['nscan_options']['scan_nfwpintegration']) ) {
		$nscan_options['scan_nfwpintegration'] = 0;
	} else {
		$nscan_options['scan_nfwpintegration'] = 1;
	}
	// Show the status of the scan in the Toolbar (value: 0-1 only):
	if ( empty( $_POST['nscan_options']['scan_toolbarintegration']) ) {
		$nscan_options['scan_toolbarintegration'] = 0;
	} else {
		$nscan_options['scan_toolbarintegration'] = 1;
	}

	// Fork method:
	if ( empty( $_POST['nscan_options']['scan_fork_method'] ) || ! preg_match( '/^[12]$/', $_POST['nscan_options']['scan_fork_method'] ) ) {
		$nscan_options['scan_fork_method'] = 1;
	} else {
		$nscan_options['scan_fork_method'] = (int) $_POST['nscan_options']['scan_fork_method'];
	}

	// User interface
	if ( empty( $_POST['nscan_options']['mutipagereport'] ) ) {
		$nscan_options['mutipagereport'] = 0;
	} else {
		$nscan_options['mutipagereport'] = 1;
	}

	if ( empty( $_POST['nscan_options']['row_action'] ) ) {
		$nscan_options['row_action'] = 0;
	} else {
		$nscan_options['row_action'] = 1;
	}

	if ( empty( $_POST['nscan_options']['table_rows'] ) || ! preg_match( '/^\d+$/', $_POST['nscan_options']['table_rows'] ) ) {
		$nscan_options['table_rows'] = 6;
	} else {
		$nscan_options['table_rows'] = (int) $_POST['nscan_options']['table_rows'];
	}

	if ( empty( $_POST['nscan_options']['show_path'] ) ) {
		$nscan_options['show_path'] = 0;
	} else {
		$nscan_options['show_path'] = 1;
	}

	if ( empty( $_POST['nscan_options']['highlight'] ) ) {
		$nscan_options['highlight'] = 0;
	} else {
		$nscan_options['highlight'] = 1;
	}

	// HTTP authentication:
	if (! empty( $_POST['nscan_options']['username'] ) && ! empty( $_POST['nscan_options']['password'] ) ) {
		$nscan_options['username'] = $_POST['nscan_options']['username'];
		$nscan_options['password'] = $_POST['nscan_options']['password'];
	} else {
		$nscan_options['username'] = '';
		$nscan_options['password'] = '';
	}

	// Checkum options:
	// values: 1-3 only
	if (! isset( $_POST['nscan_options']['scan_checksum'] ) || ! preg_match( '/^[123]$/', $_POST['nscan_options']['scan_checksum'] ) ) {
		$nscan_options['scan_checksum'] = 1;
	} else {
		$nscan_options['scan_checksum'] = (int)$_POST['nscan_options']['scan_checksum'];
	}

	// Debugging log (value: 0-1 only):
	if ( empty( $_POST['nscan_options']['scan_debug_log'] ) ) {
		$nscan_options['scan_debug_log'] = 0;
	} else {
		$nscan_options['scan_debug_log'] = 1;
	}

	// Quarantine sandbox (value: 0-1 only):
	if ( empty( $_POST['nscan_options']['sandbox'] ) ) {
		$nscan_options['sandbox'] = 0;
	} else {
		$nscan_options['sandbox'] = 1;
	}

	// Garbage collector options (value: 1-4 only):
	if ( empty( $_POST['nscan_options']['scan_garbage_collector'] ) || ! preg_match( '/^[1-4]$/', $_POST['nscan_options']['scan_garbage_collector'] ) ) {
		$nscan_options['scan_garbage_collector'] = 1;
	} else {
		// We save the value only if it is different than the previous one:
		if ( $nscan_options['scan_garbage_collector'] != $_POST['nscan_options']['scan_garbage_collector'] ) {
			$nscan_options['scan_garbage_collector'] = (int)$_POST['nscan_options']['scan_garbage_collector'];
		} else{
			$keep_gc = 1;
		}
	}

	// Cache folder deletion (value: 0-1 only):
	if ( empty( $_POST['nscan_options']['dont_delete_cache'] ) ) {
		$nscan_options['dont_delete_cache'] = 0;
	} else {
		$nscan_options['dont_delete_cache'] = 1;
	}

	// Scanner built-in and user-defined signatures:
	$scan_signatures = array();
	// Grab potential user-defined signatures files from the cache folder:
	$glob = array();
	$glob = glob( NSCAN_LOCAL .'/*.sig' );
	if (! empty( $_POST['scan_signatures'] ) ) {
		foreach( $glob as $signature ) {
			if ( in_array( sha1( $signature ), $_POST['scan_signatures'] ) ) {
				$scan_signatures[] = $signature;
			}
		}
		// NinjaScanner's own signatures file:
		if ( in_array( 'lmd', $_POST['scan_signatures'] ) ) {
			$scan_signatures[] = 'lmd';
		}
	}
	$nscan_options['scan_signatures'] = json_encode( $scan_signatures );

	// Update options:
	update_option( 'nscan_options', $nscan_options );

	// WP_Cron:
	require( __DIR__ . '/install.php' );
	// Garbage collector:
	if ( empty( $keep_gc ) ) {
		nscan_default_gc( $nscan_options['scan_garbage_collector'] );
	}
	if ( empty( $keep_sc ) ) {
		// Scheduled scan:
		nscan_default_sc( $nscan_options['scan_scheduled'] );
	}

}
// =====================================================================
// EOF
