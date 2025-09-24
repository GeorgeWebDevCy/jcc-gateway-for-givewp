=== JCC Gateway For GiveWP ===
Author URI: https://www.georgenicolaou.me/
Plugin URI: https://www.georgenicolaou.me/plugins/gncy-jcc-give-wp
Donate link: 
Contributors: 
Tags: 
Requires at least: 
Tested up to: 
Requires PHP: 
Stable tag: 1.0.4
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

JCC Payment Gateway for GiveWP

== Description ==

This is the long description.  No limit, and you can use Markdown (as well as in the following sections).

For backwards compatibility, if this section is missing, the full length of the short description will be used, and
Markdown parsed.

A few notes about the sections above:

*   "Contributors" is a comma separated list of wordpress.org usernames
*   "Tags" is a comma separated list of tags that apply to the plugin
*   "Requires at least" is the lowest version that the plugin will work on
*   "Tested up to" is the highest version that you've *successfully used to test the plugin*. Note that it might work on
higher versions... this is just the highest one you've verified.
*   Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for
stable.

    Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

    If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.


== Frequently Asked Questions ==

= A question that someone might have =

An answer to that question.


== Installation ==

1. Go to `Plugins` in the Admin menu
2. Click on the button `Add new`
3. Search for `JCC Gateway For GiveWP` and click 'Install Now' or click on the `upload` link to upload `jcc-gateway-for-givewp.zip`
4. Click on `Activate plugin`

== Changelog ==

= 1.0.4: September 26, 2025 =
* Set the default JCC donation currency fallback to EUR so Euro remains the standard when no other value is provided.

= 1.0.3: September 25, 2025 =
* Ensure the `_give_payment_currency` metadata is saved for every new JCC donation so GiveWP's Money value object always receives a valid currency code.
* Backfill the missing `_give_payment_currency` metadata for historical JCC donations when upgrading, alongside normalising the donations table currency column.

= 1.0.2: September 24, 2025 =
* Prevent fatal errors in GiveWP 3.0+ by always storing a valid currency code with JCC donations and normalising incoming form data.
* Automatically backfill the currency for historical JCC donations that were saved without a currency value.

= 1.0.1: September 24, 2025 =
* Improve compatibility with multilingual forms by detecting the correct language when redirecting donors to JCC.

= 1.0.0: January 16, 2024 =
* Birthday of JCC Gateway For GiveWP
