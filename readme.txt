=== WooCommerce Extra Checkout Fields for Brazil ===
Contributors: claudiosanches
Donate link: http://claudiosmweb.com/doacoes/
Tags: woocommerce, checkout, brazil, cpf, cpnj, rg, ie
Requires at least: 3.5
Tested up to: 4.7
Stable tag: 3.4.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds Brazilian checkout fields in WooCommerce

== Description ==

Adiciona novos campos para Pessoa Física ou Jurídica, Data de Nascimento, Sexo, Número, Bairro e Celular. Além de máscaras em campos, aviso de e-mail incorreto e auto preenchimento dos campos de endereço pelo CEP.

É necessário estar utilizando uma versão do [WooCommerce](http://wordpress.org/extend/plugins/woocommerce/) para que o WooCommerce Extra Checkout Fields for Brazil funcione.

A partir da versão 3.1.0 é feita integração também com a [API](http://docs.woothemes.com/document/woocommerce-rest-api/) de pedidos e de clientes do WooCommerce.

= Compatibilidade =

Compatível desde a versão 2.2.x até 2.6.x do WooCommerce.

Funciona com os plugins:

* [WooCommerce Bcash](http://wordpress.org/extend/plugins/woocommerce-bcash/) (adiciona as informações de **número**, **CPF**, **Razão Social** e **CNPJ**)
* [WooCommerce Moip](http://wordpress.org/extend/plugins/woocommerce-moip/) (adiciona as informações de **bairro** e **número** e melhora o Checkout Transparente)
* [WooCommerce PagSeguro](http://wordpress.org/extend/plugins/woocommerce-pagseguro/) (adiciona as informações de **bairro**, **CPF** e **número**)

= Dúvidas? =

Você pode esclarecer suas dúvidas usando:

* Utilizando o nosso [fórum no Github](https://github.com/claudiosmweb/woocommerce-extra-checkout-fields-for-brazil/issues).
* Criando um tópico no [fórum de ajuda do WordPress](https://wordpress.org/support/plugin/woocommerce-extra-checkout-fields-for-brazil).

= Créditos =

Foram utilizados os seguintes scripts/serviços de terceiros:

* [MailCheck jQuery](https://github.com/Kicksend/mailcheck).
* [Masked Input jQuery](https://github.com/digitalBush/jquery.maskedinput).
* [Correios RESTful API por Emerson Soares](http://correiosapi.apphb.com/).

= Colaborar =

Você pode contribuir com código-fonte em nossa página no [GitHub](https://github.com/claudiosmweb/woocommerce-extra-checkout-fields-for-brazil).

== Installation ==

* Upload plugin files to your plugins folder, or install using WordPress built-in Add New Plugin installer;
* Activate the plugin.

= Instalação e configuração em Português: =

* Envie os arquivos do plugin para a pasta wp-content/plugins ou usando o instalador de plugins do WordPress.
* Ative o plugin.

== Frequently Asked Questions ==

= Qual é a licença do plugin? =

* Este plugin esta licenciado como GPL.

= O que eu preciso para utilizar este plugin? =

* Ter instalado o plugin WooCommerce.

== Screenshots ==

1. Página de Checkout
2. Página de edição de endereço
3. Página de dados do pedido
4. Configurações do plugin
5. Sugestão de e-mail

== Changelog ==

= 3.4.6 - 2017/01/04 =

- Corrigido alinhamento do campo de CEP na página de edição de endereços em "Minha conta".

= 3.4.5 - 2016/10/09 =

- Melhorada validação do campo "sexo".
- Melhorado o registro e carregamento dos scripts do plugin.

= 3.4.4 - 2016/06/20 =

- Adicionado suporte a nova API REST do WooCommerce.
- Corrigido localização do campo de CEP na página de editar endereços.
- Corrigido os campos de bairro e número no método `WC_Order::get_order_address()`.
- Adicionada compatibilidade com o WooCommerce Correios 3.0.0.

= 3.4.3 - 2016/03/20 =

- Corrigida as mascaras quando não esta preenchendo um endereço brasileiro.
- Incluídos os campos de bairro e número no método `WC_Order::get_order_address()`.

= 3.4.2 - 2016/02/10 =

- Adicionado o filtro `wcbcf_disable_checkout_validation` para suportar o plugin [WooCommerce Digital Goods Checkout](https://wordpress.org/plugins/wc-digital-goods-checkout/).

= 3.4.1 - 2015/09/07 =

- Corrigido erros na tela de configurações do plugin.
- Corrigido os campos de estado e país na tela de edição de usuário no administrador do WordPress.

= 3.4.0 - 2015/08/18 =

- Adicionado suporte para WooCommerce 2.4.
- Removido suporte para WooCommerce 2.0 e 2.1.
- Corrigida integração com a API do WooCommerce quando é usado filtros para campos.

== Upgrade Notice ==

= 3.4.5 =

- Melhorada validação do campo "sexo".
- Melhorado o registro e carregamento dos scripts do plugin.
