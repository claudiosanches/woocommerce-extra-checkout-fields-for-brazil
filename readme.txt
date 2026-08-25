=== Brazilian Market on WooCommerce ===
Contributors: claudiosanches, tiagosartor3
Donate link: https://apoia.se/claudiosanches?utm_source=plugin-bmw
Tags: woocommerce, checkout, brazil, cpf, cnpj
Requires at least: 6.7
Tested up to: 7.1
Stable tag: 5.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds CPF, CNPJ, RG, State Registration, street number, neighborhood and other checkout fields Brazilian stores need.

== Description ==

WooCommerce ships with an address form designed for the United States. Brazilian stores need more than that: a tax document to issue an invoice, a street number and a neighborhood to get a package delivered, and a phone number in the format carriers expect.

Brazilian Market on WooCommerce adds those fields to the checkout, the My Account address forms and the admin order screen, validates them, and formats them as the customer types.

= Fields you can add =

* **Person type** - lets the customer choose between individual and legal entity, showing only the documents that apply to the choice.
* **CPF** and **RG** for individuals.
* **CNPJ** and **State Registration** for companies, with Company name made required. Companies with no state registration can tick a box to fill it with ISENTO.
* **Birthdate** and **Gender**.
* **Cell phone**, either as an extra field or replacing the regular phone field.
* **Number** and **Neighborhood** on both billing and shipping addresses.

Every field is optional to enable. Turn on only what your store actually needs, and set which ones are required.

= Validation and formatting =

* CPF and CNPJ are checked against their real check digits, so typos and made-up numbers are rejected before the order is placed. Both checks are optional.
* The new alphanumeric CNPJ format is supported.
* Input masks format CPF, CNPJ, postcode, birthdate, phone and cell phone while the customer types.
* Mail check suggests a correction when an email address has a typo in the domain, such as `gmail.con`.

= Works with the block checkout =

The plugin supports both the block checkout and the classic shortcode checkout, with the same fields, masks and validation in each.

On the block checkout, fields appear and disappear as the customer picks a person type or changes country, using WooCommerce's own rules rather than custom scripts.

Values entered on the block checkout are also written to the historic meta keys (`_billing_cpf`, `_billing_number`, `_shipping_neighborhood` and the rest), so payment gateways, shipping plugins, ERPs and invoicing integrations that read them keep working with no changes.

= Address formatting =

Brazilian addresses are rendered in the local format, with the street number after the street name and the neighborhood on its own line. This applies to the order confirmation page, order emails, the admin order screen and shipping labels.

= Compatibility =

Compatible with High-Performance Order Storage (HPOS) and with the cart and checkout blocks.

Known to work with:

