/**
 * A checkbox drawn inside a text field that fills it with a fixed value.
 *
 * Some fields have a conventional value for "none": ISENTO for a company
 * without a state registration, S/N for an address without a number.
 * Customers have no way of guessing it from a field that only says it is
 * required, so the toggle writes it for them.
 *
 * The value is what gets stored, so nothing downstream has to know the
 * toggle exists: the field still holds a plain string.
 */

export interface ValueToggleOptions {
	/** What the toggle writes. */
	value: string;
	/** Class naming this toggle, alongside the shared one. */
	name: string;
	/** Accessible name. */
	label: string;
	/** Visible text beside the box. */
	text: string;
	/** Writes a value into the input. */
	write?: ( input: HTMLInputElement, value: string ) => void;
}

/**
 * Whether a value is the toggle's, whatever case it was typed in.
 *
 * @param value  Field value.
 * @param target The toggle's value.
 * @return True when they match.
 */
export const isValue = (
	value: string | null | undefined,
	target: string
): boolean =>
	String( value ?? '' )
		.trim()
		.toUpperCase() === target.trim().toUpperCase();

/**
 * The toggle already added for an input, wherever it currently sits.
 *
 * @param input Text input.
 * @param name  Toggle name.
 * @return The toggle, or null.
 */
export const toggleFor = (
	input: HTMLInputElement,
	name: string
): Element | null =>
	input.id
		? input.ownerDocument.querySelector(
				`.wcbcf-${ name }[data-bmw-for="${ input.id }"]`
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

const toggleText = ( doc: Document, text: string ): HTMLElement => {
	const element = doc.createElement( 'span' );
	element.className = 'wcbcf-value-toggle-text';
	element.setAttribute( 'aria-hidden', 'true' );
	element.textContent = text;

	return element;
};

/**
 * The toggle as one of the checkout block's own checkboxes, so WooCommerce's
 * styles draw it.
 *
 * @param input   Text input.
 * @param options Toggle options.
 * @return The toggle.
 */
function blockToggle(
	input: HTMLInputElement,
	options: ValueToggleOptions
): Toggle {
	const doc = input.ownerDocument;
	const element = doc.createElement( 'span' );
	const label = doc.createElement( 'label' );
	const checkbox = doc.createElement( 'input' );

	element.className = `wc-block-components-checkbox wcbcf-value-toggle wcbcf-${ options.name }`;
	label.className = 'wcbcf-value-toggle-label';
	checkbox.type = 'checkbox';
	checkbox.className = 'wc-block-components-checkbox__input';
	checkbox.setAttribute( 'aria-label', options.label );

	const text = toggleText( doc, options.text );
	text.classList.add( 'wc-block-components-checkbox__label' );

	label.append(
		checkbox,
		checkMark( doc, 'wc-block-components-checkbox__mark' ),
		text
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
 * The order screen gets it too, having no checkbox styles of its own.
 *
 * @param input   Text input.
 * @param options Toggle options.
 * @return The toggle.
 */
function classicToggle(
	input: HTMLInputElement,
	options: ValueToggleOptions
): Toggle {
	const doc = input.ownerDocument;
	const element = doc.createElement( 'button' );
	const box = doc.createElement( 'span' );

	element.type = 'button';
	element.className = `wcbcf-value-toggle wcbcf-${ options.name }`;
	element.setAttribute( 'role', 'checkbox' );
	element.setAttribute( 'aria-checked', 'false' );
	element.setAttribute( 'aria-label', options.label );

	box.className = 'wcbcf-value-toggle-box';
	box.append( checkMark( doc, 'wcbcf-value-toggle-mark' ) );
	element.append( box, toggleText( doc, options.text ) );

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
 * @param input  Text input.
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
 * Add a value toggle inside a text input.
 *
 * @param input   Text input.
 * @param options Toggle options.
 * @return Removes the toggle.
 */
export function bindValueToggle(
	input: HTMLInputElement | null | undefined,
	options: ValueToggleOptions
): () => void {
	if ( ! input || input.dataset.bmwValueToggle ) {
		return () => {};
	}

	input.dataset.bmwValueToggle = options.name;

	// A re-rendered input arrives without the marker, and the toggle from the
	// previous render can be left behind, so it goes first.
	toggleFor( input, options.name )?.remove();

	const setValue =
		options.write ||
		( ( target: HTMLInputElement, value: string ) => {
			target.value = value;
		} );

	const toggle = input.closest( '.wc-block-components-text-input' )
		? blockToggle( input, options )
		: classicToggle( input, options );

	if ( input.id ) {
		toggle.element.dataset.bmwFor = input.id;
	}

	input.insertAdjacentElement( 'afterend', toggle.element );

	const stopFollowing = followInput( input, toggle.element );

	// A value carried over from a previous order should show as ticked.
	const applyState = ( on: boolean ) => {
		toggle.setOn( on );
		input.readOnly = on;
	};

	applyState( isValue( input.value, options.value ) );

	const stopToggling = toggle.onToggle( () => {
		const on = toggle.isOn();

		setValue( input, on ? options.value : '' );
		applyState( on );

		if ( ! on ) {
			input.focus();
		}
	} );

	// Typing the value by hand should tick the box, and editing away should
	// clear it, so the two controls never disagree.
	const onInput = () => {
		if ( ! input.readOnly ) {
			toggle.setOn( isValue( input.value, options.value ) );
		}
	};

	input.addEventListener( 'input', onInput );

	return () => {
		stopFollowing();
		stopToggling();
		input.removeEventListener( 'input', onInput );
		toggle.element.remove();
		input.style.paddingRight = '';
		delete input.dataset.bmwValueToggle;
	};
}
