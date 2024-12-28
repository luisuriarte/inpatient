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
 
if (! file_exists( NSCAN_SNAPSHOT ) ) { 
	esc_html_e('Error: Missing NSCAN_SNAPSHOT file.', 'ninjascanner'); 
	wp_die(); 
} 
 
$snapshot = unserialize( file_get_contents( NSCAN_SNAPSHOT ) ); 
 
if ( empty( $snapshot['abspath'] ) ) { 
	esc_html_e('Error: Snapshot is corrupted (abspath).', 'ninjascanner'); 
	wp_die(); 
} 
 
if ( empty( $snapshot['abspath'][$file]['type'] ) ) { 
	esc_html_e('Error: No match found (local file).', 'ninjascanner'); 
	wp_die(); 
} 
 
// WordPress core file: 
if ( $snapshot['abspath'][$file]['type'] == 'core' ) { 
 
	$version = $snapshot['version']; 
	if (! empty( $snapshot['locale'] ) ) { 
		$locale = "-{$snapshot['locale']}"; 
	} else { 
		$locale = ''; 
	} 
 
	// Clean-up the path (order matters): 
	$rpath = str_replace( WP_PLUGIN_DIR, 'wp-content/plugins', $file ); 
	$rpath = str_replace( WP_CONTENT_DIR, 'wp-content', $rpath ); 
	$rpath = str_replace( ABSPATH, '', $rpath ); 
 
	// Check if we have a local copy of the installation package: 
	if ( file_exists( NSCAN_CACHEDIR ."/wordpress-{$version}{$locale}.zip" ) ) { 
		// Fetch it from the ZIP file: 
		$tmp_remote_content = nscan_read_zipped_file( 
			NSCAN_CACHEDIR ."/wordpress-{$version}{$locale}.zip", 
			"wordpress/{$rpath}" 
		); 
 
	// Download it from wordpress.org: 
	} else { 
		$url = sprintf( NSCAN_SVN_CORE, $version ); 
		$tmp_remote_content = nscan_download_original( "{$url}/{$rpath}" ); 
	} 
 
// Themes & plugins: 
} else { 
 
	// Plugins: 
	if ( $snapshot['abspath'][$file]['type'] == 'plugin' ) { 
 
		// Check if we have a local copy of the ZIP package: 
 
		// Clean-up path: 
		$rpath = str_replace( 
			WP_PLUGIN_DIR ."/{$snapshot['abspath'][$file]['slug']}/", 
			'', 
			$file 
		); 
 
		// Search in the cache folder (free open-source plugins): 
		if ( file_exists( NSCAN_CACHEDIR ."/plugin_{$snapshot['abspath'][$file]['slug']}.{$snapshot['abspath'][$file]['version']}.zip" ) ) { 
			// Fetch it from the ZIP file: 
			$tmp_remote_content = nscan_read_zipped_file( 
				NSCAN_CACHEDIR ."/plugin_{$snapshot['abspath'][$file]['slug']}.{$snapshot['abspath'][$file]['version']}.zip", 
				$snapshot['abspath'][$file]['slug'] .'/'. $rpath 
			); 
 
		// Or search in the user "/local" folder (premium plugins): 
		} elseif ( file_exists( NSCAN_LOCAL ."/{$snapshot['abspath'][$file]['slug']}.{$snapshot['abspath'][$file]['version']}.zip" ) ) { 
			// Fetch it from the ZIP file: 
			$tmp_remote_content = nscan_read_zipped_file( 
				NSCAN_LOCAL ."/{$snapshot['abspath'][$file]['slug']}.{$snapshot['abspath'][$file]['version']}.zip", 
				$snapshot['abspath'][$file]['slug'] .'/'. $rpath 
			); 
 
		// No local copy, try to download it from wordpress.org: 
		} else { 
			$url = sprintf( 
				NSCAN_SVN_PLUGINS, 
				$snapshot['abspath'][$file]['slug'], 
				$snapshot['abspath'][$file]['version'] 
			); 
			$tmp_remote_content = nscan_download_original( "{$url}/{$rpath}" ); 
		} 
 
	// Theme: 
	} elseif ( $snapshot['abspath'][$file]['type'] == 'theme' ) { 
 
		// Check if we have a local copy of the ZIP package: 
 
		// Clean-up path: 
		$rpath = str_replace( 
			WP_CONTENT_DIR ."/themes/{$snapshot['abspath'][$file]['slug']}/", 
			'', 
			$file 
		); 
 
		// Search in the cache folder (free open-source themes): 
		if ( file_exists( NSCAN_CACHEDIR ."/theme_{$snapshot['abspath'][$file]['slug']}.{$snapshot['abspath'][$file]['version']}.zip" ) ) { 
			// Fetch it from the ZIP file: 
			$tmp_remote_content = nscan_read_zipped_file( 
				NSCAN_CACHEDIR ."/theme_{$snapshot['abspath'][$file]['slug']}.{$snapshot['abspath'][$file]['version']}.zip", 
				$snapshot['abspath'][$file]['slug'] .'/'. $rpath 
			); 
 
		// Or search in the user "/local" folder (premium themes): 
		} elseif ( file_exists( NSCAN_LOCAL ."/{$snapshot['abspath'][$file]['slug']}.{$snapshot['abspath'][$file]['version']}.zip" ) ) { 
			// Fetch it from the ZIP file: 
			$tmp_remote_content = nscan_read_zipped_file( 
				NSCAN_LOCAL ."/{$snapshot['abspath'][$file]['slug']}.{$snapshot['abspath'][$file]['version']}.zip", 
				$snapshot['abspath'][$file]['slug'] .'/'. $rpath 
			); 
 
		// No local copy, try to download it from wordpress.org: 
		} else { 
			$url = sprintf( 
				NSCAN_SVN_THEMES, 
				$snapshot['abspath'][$file]['slug'], 
				$snapshot['abspath'][$file]['version'] 
			); 
			$tmp_remote_content = nscan_download_original( "{$url}/{$rpath}" ); 
		} 
 
	} else  { 
		esc_html_e('Error: No match found (file type).', 'ninjascanner'); 
		wp_die(); 
	} 
} 
 
