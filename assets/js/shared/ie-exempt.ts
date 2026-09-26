/**
 * "Exempt" toggle for the State Registration field.
 *
 * Companies without a state registration have to send the literal word
 * ISENTO on the invoice, which customers have no way of guessing from a
 * field that only says it is required. The checkbox writes it for them.
 *
 * The value is what gets stored, so nothing downstream has to know the
 * checkbox exists: the field still holds a plain string.
 */

import { __ } from '@wordpress/i18n';

export const EXEMPT_VALUE = 'ISENTO';

export interface IeExemptOptions {
	write?: ( input: HTMLInputElement, value: string ) => void;
}

/**
 * Whether a value is the exemption marker, whatever case it was typed in.
 *
 * @param value Field value.
 * @return True when the value marks an exemption.
 */
export const isExempt = ( value: string | null | undefined ): boolean =>
	String( value ?? '' )
		.trim()
		.toUpperCase() === EXEMPT_VALUE;

/**
 * The element the checkbox is placed after.
 *
 * Both checkouts lay a floating label over the input, so the checkbox goes
 * after the whole field rather than straight after the input, where the label
 * would swallow its clicks.
 *
 * @param input State Registration input.
 * @return Element the checkbox follows.
 */
const anchorFor = ( input: HTMLInputElement ): Element =>
	input.closest( '.wc-block-components-text-input' ) ||
	input.closest( '.form-row' ) ||
	input;

/**
 * The checkbox already added for an input, wherever it currently sits.
 *
 * @param input State Registration input.
 * @return The checkbox wrapper, or null.
 */
const wrapperFor = ( input: HTMLInputElement ): Element | null =>
	input.id
		? input.ownerDocument.querySelector(
				`.wcbcf-ie-exempt[data-bmw-for="${ input.id }"]`
		  )
		: null;

/**
 * Put the checkbox back after its field.
 *
 * The classic checkout re-appends every row WooCommerce knows in locale order
 * whenever the country changes, which leaves the checkbox stranded at the top
 * of the form. It cannot live inside the row instead: WooCommerce reads an
 * unticked checkbox there as an empty required field and paints the row red.
 *
 * @param input State Registration input.
 */
export function placeIeExempt( input: HTMLInputElement | null | undefined ) {
	if ( ! input ) {
		return;
	}

	const wrapper = wrapperFor( input );
	const anchor = anchorFor( input );

	if ( wrapper && anchor.nextElementSibling !== wrapper ) {
		anchor.insertAdjacentElement( 'afterend', wrapper );
	}
}

/**
 * The checkbox as a row of the classic form, in WooCommerce's markup.
 *
 * It takes the column of the State Registration row, and the priority right
 * after it, which is what WooCommerce sorts rows by when the country changes.
 * The row carries no validate-required class, so WooCommerce does not read
 * the unticked box as a missing value.
 *
 * @param row      State Registration row.
 * @param checkbox Checkbox.
 * @param text     Label text.
 * @return The row.
 */
function classicCheckbox(
	row: Element,
	checkbox: HTMLInputElement,
	text: HTMLElement
): HTMLElement {
	const doc = row.ownerDocument;
	const wrapper = doc.createElement( 'p' );
	const label = doc.createElement( 'label' );

	wrapper.className = [ 'form-row', ...row.classList ]
		.filter( ( name ) =>
			[
				'form-row',
				'form-row-first',
				'form-row-last',
				'form-row-wide',
				'person-type-field',
			].includes( name )
		)
		.join( ' ' );

	const priority = parseInt( row.getAttribute( 'data-priority' ) || '', 10 );

	if ( ! Number.isNaN( priority ) ) {
		wrapper.dataset.priority = String( priority + 1 );
	}

	label.className =
		'woocommerce-form__label woocommerce-form__label-for-checkbox checkbox';
	checkbox.className =
		'woocommerce-form__input woocommerce-form__input-checkbox input-checkbox';

	label.append( checkbox, ' ', text );
	wrapper.append( label );

	return wrapper;
}

/**
 * The checkbox in the markup of the checkout block's own checkboxes.
 *
 * @param input    State Registration input.
 * @param checkbox Checkbox.
 * @param text     Label text.
 * @return The checkbox wrapper.
 */
