/* global bmwShopOrderParams, woocommerce_admin_meta_boxes */
/* eslint-disable no-alert, camelcase */

import { bindMask } from '../shared/mask';
import {
	isCellphone,
	isCnpj,
	isCpf,
	isPhone,
	isPostcode,
} from '../shared/validation';
import '../../scss/admin/admin.scss';

const MASKED_FIELDS = {
	_billing_cpf: { mask: 'cpf', valid: isCpf },
	_billing_cnpj: { mask: 'cnpj', valid: isCnpj },
	_billing_phone: { mask: 'phone', valid: isPhone },
	_billing_cellphone: { mask: 'phone', valid: isCellphone },
	_billing_postcode: { mask: 'cep', valid: isPostcode },
	_shipping_postcode: { mask: 'cep', valid: isPostcode },
};

const BILLING_FIELDS = [
	'number',
	'neighborhood',
	'persontype',
	'cpf',
	'rg',
	'cnpj',
	'ie',
	'birthdate',
	'gender',
	'cellphone',
];

const SHIPPING_FIELDS = [ 'number', 'neighborhood' ];

function setupField( id, { mask, valid } ) {
	const input = document.getElementById( id );

	if ( ! input ) {
		return;
	}

	// An empty field is not wrong, it is unfilled. Only what was typed gets a
	// verdict, or every optional field would load flagged as invalid.
	const flag = () => {
		const filled = '' !== input.value.trim();
		const ok = filled && valid( input.value );

		input.classList.toggle( 'is-valid', ok );
		input.classList.toggle( 'is-invalid', filled && ! ok );
	};

	bindMask( input, mask );
	input.addEventListener( 'input', flag );
	input.addEventListener( 'change', flag );
	flag();
}

jQuery( function ( $ ) {
	Object.entries( MASKED_FIELDS ).forEach( ( [ id, config ] ) =>
		setupField( id, config )
	);

	if ( '1' === bmwShopOrderParams.person_type ) {
		$( '#_billing_persontype' )
			.on( 'change', function () {
				$(
					'._billing_cpf_field, ._billing_rg_field, ._billing_company_field, ._billing_cnpj_field, ._billing_ie_field'
				).hide();

				if ( '1' === $( this ).val() ) {
					$( '._billing_cpf_field, ._billing_rg_field' ).show();
				}

				if ( '2' === $( this ).val() ) {
					$(
						'._billing_company_field, ._billing_cnpj_field, ._billing_ie_field'
					).show();
				}
			} )
			.change();
	}

	const loadCustomerDetails = ( button, type, fields ) => {
		if ( ! window.confirm( bmwShopOrderParams.load_message ) ) {
			return;
		}

		const userId = $( '#customer_user' ).val();

		if ( ! userId ) {
			window.alert( woocommerce_admin_meta_boxes.no_customer_selected );
			return;
		}

		$( button )
			.closest( '.edit_address' )
			.block( {
				message: null,
				overlayCSS: {
					background:
						'#fff url(' +
						woocommerce_admin_meta_boxes.plugin_url +
						'/assets/images/ajax-loader.gif) no-repeat center',
					opacity: 0.6,
				},
			} );

		$.ajax( {
			url: woocommerce_admin_meta_boxes.ajax_url,
			data: {
				user_id: userId,
				type_to_load: type,
				action: 'woocommerce_get_customer_details',
				security:
					woocommerce_admin_meta_boxes.get_customer_details_nonce,
			},
			type: 'POST',
			success( response ) {
				if ( response ) {
					fields.forEach( ( field ) => {
						$( `#_${ type }_${ field }` )
							.val( response[ `${ type }_${ field }` ] )
							.change();
					} );
				}

				$( '.edit_address' ).unblock();
			},
		} );
	};

	$( '.load_customer_billing' ).on( 'click', function () {
		loadCustomerDetails( this, 'billing', BILLING_FIELDS );
		return false;
	} );

	$( '.load_customer_shipping' ).on( 'click', function () {
		loadCustomerDetails( this, 'shipping', SHIPPING_FIELDS );
		return false;
	} );

	$( 'button.billing-same-as-shipping' ).on( 'click', function () {
		if ( window.confirm( bmwShopOrderParams.copy_message ) ) {
			$( '#_shipping_number' )
				.val( $( '#_billing_number' ).val() )
				.change();
			$( '#_shipping_neighborhood' )
				.val( $( '#_billing_neighborhood' ).val() )
				.change();
		}

		return false;
	} );
} );
