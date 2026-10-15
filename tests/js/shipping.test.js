/**
 * @jest-environment jsdom
 */

const calculator = ( productId, automatic = false ) =>
	`<div class="csbmw-shipping-calculator" data-product-id="${ productId }"${
		automatic ? ' data-automatic="1"' : ''
	}></div>`;

describe( 'product calculators', () => {
	it( 'drop the automatic one where one is placed for the product', () => {
		document.body.innerHTML =
			calculator( 1, true ) + calculator( 2, true ) + calculator( 1 );

		jest.isolateModules( () =>
			require( '../../assets/js/shipping/shipping' )
		);

		expect(
			Array.from(
				document.querySelectorAll( '.csbmw-shipping-calculator' ),
				( node ) => [
					node.dataset.productId,
					!! node.dataset.automatic,
				]
			)
		).toEqual( [
			[ '2', true ],
			[ '1', false ],
		] );
	} );
} );
