# Contributing

Development notes for the plugin. The user facing description lives in
`readme.txt`, which `README.md` is generated from.

## Requirements

| Tool | Version |
| --- | --- |
| PHP | 7.4 or newer |
| Node | 20 or newer |
| Composer | 2 |
| Docker | required by `wp-env` for the integration and end to end suites |

`composer.json` pins `config.platform.php` to `7.4.33`, so Composer resolves
dependencies against the oldest PHP the plugin supports no matter which version
you run locally.

## Getting started

```sh
npm install
composer install
npm run build
```

`build/` holds the compiled assets and is not committed, so a fresh clone has to
be built before the plugin will load its scripts.

Use `npm start` while working on assets. It rebuilds on save.

## Environment

Integration and end to end tests run against
[`@wordpress/env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/),
which installs WordPress and WooCommerce in Docker.

```sh
npm run env start     # http://localhost:8977, tests on :8978
npm run env stop
npm run env -- run cli wp option get wcbcf_settings --format=json
```

The plugin settings are one option, `wcbcf_settings`, so a scenario is set up by
writing it directly:

```sh
npm run env -- run cli wp option update wcbcf_settings --format=json \
  '{"person_type":"1","rg":"1","ie":"1","birthdate":"1","gender":"1","cell_phone":"1","maskedinput":"1","mailcheck":"1","validate_cpf":"1","validate_cnpj":"1"}'
```

## Tests

```sh
npm test                     # JS unit tests and PHP unit tests
npm run test:unit            # Jest
npm run test:php             # PHPUnit, no WordPress
npm run test:php-integration # PHPUnit inside wp-env, needs it running
npm run test:e2e             # Playwright, needs wp-env running
npm run test:e2e:ui          # the same with the Playwright inspector
```

| Suite | Location | What it covers |
| --- | --- | --- |
| Jest | `tests/js/` | Masking, validation, the mail suggestion and the State Registration exemption, as pure functions and against jsdom |
| PHPUnit unit | `tests/phpunit/unit/` | CPF, CNPJ and date validation. Runs without WordPress |
| PHPUnit integration | `tests/phpunit/integration/` | Field registration across every settings combination, the conditional rules, and the meta written on both stores |
| Playwright | `tests/e2e/` | Both checkouts, My Account and the Store API, in Google Chrome |

The end to end suite drives real Google Chrome rather than bundled Chromium.
Set `CHROME_PATH` to use a local binary; CI installs one with
`npx playwright install --with-deps chrome`.

Playwright runs with a single worker. The suites share one store, so parallel
runs would fight over the same orders and settings.

## Linting

```sh
npm run lint      # JS, styles and PHP
npm run fix:js
npm run fix:css
npm run fix:php
```

`npm run build:pot` regenerates the translation template, and `npm run build:md`
regenerates `README.md` from `readme.txt`. Run the latter whenever `readme.txt`
changes.

## Layout

```
assets/js/shared/     modules shared by both checkouts
assets/js/blocks/     block checkout enhancements
assets/js/frontend/   classic checkout, jQuery based
assets/js/admin/      order screen and settings
includes/             plugin classes
```

Notable classes:

- `Extra_Checkout_Fields_For_Brazil_Blocks` registers the fields on the block
  checkout and builds the rules that show, hide and require them.
- `Extra_Checkout_Fields_For_Brazil_Legacy_Sync` mirrors those values into the
  historic meta keys and seeds the block fields from them.
- `Extra_Checkout_Fields_For_Brazil_Front_End` handles the classic checkout.
- `Extra_Checkout_Fields_For_Brazil_Formatting` holds the CPF, CNPJ and date
  validation, and is the only class the unit suite loads.

## Field storage

Every field is stored twice, and both copies matter.

The block checkout stores values under its own keys, `_wc_billing/csbmw/number`
and `_wc_other/csbmw/cpf` among them. The plugin has always stored them under
`_billing_cpf` and `_billing_number` on orders, and `billing_cpf` on customers,
which is what gateways, shipping plugins and ERPs read.

`Legacy_Sync` keeps the two in step. When adding a field, register it in
`Blocks::CONTACT_FIELDS` or `Blocks::ADDRESS_FIELDS` so the mirror picks it up,
and add a case to `LegacySyncTest` covering both directions.

Values that belong to a person type the customer moved away from are cleared
before the order is saved. WooCommerce only drops hidden fields when the
`experimental-blocks` feature is on, which is off by default, so without that
step an abandoned CNPJ would be stored unvalidated.

## Conventions

- PHP follows WordPress coding standards, checked by `.phpcs.xml`.
- JavaScript and styles follow the `@wordpress/scripts` configuration.
- Anything user facing has to be translatable, using the
  `woocommerce-extra-checkout-fields-for-brazil` text domain.
- Fields are registered on `init` at priority 20, after the text domain is
  loaded, so labels and option values are translated.

## Pull requests

Include a test for the behaviour you change. If a fix cannot be covered by the
unit suites, add an end to end case: several bugs in this plugin only appear
once a real browser and a real order are involved.
