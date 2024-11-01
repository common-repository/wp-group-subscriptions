=== WP Group Subscriptions ===

Contributors: Hive 4 Apps
Tags: members, paid members, subscribers, group subscription, subscription form
Requires at least: 4.9
Tested up to: 5.0.2
Requires PHP: 7.0.29
Stable tag: 0.1.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accepts paying group registrations. Gives access to restricted content for members or groups of members.

== Description ==

= What is WP Group Subscription ? =WGS is a free and paid membership management solution designed from the outset to manage, in addition to traditional individual subscription, group membership registration into a single subscription.= Getting started =Once your plugin is installed and have configured it in settings, you still have a few steps to get started:1. Edit a first plan in Dashbord > Plans > New Plan and save it.2. Write inside a page or a post content the shortcode [wgs-plans][/wgs-plans] in order to display the plan on the front-end side.3. Inside an other page or post, wrap a content that should be visible only for wgs members by the shortcode [wgs-restricted][/wgs-restricted].Now, a user can access to the plan thanks to the page with [wgs-plans], click on the "Sign up" button, follow all registration steps and after to log in, see the content wrapped by [wgs-restricted].[See DOCUMENTATION](https://wp-group-subscriptions.com/?page_id=411)[See about Premium features](https://wp-group-subscriptions.com)

== Features ==* Admin	* Plans management	* Members management	* Subscribers management	* Paypal payments management* Settings	* Currency	* Paypal	* Page Profile* Front-end	* Quick display of plans on a page thanks the shortcode [wgs-plans][/wgs-plans]	* Automatic redirection on the right form (Single or Group Subscription)	* Paypal payment	* Login page	* Profile page* Security for forms	* Warning to advise administrator about enabling HTTPS for WS Form Pages	* Native client-side validation for inputs : patterns and lengths checking	* Server-side validation for inputs : patterns and lengths checking	* Repeat password	* Repeat email	* password hashing

== Installation ==1. Upload WP Group Subscriptions2. Activate the plugin through the 'Plugins' menu in WordPress3. Configure the plugin by the settings menu.4. Enjoy it!

== Frequently Asked Questions === How to display the list of plans on the front-end side? = To insert the published plans as cards on your website, you must to write the shortcode [wgs-plans][/wgs-plans] in a page or a post.= The "Sign up" plan button does not redirect to show the right form, how can I fix this? = Unfortunately, for some reasons, sometimes that happens. You need to go to Settings > Permalinks and save again. Normally, that fixes the bug.

== Screenshots ==1. plan editor admin page2. subscriber editor admin page3. member editor admin page4. display of plans list5. plan form6. profile page

== Changelog === 0.1.7 =* BUG FIX: activate/deactivate license key without an addon* ENHANCEMENT: .htaccess to block text or config files access= 0.1.6 =* BUG FIX: login/logout redirection header already sent.* ENHANCEMENT: Redirection in profile page* BUG FIX: link to sign out page in plan list when the member is logged in.* ENHANCEMENT: Link to documentation and premium features in readme.txt= 0.1.5 =* BUG FIX: Replaced some file paths in ajax function config.* FEATURE: Options to display the message or not for restricted content if the member is not logged in.* BUG FIX: Display modal for premium features in settings.= 0.1.4 =* BUG FIX: Repaired filter for forms working with the recaptcha addon* BUG FIX: Remove the modal object display in settings.js inside the browser console= 0.1.3 =* Long description in readme.txt= 0.1.2 =* Small fix in readme.txt= 0.1.1 =* FAQ in readme.txt= 0.1.0 =* First stable version with basic features* Plans, Members, Subscription management* Profile page...