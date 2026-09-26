=== Brazilian Market on WooCommerce ===
Contributors: claudiosanches, tiagosartor3
Donate link: https://apoia.se/claudiosanches?utm_source=plugin-bmw
Tags: woocommerce, checkout, brazil, cpf, cnpj
Requires at least: 6.7
Tested up to: 7.1
Stable tag: 5.0.0
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Adds CPF, CNPJ, RG, State Registration, street number, neighborhood and other checkout fields Brazilian stores need.

== Description ==

WooCommerce ships with an address form designed for the United States. Brazilian stores need more than that: a tax document to issue an invoice, a street number and a neighborhood to get a package delivered, and a phone number in the format carriers expect.

Brazilian Market on WooCommerce adds those fields to the checkout, the My Account address forms and the admin order screen, validates them, and formats them as the customer types.

= Fields you can add =

* **Person type** - lets the customer choose between individual and legal entity, showing only the documents that apply to the choice.
* **CPF** and **RG** for individuals.
* **CNPJ** and **State Registration** for companies, with Company name asked of companies only and made required for them, or left to the WooCommerce setting. Companies with no state registration can tick a box to fill it with ISENTO.
* **Birthdate** and **Gender**.
* **Cell phone**, either as an extra field or replacing the regular phone field.
* **Number** and **Neighborhood** on both billing and shipping addresses.

Every field is optional to enable. Turn on only what your store actually needs, and set RG, State Registration, Birthdate and Gender as optional or required.

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

= Shipping calculators that ask only for the CEP =

Customers type their CEP and the plugin works out the rest.

* **Cart** - the shipping calculator asks only for the CEP and fills the state and city from it. The cart block, which has no calculator since WooCommerce 10, gets one of its own in the order summary.
* **Product page** - a Shipping Calculator block lists the shipping options and prices for the product before it goes into the cart. Classic themes can show it below the add to cart button with a setting.
* **Checkout and My Account** - the street, neighborhood, city and state are filled once the customer enters a CEP, on the block and classic checkouts alike.

Every calculator links to the Correios CEP search for customers who do not know theirs.

The calculators work when WooCommerce sells and ships only to Brazil. The settings screen explains how to set that up, and can do it for you.

= WooCommerce Correios integration =

