=== WooCommerce - ActiveCampaign ===
Contributors: equalserving
Donate link: https://equalserving.com/donate
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html
Tags: woocommerce, activecampaign
Requires at least: 4.4
Requires PHP: 5.3
Tested up to: 6.6.2
Stable tag: 2.1.5
WC requires at least: 3.6
WC tested up to: 9.3.3

Easily add ActiveCampaign integration to WooCommerce.

== Description ==

Integrates WooCommerce with ActiveCampaign by adding customers to ActiveCampaign at time of purchase.

Easily tag your customers with product tags so that Automations can be triggered once the purchase is made.

= Support =
The EqualServing team does not always provide active support for the WooCommerce ActiveCampaign plugin on the WordPress.org forums. One-on-one email support is available at [EqualServing Help Desk](https://equalserving.com/support).

= Opt-in On Checkout =
If configured, add a checkbox on your WooCommerce Checkout page prompting your customers to subscribe to your newsletter or email updates.

= Tag Customer With Products Purchased =
If configured, tag your customers with the ids of products they purchased so that ActiveCampaign Automations can be triggered.

= Purchased Product Additional Tags =
If this field is blank, the option is ignored. If not blank, the contact will be tagged with the information provided. Use commas to separate tags. If you would like to tag contacts with the product SKU or product category. Use placeholder #SKU# for the product SKU and/or #CAT# for the product category. If you would like to assign both separate the items in the field above with a comma. EXAMPLE: sku: #SKU#, category: #CAT#.

= More Information About ActiveCampaign =
Do you want to know more about ActiveCampaign?
Go to The [ActiveCampaign Website](https://equalserving.com/likes/activecampaign.com).

== Installation ==

= From within WordPress =
1. Visit 'Plugins > Add New'
2. Search for 'WooCommerce ActiveCampaign'
3. Activate WooCommerce ActiveCampaign from your Plugins page.
4. Go to "after activation" below.

= After activation =
1. Mouse over the WooCommerce menu item and select Settings.
2. Click on the Integration tab.
3. The ActiveCampaign configuration panel should display. If other integrations are available, just select the link labled 'ActiveCampaign.'
4. Enable the plugin and enter the necessary API information.
5. You're done!

== Frequently Asked Questions ==

= Where can I get support? =
You'll find answers to many of your questions on [EqualServing Help Desk](https://equalserving.com/support).

= Is ActiveCampaign Free? =
No! ActiveCampaign does have a free trial account where you can test for yourself the robust application. [For more information about ActiveCampaign](https://equalserving.com/likes/activecampaign)

== Screenshots ==

1. The WooCommerce ActiveCampaign plugin general options configuration. You'll get to this screen by mousing over (1) WooCommerce menu item, select (2) Settings, click on (3) Integration tab and (4) be sure that the ActiveCampaign configuration screen is displayed.
2. The WooCommerce Checkout page shown with opt-in field. If enabled, this is how the opt-in
will display on the WooCommerce Checkout page.
3. Test contact in ActiveCampaign shown with product tag assigined and subscribed to selected list.

== Support ==
The EqualServing team does not always provide active support for the WooCommerce ActiveCampaign plugin on the WordPress.org forums. One-on-one email support is available at [EqualServing Help Desk](https://equalserving.com/support).

== Changelog ==

= 2.1.5 =

Release Date: Sep 27, 2024

* Remove dynamically created properties.

= 2.1.4 =

Release Date: Mar 15, 2024

* Fix uncaught error issue.

= 2.1.3 =

Release Date: Nov 29, 2023

* Remove encoding of name and email address.

= 2.1.2 =

Release Date: Nov 20, 2023

* Modify error handling.

= 2.1.1 =

Release Date: Nov 20, 2023

* Verify compatibility with Wordpress version 6.4.1.

= 2.1.0 =

Release Date: Nov 13, 2023

* Update for WooCommerce High-Performance Order Storage (HPOS) compatibility.
* Expand error handling.

= 2.0.3 =

Release Date: Sep 6, 2022

* Correct typo.

= 2.0.2 =

Release Date: Sep 6, 2022

* Verify compatibility with Wordpress version 6.0.2.

= 2.0.1 =

Release Date: Jun 30, 2022

* To adhere to GDPR - only customers that opt-in to your newsletter will be added to ActiveCampaign. For those users selling products or services that require email addresses to send essential product information to the customer, a new option has been added to the 'Display Opt-In Field'. The option to select for these types of shops is 'Visible. Must collect email address to send essential product information. Unchecked by default.' They will be added to ActiveCampaign but unless they opt-in for your newsletter, they will not be tagged with the tag newsletter_opt_in.
* All customers who check the newsletter opt in box will be tagged with newsletter_opt_in.
* Fix PHP 8.0: Deprecate required parameters after optional parameters in function/method signatures

= 2.0.0 =

Release Date: Apr 5, 2022

* All customers will be added to ActiveCampaign regardless if they check the newsletter opt in box.
* All customers who check the newsletter opt in box will be tagged with newsletter_opt_in.
* Removed checkbox field Tag Products Purchased. If the field Purchased Product Tags is not blank, tags will be added based on product-based placeholders.
* Removed field Purchased Product Tag Prefix. All tags will be generated using placeholders specified in Purchased Product Tags field.
* Added #TAG# as placeholder. Contact will be tagged with the name of the WooCommerce tag assigned to the product.
* Added #PAYMETHOD# as placeholder. Contact will be tagged with the name of the payment method they used to make the purchase.
* Corrected Notice: id was called incorrectly.

= 1.9.15 =

Release Date: Jul 13, 2020

* Bug fix woocommerce_update_options_integration - thank you @Hetty.

= 1.9.14 =

Release Date: Mar 4, 2020

* Increased wp_remote_post and wp_remote_get timeout from 5 sec to 30 sec.

= 1.9.13 =

Release Date: Nov 6, 2019

* Enhance error trapping.

= 1.9.12 =

Release Date: Sep 26, 2019

* To avoid conflicts with other plugins using the ActiveCampaign API, the plugin now uses namespace. Please do not update unless you are running PHP version 5.3 or greater.
* The next major release of this plugin, will no longer us the field Purchased Product Tag Prefix as it has become redundant. Please review the note on the admin panel and make the necessary changes.

= 1.9.11 =

Release Date: Aug 20, 2019

* Renamed ActiveCampaign exception classes to avoid conflicts with other ActiveCampaign related plugins.

= 1.9.10 =

Release Date: Aug 18, 2019

* Renamed ActiveCampaign classes to avoid conflicts with other ActiveCampaign related plugins.
* Orders can be submitted to ActiveCampaign on multiple statuses instead of just one. Previous versions of the plugin allowed you to select created, processing, or completed as the order status to submit contact details to ActiveCampaign. You can now submit on multiple status including - on hold, cancelled, refunded and failed.
* Added tagging for order status - submitted tags can now include order status.
* Subscribe to list checkbox can be moved to above the Place Order button.

= 1.9.9 =

Release Date: Jun 10, 2019

* Fix transient variable name. Thank you RobertMJr.

= 1.9.8 =

Release Date: Mar 9, 2019

* The last bug fix introduced a typo that was corrected in this release.

= 1.9.7 =

Release Date: Mar 8, 2019

* Fix bug that prevented opt in checkbox from displaying and/or move the opt in checkbox above the Order Notes section.

= 1.9.6 =

Release Date: Nov 28, 2018

* Changes to the implementation of Display Opt-In Field. Site owners should review these options.

= 1.9.5 =

Release Date: Nov 27, 2018

* Fixed a bug that prevented customers from being added to ActiveCampaign when the Subscribe Event was set to Order Completed.

= 1.9.4 =

Release Date: Nov 22, 2018

* Opt in field now has three configuration options and can be positioned above or below Order Notes field.

= 1.9.2 / 1.9.3 =

Release Date: Nov 5, 2018

* Enable debugging log.

= 1.9.1 =

Release Date: Nov 3, 2018

* Display more useful error messages.

= 1.9 =

Release Date: Oct 7, 2018

* Add a link to reset ActiveCampaign Lists and Tags dropdowns.

= 1.8 =

Release Date: May 10, 2018

* Bug fix: Contact tags were not being applied. Error reported: Tag contact failed.
* Fixed calls to deprecated WooCommerce methods.

= 1.7 =

Release Date: May 6, 2018

* Contact Tag: Permit the possiblity of not assigning any tags at all.
* Purchased Product Additional Tags: fix bug that applied category tags but prevented sku tags when #CAT# placeholder was not used.

= 1.6 =

Release Date: April 22, 2018

* Add ability to track purchases by SKU and/or category.
* Add ability to assign a tag to all contacts making a purchase via WooCommerce.

= 1.5 =

Release Date: February 26, 2018

* Provide more informative error messages.

= 1.4 =

Release Date: January 9, 2018

* Capture error from Connector Class.

= 1.3 =

Release Date: August 21, 2017

* Added error logging. API errors generated will appear in WooCommerce | Status | Logs

= 1.2 =

Release Date: August 14, 2017

* Added error check in Connector.class.php

= 1.1 =

Release Date: March 18th, 2017

* Changes to readme file.

= 1.0 =

Release Date: February 3nd, 2017

* Initial release.

== Upgrade Notice ==

= 2.0 =

2.0 This update no longer uses the checkbox field Tag Products Purchased and Purchased Product Tag Prefix. All tags will be generated using the placeholders specified in Purchased Product Tags field only.
