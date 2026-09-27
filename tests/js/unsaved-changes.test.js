/**
 * @jest-environment jsdom
 */

import { changedTabs } from '../../assets/js/admin/unsaved-changes';

const setup = () => {
	document.body.innerHTML = `
		<form>
			<input type="hidden" name="_wpnonce" value="abc" />
			<div data-bmw-tab="fields">
				<select name="rg">
					<option value="">Disabled</option>
					<option value="required" selected>Required</option>
				</select>
				<input type="text" name="note" value="kept" />
			</div>
			<div data-bmw-tab="features">
				<input type="checkbox" name="mailcheck" value="1" checked />
			</div>
		</form>`;

	return document.querySelector( 'form' );
};

describe( 'changedTabs', () => {
	it( 'is empty for the page as loaded', () => {
		expect( changedTabs( setup() ).size ).toBe( 0 );
	} );

	it( 'names the tab of each changed setting', () => {
		const form = setup();

		form.elements.rg.value = '';
		form.elements.mailcheck.checked = false;

		expect( [ ...changedTabs( form ) ] ).toEqual( [
			'fields',
			'features',
		] );
	} );

	it( 'counts typed text', () => {
		const form = setup();

		form.elements.note.value = 'edited';

		expect( [ ...changedTabs( form ) ] ).toEqual( [ 'fields' ] );
	} );

	it( 'forgets a setting changed back', () => {
		const form = setup();

		form.elements.mailcheck.checked = false;
		form.elements.mailcheck.checked = true;

		expect( changedTabs( form ).size ).toBe( 0 );
	} );

	it( 'ignores fields outside a tab', () => {
		const form = setup();

		form.elements._wpnonce.value = 'changed';

		expect( changedTabs( form ).size ).toBe( 0 );
	} );
} );
