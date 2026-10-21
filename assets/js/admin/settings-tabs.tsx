/**
 * Tabs for the settings page.
 *
 * Every section stays in the one form, so a save still posts all settings.
 * The tabs only choose which sections are shown, and the active one goes in
 * the URL so a reload, a link or a save comes back to it. A tab holding an
 * unsaved change is marked, since switching tabs hides it.
 */

import { TabPanel } from '@wordpress/components';
import { createRoot, useEffect, useState } from '@wordpress/element';
import { changedTabs } from './unsaved-changes';

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

/**
 * Mark the tabs holding unsaved changes, and ask before leaving with any.
 *
 * @param form Settings form.
 * @return Tab names.
 */
function useChangedTabs( form: HTMLFormElement ): Set< string > {
	const [ changed, setChanged ] = useState( () => new Set< string >() );

	useEffect( () => {
		let saving = false;

		const update = () => setChanged( changedTabs( form ) );
		const onSubmit = () => {
			saving = true;
		};
		const onBeforeUnload = ( event: BeforeUnloadEvent ) => {
			if ( ! saving && changedTabs( form ).size ) {
				event.preventDefault();
			}
		};

		update();
		form.addEventListener( 'input', update );
		form.addEventListener( 'change', update );
		form.addEventListener( 'submit', onSubmit );
		window.addEventListener( 'beforeunload', onBeforeUnload );

		return () => {
			form.removeEventListener( 'input', update );
			form.removeEventListener( 'change', update );
			form.removeEventListener( 'submit', onSubmit );
			window.removeEventListener( 'beforeunload', onBeforeUnload );
		};
	}, [ form ] );

	return changed;
}

function SettingsTabs( { tabs, form }: { tabs: Tabs; form: HTMLFormElement } ) {
	const changed = useChangedTabs( form );

	return (
		<TabPanel
			className="bmw-settings-tabs"
			initialTabName={ requestedTab( Object.keys( tabs ) ) }
			tabs={ Object.entries( tabs ).map( ( [ name, title ] ) => ( {
				name,
				title,
				className: changed.has( name ) ? 'bmw-has-changes' : '',
			} ) ) }
		>
			{ ( tab ) => <ActiveTab name={ tab.name } form={ form } /> }
		</TabPanel>
	);
}

const mount = document.getElementById( 'bmw-settings-tabs' );
const form = document.getElementById( 'bmw-settings' );

if ( mount && form instanceof HTMLFormElement ) {
	const tabs: Tabs = JSON.parse( mount.dataset.tabs ?? '{}' );

	createRoot( mount ).render( <SettingsTabs tabs={ tabs } form={ form } /> );
}
