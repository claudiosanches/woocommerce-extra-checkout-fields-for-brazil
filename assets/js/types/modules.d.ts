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
