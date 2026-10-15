/**
 * Email domain typo suggestions.
 */

import { __, sprintf } from '@wordpress/i18n';
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

/**
 * Where the suggestion goes: after the block checkout's field wrapper, which
 * has a fixed height the text would overflow, or else after the input.
 *
 * @param input Email input.
 * @return Element the suggestion follows.
 */
const anchorFor = ( input: HTMLInputElement ): Element =>
	input.closest( '.wc-block-components-text-input' ) || input;

const suggestionNode = ( input: HTMLInputElement ): Element => {
	const anchor = anchorFor( input );
	const existing = anchor.nextElementSibling;

	if ( existing?.classList.contains( SUGGESTION_CLASS ) ) {
		return existing;
	}

	const node = document.createElement( 'div' );
	node.className = SUGGESTION_CLASS;
	anchor.insertAdjacentElement( 'afterend', node );

	return node;
};

/**
 * Suggest a corrected domain below an email input when it looks misspelled.
 *
 * @param input Email input to watch.
 * @return Detaches the listener.
 */
export function bindMailcheck(
	input: HTMLInputElement | null | undefined
): () => void {
	if ( ! input ) {
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
				node.textContent = sprintf(
					/* translators: %s: email address with the domain corrected */
					__(
						'Did you mean: %s?',
						'woocommerce-extra-checkout-fields-for-brazil'
					),
					suggestion.full
				);
			},
		} );
	};

	input.addEventListener( 'blur', handler );

	return () => input.removeEventListener( 'blur', handler );
}