function blockCheckbox(
	input: HTMLInputElement,
	checkbox: HTMLInputElement,
	text: HTMLElement
): HTMLElement {
	const doc = input.ownerDocument;
	const wrapper = doc.createElement( 'div' );
	const label = doc.createElement( 'label' );

	wrapper.className = 'wc-block-components-checkbox';
	checkbox.className = 'wc-block-components-checkbox__input';
	text.className = 'wc-block-components-checkbox__label';

	if ( input.id ) {
		checkbox.id = `${ input.id }-exempt`;
		label.htmlFor = checkbox.id;
	}

	const mark = doc.createElementNS( 'http://www.w3.org/2000/svg', 'svg' );
	mark.setAttribute( 'class', 'wc-block-components-checkbox__mark' );
	mark.setAttribute( 'aria-hidden', 'true' );
	mark.setAttribute( 'viewBox', '0 0 24 20' );

	const path = doc.createElementNS( 'http://www.w3.org/2000/svg', 'path' );
	path.setAttribute(
		'd',
		'M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z'
	);
	mark.append( path );

	label.append( checkbox, mark, text );
	wrapper.append( label );

	return wrapper;
}

/**
 * Add an exemption checkbox to a State Registration input.
 *
 * @param input         State Registration input.
 * @param options       Options.
 * @param options.write Writes a value into the input.
 * @return Removes the checkbox.
 */
export function bindIeExempt(
	input: HTMLInputElement | null | undefined,
	{ write }: IeExemptOptions = {}
): () => void {
	if ( ! input ) {
		return () => {};
	}

	if ( input.dataset.bmwIeExempt ) {
		placeIeExempt( input );

		return () => {};
	}

	input.dataset.bmwIeExempt = '1';

	const anchor = anchorFor( input );

	// A re-rendered input arrives without the marker, and the checkbox from the
	// previous render can be left behind anywhere the unmounted field used to
	// be, so everything that belongs to this input goes first.
	[ anchor.nextElementSibling, wrapperFor( input ) ].forEach( ( element ) => {
		if ( element && element.classList.contains( 'wcbcf-ie-exempt' ) ) {
			element.remove();
		}
	} );

	const setValue =
		write ||
		( ( target: HTMLInputElement, value: string ) => {
			target.value = value;
		} );

	const checkbox = input.ownerDocument.createElement( 'input' );
	checkbox.type = 'checkbox';

	const text = input.ownerDocument.createElement( 'span' );
	text.textContent = __(
		'Exempt from State Registration',
		'woocommerce-extra-checkout-fields-for-brazil'
	);

	const wrapper = anchor.classList.contains( 'form-row' )
		? classicCheckbox( anchor, checkbox, text )
		: blockCheckbox( input, checkbox, text );

	wrapper.classList.add( 'wcbcf-ie-exempt' );
	checkbox.classList.add( 'wcbcf-ie-exempt-input' );

	if ( input.id ) {
		wrapper.dataset.bmwFor = input.id;
	}

	anchor.insertAdjacentElement( 'afterend', wrapper );

	// A value carried over from a previous order should show as exempt.
	const applyState = ( checked: boolean ) => {
		input.readOnly = checked;
		input.classList.toggle( 'wcbcf-ie-exempt-on', checked );
	};

	checkbox.checked = isExempt( input.value );
	applyState( checkbox.checked );

	const onToggle = () => {
		setValue( input, checkbox.checked ? EXEMPT_VALUE : '' );
		applyState( checkbox.checked );

		if ( ! checkbox.checked ) {
			input.focus();
		}
	};

	// Typing ISENTO by hand should tick the box, and editing away should clear
	// it, so the two controls never disagree.
	const onInput = () => {
		if ( input.readOnly ) {
			return;
		}

		const exempt = isExempt( input.value );

		if ( exempt !== checkbox.checked ) {
			checkbox.checked = exempt;
		}
	};

	checkbox.addEventListener( 'change', onToggle );
	input.addEventListener( 'input', onInput );

	return () => {
		checkbox.removeEventListener( 'change', onToggle );
		input.removeEventListener( 'input', onInput );
		wrapper.remove();
		delete input.dataset.bmwIeExempt;
	};
}
