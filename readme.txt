=== Brazilian Market on WooCommerce ===
Contributors: claudiosanches
Donate link: https://apoia.se/claudiosanches?utm_source=plugin-bmw
Tags: woocommerce, checkout, brazil, cpf, cpnj
Requires at least: 4.0
Tested up to: 6.3
Stable tag: 4.0.0
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds Brazilian checkout fields in WooCommerce

== Description ==

Adiciona novos campos para Pessoa Física ou Jurídica, Data de Nascimento, Gênero, Número, Bairro e Celular. Além de máscaras em campos e aviso de e-mail incorreto.

Em breve serão integradas mais novidades para o mercado brasileiro, como poder fazer login por CPF/CNPJ, ocultar alguns campos no carrinho, aguardem!

É necessário estar utilizando uma versão do [WooCommerce](http://wordpress.org/extend/plugins/woocommerce/) para que o Brazilian Market on WooCommerce funcione.

= Compatibilidade =

Compatível desde a versão 5.0.x do WooCommerce.

Funciona com os plugins:

* [PagSeguro](http://wordpress.org/extend/plugins/woocommerce-pagseguro/) (adiciona as informações de **bairro**, **CPF** e **número**)
* Flux Checkout for WooCommerce

= Dúvidas? =

Você pode esclarecer suas dúvidas usando:

* Utilizando o nosso [fórum no Github](https://github.com/claudiosmweb/woocommerce-extra-checkout-fields-for-brazil/issues).
* Criando um tópico no [fórum de ajuda do WordPress](https://wordpress.org/support/plugin/woocommerce-extra-checkout-fields-for-brazil).

= Créditos =

Foram utilizados os seguintes scripts/serviços de terceiros:

* [MailCheck jQuery](https://github.com/Kicksend/mailcheck).
* [jQuery Mask Plugin](https://github.com/igorescobar/jQuery-Mask-Plugin).

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

= 4.0.0 - 2023/11/06 =

- Adicionada nova opção para estilo dos campos, agora por padrão com largura total para prevenir incompatibilidade com temas e plugins.
- Melhorada a opção para campo de celular, podendo agora substituir o campo de telefone.
- Separado os campos de Data de Nascimento e Gênero em campos próprios.
- Atualizado sufixo `_sex` para `_gender` no banco de dados.
- Corrigido bug que deixava o campo de Nome da Empresa sempre opcional.

= 3.10.0 - 2023/10/30 =

- Adicionada "Não quero informar" e "Outro" como opções para o campo de gênero.

= 3.9.1 - 2023/10/29 =

- Melhorado como o plugin lida o requerimento dos campos de PF e PJ.

= 3.9.0 - 2023/10/29 =

- Adicionado suporte para o plugin Flux Checkout for WooCommerce.
- Adicionado suporte para WooCommerce 8.2+.
- Adicionada opção para controlar a obrigatoriedade do campo de Bairro.
- Adicionada regra para ignorar o requerimento do campo de empresa quando CPF é selecionado no checkout.

= 3.8.4 - 2023/09/25 =

- Adicionado suporte para WooCommerce 8.1+.

= 3.8.3 - 2023/09/13 =

- Adicionado suporte para WooCommerce HPOS.

= 3.8.2 - 2023/05/01 =

- Sugestão de e-mail agora pode ser traduzida.

= 3.8.1 - 2023/05/01 =

- Atualizado arquivo de tradução.

= 3.8.0 - 2023/05/01 =

- Adicionado suporte para versões atuais do WooCommerce.
- Removido suporte a versões anteriores a 3.0 do WooCommerce.
- Corrida aplicação de máscaras no checkout.
- Corrido retorno dos resultados para o hook `woocommerce_ajax_get_customer_details`. 

= 3.7.2 - 2019/09/26 =

- Nome do plugin alterado de "WooCommerce Extra Checkout Fields for Brazil" to "Brazilian Market on WooCommerce".
- Removida opção obsoleta de preenchimento de endereço, no lugar dela utilize a integração direta que existe no plugin "Claudio Sanches - Correios for WooCommerce".
- Corrigido bug causado pelo WooCommerce na exibição do endereço de entrega na lista de pedidos no painel admininstrativo.

= 3.7.1 - 2019/09/24 =

- Corrigido bug causado pelo WooCommerce na exibição do endereço de entrega na lista de pedidos no painel admininstrativo.

= 3.7.0 - 2019/09/20 =

- Nome do plugin alterado de "WooCommerce Extra Checkout Fields for Brazil" to "Brazilian Market on WooCommerce".
- Removida opção obsoleta de preenchimento de endereço, no lugar dela utilize a integração direta que existe no plugin "Claudio Sanches - Correios for WooCommerce".

= 3.6.1 - 2018/05/24 =

- Correção de mensagens dizendo que alguns campos eram opcionais, mesmo quando marcados como obrigatórios.

= 3.6.0 - 2017/05/12 =

- Modificada a posição de todos os campos do formulário para funcionar melhor com temas que ainda não são totalmente compatíveis com o WooCommerce 3.0.

= 3.5.1 - 2017/04/26 =

- Corrigido o posicionamento do campo de CEP.
- Corrigida a validação de CNPJ.

= 3.5.0 - 2017/03/04 =

- Adicionado suporte ao WooCommerce 3.0.
- Alterado o plugin de máscara de [jquery.maskedinput](https://github.com/digitalBush/jquery.maskedinput) para [jquery.mask](https://github.com/igorescobar/jQuery-Mask-Plugin). (Possível com a ajuda de [Thiago Guimarães](https://github.com/thiagogsr)).
- Corrigida a máscara dos campos telefone e celular, permitido ter 10 ou 11 dígitos sem alterar a experiência do usuário. (Possível com a ajuda de [Thiago Guimarães](https://github.com/thiagogsr)).
- Alterados os tipos dos campos telefone, celular, cep, data de nascimento para `tel` quando o país selecionado for BR. (Possível com a ajuda de [Thiago Guimarães](https://github.com/thiagogsr)).
- Alterados os tipos dos campos cpf, cnpj para `tel` e e-mail para `email`. (Possível com a ajuda de [Thiago Guimarães](https://github.com/thiagogsr)).

== Upgrade Notice ==

= 4.0.0 =

- Adicionada nova opção para estilo dos campos, agora por padrão com largura total para prevenir incompatibilidade com temas e plugins.
- Melhorada a opção para campo de celular, podendo agora substituir o campo de telefone.
- Separado os campos de Data de Nascimento e Gênero em campos próprios.
- Atualizado sufixo `_sex` para `_gender` no banco de dados.
- Corrigido bug que deixava o campo de Nome da Empresa sempre opcional.
