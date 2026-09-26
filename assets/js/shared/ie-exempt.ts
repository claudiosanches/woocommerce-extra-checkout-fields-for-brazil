/**
 * "Exempt" toggle for the State Registration field.
 *
 * Companies without a state registration have to send the literal word
 * ISENTO on the invoice, which customers have no way of guessing from a
 * field that only says it is required. The toggle writes it for them.
 *
 * The value is what gets stored, so nothing downstream has to know the
 * toggle exists: the field still holds a plain string.
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
 * The toggle already added for an input, wherever it currently sits.
 *
 * @param input State Registration input.
 * @return The toggle, or null.
 */
const toggleFor = ( input: HTMLInputElement ): Element | null =>
	input.id
		? input.ownerDocument.querySelector(
				`.wcbcf-ie-exempt[data-bmw-for="${ input.id }"]`
		  )
		: null;

interface Toggle {
	element: HTMLElement;
	isOn: () => boolean;
	setOn: ( on: boolean ) => void;
	onToggle: ( listener: () => void ) => () => void;
}

/**
 * WooCommerce's check mark.
 *
 * @param doc       Document.
 * @param className Class for the icon.
 * @return The icon.
 */
function checkMark( doc: Document, className: string ): SVGSVGElement {
	const mark = doc.createElementNS( 'http://www.w3.org/2000/svg', 'svg' );
	mark.setAttribute( 'class', className );
	mark.setAttribute( 'aria-hidden', 'true' );
	mark.setAttribute( 'viewBox', '0 0 24 20' );

	const path = doc.createElementNS( 'http://www.w3.org/2000/svg', 'path' );
	path.setAttribute(
		'd',
		'M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z'
	);
	mark.append( path );

	return mark;
}

const exemptLabel = () =>
	__(
		'Exempt from State Registration',
		'woocommerce-extra-checkout-fields-for-brazil'
	);

const exemptText = ( doc: Document, className: string ): HTMLElement => {
	const text = doc.createElement( 'span' );
	text.className = className;
	text.setAttribute( 'aria-hidden', 'true' );
	text.textContent = __(
		'Exempt',
		'woocommerce-extra-checkout-fields-for-brazil'
	);

	return text;
};

/**
 * The toggle as one of the checkout block's own checkboxes, so WooCommerce's
 * styles draw it.
 *
 * @param input State Registration input.
 * @return The toggle.
 */
function blockToggle( input: HTMLInputElement ): Toggle {
	const doc = input.ownerDocument;
	const element = doc.createElement( 'span' );
	const label = doc.createElement( 'label' );
	const checkbox = doc.createElement( 'input' );

	element.className = 'wc-block-components-checkbox wcbcf-ie-exempt';
	label.className = 'wcbcf-ie-exempt-label';
	checkbox.type = 'checkbox';
	checkbox.className = 'wc-block-components-checkbox__input';
	checkbox.setAttribute( 'aria-label', exemptLabel() );

	label.append(
		checkbox,
		checkMark( doc, 'wc-block-components-checkbox__mark' ),
		exemptText(
			doc,
			'wc-block-components-checkbox__label wcbcf-ie-exempt-text'
		)
	);
	element.append( label );

	return {
		element,
		isOn: () => checkbox.checked,
		setOn: ( on ) => {
			checkbox.checked = on;
		},
		onToggle: ( listener ) => {
			checkbox.addEventListener( 'change', listener );

			return () => checkbox.removeEventListener( 'change', listener );
		},
	};
}

/**
 * The toggle as a button drawn like the block checkbox.
 *
 * The classic checkout treats a required row holding an unticked checkbox as
 * empty, whatever the text field says, so a real checkbox cannot live here.
 *
 * @param input State Registration input.
 * @return The toggle.
 */
