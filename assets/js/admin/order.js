/* global bmwShopOrderParams */

import { bindMask } from '../shared/mask';
import { bindNoNumber } from '../shared/no-number';
import { bindHouseNumber } from '../shared/house-number';
import { bindPhone, isPhoneNumber, rebasePhone } from '../shared/phone';
import { isCnpj, isCpf, isDate, isPostcode } from '../shared/validation';
import '../../scss/admin/admin.scss';

const phoneParams = bmwShopOrderParams.phone || {};

const addressCountry = ( group ) =>
	jQuery( `#_${ group }_country` ).val() || '';

/**
 * A phone field, formatted and checked for the country of its address.
 *
 * @param {string} group Address group, billing or shipping.
 * @return {Object} Field config.
 */
const phoneField = ( group ) => ( {
	mask: ( input ) =>
		bindPhone( input, () => addressCountry( group ), phoneParams ),
	valid: ( value ) =>
		isPhoneNumber( value, addressCountry( group ), phoneParams ),
	group,
} );

const MASKED_FIELDS = {
	_billing_cpf: { mask: 'cpf', valid: isCpf },
	_billing_cnpj: { mask: 'cnpj', valid: isCnpj },
	_billing_phone: phoneField( 'billing' ),
	_billing_cellphone: phoneField( 'billing' ),
	_shipping_phone: phoneField( 'shipping' ),
	_billing_birthdate: { mask: 'date', valid: isDate },
	_billing_postcode: { mask: 'cep', valid: isPostcode },
	_shipping_postcode: { mask: 'cep', valid: isPostcode },
};

function setupField( id, { mask, valid, group } ) {
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

	if ( 'function' === typeof mask ) {
		mask( input );
	} else {
		bindMask( input, mask );
	}

	input.addEventListener( 'input', flag );

	// A phone typed without a code keeps the country it was typed for.
	// Loading a customer's details writes both at once, and that phone was
	// already saved for the new country.
	if ( group ) {
		let previous = addressCountry( group );
		let typed = input.value;

		input.addEventListener( 'input', () => {
			typed = input.value;
		} );

		jQuery( `#_${ group }_country` ).on( 'change', () => {
			const next = addressCountry( group );

			input.value = rebasePhone(
				input.value,
				typed === input.value ? previous : next,
				next,
				phoneParams
			);
			previous = next;
			typed = input.value;
			flag();
		} );
	}

	// WooCommerce fills these fields from its own scripts and announces it with
	// jQuery, which dispatches no native event, so the change is only heard by
	// a jQuery listener.
	jQuery( input ).on( 'change', flag );

	flag();
}

jQuery( function ( $ ) {
	Object.entries( MASKED_FIELDS ).forEach( ( [ id, config ] ) =>
		setupField( id, config )
	);

	[ '_billing_number', '_shipping_number' ].forEach( ( id ) => {
		const input = document.getElementById( id );

		bindHouseNumber( input, bmwShopOrderParams.no_number );
		bindNoNumber( input, bmwShopOrderParams.no_number );
	} );

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
			.trigger( 'change' );
	}
} );