// Make sure we have something: 
if ( empty( $tmp_remote_content ) ) { 
	esc_html_e('Error: Content is empty.', 'ninjascanner'); 
	wp_die(); 
} 
 
// Replace current file with new content: 
if (! is_writable( $file ) ) { 
	esc_html_e('Error: The destination folder is not writable.', 'ninjascanner'); 
	wp_die(); 
} 
$byte = file_put_contents( $file, $tmp_remote_content ); 
if ( $byte != strlen( $tmp_remote_content ) ) { 
	esc_html_e('Warning: The operation seems to have failed.', 'ninjascanner'); 
	wp_die(); 
} 
 
// Unmark the file from our snapshot: 
if ( $snapshot['abspath'][$file]['type'] == 'core' ) { 
	// Core: 
	unset( $snapshot['core_failed_checksum'][$file] ); 
} elseif ( $snapshot['abspath'][$file]['type'] == 'plugin' ) { 
	// Plugin: 
	$slug = $snapshot['abspath'][$file]['slug']; 
	unset( $snapshot['plugins'][$slug][$rpath] ); 
} else { 
	// Theme: 
	$slug = $snapshot['abspath'][$file]['slug']; 
	unset( $snapshot['themes'][$slug][$rpath] ); 
} 
// Save the new snapshot: 
file_put_contents( NSCAN_SNAPSHOT, serialize( $snapshot ) ); 
 
// ===================================================================== 
// Download the original file from the wordpress.org repo. 
// Applies to themes, plugins or core files only. 
// (This function exists also in file_compare.php). 
 
function nscan_download_original( $file ) { 
 
	global $wp_version; 
 
	$res = wp_remote_get( 
		$file, 
		array( 
			'timeout' => NSCAN_CURL_TIMEOUT, 
			'httpversion' => '1.1' , 
			'user-agent' => 'Mozilla/5.0 (compatible; NinjaScanner/'. 
									NSCAN_VERSION .'; WordPress/'. $wp_version . ')', 
			'sslverify' => true 
		) 
	); 
 
	if (! is_wp_error( $res ) ) { 
 
		if ( $res['response']['code'] == 200 ) { 
			// Return the file content: 
			return $res['body']; 
 
		} else { 
			// HTTP error: 
			esc_html_e('Error: Cannot download the original file from wordpress.org.', 'ninjascanner'); 
			wp_die(); 
		} 
	} 
	// cURL error: 
	esc_html_e('Error: Cannot download the original file from wordpress.org.', 'ninjascanner'); 
	wp_die(); 
 
} 
 
// ===================================================================== 
// EOF 
