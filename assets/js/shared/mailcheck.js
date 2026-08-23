/**
 * Email domain typo suggestions.
 */

import Mailcheck from 'mailcheck';

const SUGGESTION_CLASS = 'wcbcf-mailsuggest';

const suggestionNode = ( input ) => {
	const existing = input.parentNode?.querySelector(
		`.${ SUGGESTION_CLASS }`
	);

	if ( existing ) {
		return existing;
	}

	const node = document.createElement( 'div' );
	node.className = SUGGESTION_CLASS;
	input.insertAdjacentElement( 'afterend', node );

	return node;
};

/**
 * Suggest a corrected domain below an email input when it looks misspelled.
 *
 * @param {HTMLInputElement} input    Email input to watch.
 * @param {string}           template Message with a `%hint%` placeholder.
 * @return {() => void} Detaches the listener.
 */
export function bindMailcheck( input, template ) {
	if ( ! input || ! template ) {
		return () => {};
	}

	const handler = () => {
		const node = suggestionNode( input );
		node.textContent = '';

		Mailcheck.run( {
			email: input.value,
			suggested: ( suggestion ) => {
				node.textContent = template.replace(
					'%hint%',
					suggestion.full
				);
			},
		} );
	};

	input.addEventListener( 'blur', handler );

	return () => input.removeEventListener( 'blur', handler );
}
