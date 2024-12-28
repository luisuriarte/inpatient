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
 
$quarantined_files = array(); 
// Get all quarantined files: 
$quarantined_files = nscan_get_quarantined(); 
 
$nscan_options = get_option( 'nscan_options' ); 
if ( empty( $nscan_options['show_path'] ) ) { 
	define( 'NSCAN_ABSOLUTE_PATH', true ); 
} else { 
	define( 'NSCAN_ABSOLUTE_PATH', false ); 
} 
 
if (! empty( $_POST['quarantined'] ) ) { 
 
	// Check nonce: 
	if ( empty( $_POST['nscannonce'] ) || ! wp_verify_nonce($_POST['nscannonce'], 'nscan_quarantine') ) { 
		wp_nonce_ays('nscan_quarantine'); 
	} 
	// Delete selected files: 
	if (! empty( $_POST['delete'] ) ) { 
		$qres = nscan_quarantine_select( $quarantined_files, 'delete' ); 
 
	// Restore selected files: 
	} elseif (! empty( $_POST['restore'] ) ) { 
		$qres = nscan_quarantine_select( $quarantined_files, 'restore' ); 
	} 
	// Update quarantined files list: 
	$quarantined_files = nscan_get_quarantined(); 
 
	if ( isset( $qres ) ) { 
		echo '<div class="notice-success notice is-dismissible"><p>' . __('Changes have been applied.', 'ninjascanner') . '</p></div>'; 
	} 
} 
 
if ( defined('NSCAN_TEXTAREA_HEIGHT') ) { 
	$th = (int) NSCAN_TEXTAREA_HEIGHT; 
} else { 
	$th = '450'; 
} 
 
echo nscan_display_tabs( 6 ); 
?> 
<form method="post"> 
<?php wp_nonce_field('nscan_quarantine', 'nscannonce', 0); ?> 
<table class="form-table"> 
	<tr> 
		<td style="width:100%"> 
		<?php 
		if (! empty( $quarantined_files ) ) { 
		?> 
			<select multiple="multiple" id="qf" name="quarantined[]" style="max-width:100%;width:100%;height:<?php echo $th; ?>px;"> 
			<?php 
			foreach( $quarantined_files as $k => $v ) { 
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
			<p class="alignleft"><input class="button-secondary" type="submit" name="delete" value="<?php _e('Delete selected files', 'ninjascanner' )?>" onclick="return nscanjs_quarantine_form(1);" />&nbsp;&nbsp;&nbsp;&nbsp;<input class="button-secondary" type="submit" name="restore" value="<?php _e('Restore selected files', 'ninjascanner' )?>" onclick="return nscanjs_quarantine_form(2);" /></p> 
		<?php 
		} else { 
		?> 
			<textarea name="nscantxtlog" class="small-text code" style="width:100%;height:<?php echo $th; ?>px;" wrap="off" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"><?php	echo "\n\n > " . __("The quarantine folder is empty.", 'ninjascanner');	?></textarea> 
			<br /> 
			<p class="alignleft"><input class="button-secondary" disabled="disabled" type="button" value="<?php _e('Delete selected files', 'ninjascanner' )?>" />&nbsp;&nbsp;&nbsp;&nbsp;<input class="button-secondary" disabled="disabled" type="button" value="<?php _e('Restore selected files', 'ninjascanner' )?>" /></p> 
		<?php 
		} 
		?> 
		<p class="alignright description"><?php _e('Files will remain in the quarantine folder until you restore or delete them.', 'ninjascanner') ?></p> 
		</td> 
	</tr> 
</table> 
</form> 
<?php 
 
// ===================================================================== 
// Retrieve the list of quarantined files. 
 
function nscan_get_quarantined() { 
 
	$quarantined_files = array(); 
	if ( file_exists( NSCAN_QUARANTINE .'/quarantine.php' ) ) { 
		$quarantined_files = unserialize( file_get_contents( NSCAN_QUARANTINE .'/quarantine.php' ) ); 
		ksort( $quarantined_files ); 
	} 
 
	return $quarantined_files; 
 
} 
 
// ===================================================================== 
// Delete/restore selected files from the quarantine folder: 
 
function nscan_quarantine_select( $quarantined_files, $action ) { 
 
	foreach( $_POST['quarantined'] as $file ) { 
		if (! empty( $quarantined_files[$file] ) ) { 
 
			// Remove the file: 
			if ( file_exists( NSCAN_QUARANTINE ."/{$quarantined_files[$file]}" ) ) { 
				if ( $action == 'delete' ) { 
					// Delete it: 
					unlink( NSCAN_QUARANTINE ."/{$quarantined_files[$file]}" ); 
				} else { 
					// Restore it: 
					rename( NSCAN_QUARANTINE ."/{$quarantined_files[$file]}", $file ); 
				} 
			} 
 
			// Remove it from the quarantined files list: 
			unset( $quarantined_files[$file] ); 
		} 
	} 
 
	// Save the list of quarantined files, or delete the file if empty: 
	if ( empty( $quarantined_files ) ) { 
		unlink( NSCAN_QUARANTINE .'/quarantine.php' ); 
	} else { 
		file_put_contents( NSCAN_QUARANTINE .'/quarantine.php', serialize( $quarantined_files ) ); 
	} 
 
	return true; 
 
} 
 
// ===================================================================== 
// EOF 
