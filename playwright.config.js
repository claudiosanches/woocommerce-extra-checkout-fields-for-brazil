const path = require( 'path' );
const { defineConfig, devices } = require( '@playwright/test' );

const baseURL = process.env.WP_BASE_URL || 'http://localhost:8977';

// Real Google Chrome. Locally CHROME_PATH points at an installed binary; CI
// installs the `chrome` channel.
const chrome = process.env.CHROME_PATH
	? { launchOptions: { executablePath: process.env.CHROME_PATH } }
	: { channel: 'chrome' };

module.exports = defineConfig( {
	testDir: path.join( __dirname, 'tests', 'e2e' ),
	outputDir: path.join( __dirname, 'artifacts', 'e2e' ),
	globalSetup: require.resolve( './tests/e2e/global-setup.js' ),
	// The specs share one store, so they run in order rather than in parallel.
	workers: 1,
	fullyParallel: false,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 1 : 0,

	// A test that only passes on retry still points at a real race, and a
	// green run hides it. Retries stay, so the report names the culprit, but
	// the run fails.
	failOnFlakyTests: !! process.env.CI,
	timeout: 90_000,
	expect: { timeout: 15_000 },
	reporter: process.env.CI ? [ [ 'github' ], [ 'list' ] ] : [ [ 'list' ] ],
	use: {
		baseURL,
		headless: true,
		ignoreHTTPSErrors: true,
		actionTimeout: 15_000,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
	},
	projects: [
		{
			name: 'chrome',
			use: { ...devices[ 'Desktop Chrome' ], ...chrome },
		},
	],
} );
