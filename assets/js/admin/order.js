/* global bmwShopOrderParams */

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
} );
