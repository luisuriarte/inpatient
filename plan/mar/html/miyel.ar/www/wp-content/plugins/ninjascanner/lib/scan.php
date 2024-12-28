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
// Get scan's status
$lock_status = nscan_get_lock_status();
if ( empty( $lock_status['current_step'] ) ) {
	nscan_log_error( sprintf(
		__('Missing %s value. Exiting scanning process', 'ninjascanner'),
		"'current_step'"
	) );
	touch( NSCAN_CANCEL );
}
if ( $lock_status['status'] != 'success' ) {
	nscan_log_error( sprintf(
		__('Missing %s value. Exiting scanning process', 'ninjascanner'),
		"'status'"
	) );
	exit;
}

global $nscan_steps;
global $snapshot; $snapshot = array();
global $current_snapshot; $current_snapshot = array();

// Ignored files list
global $ignored_files;
$ignored_files = array();
if ( file_exists( NSCAN_IGNORED_LOG ) ) {
	$ignored_files = unserialize( file_get_contents( NSCAN_IGNORED_LOG ) );
}

if ( file_exists( NSCAN_TMP_SNAPSHOT ) ) {
	$snapshot = unserialize( file_get_contents( NSCAN_TMP_SNAPSHOT ) );
	if ( empty( $snapshot['sys']['starttime'] ) ) {
		$msg = __('Fatal error: Snapshot seems to be corrupted.', 'ninjascanner');
		nscan_log_error( $msg );
		touch( NSCAN_CANCEL );
		nscan_set_lock_status(
			$lock_status['current_step'],
			'cancelled',
			$msg
		);
		exit;
	}
}

@set_time_limit( 0 );
@ini_set('memory_limit', -1 );

register_shutdown_function('nscan_erroronexit');

if ( version_compare( PHP_VERSION, '7.3', '<' ) ) {
	$snapshot['sys']['metrics'] = 'microtime';
} else {
	$snapshot['sys']['metrics'] = 'hrtime';
}
$snapshot['sys']['starttime'] = $snapshot['sys']['metrics']( true );

nscan_log_info( sprintf(
	__('Processing step %s/%s', 'ninjascanner'),
	$lock_status['current_step'], count( $nscan_steps )
) );
$snapshot['sys']['startmem'] = memory_get_peak_usage( false );
$nscan_steps[ $lock_status['current_step'] ]( $lock_status );
nscan_memory_used( $snapshot['sys']['starttime'], $snapshot['sys']['startmem'], $snapshot['sys']['metrics'] );

$lock_status = nscan_get_lock_status();
if (! empty( $nscan_steps[ $lock_status['current_step'] ] ) ) {
	// Fork another process
	$_POST['nscan_key'] = nscan_generate_key();
	$return = array();
	$return = json_decode( nscan_ajax_startscan(), true );
	if ( $return['status'] != 'success' ) {
		$msg = sprintf(
			__('Fatal error: process returned %s (%s).', 'ninjascanner'),
			$return['status'],
			$return['message']
		);
		nscan_log_error( $msg );
		touch( NSCAN_CANCEL );
		nscan_set_lock_status(
			$lock_status['current_step'],
			'cancelled',
			$msg
		);
		exit;
	}

} else {
	// Save and quit
	if ( $lock_status['current_step'] > count( $nscan_steps ) ) {
		if ( file_exists( NSCAN_TMP_SNAPSHOT ) ) {
			// Save old one
			if ( file_exists( NSCAN_SNAPSHOT ) ) {
				rename( NSCAN_SNAPSHOT, NSCAN_OLD_SNAPSHOT );
			}
			// Save new one
			rename( NSCAN_TMP_SNAPSHOT, NSCAN_SNAPSHOT );
		}
	}
	nscan_set_lock_status(
		--$lock_status['current_step'], // Decrement it
		'stopped',
		''
	);
	// Send report
	nscan_send_email();
	nscan_log_info( __('Exiting scanning process', 'ninjascanner') );
	delete_transient( 'nscan_temp_sigs' );
}

// =====================================================================
// Send a email report.

function nscan_send_email() {

	global $snapshot;
	// Shall we send the report by email?
	$nscan_options = get_option( 'nscan_options' );
	if (! empty( $nscan_options['admin_email'] ) ) {
		require __DIR__ .'/report_email.php';
		nscan_send_email_report( $snapshot, $nscan_options );
	}
}

// =====================================================================
// Catch potential errors.

function nscan_erroronexit() {

	global $lock_status;

	$e = error_get_last();
	if ( isset( $e['type'] ) && $e['type'] === E_ERROR ) {
		$err = str_replace( "\n", ' - ', $e['message'] );
		nscan_log_error( sprintf(
			__('Error: E_ERROR (%s - line %s in %s)', 'ninjascanner'),
			$err,
			$e['line'],
			$e['file']
		));

		nscan_set_lock_status(
			$lock_status['current_step'],
			'error',
			$e['message']
		);
		$nscan_options = get_option( 'nscan_options' );
		if (! empty( $nscan_options['admin_email'] ) ) {
			$recipient = $nscan_options['admin_email'];
		} else {
			$recipient = get_option('admin_email');
		}
		$subject = '[NinjaScanner] ' . __('Scan error', 'ninjascanner');
		$message = __('An fatal error occurred during the scan:', 'ninjascanner') ."\n";
		$message .= $e['message'] ."\n\n";
		$message.= __('Blog:', 'ninjascanner') .' '. home_url('/') . "\n";
		$message.= __('Date:', 'ninjafirewall') .' '. date('F j, Y @ H:i:s') . ' (UTC '. date('O') . ")\n\n";
		wp_mail( $recipient, $subject, $message );

		global $snapshot;
		nscan_memory_used( $snapshot['sys']['starttime'], $snapshot['sys']['startmem'], $snapshot['sys']['metrics'] );
	}
}

// =====================================================================
// Check NinjaScanner's files integrity by downloading its checksum
// hashes - or using the local cached copy. In case of mismatch,
// refuse to run (users can still bypass this by disabling the integrity
// checker from the settings page).

function nscan_check_scanner_integrity( $lock_status ) {

	$nscan_options = get_option( 'nscan_options' );

	// Are we supposed to do that
	if (! $nscan_options['scan_ninjaintegrity'] ) {
		$message = __('Skipping NinjaScanner files integrity check', 'ninjascanner' );
		nscan_log_info( $message );
		// Next step
		nscan_set_lock_status(
			++$lock_status['current_step'],
			'success',
			$message
		);
		return;
	}

	$message = __('Checking NinjaScanner files integrity', 'ninjascanner');
	nscan_log_info( $message );
	nscan_set_lock_status(
		$lock_status['current_step'],
		'success',
		$message
	);

	if ( ( $res = nscan_check_ninjascanner_integrity() ) === true ) {
		nscan_log_info( __('Files integrity is O.K', 'ninjascanner') );
	} else {
		if ( $res === false ) {
			// Failed! We warn and quit
			touch( NSCAN_CANCEL );
			nscan_set_lock_status(
				$lock_status['current_step'],
				'cancelled',
				__('Fatal error: NinjaScanner files integrity check: Decoded hashes seem corrupted. Aborting.', 'ninjascanner')
			);
			exit;
		} else {
			// The server may be down. Clear the 'scan_ninjaintegrity' flag,
			// we'll attempt to check the plugin again while checking all
			// plugin files integrity
			$nscan_options['scan_ninjaintegrity'] = 0;
			nscan_log_debug( __("Clearing 'scan_ninjaintegrity', we'll check the plugin again later", 'ninjascanner') );
		}
	}

	nscan_set_lock_status(
		++$lock_status['current_step'],
		'success',
		$message
	);
}

// =====================================================================
// Retrieve the list of files.

function nscan_build_files_list( $lock_status ) {

	global $snapshot, $ignored_files;

	// Clear temp data
	unset( $snapshot['tmp'] );
	delete_transient( 'nscan_temp_sigs' );

	// Save WP version
	global $wp_version;
	$snapshot['version'] = $wp_version;
	// Save locale
	global $wp_local_package;
	if (! empty( $wp_local_package ) ) {
		$snapshot['locale'] = $wp_local_package;
	}
	// Save NinjaScanner's version
	$snapshot['nscan_version'] = NSCAN_VERSION;

	$nscan_options = get_option( 'nscan_options' );

	$message = __("Building file's list", 'ninjascanner' );
	nscan_log_info( $message );
	nscan_set_lock_status(
		$lock_status['current_step'],
		'success',
		$message
	);

	// Get rid of any trailing slash, unless it's a chrooted WP:
	$abspath = realpath( ABSPATH );
	// Exclude root folders
	$excluded_root_folders = array();
	if (! empty( $nscan_options['scan_root_folders'] ) ) {
		$erf = json_decode( $nscan_options['scan_root_folders'], true );
		foreach( $erf as $folder ) {
			$tmp = realpath( "$abspath/$folder" );
			if ( $tmp !== false ) {
				$excluded_root_folders[] = $tmp;
			}
		}
	}
	$arg = array(
		'abspath'					=> $abspath,
		'excluded_root_folders' => $excluded_root_folders,
		'scan_nosymlink'			=> $nscan_options['scan_nosymlink'],
		'scan_warnsymlink'		=> $nscan_options['scan_warnsymlink'],
		'scan_warnhiddenphp'		=> $nscan_options['scan_warnhiddenphp'],
		'scan_warnunreadable'	=> $nscan_options['scan_warnunreadable'],
		'scan_warnbinary'			=> $nscan_options['scan_warnbinary'],
		'plugin_dir'				=> realpath( WP_PLUGIN_DIR ),
		'theme_dir'					=> realpath( WP_CONTENT_DIR .'/themes' )
	);
	_nscan_build_files_list( $arg['abspath'], $arg );

	// Save ignored list as it may have changed
	file_put_contents( NSCAN_IGNORED_LOG, serialize( $ignored_files ) );

	// Make sure we have some files:
	if ( empty( $snapshot['abspath'] ) ) {
		$msg = __('Fatal error: No file found. Check your NinjaScanner configuration. Aborting.', 'ninjascanner');
		$snapshot['error'] = $msg;
		nscan_log_error( $msg );
		touch( NSCAN_CANCEL );
		nscan_set_lock_status(
			$lock_status['current_step'],
			'cancelled',
			$msg
		);
		exit;
	}

	nscan_log_info( sprintf(
		__('Total files found: %s', 'ninjascanner'),
		number_format_i18n( count( $snapshot['abspath'] ) )
	));

	if (! empty( $snapshot['core_symlink'] ) ) {
		nscan_log_warn( sprintf(
			__('Symlinks found: %s', 'ninjascanner'),
			number_format_i18n( count( $snapshot['core_symlink'] ) )
		));
	}
	if ( empty( $nscan_options['scan_warnsymlink'] ) ) {
		$snapshot['skip']['core_symlink'] = 1;
	}

	if (! empty( $snapshot['core_unreadable'] ) ) {
		nscan_log_warn( sprintf(
			__('Unreadable files found: %s', 'ninjascanner'),
			number_format_i18n( count( $snapshot['core_unreadable'] ) )
		));
	}
	if ( empty( $nscan_options['scan_warnunreadable'] ) ){
		$snapshot['skip']['core_unreadable'] = 1;
	}

	if (! empty( $snapshot['core_hidden'] ) ) {
		nscan_log_warn( sprintf(
			__('Hidden scripts found: %s', 'ninjascanner'),
			number_format_i18n( count( $snapshot['core_hidden'] ) )
		));
	}
	if ( empty( $nscan_options['scan_warnhiddenphp'] ) ) {
		$snapshot['skip']['core_hidden'] = 1;
	}

	// Create the database posts and pages checksum:
	global $wpdb;
	$snapshot['posts'] = array(); $snapshot['pages'] = array();
	nscan_log_info( __('Building database posts and pages checksum', 'ninjascanner') );
	// Posts:
	$tmp_array = $wpdb->get_results(
		"SELECT ID, post_title, sha1(concat(post_content, post_title, post_excerpt, post_name))
		as hash
		FROM {$wpdb->posts}
		WHERE `post_type` = 'post' and `post_status` = 'publish'"
	);
	foreach( $tmp_array as $item ) {
		$snapshot['posts'][$item->ID]['permalink'] = get_permalink( $item->ID );
		$snapshot['posts'][$item->ID]['hash'] = $item->hash;
	}
	unset($tmp_array);
	// Pages:
	$tmp_array = $wpdb->get_results(
		"SELECT ID, post_title, sha1(concat(post_content, post_title, post_excerpt, post_name))
		as hash
		FROM {$wpdb->posts}
		WHERE `post_type` = 'page' and `post_status` = 'publish'"
	);
	foreach( $tmp_array as $item ) {
		$snapshot['pages'][$item->ID]['permalink'] = get_permalink( $item->ID );
		$snapshot['pages'][$item->ID]['hash'] = $item->hash;
	}
	unset($tmp_array);
	nscan_log_info( sprintf(
		__('Found %s posts and %s pages in the database', 'ninjascanner'),
		count( $snapshot['posts'] ),
		count( $snapshot['pages'] )
	) );

	// Save snapshot
	file_put_contents( NSCAN_TMP_SNAPSHOT, serialize( $snapshot ) );

	nscan_set_lock_status(
		++$lock_status['current_step'],
		'success',
		$message
	);
}

