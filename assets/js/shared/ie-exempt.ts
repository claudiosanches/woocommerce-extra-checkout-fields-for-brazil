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

export const EXEMPT_VALUE = 'ISENTO';

export interface IeExemptOptions {
	label?: string;
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
 * Add an exemption checkbox to a State Registration input.
 *
 * @param input         State Registration input.
 * @param options       Options.
 * @param options.label Checkbox label.
 * @param options.write Writes a value into the input.
 * @return Removes the checkbox.
 */
export function bindIeExempt(
	input: HTMLInputElement | null | undefined,
	{ label, write }: IeExemptOptions = {}
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

	const wrapper = input.ownerDocument.createElement( 'label' );
	wrapper.className = 'wcbcf-ie-exempt';

	if ( input.id ) {
		wrapper.dataset.bmwFor = input.id;
	}

	const checkbox = input.ownerDocument.createElement( 'input' );
	checkbox.type = 'checkbox';
	checkbox.className = 'wcbcf-ie-exempt-input';

	const text = input.ownerDocument.createElement( 'span' );
	text.textContent = label || 'Exempt';

	wrapper.append( checkbox, text );
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
