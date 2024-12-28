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
 
// Don't load anything if the scanning process isn't running or 
// if the user disabled the "Display the status of the running 
// scan in the Toolbar" option: 
$nscan_options = get_option( 'nscan_options' ); 
 
$lock_status = json_decode( nscan_is_scan_running(), true ); 
if ( $lock_status['status'] != 'success' || empty( $nscan_options['scan_toolbarintegration'] ) ) { 
	return; 
} 
 
// AJAX security nonce: 
$nscan_key = wp_create_nonce( 'nscan_on_demand_nonce' ); 
 
// Add a "Summary" page link to the status span: 
if ( is_multisite() ) {	$net = 'network/'; } else { $net = '';	} 
$summary_page = get_admin_url( null, "{$net}admin.php?page=NinjaScanner&nscantab=summary" ); 
?> 
<script type="text/javascript" > 
	var nscan_interval = 0; 
	var nscan_key = <?php echo "'$nscan_key'" ?>; 
	var nscan_milliseconds = <?php echo (int) NSCAN_MILLISECONDS ?>; 
 
	// Display scan status in the Toolbar: 
	jQuery(document).ready(function($) { 
		jQuery('#wp-admin-bar-root-default').hide().prepend('<li id="wp-admin-bar-nscan_status"><a class="ab-item" href="<?php echo $summary_page ?>"><span id="nscan-status-span" style="background-color:orange;padding:4px;border-radius:2px;color:white;box-shadow:1px 1px 2px #7F0000 inset;">&nbsp;<?php _e('Scan in progress', 'ninjascanner') ?>...&nbsp;</span></a></li>').fadeIn(800); 
		nscan_interval = setInterval( nscanjs_is_running_all.bind( null, nscan_key ), nscan_milliseconds ); 
		function nscanjs_is_running_all( nscan_key ) { 
			var data = { 
				'action': 'nscan_check_status', 
				'nscan_key': nscan_key 
			}; 
			jQuery.ajax( { 
				type: 'POST', 
				url: ajaxurl, 
				data: data, 
				dataType: 'json', 
				success: function( response ) { 
					if (typeof response === 'undefined') { 
						if ( nscan_interval != 0 ) { 
							clearInterval( nscan_interval ); 
						} 
						if ( $('#wp-admin-bar-nscan_status').length ) { 
							$('#wp-admin-bar-nscan_status').html( '<a class="ab-item" href="<?php echo $summary_page ?>"><span id="nscan-status-span-pulse" style="background-color:#FF0000;padding:4px;border-radius:2px;color:white;box-shadow:1px 1px 2px #7F0000 inset;">&nbsp;<?php echo esc_js( __('Scan error.', 'ninjascanner' ) ) ?>&nbsp;</span></a>' ); 
						} 
					} else if ( response.status != 'success' ) { 
						if ( nscan_interval != 0 ) { 
							clearInterval( nscan_interval ); 
						} 
						if ( $('#wp-admin-bar-nscan_status').length ) { 
							$('#wp-admin-bar-nscan_status').html( '<a class="ab-item" href="<?php echo $summary_page ?>"><span id="nscan-status-span-pulse" style="background-color:#13AA13;padding:4px;border-radius:2px;color:white;box-shadow:1px 1px 2px #7F0000 inset;">&nbsp;<?php echo esc_js( __('Scan terminated', 'ninjascanner' ) ) ?>&nbsp;</span></a>' ); 
						} 
					} 
				} 
			}); 
		} 
	}); 
</script> 
<?php 
// ===================================================================== 
// EOF 
