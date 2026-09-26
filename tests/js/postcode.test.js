/**
 * @jest-environment jsdom
 */

import {
	createAutofill,
	describeAddress,
	followPostcodeConsent,
	lookupPostcode,
	planAutofill,
	postcodeDigits,
	rememberPostcode,
	rememberedPostcode,
} from '../../assets/js/shared/postcode';

const ADDRESS = {
	postcode: '01001000',
	address: 'Praça da Sé',
	neighborhood: 'Sé',
	city: 'São Paulo',
	state: 'SP',
};

describe( 'postcodeDigits', () => {
	it( 'keeps the digits of a complete CEP', () => {
		expect( postcodeDigits( '01001-000' ) ).toBe( '01001000' );
	} );

	it( 'rejects an incomplete or overlong CEP', () => {
		expect( postcodeDigits( '01001-00' ) ).toBe( '' );
		expect( postcodeDigits( '010010001' ) ).toBe( '' );
		expect( postcodeDigits( undefined ) ).toBe( '' );
	} );
} );

describe( 'describeAddress', () => {
	it( 'joins the parts the address has', () => {
		expect( describeAddress( ADDRESS ) ).toBe(
			'Praça da Sé, Sé, São Paulo - SP'
		);
	} );

	it( 'skips a street and neighborhood a city-wide CEP lacks', () => {
		expect(
			describeAddress( { ...ADDRESS, address: '', neighborhood: '' } )
		).toBe( 'São Paulo - SP' );
	} );
} );