function classicToggle( input: HTMLInputElement ): Toggle {
	const doc = input.ownerDocument;
	const element = doc.createElement( 'button' );
	const box = doc.createElement( 'span' );

	element.type = 'button';
	element.className = 'wcbcf-ie-exempt';
	element.setAttribute( 'role', 'checkbox' );
	element.setAttribute( 'aria-checked', 'false' );
	element.setAttribute( 'aria-label', exemptLabel() );

	box.className = 'wcbcf-ie-exempt-box';
	box.append( checkMark( doc, 'wcbcf-ie-exempt-mark' ) );
	element.append( box, exemptText( doc, 'wcbcf-ie-exempt-text' ) );

	const isOn = () => 'true' === element.getAttribute( 'aria-checked' );
	const setOn = ( on: boolean ) =>
		element.setAttribute( 'aria-checked', on ? 'true' : 'false' );

	return {
		element,
		isOn,
		setOn,
		onToggle: ( listener ) => {
			const onClick = () => {
				setOn( ! isOn() );
				listener();
			};

			element.addEventListener( 'click', onClick );

			return () => element.removeEventListener( 'click', onClick );
		},
	};
}

/**
 * Keep the toggle over the right end of the input.
 *
 * The block checkout renders the field's error inside the same container,
 * below the input, so the toggle is lined up with the input itself rather
 * than centred in the container. The input is padded so text never runs
 * under it.
 *
 * @param input  State Registration input.
 * @param toggle The toggle.
 * @return Stops following the input.
 */
function followInput(
	input: HTMLInputElement,
	toggle: HTMLElement
): () => void {
	const place = () => {
		toggle.style.top = `${ input.offsetTop }px`;
		toggle.style.height = `${ input.offsetHeight }px`;
		input.style.paddingRight = `${ toggle.offsetWidth + 16 }px`;
	};

	place();

	// Missing in older browsers and in tests, where the first placement is
	// all there is.
	if ( 'function' !== typeof window.ResizeObserver ) {
		return () => {};
	}

	// Also fires when a hidden row is shown and the input gets a size.
	const observer = new window.ResizeObserver( place );
	observer.observe( input );

	return () => observer.disconnect();
}

/**
 * Add an exemption toggle inside a State Registration input.
 *
 * @param input         State Registration input.
 * @param options       Options.
 * @param options.write Writes a value into the input.
 * @return Removes the toggle.
 */
export function bindIeExempt(
	input: HTMLInputElement | null | undefined,
	{ write }: IeExemptOptions = {}
): () => void {
	if ( ! input || input.dataset.bmwIeExempt ) {
		return () => {};
	}

	input.dataset.bmwIeExempt = '1';

	// A re-rendered input arrives without the marker, and the toggle from the
	// previous render can be left behind, so it goes first.
	toggleFor( input )?.remove();

	const setValue =
		write ||
		( ( target: HTMLInputElement, value: string ) => {
			target.value = value;
		} );

	const toggle = input.closest( '.form-row' )
		? classicToggle( input )
		: blockToggle( input );

	if ( input.id ) {
		toggle.element.dataset.bmwFor = input.id;
	}

	input.insertAdjacentElement( 'afterend', toggle.element );

	const stopFollowing = followInput( input, toggle.element );

	// A value carried over from a previous order should show as exempt.
	const applyState = ( on: boolean ) => {
		toggle.setOn( on );
		input.readOnly = on;
	};

	applyState( isExempt( input.value ) );

	const stopToggling = toggle.onToggle( () => {
		const on = toggle.isOn();

		setValue( input, on ? EXEMPT_VALUE : '' );
		applyState( on );

		if ( ! on ) {
			input.focus();
		}
	} );

	// Typing ISENTO by hand should tick the box, and editing away should clear
	// it, so the two controls never disagree.
	const onInput = () => {
		if ( ! input.readOnly ) {
			toggle.setOn( isExempt( input.value ) );
		}
	};

	input.addEventListener( 'input', onInput );

	return () => {
		stopFollowing();
		stopToggling();
		input.removeEventListener( 'input', onInput );
		toggle.element.remove();
		input.style.paddingRight = '';
		delete input.dataset.bmwIeExempt;
	};
}
