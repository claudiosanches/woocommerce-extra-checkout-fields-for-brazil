# Integração com o Brazilian Market on WooCommerce

Also available in [English](developers.md).

Para gateways de pagamento, plugins de frete, ERPs, emissores de nota fiscal e qualquer
código que leia ou grave os dados que este plugin coleta. Descreve a versão 5.0.0 em diante.
Onde uma versão anterior difere, isso é dito.

## Onde os dados ficam

Cada campo é salvo em uma meta key histórica, a que as integrações sempre leram. O checkout
em blocos também guarda uma cópia própria em uma chave com namespace, e o plugin mantém as
duas iguais.

Pedidos usam as chaves históricas com um sublinhado na frente; clientes, sem. Os campos de
contato ficam no endereço de cobrança.

| Meta do pedido                                    | Meta do cliente                                 | Campo do bloco       | Valor                                                                    |
| ------------------------------------------------- | ----------------------------------------------- | -------------------- | ------------------------------------------------------------------------ |
| `_billing_persontype`                             | `billing_persontype`                            | `csbmw/persontype`   | `1` pessoa física, `2` pessoa jurídica                                   |
| `_billing_cpf`                                    | `billing_cpf`                                   | `csbmw/cpf`          | Como digitado, como `529.982.247-25`                                     |
| `_billing_rg`                                     | `billing_rg`                                    | `csbmw/rg`           | Como digitado                                                            |
| `_billing_cnpj`                                   | `billing_cnpj`                                  | `csbmw/cnpj`         | Como digitado, como `11.222.333/0001-81`. CNPJs alfanuméricos têm letras |
| `_billing_ie`                                     | `billing_ie`                                    | `csbmw/ie`           | Em maiúsculas e sem espaços, ou `ISENTO`                                 |
| `_billing_birthdate`                              | `billing_birthdate`                             | `csbmw/birthdate`    | `dd/mm/aaaa`                                                             |
| `_billing_gender`                                 | `billing_gender`                                | `csbmw/gender`       | O rótulo no idioma da loja, como `Feminino`                              |
| `_billing_cellphone`                              | `billing_cellphone`                             | `csbmw/cellphone`    | Veja [Telefones](#telefones)                                             |
| `_billing_number`, `_shipping_number`             | `billing_number`, `shipping_number`             | `csbmw/number`       | Veja [Endereços](#endereços)                                             |
| `_billing_neighborhood`, `_shipping_neighborhood` | `billing_neighborhood`, `shipping_neighborhood` | `csbmw/neighborhood` | Veja [Endereços](#endereços)                                             |
| `_billing_cnpj_lookup`                            |                                                 |                      | Veja [Situação do CNPJ](#situação-do-cnpj)                               |

A empresa é o próprio campo de empresa de cobrança do WooCommerce. Quando a loja pede a
empresa só de pessoas jurídicas, o checkout em blocos a coleta como `csbmw/company` e a salva
como empresa de cobrança.

Os documentos são salvos como o cliente os digitou, formatados pelas máscaras quando elas
estão ativas. Remova tudo que não for letra ou dígito antes de comparar. Pedidos feitos antes
da 5.0.0 podem ter a data de nascimento em outro formato, como `aaaa-mm-dd`.

O tipo de pessoa só é salvo quando a loja deixa o cliente escolher entre pessoa física e
jurídica. Nesse caso, o pedido e o cadastro de uma pessoa física não guardam CNPJ nem
Inscrição Estadual, e os de uma pessoa jurídica não guardam CPF nem RG. Enquanto a empresa for
pedida só de pessoas jurídicas, uma pessoa física também não guarda empresa.

### Chaves do checkout em blocos

O checkout em blocos guarda sua cópia como `_wc_other/csbmw/cpf` para os campos de contato, e
`_wc_billing/csbmw/number` ou `_wc_shipping/csbmw/number` para os de endereço, no pedido e no
cliente. O gênero fica ali como uma chave fixa: `female`, `male`, `other` ou
`prefer_not_to_say`.

O WooCommerce lê essas chaves para preencher o checkout em blocos e para mostrar os campos na
página de confirmação do pedido. Todo o resto, incluindo a tela do pedido, os e-mails e a API
REST, lê as chaves históricas.

### Gravando os dados

Em um pedido, grave as chaves históricas. Um pedido editado na tela do pedido tem as chaves do
bloco atualizadas a partir delas.

Em um cliente, grave as duas. O checkout em blocos preenche a partir da sua própria chave e só
recorre à histórica quando a sua está vazia, então um cliente que já comprou pelo checkout em
blocos veria o valor antigo.

## API REST

Pedidos (`/wc/v3/orders`) e clientes (`/wc/v3/customers`) retornam estes campos.

| Campo                                                                 | Valor                                                                                                                        |
| --------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| `billing.persontype`                                                  | `F` para pessoa física, `J` para pessoa jurídica. Quando a loja pede um só tipo, esse tipo. Vazio quando a loja não pergunta |
| `billing.cpf`, `billing.cnpj`, `billing.rg`, `billing.ie`             | Sem pontos, barras e traços. Um CNPJ alfanumérico mantém suas letras                                                         |
| `billing.birthdate`                                                   | Como `1990-01-15T00:00:00`. Vazio quando a data salva não é reconhecida                                                      |
| `billing.gender`                                                      | Primeira letra do rótulo salvo: `F`, `M`, `O`, e `N` ou `P` para "Não quero informar" em português ou inglês                 |
| `billing.cellphone`                                                   | Como salvo                                                                                                                   |
| `billing.number`, `shipping.number`                                   | Como salvo                                                                                                                   |
| `billing.neighborhood`, `shipping.neighborhood`                       | Como salvo                                                                                                                   |
| `billing.phone_e164`, `billing.cellphone_e164`, `shipping.phone_e164` | Veja [Telefones](#telefones)                                                                                                 |
| `billing.cnpj_lookup`                                                 | Só em pedidos. Veja [Situação do CNPJ](#situação-do-cnpj)                                                                    |

Antes da 5.0.0, `billing.birthdate` vinha com o mês primeiro, como `01-15-1990T00:00:00`.

A API legada (`/wc-api/v3`) retorna os mesmos campos em `billing_address` e
`shipping_address`, menos `cnpj_lookup`, com a data de nascimento no formato de datas dessa API.

Esses campos são somente leitura. O WooCommerce só salva as chaves de endereço que têm setter,
e descarta as demais. Grave as meta keys por `meta_data`, como com
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

Em um cliente, use `billing_cpf` e as demais chaves de cliente, e as chaves do bloco como
descrito em [Gravando os dados](#gravando-os-dados).

## Store API

Um checkout headless que envia para `/wc/store/v1/checkout` manda os campos de contato em
`additional_fields` e os de endereço dentro de cada endereço:

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

Um campo só existe enquanto a loja o mantém ativo, e é validado como no checkout. O checkout
em blocos pede o tipo de pessoa e os documentos na etapa de contato, sob o título Dados do
cliente.

## Endereços

Número e Bairro são pedidos apenas em endereços brasileiros. Cobrança e entrega seguem cada uma
o seu país, então os dois ficam vazios em um endereço estrangeiro.

`_billing_number` e `_shipping_number` guardam apenas dígitos, ou o valor de Sem número da loja
quando o cliente marca Sem número. Esse valor é `S/N`, a menos que a loja o tenha mudado.

## Telefones

Um número brasileiro em um endereço brasileiro é salvo como `(11) 98765-4321`, ou
`+55 (11) 98765-4321` quando a opção Formato de telefone brasileiro pede o código do país.
Qualquer outro número é salvo com o código do seu país, como `+44 20 7946 0958`.

Pedidos e clientes reescrevem um telefone ao salvar apenas se o telefone ou o país do seu
endereço mudou e ele é um número completo. Um número incompleto, ou salvo por uma versão
anterior, fica como está.

`phone_e164` e `cellphone_e164` na API REST são obtidos do que foi salvo, como
`+5511987654321`, e ficam vazios quando o número não está completo.

O tamanho dos telefones é verificado pelo filtro `woocommerce_validate_phone` do WooCommerce,
que exige o WooCommerce 11.0 ou mais recente.

## Situação do CNPJ

Quando a loja ativa a consulta, o CNPJ de uma pessoa jurídica é consultado na Receita Federal
pela BrasilAPI, e pelo OpenCNPJ quando a BrasilAPI não responde. O resultado é salvo no pedido
como `_billing_cnpj_lookup`:

| Chave        | Valor                                                                                                       |
| ------------ | ----------------------------------------------------------------------------------------------------------- |
| `cnpj`       | O CNPJ consultado, apenas letras e dígitos                                                                  |
| `status`     | `active`, `inactive`, `not_found`, ou `unavailable` quando nenhum serviço respondeu                         |
| `situation`  | Como a Receita Federal escreve: `ATIVA`, `SUSPENSA`, `INAPTA`, `BAIXADA` ou `NULA`. Vazio se não encontrado |
| `provider`   | O serviço que respondeu, como `brasilapi` ou `opencnpj`. Vazio quando nenhum respondeu                      |
| `checked_at` | Quando foi consultado, em UTC, como `2026-09-27T14:05:00Z`                                                  |

`billing.cnpj_lookup` na API REST traz as mesmas chaves, menos `cnpj`. É null quando o pedido
não foi consultado, ou quando o CNPJ do pedido foi alterado depois.

Os resultados ficam em cache por CNPJ: um dia quando encontrado, uma hora quando não
encontrado. Uma consulta sem resposta não vai para o cache.

## Hooks

### Campos

| Filtro                              | Argumentos          | Onde                                                                       |
| ----------------------------------- | ------------------- | -------------------------------------------------------------------------- |
| `wcbcf_billing_fields`              | `$fields`           | Checkout clássico e formulário de endereço de cobrança em Minha conta      |
| `wcbcf_shipping_fields`             | `$fields`           | Checkout clássico e formulário de endereço de entrega em Minha conta       |
| `wcbcf_admin_billing_fields`        | `$fields`           | Endereço de cobrança na tela do pedido                                     |
| `wcbcf_admin_shipping_fields`       | `$fields`           | Endereço de entrega na tela do pedido                                      |
| `wcbcf_customer_meta_fields`        | `$fields`           | Tela de perfil do usuário                                                  |
| `wcbcf_disable_checkout_validation` | `$disabled`         | Veja abaixo                                                                |
| `csbmw_order_customer_data`         | `$fields`, `$order` | Confirmação do pedido, pedido em Minha conta e e-mails, nos dois checkouts |

O checkout em blocos registra seus campos pelos campos adicionais de checkout do WooCommerce.
Altere-os com os hooks do próprio WooCommerce, como `woocommerce_validate_additional_field`,
`woocommerce_sanitize_additional_field` e `woocommerce_get_default_value_for_{$field_id}`.

`wcbcf_disable_checkout_validation` retornando true pula as verificações de documentos,
número, data de nascimento e situação do CNPJ do checkout clássico e do formulário de
endereço em Minha conta, e toda verificação de tamanho de telefone. O checkout em blocos
continua verificando documentos, número e data de nascimento conforme suas configurações.

```php
// Mostra o código do cliente no ERP na confirmação do pedido e nos e-mails.
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

### Consulta de CEP

Valem para todas as calculadoras e para o preenchimento do endereço nos dois checkouts e em
Minha conta.

| Filtro                    | Argumentos              | Uso                                                         |
| ------------------------- | ----------------------- | ----------------------------------------------------------- |
| `csbmw_postcode_services` | `$services`             | Serviços consultados, em ordem, para um CEP ainda não salvo |
| `csbmw_postcode_address`  | `$address`, `$postcode` | Endereço encontrado para um CEP, ou null                    |
| `csbmw_find_postcode_url` | `$url`                  | Link oferecido a clientes que não sabem o CEP               |

Um serviço é um callback que recebe os oito dígitos do CEP e retorna um array com
`postcode`, `address`, `neighborhood`, `city` e `state`, false quando sabe que o CEP não
existe, ou null quando não conseguiu responder. Um endereço sem cidade ou sem um estado válido
é ignorado.

```php
// Consulta um serviço interno antes dos públicos.
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

### Estimativas de frete na página do produto

Valem para o bloco Calculadora de Frete e para a calculadora abaixo do botão de comprar.

| Filtro                              | Argumentos                          | Uso                                                                                     |
| ----------------------------------- | ----------------------------------- | --------------------------------------------------------------------------------------- |
| `csbmw_shipping_estimate_package`   | `$package`, `$product`, `$quantity` | Pacote para o qual a estimativa é calculada                                             |
| `csbmw_shipping_estimate_cache_ttl` | `$ttl`, `$product`                  | Segundos em que uma estimativa é reaproveitada, uma hora por padrão. 0 calcula toda vez |

```php
// Não usa o cache para produtos enviados por um fornecedor.
add_filter(
	'csbmw_shipping_estimate_cache_ttl',
	function ( $ttl, $product ) {
		return $product->get_meta( '_ships_from_supplier' ) ? 0 : $ttl;
	},
	10,
	2
);
```

### Situação do CNPJ

Valem para os dois checkouts e para o formulário de endereço de cobrança em Minha conta.

| Filtro                | Argumentos         | Uso                                                        |
| --------------------- | ------------------ | ---------------------------------------------------------- |
| `csbmw_cnpj_services` | `$services`        | Serviços consultados, em ordem, para um CNPJ fora do cache |
| `csbmw_cnpj_lookup`   | `$result`, `$cnpj` | Resultado, com as chaves de `_billing_cnpj_lookup`         |

Um serviço é um callback que recebe o CNPJ, apenas letras e dígitos, e retorna a situação
cadastral como a Receita Federal escreve, false quando sabe que o CNPJ não está registrado, ou
null quando não conseguiu responder. Um serviço que responde false não impede que o próximo
seja consultado.

```php
// Responde pelo CNPJ de teste da loja antes dos serviços públicos.
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

| Filtro             | Argumentos | Uso                                                      |
| ------------------ | ---------- | -------------------------------------------------------- |
| `wcbcf_support_us` | `$show`    | False esconde o quadro de apoio na tela de configurações |
