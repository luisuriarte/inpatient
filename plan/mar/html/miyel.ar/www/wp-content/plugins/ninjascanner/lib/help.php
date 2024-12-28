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
 
function nscan_help() { 
 
	// Contextual help: 
	if ( nscan_is_valid() < 1 ) { 
		$premium = ' <sup><font color="#D60404">'. __('Premium only', 'ninjascanner'). '</font></sup>'; 
	} else { 
		$premium = ''; 
	} 
 
	get_current_screen()->add_help_tab( array( 
		'id'        => 'nscan_basic_settings', 
		'title'     => __('Basic Settings', 'ninjascanner'), 
		'content'   => '<div style="height:400px;">' . 
 
		// Basic Settings 
 
		'<h3>'. __('Basic Settings', 'ninjascanner') .'</h3>'. 
 
		'<p><strong>'. __('Blog directory', 'ninjascanner') .'</strong>'. 
		'<br />'. 
		__('Displays the WordPress root folder (a.k.a ABSPATH) where the scan will take place. It cannot be changed.', 'ninjascanner') .'<br />'. __('You can select which folders you want to scan in that directory', 'ninjascanner') . 
		'</p>'. 
 
		'<p><strong>'. __('File size', 'ninjascanner') .'<sup style="color:red">*</sup></strong>'. 
		'<br />'. 
		__('Allows you to scan only files smaller than a certain size. To scan any file regardless of its size, set this value to zero.', 'ninjascanner'). 
		'</p>'. 
 
		'<p><strong>'. __('Ignore file extensions', 'ninjascanner') .'<sup style="color:red">*</sup></strong>'. 
		'<br />'. 
		__('Allows you to exclude files depending on their extension (case-insensitive).', 'ninjascanner'). 
		'</p>'. 
 
		'<sup style="color:red">*</sup>'. __('Those two options do not apply to the File Integrity Checker (see "Advanced Settings").', 'ninjascanner' ). 
 
		'<p><strong>'. __('Ignore files/folders', 'ninjascanner') .'</strong>'. 
		'<br />'. 
		__('Allows you to exclude specific files or folders. It can be full or partial case-sensitive string (<code>/foo/bar.php</code> or simply <code>foo</code>).', 'ninjascanner'). '</p>'. 
 
		'<ul>'. 
			'<li><strong>'. __('Apply the exclusion list to the file integrity checker (themes and plugins)', 'ninjascanner') .'</strong>: '. __('This option will apply the exclusion list to the file integrity checker when comparing plugin or theme files to their original package. It can be useful, for instance, if you have themes or plugins that create temporary or cached files inside their own installation folder, in order to exclude them from the file integrity checker.', 'ninjascanner') .'</li>'. 
		'</ul>'. 
 
		'<p><strong>'. __('Send the scan report to', 'ninjascanner') .'</strong>'. 
		'<br />'. 
		__('This feature is optional. You can send a copy of the scan report to one or more email addresses.', 'ninjascanner'). 
		'</p>'. 
 
		'<p><strong>'. __('Run a scheduled scan', 'ninjascanner') .'</strong>'. $premium. 
		'<br />'. 
		__('Allows you to run a scheduled scan hourly, twice daily or daily. The next scheduled scan date and time, if any, will be displayed in the "Summary" page.', 'ninjascanner'). 
		'</p>'. 
 
		'<p><strong>'. __('WP-CLI', 'ninjascanner') .'</strong>'. $premium. 
		'<br />'. 
		__('Enable WP-CLI integration. See the "WP-CLI" help menu for more details.', 'ninjascanner'). 
		'</p>'. 
 
		'</div>' 
	) ); 
 
	get_current_screen()->add_help_tab( array( 
		'id'        => 'nscan_advanced_settings', 
		'title'     => __('Advanced Settings', 'ninjascanner'), 
		'content'   => '<div style="height:400px;">' . 
 
		// Advanced Users Settings 
 
		'<h3>'. __('Advanced Users Settings', 'ninjascanner') .'</h3>'. 
 
		'<h4>'. __('File integrity checker', 'ninjascanner') .'</h4>'. 
		'<ul>'. 
			'<li><strong>'. __('Always verify NinjaScanner\'s files integrity before starting a scan.', 'ninjascanner') .'</strong>'. 
				'<br />'. 
				__('When this option is enabled, NinjaScanner will check if any of its files were tampered with, right before starting the scanning process.', 'ninjascanner') . 
			'</li>'. 
			'<li><strong>'. __('Compare WordPress core files to their original package.', 'ninjascanner') .'</strong>'. 
				'<br />'. 
				__('NinjaScanner will compare all core files from your installation to the original ones.', 'ninjascanner') . 
			'</li>'. 
			'<li><strong>'. __('Compare plugin files to their original package.', 'ninjascanner') .'</strong>'. 
			'<br /> 
			<strong>'. __('Compare theme files to their original package.', 'ninjascanner') .'</strong>'. 
				'<br />'. 
				__('NinjaScanner will compare the plugin and themes files from your installation to the original ones.', 'ninjascanner') .' '. sprintf( __('By default, only themes and plugins available in the wordpress.org repo can be checked that way. If you want to include premium plugins or themes too, <a href="%s">consult our blog</a>.', "ninjascanner"), NSCAN_LINK_INTEGRITY_CHECK ) . 
			'</li>'. 
		'</ul>'. 
 
		'<p><strong>'. __('File snapshot', 'ninjascanner') .'</strong>'. 
			'<br />'. 
			__('NinjaScanner will compare all files with the previous scan and will report if any of them were changed, added or deleted.', 'ninjascanner'). '</p>'. 
 
		'<p><strong>'. __('Database snapshot', 'ninjascanner') .'</strong>'. 
			'<br />'. 
			__('NinjaScanner will compare all posts and pages in the database with the previous scan and will report if any of them were changed, added or deleted.', 'ninjascanner'). '</p>'. 
 
		'<p><strong>'. __('Anti-malware signatures', 'ninjascanner') .'</strong>'. 
			'<br />'. 
			__('This option lets you scan your files for potential malware and virus using the built-in signatures.', 'ninjascanner'). ' '. sprintf( __('<a href="%s">Consult our blog</a> if you want to add your own signatures.', 'ninjascanner'), NSCAN_LINK_ADD_SIGS ). 
		'</p>'. 
		'<p><strong>'. __('Google Safe Browsing', 'ninjascanner') .'</strong>'. 
			'<br />'. 
			__('This option lets you check if your website is identified as having malware or exhibiting phishing activity by the Google Safe Browsing API.', 'ninjascanner') .' '.	sprintf( __('You will need <a href="%s">a free Google API key</a> in order to use this feature.', 'ninjascanner'), 'https://developers.google.com/safe-browsing/v4/get-started' ). 
		'</p>'. 
 
 
		'<strong>'. __('Files and folders', 'ninjascanner') .'</strong>'. 
		'<ul>'. 
			'<li><strong>'. __('Do not follow symbolic links.', 'ninjascanner') .'</strong>'. 
				'<br />'. 
				__('By default, NinjaScanner will not follow symbolic links.', 'ninjascanner') . 
			'</li>'. 
			'<li><strong>'. __('Warn if symbolic links.', 'ninjascanner') .'</strong>'. 
				'<br />'. 
				__('The scanner will warn you if it has found symbolic links in your WordPress installation.', 'ninjascanner') . 
			'</li>'. 
			'<li><strong>'. __('Warn if executable files (MZ/PE/NE and ELF formats).', 'ninjascanner') .'</strong>'. 
				'<br />'. 
				__('The scanner will warn you if it has found executable files in your WordPress installation.', 'ninjascanner') . 
			'</li>'. 
			'<li><strong>'. __('Warn if hidden PHP scripts.', 'ninjascanner') .'</strong>'. 
				'<br />'. 
				__('The scanner will warn you if it has found hidden PHP scripts in your WordPress installation.', 'ninjascanner') . 
			'</li>'. 
			'<li><strong>'. __('Warn if unreadable files of folders.', 'ninjascanner') .'</strong>'. 
				'<br />'. 
				__('The scanner will warn you if it has found unreadable files or folders in your WordPress installation.', 'ninjascanner') . 
			'</li>'. 
		'</ul>'. 
 
		'<h4>'. __('Scanning process', 'ninjascanner') .'</h4>'. 
		__('This set of option lets you manage how NinjaScanner will fork its scanning process that will run in the background.', 'ninjascanner') .' '. __('If the scanner does not start and throws an error, select a different fork method.', 'ninjascanner') . 
		'<ul>'. 
			'<li><strong>'. __('Fork process using WordPress built-in WP-CRON.', 'ninjascanner') .'</strong>'. 
				'<br />'. 
				__('This option will work even if you disabled WP-CRON with <code>DISABLE_WP_CRON</code>.', 'ninjascanner') . 
			'</li>'. 
			'<li><strong>'. __('Fork process using WordPress built-in Ajax Process Execution.', 'ninjascanner') .'</strong>'. 
				'<br />'. 
				__('This is the default value and should work on most systems.', 'ninjascanner') . 
 
			'</li>'. 
		'</ul>'. 
 
		'<h4>'. __('Integration', 'ninjascanner') .'</h4>'. 
		'<ul>'. 
			'<li><strong>'. __('Display the status of the running scan in the Toolbar.', 'ninjascanner') .'</strong>'. 
				'<br />'. 
				__('NinjaScanner will display the running scan status in the Toolbar.', 'ninjascanner') . 
			'</li>'. 
			'<li><strong>'. __('Integrate NinjaScanner with NinjaFirewall.', 'ninjascanner') .'</strong>'. 
				'<br />'. 
				sprintf( __('If you are running our <a href="%s">NinjaFirewall (WP or WP+ Edition)</a> web application firewall, you can integrate the NinjaScanner menu into NinjaFirewall. You could access it by clicking on "NinjaFirewall > NinjaScanner":', 'ninjascanner'), 'https://nintechnet.com/ninjafirewall/' ) . 
				'<p style="text-align:center"><img src="'. plugins_url() .'/ninjascanner/static/integration.png"></p>' . 
				__('Note that you need at least NinjaFirewall version v3.6.', 'ninjascanner') .'<br />'. 
 
			'</li>'. 
		'</ul>'. 
 
		'<h4>'. __('Scan report', 'ninjascanner') .'</h4>'. 
		'<ul>'. 
			'<li><strong>'. __('Display report.', 'ninjascanner') .'</strong>'. 
				'<br />'. 
				__('This option lets you select if you want to see the scan report on one or multiples pages. If you have a lot of files on your website, consider viewing the report on multiple pages in order to speed up the loading of the page.', 'ninjascanner') . 
			'</li>'. 
			'<li><strong>'. __('Row action links.', 'ninjascanner') .'</strong>'. 
				'<br />'. 
				__('Those links are displayed below each file in the scan report (e.g., "View file", "File Info" etc).', 'ninjascanner') . 
			'</li>'. 
			'<li><strong>'. __('Maximum number of visible rows in table.', 'ninjascanner') .'</strong>'. 
				'<br />'. 
				__('This option will control the height of each table.', 'ninjascanner') . 
			'</li>'. 
			'<li><strong>'. __('File names.', 'ninjascanner') .'</strong>'. 
				'<br />'. 
				__('This option lets you choose whether you want to see the absolute or relative path of a file. Note that it applies to the  quarantined and ignored files lists as well.', 'ninjascanner') . 
 
			'</li>'. 
		'</ul>'. 
 
		'<p><strong>'. __('HTTP basic authentication (optional)', 'ninjascanner') .'</strong>'. 
		'<br />'. 
		__('If your site is password-protected using HTTP basic authentication, you can use this option to enter your username and password.', 'ninjascanner'). '</p>'. 
 
		'</div>' 
	) ); 
 
	get_current_screen()->add_help_tab( array( 
		'id'        => 'nscan_nerds_settings', 
		'title'     => __('Nerds Settings', 'ninjascanner'), 
		'content'   => '<div style="height:400px;">' . 
 
		// Nerds Settings 
 
		'<h3>'. __('Nerds Settings', 'ninjascanner') .'</h3>'. 
 
		'<h4>'. __('File integrity checksum', 'ninjascanner') .'</h4>'. 
		'<ul>'. 
			'<li><strong>'. __('MD5, SHA-1, SHA-256.', 'ninjascanner') .'</strong>'. 
				'<br />'. 
				__('This options lets you select which algorithm the scanner file integrity checker should use when comparing files: MD5 (faster but less secure), SHA-1 or SHA-256 (slower but more secure).', 'ninjascanner') . 
			'</li>'. 
		'</ul>'. 
 
		'<strong>'. __('Note:', 'ninjascanner' ) .'</strong> '. 
		__('If your server is too slow or has very limited resources, try using less secure algorithms for faster processing.', 'ninjascanner'). 
 
		'<p><strong>'. __('Debugging', 'ninjascanner') .'</strong>'. 
			'<br />'. 
		__('This option will display the "Log" tab which lets you access the scanner debugging log.', 'ninjascanner'). '</p>'. 
 
		'<p><strong>'. __('Sandbox', 'ninjascanner') .'</strong>'. 
			'<br />'. 
		__('When moving a file to the quarantine folder, NinjaScanner can use a testing environment (a.k.a. sandbox) to make sure that this action does not crash your blog with a fatal error. If it does, it will warn you and will not quarantine the file. Disabling this option will void the testing environment (not recommended).', 'ninjascanner'). '</p>'. 
 
		'<p><strong>'. __('Run the garbage collector', 'ninjascanner') .'</strong>'. 
			'<br />'. 
		__('This option lets you setup NinjaScanner\'s built-in garbage collector frequency. It is used to clean-up the cache folder from temporary files created or downloaded during a scan.', 'ninjascanner'). '</p>'. 
 
		'</div>' 
	) ); 
 
 
	get_current_screen()->add_help_tab( array( 
		'id'        => 'nscan_quarantine', 
		'title'     => __('Quarantine', 'ninjascanner'), 
		'content'   => '<div style="height:400px;">' . 
 
		// Quarantine tab 
 
		'<h3>'. __('Quarantine', 'ninjascanner') .'</h3>'. 
		__('All files moved to the quarantine folder can be either permanently deleted or restored to their original folder. You can select one or multiple files.', 'ninjascanner') .'<br />'. __('Note that the Garbage Collector will not flush the quarantine folder, therefore quarantined files will remain in the folder until you restore or delete them.', 'ninjascanner'). 
		'</div>' 
	) ); 
 
 
	get_current_screen()->add_help_tab( array( 
		'id'        => 'nscan_ignored', 
		'title'     => __('Ignored', 'ninjascanner'), 
		'content'   => '<div style="height:400px;">' . 
 
		// Quarantine tab 
 
		'<h3>'. __('Ignored files list', 'ninjascanner') .'</h3>'. 
		__('All files moved to the ignored files list will remain there until they are modified or manually removed from the list.', 'ninjascanner'). 
		'</div>' 
	) ); 
 
 
	get_current_screen()->add_help_tab( array( 
		'id'        => 'nscan_cli', 
		'title'     => __("WP-CLI", "ninjascanner"), 
		'content'   => '<div style="height:400px;">' . 
 
		// WP-CLI 
 
		'<h3>'. __('WP-CLI', 'ninjascanner') . $premium .'</h3>'. 
 
		'<p>'. sprintf( __('NinjaScanner can nicely integrate with <a href="%s">WP-CLI</a>, using the <code>ninjascanner</code> command.', 'ninjascanner' ), 'http://wp-cli.org/') 
		.'<br />'. 
		__('You can use it to start or stop a scanning process, view its status, its report or log:', 'ninjascanner' ) . 
		'</p>'. 
 
		'<p><textarea class="small-text code" style="width:100%;height:230px;color:#00FF00;background-color:#23282D;font-size:13px" wrap="off">'. 
 
		"$ wp ninjascanner help\n\n". 
		"NinjaScanner v". NSCAN_VERSION . 
		" (c)". date('Y') ." NinTechNet ~ https://nintechnet.com/\n". 
		__('Available commands:', 'ninjascanner') ."\n". 
		"   wp ninjascanner help         ". __('Display this help screen', 'ninjascanner') ."\n". 
		"   wp ninjascanner start        ". __('Start a scan', 'ninjascanner') ."\n". 
		"   wp ninjascanner stop         ". __('Stop the scanning process', 'ninjascanner') ."\n". 
		"   wp ninjascanner status       ". __('Show scan status', 'ninjascanner') ."\n". 
		"   wp ninjascanner report       ". __('View the last scan report', 'ninjascanner') ."\n". 
		"   wp ninjascanner log          ". __('View the debugging log', 'ninjascanner') ."\n". 
		"   wp ninjascanner license      ". __('Enter your Premium license key', 'ninjascanner') ."\n". 
		'</textarea></p>'. 
		__('Premium users can also enter their license from WP-CLI, without having to log in to their Dashboard.', 'ninjascanner' ). 
 
		'</div>' 
	) ); 
 
	get_current_screen()->add_help_tab( array( 
		'id'        => 'nscan_ts', 
		'title'     => __("Troubleshooting", "ninjascanner"), 
		'content'   => '<div style="height:400px;">' . 
 
		// Troubleshooting link: 
		'<h3>'. __('Troubleshooting', 'ninjascanner') .'</h3>'. 
 
		sprintf(	__('Please consult this article: %s.', 'ninjascanner' ),	'<a href="https://blog.nintechnet.com/ninjascanner-troubleshooting/">NinjaScanner Troubleshooting</a>' ). 
 
		'</div>' 
	) ); 
} 
 
// ===================================================================== 
// EOF 
