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

if (! defined('ABSPATH') ) {
	die('Forbidden');
}

// Make sure it's not a directory
nscan_is_directory( $file );

// Make sure the file is not empty
$filesize = filesize( $file );
if (! $filesize ) {
	wp_die( sprintf(
		esc_html__('File is empty.', 'ninjascanner'),
		htmlspecialchars( $file )
	) );
}

if ( ( $file_content = file_get_contents( $file ) ) === false ) {
	wp_die( sprintf(
		esc_html__('Cannot open file: %s', 'ninjascanner'),
		htmlspecialchars( $file )
	) );
}
$extension = pathinfo( $file, PATHINFO_EXTENSION );
$nscan_options = get_option('nscan_options');

// Make sure it's not a binary file:
nscan_is_binary( $file_content );

if (! empty( $_GET['signature'] ) ) {
	// Highlight suspicious code:
	$sig = array();
	$sig = nscan_find_signature( $_GET['signature'], $nscan_options );
	if (! empty( $sig['type'] ) && ! empty( $sig['signature'] ) ) {
		$file_content = nscan_highlight_content(
			$file_content,
			$sig['signature'],
			$sig['type']
		);
	}
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<style>body{background:#F7F7F7;font-family:Arial;font-size:14px;font-weight:400;color:#312A2A}table{height:100%;border:1px solid #BFBFBF}.nscanview{width:100%;border-collapse:collapse;border-spacing:0;empty-cells:show}.nscanview thead th{text-align:left;border-bottom:1px solid #BFBFBF;background:#ECECEC;color:#7F7F7F;padding:4px;border-bottom:1px solid #BFBFBF}h3{color:#7F7F7F;}</style>
	<?php
	if (! empty( $nscan_options['highlight'] ) && $extension != 'php') {
	?>
		<link href="<?php echo plugins_url() ?>/ninjascanner/static/vendor/prism/prism.css" rel="stylesheet" type="text/css">
	<?php
	}
	?>
	<script src="<?php echo plugins_url() ?>/ninjascanner/static/ninjascanner.js"></script>
</head>
<body>
	<table class="nscanview" style="height:530px">
	<thead>
		<tr style="height:30px">
			<th colspan="2" style="text-align:center;"><?php printf( esc_html('Viewing: %s', 'ninjascanner'), esc_html( $file ) ) ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>
				<center>
					<?php
					if (! empty( $_GET['signature'] ) ) {
					?>
						<h3><?php printf( esc_html__('Suspicious code is %shighlighted%s.', 'ninjascanner' ), '<span style="background-color:yellow">', '</span>' ) ?></h3>
					<?php
					}
					?>
					<pre style="width:90vw;height:80vh;background-color:white;resize:both;padding:3px;font-size:11px;background-color:#fbfbfb;border:1px solid #ccc;border-radius:3px;overflow:auto;white-space:pre-wrap;text-align:left"><code id="nscan-highlight" class="language-php" style="white-space:pre-wrap;font-size:14px"><?php
					if ( $extension == 'php' && ! empty( $nscan_options['highlight'] ) ) {
						highlight_string( $file_content );
					} else {
						echo htmlspecialchars( $file_content, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE );
					}
					?></code></pre>
				</center>
			</td>
		</tr>
	</tbody>
	</table>
	<?php
	if (! empty( $nscan_options['highlight'] ) && $extension != 'php') {
	?>
		<script src="<?php echo plugins_url() ?>/ninjascanner/static/vendor/prism/prism.js"></script>
	<?php
	}
	?>
	<h3 style="text-align:center;">NinjaScanner &copy; <?php echo date('Y') ?> <a href="https://nintechnet.com/" target="_blank" title="The Ninja Technologies Network">NinTechNet</a></h3>
	<?php
	// Highlight suspicious code:
	if (! empty( $_GET['signature'] ) ) {
	?>
	<script>window.onload = nscanjs_highlight;</script>
	<?php
	}
	?>
</body>
</html><?php

// =====================================================================
// Find the corresponding antimalware signature.

function nscan_find_signature( $signature, $nscan_options ) {

	$sig = array();
	$lines = array();

	if ( file_exists( NSCAN_SIGNATURES ) ) {
		$lines = file( NSCAN_SIGNATURES );
		$signature = preg_quote( $signature );
		foreach( $lines as $line ) {
			if ( preg_match( '/^{(HEX|REX)\d*}'. $signature .':0:\*:(.+)$/', $line, $match ) ) {
				$sig['type'] = $match[1];
				$sig['signature'] = $match[2];
				break;
			}
		}
	}
	// Parse all potential custom signatures files
	if ( empty( $sig ) && ! empty( $nscan_options['scan_signatures'] ) ) {
		$files = json_decode( $nscan_options['scan_signatures'] );
		foreach( $files as $file ) {
			if ( $file == "lmd" ) { continue; }
			if ( file_exists( $file ) ) {
				$lines = file( $file );
				foreach( $lines as $line ) {
					if ( preg_match( '/^{(HEX|REX)\d*}'. $signature .':0:\*:(.+)$/', $line, $match ) ) {
						$sig['type'] = $match[1];
						$sig['signature'] = $match[2];
						break;
					}
				}
			}
		}
	}

	if ( empty( $sig ) ) {
		$sig['error'] = 1;
	}

	return $sig;
}

// =====================================================================
// Prepare the output to be highlighted.

function nscan_highlight_content( $file_content, $hex, $type ) {

    $str = '';
    for ( $i = 0; $i < strlen( $hex ); $i += 2 ) {
		 $str .= chr( hexdec( substr( $hex, $i, 2 ) ) );
	 }

	if ( $type == 'REX' ) {
		$str = str_replace( '`', '\x60', $str );
		// Some regex signatures can contain capturing parentheses,
		// hence we must do the search and replace thing without altering them:
		if ( preg_match_all( "`$str`", $file_content, $out ) ) {
			foreach( $out[0] as $k => $v ) {
				$file_content = str_replace( $v, "NSCANFOO{$v}NSCANBAR", $file_content );
			}
		}

	} else {
		$file_content = str_replace( $str, "NSCANFOO{$str}NSCANBAR", $file_content );
	}

	return $file_content;
}

// =====================================================================
// Check if the file is a binary file.

function nscan_is_binary( $file_content ) {

	if ( strpos( $file_content, "\x00" ) !== false ) {
		wp_die( esc_html__('This is a binary file and it cannot be viewed.', 'ninjascanner') );
	}
}
// =====================================================================
// Check if it is a directory.

function nscan_is_directory( $file ) {

	if ( is_dir( $file ) ) {
		wp_die( esc_html__('This is a directory and it cannot be viewed.', 'ninjascanner') );
	}
}

// =====================================================================
// EOF
