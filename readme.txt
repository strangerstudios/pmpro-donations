=== Paid Memberships Pro - Donations ===
Contributors: strangerstudios
Tags: paid memberships pro, pmpro, membership, donate, donations, gifts, charity, charities
Requires at least: 4
Tested up to: 5.5
Stable tag: 1.1

Allow customers to set an additional donation amount with customized minimum, maxium, and suggested amounts via dropdown at checkout.

== Description ==

This plugin requires Paid Memberships Pro.

== Installation ==

1. Upload the `pmpro-donations` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Edit the levels you want to add donations to and set the "Donation" settings.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/strangerstudios/pmpro-donations/issues

== Changelog ==
= 1.1 - 2020-08-12 =
* FEATURE: Added !!donation!! email template variable.
* BUG FIX/ENHANCEMENT: Now only starting sessions on checkout page, which resolves warning from Site Health tool.
* BUG FIX/ENHANCEMENT: Now properly stripping slashes in donation "Help Text" setting.
* BUG FIX/ENHANCEMENT: Resolved PHP notice when loading the checkout page with 'Other' as a dropdown donation option.
* BUG FIX/ENHANCEMENT: Resolved PHP warning when loading a level's donation settings for the first time.

= 1.0 - 2020-04-09 =
* FEATURE: Added "Donations-Only Level" option.
* BUG FIX: Fixed bug where donation was not being added to order notes.
* BUG FIX: Now correctly hiding 'Other' option in donation dropdown menu if 'Other' is not allowed.
* BUG FIX: Fixed regular expression in pmprodon_getPriceComponents().
* BUG FIX/ENHANCEMENT: Fixed some strings that were using the wrong text domain and added translation files.
* REFACTOR: Organized code into separate files.

= .5 =
* BUG FIX: Making sure session is started before interacting with session vars.
* BUG FIX: Keeping billing address fields visible when switching to pay by check option.
* BUG FIX: No longer allowing negative donations if a minimum value wasn't set.
* ENHANCEMENT/FIX: Wouldn't always substitute text properly when translated
* ENHANCEMENT/FIX: PHP Warnings during checkout
* ENHANCEMENT: Improved fields display on membership checkout page to use no tables for compatibility with Paid Memberships Pro v1.9.4.

= .4 =
* ENHANCEMENT: Layout improvements to donations field on membership checkout.
* ENHANCEMENT: Updating links to add on documentation and plugin author to PMPro.

= .3.2 =
* ENHANCEMENT: Wrapped all text to allow translation.

= .3.1 =
* BUG: Fixed bug where donation dropdown amount was not set correctly when returning from PayPal to review. (Thanks, lok1728 on GitHub)
* BUG: Fixed bug where donation amount was not being passed correctly to PayPal.

= .3 =
* Added support for PayPal express by saving donation amount into a session variable.

= .2.2 =
* Fixed pricing conflict with PMPro Variable Pricing addon.

= .2.1 =
* Removed extra </strong> from the order bullets.

= .2 =
* Fixed the plugin URI
* Added the pmpro_donations_get_price_components filter to adjust components (e.g. if you are saving your own itemized prices)

= .1 =
* This is the initial version of the plugin.
