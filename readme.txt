=== Adshares ===
Contributors: adshares
Tags: adshares, ad, ads, advertising, banners, publisher, ad injection, ad inserter, ad manager
Requires at least: 4.0
Tested up to: 5.1
Stable tag: 0.1.3
Requires PHP: 5.5
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Plugin URL: https://adshares.net

The easiest way to connect your site to the Adshares network.

== Description ==

This plugin provides integration with Adshares [AdServer](https://github.com/adshares/adserver) for publishers.
All you have to do is to login into your Adshares account and select which ad units will be displayed.
It supports various options for position and visibility.

== Frequently Asked Questions ==

Post your question in the [support forum](https://wordpress.org/support/plugin/adshares)

== Installation ==

This plugin requires **PHP 5.5** or higher.

Recommended installation:

1. Go to YourWebsite->Plugins->Add New
2. Search for "Adshares"
3. Click "Install Now"

Alternative installation:

* Install with [Composer](https://getcomposer.org/): `composer require adshares/wordpress-plugin`
* [Download the latest release](https://github.com/adshares/wordpress-plugin/releases/latest)
* Clone the repo: `git clone https://github.com/adshares/wordpress-plugin.git`

Building plugin

1. Clone or download project
2. Install [Composer](https://getcomposer.org/)
3. Build distribution files:
```
composer install
composer build
```
4. Plugin files will be saved in `build/adshares` directory
5. Copy directory `build/adshares` into `wp-content/plugins`

== Screenshots ==

1. Settings page
2. Example post

== Changelog ==

= 0.1.3 =
* Updated ad insertion (prevent from AdUnit flocking)
* Updated service discovery (new INFO endpoint format)
* Fixed empty paragraph handling

= 0.1.2 =
* Fixed excerpt support

= 0.1.1 =
* Autoloading
* Cache dir

= 0.1.0 =
* Installation process
* Settings page
* Synchronization with AdServer
* Ads in posts content

Complete changelog: https://github.com/adshares/wordpress-plugin/blob/master/CHANGELOG.md

== Upgrade Notice ==

= 0.1.3 =
This version supports new protocol version. Upgrade immediately.

= 0.1.1 =
This version fixes a class loading bug. Upgrade immediately.
