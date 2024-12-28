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
 +=====================================================================+// sa+i18n 
*/ 
 
if (! defined( 'ABSPATH' ) ) { die( 'Forbidden' ); } 
 
$nscan_options = get_option( 'nscan_options' ); 
 
echo nscan_display_tabs( 4 ); 
 
if (! empty( $_POST ) ) { 
 
	// Check security nonce: 
	if ( empty( $_POST['nscannonce'] ) || ! wp_verify_nonce($_POST['nscannonce'], 'nscan_license') ) { 
		wp_nonce_ays('nscan_license'); 
	} 
 
	$res = array(); 
	// Verify license key: 
	if (! empty( $_POST['check_license'] ) ) { 
		$res = nscan_check_license( $nscan_options ); 
 
	} elseif (! empty( $_POST['save_license'] ) && ! empty( $_POST['key'] ) ) { 
		$res = nscan_save_license( $nscan_options, $_POST['key'] ); 
	} 
 
	if (! empty( $res['nscan_err'] ) ) { 
		echo '<div class="notice-warning notice is-dismissible"><p>' . $res['nscan_err'] . '</p></div>'; 
	} else { 
		echo '<div class="notice-success notice is-dismissible"><p>' . $res['nscan_msg'] . '</p></div>'; 
	} 
	// Refresh options: 
	$nscan_options = get_option( 'nscan_options' ); 
} 
 
 
 
if (! empty( $nscan_options['key'] ) ) { 
	premium_license_tab( $nscan_options ); 
} else { 
	premium_tab(); 
} 
 
// ===================================================================== 
 
function premium_tab() { 
 
?> 
	<p><?php printf( __('<a href="%s">Get Premium</a> and enjoy the following benefits:', 'ninjascanner'), 'https://nintechnet.com/ninjascanner/?pricing' ) ?></p> 
 
	<h3><?php _e('Scheduled scan', 'ninjascanner') ?></h3> 
	<p><?php _e("Don't leave your blog at risk. With the scheduled scan option, NinjaScanner will run automatically hourly, twice daily or daily.", 'ninjascanner') ?></p> 
 
	<h3><?php _e('WP-CLI integration', 'ninjascanner') ?></h3> 
	<p><?php 
	_e('Do you own several blogs and prefer to manage them from the command line?', 'ninjascanner' ); 
	echo ' '; 
	_e('NinjaScanner can nicely integrate with WP-CLI, using the <code>ninjascanner</code> command.', 'ninjascanner' ); 
	echo ' '; 
	_e('You can use it to start or stop a scanning process, view its status, its report or log and enter your Premium license from your favourite terminal, without having to log in to the WordPress Admin Dashboard', 'ninjascanner' ); 
	?> 
	</p> 
	<p> 
		<textarea class="small-text code" style="width:100%;height:230px;color:#00FF00;background-color:#23282D;font-size:13px" wrap="off"> 
$ wp ninjascanner help 
 
NinjaScanner v<?php echo NSCAN_VERSION ?> (c)<?php date('Y') ?> NinTechNet ~ https://nintechnet.com 
<?php _e('Available commands:', 'ninjascanner'); echo "\n" ?> 
   wp ninjascanner help         <?php _e('Display this help screen', 'ninjascanner'); echo "\n" ?> 
   wp ninjascanner start        <?php _e('Start a scan', 'ninjascanner'); echo "\n" ?> 
   wp ninjascanner stop         <?php _e('Stop the scanning process', 'ninjascanner'); echo "\n" ?> 
   wp ninjascanner status       <?php _e('Show scan status', 'ninjascanner'); echo "\n" ?> 
   wp ninjascanner report       <?php _e('View the last scan report', 'ninjascanner'); echo "\n" ?> 
   wp ninjascanner log          <?php _e('View the debugging log', 'ninjascanner'); echo "\n" ?> 
   wp ninjascanner license      <?php _e('Enter your Premium license key', 'ninjascanner'); echo "\n" ?></textarea> 
	</p> 
 
	<br /> 
 
	<h3><?php _e('Dedicated Help Desk with Priority Support', 'ninjascanner') ?></h3> 
	<p><?php printf( __('Need help with NinjaScanner? Premium users have a <a href="%s">dedicated help desk</a>.', 'ninjascanner'), 'https://secure.nintechnet.com/login/?ns') ?></p> 
 
	<br /> 
 
	<h3><?php _e('Premium License', 'ninjascanner') ?></h3> 
	<p><?php _e('Already have a license key? Enter it below:', 'ninjascanner' ) ?></p> 
	<form method="post"> 
		<?php wp_nonce_field('nscan_license', 'nscannonce', 0); ?> 
		<input type="text" size="50" name="key" value="" required autocomplete="off" autocapitalize="none" />&nbsp;&nbsp;<input type="submit" class="button-primary" name="save_license" value="<?php _e('Save License', 'ninjascanner') ?>" /> 
	</form> 
	<br /> 
	<p><h3><?php 
		_e("Don't have a license yet?", 'ninjascanner' ); 
		echo ' <a href="https://nintechnet.com/ninjascanner/?pricing">'; 
		_e('Get Premium now!', 'ninjascanner' ); 
		echo '</a>'; 
	?></h3></p> 
	<br /> 
	<br /> 
 
<?php 
} 
 
