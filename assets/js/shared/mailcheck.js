/**
 * Email domain typo suggestions.
 */

import Mailcheck from 'mailcheck';

const SUGGESTION_CLASS = 'wcbcf-mailsuggest';

// Mailcheck ships no Brazilian domains, so it reads every `.com.br` address as
// a misspelling of `.com` and suggests cutting the country off.
const DOMAINS = [
	...Mailcheck.defaultDomains,
	'bol.com.br',
	'globo.com',
	'globomail.com',
	'hotmail.com.br',
	'ig.com.br',
	'live.com',
	'oi.com.br',
	'outlook.com.br',
	'terra.com.br',
	'uol.com.br',
	'yahoo.com.br',
];

const TOP_LEVEL_DOMAINS = [
	...Mailcheck.defaultTopLevelDomains,
	'com.br',
	'net.br',
	'org.br',
	'edu.br',
	'gov.br',
];

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
			domains: DOMAINS,
			topLevelDomains: TOP_LEVEL_DOMAINS,
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