When [WooCommerce Correios](https://wordpress.org/plugins/woocommerce-correios/) is active with its Correios Web Services (CWS) connection set up, addresses are looked up through it first, using your Correios contract.

Both plugins keep the addresses they find in the same database table, so a CEP looked up by one is never fetched again by the other. The table is created when the first CEP is looked up, and left in place when this plugin is uninstalled, since WooCommerce Correios may still use it.

When the address autofill here is on, it replaces the one in WooCommerce Correios, so the address is not filled twice. It looks addresses up the same way, and fills the Neighborhood field where WooCommerce Correios would use the second address line.

= Address lookup services =

Thanks to [ViaCEP](https://viacep.com.br/) and [BrasilAPI](https://brasilapi.com.br/) for their free CEP lookup services.

A CEP that is not yet in the database, and that WooCommerce Correios could not find, is sent from your store's server to ViaCEP, and to BrasilAPI when ViaCEP does not answer. Only the CEP is sent, and only when a customer uses one of the shipping calculators or has an address filled from the CEP.

= Privacy =

The CEP a customer enters is kept in a cookie, `csbmw_postcode`, so the next product page can show its shipping options. It is deleted when the browser closes or the customer logs out. With a consent plugin that supports the [WP Consent API](https://wordpress.org/plugins/wp-consent-api/), it is kept for 30 days once the customer agrees to have preferences remembered, and deleted as soon as that consent is withdrawn.

Suggested text for your privacy policy is available under Settings > Privacy.

= Compatibility =

Compatible with High-Performance Order Storage (HPOS) and with the cart and checkout blocks.

Known to work with:

* **[WooCommerce](https://wordpress.org/plugins/woocommerce)** (requires WooCommerce 9.9 or newer)
* **[PagSeguro for WooCommerce](https://wordpress.org/plugins/woocommerce-pagseguro)** (uses **neighborhood**, **CPF**, and **street number** fields)
* **[WooCommerce Correios](https://wordpress.org/plugins/woocommerce-correios/)** (shares its CEP database and address lookup)
* **[WP Consent API](https://wordpress.org/plugins/wp-consent-api/)** (remembers the CEP for longer with consent to preferences)

= Questions? =

* Open an issue on our [GitHub repository](https://github.com/claudiosmweb/woocommerce-extra-checkout-fields-for-brazil/issues).
* Or start a thread on the [WordPress support forum](https://wordpress.org/support/plugin/woocommerce-extra-checkout-fields-for-brazil).

= Credits =

This plugin uses:

* [Mailcheck](https://github.com/mailcheck/mailcheck), to suggest corrections for misspelled email domains.
* [Heroicons](https://heroicons.com/) by Tailwind Labs, MIT license, for the shipping calculator icons.

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

The shipping calculators that ask only for the CEP are the exception: they need the store to sell and ship only to Brazil.

= How do I add the shipping calculator to product pages? =

On a block theme, open Appearance > Editor, edit the Single Product template and add the Shipping Calculator block. On a classic theme, turn on "Shipping calculator on product pages" in the plugin settings.

= How do I change the look of the shipping calculator? =

It follows your theme and WooCommerce's styles. To change a detail, add CSS in Appearance > Editor > Styles > Additional CSS, or Appearance > Customize > Additional CSS on a classic theme. For example:

    .csbmw-shipping-calculator-destination {
        border-bottom-color: #0a7d32;
    }

    .csbmw-shipping-calculator-rate-cost {
        color: #0a7d32;
    }

The main classes are:

* `.csbmw-shipping-calculator`: the product page calculator.
* `.csbmw-shipping-calculator-destination`: the line with the CEP's city.
* `.csbmw-shipping-calculator-rate`: each shipping option, with `-rate-name` and `-rate-cost` inside.
* `.csbmw-shipping-calculator-dialog`: the dialog for changing the CEP.
* `.csbmw-cart-shipping-calculator`: the cart block calculator.

= Where are the settings? =

Under WooCommerce > Brazilian Market.

= What is the plugin license? =

This plugin is licensed under the [GNU General Public License](https://www.gnu.org/licenses/gpl-3.0.html).

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
- Added shipping calculators that ask only for the CEP, on the cart, the cart block and product pages, with a Shipping Calculator block for block themes.
- Added address autofill from the CEP on the block and classic checkouts and in My Account.
- Addresses are looked up through WooCommerce Correios when it is set up for it, then ViaCEP and BrasilAPI, and cached in the table WooCommerce Correios uses.
- Added a Customer data section to the order confirmation, the order view in My Account and the order emails, listing the documents, birthdate, gender and cell phone apart from WooCommerce's additional information. Block themes get it as a Customer Data block in the order confirmation template, which can be moved or removed in the Site Editor.
- Fields filled in on the block checkout keep being saved to the historic meta keys (`_billing_cpf`, `_billing_number` and so on), preserving compatibility with gateways, ERPs and other integrations.
- Added an "Exempt from State Registration" checkbox, which fills the field with ISENTO for companies that have no state registration. (Made possible with help from [Matthieuhal](https://github.com/Matthieuhal)).
- Added support for the alphanumeric CNPJ. (Made possible with help from [Jonathan Afranio](https://github.com/jonathanafranio)).
- Added masks and validation to the Brazilian fields on the admin order screen. (Made possible with help from [Tiago Sartor](https://github.com/tiago-sartor)).
- Fixed the Brazilian fields not working on the admin order screen when High-Performance Order Storage is enabled, and the person type not switching after the customer autofill. (Made possible with help from [Tiago Sartor](https://github.com/tiago-sartor)).
- Fixed a document corrected in My Account being ignored by the block checkout, which kept prefilling the old value and wrote it back with the next order.
- Fixed "Load billing address" on the admin order screen clearing the Brazilian fields instead of filling them, and the matching Copy billing address handler never running at all. WooCommerce loads and copies these fields itself, so the plugin no longer duplicates the work.
- Added validation to the Birthdate field, which used to accept dates that do not exist.
- RG, State Registration, Birthdate and Gender can now be optional as well as required. Stores that had them turned on keep them required.
- Added a Company name setting: ask it of legal persons only, as before, or follow WooCommerce's own Company setting.
- Company name is now asked right after the CNPJ on both checkouts. On the block checkout it moves from the address form to the contact information, and is still saved as the billing company.
- The State Registration is stored in capitals, with ISENTO however it was typed, and rejected unless it is ISENTO or has 8 to 14 digits.
- Fixed the classic checkout dropping the company field for stores that accept individuals only.
- Declared WooCommerce as a required plugin, so WordPress installs and activates it with the plugin, and removed the notice that used to say it was missing.
- Fixed the email suggestion reading Brazilian domains such as `.com.br` as typos and offering to cut the country code off.
- Declared compatibility with the WooCommerce cart and checkout blocks feature.
- Fixed the "Change the label of the Phone field to Cell Phone" option doing nothing on either checkout, because WooCommerce rewrites every label from the country locale after the form loads.
- The Cell Phone field is now checked as a phone number on the block checkout, as it already was on the classic one.
- Fixed the block checkout address summary printing `{number}` and `{neighborhood}` where the values belong. WooCommerce formats that summary in the browser and replaces only the fields it ships with.
- Fixed the My Account address form saving a CPF, a CNPJ or a birthdate the checkout would have refused, which the block checkout then prefilled and carried into the next order.
- Fixed My Account listing Number and Neighborhood a second time under each address, which already shows both.
- Fixed the "Exempt from State Registration" checkbox rendering at the top of the classic checkout billing form instead of next to the field it fills.
- Fixed the order screen keeping the documents of the person type an order was moved away from, as the checkout already clears them.
- Fixed the "Exempt from State Registration" checkbox piling up on the block checkout, one copy for every person type change.
- Fixed the account details form in My Account refusing to save for a Brazilian customer, because it asked for a CPF it never showed.
- Fixed the company name missing from every Brazilian address the store renders, since the format the plugin registers replaces the one WooCommerce ships with.
- Added the date mask to the Birthdate field on the order screen, where a date typed without it was stored as it stood.
- The Gender field on the order screen is now picked from the same list as both checkouts, instead of accepting any text.
- Redesigned the settings screen, with each group of options in its own card.
- Minimum requirements raised to WordPress 6.7, PHP 7.4 and WooCommerce 9.9.
- Removed the jQuery Mask Plugin in favor of a dependency-free implementation.
- Removed support for the discontinued Flux Checkout plugin.
- Relicensed from GPLv2 or later to GPLv3 or later.

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

Adds support for the WooCommerce block checkout, keeping the same fields, masks and validation as the classic checkout. Adds shipping calculators and address autofill that ask only for the CEP. Values are still written to the historic meta keys, so gateways and other integrations are unaffected. Minimum requirements are now WordPress 6.7, PHP 7.4 and WooCommerce 9.9.
