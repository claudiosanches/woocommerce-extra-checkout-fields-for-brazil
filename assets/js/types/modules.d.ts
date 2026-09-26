declare module '*.scss';

declare module 'mailcheck' {
	interface Suggestion {
		address: string;
		domain: string;
		full: string;
	}

	interface RunOptions {
		email: string;
		domains?: readonly string[];
		secondLevelDomains?: readonly string[];
		topLevelDomains?: readonly string[];
		distanceFunction?: ( a: string, b: string ) => number;
		suggested?: ( suggestion: Suggestion ) => void;
		empty?: () => void;
	}

	const Mailcheck: {
		defaultDomains: string[];
		defaultSecondLevelDomains: string[];
		defaultTopLevelDomains: string[];
		run: ( opts: RunOptions ) => Suggestion | undefined;
	};

	export default Mailcheck;
}

// WooCommerce publishes these as script globals rather than npm packages, and
// the dependency extraction plugin maps the imports to them.
declare module '@woocommerce/blocks-checkout' {
	import type { ComponentType, ReactNode } from 'react';

	export const ExperimentalOrderMeta: ComponentType< {
		children?: ReactNode;
	} >;
}

declare module '@woocommerce/block-data' {
	export const CART_STORE_KEY: 'wc/store/cart';
	export const CHECKOUT_STORE_KEY: 'wc/store/checkout';
}

declare module '@woocommerce/price-format' {
	export function formatPrice(
		value: number | string,
		currency?: unknown
	): string;
	export function getCurrencyFromPriceResponse( response: object ): unknown;
}

declare module '@woocommerce/settings' {
	export function getSetting< T >( name: string, fallback?: T ): T;
}
