# Integrating with Brazilian Market on WooCommerce

Também disponível em [português](developers.pt-BR.md).

For payment gateways, shipping plugins, ERPs, invoicing and other code that reads or writes
the data this plugin collects. It describes version 5.0.0 and later. Where an older version
differs, it says so.

## Where the data lives

Every field is stored under a historic meta key, the one integrations have always read. The
block checkout also keeps its own copy under a namespaced key, and the plugin keeps the two
in step.

Orders prefix the historic keys with an underscore; customers do not. Contact fields are
stored against billing.

| Order meta | Customer meta | Block field | Value |
| --- | --- | --- | --- |
| `_billing_persontype` | `billing_persontype` | `csbmw/persontype` | `1` individual, `2` company |
| `_billing_cpf` | `billing_cpf` | `csbmw/cpf` | As typed, such as `529.982.247-25` |
| `_billing_rg` | `billing_rg` | `csbmw/rg` | As typed |
| `_billing_cnpj` | `billing_cnpj` | `csbmw/cnpj` | As typed, such as `11.222.333/0001-81`. Alphanumeric CNPJs have letters |
| `_billing_ie` | `billing_ie` | `csbmw/ie` | Uppercase without spaces, or `ISENTO` |
| `_billing_birthdate` | `billing_birthdate` | `csbmw/birthdate` | `dd/mm/yyyy` |
| `_billing_gender` | `billing_gender` | `csbmw/gender` | The label in the store's language, such as `Feminino` |
| `_billing_cellphone` | `billing_cellphone` | `csbmw/cellphone` | See [Phones](#phones) |
| `_billing_number`, `_shipping_number` | `billing_number`, `shipping_number` | `csbmw/number` | See [Addresses](#addresses) |
| `_billing_neighborhood`, `_shipping_neighborhood` | `billing_neighborhood`, `shipping_neighborhood` | `csbmw/neighborhood` | See [Addresses](#addresses) |
| `_billing_cnpj_lookup` | | | See [CNPJ registration lookup](#cnpj-registration-lookup) |

The company is WooCommerce's own billing company. When the store asks it of companies only,
the block checkout collects it as `csbmw/company` and saves it as the billing company.

Documents are saved as the customer typed them, formatted by the input masks when those are
on. Strip everything but letters and digits before comparing. Orders placed before 5.0.0
may hold a birthdate in another format, such as `yyyy-mm-dd`.

The person type is saved only when the store lets the customer choose between an individual
and a company. When it does, an individual's order and customer record keep no CNPJ or State
Registration, and a company's keep no CPF or RG. While the company name is asked of companies
only, an individual keeps no company either.

### Block checkout keys

The block checkout stores its copy as `_wc_other/csbmw/cpf` for contact fields, and
`_wc_billing/csbmw/number` or `_wc_shipping/csbmw/number` for address fields, on the order
and on the customer. Gender holds a stable key there: `female`, `male`, `other` or
`prefer_not_to_say`.

WooCommerce reads these keys to prefill the block checkout and to show the fields on the
order confirmation page. Everything else, including the order screen, emails and the REST
API, reads the historic keys.

### Writing the data

On an order, write the historic keys. An order edited on the order screen gets its block keys
updated from them.

On a customer, write both. The block checkout prefills from its own key and falls back to the
historic one only when its own is empty, so a customer who has checked out on the block
would otherwise see the old value.

## REST API

Orders (`/wc/v3/orders`) and customers (`/wc/v3/customers`) return these fields.

| Field | Value |
| --- | --- |
| `billing.persontype` | `F` for an individual, `J` for a company. When the store asks only one type, that type. Empty when the store does not ask |
| `billing.cpf`, `billing.cnpj`, `billing.rg`, `billing.ie` | Without dots, slashes and dashes. An alphanumeric CNPJ keeps its letters |
| `billing.birthdate` | Such as `1990-01-15T00:00:00`. Empty when the stored date is not recognised |
| `billing.gender` | First letter of the stored label: `F`, `M`, `O`, and `P` or `N` for "Prefer not to say" in English or Portuguese |
| `billing.cellphone` | As stored |
| `billing.number`, `shipping.number` | As stored |
| `billing.neighborhood`, `shipping.neighborhood` | As stored |
| `billing.phone_e164`, `billing.cellphone_e164`, `shipping.phone_e164` | See [Phones](#phones) |
| `billing.cnpj_lookup` | Orders only. See [CNPJ registration lookup](#cnpj-registration-lookup) |

Before 5.0.0, `billing.birthdate` gave the month first, such as `01-15-1990T00:00:00`.

The legacy API (`/wc-api/v3`) returns the same fields under `billing_address` and
`shipping_address`, except `cnpj_lookup`, with the birthdate in that API's date format.

These fields are read only. WooCommerce saves only the address keys it has setters for, and
drops the rest. Write the meta keys through `meta_data` instead, such as with
`PUT /wp-json/wc/v3/orders/123`:

```json
{
	"meta_data": [
		{ "key": "_billing_persontype", "value": "1" },
		{ "key": "_billing_cpf", "value": "529.982.247-25" },
		{ "key": "_billing_number", "value": "100" },
		{ "key": "_billing_neighborhood", "value": "Centro" }
	]
}
```

For a customer, use `billing_cpf` and the other customer keys, and the block keys as
described in [Writing the data](#writing-the-data).

## Store API

A headless checkout posting to `/wc/store/v1/checkout` sends the contact fields in
`additional_fields` and the address fields inside each address:

```json
{
	"billing_address": {
		"first_name": "Maria",
		"address_1": "Avenida Paulista",
		"csbmw/number": "1000",
		"csbmw/neighborhood": "Bela Vista",
		"city": "São Paulo",
		"state": "SP",
		"postcode": "01310-100",
		"country": "BR"
	},
	"additional_fields": {
		"csbmw/persontype": "1",
		"csbmw/cpf": "529.982.247-25"
	}
}
```

A field exists only while the store has it turned on, and is validated as on the checkout.
The block checkout asks for the person type and documents in the contact step, headed
Customer details.

## Addresses

Number and Neighborhood are asked of Brazilian addresses only. Billing and shipping each
follow their own country, so both are empty on a foreign address.

`_billing_number` and `_shipping_number` hold digits only, or the store's No number value
when the customer ticks No number. That value is `S/N` unless the store changed it.

## Phones

A Brazilian number on a Brazilian address is saved as `(11) 98765-4321`, or
`+55 (11) 98765-4321` when the Brazilian phone format setting asks for the country code.
Every other number is saved with its country code, such as `+44 20 7946 0958`.

Orders and customers rewrite a phone when saved only if the phone or its address country
changed and it is a complete number. A number that is not complete, or was saved by an older
version, is left as it is.

`phone_e164` and `cellphone_e164` in the REST API are parsed from whatever was stored, such as
`+5511987654321`, and are empty when the number is not complete.

Phone lengths are checked through WooCommerce's `woocommerce_validate_phone` filter, which
needs WooCommerce 11.0 or later.

## CNPJ registration lookup

When the store turns it on, the CNPJ of a company is looked up at Receita Federal through
BrasilAPI, and OpenCNPJ when BrasilAPI does not answer. The result is saved on the order as
`_billing_cnpj_lookup`:

| Key | Value |
| --- | --- |
| `cnpj` | The CNPJ looked up, letters and digits only |
| `status` | `active`, `inactive`, `not_found`, or `unavailable` when no service answered |
| `situation` | As Receita Federal writes it: `ATIVA`, `SUSPENSA`, `INAPTA`, `BAIXADA` or `NULA`. Empty unless found |
| `provider` | The service that answered, such as `brasilapi` or `opencnpj`. Empty when none did |
| `checked_at` | When it was looked up, in UTC, such as `2026-09-27T14:05:00Z` |

`billing.cnpj_lookup` in the REST API gives the same keys except `cnpj`. It is null when the
order was not looked up, or when its CNPJ was changed afterwards.

Results are cached per CNPJ: a day when found, an hour when not found. An unanswered lookup is
not cached.

## Hooks

### Fields

| Filter | Arguments | Where |
| --- | --- | --- |
| `wcbcf_billing_fields` | `$fields` | Classic checkout and My Account billing address form |
| `wcbcf_shipping_fields` | `$fields` | Classic checkout and My Account shipping address form |
| `wcbcf_admin_billing_fields` | `$fields` | Order screen billing address |
| `wcbcf_admin_shipping_fields` | `$fields` | Order screen shipping address |
| `wcbcf_customer_meta_fields` | `$fields` | User profile screen |
| `wcbcf_disable_checkout_validation` | `$disabled` | See below |
| `csbmw_order_customer_data` | `$fields`, `$order` | Order confirmation, My Account order view and emails, on both checkouts |

The block checkout registers its fields through WooCommerce's additional checkout fields.
Change them with WooCommerce's own hooks, such as `woocommerce_validate_additional_field`,
`woocommerce_sanitize_additional_field` and `woocommerce_get_default_value_for_{$field_id}`.

`wcbcf_disable_checkout_validation` returning true skips the classic checkout's and the My
Account address form's checks of documents, number, birthdate and CNPJ registration, and
every phone length check. The block checkout still checks documents, number and birthdate as
its settings say.

```php
// Add the ERP's customer code to order confirmations and emails.
add_filter(
	'csbmw_order_customer_data',
	function ( $fields, $order ) {
		$fields[] = array(
			'label' => 'Código do cliente',
			'value' => $order->get_meta( '_erp_customer_code' ),
		);

		return $fields;
	},
	10,
	2
);
```

### CEP lookup

These apply to every calculator and to the address autofill on both checkouts and My Account.

| Filter | Arguments | Use |
| --- | --- | --- |
| `csbmw_postcode_services` | `$services` | Services asked for a CEP not yet stored, in order |
| `csbmw_postcode_address` | `$address`, `$postcode` | Address found for a CEP, or null |
| `csbmw_find_postcode_url` | `$url` | Link offered to customers who do not know their CEP |

A service is a callback that takes the CEP's eight digits and returns an array with
`postcode`, `address`, `neighborhood`, `city` and `state`, false when it knows the CEP does
not exist, or null when it could not answer. An address without a city or a valid state is
ignored.

```php
// Ask an internal service before the public ones.
add_filter(
	'csbmw_postcode_services',
	function ( $services ) {
		return array(
			'internal' => function ( $postcode ) {
				$response = wp_remote_get( 'https://cep.example.com/' . $postcode );

				if ( is_wp_error( $response ) ) {
					return null;
				}

				if ( 404 === wp_remote_retrieve_response_code( $response ) ) {
					return false;
				}

				$data = json_decode( wp_remote_retrieve_body( $response ), true );

				return array(
					'postcode'     => $postcode,
					'address'      => $data['street'],
					'neighborhood' => $data['district'],
					'city'         => $data['city'],
					'state'        => $data['uf'],
				);
			},
		) + $services;
	}
);
```

### Product page shipping estimates

These apply to the Shipping Calculator block and the calculator below the add to cart button.

| Filter | Arguments | Use |
| --- | --- | --- |
| `csbmw_shipping_estimate_package` | `$package`, `$product`, `$quantity` | Package the estimate is calculated for |
| `csbmw_shipping_estimate_cache_ttl` | `$ttl`, `$product` | Seconds an estimate is reused, an hour by default. 0 calculates every time |

```php
// Skip the cache for products a supplier ships.
add_filter(
	'csbmw_shipping_estimate_cache_ttl',
	function ( $ttl, $product ) {
		return $product->get_meta( '_ships_from_supplier' ) ? 0 : $ttl;
	},
	10,
	2
);
```

### CNPJ registration lookup

These apply to both checkouts and the My Account billing address form.

| Filter | Arguments | Use |
| --- | --- | --- |
| `csbmw_cnpj_services` | `$services` | Services asked for a CNPJ not yet cached, in order |
| `csbmw_cnpj_lookup` | `$result`, `$cnpj` | Result, with the keys of `_billing_cnpj_lookup` |

A service is a callback that takes the CNPJ, letters and digits only, and returns the
registration situation as Receita Federal writes it, false when it knows the CNPJ is not
registered, or null when it could not answer. A service answering false does not stop the
next one from being asked.

```php
// Answer for the store's own test CNPJ before the public services are asked.
add_filter(
	'csbmw_cnpj_services',
	function ( $services ) {
		return array(
			'test' => function ( $cnpj ) {
				return '11222333000181' === $cnpj ? 'ATIVA' : null;
			},
		) + $services;
	}
);
```

### Admin

| Filter | Arguments | Use |
| --- | --- | --- |
| `wcbcf_support_us` | `$show` | False hides the support box on the settings screen |
