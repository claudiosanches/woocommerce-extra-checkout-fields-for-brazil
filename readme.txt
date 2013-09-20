=== WooCommerce Extra Checkout Fields for Brazil ===
Contributors: claudiosanches
Donate link: http://claudiosmweb.com/doacoes/
Tags: woocommerce, checkout fields, brazil, cpf, cpnj
Requires at least: 3.0
Tested up to: 3.6.1
Stable tag: 2.7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds Brazilian checkout fields in WooCommerce

== Description ==

### Adds Brazilian checkout fields in WooCommerce ###

This plugin adds Brazilian checkout fields in WooCommerce.

Please notice that [WooCommerce](http://wordpress.org/extend/plugins/woocommerce/) must be installed and active.

#### Contribute ####

You can contribute to the source code in our [GitHub](https://github.com/claudiosmweb/woocommerce-extra-checkout-fields-for-brazil) page.

### Descrição em Português: ###

Adiciona novos campos para Pessoa Física ou Jurídica, Data de Nascimento, Sexo, Número, Bairro e Celular. Além de máscaras em campos, aviso de e-mail incorreto e auto preenchimento dos campos de endereço pelo CEP.

É necessário estar utilizando uma versão do [WooCommerce](http://wordpress.org/extend/plugins/woocommerce/) para que o WooCommerce Extra Checkout Fields for Brazil funcione.

#### Compatibilidade: ####

Funciona com os plugins:

* [WooCommerce Bcash](http://wordpress.org/extend/plugins/woocommerce-bcash/) (adiciona as informações de **número**, **CPF**, **Razão Social** e **CNPJ**)
* [WooCommerce Moip](http://wordpress.org/extend/plugins/woocommerce-moip/) (adiciona as informações de **bairro** e **número** e melhora o Checkout Transparente)
* [WooCommerce PagSeguro](http://wordpress.org/extend/plugins/woocommerce-pagseguro/) (adiciona as informações de **bairro**, **CPF** e **número**)

#### Créditos: ####

Foram utilizados os seguintes scripts/serviços de terceiros:

* [MailCheck jQuery](https://github.com/Kicksend/mailcheck).
* [Masked Input jQuery](https://github.com/digitalBush/jquery.maskedinput).
* [Correios RESTful API por Emerson Soares](http://correiosapi.apphb.com/).

#### Coloborar ####

Você pode contribuir com código-fonte em nossa página no [GitHub](https://github.com/claudiosmweb/woocommerce-extra-checkout-fields-for-brazil).

== Installation ==

* Upload plugin files to your plugins folder, or install using WordPress built-in Add New Plugin installer;
* Activate the plugin.

= Instalação e configuração em Português: =

* Envie os arquivos do plugin para a pasta wp-content/plugins ou usando o instalador de plugins do WordPress.
* Ative o plugin.

== Frequently Asked Questions ==

= What is the plugin license? =

* This plugin is released under a GPL license.

= What is needed to use this plugin? =

* WooCommerce installed and active

### FAQ em Português: ###

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

= 2.7.0 - 19/09/2013 =

* Adicionado script para corrigir a posição do campo de CEP.
* Melhorado o script de auto preenchimento de endereço quando não existe opções de endereço de entrega.

= 2.6.1 - 11/09/2013 =

* Adicionada condição para não auto completar o endereço caso ele já esteja preenchido.

= 2.6.0 - 11/09/2013 =

* Adicionada nova api de busca de CEP: [Correios RESTful API](http://correiosapi.apphb.com/).
* Correção do erro causado ao buscar CEP utilizando HTTPS/SSL.

= 2.5.0 - 30/08/2013 =

* Melhoria no auto preenchimento de endereços. Agora preenche os dados ao carregar a janela.
* Adicionado suporte ao Chosen no auto preenchimento de endereços.

= 2.4.1 - 19/08/2013 =

* Melhorada a compatibilidade com o [WooCommerce PagSeguro](http://wordpress.org/extend/plugins/woocommerce-pagseguro/).

= 2.4.0 - 18/08/2013 =

* Adicionado suporte ao [WooCommerce PagSeguro](http://wordpress.org/extend/plugins/woocommerce-pagseguro/) 2.0.0.

= 2.3.0 - 26/07/2013 =

* Melhorada a integração com o [WooCommerce Bcash](http://wordpress.org/extend/plugins/woocommerce-bcash/).
* Melhorada a integração com o [WooCommerce Moip](http://wordpress.org/extend/plugins/woocommerce-moip/).
* Melhorada a integração com o [WooCommerce PagSeguro](http://wordpress.org/extend/plugins/woocommerce-pagseguro/).
* Adicionada integração com o Checkout Transparente do [WooCommerce Moip](http://wordpress.org/extend/plugins/woocommerce-moip/).
* Corrigido um bug na validação de CPF e CNPJ.

= 2.2.0 - 24/06/2013 =

* Adicionado suporte para o WooCommerce 2.1 ou superior.
* Adicionada opção para validar CPF.
* Adicionada opção para validar CNPJ.
* Correçao da função que instala as opções padrões na instalação.
* Removido o método `shop_order_head` em favor da função `wp_localize_script`.
* Melhorada a tradução.

= 2.1.1 - 26/04/2013 =

* Correção da formatação de endereços para o WooCommerce 1.6.6 ou anterior.

= 2.1 - 13/04/2013 =

* Adicionada nova formatação de endereços (funciona para o WooCommerce 2.0.6 ou superior).

= 2.0.1 - 01/04/2013 =

* Correção da compatibilidade com o [WooCommerce PagSeguro](http://wordpress.org/extend/plugins/woocommerce-pagseguro/).

= 2.0 - 10/03/2013 =

* Compatível com o WooCommerce 2.0.0 ou superior.
* Adicionado campo de **número**.
* Adicionada compatibilidade com o [WooCommerce PagSeguro](http://wordpress.org/extend/plugins/woocommerce-pagseguro/).
* Campo de **bairro** agora é nativo do plugin.
* Adicionado campos personalizados na página de edição do perfil do cliente.
* Correção da página de detalhes do pedido.

= 1.2.1 - 10/12/2012 =

* Corrigida a máscara do campo *Data de Nascimento*.

= 1.2 - 10/12/2012 =

* Adicionadas máscaras para campos de CEP.

= 1.1 =

* Trocado o Webservice de CEP da http://www.republicavirtual.com.br/ para http://www.toolsweb.com.br/.

= 1.0 =

* Primeira versão.

== Upgrade Notice ==

= 2.7.0 =

* Melhoria no auto preenchimento de endereço e adicionado script para corrigir a posição do campo CEP.

== License ==

WooCommerce Extra Checkout Fields for Brazil is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

WooCommerce Extra Checkout Fields for Brazil is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with WooCommerce Extra Checkout Fields for Brazil. If not, see <http://www.gnu.org/licenses/>.
