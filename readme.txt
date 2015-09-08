=== WooCommerce Extra Checkout Fields for Brazil ===
Contributors: claudiosanches
Donate link: http://claudiosmweb.com/doacoes/
Tags: woocommerce, checkout, brazil, cpf, cpnj, rg, ie
Requires at least: 3.5
Tested up to: 4.3
Stable tag: 3.4.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds Brazilian checkout fields in WooCommerce

== Description ==

Adiciona novos campos para Pessoa Física ou Jurídica, Data de Nascimento, Sexo, Número, Bairro e Celular. Além de máscaras em campos, aviso de e-mail incorreto e auto preenchimento dos campos de endereço pelo CEP.

É necessário estar utilizando uma versão do [WooCommerce](http://wordpress.org/extend/plugins/woocommerce/) para que o WooCommerce Extra Checkout Fields for Brazil funcione.

A partir da versão 3.1.0 é feita integração também com a [API](http://docs.woothemes.com/document/woocommerce-rest-api/) de pedidos e de clientes do WooCommerce.

= Compatibilidade =

Compatível com as versões 2.2.x, 2.3.x e 2.4.x do WooCommerce.

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

= Coloborar =

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

= 3.4.1 - 2015/09/07 =

* Corrigido erros na tela de configurações do plugin.
* Corrigido os campos de estado e país na tela de edição de usuário no administrador do WordPress.

= 3.4.0 - 2015/08/18 =

* Adicionado suporte para WooCommerce 2.4.
* Removido suporte para WooCommerce 2.0 e 2.1.
* Corrigida integração com a API do WooCommerce quando é usado filtros para campos.

= 3.3.1 - 2014/10/13 =

* Corrigido o método de instalação e atualização do plugin.

= 3.3.0 - 2014/10/11 =

* Adicionada opção para não tornar os campos de Pessoa Física ou Jurídica obrigatórios quando o cliente é estrangeiro.
* Removida máscara numerica para o campo de RG. Alguns RG antigos possuem também letras.
* Corrigida a validação com javascript dos campos de Pessoa Física e Jurídica.

= 3.2.0 - 2014/09/07 =

* Melhorada a integração com o WooCommerce 2.2.
* Adicionada a exibição da nota do cliente na página de detalhes do pedido.

= 3.1.0 - 2014/09/07 =

* Adicionada integração com a API de pedidos e clientes do WooCommerce.

= 3.0.1 - 2014/08/25 =

* Corrigido o autopreenchimento de endereço por CEP quando esta ativada a opção "Habilitar caixas de seleção de país aprimoradas".

= 3.0.0 - 2014/07/06 =

* Adicionada opção para controlar os campos de "tipo de pessoa", agora é possível usar apenas como "pessoa física" ou apenas como "pessoa jurídica".
* Removido script para integrar com o WooCommerce PagSeguro, a partir da versão 2.5.0 do WooCommerce PagSeguro o suporte é feito direto, sem necessidade de código extra.
* Melhorado o script que faz preenchimento automático dos campos de endereço com base no CEP.

= 2.9.2 - 2014/05/24 =

* Adicionada informações sobre o método de pagamento (WooCommerce 2.1 ou superior).

= 2.9.1 - 2014/02/23 =

* Melhorada a máscara para telefone, agora aceita também o nono dígito.
* Melhorada a máscara do RG, agora não limita a quantidade de caracteres e aceita apenas números.

= 2.9.0 - 2014/02/13 =

* Correção do campo "tipo de pessoa" na página de edição do pedido no admin.
* Correção do carregamento das informações do cliente para pagamento e envio na tela de edição do pedido no admin.
* Melhoria na inicialização do plugin.
* Melhoria na compatibilidade com as novas versões do WooCommerce
* Adicionado campo de RG.
* Adicionado campo de Inscrição Estadual.

= 2.8.2 - 2014/01/05 =

* Correção da mensagem que avisa sobre a falta do WooCommerce na instalação.

= 2.8.1 - 2014/01/05 =

* Corrigido os ganchos e filtros da classe `Extra_Checkout_Fields_For_Brazil`.

= 2.8.0 - 2013/12/21 =

* Melhorada a compatibilidade com o WooCommerce 2.1.
* Correção nas traduções.
* Melhoria em todo o código PHP, JavaScript e CSS.

= 2.7.1 - 2013/09/06 =

* Adicionado suporte para a versão 2.1 do WooCommerce.
* Correção de standards de código.

= 2.7.0 - 2013/09/19 =

* Adicionado script para corrigir a posição do campo de CEP.
* Melhorado o script de auto preenchimento de endereço quando não existe opções de endereço de entrega.

= 2.6.1 - 2013/09/11 =

* Adicionada condição para não auto completar o endereço caso ele já esteja preenchido.

= 2.6.0 - 2013/09/11 =

* Adicionada nova api de busca de CEP: [Correios RESTful API](http://correiosapi.apphb.com/).
* Correção do erro causado ao buscar CEP utilizando HTTPS/SSL.

= 2.5.0 - 2013/08/30 =

* Melhoria no auto preenchimento de endereços. Agora preenche os dados ao carregar a janela.
* Adicionado suporte ao Chosen no auto preenchimento de endereços.

= 2.4.1 - 2013/08/19 =

* Melhorada a compatibilidade com o [WooCommerce PagSeguro](http://wordpress.org/extend/plugins/woocommerce-pagseguro/).

= 2.4.0 - 2013/08/18 =

* Adicionado suporte ao [WooCommerce PagSeguro](http://wordpress.org/extend/plugins/woocommerce-pagseguro/) 2.0.0.

= 2.3.0 - 2013/07/26 =

* Melhorada a integração com o [WooCommerce Bcash](http://wordpress.org/extend/plugins/woocommerce-bcash/).
* Melhorada a integração com o [WooCommerce Moip](http://wordpress.org/extend/plugins/woocommerce-moip/).
* Melhorada a integração com o [WooCommerce PagSeguro](http://wordpress.org/extend/plugins/woocommerce-pagseguro/).
* Adicionada integração com o Checkout Transparente do [WooCommerce Moip](http://wordpress.org/extend/plugins/woocommerce-moip/).
* Corrigido um bug na validação de CPF e CNPJ.

= 2.2.0 - 2013/06/24 =

* Adicionado suporte para o WooCommerce 2.1 ou superior.
* Adicionada opção para validar CPF.
* Adicionada opção para validar CNPJ.
* Correçao da função que instala as opções padrões na instalação.
* Removido o método `shop_order_head` em favor da função `wp_localize_script`.
* Melhorada a tradução.

= 2.1.1 - 2013/04/26 =

* Correção da formatação de endereços para o WooCommerce 1.6.6 ou anterior.

= 2.1 - 2013/04/13 =

* Adicionada nova formatação de endereços (funciona para o WooCommerce 2.0.6 ou superior).

= 2.0.1 - 2013/04/01 =

* Correção da compatibilidade com o [WooCommerce PagSeguro](http://wordpress.org/extend/plugins/woocommerce-pagseguro/).

= 2.0 - 2013/03/10 =

* Compatível com o WooCommerce 2.0.0 ou superior.
* Adicionado campo de **número**.
* Adicionada compatibilidade com o [WooCommerce PagSeguro](http://wordpress.org/extend/plugins/woocommerce-pagseguro/).
* Campo de **bairro** agora é nativo do plugin.
* Adicionado campos personalizados na página de edição do perfil do cliente.
* Correção da página de detalhes do pedido.

= 1.2.1 - 2012/12/10 =

* Corrigida a máscara do campo *Data de Nascimento*.

= 1.2 - 2012/12/10 =

* Adicionadas máscaras para campos de CEP.

= 1.1 =

* Trocado o Webservice de CEP da http://www.republicavirtual.com.br/ para http://www.toolsweb.com.br/.

= 1.0 =

* Primeira versão.

== Upgrade Notice ==

= 3.4.1 =

* Corrigido erros na tela de configurações do plugin.
* Corrigido os campos de estado e país na tela de edição de usuário no administrador do WordPress.

== License ==

WooCommerce Extra Checkout Fields for Brazil is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

WooCommerce Extra Checkout Fields for Brazil is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with WooCommerce Extra Checkout Fields for Brazil. If not, see <http://www.gnu.org/licenses/>.
