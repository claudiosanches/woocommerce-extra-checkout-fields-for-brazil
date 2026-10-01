<?php
/**
 * Settings screen text that depends on the store.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

require_once CSBMW_PLUGIN_DIR . '/includes/admin/class-extra-checkout-fields-for-brazil-settings.php';

/**
 * Settings screen test.
 */
class SettingsScreenTest extends WP_UnitTestCase {

	/**
	 * The field layout description with a checkout page holding the content.
	 *
	 * @param string $content Checkout page content.
	 *
	 * @return string
	 */
	protected function layout_description( $content ) {
		$page = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_content' => $content,
			)
		);

		update_option( 'woocommerce_checkout_page_id', $page );

		$method = new ReflectionMethod( Extra_Checkout_Fields_For_Brazil_Settings::class, 'fields_style_description' );
		$method->setAccessible( true );

		return $method->invoke( new Extra_Checkout_Fields_For_Brazil_Settings() );
	}

	public function test_the_layout_says_the_checkout_block_arranges_its_own_fields() {
		$this->assertStringContainsString(
			'changes only the My Account address forms',
			$this->layout_description( '<!-- wp:woocommerce/checkout --><div class="wp-block-woocommerce-checkout"></div><!-- /wp:woocommerce/checkout -->' )
		);
	}

	public function test_the_layout_applies_to_the_classic_checkout() {
		$description = $this->layout_description( '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->' );

		$this->assertStringContainsString( 'classic checkout', $description );
		$this->assertStringNotContainsString( 'Checkout block', $description );
	}
}
