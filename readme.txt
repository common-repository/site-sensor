=== Synthesis Site Sensor ===
Contributors: wpmuguru, anderswc, derickschaefer, Tony Ferrell
Tags: page views, statistics, tracking, author page views
Requires at least: 3.4.2
Tested up to: 3.5.1
Stable tag: 0.5.3

Site Sensor is a website uptime monitoring service with WordPress specific checks.

== Description ==

Site Sensor is a site uptime checking service that checks key content publishing features of WordPress sites in addition to basic UP/DOWN reporting. Sensor's WordPress plugin enables Site Sensor's uptime checker to retrieve WordPress specific integrity information including the validity of RSS Feeds, XML Sitemap, WordPress Privacy Setting, and WordPress Update status. These status are checked during uptime checking and made available in Site Sensor's dashboard and iPhone application.


Site Sensor supports:

* Validation that a site's xml sitemap matches the most recent post.
* Support for Yoast WordPress SEO Sitemaps.
* Validation that a site's RSS Feed contains the most recent post.
* WordPress update available check.
* WordPress Privacy setting warning check.
* Debug output mode for troubleshooting sitemap and RSS warnings.
* Support for RSS feeds hosted by FeedBlitz.

== Installation ==

The Site Sensor Plugin installation is a snap.

1. Upload the 'site-sensor' folder' to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Sign up for a Site Sensor account at http://websynthesis/site-sensor/
4. Configure the plugin with a validation code from your Site Sensor dashboard.
5. Disable any specific checks you are not interested in.

== Frequently Asked Questions ==

= Where can I find details about Site Sensor? =

Please visit http://websynthesis.com/site-sensor/ for details.

= Do you support News and Video Sitemaps? =

Not in this release but we will eventually.

== Screenshots ==

1. Site Sensor has one administration screen for turning WP specific checks on/off.
2. The Site Sensor service dashboard allows you to add new checks.
3. The Site Sensor service dashboard provides data regarding performance and uptime.

== Changelog ==

= 0.1 through 0.4 =

* Dev releases tested on copyblogger.com, studiopress.com, and other high traffic content publishing sites.

= 0.5 through 0.5.1 =

* Fixed links in views to point to final login destinations.
* Added support for WordPress SEO by Yoast

= 0.5.2 =

* Added debug output mode detailed instrumentaiton for detecting causes of WordPress check warnings. 

= 0.5.3 =

* Added support for FeedBlizt and legacy FeedBurner feeds.

== Support ==

Our customer support (including this plugin) is provided through the Site Sensor customer portal and dashboard. However, we will do our best to keep up with feedback in this repository.
