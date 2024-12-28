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
	wp_die( esc_html__('Error: Missing NSCAN_SNAPSHOT file.', 'ninjascanner' ) ); 
} 
 
$snapshot = unserialize( file_get_contents( NSCAN_SNAPSHOT ) ); 
 
if ( empty( $snapshot['abspath'] ) ) { 
	wp_die( esc_html__('Error: Snapshot is corrupted (abspath).', 'ninjascanner') ); 
} 
 
if ( empty( $snapshot['abspath'][$file]['type'] ) ) { 
	wp_die( esc_html__('Error: No match found (local file).', 'ninjascanner') ); 
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
 
	// Plugin: 
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
		wp_die( esc_html__('Error: No match found (file type).', 'ninjascanner') ); 
	} 
} 
 
// Read local file: 
$local_end = ''; 
$local_content = explode( "\n", file_get_contents( $file ) ); 
if ( ! empty( $local_content[0] ) ) { 
	if ( preg_match( "/\r$/", $local_content[0] ) ) { 
		$local_content = array_map('rtrim', $local_content); 
		$local_end = 'cr'; 
	} else { 
		$local_end = 'lf'; 
	} 
} 
 
// Turn remote content into an array: 
$remote_end = ''; 
$remote_content = explode( "\n", $tmp_remote_content ); 
if ( ! empty( $remote_content[0] ) ) { 
	if ( preg_match( "/\r$/", $remote_content[0] ) ) { 
		$remote_content = array_map('rtrim', $remote_content); 
		$remote_end = 'cr'; 
	} else { 
		$remote_end = 'lf'; 
	} 
} 
 
// Return diff: 
$diff_output = nscan_diff( $remote_content, $local_content ); 
// Display it: 
if ( empty( $diff_output ) ) { 
 
	if ( $local_end != $remote_end ) { 
		wp_die( esc_html_e('Those files are identical but have different line endings (LF vs CRLF): different line endings will always trigger a warning from the file integrity scanner because their checksums will not match.', 'ninjascanner' ) ); 
	} 
 
	wp_die( esc_html_e('No difference found: the local file matches the original one.', 'ninjascanner' ) ); 
} 
scan_compare_template( $diff_output, $file, $local_end, $remote_end ); 
 
exit; 
 
// ===================================================================== 
// Download the original file from the wordpress.org repo. 
// Applies to themes, plugins or core files only. 
// (This function exists also in file_restore.php). 
 
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
			wp_die( sprintf( 
				esc_html__('Error: Cannot download the original file from wordpress.org (%s).', 'ninjascanner'), 
				(int)$res['response']['code'] 
			) ); 
		} 
	} 
	// cURL error: 
	wp_die( sprintf( 
		esc_html__('Error: Cannot download the original file from wordpress.org (%s).', 'ninjascanner'), 
		htmlspecialchars( $res->get_error_message() ) 
	) ); 
 
} 
 
// ===================================================================== 
// Return the differences between two files. 
 
function nscan_diff( $remote, $local ) { 
 
	// Load the diff class: 
	require __DIR__ .'/vendor/diff/Diff.php'; 
 
	$diff = new nscanDiff( $remote, $local ); 
	require __DIR__ .'/vendor/diff/Diff/Renderer/Html/SideBySide.php'; 
	$renderer = new nscanDiff_Renderer_Html_SideBySide; 
 
	return $diff->Render( $renderer ); 
 
} 
 
// ===================================================================== 
// Display files diff in two windows side-by-side 
 
function scan_compare_template( $output, $file, $local_end, $remote_end ) { 
 
	?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en"> 
<head> 
	<style>.Diff tbody th,body{font-size:11px;font-weight:400}body{background:#F7F7F7;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;color:#312A2A}.Diff tbody th,.Diff thead th{background:#ECECEC;color:#7F7F7F}table{border:1px solid #BFBFBF}.toline{border-left:1px solid #BFBFBF}.Diff{width:100%;border-collapse:collapse;border-spacing:0;empty-cells:show}.Diff thead th{text-align:left;padding:4px;border-bottom:1px solid #BFBFBF}.Diff tbody th{text-align:right;width:3em;padding:1px 2px;border-right:1px solid #BFBFBF;vertical-align:top}.Diff td{padding:1px 2px;font-family:Consolas,monospace;font-size:14px}.CDelete td.Left,.CReplace .Left,.dsbs .CInsert td.Left{background:#FDD}.CDelete td.Right,.CReplace .Right,.dsbs .CInsert td.Right{background:#DFD}.Diff del,.Diff ins{text-decoration:none}.dsbs .CReplace ins{background:#9E9}.dsbs .CReplace del{background:#E99}.Diff .Skipped{background:#f7f7f7}pre{width:100%;overflow:auto}.nscanview{width:100%;border-collapse:collapse;border-spacing:0;empty-cells:show}.nscanview thead th{font-size:12px;border-bottom:1px solid #BFBFBF;background:#ECECEC;color:#7F7F7F;padding:4px;border-bottom:1px solid #BFBFBF}.nfw-notice{margin: 5px 0 15px;background: #fff;border: 1px solid #ccd0d4;border-left: 4px solid #fff;box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);padding: 1px 12px;font-size:14px;}.nfw-notice-orange{border-left-color: #ffb900;}</style> 
</head> 
<body> 
	<table class="nscanview"> 
		<thead> 
			<tr> 
				<th colspan="2" style="text-align:center;font-size:14px"><?php esc_html_e('File:', 'ninjascanner' )?> <?php echo htmlspecialchars( $file ) ?></th> 
			</tr> 
		</thead> 
	</table> 
	<?php 
	if ( $local_end != $remote_end ) { 
	?> 
	<br /> 
	<div class="nfw-notice nfw-notice-orange"><p><?php esc_html_e('Those files have different line endings (LF vs CRLF).', 'ninjascanner' )?></p></div> 
	<?php 
	} 
	?> 
	<br /> 
	<?php echo $output; ?> 
	<h3 style="text-align:center;">NinjaScanner &copy; <?php echo date('Y') ?> <a href="https://nintechnet.com/" target="_blank" title="The Ninja Technologies Network">NinTechNet</a></h3> 
</body> 
</html><?php 
 
} 
// ===================================================================== 
// EOF 
