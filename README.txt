=== FluentCommunity Extreme Add-On ===
Contributors: marcmeese
Donate link: https://marcmeese.com
Tags: fluentcommunity, api, payments, digistore24, copecart, membership
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add API and automation features to FluentCommunity for managing members via external payment systems.

== Description ==

This add-on extends the FluentCommunity plugin by providing an integration layer for external payment providers such as CopeCart and Digistore24.

It enables full automation of user access management, including:

* Mapping digital products to courses and spaces
* Handling Instant Payment Notifications (IPNs)
* Auto-creating user accounts and granting access
* Managing expirations and cancellations via cronjob
* REST API endpoints for external integration
* Admin override functionality for manual control

Use this plugin to connect your external shop system with your FluentCommunity-powered WordPress site.

== Installation ==

1. Upload the plugin ZIP via **Plugins > Installieren > Plugin hochladen** in your WordPress admin
2. Activate the plugin
3. Make sure **FluentCommunity** is installed and active
4. Go to **Einstellungen > FluentCommunity Extreme** to configure the plugin
5. Map products to courses or spaces under the Product Mapping section

== Frequently Asked Questions ==

= Does this work without FluentCommunity? =
No. This plugin is an add-on and requires FluentCommunity to be installed and active.

= Which payment providers are supported? =
Currently CopeCart and Digistore24, via their IPN (Instant Payment Notification) systems.

= Can I manually override access? =
Yes. Admins can override access to spaces and courses independent of payment status.

= What happens when a product expires or is cancelled? =
Access is automatically revoked unless another active product grants access to the same space or course.

== Screenshots ==

1. API & Darstellung settings page
2. Product mapping interface
3. Simulated IPN testing tool (for admin use)

== Changelog ==

= 1.1.6 =
* Added buy credits button on control panel

= 1.1.3 =
* Added json support

= 1.0.7 =
* Added json webhook for adding users to public courses or spaces. Also added overwrite for login landing page

= 1.0.6 =
* Added responsive style for control panel

= 1.0.5 =
* Fixed translation

= 1.0.4 =
* Fixed some bugs

= 1.0.3 =
* Fixed some bugs

= 1.0.2 =
* Added community api integration
* Fixed some bugs

= 1.0.1 =
* Product-space mapping system fully integrated
* Fluent access sync via cronjob and real-time events
* Evaluator-based permission logic with admin overrides
* Access logging and REST interface improvements
* Performance optimization and redundancy cleanup

= 0.0.1 =
* Initial release with IPN support for Digistore24 and CopeCart
* User creation and membership handling
* Cronjob for expiration check

== Upgrade Notice ==

= 1.0.1 =
Major access system overhaul. Adds override support, Fluent sync, and evaluator logic.

= 0.0.1 =
Initial release of FluentCommunity Extreme Add-On. Requires FluentCommunity to function.

== License ==

GPL-2.0-or-later

== Author ==

Marc Meese — https://marcmeese.com
