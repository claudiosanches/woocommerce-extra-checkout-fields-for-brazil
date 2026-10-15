/**
 * Which settings differ from what the page was loaded with.
 *
 * The comparison is against the defaults in the markup rather than a copy
 * taken on load, so values the browser restores on a back navigation still
 * count as changes, and changing a value back clears it.
 */

/**
 * Whether a field holds something other than what the page was loaded with.
 *
 * @param field Form field.
 * @return True when it changed.
 */
export function isChanged( field: Element ): boolean {
	if ( field instanceof HTMLSelectElement ) {
		return Array.from( field.options ).some(
			( option ) => option.selected !== option.defaultSelected
		);
	}

	if ( field instanceof HTMLInputElement ) {
		return 'checkbox' === field.type || 'radio' === field.type
			? field.checked !== field.defaultChecked
			: field.value !== field.defaultValue;
	}

	return (
		field instanceof HTMLTextAreaElement &&
		field.value !== field.defaultValue
	);
}

/**
 * The tabs holding a changed setting.
 *
 * @param form Settings form.
 * @return Tab names.
 */
export function changedTabs( form: HTMLFormElement ): Set< string > {
	const tabs = new Set< string >();

	Array.from( form.elements ).forEach( ( field ) => {
		const tab =
			field.closest< HTMLElement >( '[data-bmw-tab]' )?.dataset.bmwTab;

		if ( tab && isChanged( field ) ) {
			tabs.add( tab );
		}
	} );

	return tabs;
}
