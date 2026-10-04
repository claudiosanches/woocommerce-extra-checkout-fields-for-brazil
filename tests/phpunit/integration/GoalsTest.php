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
	 */
	protected function zone( array $locations, $minimum, $ignore = 'no' ) {
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
					'requires'         => 'min_amount',
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
	 * Nothing is applied while the bar shows nowhere.
	 */
	public function test_goal_coupon_waits_for_the_bar() {
		update_option( 'wcbcf_settings', array() );
		$this->coupon( 'meta', 300 );

		$this->fill_cart( 6 );

		$this->assertFalse( WC()->cart->has_discount( 'meta' ) );
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
	 * Free shipping follows the zone's minimum and its discount rule.
	 */
	public function test_free_shipping_follows_the_zone() {
		$this->zone( array( 'BR' => 'country' ), '150' );
		$this->fill_cart( 2 );

		$this->assertSame( array( 'free_shipping' => 50.0 ), $this->remaining() );

		$this->coupon( 'meta', 100 );
		WC()->cart->calculate_totals();

		// 100 less the 10% the goal coupon took.
		$this->assertSame( 60.0, $this->remaining()['free_shipping'] );
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

		$this->fill_cart( 6 );
		$this->assertSame( 'You got free shipping and 10% off.', Extra_Checkout_Fields_For_Brazil_Goals::get_bar()['message'] );
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
