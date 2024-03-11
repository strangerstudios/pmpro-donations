=== Paid Memberships Pro - Donations ===
Contributors: strangerstudios
Tags: paid memberships pro, pmpro, membership, donate, donations, gifts, charity, charities
Requires at least: 4
Tested up to: 6.4
Stable tag: 2.0

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
= 2.0 - 2024-03-08 =
* FEATURE: Donation levels can now have a "donation confirmation message" that will be shown on the confirmation page after a user donates. #64 (@MaximilianoRicoTabo)
* ENAHNCEMENT: Order donation amounts are now stored in order meta. #66 (@MaximilianoRicoTabo)
* ENHANCEMENT: Added a new panel for "Donations Settings" when editing a membership level. #65 (@MaximilianoRicoTabo)
* ENHANCEMENT: Added the `pmpro_alter_price` class to donation fields at checkout to detect when the checkout level price changes. #70 (@MaximilianoRicoTabo)
* BUG FIX/ENHANCEMENT: Improved the sanitization of some `$_REQUEST` variables. #68 (@MaximilianoRicoTabo)
* DEPRECATED: Donation amounts are no longer stored in order notes. Existing donation data in order notes will be gradually migrated to order meta as the donation data is accessed. #66 (@MaximilianoRicoTabo)

= 1.1.3 - 2023-09-06 =
* BUG FIX: Fixing issue where donations would not be charged when using the PayPal Express gateway. #63 (@dparker1005)

= 1.1.2 - 2023-08-15 =
* ENHANCEMENT: Added a new filter `pmpro_donations_invoice_bullets` to filter the bullet points that are added to the PMPro Invoice page. #55 (@MaximilianoRicoTabo, @dparker1005)
* ENHANCEMENT: Now wrapping the donation prompt text at checkout in a `<span>` tag so that it can be styled with CSS. #39 (@elementswebsolutions)
* ENHANCEMENT: Updating `<h3>` tags to `<h2>` tags for better accessibility. #50 (@ipokkel)
* ENHANCEMENT: Now preventing autocompletion of donation field at checkout. #45 (@MaximilianoRicoTabo)
* BUG FIX/ENHANCEMENT: Fixing paragraph spacing issues with custom help text by switching to using `wp_editor()`. #58 (@dparker1005, @MaximilianoRicoTabo)
* BUG FIX/ENHANCEMENT: Now always allowing users to renew a donations-only level. #60 (@dparker1005)
* BUG FIX: Added delimiters to regular expressions. Fixes issue where donation inputs may be ignored if non-numeric characters were included. #47 (@becleung)
* BUG FIX: Fixing issue where payment fields may not show if a non-zero donation dropdown option is selected. #59 (@dparker1005)
* BUG FIX: Now correctly sending the “Checkout - Paid" email template if a non-zero donation was added to a free level at checkout. #61 (@dparker1005)
* BUG FIX: No longer storing donation amounts in `$_SESSION` which was causing issues on some sites. #36 (@MaximilianoRicoTabo)
* REFACTOR: No longer pulling the checkout level from the `$_REQUEST` variable. #46 (@dparker1005)
* REFACTOR: Now using the function `get_option()` instead of `pmpro_getOption()`. #48 (@michaelbeil)


= 1.1.1 - 2020-08-12 =
* BUG FIX: Resolved issue where !!donation!! email variable would not be replaced if user did not donate.

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
