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
	if ( ! input || input.dataset.bmwIeExempt ) {
		return () => {};
	}

	input.dataset.bmwIeExempt = '1';

	// Both checkouts lay a floating label over the input, so the checkbox goes
	// after the whole field rather than straight after the input, where the
	// label would swallow its clicks. On the classic checkout it goes inside
	// the row: WooCommerce re-appends every field it knows in locale order
	// whenever the country changes, which would strand a sibling of its own at
	// the top of the form.
	const blockField = input.closest( '.wc-block-components-text-input' );
	const row = blockField ? null : input.closest( '.form-row' );
	const anchor = blockField || row || input;
	const position: InsertPosition = row ? 'beforeend' : 'afterend';

	// A re-rendered input arrives without the marker, and the checkbox from the
	// previous render can be left behind anywhere the unmounted field used to
	// be, so everything that belongs to this input goes first.
	const alongside = row
		? row.querySelector( ':scope > .wcbcf-ie-exempt' )
		: anchor.nextElementSibling;
	const owned = input.id
		? Array.from(
				input.ownerDocument.querySelectorAll(
					`.wcbcf-ie-exempt[data-bmw-for="${ input.id }"]`
				)
		  )
		: [];

	[ alongside, ...owned ].forEach( ( element ) => {
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
	anchor.insertAdjacentElement( position, wrapper );

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