// ---------------------------------------------------------------------

function _nscan_build_files_list( $scan_dir, $arg ) {

	global $snapshot, $ignored_files;

  if ( is_dir( $scan_dir ) && is_readable( $scan_dir ) ) {

		// User-excluded root folders
		if ( in_array( $scan_dir, $arg['excluded_root_folders'] ) ) {
			return;
		}

		if ( $dh = opendir( $scan_dir ) ) {
			while ( FALSE !== ( $file = readdir($dh) ) ) {

				if ( $file == '.' || $file == '..' ) { continue; }
				if ( strpos( $scan_dir, NSCAN_QUARANTINE ) !== false ) {
					continue;
				}
				$full_path = $scan_dir . '/' . $file;

				// Check if it is in the ignored files list:
				if (! empty( $ignored_files[$full_path] ) ) {
					if ( $ignored_files[$full_path] == filectime( $full_path ) ) {
						continue;

					} else {
						unset( $ignored_files[$full_path] );
					}
				}

				if ( is_readable( $full_path ) ) {
					// Directory:
					if ( is_dir( $full_path ) ) {
						if ( is_link( $full_path ) ) {
							if ( $arg['scan_warnsymlink'] ) {
								$snapshot['core_symlink'][$full_path] = 1;
							}
							// Follow symlinks?
							if ( $arg['scan_nosymlink'] ) { continue; }
						}
						_nscan_build_files_list( $full_path, $arg );

					// File:
					} elseif ( is_file( $full_path ) ) {
						if ( $arg['scan_warnsymlink'] && is_link( $full_path ) ) {
							$snapshot['core_symlink'][$full_path] = 1;
						}

						$snapshot['abspath'][$full_path][0] = filectime( $full_path );
						$snapshot['abspath'][$full_path][1] = filesize( $full_path );
						if ( strpos( $full_path, "{$arg['plugin_dir']}/" ) !== false ) {
							$str = substr( $full_path, strlen( $arg['plugin_dir'] ) + 1 );
							$list = explode( '/', $str, 2 );
							// Don't add plugins/hello.php and plugins/index.php, we'll check them with core files:
							if ( $list[0] != 'hello.php' &&  $list[0] != 'index.php' && isset( $list[1] ) ) {
								$snapshot['plugins'][$list[0]][$list[1]] = 0;
							}
							continue;
						}
						if ( strpos( $full_path, "{$arg['theme_dir']}/" ) !== false ) {
							$str = substr( $full_path, strlen( $arg['theme_dir'] ) + 1 );
							$list = explode( '/', $str, 2 );
							// Don't add themes/index.php, we'll check it with core files:
							if ( $list[0] != "index.php" && isset( $list[1] ) ) {
								$snapshot['themes'][$list[0]][$list[1]] = 0;
							}
							continue;
						}

						// Look for additional files among WP system files:
						if ( strpos( $scan_dir, ABSPATH .'wp-admin' ) !== false || strpos( $scan_dir, ABSPATH .'wp-includes' ) !== false ) {
							$snapshot['core_unknown'][$full_path] = 0;
						}

						// Look for additional files in the ABSPATH:
						if ( preg_match( '`^'. ABSPATH .'wp-[^/\\\]+\.ph(?:p(?:[34x7]|5\d?)?|t(?:ml)?|ar)$`', $full_path ) ) {
							if ( $full_path != ABSPATH .'wp-config.php' ) {
								$snapshot['core_unknown_root'][$full_path] = 0;
							}
						}

						// Look for hidden PHP scripts:
						if ( $arg['scan_warnhiddenphp'] && $file[0] == '.' && preg_match( '/\.ph(?:p([34x7]|5\d?)?|t(ml)?|ar)$/', $file ) ) {
							$snapshot['core_hidden'][$full_path] = 1;
						}
					}

				// Unreadable file/dir:
				} else {
					if ( $arg['scan_warnunreadable'] ) {
						$snapshot['core_unreadable'][$full_path] = 1;
					}
				}
			}
			closedir( $dh );
		}
   }
}

// =====================================================================
// Write to log the memory usage and execution time.

function nscan_memory_used( $starttime, $startmem, $metrics ) {

	$mem = memory_get_peak_usage( false );
	$ns = $mem - $startmem;

	if ( $metrics == 'hrtime' ) {
		$elapse = number_format( ( $metrics( true ) - $starttime ) / 1000000000, 4 );
	} else {
		$elapse = number_format( $metrics( true ) - $starttime, 4 );
	}

	nscan_log_debug( sprintf(
		__('Process executed in %s seconds and used %s MB of memory (NinjaScanner additional memory: %s MB).', 'ninjascanner' ),
		$elapse,
		number_format_i18n( $mem / 1024 / 1024, 2 ),
		number_format_i18n( $ns / 1024 / 1024, 2 )
	) );
}

// =====================================================================

function nscan_check_wordpress( $lock_status ) {

	global $snapshot;

	$nscan_options = get_option( 'nscan_options' );

	if ( $nscan_options['scan_wpcoreintegrity'] ) {

		$message = __("Checking WordPress core files integrity", 'ninjascanner' );
		nscan_log_info( $message );
		nscan_set_lock_status(
			$lock_status['current_step'],
			'success',
			$message
		);

		if ( _nscan_check_wordpress( $lock_status ) === true ) {
			nscan_log_info( __('Files integrity is O.K', 'ninjascanner') );
		}
		if (! empty( $snapshot['core_unknown'] ) ) {
			nscan_log_warn( sprintf(
				__('Additional/suspicious files: %s', 'ninjascanner'),
				number_format_i18n( count( $snapshot['core_unknown'] ) )
			));
		}

	} else {
		// Skip this step
		$message = __("Skipping WordPress core files integrity check", 'ninjascanner' );
		nscan_log_info( $message );
		$snapshot['skip']['scan_wpcoreintegrity'] = 1;
	}

	// Save snapshot
	file_put_contents( NSCAN_TMP_SNAPSHOT, serialize( $snapshot ) );

	nscan_set_lock_status(
		++$lock_status['current_step'],
		'success',
		$message
	);

}

// ---------------------------------------------------------------------
// Check WordPress core files integrity by downloading it from
// wordpress.org or using its local cached copy.
// The copy will be kept locally until the garbage collector cron job
// deletes it.

function _nscan_check_wordpress( $lock_status ) {

	$nscan_options = get_option( 'nscan_options' );
	global $snapshot, $wp_version, $wp_local_package, $nscan_steps;

	if ( empty( $wp_local_package ) ) {
		$wp_zip = "wordpress-{$wp_version}.zip";
		$wp_zip_url = "https://wordpress.org/{$wp_zip}";
	} else {
		$wp_zip = "wordpress-{$wp_version}-{$wp_local_package}.zip";
		$wp_zip_url = "https://de.wordpress.org/{$wp_zip}";
	}

	// Remove empty/corrupted file, if any
	if ( file_exists( NSCAN_CACHEDIR ."/$wp_zip" ) ) {
		if ( filesize( NSCAN_CACHEDIR ."/$wp_zip" ) < 10000000 ) { // WP is 15MB+
			unlink( NSCAN_CACHEDIR ."/$wp_zip" );
		}
	}

	// Download it if we don't have a copy in our cache:
	if (! file_exists( NSCAN_CACHEDIR ."/$wp_zip" ) ) {

		$message = sprintf( __('Downloading %s from wordpress.org', 'ninjascanner'), $wp_zip );
		nscan_log_debug( $message );
		nscan_set_lock_status(
			$lock_status['current_step'],
			'success',
			$message
		);

		$res = wp_remote_get(
			$wp_zip_url,
			array(
				'stream' => true,
				'filename' => NSCAN_CACHEDIR ."/$wp_zip",
				'timeout' => NSCAN_CURL_TIMEOUT,
				'httpversion' => '1.1' ,
				'user-agent' => 'Mozilla/5.0 (compatible; NinjaScanner/'.
										NSCAN_VERSION .'; WordPress/'. $wp_version . ')',
				'sslverify' => true
			)
		);
		if ( is_wp_error( $res ) ) {
			// Save error:
			$message = sprintf(
				__('%s. Skipping this step', 'ninjascanner'), $res->get_error_message()
			);
			$snapshot['step_error'][ $nscan_steps[ $lock_status['current_step'] ] ] = $message;
			nscan_log_error( $message );
			return false;
		}

		if ( $res['response']['code'] != 200 ) {
			if (file_exists( NSCAN_CACHEDIR ."/$wp_zip" )) {
				unlink( NSCAN_CACHEDIR ."/$wp_zip" );
			}

			nscan_log_warn( sprintf(
				__('HTTP Error %s. Skipping this step, you may try again later', 'ninjascanner'),
				(int)$res['response']['code']
			));
			return false;
		}

	// Use the local copy:
	} else {
		nscan_log_debug(
			sprintf( __('Using local cached copy (%s)', 'ninjascanner'), $wp_zip )
		);
	}

	$zip_files_list = array();
	if ( ( $zip_files_list = nscan_get_zip_files_list(  NSCAN_CACHEDIR ."/$wp_zip" ) ) === false ) {
		// Save error:
		$message = __('Unable to retrieve ZIP files list. Skipping this step', 'ninjascanner');
		$snapshot['step_error'][ $nscan_steps[ $lock_status['current_step'] ] ] = $message;
		nscan_log_error( $message );
		return false;
	}

	$message = __('Checking WordPress files integrity', 'ninjascanner');
	nscan_set_lock_status(
		$lock_status['current_step'],
		'success',
		$message
	);

	// Extract the ZIP
	if ( nscan_extract_archive( NSCAN_CACHEDIR ."/$wp_zip", NSCAN_CACHEDIR ."/$wp_version" ) === false ) {
		// Save error:
		$err = __('Unable to extract ZIP archive. Skipping this step', 'ninjascanner');
		nscan_log_error( $err );
		$snapshot['step_error'][ $nscan_steps[ $lock_status['current_step'] ] ] = $err;
		return false;
	}

	// Select algo:
	if ( empty( $nscan_options['scan_checksum'] ) || $nscan_options['scan_checksum'] == 1 ) {
		$algo = 'md5';
	} elseif ( $nscan_options['scan_checksum'] == 2 ) {
		$algo = 'sha1';
	} else {
		$algo = 'sha256';
	}
	nscan_log_debug( sprintf( __('Using %s algo', 'ninjascanner'), $algo ) );

	// Compare local files with archive files:
	foreach( $zip_files_list as $file => $checksum ) {

		// Don't check bundled themes/plugins, because the blog may use newer versions,
		// but still check the index.php of the themes, plugins & wp-content folders
		// as well as the "Hello_Dolly" plugin:
		if ( $file == 'wp-content/index.php' || $file == 'wp-content/themes/index.php' ) {
			$tmpfile = str_replace( 'wp-content', WP_CONTENT_DIR, $file );

		} elseif ( $file == 'wp-content/plugins/index.php' || $file == 'wp-content/plugins/hello.php' ) {
			$tmpfile = str_replace( 'wp-content/plugins', WP_PLUGIN_DIR, $file );

		} elseif ( strpos( $file, 'wp-content/plugins/' ) !== false || strpos( $file, 'wp-content/themes/' ) !== false ) {
			continue;

		} else {
			$tmpfile = ABSPATH . $file;
		}

		// Make sure the file exists:
		if ( isset( $snapshot['abspath'][$tmpfile] ) ) {
			$local_file = hash_file( $algo, $tmpfile );
			$original_file = hash_file( $algo, NSCAN_CACHEDIR ."/$wp_version/wordpress/$file" );

			// Compare checksums:
			if ( $local_file !== $original_file ) {
				$snapshot['core_failed_checksum'][$tmpfile] = 1;
				nscan_log_warn(
					sprintf( __( 'Checksum mismatch: %s', 'ninjascanner' ), $tmpfile )
				);
				$snapshot['abspath'][$tmpfile]['type'] = 'core';
			} else {
				$snapshot['abspath'][$tmpfile]['v'] = 1;
			}

		}
		// Used to check for additional files uploaded in
		// the wp-admin & wp-includes folders and ABSPATH:
		if ( isset( $snapshot['core_unknown'][$tmpfile] ) ) {
			unset( $snapshot['core_unknown'][$tmpfile] );
		}
		if ( isset( $snapshot['core_unknown_root'][$tmpfile] ) ) {
			unset( $snapshot['core_unknown_root'][$tmpfile] );
		}
	}
	// Remove the extracted files/directories:
	nscan_remove_dir( NSCAN_CACHEDIR ."/$wp_version" );


	// Build the files/folders exclusion list
	$excluded_folders = '';
	if (! empty( $nscan_options['scan_folders'] ) && ! empty( $nscan_options['scan_folders_fic'] ) ) {
		$folders = json_decode( $nscan_options['scan_folders'], true );
		if ( is_array( $folders ) ) {
			foreach( $folders as $folder ) {
				$excluded_folders .= preg_quote( $folder ) . '|';
			}
			$excluded_folders = rtrim( $excluded_folders , '|' );
			nscan_log_debug( __('Creating files/folders exclusion list', 'ninjascanner') );
		}
	}
	// Remove unknow file that are in the exclusion list
	if ( $excluded_folders ) {
		foreach( $snapshot['core_unknown_root'] as $n => $t ) {
			if ( preg_match( "`$excluded_folders`i", $n ) ) {
				unset( $snapshot['core_unknown_root'][ $n ] );
				nscan_log_debug(
					sprintf(
						__('Ignoring unknown file, it is in the exclusion list: %s', 'ninjascanner'),
						$n
					)
				);
			}
		}
		foreach( $snapshot['core_unknown'] as $n => $t ) {
			if ( preg_match( "`$excluded_folders`i", $n ) ) {
				unset( $snapshot['core_unknown'][ $n ] );
				nscan_log_debug(
					sprintf(
						__('Ignoring unknown file, it is in the exclusion list: %s', 'ninjascanner'),
						$n
					)
				);
			}
		}
	}

	if (! empty( $snapshot['core_failed_checksum'] ) ) {
		nscan_log_warn( sprintf(
			__('Total modified core files: %s', 'ninjascanner'),
			count( $snapshot['core_failed_checksum'] )
		));
		return false;
	}
	// Checksums match:
	return true;
}

