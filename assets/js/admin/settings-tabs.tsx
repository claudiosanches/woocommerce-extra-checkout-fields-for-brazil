/**
 * Tabs for the settings page.
 *
 * Every section stays in the one form, so a save still posts all settings.
 * The tabs only choose which sections are shown, and the active one goes in
 * the URL so a reload, a link or a save comes back to it.
 */

import { TabPanel } from '@wordpress/components';
import { createRoot, useEffect } from '@wordpress/element';

type Tabs = Record< string, string >;

const HIDDEN_CLASS = 'bmw-is-other-tab';

/**
 * The tab named in the URL, when it is one of them.
 *
 * @param names Tab names.
 * @return The tab to open.
 */
function requestedTab( names: string[] ): string | undefined {
	const tab = new URLSearchParams( window.location.search ).get( 'tab' );

	return tab && names.includes( tab ) ? tab : names[ 0 ];
}

/**
 * Show the sections of one tab and remember it.
 *
 * @param name Tab name.
 * @param form Settings form.
 */
function showTab( name: string, form: HTMLFormElement ): void {
	form.querySelectorAll< HTMLElement >( '[data-bmw-tab]' ).forEach(
		( section ) =>
			section.classList.toggle(
				HIDDEN_CLASS,
				section.dataset.bmwTab !== name
			)
	);

	const url = new URL( window.location.href );
	url.searchParams.set( 'tab', name );
	window.history.replaceState( null, '', url );

	// options.php sends the browser back to the referer after a save.
	const referer = form.querySelector< HTMLInputElement >(
		'input[name="_wp_http_referer"]'
	);

	if ( referer ) {
		referer.value = url.pathname + url.search;
	}
}

function ActiveTab( { name, form }: { name: string; form: HTMLFormElement } ) {
	useEffect( () => showTab( name, form ), [ name, form ] );

	return null;
}

const mount = document.getElementById( 'bmw-settings-tabs' );
const form = document.getElementById( 'bmw-settings' );

if ( mount && form instanceof HTMLFormElement ) {
	const tabs: Tabs = JSON.parse( mount.dataset.tabs ?? '{}' );
	const names = Object.keys( tabs );

	createRoot( mount ).render(
		<TabPanel
			className="bmw-settings-tabs"
			initialTabName={ requestedTab( names ) }
			tabs={ Object.entries( tabs ).map( ( [ name, title ] ) => ( {
				name,
				title,
			} ) ) }
		>
			{ ( tab ) => <ActiveTab name={ tab.name } form={ form } /> }
		</TabPanel>
	);
}
