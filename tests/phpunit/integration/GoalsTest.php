<?php
/**
 * Spending goals.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Tests
 */

/**
 * Goals test.
 */
class GoalsTest extends WP_UnitTestCase {

	/**
	 * Product costing 50.
	 *
	 * @var WC_Product_Simple
	 */
	protected $product;

	public function set_up() {
		parent::set_up();

		update_option(
			'wcbcf_settings',
			array(
				'goals_cart'          => '1',
				'goals_free_shipping' => '1',
			)
		);
		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'BR' ) );
		update_option( 'woocommerce_ship_to_countries', '' );

		if ( null === WC()->session ) {
			wc_load_cart();
		}

		WC()->session->set( Extra_Checkout_Fields_For_Brazil_Goals::APPLIED, array() );
		WC()->session->set( Extra_Checkout_Fields_For_Brazil_Goals::DECLINED, array() );
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();
		WC()->customer->set_shipping_country( 'BR' );
		WC()->customer->set_shipping_state( '' );
		WC()->customer->set_shipping_postcode( '' );

		$this->product = new WC_Product_Simple();
		$this->product->set_regular_price( '50' );
		$this->product->save();
	}

	public function tear_down() {
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();
		wc_clear_notices();

		foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
			WC_Shipping_Zones::delete_zone( $zone['id'] );
		}

		parent::tear_down();
	}

	/**
	 * Put the product in the cart.
	 *
	 * @param int $quantity Quantity.
	 */
	protected function fill_cart( $quantity ) {
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $this->product->get_id(), $quantity );
		WC()->cart->calculate_totals();
	}

	/**
	 * A coupon with a minimum spend.
	 *
	 * @param string $code    Code.
	 * @param float  $minimum Minimum spend.
	 * @param bool   $goal    Whether it is marked as a goal.
	 *
	 * @return WC_Coupon
	 */
	protected function coupon( $code, $minimum, $goal = true ) {
		$coupon = new WC_Coupon();
		$coupon->set_code( $code );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 10 );
		$coupon->set_minimum_amount( $minimum );

		if ( $goal ) {
			$coupon->update_meta_data( Extra_Checkout_Fields_For_Brazil_Goals::COUPON_META, 'yes' );
		}

		$coupon->save();

		return $coupon;
	}

	/**
	 * A zone with a free shipping minimum.
	 *
	 * @param array  $locations Locations, as code => type.
	 * @param string $minimum   Minimum order amount, or empty for no free shipping.
	 * @param string $ignore    Whether discounts are left out of the minimum.
	 * @param string $requires  What free shipping requires.
	 */
	protected function zone( array $locations, $minimum, $ignore = 'no', $requires = 'min_amount' ) {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( implode( ',', array_keys( $locations ) ) );

		foreach ( $locations as $code => $type ) {
			$zone->add_location( $code, $type );
		}

		$zone->save();
		$zone->add_shipping_method( 'flat_rate' );

		if ( '' !== $minimum ) {
			$instance = $zone->add_shipping_method( 'free_shipping' );
			update_option(
				'woocommerce_free_shipping_' . $instance . '_settings',
				array(
					'requires'         => $requires,
					'min_amount'       => $minimum,
					'ignore_discounts' => $ignore,
				)
			);
		}

		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}

	/**
	 * Goals by type, with what they still need.
	 *
	 * @return array
	 */
	protected function remaining() {
		$goals = array();

		foreach ( Extra_Checkout_Fields_For_Brazil_Goals::get_goals() as $goal ) {
			$goals[ 'coupon' === $goal['type'] ? $goal['label'] : $goal['type'] ] = $goal['remaining'];
		}

		return $goals;
	}

	/**
	 * Shipping methods the cart is offered.
	 *
	 * @return string[]
	 */
	protected function rates() {
		$packages = WC()->shipping()->get_packages();

		return array_values( array_map( static fn( $rate ) => $rate->get_method_id(), $packages[0]['rates'] ?? array() ) );
	}

	/**
	 * Only coupons marked as goals, with a minimum spend, are goals.
	 */
	public function test_marked_coupons_with_a_minimum_are_goals() {
		$this->coupon( 'meta', 300 );
		$this->coupon( 'plain', 200, false );
		$this->coupon( 'nominimum', 0 );
		$this->fill_cart( 2 );

		$this->assertSame( array( '10% off' => 200.0 ), $this->remaining() );
	}

	/**
	 * A goal coupon applies once reached, leaves when the cart drops below it,
	 * and stays away once the customer removes it.
	 */
	public function test_goal_coupon_follows_the_cart() {
		$this->coupon( 'meta', 300 );

		$this->fill_cart( 6 );
		$this->assertTrue( WC()->cart->has_discount( 'meta' ) );
		$this->assertSame( 270.0, (float) WC()->cart->get_total( 'edit' ) );
		$this->assertSame( 0, wc_notice_count() );

		WC()->cart->set_quantity( array_key_first( WC()->cart->get_cart() ), 5 );
		$this->assertFalse( WC()->cart->has_discount( 'meta' ) );

		WC()->cart->set_quantity( array_key_first( WC()->cart->get_cart() ), 6 );
		$this->assertTrue( WC()->cart->has_discount( 'meta' ) );

		WC()->cart->remove_coupon( 'meta' );
		WC()->cart->calculate_totals();
		$this->assertFalse( WC()->cart->has_discount( 'meta' ) );
		$this->assertSame( array(), $this->remaining() );
	}

	/**
	 * A removed goal coupon comes back once the cart grows, or drops below
	 * its minimum and reaches it again.
	 */
	public function test_removed_goal_coupon_returns_when_the_cart_changes() {
		$this->coupon( 'meta', 300 );
		$this->fill_cart( 6 );
		$key = array_key_first( WC()->cart->get_cart() );

		WC()->cart->remove_coupon( 'meta' );
		WC()->cart->calculate_totals();
		$this->assertFalse( WC()->cart->has_discount( 'meta' ) );

		WC()->cart->set_quantity( $key, 7 );
		$this->assertTrue( WC()->cart->has_discount( 'meta' ) );

		WC()->cart->remove_coupon( 'meta' );
		WC()->cart->calculate_totals();
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$this->assertTrue( WC()->cart->has_discount( 'meta' ) );

		WC()->cart->remove_coupon( 'meta' );
		WC()->cart->calculate_totals();
		WC()->cart->set_quantity( $key, 7 );
		$this->assertFalse( WC()->cart->has_discount( 'meta' ) );

		WC()->cart->set_quantity( $key, 5 );
		WC()->cart->set_quantity( $key, 6 );
		$this->assertTrue( WC()->cart->has_discount( 'meta' ) );
	}

	/**
	 * A goal coupon past its maximum spend comes back once the cart is under
	 * it again, whoever took it off.
	 */
	public function test_goal_coupon_returns_below_its_maximum() {
		$coupon = $this->coupon( 'meta', 300 );
		$coupon->set_maximum_amount( 400 );
		$coupon->save();
		$this->fill_cart( 6 );
		$key = array_key_first( WC()->cart->get_cart() );
		$this->assertTrue( WC()->cart->has_discount( 'meta' ) );

		WC()->cart->set_quantity( $key, 9 );
		$this->assertFalse( WC()->cart->has_discount( 'meta' ) );
		$this->assertSame( array(), $this->remaining() );

		WC()->cart->set_quantity( $key, 7 );
		$this->assertTrue( WC()->cart->has_discount( 'meta' ) );

		// WooCommerce takes it off when the maximum drops below the cart.
		$coupon->set_maximum_amount( 320 );
		$coupon->save();
		WC()->cart->check_cart_coupons();
		wc_clear_notices();
		$this->assertFalse( WC()->cart->has_discount( 'meta' ) );

		$coupon->set_maximum_amount( 400 );
		$coupon->save();
		WC()->cart->calculate_totals();
		$this->assertTrue( WC()->cart->has_discount( 'meta' ) );
	}

	/**
	 * A goal coupon applies even with no bar shown on its own, as a block or
	 * shortcode may show it.
	 */
	public function test_goal_coupon_applies_without_an_automatic_bar() {
		update_option( 'wcbcf_settings', array() );
		$this->coupon( 'meta', 300 );

		$this->fill_cart( 6 );

		$this->assertTrue( WC()->cart->has_discount( 'meta' ) );
	}

	/**
	 * A goal coupon never pushes out, or is refused by, an individual use one.
	 */
	public function test_individual_use_coupons_are_left_alone() {
		$own = $this->coupon( 'own', 0, false );
		$own->set_individual_use( true );
		$own->save();
		$this->coupon( 'meta', 300 );

		$this->fill_cart( 1 );
		WC()->cart->apply_coupon( 'own' );
		wc_clear_notices();
		WC()->cart->set_quantity( array_key_first( WC()->cart->get_cart() ), 6 );

		$this->assertSame( array( 'own' ), WC()->cart->get_applied_coupons() );
		$this->assertSame( 0, wc_notice_count( 'error' ) );
		$this->assertSame( array(), $this->remaining() );

		// The other way round: an individual use goal keeps off another coupon.
		$own->set_individual_use( false );
		$own->save();
		$meta = new WC_Coupon( 'meta' );
		$meta->set_individual_use( true );
		$meta->save();
		WC()->cart->calculate_totals();

		$this->assertSame( array( 'own' ), WC()->cart->get_applied_coupons() );
		$this->assertSame( array(), $this->remaining() );
	}

	/**
	 * Goal coupons an individual use coupon replaces come back once the
	 * customer removes it, since the customer never removed them.
	 */
	public function test_goal_coupons_return_after_an_individual_use_coupon() {
		$own = $this->coupon( 'own', 0, false );
		$own->set_individual_use( true );
		$own->save();
		$this->coupon( 'meta', 300 );

		$this->fill_cart( 6 );
		$this->assertSame( array( 'meta' ), WC()->cart->get_applied_coupons() );

		WC()->cart->apply_coupon( 'own' );
		WC()->cart->calculate_totals();
		$this->assertSame( array( 'own' ), array_values( WC()->cart->get_applied_coupons() ) );

		WC()->cart->remove_coupon( 'own' );
		WC()->cart->calculate_totals();
		$this->assertSame( array( 'meta' ), array_values( WC()->cart->get_applied_coupons() ) );
		$this->assertSame( array( '10% off' => 0.0 ), $this->remaining() );
	}

	/**
	 * Free shipping follows the zone's minimum and its discount rule.
	 */
	public function test_free_shipping_follows_the_zone() {
		$this->zone( array( 'BR' => 'country' ), '150' );
		$this->fill_cart( 2 );

		$this->assertSame( array( 'free_shipping' => 50.0 ), $this->remaining() );

		$this->coupon( 'meta', 100 );
		WC()->cart->calculate_totals();

		// 60 after the 10% the goal coupon takes from it too.
		$this->assertSame( 66.67, $this->remaining()['free_shipping'] );
		$this->assertNotContains( 'free_shipping', $this->rates() );

		// Adding what the bar asks for is enough.
		$extra = new WC_Product_Simple();
		$extra->set_regular_price( '66.67' );
		$extra->save();
		WC()->cart->add_to_cart( $extra->get_id(), 1 );
		WC()->cart->calculate_totals();

		$this->assertContains( 'free_shipping', $this->rates() );
		$this->assertLessThanOrEqual( 0, $this->remaining()['free_shipping'] );
	}

	/**
	 * Before the CEP, free shipping shows only when every zone a Brazilian
	 * address can fall in agrees.
	 */
	public function test_free_shipping_waits_for_the_postcode_when_zones_differ() {
		$this->zone( array( 'BR:SP' => 'state' ), '150' );
		$this->zone( array( 'BR' => 'country' ), '300' );
		$this->fill_cart( 2 );

		$this->assertSame( array(), $this->remaining() );

		WC()->customer->set_shipping_postcode( '01001-000' );
		WC()->customer->set_shipping_state( 'SP' );
		$this->assertSame( array( 'free_shipping' => 50.0 ), $this->remaining() );

		WC()->customer->set_shipping_postcode( '20040-020' );
		WC()->customer->set_shipping_state( 'RJ' );
		$this->assertSame( array( 'free_shipping' => 200.0 ), $this->remaining() );
	}

	/**
	 * Zones that agree, or a zone that takes every Brazilian address first,
	 * leave no doubt.
	 */
	public function test_free_shipping_shows_before_the_postcode_when_zones_agree() {
		$this->zone( array( 'BR:SP' => 'state' ), '150' );
		$this->zone( array( 'BR' => 'country' ), '150' );
		$this->fill_cart( 2 );

		$this->assertSame( array( 'free_shipping' => 50.0 ), $this->remaining() );
	}

	/**
	 * With no zone for Brazil, the rest of the world decides.
	 */
	public function test_free_shipping_counts_the_rest_of_the_world() {
		$this->zone( array( 'BR:SP' => 'state' ), '150' );
		$this->fill_cart( 2 );

		$this->assertSame( array(), $this->remaining() );
	}

	/**
	 * A free shipping coupon counts only where the zone takes it.
	 */
	public function test_free_shipping_coupon_follows_the_zone() {
		$coupon = $this->coupon( 'fretegratis', 100 );
		$coupon->set_amount( 0 );
		$coupon->set_free_shipping( true );
		$coupon->save();

		// The zone asks for both, so the coupon alone is not enough.
		$this->zone( array( 'BR' => 'country' ), '300', 'no', 'both' );
		$this->fill_cart( 3 );
		$this->assertTrue( WC()->cart->has_discount( 'fretegratis' ) );
		$this->assertSame( array( 'free_shipping' => 150.0 ), $this->remaining() );
		$this->assertNotContains( 'free_shipping', $this->rates() );
		$this->assertStringStartsWith( 'Add', Extra_Checkout_Fields_For_Brazil_Goals::get_bar()['message'] );
	}

	/**
	 * Either the coupon or the minimum, whichever comes first, and the
	 * coupon alone with the zone's minimum turned off.
	 */
	public function test_free_shipping_takes_the_nearest_way() {
		$coupon = $this->coupon( 'fretegratis', 150 );
		$coupon->set_amount( 0 );
		$coupon->set_free_shipping( true );
		$coupon->save();

		$this->zone( array( 'BR' => 'country' ), '200', 'no', 'either' );
		$this->fill_cart( 2 );
		$this->assertSame( array( 'free_shipping' => 50.0 ), $this->remaining() );

		update_option( 'wcbcf_settings', array( 'goals_cart' => '1' ) );
		$this->assertSame( array( 'free_shipping' => 50.0 ), $this->remaining() );

		$coupon->set_minimum_amount( 250 );
		$coupon->save();
		$this->assertSame( array( 'free_shipping' => 150.0 ), $this->remaining() );
	}

	/**
	 * A coupon the zone's free shipping ignores claims nothing.
	 */
	public function test_free_shipping_coupon_without_a_zone_for_it() {
		$coupon = $this->coupon( 'fretegratis', 150 );
		$coupon->set_amount( 0 );
		$coupon->set_free_shipping( true );
		$coupon->save();

		$this->zone( array( 'BR' => 'country' ), '200' );
		$this->fill_cart( 2 );

		$this->assertSame( array( 'free_shipping' => 100.0 ), $this->remaining() );
	}

	/**
	 * Free shipping stays out when the setting is off or nothing ships.
	 */
	public function test_free_shipping_needs_the_setting_and_a_shipped_cart() {
		$this->zone( array( 'BR' => 'country' ), '150' );
		$this->fill_cart( 2 );

		update_option( 'wcbcf_settings', array( 'goals_cart' => '1' ) );
		$this->assertSame( array(), $this->remaining() );

		update_option( 'wcbcf_settings', array( 'goals_free_shipping' => '1' ) );
		$this->product->set_virtual( true );
		$this->product->save();
		$this->fill_cart( 2 );
		$this->assertSame( array(), $this->remaining() );
	}

	/**
	 * One bar marks every goal on the subtotal scale, and names the next one.
	 */
	public function test_bar_marks_every_goal() {
		$this->zone( array( 'BR' => 'country' ), '150', 'yes' );
		$this->coupon( 'meta', 300 );
		$this->fill_cart( 3 );

		$bar = Extra_Checkout_Fields_For_Brazil_Goals::get_bar();

		$this->assertSame( 'milestones', $bar['style'] );
		$this->assertStringContainsString( 'more to get 10% off.', $bar['message'] );
		$this->assertSame( 50.0, $bar['progress'] );
		$this->assertSame( array( 'free shipping', '10% off' ), wp_list_pluck( $bar['milestones'], 'label' ) );
		$this->assertSame( array( 50.0, 100.0 ), wp_list_pluck( $bar['milestones'], 'position' ) );
		$this->assertSame( array( true, false ), wp_list_pluck( $bar['milestones'], 'reached' ) );

		$this->assertSame(
			array( '10% off' ),
			array_values( wp_list_pluck( wp_list_filter( $bar['parts'], array( 'strong' => true ) ), 'text' ) )
		);

		$this->fill_cart( 6 );
		$bar = Extra_Checkout_Fields_For_Brazil_Goals::get_bar();

		$this->assertSame( 'You got free shipping and 10% off.', $bar['message'] );
		$this->assertSame(
			array(
				array(
					'text'   => 'You got ',
					'strong' => false,
				),
				array(
					'text'   => 'free shipping',
					'strong' => true,
				),
				array(
					'text'   => ' and ',
					'strong' => false,
				),
				array(
					'text'   => '10% off',
					'strong' => true,
				),
				array(
					'text'   => '.',
					'strong' => false,
				),
			),
			$bar['parts']
		);
	}

	/**
	 * Past the last goal the bar holds still: full, with each goal at its own
	 * amount, even as a discount grows with the cart.
	 */
	public function test_bar_holds_still_past_the_last_goal() {
		$this->zone( array( 'BR' => 'country' ), '150' );
		$this->coupon( 'meta', 300 );

		foreach ( array( 6, 12, 40 ) as $quantity ) {
			$this->fill_cart( $quantity );
			$bar = Extra_Checkout_Fields_For_Brazil_Goals::get_bar();

			$this->assertSame( 100.0, $bar['progress'] );
			$this->assertSame( array( 'free shipping', '10% off' ), wp_list_pluck( $bar['milestones'], 'label' ) );
			// Free shipping at 166.67, the 150 it needs after the 10% off.
			$this->assertSame( array( 55.56, 100.0 ), wp_list_pluck( $bar['milestones'], 'position' ) );
			$this->assertSame( 'You got free shipping and 10% off.', $bar['message'] );
		}
	}

	/**
	 * The next goal style fills toward one goal at a time.
	 */
	public function test_bar_can_show_the_next_goal_only() {
		update_option(
			'wcbcf_settings',
			array(
				'goals_cart'          => '1',
				'goals_free_shipping' => '1',
				'goals_style'         => 'next',
			)
		);
		$this->zone( array( 'BR' => 'country' ), '200', 'yes' );
		$this->coupon( 'meta', 300 );
		$this->fill_cart( 2 );

		$bar = Extra_Checkout_Fields_For_Brazil_Goals::get_bar();

		$this->assertSame( 'next', $bar['style'] );
		$this->assertSame( 50.0, $bar['progress'] );
		$this->assertSame( array(), $bar['milestones'] );
		$this->assertStringContainsString( 'more to get free shipping.', $bar['message'] );
	}

	/**
	 * An empty cart, as on a product page, still counts toward free shipping.
	 */
	public function test_empty_cart_counts_toward_free_shipping() {
		$this->zone( array( 'BR' => 'country' ), '150' );

		$this->assertSame( array( 'free_shipping' => 150.0 ), $this->remaining() );
	}

	/**
	 * The Store API cart carries the bar for the Cart and Checkout blocks.
	 */
	public function test_store_api_cart_carries_the_bar() {
		$this->coupon( 'meta', 300 );
		$this->fill_cart( 2 );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/wc/store/v1/cart' ) );
		$bar      = $response->get_data()['extensions']->{'csbmw-goals'}['bar'];

		$this->assertStringContainsString( 'more to get 10% off.', $bar['message'] );

		update_option( 'wcbcf_settings', array() );
		$response = rest_do_request( new WP_REST_Request( 'GET', '/wc/store/v1/cart' ) );

		$this->assertNull( $response->get_data()['extensions']->{'csbmw-goals'}['bar'] );
	}

	/**
	 * A placed bar can choose its own style, and the store's colors travel
	 * with the bar.
	 */
	public function test_bar_takes_a_style_and_the_store_colors() {
		update_option(
			'wcbcf_settings',
			array(
				'goals_free_shipping' => '1',
				'goals_bar_color'     => '#123abc',
				'goals_track_color'   => 'red',
			)
		);
		$this->zone( array( 'BR' => 'country' ), '150' );
		$this->fill_cart( 2 );

		$this->assertSame( 'milestones', Extra_Checkout_Fields_For_Brazil_Goals::get_bar()['style'] );
		$this->assertSame( 'next', Extra_Checkout_Fields_For_Brazil_Goals::get_bar( null, 'next' )['style'] );
		$this->assertSame( 'milestones', Extra_Checkout_Fields_For_Brazil_Goals::get_bar( null, 'other' )['style'] );
		$this->assertSame(
			array(
				'bar'   => '#123abc',
				'track' => '',
			),
			Extra_Checkout_Fields_For_Brazil_Goals::get_bar()['colors']
		);
		$this->assertStringContainsString( 'style="--csbmw-goals-default-bar:#123abc;"', Extra_Checkout_Fields_For_Brazil_Goals::render( 'cart' ) );
	}

	/**
	 * The block and the shortcode print a holder with their own style and
	 * colors, kept while empty so a cart change can fill it.
	 */
	public function test_block_and_shortcode_print_the_bar() {
		update_option( 'wcbcf_settings', array( 'goals_free_shipping' => '1' ) );
		$this->zone( array( 'BR' => 'country' ), '150' );
		$this->fill_cart( 2 );

		$shortcode = do_shortcode( '[csbmw_spending_goals style="next" bar_color="#ff0000" track_color="nope"]' );

		$this->assertStringContainsString( 'class="csbmw-goals-holder is-placed" style="--csbmw-goals-bar:#ff0000;" data-goals-style="next"', $shortcode );
		$this->assertStringContainsString( 'csbmw-goals-style-next', $shortcode );
		$this->assertTrue( wp_script_is( Extra_Checkout_Fields_For_Brazil_Goals::HANDLE ) );

		$block = do_blocks( '<!-- wp:csbmw/spending-goals {"goalsStyle":"milestones","trackColor":"#00ff00"} /-->' );

		$this->assertStringContainsString( '--csbmw-goals-track:#00ff00', $block );
		$this->assertStringContainsString( 'data-goals-style="milestones"', $block );
		$this->assertStringContainsString( '50.00 more to get free shipping.', $block );

		WC()->cart->empty_cart();
		update_option( 'wcbcf_settings', array() );

		$this->assertMatchesRegularExpression( '/<div class="csbmw-goals-holder is-placed" data-goals-style="" aria-live="polite"><\/div>/', do_shortcode( '[csbmw_spending_goals]' ) );
	}

	/**
	 * The settings keep only hex colors.
	 */
	public function test_settings_keep_only_hex_colors() {
		$settings = new Extra_Checkout_Fields_For_Brazil_Settings();
		$output   = $settings->validate_options(
			array(
				'goals_bar_color'   => '#AbCdEf',
				'goals_track_color' => 'javascript:alert(1)',
			)
		);

		$this->assertSame( '#AbCdEf', $output['goals_bar_color'] );
		$this->assertSame( '', $output['goals_track_color'] );
	}

	/**
	 * The coupon screen saves the goal checkbox.
	 */
	public function test_coupon_screen_saves_the_goal() {
		$coupon = $this->coupon( 'meta', 300, false );
		$goals  = new Extra_Checkout_Fields_For_Brazil_Goals();

		$_POST['csbmw_spending_goal'] = 'yes';
		$goals->save_coupon_field( $coupon->get_id(), $coupon );
		$this->assertSame( 'yes', ( new WC_Coupon( $coupon->get_id() ) )->get_meta( Extra_Checkout_Fields_For_Brazil_Goals::COUPON_META ) );

		unset( $_POST['csbmw_spending_goal'] );
		$goals->save_coupon_field( $coupon->get_id(), $coupon );
		$this->assertSame( '', ( new WC_Coupon( $coupon->get_id() ) )->get_meta( Extra_Checkout_Fields_For_Brazil_Goals::COUPON_META ) );
	}
}
