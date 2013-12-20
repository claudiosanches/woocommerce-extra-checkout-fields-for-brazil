/*global wcbcf_writepanel_params*/
jQuery(document).ready(function($) {
    $('button.load_customer_billing').click(function(){
        var answer = confirm(wcbcf_writepanel_params.load_message);
        if (answer) {

            // Get user ID to load data for
            var user_id = $('#customer_user').val();

            if (!user_id) {
                alert(woocommerce_writepanel_params.no_customer_selected);
                return false;
            }

            var data = {
                user_id:            user_id,
                type_to_load:       'billing',
                action:             'woocommerce_get_customer_details',
                security:           woocommerce_writepanel_params.get_customer_details_nonce
            };

            $(this).closest('.edit_address').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

            $.ajax({
                url: woocommerce_writepanel_params.ajax_url,
                data: data,
                type: 'POST',
                success: function( response ) {
                    var info = response;

                    if (info) {
                        $('input#_billing_number').val( info.billing_number );
                        $('input#_billing_neighborhood').val( info.billing_neighborhood );
                        $('input#_billing_persontype').val( info.billing_persontype );
                        $('input#_billing_cpf').val( info.billing_cpf );
                        $('input#_billing_cnpj').val( info.billing_cnpj );
                        $('input#_billing_birthdate').val( info.billing_birthdate );
                        $('input#_billing_sex').val( info.billing_sex );
                        $('input#_billing_cellphone').val( info.billing_cellphone );
                    }

                    $('.edit_address').unblock();
                }
            });
        }
        return false;
    });


    $('button.load_customer_shipping').click(function(){
        var answer = confirm(wcbcf_writepanel_params.load_message);
        if (answer) {

            // Get user ID to load data for
            var user_id = $('#customer_user').val();

            if (!user_id) {
                alert(woocommerce_writepanel_params.no_customer_selected);
                return false;
            }

            var data = {
                user_id:            user_id,
                type_to_load:       'shipping',
                action:             'woocommerce_get_customer_details',
                security:           woocommerce_writepanel_params.get_customer_details_nonce
            };

            $(this).closest('.edit_address').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

            $.ajax({
                url: woocommerce_writepanel_params.ajax_url,
                data: data,
                type: 'POST',
                success: function( response ) {
                    var info = response;

                    if (info) {
                        $('input#_shipping_number').val( info.shipping_number );
                        $('input#_shipping_neighborhood').val( info.shipping_neighborhood );
                    }

                    $('.edit_address').unblock();
                }
            });
        }
        return false;
    });

    $('button.billing-same-as-shipping').click(function(){
        var answer = confirm(wcbcf_writepanel_params.copy_message);
        if (answer) {
            $('input#_shipping_number').val( $('input#_billing_number').val() );
            $('input#_shipping_neighborhood').val( $('input#_billing_neighborhood').val() );
        }

        return false;
    });

});
