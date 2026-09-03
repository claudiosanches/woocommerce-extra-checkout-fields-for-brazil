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
	// label would swallow its clicks.
	const anchor =
		input.closest( '.wc-block-components-text-input' ) ||
		input.closest( '.form-row' ) ||
		input;

	// A re-rendered input arrives without the marker but can still be followed
	// by the checkbox from the previous render.
	const stale = anchor.nextElementSibling;

	if ( stale && stale.classList.contains( 'wcbcf-ie-exempt' ) ) {
		stale.remove();
	}

	const setValue =
		write ||
		( ( target: HTMLInputElement, value: string ) => {
			target.value = value;
		} );

	const wrapper = input.ownerDocument.createElement( 'label' );
	wrapper.className = 'wcbcf-ie-exempt';

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