// =====================================================================
// Return the list of files from a ZIP archive without extracting them.

function nscan_get_zip_files_list( $zip_file ) {

	nscan_log_debug( __('Building files list from ZIP archive', 'ninjascanner') );

	// By default we use ZipArchive, but if it's not available,
	// we fall back to the built-in PclZip library:
	if ( class_exists('ZipArchive') ) {

		$zip = new ZipArchive();
		$zip_files_list = array();

		if ( ( $res = $zip->open( $zip_file ) ) === true ) {
			for ( $i = 0; $i < $zip->numFiles; ++$i ) {
				$stat = $zip->statIndex( $i );
				// Ignore folders:
				if ( substr( $stat['name'], -1 ) == '/' ) {
					continue;
				}
				// Remove the plugin slug + its following slash:
				$file_name =  substr( $stat['name'], strpos( $stat['name'], '/' ) + 1 );
				$zip_files_list[$file_name] = $stat['crc']; // 'crc' is unused since v3.0
				unset($stat);
			}
			$zip->close();

			// Make sure we have something:
			if ( count( $zip_files_list ) < 1 ) {
				nscan_log_error( __('Files list is empty. Skipping this archive', 'ninjascanner') );
				return false;
			}
			// Return the files list:
			return $zip_files_list;
		}

		nscan_log_error( sprintf(
			__('Unable to open ZIP archive (error code: %s)', 'ninjascanner'),
			$res
		));
		// Delete the corrupted zip file
		unlink( $zip_file );
		return false;

	} else {
		// PclZip
		require_once ABSPATH .'wp-admin/includes/class-pclzip.php';

		$zip = new PclZip( $zip_file );
		$zip_files_list = array();

		if ( ( $list = $zip->listContent() ) === 0 ) {
			nscan_log_error( sprintf(
				__('Unable to open ZIP archive (error code: %s)', 'ninjascanner'),
				'PclZip'
			));
			// Delete the corrupted zip file
			unlink( $zip_file );
			return false;
		}
		for ( $i = 0; $i < sizeof( $list ); $i++ ) {
			// Ignore folders:
			if ( substr( $list[$i]['filename'], -1 ) == '/' ) {
				continue;
			}
			// Remove the plugin slug + its following slash:
			$file_name =  substr( $list[$i]['filename'], strpos( $list[$i]['filename'], '/' ) + 1 );
			$zip_files_list[$file_name] = 1;
		}
		// Make sure we have something:
		if ( count( $zip_files_list ) < 1 ) {
			nscan_log_error( __('Files list is empty. Skipping this archive', 'ninjascanner') );
			return false;
		}
		// Return the files list:
		return $zip_files_list;
	}
}

// =====================================================================
// Extract a ZIP archive into the cache folder. Destination folder
// will match the plugin/theme slug.

function nscan_extract_archive( $zip_file, $destination_folder ) {

	if ( is_dir( $destination_folder ) ) {
		// The destination folder exists, let's delete it:
		nscan_remove_dir( $destination_folder );
	}

	if ( mkdir( $destination_folder ) === false ) {
		nscan_log_warn( sprintf(
			__('Cannot create folder %s. Is your filesystem read-only?', 'ninjascanner'),
			$destination_folder
		));
		return false;
	}

	// By default we use ZipArchive, but if it's not available,
	// we fall back to the built-in PclZip library:
	if ( class_exists('ZipArchive') ) {
		$zip = new ZipArchive;
		if ( ( $res = $zip->open( $zip_file ) ) === true ) {
			$zip->extractTo( $destination_folder );
			$zip->close();
			return true;
		}

	} else {
		// PclZip
		require_once ABSPATH .'wp-admin/includes/class-pclzip.php';
		$zip = new PclZip( $zip_file );
		if ( $zip->extract( $destination_folder ) !== 0 ) {
			return true;
		}
	}

	nscan_log_error( sprintf(
		__('Unable to extract ZIP archive (error code: %s)', 'ninjascanner'),
		$res
	));
	// Delete destination folder:
	nscan_remove_dir( $destination_folder );

	return false;
}

// =====================================================================
// Check Google Safe Browsing.

function nscan_check_gsb( $lock_status ) {

	global $snapshot, $wp_version, $nscan_steps;

	$nscan_options = get_option( 'nscan_options' );

	if ( $nscan_options['scan_gsb'] ) {
		$message = __('Checking Google Safe Browsing', 'ninjascanner' );
		nscan_log_info( $message );
		nscan_set_lock_status(
			$lock_status['current_step'],
			'success',
			$message
		);

		$url = '';

		// In a multisite environment, we must check all sites:
		if ( is_multisite() && function_exists( 'get_sites' ) && class_exists( 'WP_Site_Query' ) ) {
			$mysites = get_sites([
				'public'  => 1,
				// <500, Google Safe Browsing usage limit
				'number'  => 499,
				'orderby' => 'registered',
				'order'   => 'ASC'
			]);
			foreach( $mysites as $id => $v ) {
				$site = get_site_url( $mysites[$id]->blog_id );
				if ( $site ) {
					$url .= '{"url": "'. $site .'"},';
				}
			}
			$total = count($mysites);
			$mysites = '';

		// Single site:
		} else {
			$site = home_url('/');
			$url .= '{"url": "'. $site .'"},';
			$total = 1;
		}

		nscan_log_info( sprintf(
			__('Total URL to check: %s', 'ninjascanner'),
			$total
		) );

		// Used for Google referrer restriction:
		$referrer = get_site_url();

		$body = array(
			'body' => '{
				"threatInfo": {
					"threatTypes":      ["MALWARE", "SOCIAL_ENGINEERING"],
					"platformTypes":    ["ANY_PLATFORM"],
					"threatEntryTypes": ["URL"],
					"threatEntries": [
						'. $url .'
					]
				}
			}',
			'headers' => array(
				'content-type' => 'application/json',
				'Referer' => $referrer
			),
			'data_format' => 'body',
			'user-agent' => 'Mozilla/5.0 (compatible; NinjaScanner/'.
								NSCAN_VERSION ."; WordPress/{$wp_version})",
			'timeout' => NSCAN_CURL_TIMEOUT,
			'httpversion' => '1.1' ,
			'sslverify' => true
		);
		$res = wp_remote_post( NSCAN_GSB . "?key={$nscan_options['scan_gsb']}", $body );

		if (! is_wp_error($res) ) {
			$data = json_decode( $res['body'], true );

			// Invalid key:
			if (! empty( $data['error']['message'] ) ) {
				nscan_log_error( $data['error']['message'] );
				$snapshot['step_error'][ $nscan_steps[ $lock_status['current_step'] ] ] = $data['error']['message'];
				goto GSB_SAVE_SNAPSHOT;
			}

			$snapshot['scan_gsb'] = array();
			if (! empty( $data['matches'] ) ) {
				foreach( $data['matches'] as $key ) {
					foreach( $key as $k => $v ) {
						$snapshot['scan_gsb'][$key['threat']['url']] = 1;
					}
				}
			}

			if (! empty( $snapshot['scan_gsb'] ) ) {
				nscan_log_warn( sprintf(
					__('Total blacklisted URL: %s', 'ninjascanner' ),
					count( $snapshot['scan_gsb'] )
				));
			}

		// Unknown error
		} else {
			$err = sprintf(
				__('%s. Cannot check Google Safe Browsing. Try again later', 'ninjascanner'),
				$res->get_error_message()
			);
			nscan_log_error( $err );
			$snapshot['step_error'][ $nscan_steps[ $lock_status['current_step'] ] ] = $err;
		}

	} else {
		$message = __('Skipping Google Safe Browsing: no API key found', 'ninjascanner');
		nscan_log_info( $message );
		$snapshot['skip']['scan_gsb'] = 1;
	}


GSB_SAVE_SNAPSHOT:
	// Save snapshot
	file_put_contents( NSCAN_TMP_SNAPSHOT, serialize( $snapshot ) );

	nscan_set_lock_status(
		++$lock_status['current_step'],
		'success',
		$message
	);
}

// =====================================================================
// Retrieve the list of all plugins (inc. MU and dropins).

