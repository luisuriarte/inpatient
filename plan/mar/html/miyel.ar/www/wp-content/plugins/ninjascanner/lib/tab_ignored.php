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
 
$ignored_files = array(); 
// Get all ignored files: 
$ignored_files = nscan_get_ignored(); 
 
$nscan_options = get_option( 'nscan_options' ); 
if ( empty( $nscan_options['show_path'] ) ) { 
	define( 'NSCAN_ABSOLUTE_PATH', true ); 
} else { 
	define( 'NSCAN_ABSOLUTE_PATH', false ); 
} 
 
if (! empty( $_POST['ignored'] ) ) { 
 
	// Check nonce: 
	if ( empty( $_POST['nscannonce'] ) || ! wp_verify_nonce($_POST['nscannonce'], 'nscan_ignored') ) { 
		wp_nonce_ays('nscan_ignored'); 
	} 
	// Remove from ingored list: 
	if (! empty( $_POST['remove'] ) ) { 
		$qres = nscan_remove_ignored( $ignored_files ); 
	} 
	// Update ignored files list: 
	$ignored_files = nscan_get_ignored(); 
 
	if ( isset( $qres ) ) { 
		echo '<div class="notice-success notice is-dismissible"><p>' . __('Changes have been applied.', 'ninjascanner') . '</p></div>'; 
	} 
} 
 
if ( defined('NSCAN_TEXTAREA_HEIGHT') ) { 
	$th = (int) NSCAN_TEXTAREA_HEIGHT; 
} else { 
	$th = '450'; 
} 
 
echo nscan_display_tabs( 7 ); 
?> 
<form method="post"> 
<?php wp_nonce_field('nscan_ignored', 'nscannonce', 0); ?> 
<table class="form-table"> 
	<tr> 
		<td width="100%"> 
		<?php 
		if (! empty( $ignored_files ) ) { 
		?> 
			<select multiple="multiple" id="if" name="ignored[]" style="max-width:100%;width:100%;height:<?php echo $th; ?>px;"> 
			<?php 
			foreach( $ignored_files as $k => $v ) { 
				if (! defined( 'NSCAN_ABSOLUTE_PATH' ) || NSCAN_ABSOLUTE_PATH === false ) { 
					$display_name = str_replace( ABSPATH, '', $k ); 
				} else { 
					$display_name = $k; 
				} 
				echo "<option value='". htmlspecialchars( $k ) ."'>". htmlspecialchars( $display_name ) ."</option>\n"; 
			} 
			?> 
			</select> 
			<br /> 
			<p class="alignleft"><input class="button-secondary" type="submit" name="remove" value="<?php _e('Remove from the ignored files list', 'ninjascanner' )?>" onclick="return nscanjs_remove_ignored();" /></p> 
		<?php 
		} else { 
		?> 
			<textarea name="nscantxtlog" class="small-text code" style="width:100%;height:<?php echo $th; ?>px;" wrap="off" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"><?php	echo "\n\n > " . __("The ignored files list is empty.", 'ninjascanner');	?></textarea> 
			<br /> 
			<p class="alignleft"><input class="button-secondary" disabled="disabled" type="button" value="<?php _e('Remove from the ignored files list', 'ninjascanner' )?>" /></p> 
		<?php 
		} 
		?> 
		<p class="alignright description"><?php _e('Files and folders will remain in the ignored list until they are modified or manually removed from the list.', 'ninjascanner') ?></p> 
		</td> 
	</tr> 
</table> 
</form> 
<?php 
 
// ===================================================================== 
// Retrieve the list of ignored files. 
 
function nscan_get_ignored() { 
 
	$ignored_list = array(); 
	if ( file_exists( NSCAN_IGNORED_LOG ) ) { 
		$ignored_list = unserialize( file_get_contents( NSCAN_IGNORED_LOG ) ); 
	} 
	return $ignored_list; 
} 
 
// ===================================================================== 
// Remove the selected file(s) from the ignored list: 
 
function nscan_remove_ignored( $ignored_files ) { 
 
	foreach( $_POST['ignored'] as $file ) { 
		if (! empty( $file ) && isset( $ignored_files[$file] ) ) { 
			// Remove it from the ignored files list: 
			unset( $ignored_files[$file] ); 
		} 
	} 
 
	// Save the list of ignored files, or delete the file if empty: 
	if ( empty( $ignored_files ) ) { 
		unlink( NSCAN_IGNORED_LOG ); 
	} else { 
		file_put_contents( NSCAN_IGNORED_LOG, serialize( $ignored_files ) ); 
	} 
 
	return true; 
 
} 
 
// ===================================================================== 
// EOF 
