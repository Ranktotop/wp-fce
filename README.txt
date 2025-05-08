=== FluentCommunity Extreme Add-On ===
Contributors: marcmeese
Donate link: https://marcmeese.com
Tags: fluentcommunity, api, payments, digistore24, copecart, membership
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 0.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add API and automation features to FluentCommunity for managing members via external payment systems.

== Description ==

This add-on extends the FluentCommunity plugin by providing an integration layer for external payment providers such as CopeCart and Digistore24.

Features include:
* Product-to-course/space mapping
* Automatic user registration
* Access management based on payment status
* REST API for IPN handling
* Membership expiration check via cronjob

== Installation ==

1. Upload the plugin ZIP via **Plugins > Installieren > Plugin hochladen** in your WordPress admin
2. Activate the plugin
3. Make sure **FluentCommunity** is installed and active
4. Configure under **Einstellungen > FluentCommunity Extreme**

== Frequently Asked Questions ==

= Does this work without FluentCommunity? =
No, this plugin is an add-on and requires FluentCommunity to be active.

= Which payment providers are supported? =
Currently CopeCart and Digistore24, via their IPN systems.

== Screenshots ==

1. API & Darstellung settings page
2. Product mapping interface

== Changelog ==

= 0.0.1 =
* Initial release with IPN support for Digistore24 and CopeCart
* User creation and membership handling
* Cronjob for expiration check

== Upgrade Notice ==

= 0.0.1 =
Initial release of FluentCommunity Extreme Add-On. Requires FluentCommunity to function.

== License ==

GPL-2.0-or-later

== Author ==

Marc Meese — https://marcmeese.com