function nscan_get_plugins_list( $lock_status ) {

	global $snapshot, $ignored_files;

	$nscan_options = get_option( 'nscan_options' );

	if ( $nscan_options['scan_pluginsintegrity'] ) {
		$message = __('Building plugins list', 'ninjascanner' );
		nscan_log_info( $message );
		nscan_set_lock_status(
			$lock_status['current_step'],
			'success',
			$message
		);

		// Build the list of plugins (slug & version):
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = get_plugins();
		$nscan_plugins_list = array();
		foreach( $plugins as $k => $v ) {
			if ( $slug = substr( $k, 0, strpos( $k, '/' ) ) ) {
				$nscan_plugins_list['plugins'][$slug] = $v['Version'];
			} else {
				// Ignore 'Hello Dolly', we checked it already with WP core files:
				if ( $v['Name'] != 'Hello Dolly' ) {

					// Don't know what it is. It could be a backdoor,
					// we'll warn the user about it later:
					$snapshot['plugins_unknown'][$k] = $v['Version'];

					nscan_log_warn( sprintf(
						__('Additional/suspicious plugin: %s %s (%s)', 'ninjascanner'),
						$v['Name'], $v['Version'], WP_PLUGIN_DIR . "/$k"
					));
				}
			}
		}

		// Check if there is any MU plugins too:
		$mu_plugins = get_mu_plugins();
		foreach( $mu_plugins as $k => $v ) {

			if (! empty( $ignored_files[ WPMU_PLUGIN_DIR ."/$k"] ) ) {
				// It's in our ignored list, skip it
				continue;
			}

			if ( $slug = substr( $k, 0, strpos( $k, '/' ) ) ) {
				// Plugin with a folder/slug:
				$snapshot['mu_plugins'][$slug] = $v['Version'];

			} else {
				// No folder, just a single PHP script:
				if ( $k == '0-ninjafirewall.php' ) {
					$mu = __FILE__; // Placeholder
					if ( file_exists( WP_PLUGIN_DIR .'/ninjafirewall/lib/loader.php' ) ) {
						$mu = WP_PLUGIN_DIR .'/ninjafirewall/lib/loader.php';
					} elseif ( file_exists( WP_PLUGIN_DIR .'/nfwplus/lib/loader.php' ) ) {
						$mu = WP_PLUGIN_DIR .'/nfwplus/lib/loader.php';
					}
					if ( md5_file( WPMU_PLUGIN_DIR . "/$k" ) === md5_file( $mu ) ) {
						continue;
					}
				}
				$snapshot['mu_plugins'][$k] = $v['Version'];

			}
			nscan_log_warn( sprintf(
				__('mu-plugin found: %s %s (%s)', 'ninjascanner'),
				$v['Name'], $v['Version'], WPMU_PLUGIN_DIR . "/$k"
			));
		}

		$dropins = array(
			'advanced-cache.php', 'db.php', 'db-error.php', 'install.php', 'maintenance.php',
			'object-cache.php', 'sunrise.php', 'blog-deleted.php', 'blog-inactive.php',
			'blog-suspended.php'
		);
		foreach( $dropins as $dropin ) {
			if (! empty( $snapshot['abspath'][WP_CONTENT_DIR ."/$dropin"] ) ) {
				$snapshot['plugins_dropins'][$dropin] = 1;
			}
		}

		if ( empty( $nscan_plugins_list['plugins'] ) ) {
			nscan_log_warn( __('No plugins found', 'ninjascanner') );
			goto PLUGINS_SAVE_SNAPSHOT;
		}

		nscan_log_debug( sprintf(
			__('Total plugins found: %s', 'ninjascanner'),
			count( $nscan_plugins_list['plugins'] )
		));

		// Save list to temp file
		file_put_contents( NSCAN_TMP_LIST, serialize( $nscan_plugins_list ) );

		// Build the files/folders exclusion list
		$excluded_folders = 'readme\.txt|';
		if (! empty( $nscan_options['scan_folders'] ) && ! empty( $nscan_options['scan_folders_fic'] ) ) {
			$folders = json_decode( $nscan_options['scan_folders'], true );
			if ( is_array( $folders ) ) {
				foreach( $folders as $folder ) {
					$excluded_folders .= preg_quote( $folder ) . '|';
				}
				nscan_log_debug( __('Creating files/folders exclusion list', 'ninjascanner') );
			}
		}
		$snapshot['tmp']['excluded_folders'] = rtrim( $excluded_folders , '|' );


	// Skip this step
	} else {
		$message = __('Skipping plugin files integrity check', 'ninjascanner');
		nscan_log_info( $message );
		$snapshot['skip']['scan_pluginsintegrity'] = 1;
		// We want to skip the next step as well
		++$lock_status['current_step'];
	}

PLUGINS_SAVE_SNAPSHOT:
	// Save snapshot
	file_put_contents( NSCAN_TMP_SNAPSHOT, serialize( $snapshot ) );

	nscan_set_lock_status(
		++$lock_status['current_step'],
		'success',
		$message
	);

}

// =====================================================================
// Check integrity of all plugins.

function nscan_check_plugins( $lock_status ) {

	global $snapshot, $ignored_files;

	$nscan_options = get_option( 'nscan_options' );

	$message = __('Checking plugin files integrity', 'ninjascanner' );

	nscan_log_info( $message );
	nscan_set_lock_status(
		$lock_status['current_step'],
		'success',
		$message
	);

	$failed = 0;

	// Fetch files/folders exclusion list
	$excluded_folders = $snapshot['tmp']['excluded_folders'];

	// Retrieve list of plugins
	$nscan_plugins_list = unserialize( file_get_contents( NSCAN_TMP_LIST ) );
	@unlink( NSCAN_TMP_LIST );

	// Let's check their integrity if possible
	// (i.e., if they are available on wordpress.org)
	$unknown_count = 0;
	foreach( $nscan_plugins_list['plugins'] as $slug => $version ) {

		nscan_is_scan_cancelled();

		$msg = "$slug $version";
		nscan_log_debug( $msg );
		nscan_set_lock_status(
			$lock_status['current_step'],
			'success',
			"$message ($msg)"
		);
		// Remove empty file, if any
		if ( file_exists( NSCAN_CACHEDIR ."/plugin_{$slug}.{$version}.zip" ) ) {
			if ( filesize( NSCAN_CACHEDIR ."/plugin_{$slug}.{$version}.zip" ) < 100 ) {
				unlink( NSCAN_CACHEDIR ."/plugin_{$slug}.{$version}.zip" );
			}
		}

		// If we already checked NinjaScanner files integrity, we skip it:
		if ( $nscan_options['scan_ninjaintegrity'] && $slug == 'ninjascanner' ) {
			nscan_log_debug( __('Ignoring NinjaScanner, its integrity was checked already', 'ninjascanner') );
			$snapshot['plugins'][$slug] = array();
			continue;
		}

		// Users can upload their own ZIP in a folder named "local":
		if ( file_exists( NSCAN_LOCAL ."/{$slug}.{$version}.zip" ) ) {
			nscan_log_debug( __('Using user-uploaded local copy', 'ninjascanner') );
			$plugin_zip = NSCAN_LOCAL ."/{$slug}.{$version}.zip";

		// Check if we have a cached copy of the ZIP file:
		} elseif ( file_exists( NSCAN_CACHEDIR ."/plugin_{$slug}.{$version}.zip" ) ) {
			nscan_log_debug( __('Using local copy', 'ninjascanner') );
			$plugin_zip = NSCAN_CACHEDIR ."/plugin_{$slug}.{$version}.zip";

		} else {
			nscan_log_debug( __('Attempting to download it from wordpress.org', 'ninjascanner') );

			if ( nscan_wp_repo_download( $slug, $version, 'plugin', false ) === false ) {
				// Try to download it from the trunk folder instead:
				nscan_log_debug( __('Not found. Attempting to download it from the trunk folder instead', 'ninjascanner') );
				if ( nscan_wp_repo_download( $slug, $version, 'plugin', true ) === false ) {
					// Remove the plugin from the list if we didn't find it in the WP repo:
					unset( $snapshot['plugins'][$slug] );
					$snapshot['plugins_not_found'][$slug] = $version;
					continue;
				}
			}
			$plugin_zip = NSCAN_CACHEDIR ."/plugin_{$slug}.{$version}.zip";
		}

		// Return the ZIP archive list of files:
		$zip_files_list = array();
		if ( ( $zip_files_list = nscan_get_zip_files_list( $plugin_zip ) ) === false ) {
			// Error, try next one:
			continue;
		}

		// Extract the ZIP
		if ( nscan_extract_archive( $plugin_zip, NSCAN_CACHEDIR ."/$slug" ) === false ) {
			// Error, try next one:
			continue;
		}

		// Select algo:
		if ( empty( $nscan_options['scan_checksum'] ) || $nscan_options['scan_checksum'] == 1 ) {
			$algo = 'md5';
		} elseif ( $nscan_options['scan_checksum'] == 2 ) {
			$algo = 'sha1';
		} else {
			$algo = 'sha256';
		}

		nscan_log_debug( sprintf( __('Using %s algo', 'ninjascanner'), $algo ) );

		// Compare local files with archive files:
		foreach( $zip_files_list as $file => $checksum ) {

			// Make sure the file exists and is not on our ignored files list
			if ( file_exists( WP_PLUGIN_DIR ."/$slug/$file" ) && empty( $ignored_files[WP_PLUGIN_DIR ."/$slug/$file"] ) ) {

				$local_file = hash_file( $algo, WP_PLUGIN_DIR ."/$slug/$file" );
				$original_file = hash_file( $algo, NSCAN_CACHEDIR ."/$slug/$slug/$file" ); // NSCAN_CACHEDIR/slug/slug/*

				// Compare checksums:
				if ( $local_file !== $original_file ) {
					$snapshot['plugins'][$slug][$file] = 1;
					nscan_log_warn( sprintf(
						__('Checksum mismatch: %s', 'ninjascanner'),
						WP_PLUGIN_DIR ."/$slug/$file"
					));
					++$failed;
					// Record type, version and slug for the report:
					$snapshot['abspath'][WP_PLUGIN_DIR ."/$slug/$file"]['slug'] = $slug;
					$snapshot['abspath'][WP_PLUGIN_DIR ."/$slug/$file"]['version'] = $version;
					$snapshot['abspath'][WP_PLUGIN_DIR ."/$slug/$file"]['type'] = 'plugin';
				} else {
					// Remove the file from our list if it matches:
					unset( $snapshot['plugins'][$slug][$file] );
					$snapshot['abspath'][WP_PLUGIN_DIR ."/$slug/$file"]['v'] = 2;
				}
			}
		}
		// Remove the extracted files/directories:
		nscan_remove_dir( NSCAN_CACHEDIR ."/$slug" );

		// Look for additional files in the plugins folder
		// unless we didn't scan that folder
		if (! empty( $snapshot['plugins'][$slug] ) ) {
			foreach( $snapshot['plugins'][$slug] as $k => $v ) {
				if ( $excluded_folders && preg_match( "`$excluded_folders`i", WP_PLUGIN_DIR ."/$slug/$k" ) ) {
					// Ignore it, it's in the exclusion list:
					unset( $snapshot['plugins'][$slug][$k] );
					continue;
				}
				if ( $v == 0 ) { ++$unknown_count; }
			}
		}
	}

	if ( $unknown_count ) {
		nscan_log_warn( sprintf(
			__('Additional/suspicious files: %s', 'ninjascanner'),
			$unknown_count
		));
	}

	if ( $failed ) {
		nscan_log_warn( sprintf(
			__('Total modified plugin files: %s', 'ninjascanner'),
			$failed
		));

	} else {
		nscan_log_info( __('Plugin files integrity is O.K', 'ninjascanner') );
	}

	// Save snapshot
	file_put_contents( NSCAN_TMP_SNAPSHOT, serialize( $snapshot ) );

	nscan_set_lock_status(
		++$lock_status['current_step'],
		'success',
		$message
	);
}

// =====================================================================
// Retrieve the list of all themes.