* **[WooCommerce](https://wordpress.org/plugins/woocommerce)** (requires WooCommerce 9.9 or newer)
* **[PagSeguro for WooCommerce](https://wordpress.org/plugins/woocommerce-pagseguro)** (uses **neighborhood**, **CPF**, and **street number** fields)

= Questions? =

* Open an issue on our [GitHub repository](https://github.com/claudiosmweb/woocommerce-extra-checkout-fields-for-brazil/issues).
* Or start a thread on the [WordPress support forum](https://wordpress.org/support/plugin/woocommerce-extra-checkout-fields-for-brazil).

= Credits =

This plugin uses [Mailcheck](https://github.com/mailcheck/mailcheck).

= Contributing =

You can contribute code on our [GitHub repository](https://github.com/claudiosmweb/woocommerce-extra-checkout-fields-for-brazil).

== Frequently Asked Questions ==

= What do I need to use this plugin? =

WooCommerce 9.9 or newer, running on WordPress 6.7 or newer with PHP 7.4 or newer.

= Does it work with the block checkout? =

Yes. All fields, masks and validations work on the block checkout and on the classic shortcode checkout.

= I already have orders and customers from an older version. Will the data still be there? =

Yes. The plugin keeps reading and writing the same meta keys it always has, so existing orders, customer addresses and integrations are unaffected.

= Can I use it on a store that also sells outside Brazil? =

Yes. Person type and the document fields can be made required only for Brazilian addresses, leaving international checkouts untouched.

= Where are the settings? =

Under WooCommerce > Settings > Checkout Fields.

= What is the plugin license? =

This plugin is licensed as GPL.

== Screenshots ==

1. Person type, CPF and RG fields on the block checkout
2. Company fields on the block checkout with Legal Person selected
3. Fields on the classic shortcode checkout
4. Billing address form in My Account
5. Brazilian fields on the admin order screen
6. Plugin settings
7. Email typo suggestion

== Changelog ==

= 5.0.0 - 2026/08/23 =

- Added support for the WooCommerce block checkout, with every field, mask and validation from the classic checkout.
- Fields filled in on the block checkout keep being saved to the historic meta keys (`_billing_cpf`, `_billing_number` and so on), preserving compatibility with gateways, ERPs and other integrations.
- Added an "Exempt from State Registration" checkbox, which fills the field with ISENTO for companies that have no state registration. (Made possible with help from [Matthieuhal](https://github.com/Matthieuhal)).
- Added support for the alphanumeric CNPJ. (Made possible with help from [Jonathan Afranio](https://github.com/jonathanafranio)).
- Added masks and validation to the Brazilian fields on the admin order screen. (Made possible with help from [Tiago Sartor](https://github.com/tiago-sartor)).
- Fixed the Brazilian fields not working on the admin order screen when High-Performance Order Storage is enabled, and the person type not switching after the customer autofill. (Made possible with help from [Tiago Sartor](https://github.com/tiago-sartor)).
- Added validation to the Birthdate field, which used to accept dates that do not exist.
- Declared WooCommerce as a required plugin, so WordPress installs and activates it with the plugin, and removed the notice that used to say it was missing.
- Fixed the email suggestion reading Brazilian domains such as `.com.br` as typos and offering to cut the country code off.
- Declared compatibility with the WooCommerce cart and checkout blocks feature.
- Minimum requirements raised to WordPress 6.7, PHP 7.4 and WooCommerce 9.9.
- Removed the jQuery Mask Plugin in favor of a dependency-free implementation.
- Removed support for the discontinued Flux Checkout plugin.

= 4.0.2 - 2024/02/17 =

- Fixed CPF/CNPJ validation.

= 4.0.1 - 2024/02/17 =

- Declared support for WooCommerce 8.6+ and WordPress 6.4+.

= 4.0.0 - 2023/11/06 =

- Added a new option for the field style, now defaulting to full width to prevent incompatibilities with themes and plugins.
- Improved the cell phone field option, which can now replace the phone field.
- Split Birthdate and Gender into their own fields.
- Updated the `_sex` suffix to `_gender` in the database.
- Fixed a bug that left the Company name field always optional.

= 3.10.0 - 2023/10/30 =

- Added "Prefer not to say" and "Other" as options for the gender field.

= 3.9.1 - 2023/10/29 =

- Improved how the plugin handles requiring the individual and legal entity fields.

= 3.9.0 - 2023/10/29 =

- Added support for the Flux Checkout for WooCommerce plugin.
- Added support for WooCommerce 8.2+.
- Added an option to control whether the Neighborhood field is required.
- Added a rule to ignore the Company field requirement when CPF is selected at checkout.

= 3.8.4 - 2023/09/25 =

- Added support for WooCommerce 8.1+.

= 3.8.3 - 2023/09/13 =

- Added support for WooCommerce HPOS.

= 3.8.2 - 2023/05/01 =

- The email suggestion can now be translated.

= 3.8.1 - 2023/05/01 =

- Updated translation file.

= 3.8.0 - 2023/05/01 =

- Added support for current WooCommerce versions.
- Dropped support for WooCommerce versions older than 3.0.
- Fixed how masks are applied at checkout.
- Fixed the values returned to the `woocommerce_ajax_get_customer_details` hook.

= 3.7.2 - 2019/09/26 =

- Renamed the plugin from "WooCommerce Extra Checkout Fields for Brazil" to "Brazilian Market on WooCommerce".
- Removed the obsolete address autofill option; use the built-in integration in the "Claudio Sanches - Correios for WooCommerce" plugin instead.
- Fixed a WooCommerce bug affecting how the shipping address is displayed in the admin order list.

= 3.7.1 - 2019/09/24 =

- Fixed a WooCommerce bug affecting how the shipping address is displayed in the admin order list.

= 3.7.0 - 2019/09/20 =

- Renamed the plugin from "WooCommerce Extra Checkout Fields for Brazil" to "Brazilian Market on WooCommerce".
- Removed the obsolete address autofill option; use the built-in integration in the "Claudio Sanches - Correios for WooCommerce" plugin instead.

= 3.6.1 - 2018/05/24 =

- Fixed messages saying some fields were optional even when marked as required.

= 3.6.0 - 2017/05/12 =

- Changed the position of every form field to work better with themes that are not yet fully compatible with WooCommerce 3.0.

= 3.5.1 - 2017/04/26 =

- Fixed the postcode field position.
- Fixed CNPJ validation.

= 3.5.0 - 2017/03/04 =

- Added support for WooCommerce 3.0.
- Switched the mask library from [jquery.maskedinput](https://github.com/digitalBush/jquery.maskedinput) to [jquery.mask](https://github.com/igorescobar/jQuery-Mask-Plugin). (Made possible with help from [Thiago Guimarães](https://github.com/thiagogsr)).
- Fixed the phone and cell phone masks, allowing 10 or 11 digits without changing the user experience. (Made possible with help from [Thiago Guimarães](https://github.com/thiagogsr)).
- Changed the phone, cell phone, postcode and birthdate fields to type `tel` when the selected country is BR. (Made possible with help from [Thiago Guimarães](https://github.com/thiagogsr)).
- Changed the CPF and CNPJ fields to type `tel` and email to type `email`. (Made possible with help from [Thiago Guimarães](https://github.com/thiagogsr)).

== Upgrade Notice ==

= 5.0.0 =

Adds support for the WooCommerce block checkout, keeping the same fields, masks and validation as the classic checkout. Values are still written to the historic meta keys, so gateways and other integrations are unaffected. Minimum requirements are now WordPress 6.7, PHP 7.4 and WooCommerce 9.9.