// ===================================================================== 
 
function premium_license_tab( $nscan_options ) { 
 
	$renew = 0; 
 
	$lic_exp_warn = nscan_is_valid(); 
	if ( empty( $lic_exp_warn ) ) { 
		$exp = ''; 
	} else { 
		// Convert to current timezon format: 
		nscan_get_blogtimezone(); 
		$exp = ucfirst( date( 'F d, Y', strtotime( $nscan_options['exp'] ) ) ); 
	} 
 
	?> 
	<br /> 
	<form method="post"> 
	<?php 
	wp_nonce_field('nscan_license', 'nscannonce', 0); 
	?> 
	<h3><?php _e('Current License', 'ninjascanner') ?></h3> 
	<table class="form-table"> 
		<tr> 
			<th scope="row"><?php _e('License Number', 'ninjascanner') ?></th> 
			<td> 
			<?php 
			if (! $nscan_options['key'] ) { 
				$renew = 1; 
				?> 
				<span class="dashicons dashicons-dismiss" style="font-size:22px;color:red;"></span>&nbsp;&nbsp;<font style="color:red"><?php _e('No license found', 'ninjascanner') ?></font></td> 
				<?php 
			} else { 
				?> 
				&nbsp;<input type="text" name="key" value="<?php echo htmlspecialchars( $nscan_options['key'] ) ?>" class="large-text" readonly /> 
				<p><input class="button-secondary" type="submit" name="check_license" value="<?php _e('Check your license validity', 'ninjascanner') ?>" /></p> 
			</td> 
				<?php 
				} 
			?> 
		</tr> 
		<?php 
		if ( $nscan_options['key'] ) { 
		?> 
		<tr> 
			<th scope="row"><?php _e('Expiration date', 'ninjascanner') ?></th> 
			<td> 
			<?php 
			if (! $exp ) { 
				$renew = 1; 
				?> 
				<span class="dashicons dashicons-dismiss" style="font-size:22px;color:red;"></span> 
				<?php 
				$exp = '<span class="description"><font color="red">'. __('Your license does not seem to be valid.', 'ninjascanner') . '<br />'. __('Click on the "Check your license validity" button to attempt to fix this error.', 'ninjascanner') . '</span>'; 
			} elseif ( $lic_exp_warn > 1 ) { 
				$renew = 1; 
				?> 
				<span class="dashicons dashicons-warning" style="font-size:22px;color:orange;"></span> 
				<?php 
				$exp .= '&nbsp;&nbsp;<span class="description"><font color="red">'. __('Your license will expire soon!', 'ninjascanner') . '</font></span>'; 
			} elseif ( $lic_exp_warn < 0 ) { 
				$renew = 1; 
				?> 
				<span class="dashicons dashicons-dismiss" style="font-size:22px;color:red;"></span> 
				<?php 
				$exp = '<span class="description"><font color="red">'. __('Your license has expired.', 'ninjascanner') . '</font></span>'; 
			} else { 
				$renew = 0; 
				echo '&nbsp;'; 
			} 
			echo "&nbsp;{$exp}" ?> 
			</td> 
		</tr> 
		<?php 
		} 
	?> 
	</table> 
	</form> 
 
<?php 
	if (! empty( $renew ) ) { 
	?> 
	<br /> 
 
	<h3><?php _e('License renewal', 'ninjascanner') ?></h3> 
	<p><?php _e('Enter your license below:', 'ninjascanner' ) ?></p> 
	<form method="post"> 
		<?php wp_nonce_field('nscan_license', 'nscannonce', 0); ?> 
		<input type="text" size="50" name="key" value="" required autocomplete="off" autocapitalize="none" />&nbsp;&nbsp;<input type="submit" class="button-primary" name="save_license" value="<?php _e('Save License', 'ninjascanner') ?>" /> 
	</form> 
	<br /> 
	<p><h3><?php 
		_e("Don't have a license yet?", 'ninjascanner' ); 
		echo ' <a href="https://nintechnet.com/ninjascanner/">'; 
		_e('Click here to get one!', 'ninjascanner' ); 
		echo '</a>'; 
	?></h3></p> 
	<br /> 
	<br /> 
	<?php 
	} 
} 
 
// ===================================================================== 
// EOF 