function nscan_get_themes_list( $lock_status ) {

	global $snapshot;

	$nscan_options = get_option( 'nscan_options' );

	if ( $nscan_options['scan_themeseintegrity'] ) {
		$message = __('Building themes list', 'ninjascanner' );
		nscan_log_info( $message );
		nscan_set_lock_status(
			$lock_status['current_step'],
			'success',
			$message
		);

		// Build the list of themes (slug & version):
		if ( ! function_exists( 'wp_get_themes' ) ) {
			require_once ABSPATH . 'wp-includes/theme.php';
		}
		$themes = wp_get_themes();
		$nscan_themes_list = array();
		foreach( $themes as $k => $v ) {
			$nscan_themes_list['themes'][$k] = $v->Version;
		}

		if ( empty( $nscan_themes_list['themes'] ) ) {
			// That should never happened!
			nscan_log_warn( __('No themes found', 'ninjascanner') );
			goto THEMES_SAVE_SNAPSHOT;
		}

		nscan_log_debug( sprintf(
			__('Total themes found: %s', 'ninjascanner'),
			count( $nscan_themes_list['themes'] )
		));

		// Save list to temp file
		file_put_contents( NSCAN_TMP_LIST, serialize( $nscan_themes_list ) );

		// Build the files/folders exclusion list
		$excluded_folders = '';
		if (! empty( $nscan_options['scan_folders'] ) && ! empty( $nscan_options['scan_folders_fic'] ) ) {
			$folders = json_decode( $nscan_options['scan_folders'], true );
			if ( is_array( $folders ) ) {
				foreach( $folders as $folder ) {
					$excluded_folders .= preg_quote( $folder ) . '|';
				}
				$excluded_folders = rtrim( $excluded_folders , '|' );
				nscan_log_debug( __('Creating files/folders exclusion list', 'ninjascanner') );
			}
		}
		$snapshot['tmp']['excluded_folders'] = $excluded_folders;

	// Skip this step
	} else {
		$message = __('Skipping theme files integrity check', 'ninjascanner');
		nscan_log_info( $message );
		$snapshot['skip']['scan_themeseintegrity'] = 1;
		// We want to skip the next step as well
		++$lock_status['current_step'];
	}

THEMES_SAVE_SNAPSHOT:
	// Save snapshot
	file_put_contents( NSCAN_TMP_SNAPSHOT, serialize( $snapshot ) );

	nscan_set_lock_status(
		++$lock_status['current_step'],
		'success',
		$message
	);

}

// =====================================================================
// Check integrity of all themes.

function nscan_check_themes( $lock_status ) {

	global $snapshot, $ignored_files;

	$nscan_options = get_option( 'nscan_options' );

	$message = __('Checking theme files integrity', 'ninjascanner' );

	nscan_log_info( $message );
	nscan_set_lock_status(
		$lock_status['current_step'],
		'success',
		$message
	);

	$failed = 0;

	// Fetch files/folders exclusion list
	$excluded_folders = $snapshot['tmp']['excluded_folders'];

	// Retrieve list of themes
	$nscan_themes_list = unserialize( file_get_contents( NSCAN_TMP_LIST ) );
	@unlink( NSCAN_TMP_LIST );

	// Let's check their integrity if possible
	// (i.e., they are available at wordpress.org)
	$unknown_count = 0;
	foreach( $nscan_themes_list['themes'] as $slug => $version ) {

		nscan_is_scan_cancelled();

		$msg = "$slug $version";
		nscan_log_debug( $msg );
		nscan_set_lock_status(
			$lock_status['current_step'],
			'success',
			"$message ($msg)"
		);

		// Remove empty file, if any
		if ( file_exists( NSCAN_CACHEDIR ."/theme_{$slug}.{$version}.zip" ) ) {
			if ( filesize( NSCAN_CACHEDIR ."/theme_{$slug}.{$version}.zip" ) < 100 ) {
				unlink( NSCAN_CACHEDIR ."/theme_{$slug}.{$version}.zip" );
			}
		}

		// Users can upload their own ZIP in a folder named "local":
		if ( file_exists( NSCAN_LOCAL ."/{$slug}.{$version}.zip" ) ) {
			nscan_log_debug( __('Using user-uploaded local copy', 'ninjascanner') );
			$theme_zip = NSCAN_LOCAL ."/{$slug}.{$version}.zip";

		// Check if we have a cached copy of the ZIP file:
		} elseif ( file_exists( NSCAN_CACHEDIR ."/theme_{$slug}.{$version}.zip" ) ) {
			nscan_log_debug( __('Using local copy', 'ninjascanner') );
			$theme_zip = NSCAN_CACHEDIR ."/theme_{$slug}.{$version}.zip";

		} else {
			nscan_log_debug( __('Attempting to download it from wordpress.org', 'ninjascanner') );

			if ( nscan_wp_repo_download( $slug, $version, 'theme', false ) === false ) {
				// Remove the theme from the list if we didn't find it in the WP repo:
				unset( $snapshot['themes'][$slug] );
				$snapshot['themes_not_found'][$slug] = $version;
				continue;
			}
			$theme_zip = NSCAN_CACHEDIR ."/theme_{$slug}.{$version}.zip";
		}

		// Return the ZIP archive list of files:
		$zip_files_list = array();
		if ( ( $zip_files_list = nscan_get_zip_files_list( $theme_zip ) ) === false ) {
			// Error, try next one:
			continue;
		}

		// Extract the ZIP
		if ( nscan_extract_archive( $theme_zip, NSCAN_CACHEDIR ."/$slug" ) === false ) {
			// Error, try next one:
			continue;
		}

		// Select algo:
		if ( empty( $nscan_options['scan_checksum'] ) || $nscan_options['scan_checksum'] == 1 ) {
			$algo = 'md5';
		} elseif ( $nscan_options['scan_checksum'] == 2 ) {
			$algo = 'sha1';
		} else {
			$algo = 'sha256';
		}

		nscan_log_debug( sprintf( __('Using %s algo', 'ninjascanner'), $algo ) );

		// Compare local files with archive files:
		foreach( $zip_files_list as $file => $checksum ) {

			// Make sure the file exists and is not on our ignored files list
			if ( file_exists( WP_CONTENT_DIR ."/themes/$slug/$file" ) && empty( $ignored_files[WP_CONTENT_DIR ."/themes/$slug/$file"] ) ) {
				$local_file = hash_file( $algo, WP_CONTENT_DIR ."/themes/$slug/$file" );
				$original_file = hash_file( $algo, NSCAN_CACHEDIR ."/$slug/$slug/$file" ); // NSCAN_CACHEDIR/slug/slug/*

				// Compare checksums:
				if ( $local_file !== $original_file ) {
					$snapshot['themes'][$slug][$file] = 1;
					nscan_log_warn( sprintf(
						__('Checksum mismatch: %s', 'ninjascanner'),
						WP_CONTENT_DIR ."/themes/$slug/$file"
					));
					++$failed;
					// Record type, version and slug for the report:
					$snapshot['abspath'][WP_CONTENT_DIR ."/themes/$slug/$file"]['slug'] = $slug;
					$snapshot['abspath'][WP_CONTENT_DIR ."/themes/$slug/$file"]['version'] = $version;
					$snapshot['abspath'][WP_CONTENT_DIR ."/themes/$slug/$file"]['type'] = 'theme';
				} else {
					// Remove the file from our list if it matches:
					unset( $snapshot['themes'][$slug][$file] );
					$snapshot['abspath'][WP_CONTENT_DIR ."/themes/$slug/$file"]['v'] = 2;
				}
			}
		}
		// Remove the extracted files/directories:
		nscan_remove_dir( NSCAN_CACHEDIR ."/$slug" );

		// Look for additional files in the themes folder
		// unless we didn't scan that folder
		if (! empty( $snapshot['themes'][$slug] ) ) {
			foreach( $snapshot['themes'][$slug] as $k => $v ) {
				if ( $excluded_folders && preg_match( "`$excluded_folders`i", WP_CONTENT_DIR ."/themes/$slug/$k" ) ) {
					// Ignore it, it's in the exclusion list:
					unset( $snapshot['themes'][$slug][$k] );
					continue;
				}
				if ( $v == 0 ) { ++$unknown_count; }
			}
		}
	}

	if ( $unknown_count ) {
		nscan_log_warn( sprintf(
			__('Additional/suspicious files: %s', 'ninjascanner'),
			$unknown_count
		));
	}

	if ( $failed ) {
		nscan_log_warn( sprintf(
			__('Total modified theme files: %s', 'ninjascanner'),
			$failed
		));

	} else {
		nscan_log_info( __('Theme files integrity is O.K', 'ninjascanner') );
	}

	// Save snapshot
	file_put_contents( NSCAN_TMP_SNAPSHOT, serialize( $snapshot ) );

	nscan_set_lock_status(
		++$lock_status['current_step'],
		'success',
		$message
	);
}

// =====================================================================
// Download a plugin or theme ZIP file from the wordpress.org repo
// and save it to the cache folder. The copy will be kept locally
// until NinjaScanner's garbage collector cron job deletes it.
// If it is a plugin and the operation failed (404 Not Found), this
// function is called once again in an attempt to downloaded the file
// from the trunk folder instead (some developers may not tag their plugin).

function nscan_wp_repo_download( $slug, $version, $type, $trunk = false ) {

	global $wp_version;

	if ( $type == 'plugin' ) {
		// Plugin URL:
		if ( $trunk ) {
			$url = NSCAN_PLUGINS_URL ."{$slug}.zip";
		} else {
			$url = NSCAN_PLUGINS_URL ."{$slug}.{$version}.zip";
		}

	} else {
		// Theme URL:
		$url = NSCAN_THEMES_URL ."{$slug}.{$version}.zip";
	}

	$res = wp_remote_get(
		$url,
		array(
			'stream' => true,
			'filename' => NSCAN_CACHEDIR ."/{$type}_{$slug}.{$version}.zip",
			'timeout' => NSCAN_CURL_TIMEOUT,
			'httpversion' => '1.1' ,
			'user-agent' => 'Mozilla/5.0 (compatible; NinjaScanner/'.
									NSCAN_VERSION .'; WordPress/'. $wp_version . ')',
			'sslverify' => true
		)
	);

	if (! is_wp_error( $res ) ) {
		if ( $res['response']['code'] == 200 ) {
			return true;
		}

		if (file_exists( NSCAN_CACHEDIR ."/{$type}_{$slug}.{$version}.zip" )) {
			unlink( NSCAN_CACHEDIR ."/{$type}_{$slug}.{$version}.zip" );
		}
		if ( $trunk || $type == 'theme' ) {
			// Probably not available in wordpress.org repo, ignore it:
			nscan_log_warn( sprintf(
				__('HTTP Error %s. Skipping %s %s, it may not be available in the repo', 'ninjascanner'),
				(int)$res['response']['code'],
				$slug,
				$version
			));
		}
		return false;
	}

	// Unknown error:
	nscan_log_error( sprintf(
		__('%s. Skipping it. You may try again later', 'ninjascanner'),
		$res->get_error_message()
	));
	return false;
}

// =====================================================================
// Check NinjaScanner's files integrity by downloading its checksum
// hashes - or using the local cached copy. In case of mismatch,
// refuse to run (users can still bypass this by disabling the integrity
// checker from the settings page).

