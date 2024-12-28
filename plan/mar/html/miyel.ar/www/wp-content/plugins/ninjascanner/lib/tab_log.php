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

?>
<script>
var nscan_array = new Array();
<?php
$info = 0; $warn = 0; $error = 0; $debug = 0;
if ( file_exists( NSCAN_DEBUGLOG ) ) {
	// Get timezone:
	nscan_get_blogtimezone();
	$lines = array();
	$lines = file( NSCAN_DEBUGLOG, FILE_SKIP_EMPTY_LINES );
	$logline = '';
	$i = 0;
	$facility = array( 1 => 'INFO ', 2 => 'WARN ', 4 => 'ERROR', 8 => 'DEBUG' );
	foreach( $lines as $line ) {
		list( $date, $level, $string ) = explode( '~~', $line, 3 );
		$date = date( 'd-M-y H:i:s', $date );
		echo 'nscan_array[' . $i . '] = "' .
				rawurlencode( "$level~~$date {$facility[$level]} $string" ) . '";' . "\n";
		++$i;
		if ( $level == 1 ) {
			$info = 1;
		} else if ( $level == 2 ) {
			$warn = 1;
		} else if ( $level == 4 ) {
			$error = 1;
		} else {
			$debug = 1;
			// Don't display DEBUG (8) by default:
			continue;
		}
		$logline .= "$date {$facility[$level]} $string" ;
	}
}
?>
</script>
<?php

if ( defined('NSCAN_TEXTAREA_HEIGHT') ) {
	$th = (int) NSCAN_TEXTAREA_HEIGHT;
} else {
	$th = '450';
}

echo nscan_display_tabs( 3 );
?>
<form name="nscanlogform">
<table class="form-table">
	<tr>
		<td width="100%">
			<textarea name="nscantxtlog" class="large-text code" style="height:<?php echo $th; ?>px;" wrap="off" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"><?php
			if (! empty( $logline ) ) {
				echo esc_textarea( $logline );
				$disabled = '';
			} else {
				echo "\n\n > " . __("NinjaScanner's log is empty.", 'ninjascanner');
				$disabled = ' disabled="disabled"';
			}
			if (! $error ) {
				$error_checked = '';
			} else {
				$error_checked = ' checked="checked"';
			}
			if (! $warn ) {
				$warn_checked = '';
			} else {
				$warn_checked = ' checked="checked"';
			}
			?>
			</textarea>
			<p style="text-align:center">
				<label>
					<input type="checkbox" name="info" checked="checked"<?php disabled( $info, 0 ) ?> onClick="nscanjs_filter_log();"<?php echo $disabled ?> />
					<span style="display: inline-block"><?php esc_html_e('Info', 'ninjascanner') ?></span>&nbsp;
				</label>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<label>
					<input type="checkbox" name="warn"<?php echo $warn_checked ?><?php disabled( $warn, 0 ) ?> onClick="nscanjs_filter_log();"<?php echo $disabled ?> />
					<span style="display: inline-block"><?php esc_html_e('Warning', 'ninjascanner') ?></span>&nbsp;
				</label>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<label>
					<input type="checkbox" name="error"<?php echo $error_checked ?><?php disabled( $error, 0 ) ?> onClick="nscanjs_filter_log();"<?php echo $disabled ?> />
					<span style="display: inline-block"><?php esc_html_e('Error', 'ninjascanner') ?></span>&nbsp;
				</label>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<label>
					<input type="checkbox" name="debug"<?php disabled( $debug, 0 ) ?> onClick="nscanjs_filter_log();"<?php echo $disabled ?> />
					<span style="display: inline-block"><?php esc_html_e('Debug', 'ninjascanner') ?></span>&nbsp;
				</label>
			</p>
		</td>
	</tr>
</table>
</form>
<?php
// =====================================================================
// EOF
