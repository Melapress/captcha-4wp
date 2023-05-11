=== CAPTCHA 4WP ===
Contributors: WPWhiteSecurity
Tags: recaptcha, nocaptcha, captcha, invisible captcha, spam protection, captcha for WooCommerce, forms captcha
Requires at least: 5.0
Tested up to: 6.2.0
Stable tag: 7.2.1
Requires PHP: 7.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

Stop spam bots, fake accounts, and fake orders and allow prospects and customers to interact with your website with ease - add CAPTCHA to any form on your website.

== Description ==

<strong>THE MOST POWERFUL & EASY TO USE CAPTCHA SOLUTION FOR WORDPRESS WEBSITES</strong><br />

Add CAPTCHA to forms on your WordPress website. Protect the WordPress website and e-commerce store from spam comments, automated login attacks, fake registrations and fake orders with CAPTCHA. 

CAPTCHA 4WP is very easy to us, allowing you to implement CAPTCHA to any built-in WordPress form easily. With the Premium edition you can also add CAPTCHA checks to WooCommerce checkout pages and other forms within just minutes.

The plugin is trusted by more than 200,000 administrators to protect their websites from spam, fake accounts, & fake orders!

> <strong>With the free edition you can add CAPTCHA to the built-in WordPress forms; the login page, registration form, comments, reset and lost password forms. To add CAPTCHA to forms created with third party plugins such as WooCommerce, Contact Form 7, Gravity Forms, and BuddyPress <strong>[upgrade to CAPTCHA 4WP Premium](https://www.wpwhitesecurity.com/wordpress-plugins/captcha-plugin-wordpress/pricing/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=C4WP&utm_content=plugin+repos+description).</strong>
>

#### Maintained & Supported by WP White Security

WP White Security is a European development company that builds high-quality WordPress security & admin plugins. Check out our list of [WordPress security plugins](https://www.wpwhitesecurity.com/wordpress-plugins/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=all+plugins&utm_content=plugin+repos+description) for more information on how our plugins can help you better manage and improve the security of your WordPress websites and users.

#### Add Spam protection on:

* WordPress Login, user registration and comment form
* WordPress lost password and reset password pages
* WooCommerce checkout & registration forms (Premium)
* WooCommerce login, password reset & lost password pages (Premium)
* BuddyPress user registration, comments and group forms (Premium)
* bbPress (New topic, reply to topic & registration) (Premium)
* Contact & other types of forms created with Contact Form 7, Gravity Forms, WPForms, MailChimp for WordPress and other third party plugins (Premium).

###  Additional features

* Select from different types of CAPTCHA (v2 I'm not robot checkbox, v2 invisible or v3 invisible)
* CAPTCHA v3 failover configuration (ensure no prospect is incorrectly marked as spam)
* Set CAPTCHA passmark score
* Plugin automatically detects the visitors' language setting and show CAPTCHA in that language
* Configure the CAPTCHA properties, such as theme, size, badge location & more
* White-list logged in users, IP address and URLs (Premium)
* Add CAPTCHA to any type of form, including PHP forms (Premium)
* Show CAPTCHA on login page if there are failed logins

Refer to the <strong>[CAPTCHA plugin benefits and features](https://www.wpwhitesecurity.com/wordpress-plugins/captcha-plugin-wordpress/features-benefits/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=all+plugins&utm_content=plugin+repos+description)</strong> for a complete list of all the features you can take advantage of to protect your website and e-commerce store from spam, automated spam bots, fake registrations, and fake orders!

### Free and Premium Support

Support for CAPTCHA 4WP is free on the WordPress support forums.

Premium world-class support is available via email to all [CAPTCHA 4WP Premium](https://www.wpwhitesecurity.com/wordpress-plugins/captcha-plugin-wordpress/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=C4WP&utm_content=plugin+repos+description) users.

> <strong>Note</strong>: paid customer support is given priority and is provided via one-to-one email. [Upgrade to Premium](https://www.wpwhitesecurity.com/wordpress-plugins/captcha-plugin-wordpress/pricing/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=C4WP&utm_content=plugin+repos+description) to benefit from priority support.
>

### As Featured On:

* [WPBeginner](https://www.wpbeginner.com/plugins/how-to-add-captcha-in-wordpress-login-and-registration-form/)
* [Elegant Themes](https://www.elegantthemes.com/blog/wordpress/wordpress-captcha)
* [IsItWP](https://www.isitwp.com/best-wordpress-captcha-plugins/)
* [WPLift](https://wplift.com/best-wordpress-captcha-plugins)
* [TesterWP](https://testerwp.com/best-free-captcha-wordpress-plugins/)


#### Privacy Notice

* This plugin sends the visitor's IP address to Google for CAPTCHA verification. This happens on all websites that use these type of CAPTCHA services. Please read the [Google Privacy Policy](https://policies.google.com/) for more information.

== Installation ==

=== Install CAPTCHA 4WP from within WordPress ===

1. Visit the 'Plugins' page
1. Click the 'Add New' button and search for 'CAPTCHA 4WP'
1. Install and activate the CAPTCHA 4WP plugin

=== Install WP Activity Log manually ===

1. Upload the `advanced-nocaptcha-recaptcha` directory to the `/wp-content/plugins/` directory
1. Activate the CAPTCHA 4WP plugin from the 'Plugins' page in WordPress

== Frequently Asked Questions ==

= Can i use this plugin to my language? =
Yes. this plugin is translator ready. If you want to help translating this plugin in your language [contact us](https://www.wpwhitesecurity.com/contact/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=C4WP&utm_content=plugin+repos+description).

= Can i show multiple CAPTCHA's on the same page? =
Yes. You can show an unlimited number of CAPTCHA's on the same page.

= How can I add CAPTCHA to a form created with Contact Form 7? =
Use the unique 1-click feature: simply click the "Add CAPTCHA" button in the Contact Form 7 form builder to add the CAPTCHA to the form.

= How can I add Captcha in WooCommerce pages? =
Simply select the WooCommerce page you want to add CAPTCHA to in the plugin's CAPTCHA placement settings. You can also specify where exactly you want to add the CAPTCHA test on the checkout page.


== Screenshots ==

1. Configuring reCAPTCHA and the plugin is very easy with the wizard.
2. In the setup wizard all you have to do is select and configure reCAPTCHA.
3. When you use reCAPTCHA v3 you can also reconfigure a failover action, to cater for false negatives.
4. CAPTCHA in the WordPress login form
5. CAPTCHA in the WordPress comments form
6. CAPTCHA in lost password request form
7. CAPTCHA in password reset / change form
8. The CAPTCHA configuration can be clearly seen from the plugin's configuration.

== Changelog ==

= 7.2.1 (20230511) =

* **New features & functionality**
	* Added check for 'path' during check for REST request which returns false if no path is found and can be overriden via filter 'c4wp_is_rest_request_no_path_found'

* **Bug fixes**
	* Jetpack + V3: Fixed issue causing CAPTCHA to return false when logging in.
	* PHP 7.2 Compatibility: Fixed small PHP errors within settings when running PHP 7.2.
	* General PHP improvements: Fixed error caused by method not declared as static.

= 7.2.0 (20230427) =

Release notes: [CAPTCHA 4WP 7.2.0 - Failover for V3 and much more!](https://www.wpwhitesecurity.com/c4wp-7-2-0/)

* **New features & functionality**
	* CAPTCHA failure fallback system: V3 now has a fallback option when the initial check fails: choose between redirecting to another page or show a V2 "I am not a robot checkbox".
	* First time install wizard to guide users through setting up the Google reCAPTCHA etc.
	* Google reCAPTCHA configuration shown in the UI / plugin settings.
	* Wizard to assinst user changing the reCAPTCHA configuration.
	* Google reCAPTCHA keys validation - all keys are validated within the admin area to ensure the correct details have been provided.
	* New WPML config file for WPML support (translations).
	* Support for WooCommerce HPOS so user can activate the feature without hinderance.
	
* **Improvements**
	* WooCommerce support - improved overall form handling to ensure less friction with 3rd party scripts.
	* In WPForms editor the plugin's message is shown in the correct place when editing posts via Gutenberg. 
	* V2 Checkbox - The field now resets itself should it expire due to lack to user input.
	* Improved performance and compatibility of custom JS in BuddyPress.
	* Core JS is now written in plain Javascript for Improved performance and compatibility.
	* Overall coding standards improvements
	* UI Improvements to the ‘excluded IP’ and ‘excluded users’ fields in the plugin settings.
	* Improved plugin's help and UI text for improved ease of use.
	* Moved the Captcha preview within the configuration popup in the plugin settings.
	* Removed any use of depreciated JS function ‘jQuery.fn.load’.

* **Bug fixes**
	* CF7 + V2 Invisible - Fixed issue causing mail sent confirmation to be removed on submission.
	* BuddyPress + V3 - Fixed issue causing user registration for to return console errors
	* WPForms + V3 - Fixed issue causing ‘please solve captcha’ message to still appear on valid submissions.
	* Improved compatibility with WP comment systems which do not use jQuery.
	* Multisite - Removed link to settings page where needed.
	* Fixed bug in Settings which would cause removed IP address and users to re-appear on refresh.
	* Fixed issue in ‘hide for IP’ setting which can occur when multiple IDs are present.

= 7.1.1 (20220818) =

* **New features**
	* Site & secret key validation: plugin displays preview of CAPTCHA to confirm correct setup.
	* Users are now prompted when attempting to switch CAPTCHA versions to ensure new keys are provided (UX improvement).
	
* **Improvements**
	* Contact Form 7 AJAX validation is now handled independently to avoid compatibility issues.
	* Improved handling of CAPTCHA JS within the WooCommerce checkout for better compatibility.
	* Improved presentation of ‘hide CAPTCHA badge’ field within the settings.
	* Updated Fremius SDK to version 2.4.5.

* **Bug fixes**
	* Corrected issue in which some trial users were not being shown all features.
	* Adjusted internal ‘additional callback JS’ filter to append all content rather than override.

= 7.1.0 (20220629) =

Release notes: [Support for WPForms & Gravity Forms plugins](https://www.wpwhitesecurity.com/captcha-4wp-7-1-0/]

* **New features**
	* Added support for Gravity Forms.
	* Added support for WPForms.
	* New setting to show the CAPTCHA logo on the lower left rather than the default, right.
	
* **Security fix**
	*  Local File Inclusion reported by ZhongFu Su (JrXnm) of WuHan University.
	
* **Improvements**
	* Improved JS handling within the WooCommerce checkout to ensure a more robust field during checkout changes.
	* Improved settings inline help text.
	* The action ‘c4wp_captcha_form_field’ is now exclusive to premium editon.
	* Ensure applicable settings only accessible based on the current license.
	* Support for [c4wp-captcha] shortcode is now exclusive to the premium edition.
	* Streamlined plugin’s internal build process.
	* Improved Coding Standards throughout plugin.

* **Bug fixes**
	* Fixed logic issue which could cause an error during new user registration.
	* Ensure only specific internal files can be loaded within the help area.
	* Ensure applicable functions return first argument where needed.
	* Allow for variants in variables caused by server operating system.

= 7.0.6.1 (20220315) =

* **Improvements**
	* Ensure migration script to new options table is run where needed.
  * Ensure correct default language is set during update in free edition.
  * Ensure verification does not hinder hook requests where no CAPTCHA is posted.

= 7.0.6 (20220315) =

* **New features & functionality**
	* A new setting that allows you to enable CAPTCHA on WooCommerce logins whilst disabling it on the checkout login form.

Release notes: [CAPTCHA 4WP 7.0.6 Free Edition](https://www.wpwhitesecurity.com/c4wp-free-7-0-6/)

* **Breaking change**
	* CAPTCHA on forms creatd with third party plugins available through the Premium. [Get a free 7-day trial](https://www.wpwhitesecurity.com/wordpress-plugins/captcha-plugin-wordpress/plugin-trial/).

* **Improvements**
	* Update logic to ensure whitelisted IP addresses action only runs when needed.
	* Ensure plugin does not attempt to verify if submission contains to captcha field.
	* Updated Contact Form 7 "embeddable" form tag to include response field.
	* Updated branding within the Freemius admin areas.
	* Improved the CAPTCHA placement within the WooCommerce "password reset" page.
	* Overall admin UI improvements, including responsive styling.
	* Improved BuddyPress JS support for better compatibility.
	* Improved overall JS to ensure functions are defined when used.
	* Improved WooCommerce extension logic to ensure code only runs when functions are available.
	* Improved inline help text, including warning for users of JetPack comments regarding incompatibilty.
	* Improved logic within whitelisting to ensure accurate results.

* **Bug fixes**
	* Updated v2 invisible form submission JS for wider compatibility.
	* Updated features logic to ensure extensions are always loaded based on license.
	* Fix logic to ensure "login_captcha_filter" return accurate response.
	* Reinstated original "anr_nocaptcha" Contact Form 7 form tag (backward compatability).
	* Corrected Mailchimp 4 WP form tag from [c4wp_captcha] to [c4wp-captcha].
	* Ensure CAPTCHA language has the correct default in new installations.
	* Fixed error which caused "please solve CAPTCHA" message to appear on the "lost password" form.
	* Fixed redirection during installation on a network to ensure plugin takes user to correct admin area.
	* Fixed login within WooCommerce checkout which could cause CAPTCHA to always be hidden for logged in users.
	* Corrected typo within BBPress extension.
	* Ensure previous anr_nocaptcha Contact Form 7 code is validated.
	* Fix JS bug which could cause comments to not POST with v2 invisible captcha.

= 7.0.3 (20220121) =

* **Improvements**
	* Improved logic to dermine if a login verification should be "skipped" dependant on POSTed values.

* **Bug fixes**
	* Fixed: Re-implemented support for original CF7 form tag.
	* Fixed: Fixed issue which could cause login CAPTCHA's to not display.
	* Fixed: Fixed issue related to null variable in CF7 extension.

= 7.0.2 (20220119) =

* **Bug fixes**
	* Fixed: Ensure plugin does not interfere with unwanted "authenticate" calls.
	* Fixed: Use of private "construct" within CF7 extension.

= 7.0.1 (20220119) =

* **Bug fixes**
	* Fixed: PHP 7.2 Compatibility issue.

= 7.0.0 (20220119) =

Release notes: [Plugin reload: Advanced noCaptcha & invisible Captcha is now CAPTCHA 4WP](https://www.wpwhitesecurity.com/advanced-nocaptcha-recaptcha-renamed-captcha-4wp]

* **New features**

	* Plugin renamed to CAPTCHA 4WP.
	* New UI with improved UX.
	* A setting to choose where to place the CAPTCHA check on the WooCommerce checkout page. 
	* Added the option to exclude CAPTCHA from specific URLs.
	* Auto detect visitor language and auto-configure the CAPTCHA test language to match the visitor's language setting.
	* Plugin can now be activated at multisite network level or at individual child-sites level.
	
* **Improvements**
	* Plugin now has its own dedicated top level menu entry for configuration.
	* Replaced the plugin prefix to c4wp_ (both internally and for shortcodes). 
	* Updated a number of translatable strings.
	* Added a specific upgrade script to handle upgrades from pre v7.0.
	* Fixed support for PHP v7.2.
	* Removed the Freemius SDK from free edition.
	* Added a dedicated help and support area with downloadable "system info" for easier troubleshooting (in case support need it).
	* Added compatibility support for Wordfence 2FA.
	* Ensure failed logins filter only runs when apppropriate.
	* Added ability to detect and ignore REST API requests.
	* Failed login data is now stored in its own table with configurable pruning.
	* Seperated WooCommerce form logic to allow registration and login forms to be enabled/disabled independantally from the WordPress built-in forms.
	* Third party plugins extensions are now handled via seperate classes for modularity.
	* Better sanitization when saving admin options.
	* Removed obsolete "NoJS" setting.
	* Improved v2 Checkbox field positioning on WP login page.
	* Added support for Buddypress comments and activity areas.

* **Bug fixes**
	* Fixed: plugin "blocking" admin request to send a "reset password" email via a user's profile page.
	* Fixed: CAPTCHA not appearing on WordPress "lost password" form.
	* Removed obsolete code related to "FEP" forms.

= 6.1.7 (20211006) =

* IMPROVEMENT: Updated all the FAQs and help text links to point to the new documentation pages.
* IMPROVEMENT: Improved the help text for CAPTCHA v3 to better explain the severity scoring system.
* IMPROVEMENT: Ensure CAPTCHA is not loaded when a page is viewed via Wthe P customizer/Widget view (introduced in WP 5.8)
* IMPROVEMENT: Better support for PHP8
* FIX: Updated the CF7 field to ensure error messages are shown correctly when validation fails.
* FIX: Stopped CAPTCHA from being enforced on reset links, which causes sending of reset links via admin to fail.
* FIX: Admin notice dismissal is improved to avoid load "blank" tabs.
* FIX: Updated how form submission is handled during validation to improve compatibility (CAPTCHA V2).
* FIX: Fixed issue with CAPTCHA always failing validation on password reset screen (CAPTCHA V2).
* FIX: Fixed issue with CAPTCHA validation failing on WooCommerce checkout.

= 6.1.6 (20210907) =

* UPDATE: Announcing new developer + future updates.

= 6.1.5 =

* FIX: error in php version 7.4

= 6.1.4 =

* Use tab navigation for settings. Remove extra menu items from admin sidebar.
* Link to documentation on How to get google reCAPTCHA keys.
* Increase footer hook priority as some theme add login/register form with higher priority.

= 6.1.3 =

* recaptcha.net domain added.
* Error message now can be translated in file.

= 6.1.2 =

* Add Contact form 7 integration instruction page link in settings page of this plugin.

= 6.1.1 =

* recaptcha domain can now be changed from settings.
* footer script hook priority changed.
* use same settings if network activated.
* for cf7, use this plugins captcha instead of cf7 captcha.

= 5.7.1 =

* Minor bug fixed.

= 5.7 =

* IP whitelist feature added.
* Captcha V3 timeout issue fixed.
* UM login issue fixed.

= 5.6 =

* Return last verify incase of duplicate checking.
* Add google scripts src filters.
* Custom hook and captcha shortcode now support logged in setup.

= 5.5 =

* Fix: Multisite site signup during registration failed due to double verification.
* Fix: Comment reply failed from back-end.

= 5.4 =

* Use js for loop instead of php for loop
* Use number_formate_i18n to translate float
* Tested upto updated.

= 5.3 =

* Fix: Compatibility issue with reCaptcha v3 and CF7 version 5.1 & 5.1.1

= 5.2 =

* Now support reCaptcha v3 also
* Fix: invisible captcha sometimes was not working
* anr_verify_captcha filter added

= 4.4 =

* PRO version released
* anr_verify_captcha_pre filter added
* anr_get_option filter added

= 4.3 =

* Reset captcha if CF7 validation error occur
* Changed Tested up to

= 4.2 =

* BuddyPress mentioned in readme
* WooCommerce checkout captcha sometimes did not verify
* Reset captcha if WooCommerce checkout error occur
* If WordPress version is 4.9.0 or greater then pre_comment_approved filter used for comment which we can now return WP_Error

= 4.1 =

* Settings page redesigned.
* anr_is_form_enabled function added
* Captcha error show first before username password error. So if captcha is not validated then username password error is not shown.
* enqueue login css only if normal captcha is shown
* Enabled forms stored as an array in db. array key is enabled_forms
* Add class ANR_Settings, removed class anr_admin_class
* BuddyPress register captcha added

= 3.1 =

* Sometimes fatal error if is_admin return true in front-end.
* Do not show captcha in checkout if not checked for checkout.

= 2.8 =

* Now show captcha when use wp_login_form() function to create login form.

= 2.7 =

* Fix: Settings page checkbox uncheck was not working.

= 2.6 =

* New: Show captcha after set failed login attempts (may not work if you use ajax based login form, fall back to show always).
* Fix: contact form 7 deprecated function use.

= 2.5 =

* New: Invisible captcha feature added.
* Fix: Show captcha error when login form loaded
* Move this plugin settings page under Settings

= 2.4 =

* Bug fix: WooCommerce lostpassword corrupted link

= 2.3 =

* Comment form captcha issue fixed.
* Captcha now wraped in anr_captcha_field div class.
* Comment form captcha p tag removed.

= 2.2 =

* Security update.
* WooCommerce checkout form issue fixed.

= 2.1 =

* Captcha in WooCommerce added (WooCommerce Login, Registration, Lost password, Reset password forms).
* Allow multiple captcha in same page.
* Text domain changed.
* Some minor bug fixed.

= 1.3 =

* New filter 'anr_same_settings_for_all_sites' added, Now same settings can be used for all sites in Multisite.
* Multisite User Signup Form added.
* Some bug fixed.

= 1.2 =

* Now captcha size can be changed.
* bbPress New topic added
* bbPress reply to topic added
* XMLRPC_REQUEST Check
* Some bug fixed.

= 1.1 =

* Initial release.

== Upgrade Notice ==

= 6.1.5 =

* FIX: error in php version 7.4

= 6.1.4 =

* Use tab navigation for settings. Remove extra menu items from admin sidebar.
* Link to documentation on How to get google reCAPTCHA keys.
* Increase footer hook priority as some theme add login/register form with higher priority.

= 6.1.3 =

* recaptcha.net domain added.
* Error message now can be translated in file.

= 2.7 =

* Fix: Settings page checkbox uncheck was not working.

= 2.6 =

* New: Show captcha after set failed login attempts (may not work if you use ajax based login form, fall back to show always).
* Fix: contact form 7 deprecated function use.