function nscan_check_ninjascanner_integrity() {

	global $wp_version;
	global $snapshot;
	$nscan_hashes = array();

	// Do we have a local cached version?
	if ( file_exists( NSCAN_HASHFILE ) ) {
		nscan_log_debug( __('Using local cached version of checksums', 'ninjascanner') );
		$nscan_hashes = json_decode( file_get_contents( NSCAN_HASHFILE ), true );
		// Make sure we have what we are expecting:
		if ( empty( $nscan_hashes['checksums']['ninjascanner/lib/constants.php'] ) ) {
			nscan_log_warn( __('Decoded hashes seem corrupted. Deleting local cached version', 'ninjascanner') );
			// Delete the file:
			unlink( NSCAN_HASHFILE );
			$nscan_hashes = array();
		}
	}

	// NinjaScanner's wordpress.og repo URL:
	$url = sprintf( NSCAN_SVN_PLUGINS, 'ninjascanner',	NSCAN_VERSION ) .'/checksum.txt';

	// Download them:
	if ( empty( $nscan_hashes ) ) {
		nscan_log_debug( __('Downloading checksums', 'ninjascanner') );
		$res = wp_remote_get(
			$url,
			array(
				'timeout' => NSCAN_CURL_TIMEOUT,
				'httpversion' => '1.1' ,
				'user-agent' => 'Mozilla/5.0 (compatible; NinjaScanner/'.
										NSCAN_VERSION .'; WordPress/'. $wp_version . ')',
				'sslverify' => true
			)
		);
		if ( is_wp_error( $res ) ) {
			nscan_log_error(
				sprintf( __('%s. Skipping this step', 'ninjascanner'), $res->get_error_message() )
			);
			// Don't return false, the server may be down. We'll attempt
			// to check the files again while checking all plugins integrity:
			return -1;
		}
		// Decode the content:
		$nscan_hashes = json_decode( $res['body'], true );
		// Make sure we have what we are expecting:
		if ( empty( $nscan_hashes['checksums']['ninjascanner/lib/constants.php'] ) ) {
			$message = __('Fatal error: NinjaScanner files integrity check: Decoded hashes seem corrupted. Aborting.', 'ninjascanner');
			$snapshot['error'] = $message;
			nscan_log_error( $message );
			return false;
		}
		// Save it to disk:
		file_put_contents( NSCAN_HASHFILE, $res['body'] );
	}

	// Loop through the array and compare hashes:
	$failed = 0;
	$missing = 0;
	foreach( $nscan_hashes['checksums'] as $file => $checksum ) {

		// Use WP_PLUGIN_DIR as user may have changed the path:
		$tmpfile = WP_PLUGIN_DIR . "/$file";
		if ( file_exists( $tmpfile ) ) {
			// Checksum does not match?
			if ( hash_file( 'sha256', $tmpfile ) !== $checksum ) {
				++$failed;
				nscan_log_warn(
					sprintf( __( 'Checksum mismatch: %s', 'ninjascanner' ), $tmpfile )
				);
			}
		} else {
			// Missing file:
			++$missing;
			nscan_log_warn(
				sprintf( __( 'Missing file: %s', 'ninjascanner' ), $tmpfile )
			);
		}
	}

	if ( $failed || $missing ) {
		$message = sprintf(
			__('Fatal error: Some NinjaScanner files have been modified (%s) or are missing (%s). Please reinstall NinjaScanner or disable NinjaScanner files integrity checker. Aborting.', 'ninjascanner'),
			"x$failed",
			"x$missing"
		);
		$snapshot['error'] = $message;
		nscan_log_error( $message );
		// Delete cached version:
		unlink( NSCAN_HASHFILE );
		return false;
	}

	// Checksums match:
	return true;
}

// =====================================================================
// Removed files/folders from the array depending of the user-defined
// exclusion lists (based on names and file size).

function nscan_apply_exclusion( $buffer, $log = 1, $verified = 1 ) {

	$nscan_options = get_option( 'nscan_options' );
	$count = 0;

	if ( $log ) {
		nscan_log_debug( __('Checking user-defined exclusion lists', 'ninjascanner') );
	}

	// Build the extensions exclusion list (case insensitive):
	$excluded_extensions = '';
	if (! empty( $nscan_options['scan_extensions'] ) ) {
		$extensions = json_decode( $nscan_options['scan_extensions'], true );
		if ( is_array( $extensions ) ) {
			foreach( $extensions as $extension ) {
				$excluded_extensions .= preg_quote( $extension ) . '|';
			}
			$excluded_extensions = '\.(?:'. rtrim( $excluded_extensions , '|' ) . ')$';
			if ( $log ) {
				nscan_log_debug( __('Creating extensions exclusion list', 'ninjascanner') );
			}
		}
	}
	// Build the files/folders exclusion list
	$excluded_folders = '';
	if (! empty( $nscan_options['scan_folders'] ) ) {
		$folders = json_decode( $nscan_options['scan_folders'], true );
		if ( is_array( $folders ) ) {
			foreach( $folders as $folder ) {
				$excluded_folders .= preg_quote( $folder ) . '|';
			}
			$excluded_folders = rtrim( $excluded_folders , '|' );
			if ( $log ) {
				nscan_log_debug( __('Creating files/folders exclusion list', 'ninjascanner') );
			}
		}
	}
	// Filesize limit:
	$file_size = 0;
	if (! empty( $nscan_options['scan_size'] ) ) {
		$file_size = $nscan_options['scan_size'] * 1024;
		if ( $log ) {
			nscan_log_debug( sprintf(
				__('Limiting search to files smaller than %s bytes', 'ninjascanner' ),
				number_format_i18n( $file_size )
			) );
		}
	}

	// Apply the two exclusion lists to the array:
	foreach( $buffer as $file => $values ) {

		// Used for binaries scan
		if (! $verified && ! empty( $values['v'] ) ) {
			unset( $buffer[$file] );
			++$count;
			continue;
		}

		if ( $excluded_extensions && preg_match( "`$excluded_extensions`i", $file ) ) {
			// Remove files from list:
			unset( $buffer[$file] );
			++$count;
			continue;
		}
		if ( $excluded_folders && preg_match( "`$excluded_folders`", $file ) ) {
			// Remove files from list:
			unset( $buffer[$file] );
			++$count;
			continue;
		}

		// Since v3.x, $values[1] may not exist if the file
		// was located in a excluded root folder
		if (! isset( $values[1] ) || ( $file_size && $values[1] > $file_size ) ) {
			// Too big, exclude it too
			unset( $buffer[$file] );
			++$count;
			continue;
		}
	}

	if ( $count && $log ) {
		nscan_log_debug( sprintf(
			__('Files ignored based on user-defined exclusion lists: %s', 'ninjascanner' ),
			$count
		));
	}

	// Return the buffer:
	return $buffer;
}

// =====================================================================
// Compare the current and previous snapshots for modifications (added,
// deleted and modified files).

function nscan_compare_snapshots( $lock_status ) {

	global $snapshot;

	$nscan_options = get_option( 'nscan_options' );

	$message = __('Comparing previous and current file snapshots', 'ninjascanner' );

	if (! file_exists( NSCAN_OLD_SNAPSHOT ) ) {
		nscan_log_info( __('Skipping snapshots comparison, no older files shapshot found', 'ninjascanner') );

	} elseif (! empty( $nscan_options['scan_warnfilechanged'] ) ) {

		nscan_log_info( $message );
		nscan_set_lock_status(
			$lock_status['current_step'],
			'success',
			$message
		);

		$previous_snapshot = array();
		$old_snapshot = unserialize( file_get_contents( NSCAN_OLD_SNAPSHOT ) );

		if ( empty( $old_snapshot['abspath'] ) ) {
			nscan_log_warn( __('Old snapshot file seems corrupted. Skipping this step', 'ninjascanner') );
			goto FILESNAPSHOTSAVE;
		}

		// Removed excluded files (based on user-exclusion lists)
		$current_snapshot = nscan_apply_exclusion( $snapshot['abspath'] );
		$previous_snapshot = nscan_apply_exclusion( $old_snapshot['abspath'], 0 );

		$count = 0;
		foreach( $current_snapshot as $file => $stat ) {
			// File didn't exist when the previous snapshot was taken:
			if (! isset( $previous_snapshot[$file] ) ) {
				$snapshot['snapshot']['added_files'][$file] = 1;
				++$count;
				continue;
			}
			// File was changed:
			if ( $previous_snapshot[$file][0] != $stat[0] ) {
				$snapshot['snapshot']['mismatched_files'][$file] = 1;
				++$count;
			}
			// Remove it from the list:
			unset( $previous_snapshot[$file] );
		}

		foreach( $previous_snapshot as $file => $stat ) {
			// File was removed:
			$snapshot['snapshot']['deleted_files'][$file] = 1;
			++$count;
		}

		if (! empty( $snapshot['snapshot']['added_files'] ) ) {
			nscan_log_warn( sprintf(
				__('Total additional files: %s', 'ninjascanner' ),
				count( $snapshot['snapshot']['added_files'] )
			));
		}
		if (! empty( $snapshot['snapshot']['mismatched_files'] ) ) {
			nscan_log_warn( sprintf(
				__('Total modified files: %s', 'ninjascanner' ),
				count( $snapshot['snapshot']['mismatched_files'] )
			));
		}
		if (! empty( $snapshot['snapshot']['deleted_files'] ) ) {
			nscan_log_warn( sprintf(
				__('Total deleted files: %s', 'ninjascanner' ),
				count( $snapshot['snapshot']['deleted_files'] )
			));
		}

		if (! $count ) {
			nscan_log_info( __('Previous and current snapshots match', 'ninjascanner') );
		}

	} else {
		$message = __('Skipping snapshots comparison', 'ninjascanner');
		nscan_log_info( $message );
		$snapshot['skip']['scan_warnfilechanged'] = 1;
	}

FILESNAPSHOTSAVE:
	// Save snapshot
	file_put_contents( NSCAN_TMP_SNAPSHOT, serialize( $snapshot ) );

	nscan_set_lock_status(
		++$lock_status['current_step'],
		'success',
		$message
	);
}

// =====================================================================
// Compare the current and previous database snapshots for modifications
// (added, deleted and modified posts and pages).

function nscan_compare_db_snapshots( $lock_status ) {

	global $snapshot;

	$nscan_options = get_option( 'nscan_options' );

	$message = __('Comparing previous and current database snapshots', 'ninjascanner' );

	if (! file_exists( NSCAN_OLD_SNAPSHOT ) ) {
		nscan_log_info( __('Skipping snapshots comparison, no older database shapshot found', 'ninjascanner') );

	} elseif (! empty( $nscan_options['scan_warndbchanged'] ) ) {

		nscan_log_info( $message );
		nscan_set_lock_status(
			$lock_status['current_step'],
			'success',
			$message
		);

		$old_snapshot = unserialize( file_get_contents( NSCAN_OLD_SNAPSHOT ) );

		if ( empty( $old_snapshot['abspath'] ) ) {
			nscan_log_warn( __('Old snapshot file seems corrupted. Skipping this step', 'ninjascanner') );
			goto DBNAPSHOTSAVE;

		} elseif (! isset( $old_snapshot['posts'] ) && ! isset( $old_snapshot['pages'] ) ) {
			nscan_log_info( __('Skipping snapshots comparison, no older database shapshot found', 'ninjascanner') );
			goto DBNAPSHOTSAVE;
		}

		$count = 0;

		// Posts:
		foreach( $snapshot['posts'] as $id => $val ) {
			// Post didn't exist when the previous snapshot was taken:
			if (! isset( $old_snapshot['posts'][$id] ) ) {
				$snapshot['snapshot']['added_posts'][$id] = $val['permalink'];
				++$count;
				continue;
			}
			// Post was changed:
			if ( $old_snapshot['posts'][$id]['hash'] != $val['hash'] ) {
				$snapshot['snapshot']['mismatched_posts'][$id] = $val['permalink'];
				++$count;
			}
			// Remove it from the list:
			unset( $old_snapshot['posts'][$id] );
		}
		// Make sur its not empty:
		if ( is_array( $old_snapshot['posts'] ) ) {
			foreach( $old_snapshot['posts'] as $id => $val ) {
				// Post was removed:
				$snapshot['snapshot']['deleted_posts'][$id] = $val['permalink'];
				++$count;
			}
		}
		if (! empty( $snapshot['snapshot']['added_posts'] ) ) {
			nscan_log_warn( sprintf(
				__('Total additional posts: %s', 'ninjascanner' ),
				count( $snapshot['snapshot']['added_posts'] )
			));
		}
		if (! empty( $snapshot['snapshot']['mismatched_posts'] ) ) {
			nscan_log_warn( sprintf(
				__('Total modified posts: %s', 'ninjascanner' ),
				count( $snapshot['snapshot']['mismatched_posts'] )
			));
		}
		if (! empty( $snapshot['snapshot']['deleted_posts'] ) ) {
			nscan_log_warn( sprintf(
				__('Total deleted posts: %s', 'ninjascanner' ),
				count( $snapshot['snapshot']['deleted_posts'] )
			));
		}

		// Pages:
		foreach( $snapshot['pages'] as $id => $val ) {
			// Page didn't exist when the previous snapshot was taken:
			if (! isset( $old_snapshot['pages'][$id] ) ) {
				$snapshot['snapshot']['added_pages'][$id] = $val['permalink'];
				++$count;
				continue;
			}
			// Page was changed:
			if ( $old_snapshot['pages'][$id]['hash'] != $val['hash'] ) {
				$snapshot['snapshot']['mismatched_pages'][$id] = $val['permalink'];
				++$count;
			}
			// Remove it from the list:
			unset( $old_snapshot['pages'][$id] );
		}
		// Make sur its not empty:
		if ( is_array( $old_snapshot['pages'] ) ) {
			foreach( $old_snapshot['pages'] as $id => $val ) {
				// Page was removed:
				$snapshot['snapshot']['deleted_pages'][$id] = $val['permalink'];
				++$count;
			}
		}
		if (! empty( $snapshot['snapshot']['added_pages'] ) ) {
			nscan_log_warn( sprintf(
				__('Total additional pages: %s', 'ninjascanner' ),
				count( $snapshot['snapshot']['added_pages'] )
			));
		}
		if (! empty( $snapshot['snapshot']['mismatched_pages'] ) ) {
			nscan_log_warn( sprintf(
				__('Total modified pages: %s', 'ninjascanner' ),
				count( $snapshot['snapshot']['mismatched_pages'] )
			));
		}
		if (! empty( $snapshot['snapshot']['deleted_pages'] ) ) {
			nscan_log_warn( sprintf(
				__('Total deleted pages: %s', 'ninjascanner' ),
				count( $snapshot['snapshot']['deleted_pages'] )
			));
		}

		if (! $count ) {
			nscan_log_info( __('Previous and current snapshots match', 'ninjascanner') );
		}

	} else {
		$message = __('Skipping snapshots comparison', 'ninjascanner');
		nscan_log_info( $message );
		$snapshot['skip']['scan_warndbchanged'] = 1;
	}

DBNAPSHOTSAVE:
	// Save snapshot
	file_put_contents( NSCAN_TMP_SNAPSHOT, serialize( $snapshot ) );

	nscan_set_lock_status(
		++$lock_status['current_step'],
		'success',
		$message
	);
}

