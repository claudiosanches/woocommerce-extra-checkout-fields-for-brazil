/* global wcbcf_writepanel_params, woocommerce_writepanel_params */
/**
 * WooCommerce write panels/shop order scripts to WooCommerce 2.0 or prior.
 */
(function ( $ ) {
	'use strict';

	$(function () {

		$( 'button.load_customer_billing' ).on( 'click', function () {
			var answer = window.confirm( wcbcf_writepanel_params.load_message ),
				userId = $( '#customer_user' ).val(),
				data;

			if ( answer ) {
				if ( ! userId ) {
					window.alert( woocommerce_writepanel_params.no_customer_selected );
					return false;
				}

				data = {
					user_id:      userId,
					type_to_load: 'billing',
					action:       'woocommerce_get_customer_details',
					security:     woocommerce_writepanel_params.get_customer_details_nonce
				};

				$( this ).closest( '.edit_address' ).block({
					message: null,
					overlayCSS: {
						background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
						opacity: 0.6
					}
				});

				$.ajax({
					url: woocommerce_writepanel_params.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						if ( response ) {
							$( 'input#_billing_number' ).val( response.billing_number );
							$( 'input#_billing_neighborhood' ).val( response.billing_neighborhood );
							$( 'input#_billing_persontype' ).val( response.billing_persontype );
							$( 'input#_billing_cpf' ).val( response.billing_cpf );
							$( 'input#_billing_cnpj' ).val( response.billing_cnpj );
							$( 'input#_billing_birthdate' ).val( response.billing_birthdate );
							$( 'input#_billing_sex' ).val( response.billing_sex );
							$( 'input#_billing_cellphone' ).val( response.billing_cellphone );
						}

						$( '.edit_address' ).unblock();
					}
				});
			}

			return false;
		});

		$( 'button.load_customer_shipping' ).on( 'click', function () {
			var answer = window.confirm( wcbcf_writepanel_params.load_message ),
				userId = $( '#customer_user' ).val(),
				data;

			if ( answer ) {

				if ( ! userId ) {
					window.alert( woocommerce_writepanel_params.no_customer_selected );
					return false;
				}

				data = {
					user_id:      userId,
					type_to_load: 'shipping',
					action:       'woocommerce_get_customer_details',
					security:     woocommerce_writepanel_params.get_customer_details_nonce
				};

				$( this ).closest( '.edit_address' ).block({
					message: null,
					overlayCSS: {
						background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
						opacity: 0.6
					}
				});

				$.ajax({
					url: woocommerce_writepanel_params.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						if ( response ) {
							$( 'input#_shipping_number' ).val( response.shipping_number );
							$( 'input#_shipping_neighborhood' ).val( response.shipping_neighborhood );
						}

						$( '.edit_address' ).unblock();
					}
				});
			}

			return false;
		});

		$( 'button.billing-same-as-shipping' ).on( 'click', function () {
			var answer = window.confirm( wcbcf_writepanel_params.copy_message );

			if ( answer ) {
				$( 'input#_shipping_number' ).val( $( 'input#_billing_number' ).val() );
				$( 'input#_shipping_neighborhood' ).val( $( 'input#_billing_neighborhood' ).val() );
			}

			return false;
		});

	});

}(jQuery));