describe( 'lookupPostcode', () => {
	afterEach( () => {
		delete window.fetch;
	} );

	it( 'asks once for the same CEP', async () => {
		window.fetch = jest.fn().mockResolvedValue( {
			json: () => Promise.resolve( { success: true, data: ADDRESS } ),
		} );

		await expect(
			lookupPostcode( '/?wc-ajax=lookup', '01001-000' )
		).resolves.toEqual( ADDRESS );
		await lookupPostcode( '/?wc-ajax=lookup', '01001000' );

		expect( window.fetch ).toHaveBeenCalledTimes( 1 );
		expect( window.fetch.mock.calls[ 0 ][ 0 ] ).toContain(
			'postcode=01001000'
		);
	} );

	it( 'asks again after a failure', async () => {
		window.fetch = jest.fn().mockResolvedValue( {
			json: () =>
				Promise.resolve( {
					success: false,
					data: { message: 'CEP not found.' },
				} ),
		} );

		await expect(
			lookupPostcode( '/?wc-ajax=lookup', '99999-998' )
		).resolves.toBeNull();
		await lookupPostcode( '/?wc-ajax=lookup', '99999-998' );

		expect( window.fetch ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'does not ask for an incomplete CEP', async () => {
		window.fetch = jest.fn();

		await expect(
			lookupPostcode( '/?wc-ajax=lookup', '0100' )
		).resolves.toBeNull();
		expect( window.fetch ).not.toHaveBeenCalled();
	} );
} );

describe( 'planAutofill', () => {
	const EMPTY = {
		address_1: '',
		address_2: '',
		number: '',
		neighborhood: '',
		city: '',
		state: '',
	};

	it( 'fills every field the address has', () => {
		expect( planAutofill( ADDRESS, EMPTY, EMPTY, {} ) ).toEqual( {
			address_1: 'Praça da Sé',
			neighborhood: 'Sé',
			city: 'São Paulo',
			state: 'SP',
		} );
	} );

	it( 'keeps what the customer typed while the lookup ran', () => {
		const typed = { ...EMPTY, address_1: 'Rua digitada' };

		expect(
			planAutofill( ADDRESS, typed, EMPTY, {} ).address_1
		).toBeUndefined();
	} );

	it( 'clears a street an earlier lookup filled when the CEP covers a city', () => {
		const current = {
			address_1: 'Praça da Sé',
			neighborhood: 'Sé',
			city: 'São Paulo',
			state: 'SP',
		};
		const citywide = {
			...ADDRESS,
			address: '',
			neighborhood: '',
			city: 'Poconé',
			state: 'MT',
		};

		expect( planAutofill( citywide, current, current, current ) ).toEqual( {
			address_1: '',
			neighborhood: '',
			city: 'Poconé',
			state: 'MT',
		} );
	} );

	it( 'leaves a street the customer typed when the CEP covers a city', () => {
		const current = { ...EMPTY, address_1: 'Rua digitada' };
		const citywide = { ...ADDRESS, address: '', neighborhood: '' };

		expect(
			planAutofill( citywide, current, current, {} ).address_1
		).toBeUndefined();
	} );

	it( 'clears what the customer typed when the CEP moves to another city', () => {
		const current = {
			address_1: 'Rua digitada',
			address_2: 'Apto 51',
			number: '12',
			neighborhood: 'Centro',
			city: 'Poconé',
			state: 'MT',
		};
		const citywide = {
			...ADDRESS,
			address: '',
			neighborhood: '',
			city: 'Águas Vermelhas',
			state: 'MG',
		};

		expect( planAutofill( citywide, current, current, {} ) ).toEqual( {
			address_1: '',
			address_2: '',
			number: '',
			neighborhood: '',
			city: 'Águas Vermelhas',
			state: 'MG',
		} );
	} );

	it( 'keeps a typed street when a CEP of the same city is corrected', () => {
		const current = {
			...EMPTY,
			address_1: 'Rua digitada',
			number: '12',
			city: 'Pocone',
			state: 'MT',
		};
		const citywide = {
			...ADDRESS,
			address: '',
			neighborhood: '',
			city: 'Poconé',
			state: 'MT',
		};
		const plan = planAutofill( citywide, current, current, {} );

		expect( plan.address_1 ).toBeUndefined();
		expect( plan.number ).toBeUndefined();
	} );

	it( 'clears the number and complement of a replaced street', () => {
		const current = {
			...EMPTY,
			address_1: 'Rua Direita',
			address_2: 'Sala 3',
			number: '100',
			city: 'São Paulo',
			state: 'SP',
		};
		const plan = planAutofill( ADDRESS, current, current, {} );

		expect( plan.address_1 ).toBe( 'Praça da Sé' );
		expect( plan.address_2 ).toBe( '' );
		expect( plan.number ).toBe( '' );
	} );

	it( 'keeps the number when the same street is found again', () => {
		const current = {
			...EMPTY,
			address_1: 'Praça da Sé',
			number: '100',
			neighborhood: 'Sé',
			city: 'São Paulo',
			state: 'SP',
		};

		expect(
			planAutofill( ADDRESS, current, current, current ).number
		).toBeUndefined();
	} );
} );

describe( 'createAutofill', () => {
	afterEach( () => {
		delete window.fetch;
	} );

	it( 'asks again for a CEP whose lookup failed', async () => {
		window.fetch = jest
			.fn()
			.mockRejectedValueOnce( new Error( 'offline' ) )
			.mockResolvedValue( {
				json: () => Promise.resolve( { success: true, data: ADDRESS } ),
			} );

		const write = jest.fn();
		const fill = createAutofill( {
			url: '/?wc-ajax=lookup',
			// Not looked up by another test, so not already cached.
			postcode: () => '04538-133',
			read: () => ( {
				address_1: '',
				address_2: '',
				number: '',
				neighborhood: '',
				city: '',
				state: '',
			} ),
			write,
		} );

		fill();
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
		expect( write ).not.toHaveBeenCalled();

		fill();
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
		expect( write ).toHaveBeenCalledWith(
			expect.objectContaining( { city: 'São Paulo' } )
		);
	} );
} );

describe( 'rememberPostcode', () => {
	let written;

	beforeEach( () => {
		written = jest.spyOn( document, 'cookie', 'set' );
	} );

	afterEach( () => {
		written.mockRestore();
		rememberPostcode( '' );
		delete window.wp_has_consent;
	} );

	it( 'keeps the CEP until the browser closes without consent', () => {
		rememberPostcode( '01001-000' );

		expect( rememberedPostcode() ).toBe( '01001000' );
		expect( written.mock.calls[ 0 ][ 0 ] ).not.toContain( 'max-age' );
	} );

	it( 'keeps the CEP for thirty days with consent to preferences', () => {
		window.wp_has_consent = ( category ) => 'preferences' === category;

		rememberPostcode( '01001-000' );

		expect( written.mock.calls[ 0 ][ 0 ] ).toContain( 'max-age=2592000' );
	} );

	it( 'forgets the CEP when consent is withdrawn', () => {
		const onWithdraw = jest.fn();

		rememberPostcode( '01001-000' );
		followPostcodeConsent( onWithdraw );
		document.dispatchEvent(
			new window.CustomEvent( 'wp_listen_for_consent_change', {
				detail: { preferences: 'deny' },
			} )
		);

		expect( rememberedPostcode() ).toBe( '' );
		expect( onWithdraw ).toHaveBeenCalled();
	} );
} );
