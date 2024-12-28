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
 +=====================================================================+ sa+i18n
*/

if (! defined('ABSPATH') ) {
	die('Forbidden');
}

echo nscan_display_tabs( 5 );
$i18n = __('A lightweight, fast and powerful virus scanner for WordPress.', 'ninjascanner');
?>
<br />
<div class="card">
	<p style="text-align:center;font-size: 1.8em; font-weight: bold">NinjaScanner v<?php echo NSCAN_VERSION ?></p>
	<p style="text-align:center"><img src="<?php echo plugins_url() ?>/ninjascanner/static/logo_ns_100.png" /></p>
	<p style="text-align:center;font-size: 1.2em;"><font onContextMenu="nfw_eg();return false;">&copy;</font> 2018-<?php echo date('Y') ?> <a href="https://nintechnet.com/" target="_blank" title="The Ninja Technologies Network"><strong>NinTechNet</strong></a><br />The Ninja Technologies Network	</p>
	<br />
	<font style="font-size: 1.1em;">
	<ul style="list-style: disc;">
		<li><?php printf( __('<strong>Need more security?</strong> Check out <a href="%s">NinjaFirewall (WP Edition)</a>, our Web Application Firewall for WordPress. It will give your blog the highest level of protection it deserves, no less!', 'ninjascanner'), 'https://nintechnet.com/ninjafirewall/wp-edition/' ) ?> </li>
		<li><a href="https://wordpress.org/support/topic/ninjascanner-and-the-general-data-protection-regulation-gdpr/"><?php _e('GDPR Compliance', 'ninjascanner') ?></a></li>
		<li><?php _e('Stay informed about the latest vulnerabilities in WordPress plugins and themes:', 'ninjascanner') ?> <a href="https://twitter.com/nintechnet">https://twitter.com/nintechnet</a></li>
		<li><a href="https://wordpress.org/support/view/plugin-reviews/ninjascanner?rate=5#postform"><?php _e('Rate NinjaScanner on WordPress.org!', 'ninjascanner') ?></a> <img style="vertical-align:middle" src="<?php echo plugins_url() ?>/ninjascanner/static/rate.png" /></li>
	</ul>
	</font>
</div>
<?php
// =====================================================================
// EOF