// =====================================================================
// Build signatures and exclusion list for the malware scan.

function nscan_setup_antimalware( $lock_status ) {

	global $snapshot;

	// Check if we just started a new scan
	// or if we are already running
	if ( file_exists( NSCAN_FILES2CHECK ) ) {
		nscan_set_lock_status(
			++$lock_status['current_step'],
			'success',
			$lock_status['message']
		);
		return;
	}

	$nscan_options = get_option( 'nscan_options' );

	$scan_signatures = array();
	$scan_signatures = json_decode( $nscan_options['scan_signatures'], true );

	$message = __('Running anti-malware scanner', 'ninjascanner');

	if (! empty( $scan_signatures ) ) {

		nscan_log_info( $message );
		nscan_set_lock_status(
			$lock_status['current_step'],
			'success',
			$message
		);

		// Removed excluded files (based on user-exclusion lists)
		nscan_log_info( __('Building the list of files to check', 'ninjascanner') );
		$current_snapshot = array();
		$current_snapshot = nscan_apply_exclusion( $snapshot['abspath'] );
		// Save it
		file_put_contents( NSCAN_FILES2CHECK, serialize( $current_snapshot ) );

		// Check all signatures
		$signatures_list = array();
		nscan_log_info( __('Retrieving signatures lists', 'ninjascanner') );
		foreach( $scan_signatures as $sig ) {
			$tmp_list = array();
			// Built-in LMD + NinjaScanner signatures list
			if ( $sig == 'lmd' ) {
				// Download it:
				if ( nscan_download_signatures( $lock_status ) === false ) {
					continue;
				}
				// Verify signatures and return the list
				if ( ( $tmp_list = nscan_verify_signatures( NSCAN_SIGNATURES ) ) === false ) {
					continue;
				}
			} else {
				nscan_log_debug( sprintf(
					__('Checking user-defined signatures list (%s)', 'ninjascanner'), $sig
				));
				// Verify signatures and return the list:
				if ( ( $tmp_list = nscan_verify_signatures( $sig ) ) === false ) {
					continue;
				}
			}
			if ( $tmp_list ) {
				// Concatenate arrays
				$signatures_list += $tmp_list;
			}
		}
		// Saved compiled signatures (for 2 hours in the db)
		if ( $signatures_list ) {
			set_transient( 'nscan_temp_sigs', base64_encode( serialize( $signatures_list ) ), 7200 );
		} else {
			nscan_log_error( __('No valid signatures found', 'ninjascanner') );
			$snapshot['skip']['scan_antimalware'] = 1;
			// We want to skip the next step as well
			++$lock_status['current_step'];
		}

	} else {
		$message = __('Skipping anti-malware scan', 'ninjascanner');
		nscan_log_info( $message );
		$snapshot['skip']['scan_antimalware'] = 1;
		// We want to skip the next step as well
		++$lock_status['current_step'];
	}

	// Save snapshot
	file_put_contents( NSCAN_TMP_SNAPSHOT, serialize( $snapshot ) );

	nscan_set_lock_status(
		++$lock_status['current_step'],
		'success',
		$message
	);
}

// =====================================================================
// Scan files for malware.

function nscan_run_antimalware( $lock_status ) {

	global $snapshot;

	// Check if we still have some files to scan
	if (! file_exists( NSCAN_FILES2CHECK ) ) {
		$message = $lock_status['message'];
		goto ANTIMALWARESAVE; // goto power!
	}

	$files2check = unserialize( file_get_contents( NSCAN_FILES2CHECK ) );
	if ( empty( $files2check ) ) {
		$message = __('Files list array seems corrupted', 'ninjascanner');
		nscan_log_error( $message );
		goto ANTIMALWARESAVE; // goto power!
	}

	$nscan_temp_sigs = get_transient( 'nscan_temp_sigs' );
	if ( $nscan_temp_sigs === false ) {
		$message = __('No valid signatures found', 'ninjascanner');
		nscan_log_error( $message );
		goto ANTIMALWARESAVE; // goto power!
	}

	$signatures = unserialize( base64_decode( $nscan_temp_sigs ) );

	$message = __('Running anti-malware scanner', 'ninjascanner');
	$msg = __('items scanned:', 'ninjascanner');

	if (! empty( $snapshot['tmp']['scanstats'] ) ) {
		$stat = explode( ':', $snapshot['tmp']['scanstats'] );
		$total_scanned = $stat[0];
		$total_to_scan =  $stat[1];
	} else {
		$total_scanned = 0;
		$total_to_scan = count( $files2check );
	}

	$log_interval = 0;
	$start = time();

	foreach( $files2check as $file => $v ) {

		if ( time() - $start > NSCAN_PHPTIMEOUT ) {
			break;
		}

		if ( $log_interval > 10 ) {
			nscan_set_lock_status(
				$lock_status['current_step'],
				'success',
				"$message ($msg $total_scanned/$total_to_scan)"
			);
			$log_interval = 1;
			nscan_is_scan_cancelled();

		} else {
			++$log_interval;
		}
		++$total_scanned;

		if ( isset( $v['v'] ) && $v['v'] == 1 ) {
			// Don't scan core files that were verified already:
			unset( $files2check[$file] );
			continue;
		}

		if (! file_exists( $file ) ) {
			// The file may have just been deleted (e.g., temp file etc):
			nscan_log_warn( sprintf(
				__('File does not exist, ignoring it: %s', 'ninjascanner'), $file
			));
			unset( $files2check[$file] );
			continue;
		}

		// Clear the file from our buffer so that we could
		// restart where the scan left off in case or error/crash:
		unset( $files2check[$file] );

		if ( ( $content = file_get_contents( $file ) ) !== false ) {
			foreach ( $signatures as $name => $sig ) {

				// Don't scan verified plugin (v==2) & theme (v==3) files,
				// unless the signature requests it:
				if ( isset( $v['v'] ) && $v['v'] != $name[4] ) {
					continue;
				}

				// Regex signature:
				if ( $name[1] == 'R' ) {
					if ( preg_match( "`$sig`", $content ) ) {
						$snapshot['infected_files'][$file] = $name;
						nscan_log_warn( sprintf(
							__('Potentially unsafe files: %s', 'ninjascanner'), $file
						));
						break;
					}
				// Simple signature:
				} else {
					if ( strpos( $content, $sig ) !== false ) {
						$snapshot['infected_files'][$file] = $name;
						nscan_log_warn( sprintf(
							__('Potentially unsafe files: %s', 'ninjascanner'), $file
						));
						break;
					}
				}
				// Cancel scan request
				if ( file_exists( NSCAN_CANCEL ) ) {
					exit;
				}
			}

		} else {
			nscan_log_error( sprintf(
				__('Cannot open %s, skipping it', 'ninjascanner'), $file
			));
		}
	}

	// Temporarily stop and fork another process
	if (! empty( $files2check ) ) {
		$snapshot['tmp']['scanstats'] = "$total_scanned:$total_to_scan";
		file_put_contents( NSCAN_TMP_SNAPSHOT, serialize( $snapshot ) );
		file_put_contents( NSCAN_FILES2CHECK, serialize( $files2check ) );
		nscan_log_info( sprintf(
			__('Scanned files: %s/%s', 'ninjascanner'),
			number_format_i18n( $total_scanned ),
			number_format_i18n( $total_to_scan )
		) );
		return;
	}

	if (! empty( $snapshot['infected_files'] ) ) {
		nscan_log_warn( sprintf(
			__('Total potentially unsafe files: %s', 'ninjascanner'),
			count( $snapshot['infected_files'] )
		));
	} else {
		nscan_log_info( sprintf(
			__('No suspicious file detected (%s files checked)', 'ninjascanner'),
			number_format_i18n( $total_scanned )
		) );
	}

ANTIMALWARESAVE:
	file_put_contents( NSCAN_TMP_SNAPSHOT, serialize( $snapshot ) );

	nscan_set_lock_status(
		++$lock_status['current_step'],
		'success',
		$message
	);
}

// =====================================================================
// Download built-in  signatures list, or used the cached version
// if not older than one hour:

function nscan_download_signatures( $lock_status ) {

	global $snapshot, $nscan_steps;
	$nscan_options = get_option( 'nscan_options' );

	nscan_log_debug( __('Checking built-in signatures list', 'ninjascanner') );

	// Check it we have a cached version:
	if ( file_exists( NSCAN_SIGNATURES ) ) {
		if ( time() - filemtime( NSCAN_SIGNATURES ) < 600 ) {
			// Use it:
			nscan_log_debug( __('Using local copy', 'ninjascanner') );
			return;
		} else {
			// Too old, delete it:
			unlink( NSCAN_SIGNATURES );
			nscan_log_debug( __('Local copy is too old, deleting it', 'ninjascanner') );
		}
	}

	// Download the latest available version:
	nscan_log_debug( __('Downloading the latest version', 'ninjascanner') );
	global $wp_version;

	// Prepare the POST request:
	$data = array();
	$request_string = array(
		'body' => array(
			'action'	=> 'signatures',
			's' => 1,
			'cache_id' => sha1( home_url() )
		),
		'user-agent' => 'Mozilla/5.0 (compatible; NinjaScanner/'.
							NSCAN_VERSION ."; WordPress/{$wp_version})",
		'timeout' => NSCAN_CURL_TIMEOUT,
		'httpversion' => '1.1' ,
		'sslverify' => true
	);
	if ( isset( $nscan_options['key'] ) ) {
		// Premium users only:
		$request_string['body']['key'] = $nscan_options['key'];
		$request_string['body']['host'] = @strtolower( $_SERVER['HTTP_HOST'] );
	}

	// POST the request:
	$res = wp_remote_post( NSCAN_SIGNATURES_URL, $request_string);

	if (! is_wp_error($res) ) {
		if ( $res['response']['code'] == 200 ) {
			// Fetch the array:
			$data = json_decode( $res['body'], true );

			if (! empty( $data['exp'] ) ) {
				$nscan_options['exp'] = $data['exp'];
				update_option( 'nscan_options', $nscan_options );
			}

			// Make sure we have some signatures (a sig starts with '{', e.g., '{HEX}xxxxx'):
			if ( empty( $data['sig'] ) || $data['sig'][0] != '{' ) {
				if (! isset( $data['err'] ) ) { $data['err'] = 0; }
				$err = sprintf(
					__('The signatures list is either corrupted or empty. Try again later (error %s)', 'ninjascanner'),
					(int) $data['err']
				);
				nscan_log_warn( $err );
				$snapshot['step_error'][ $nscan_steps[ $lock_status['current_step'] ] ] = $err;
				return false;
			}

			// Verify the digital signature:
			if ( function_exists( 'openssl_pkey_get_public') && function_exists( 'openssl_verify' ) ) {
				nscan_log_debug( __('Verifying digital signature with public key', 'ninjascanner') );
				$public_key = rtrim( file_get_contents( __DIR__ .'/sign.pub' ) );
				$pubkeyid = openssl_pkey_get_public( $public_key );
				$verify = openssl_verify( trim( $data['sig'] ), base64_decode( $data['s'] ), $pubkeyid, OPENSSL_ALGO_SHA256);
				if ( $verify != 1 ) {
					$err = __('The digital signature is not correct. Aborting update, rules may have been tampered with.', 'ninjascanner');
					nscan_log_warn( $err );
					$snapshot['step_error'][ $nscan_steps[ $lock_status['current_step'] ] ] = $err;
					return false;
				}
			}

			// Save the signatures to the cache folder:
			file_put_contents( NSCAN_SIGNATURES, $data['sig'] );
			return true;

		} else {
			// HTTP error:
			$err = sprintf(
				__('HTTP Error %s. Cannot download signatures list. Try again later', 'ninjascanner'),
				(int)$res['response']['code']
			);
			nscan_log_warn( $err );
			$snapshot['step_error'][ $nscan_steps[ $lock_status['current_step'] ] ] = $err;
			return false;
		}
	}

	// Unknown error:
	$err = sprintf(
		__('%s. Cannot download built-in signatures list. Try again later', 'ninjascanner'),
		$res->get_error_message()
	);
	nscan_log_error( $err );
	$snapshot['step_error'][ $nscan_steps[ $lock_status['current_step'] ] ] = $err;
	return false;
}

