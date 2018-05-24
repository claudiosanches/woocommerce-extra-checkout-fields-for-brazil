=== WooCommerce Extra Checkout Fields for Brazil ===
Contributors: claudiosanches
Donate link: http://claudiosmweb.com/doacoes/
Tags: woocommerce, checkout, brazil, cpf, cpnj
Requires at least: 3.5
Tested up to: 4.9
Stable tag: 3.6.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds Brazilian checkout fields in WooCommerce

== Description ==

Adiciona novos campos para Pessoa Física ou Jurídica, Data de Nascimento, Sexo, Número, Bairro e Celular. Além de máscaras em campos, aviso de e-mail incorreto e auto preenchimento dos campos de endereço pelo CEP.

É necessário estar utilizando uma versão do [WooCommerce](http://wordpress.org/extend/plugins/woocommerce/) para que o WooCommerce Extra Checkout Fields for Brazil funcione.

= Compatibilidade =

Compatível desde a versão 3.0.x do WooCommerce.

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
* [jQuery Mask Plugin](https://github.com/igorescobar/jQuery-Mask-Plugin).
* [Correios RESTful API por Emerson Soares](http://correiosapi.apphb.com/).

= Colaborar =

Você pode contribuir com código-fonte em nossa página no [GitHub](https://github.com/claudiosmweb/woocommerce-extra-checkout-fields-for-brazil).

== Frequently Asked Questions ==

= Qual é a licença do plugin? =

* Este plugin esta licenciado como GPL.

= O que eu preciso para utilizar este plugin? =

* Ter instalado o plugin WooCommerce.

== Screenshots ==

1. Página de checkout usando o tema Storefront
2. Página de edição de endereço de entrega usando o tema Storefront
3. Página de dados do pedido
4. Configurações do plugin
5. Sugestão de e-mail

== Changelog ==

= 3.6.1 - 2018/05/24 =

- Correção de mensagens dizendo que alguns campos eram opcionais, mesmo quando marcados como obrigatórios.

= 3.6.0 - 2017/05/12 =

- Modificada a posição de todos os campos do formulário para funcionar melhor com temas que ainda não são totalmente compatíveis com o WooCommerce 3.0.

= 3.5.1 - 2017/04/26 =

- Corrigido o posicionamento do campo de CEP.
- Corrigida a validação de CPNJ.

= 3.5.0 - 2017/03/04 =

- Adicionado suporte ao WooCommerce 3.0.
- Alterado o plugin de máscara de [jquery.maskedinput](https://github.com/digitalBush/jquery.maskedinput) para [jquery.mask](https://github.com/igorescobar/jQuery-Mask-Plugin). (Possível com a ajuda de [Thiago Guimarães](https://github.com/thiagogsr)).
- Corrigida a máscara dos campos telefone e celular, permitido ter 10 ou 11 dígitos sem alterar a experiência do usuário. (Possível com a ajuda de [Thiago Guimarães](https://github.com/thiagogsr)).
- Alterados os tipos dos campos telefone, celular, cep, data de nascimento para `tel` quando o país selecionado for BR. (Possível com a ajuda de [Thiago Guimarães](https://github.com/thiagogsr)).
- Alterados os tipos dos campos cpf, cnpj para `tel` e e-mail para `email`. (Possível com a ajuda de [Thiago Guimarães](https://github.com/thiagogsr)).

== Upgrade Notice ==

= 3.6.1 =

- Correção de mensagens dizendo que alguns campos eram opcionais, mesmo quando marcados como obrigatórios.
