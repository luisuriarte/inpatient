=== NinjaScanner - Virus & Malware scan ===
Contributors: nintechnet, bruandet
Tags: malware, virus, security, protection, scanner
Requires at least: 4.7.0
Tested up to: 6.7
Stable tag: 3.2.4
License: GPLv3 or later
Requires PHP: 7.1
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A lightweight, fast and powerful virus scanner for WordPress.

== Description ==

= A lightweight, fast and powerful virus scanner for WordPress. =

NinjaScanner is a lightweight, fast and powerful virus scanner for WordPress which includes many features to help you scan your blog for malware and virus.

= Features =

* File integrity checker.
* File comparison viewer.
* Exclusion filters.
* File snapshot.
* Database snapshot.
* Anti-malware/Antivirus.
* [Sandbox for quarantined files](http://nin.link/nssandbox/ "NinjaScanner sandbox").
* Ignored files list.
* Google's Safe Browsing Lookup API.
* Background scans.
* Scheduled scans (Premium).
* WP-CLI integration (Premium).
* Debugging log.
* Email report.
* Integration with [NinjaFirewall (WP and WP+ Edition)](https://wordpress.org/plugins/ninjafirewall/ "Download NinjaFirewall").
* Multi-site support.
* Contextual help.
* And many more...

= File Integrity Checker =

The File Integrity Checker will compare your WordPress core files as well as your plugin and theme files to their original package. Its File Comparison Viewer will show you the differences between any modified file and the original. You can also [add your Premium themes and plugins](https://blog.nintechnet.com/ninjascanner-powerful-antivirus-scanner-for-wordpress/#integrity "") to the File Integrity Checker. Infected or corrupted files can be easily restored with one click.

= File Snapshot =

The File Snapshot will show you which files were changed, added or deleted since the previous scan.

= Database Snapshot =

NinjaScanner will compare all published posts and pages in the database with the previous scan and will report if any of them were changed, added or deleted.

= Anti-Malware Signatures =

You can scan your blog for potential malware and virus using the built-in signatures. The scanning engine is compatible with [Linux Malware Detect LMD](https://github.com/rfxn/linux-malware-detect "") (whose anti-malware signatures are included) and with some [ClamAV](https://www.clamav.net/ "") signatures as well. You can even [write your own anti-malware signatures](https://blog.nintechnet.com/ninjascanner-powerful-antivirus-scanner-for-wordpress/#signatures "").

= NinjaFirewall Integration =

If you are running our [NinjaFirewall (WP or WP+ Edition)](https://wordpress.org/plugins/ninjafirewall/ "Download NinjaFirewall") web application firewall plugin, you can use this option to integrate NinjaScanner into its menu.

= Fast and Lightweight Scanner =

NinjaScanner has strictly no impact on your database. It only uses it to store its configuration (less than 1Kb). It saves the scan data, report, logs etc on disk only, makes use of caching to save bandwidth and server resources. It also includes a Garbage Collector that will clean up its cache on a regular basis.

= Background Scans =

Another great NinjaScanner feature is that it runs in the background: start a scan, let it run and keep working on your blog as usual. You can even log out of the WordPress dashboard while a scanning process is running! You don't have to wait patiently until the scan has finished. Additionally, a scan report can be sent to one or more email addresses.

= Sandbox for quarantined files =

When moving a file to the quarantine folder, NinjaScanner can use a testing environment (a.k.a. sandbox) to make sure that this action does not crash your blog with a fatal error. If it does, it will warn you and will not quarantine the file. It is possible (but not recommended) to disable the sandbox.

= Advanced Settings =

NinjaScanner offers many advanced settings to finely tune it, such as exclusion filters, selection of the algorithm to use, a debugging log etc.

= Privacy Policy =

Your website can run NinjaScanner and be 100% compliant with the **General Data Protection Regulation (GDPR)**:

We, the authors, do not collect, share or sell personal information. We don't track or profile you. Our software does not collect any private data from you or your visitors.

= Premium Features =

Check out our [NinjaScanner Premium Edition](https://nintechnet.com/ninjascanner/ "NinjaScanner Premium Edition")

* **Scheduled Scans**: Don't leave your blog at risk. With the scheduled scan option, NinjaScanner will run automatically hourly, twice daily or daily.
* **WP-CLI Integration**: Do you own several blogs and prefer to manage them from the command line? NinjaScanner can nicely integrate with WP-CLI, using the `ninjascanner` command. You can use it to start or stop a scanning process, view its status, its report or log from your favourite terminal, without having to log in to the WordPress Admin Dashboard.
* **Dedicated Help Desk with Priority Support**

== Installation ==

1. Upload the `ninjascanner` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' page in WordPress.
3. Plugin settings are located in the 'Tools > NinjaScanner' sub-menu.

== Screenshots ==

1. Summary page.
2. Basic settings.
3. Advanced settings.
4. Nerds settings.
5. WP-CLI integration.
6. Report sample.
7. Viewing differences between the modified and the original files.
8. Debugging log.
9. Integration with NinjaFirewall.

== Changelog ==

= 3.2.4 =
* Fixed a potential "Function _load_textdomain_just_in_time was called incorrectly" PHP notice that can occurred with WordPress >=6.7.

= 3.2.3 =

* We have a new API: api.nintechnet.com. Make sure to whitelist this subdomain if you are filtering outgoing connections.

= 3.2.2 =

* Fixed a bug where MU plugins added to the list of ignored files were still displaying a warning in the report sent by email.

= 3.2.1 =

* Fixed a potential "Undefined constant NFW_ENGINE_VERSION" fatal error.
* Adjusted PHP max_execution_time and memory limit during a scan.

= 3.2 =

* The "Apply the exclusion list to the file integrity checker" feature from the "Ignore files/folders" option will now apply to WordPress core files as well, not only to the themes and plugins files.

= 3.1 =

* Fixed a potential "File is not in the ABSPATH or DOCUMENT_ROOT" error message when trying to view a file.
* Fixed an issue where it was not possible to activate or deactivate NinjaScanner from WP CLI.
* The scanner will attempt to disable the PHP display_errors directive so that notice, warning and error messages won't show up in the AJAX response.
* Small fixes and adjustments.
* Minimum PHP Version updated to 7.1.

= 3.0.12 =

* Fixed a potential "Cannot use object of type WP_Error as array" fatal error.
* Updated "Tested up to" to match WordPress 6.2.

= 3.0.11 =

* Fix compatibility issue with PHP 8.2.
* Fix compatibility issue with older PHP version (<7.3).
* Updated Prism.js libraries.
* Small fixes and adjustments.

= 3.0.10 =

* On websites running PHP 7.3 or above, NinjaScanner will use the hrtime() function instead of microtime() for its metrics, because it is more reliable as it is not based on the internal system clock.
* Fixed an issue where it was not possible to quarantine a file when running NinjaScanner on localhost over TLS because cURL rejected the self-signed certificate.
* Fixed a bug with right-to-left (RTL) WordPress sites where the checkboxes below the log were all messed up.
* Updated Prism.js libraries.
* Small fixes and adjustments.

= 3.0.9 =

* Fixed a potential PHP "sprintf" fatal error that could occur if there were an error during the scanning process.
* Fixed a regex bug when checking for a Linux or Windows absolute path.
* Updated Prism.js libraries.
* Added more details to the scanner's log when a scan is cancelled because of an error.

= 3.0.8 =

* If the PHP ZIP extension, which provides the ZipArchive class, is missing on the server, NinjaScanner will fall back to the built-in PclZip library instead of refusing to run.
* When catching a PHP fatal error (E_ERROR), the scanner will write to the log the full path to the file where the error occured.
* Small fixes and adjustments.

= 3.0.7 =

* Fixed an issue during the anti-malware scan where the number of scanned items appeared to be higher than the total of files to be scanned, and returned an "Unknown Error" message.
* Fixed an issue where corrupted ZIP files downloaded from wordpress.org were not deleted.
* The anti-malware signatures file used during the scan will be temporarily saved to the database and no longer to disk because some antivirus used on Microsoft-IIS are still flagging the file as malware and delete it.

= 3.0.6 =

* Fixed a potential "Missing Lock File" error that may occur on slow servers.
* Added streaming to the wp_remote_get function to lower the amount of memory used during downloads (props Daniel Ruf).

= 3.0.5 =

* Fixed error introduced in 3.0.3 affecting PHP versions 7.1 and below.
* Replaced the "install_plugins" capability with "manage_options", to allow administrators to run the scanner even if the WordPress built-in "DISALLOW_FILE_MODS" constant is defined.
* Fixed a potential "Undefined variable: snapshot" PHP notice.
* Better detection of any potential error during the scanner initialization by using a blocking socket.
* The temporary file used to saved malware signatures during the scanning process is now base64-encoded to prevent it form being flagged as malware by some hosting companies.
* Updated PrismJS to the latest version.
* Added missing description to the WP-CLI script (props Daniel Ruf).
* Small fixes and adjustments.

= 3.0.2 =

* Fixed a potential issue where the scan could not start.

= 3.0.1 =

* Fixed a potential syntax error introduced in v3.0.
* The whole scanner engine was rewritten from scratch, so that it can work on very low resource servers.
* The scan report can be displayed on multiple pages instead of one only. This can be selected from the "Settings > Advanced Users Settings > Display report" option.
* It is possible to select which folders to scan in the blog directory ("Settings > Blog directory").
* HTTP basic authentication is now supported by the WP-CRON fork method.
* Adjustments for PHP 8.0 compatibility.
* Updated PrismJS libraries.
* Many small fixes and improvements.