// =====================================================================
// Read and verify the signatures list (built-in + user-defined),
// and return them as an array.

function nscan_verify_signatures( $file ) {

	// File must exists:
	if (! file_exists( $file ) ) {
		nscan_log_error( sprintf(
			__('Cannot find %s, skipping it', 'ninjascanner'), $file
		));
		return false;
	}

	$fh = fopen( $file, 'r' );
	if (! $fh ) {
		nscan_log_error( sprintf(
			__('Cannot open/read %s, skipping it', 'ninjascanner'), $file
		));
		return false;
	}

	nscan_log_debug( sprintf(
		__('Verifying signatures', 'ninjascanner'), $file
	));

	$tmp_signatures = array();
	$signatures = array();
	while (! feof( $fh ) ) {
		$line = fgets( $fh );
		$tmp_signatures = explode ( ':', rtrim( $line ) );
		unset($line);
		// Make sure we have what we are looking for:
		if (! empty( $tmp_signatures[3] ) && preg_match( '/^{[HR]EX\d?}/', $tmp_signatures[0] ) ) {
			// Decode hex-encoded signatures:
			if ( $res = nscan_hex2str( $tmp_signatures[3], $tmp_signatures[0] ) ) {
				$signatures[$tmp_signatures[0]] = $res;
			}
		}
		unset($tmp_signatures);
	}
	fclose( $fh );

	if (! empty( $signatures ) ) {
		nscan_log_debug( sprintf(
			__('Verified signatures: %s', 'ninjascanner'), count( $signatures )
		));
		return $signatures;
	}

	// Error:
	nscan_log_warn( __('No valid signatures found in that file, skipping it.', 'ninjascanner') );
	return false;
}

// =====================================================================
// Decode and test the hex-encoded signatures, including user-defined
// signatures. Signatures with a syntax error are ignored.

function nscan_hex2str( $hex, $type ) {

    $str = '';
    for ( $i = 0; $i < strlen( $hex ); $i += 2 ) {
		 $str .= chr( hexdec( substr( $hex, $i, 2 ) ) );
	 }
	 if ( preg_match( '/^{REX/', $type ) ) {
		$str = str_replace( '`', '\x60', $str );
		// Check regex validity:
		if ( preg_match("`$str`", 'foobar') === FALSE ) {
			nscan_log_error( sprintf(
				__('REX signature syntax error, skipping it: %s', 'ninjascanner'), $type
			));
			return false;
		}
	 } elseif ( preg_match( '/^{HEX/', $type ) ) {
		// Check signature validity (hex numbers only):
		if ( preg_match( '`[^a-f0-9]`i', $hex ) ) {
			nscan_log_error( sprintf(
				__('HEX signature syntax error, skipping it: %s', 'ninjascanner'), $type
			));
			return false;
		}
	}
	// OK:
	return $str;
}

// =====================================================================
// Scan for binary files (MZ/PE/NE and ELF formats)

function nscan_check_binaries( $lock_status ) {

	global $snapshot, $wp_version;

	$nscan_options = get_option( 'nscan_options' );

	if ( $nscan_options['scan_warnbinary'] ) {
		$message = __('Searching for binary files', 'ninjascanner' );
		nscan_log_info( $message );
		nscan_set_lock_status(
			$lock_status['current_step'],
			'success',
			$message
		);

		$files = array();
		$files = nscan_apply_exclusion( $snapshot['abspath'], 0, 0 );

		foreach( $files as $file => $arr ) {
			$data = file_get_contents( $file, false, null, 0, 4 );
			// We only look for ELF, PE/NE/MZ headers:
			if (preg_match('`^(?:\x7F\x45\x4C\x46|\x4D\x5A)`', $data) ) {
				$snapshot['core_binary'][$file] = 1;
			}
		}

		if (! empty( $snapshot['core_binary'] ) ) {
			nscan_log_warn( sprintf(
				__('Executable files found: %s', 'ninjascanner'),
				number_format_i18n( count( $snapshot['core_binary'] ) )
			));
		} else {
			nscan_log_info( __('No binary file found', 'ninjascanner' ));
		}

	} else {
		$message = __('Skipping binary files scan', 'ninjascanner');
		nscan_log_info( $message );
		$snapshot['skip']['scan_warnbinary'] = 1; // useless ?
	}

	// Save snapshot
	file_put_contents( NSCAN_TMP_SNAPSHOT, serialize( $snapshot ) );

	nscan_set_lock_status(
		++$lock_status['current_step'],
		'success',
		$message
	);
}

// =====================================================================
// Various tests (Note: tests can be disabled in the wp-config.php
// by using their corresponding constant).

function nscan_various_checks( $lock_status ) {

	global $snapshot;

	$nscan_options = get_option( 'nscan_options' );

	$message = __('Performing various checks', 'ninjascanner' );

	nscan_log_info( $message );
	nscan_set_lock_status(
		$lock_status['current_step'],
		'success',
		$message
	);

	if (! defined('NS_SKIP_GHOSTADMIN' ) ) {
		global $wpdb;
		$user_1 = $wpdb->get_results(
			"SELECT {$wpdb->base_prefix}users.ID,{$wpdb->base_prefix}users.user_login,{$wpdb->base_prefix}users.user_pass,{$wpdb->base_prefix}users.user_nicename,{$wpdb->base_prefix}users.user_email,{$wpdb->base_prefix}users.user_registered,{$wpdb->base_prefix}users.display_name
			FROM {$wpdb->base_prefix}users
			INNER JOIN {$wpdb->base_prefix}usermeta
			ON ( {$wpdb->base_prefix}users.ID = {$wpdb->base_prefix}usermeta.user_id )
			WHERE 1=1
			AND ( ( ( {$wpdb->base_prefix}usermeta.meta_key = '{$wpdb->prefix}capabilities'
			AND {$wpdb->base_prefix}usermeta.meta_value LIKE '%\"administrator\"%' ) ) )"
		);
		$user_2 = get_users(
			array( 'role' => 'administrator',
				'fields' => array(
					'ID', 'user_login', 'user_nicename',
					'user_email', 'user_registered', 'display_name'
				)
			)
		);
		if ( count( $user_1) > count( $user_2 ) ) {
			foreach( $user_1 as $num => $user ) {
				if ( isset( $user_2[$num]->ID ) ) {
					unset( $user_1[$num] );
					continue;
				}
			}
			$snapshot['various']['ghost_admin'] = json_encode( $user_1 );
			nscan_log_warn( sprintf(
				_n('Found %s ghost admin user',
					'Found %s ghost admin users',
					count( $user_1 ), 'ninjascanner'
				),
				count( $user_1 )
			));
		}
	}

	if (! defined('NS_SKIP_SSHKEY' ) ) {
		if ( @file_exists( $key = dirname( @$_SERVER['DOCUMENT_ROOT']) .'/.ssh/authorized_keys') ||
			@file_exists( $key = @$_SERVER['DOCUMENT_ROOT'] .'/.ssh/authorized_keys') ) {

			$snapshot['various']['ssh_key'][$key] = 1;
		}
		// For backward compatibility, authorized_keys2 can still be used although deprecated since 2001:
		if ( @file_exists( $key = dirname( @$_SERVER['DOCUMENT_ROOT']) .'/.ssh/authorized_keys2') ||
			@file_exists( $key = @$_SERVER['DOCUMENT_ROOT'] .'/.ssh/authorized_keys2') ) {

			$snapshot['various']['ssh_key'][$key] = 1;
		}
		if (! empty( $snapshot['various']['ssh_key'] ) ) {
			nscan_log_warn( sprintf(
				_n('Found %s SSH key in user home folder',
					'Found %s SSH keys in user home folder',
					count( $snapshot['various']['ssh_key'] ), 'ninjascanner'
				),
				count( $snapshot['various']['ssh_key'] )
			));
		}
	}

	if (! defined('NS_SKIP_WPREGISTRATION' ) ) {
		$default_role = get_option( 'default_role' );
		$users_can_register = get_option( 'users_can_register' );
		if ( $default_role == 'administrator' ) {
			if (! empty( $users_can_register ) ) {
				// Critical
				$snapshot['various']['membership'] = 2;
				nscan_log_warn( __('All New Registered users have administrator role', 'ninjascanner') );
			} else {
				// Important
				$snapshot['various']['membership'] = 1;
				nscan_log_warn( __('New User Default Role is set to "administrator"', 'ninjascanner') );
			}
		}
	}

	if (! defined('NS_SKIP_WPUSERROLES' ) ) {
		$admin_only_cap = array(
			'activate_plugins', 'create_users', 'delete_plugins', 'delete_themes',
			'delete_users', 'edit_files', 'edit_plugins', 'edit_theme_options',
			'edit_themes', 'edit_users', 'export', 'import', 'install_plugins',
			'install_themes', 'list_users', 'manage_options', 'promote_users',
			'remove_users', 'switch_themes', 'update_core', 'update_plugins',
			'update_themes', 'edit_dashboard', 'customize',	'delete_site'
		);
		$exclusion_list = array(
			'shop_manager' => array(
				'slug'	=> WP_PLUGIN_DIR .'/woocommerce/woocommerce.php',
				'caps'	=>	array( 'edit_users', 'export', 'import', 'list_users',
								'edit_theme_options' )
			)
		);
		include_once ABSPATH .'wp-admin/includes/plugin.php';

		// Fetch user_roles:
		global $wpdb;
		$user_roles = get_option("{$wpdb->base_prefix}user_roles");

		foreach ( $user_roles as $user => $cap ) {
			if ( $user != 'administrator' ) {
				foreach( $cap['capabilities'] as $k => $v ) {
					if (! empty( $v ) && in_array( $k, $admin_only_cap ) ) {

						// Check the exclusion list:
						if (! empty( $exclusion_list[$user] ) ) {
							if ( file_exists( $exclusion_list[$user]['slug'] ) ) {
								if ( in_array( $k, $exclusion_list[$user]['caps'] ) ) {
									// Don't warn about this one:
									continue;
								}
							}
						}

						$snapshot['various']['user_roles'][$user][] = $k;
					}
				}
				if (! empty( $snapshot['various']['user_roles'][$user] ) ) {
					nscan_log_warn( sprintf( __('Found user roles with administrator capabilities: %s', 'ninjascanner'), $user ) );
				}
			}
		}
	}

	// Save snapshot
	file_put_contents( NSCAN_TMP_SNAPSHOT, serialize( $snapshot ) );

	nscan_set_lock_status(
		++$lock_status['current_step'],
		'success',
		$message
	);
}

// =====================================================================
// EOF
