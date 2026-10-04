<?php
/**
 * Spending goals: how much more the cart needs for free shipping or a coupon.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Goals
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;

/**
 * Extra_Checkout_Fields_For_Brazil_Goals class.
 */
class Extra_Checkout_Fields_For_Brazil_Goals {

	/**
	 * Script and style handle of the progress bar.
	 *
	 * @var string
	 */
	const HANDLE = 'woocommerce-extra-checkout-fields-for-brazil-goals';

	/**
	 * Coupon meta marking a coupon as a spending goal.
	 *
	 * @var string
	 */
	const COUPON_META = '_csbmw_spending_goal';

	/**
	 * Session key of the goal coupons the plugin applied.
	 *
	 * @var string
	 */
	const APPLIED = 'csbmw_goal_coupons';

	/**
	 * Session key of the goal coupons the customer removed.
	 *
	 * @var string
	 */
	const DECLINED = 'csbmw_goal_coupons_declined';

	/**
	 * WC AJAX endpoint printing the product page bar.
	 *
	 * @var string
	 */
	const ENDPOINT = 'csbmw_spending_goals';

	/**
	 * Store API namespace of the cart data.
	 *
	 * @var string
	 */
	const NAMESPACE = 'csbmw-goals';

	/**
	 * Where the bar can show, with the setting that turns it on.
	 *
	 * @var array
	 */
	const PLACES = array(
		'cart'     => 'goals_cart',
		'checkout' => 'goals_checkout',
		'product'  => 'goals_product',
	);

	/**
	 * Whether the plugin is changing the cart's coupons itself.
	 *
	 * @var bool
	 */
	protected $applying = false;

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'apply_reached_coupons' ), 20 );
		add_action( 'woocommerce_removed_coupon', array( $this, 'remember_declined_coupon' ) );
		add_action( 'woocommerce_add_to_cart', array( $this, 'forget_declined_coupons' ) );
		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'forget_declined_coupons_on_increase' ), 10, 3 );

		add_action( 'woocommerce_coupon_options_usage_restriction', array( $this, 'coupon_field' ), 10, 2 );
		add_action( 'woocommerce_coupon_options_save', array( $this, 'save_coupon_field' ), 10, 2 );

		add_action( 'woocommerce_before_cart_totals', array( $this, 'cart_bar' ) );
		add_action( 'woocommerce_review_order_before_order_total', array( $this, 'checkout_bar' ) );
		add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'product_bar' ) );
		add_action( 'wc_ajax_' . self::ENDPOINT, array( $this, 'ajax_bar' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		if ( did_action( 'woocommerce_blocks_loaded' ) ) {
			$this->register_cart_data();
		} else {
			add_action( 'woocommerce_blocks_loaded', array( $this, 'register_cart_data' ) );
		}
	}

	/**
	 * Plugin settings.
	 *
	 * @return array
	 */
	protected static function settings() {
		return (array) get_option( 'wcbcf_settings', array() );
	}

	/**
	 * Whether the bar shows in a place.
	 *
	 * @param string $place Key of PLACES.
	 *
	 * @return bool
	 */
	public static function shows_in( $place ) {
		$settings = self::settings();

		return isset( self::PLACES[ $place ] ) && ! empty( $settings[ self::PLACES[ $place ] ] );
	}

	/**
	 * Whether the bar shows anywhere, which also turns on applying coupons.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		foreach ( array_keys( self::PLACES ) as $place ) {
			if ( self::shows_in( $place ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * How the bar shows several goals.
	 *
	 * @return string milestones or next.
	 */
	public static function style() {
		$settings = self::settings();

		return isset( $settings['goals_style'] ) && 'next' === $settings['goals_style'] ? 'next' : 'milestones';
	}

	/**
	 * Price as plain text, as messages and screen readers take it.
	 *
	 * @param float $amount Amount.
	 *
	 * @return string
	 */
	protected static function price( $amount ) {
		return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Coupons marked as spending goals, with a minimum spend.
	 *
	 * @return WC_Coupon[]
	 */
	public static function goal_coupons() {
		if ( ! wc_coupons_enabled() ) {
			return array();
		}

		$ids = get_posts(
			array(
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'fields'         => 'ids',
				'meta_key'       => self::COUPON_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => 'yes', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		$coupons = array();

		foreach ( $ids as $id ) {
			$coupon = new WC_Coupon( $id );

			if ( $coupon->get_code() && 0 < (float) $coupon->get_minimum_amount() ) {
				$coupons[] = $coupon;
			}
		}

		return $coupons;
	}

	/**
	 * What a coupon gives, for the bar.
	 *
	 * @param WC_Coupon $coupon Coupon.
	 *
	 * @return string
	 */
	protected static function coupon_label( $coupon ) {
		$amount = (float) $coupon->get_amount();

		if ( 0 >= $amount && $coupon->get_free_shipping() ) {
			$label = __( 'free shipping', 'woocommerce-extra-checkout-fields-for-brazil' );
		} elseif ( $coupon->is_type( 'percent' ) ) {
			/* translators: %s: discount, such as 10% */
			$label = sprintf( __( '%s off', 'woocommerce-extra-checkout-fields-for-brazil' ), wc_format_localized_decimal( $amount ) . '%' );
		} elseif ( $coupon->is_type( 'fixed_product' ) ) {
			/* translators: %s: discount amount */
			$label = sprintf( __( '%s off each item', 'woocommerce-extra-checkout-fields-for-brazil' ), self::price( $amount ) );
		} else {
			/* translators: %s: discount amount */
			$label = sprintf( __( '%s off', 'woocommerce-extra-checkout-fields-for-brazil' ), self::price( $amount ) );
		}

		/**
		 * Filter what a coupon goal says the customer gets.
		 *
		 * @since 5.0.0
		 *
		 * @param string    $label  Label, such as "10% off".
		 * @param WC_Coupon $coupon Coupon.
		 */
		return apply_filters( 'csbmw_spending_goal_coupon_label', $label, $coupon );
	}

	/**
	 * Whether a coupon could apply to the cart once its minimum is reached.
	 *
	 * @param WC_Coupon $coupon Coupon.
	 * @param WC_Cart   $cart   Cart.
	 *
	 * @return bool
	 */
	protected static function is_eligible( $coupon, $cart ) {
		$code    = wc_format_coupon_code( $coupon->get_code() );
		$applied = array_filter(
			$cart->get_applied_coupons(),
			static fn( $other ) => ! wc_is_same_coupon( $other, $code )
		);

		// Applying it would remove the customer's coupons, or be refused.
		if ( $applied && $coupon->get_individual_use() ) {
			return false;
		}

		foreach ( $applied as $other ) {
			if ( ( new WC_Coupon( $other ) )->get_individual_use() ) {
				return false;
			}
		}

		$ignore_minimum = '__return_false';

		add_filter( 'woocommerce_coupon_validate_minimum_amount', $ignore_minimum );
		$valid = ( new WC_Discounts( $cart ) )->is_coupon_valid( $coupon );
		remove_filter( 'woocommerce_coupon_validate_minimum_amount', $ignore_minimum );

		return true === $valid;
	}

	/**
	 * Coupon goals for the cart.
	 *
	 * @param WC_Cart $cart Cart.
	 *
	 * @return array[]
	 */
	protected static function coupon_goals( $cart ) {
		$subtotal = (float) $cart->get_displayed_subtotal();
		$declined = WC()->session ? (array) WC()->session->get( self::DECLINED, array() ) : array();
		$goals    = array();

		foreach ( self::goal_coupons() as $coupon ) {
			$code = wc_format_coupon_code( $coupon->get_code() );

			// Removed by the customer, unless they applied it again.
			if ( ( in_array( $code, $declined, true ) && ! $cart->has_discount( $code ) ) || ! self::is_eligible( $coupon, $cart ) ) {
				continue;
			}

			$minimum = (float) $coupon->get_minimum_amount();

			$goals[] = array(
				'type'      => 'coupon',
				'label'     => self::coupon_label( $coupon ),
				'threshold' => $minimum,
				'remaining' => $minimum - $subtotal,
			);
		}

		return $goals;
	}

	/**
	 * What free shipping still needs in a zone.
	 *
	 * @param WC_Shipping_Zone $zone Zone.
	 * @param WC_Cart          $cart Cart.
	 *
	 * @return array|null Threshold and remaining amount, or null when the zone
	 *                    has no free shipping a spend can reach.
	 */
	protected static function zone_free_shipping( $zone, $cart ) {
		$has_coupon = false;

		foreach ( $cart->get_coupons() as $coupon ) {
			if ( $coupon->get_free_shipping() ) {
				$has_coupon = true;
				break;
			}
		}

		$best = null;

		foreach ( $zone->get_shipping_methods( true ) as $method ) {
			if ( ! $method instanceof WC_Shipping_Free_Shipping ) {
				continue;
			}

			$requires = (string) $method->requires;

			if ( '' === $requires || ( 'either' === $requires && $has_coupon ) ) {
				$goal = array(
					'threshold' => 0.0,
					'remaining' => 0.0,
				);
			} elseif ( 'min_amount' === $requires || 'either' === $requires || ( 'both' === $requires && $has_coupon ) ) {
				$total = (float) $cart->get_displayed_subtotal();

				if ( 'no' === $method->ignore_discounts ) {
					$total -= (float) $cart->get_discount_total();

					if ( $cart->display_prices_including_tax() ) {
						$total -= (float) $cart->get_discount_tax();
					}
				}

				$goal = array(
					'threshold' => (float) $method->min_amount,
					'remaining' => (float) $method->min_amount - round( $total, wc_get_price_decimals() ),
				);
			} else {
				continue;
			}

			if ( null === $best || $goal['remaining'] < $best['remaining'] ) {
				$best = $goal;
			}
		}

		return $best;
	}

	/**
	 * Zones a Brazilian address may fall in, in the order WooCommerce tries
	 * them, while the CEP that picks one is unknown.
	 *
	 * @return WC_Shipping_Zone[]
	 */
	protected static function brazilian_zones() {
		$zones = array();

		foreach ( WC_Shipping_Zones::get_zones() as $data ) {
			$zone      = new WC_Shipping_Zone( $data['id'] );
			$regions   = array();
			$postcodes = false;

			foreach ( $zone->get_zone_locations() as $location ) {
				if ( 'postcode' === $location->type ) {
					$postcodes = true;
				} else {
					$regions[] = $location;
				}
			}

			$whole = empty( $regions );
			$any   = $whole;

			foreach ( $regions as $location ) {
				if ( ( 'country' === $location->type && 'BR' === $location->code ) || ( 'continent' === $location->type && 'SA' === $location->code ) ) {
					$whole = true;
					$any   = true;
				} elseif ( 'state' === $location->type && 0 === strpos( $location->code, 'BR:' ) ) {
					$any = true;
				}
			}

			if ( ! $any ) {
				continue;
			}

			$zones[] = $zone;

			// Every Brazilian address left stops here.
			if ( $whole && ! $postcodes ) {
				return $zones;
			}
		}

		$zones[] = new WC_Shipping_Zone( 0 );

		return $zones;
	}

	/**
	 * Free shipping goal for the cart's destination.
	 *
	 * Before a Brazilian address has a CEP, the zone is unknown, so the goal
	 * shows only when every zone the address may fall in has the same one.
	 *
	 * @param WC_Cart $cart Cart.
	 *
	 * @return array|null
	 */
	protected static function free_shipping_goal( $cart ) {
		$customer = WC()->customer;

		// An empty cart, as on a product page, is yet to hold what ships.
		if ( ! $customer instanceof WC_Customer || ( ! $cart->is_empty() && ! $cart->needs_shipping() ) ) {
			return null;
		}

		$country  = $customer->get_shipping_country();
		$postcode = Extra_Checkout_Fields_For_Brazil_Postcodes::sanitize( $customer->get_shipping_postcode() );

		if ( '' === $country ) {
			return null;
		}

		if ( 'BR' === $country && 8 !== strlen( $postcode ) ) {
			$found = false;
			$goal  = null;

			foreach ( self::brazilian_zones() as $zone ) {
				$zone_goal = self::zone_free_shipping( $zone, $cart );

				if ( $found && $zone_goal !== $goal ) {
					return null;
				}

				$found = true;
				$goal  = $zone_goal;
			}
		} else {
			$state = $customer->get_shipping_state();

			if ( 'BR' === $country && '' === $state ) {
				$state = Extra_Checkout_Fields_For_Brazil_Postcodes::get_state( $postcode );
			}

			$zone = WC_Shipping_Zones::get_zone_matching_package(
				array(
					'destination' => array(
						'country'  => $country,
						'state'    => $state,
						'postcode' => $customer->get_shipping_postcode(),
					),
				)
			);
			$goal = self::zone_free_shipping( $zone, $cart );
		}

		// Free whatever the cart holds, which leaves nothing to reach.
		if ( null === $goal || 0 >= $goal['threshold'] ) {
			return null;
		}

		return array(
			'type'      => 'free_shipping',
			'label'     => __( 'free shipping', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'threshold' => $goal['threshold'],
			'remaining' => $goal['remaining'],
		);
	}

	/**
	 * Goals for the cart, nearest first.
	 *
	 * @param WC_Cart|null $cart Cart.
	 *
	 * @return array[] Type, label, threshold and remaining amount.
	 */
	public static function get_goals( $cart = null ) {
		$cart = $cart ? $cart : WC()->cart;

		if ( ! $cart instanceof WC_Cart ) {
			return array();
		}

		$goals = self::coupon_goals( $cart );

		if ( ! empty( self::settings()['goals_free_shipping'] ) ) {
			$free = self::free_shipping_goal( $cart );

			if ( null !== $free ) {
				$goals[] = $free;
			}
		}

		usort( $goals, static fn( $a, $b ) => $a['remaining'] <=> $b['remaining'] );

		/**
		 * Filter the spending goals shown for a cart.
		 *
		 * @since 5.0.0
		 *
		 * @param array[] $goals Goals with type, label, threshold and remaining amount, nearest first.
		 * @param WC_Cart $cart  Cart.
		 */
		return apply_filters( 'csbmw_spending_goals', $goals, $cart );
	}

	/**
	 * What the bar shows for the cart.
	 *
	 * @param WC_Cart|null $cart Cart.
	 *
	 * @return array|null Style, message, progress and milestones, or null with
	 *                    no goal to show.
	 */
	public static function get_bar( $cart = null ) {
		$cart  = $cart ? $cart : WC()->cart;
		$goals = self::get_goals( $cart );

		if ( empty( $goals ) ) {
			return null;
		}

		$subtotal = (float) $cart->get_displayed_subtotal();
		$style    = self::style();
		$next     = null;
		$reached  = array();

		foreach ( $goals as $goal ) {
			if ( 0 >= $goal['remaining'] ) {
				$reached[] = $goal['label'];
			} elseif ( null === $next ) {
				$next = $goal;
			}
		}

		if ( null !== $next ) {
			$message = sprintf(
				/* translators: 1: amount still to spend, 2: what the customer gets, such as free shipping */
				__( 'Add %1$s more to get %2$s.', 'woocommerce-extra-checkout-fields-for-brazil' ),
				self::price( $next['remaining'] ),
				$next['label']
			);
		} else {
			/* translators: %s: what the customer got, such as free shipping and 10% off */
			$message = sprintf( __( 'You got %s.', 'woocommerce-extra-checkout-fields-for-brazil' ), wp_sprintf( '%l', $reached ) );
		}

		// Where each goal falls on a bar measured in the cart subtotal.
		$positions = array_map( static fn( $goal ) => max( 0, $subtotal + $goal['remaining'] ), $goals );

		if ( 'next' === $style ) {
			$end      = null === $next ? $subtotal : $subtotal + $next['remaining'];
			$progress = $end > 0 ? min( 100, $subtotal / $end * 100 ) : 100;
		} else {
			$end      = max( $subtotal, max( $positions ) );
			$progress = $end > 0 ? $subtotal / $end * 100 : 100;
		}

		$milestones = array();

		if ( 'milestones' === $style ) {
			foreach ( $goals as $index => $goal ) {
				$milestones[] = array(
					'label'    => $goal['label'],
					'amount'   => self::price( $goal['threshold'] ),
					'position' => round( $end > 0 ? $positions[ $index ] / $end * 100 : 100, 2 ),
					'reached'  => 0 >= $goal['remaining'],
				);
			}
		}

		return array(
			'style'      => $style,
			'message'    => $message,
			'progress'   => round( $progress, 2 ),
			'milestones' => $milestones,
		);
	}

	/**
	 * Apply each goal coupon the cart reaches, and take back the ones it no
	 * longer reaches. A coupon the customer removed stays removed until the
	 * cart grows or drops below its minimum.
	 *
	 * @param WC_Cart $cart Cart.
	 *
	 * @return void
	 */
	public function apply_reached_coupons( $cart ) {
		$session = WC()->session;

		if ( $this->applying || ! $session || ! self::is_enabled() ) {
			return;
		}

		$applied  = (array) $session->get( self::APPLIED, array() );
		$declined = (array) $session->get( self::DECLINED, array() );
		$subtotal = (float) $cart->get_displayed_subtotal();
		$changed  = false;

		$this->applying = true;

		foreach ( self::goal_coupons() as $coupon ) {
			$code    = wc_format_coupon_code( $coupon->get_code() );
			$reached = $subtotal >= (float) $coupon->get_minimum_amount();

			if ( $cart->has_discount( $code ) ) {
				if ( ! $reached && in_array( $code, $applied, true ) ) {
					$cart->remove_coupon( $code );
					$applied = array_values( array_diff( $applied, array( $code ) ) );
					$changed = true;
				}

				continue;
			}

			if ( ! $reached ) {
				$declined = array_values( array_diff( $declined, array( $code ) ) );
				continue;
			}

			if ( in_array( $code, $declined, true ) || ! self::is_eligible( $coupon, $cart ) ) {
				continue;
			}

			// The bar tells the customer, rather than a notice.
			add_filter( 'woocommerce_coupon_message', '__return_empty_string' );

			if ( $cart->apply_coupon( $code ) ) {
				$applied[] = $code;
				$changed   = true;
			}

			remove_filter( 'woocommerce_coupon_message', '__return_empty_string' );
		}

		$session->set( self::APPLIED, $applied );
		$session->set( self::DECLINED, $declined );

		if ( $changed ) {
			$cart->calculate_totals();
		}

		$this->applying = false;
	}

	/**
	 * Keep a goal coupon the customer removed from coming back.
	 *
	 * WooCommerce also removes a coupon the cart no longer qualifies for,
	 * which is not the customer's choice.
	 *
	 * @param string $code Coupon code.
	 *
	 * @return void
	 */
	public function remember_declined_coupon( $code ) {
		$session = WC()->session;
		$cart    = WC()->cart;

		if ( $this->applying || ! $session || ! $cart ) {
			return;
		}

		$code = wc_format_coupon_code( $code );

		foreach ( self::goal_coupons() as $coupon ) {
			if ( ! wc_is_same_coupon( $coupon->get_code(), $code ) || (float) $cart->get_displayed_subtotal() < (float) $coupon->get_minimum_amount() ) {
				continue;
			}

			$declined   = (array) $session->get( self::DECLINED, array() );
			$declined[] = $code;
			$session->set( self::DECLINED, array_values( array_unique( $declined ) ) );
			$session->set( self::APPLIED, array_values( array_diff( (array) $session->get( self::APPLIED, array() ), array( $code ) ) ) );

			return;
		}
	}

	/**
	 * Offer the removed goal coupons again once the cart grows.
	 *
	 * @return void
	 */
	public function forget_declined_coupons() {
		if ( WC()->session ) {
			WC()->session->set( self::DECLINED, array() );
		}
	}

	/**
	 * Offer the removed goal coupons again when a quantity goes up.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $quantity      New quantity.
	 * @param int    $old_quantity  Previous quantity.
	 *
	 * @return void
	 */
	public function forget_declined_coupons_on_increase( $cart_item_key, $quantity, $old_quantity ) {
		if ( $quantity > $old_quantity ) {
			$this->forget_declined_coupons();
		}
	}

	/**
	 * Spending goal checkbox in the coupon's usage restrictions.
	 *
	 * @param int       $coupon_id Coupon ID.
	 * @param WC_Coupon $coupon    Coupon.
	 *
	 * @return void
	 */
	public function coupon_field( $coupon_id, $coupon ) {
		echo '<div class="options_group">';

		woocommerce_wp_checkbox(
			array(
				'id'          => 'csbmw_spending_goal',
				'label'       => __( 'Spending goal', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => __( 'Show how much more the cart needs to reach the minimum spend, and apply the coupon once it does.', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'value'       => wc_bool_to_string( 'yes' === $coupon->get_meta( self::COUPON_META ) ),
			)
		);

		echo '</div>';
	}

	/**
	 * Save the spending goal checkbox.
	 *
	 * @param int       $coupon_id Coupon ID.
	 * @param WC_Coupon $coupon    Coupon.
	 *
	 * @return void
	 */
	public function save_coupon_field( $coupon_id, $coupon ) {
		// WooCommerce checked the nonce before this action.
		if ( isset( $_POST['csbmw_spending_goal'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$coupon->update_meta_data( self::COUPON_META, 'yes' );
		} else {
			$coupon->delete_meta_data( self::COUPON_META );
		}

		$coupon->save();
	}

	/**
	 * Markup of the bar.
	 *
	 * @param string $place Key of PLACES.
	 *
	 * @return string Empty with no goal to show.
	 */
	public static function render( $place ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- The view prints it.
		$bar = self::get_bar();

		if ( null === $bar ) {
			return '';
		}

		ob_start();
		include __DIR__ . '/views/html-spending-goals.php';

		return (string) ob_get_clean();
	}

	/**
	 * Bar above the classic cart totals.
	 *
	 * @return void
	 */
	public function cart_bar() {
		if ( self::shows_in( 'cart' ) ) {
			echo self::render( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Bar above the classic checkout order total, which refreshes with it.
	 *
	 * @return void
	 */
	public function checkout_bar() {
		$html = self::shows_in( 'checkout' ) ? self::render( 'checkout' ) : '';

		if ( '' !== $html ) {
			echo '<tr class="csbmw-goals-row"><td colspan="2">' . $html . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Bar above the add to cart form, always printed so a product added
	 * without reloading the page can fill it.
	 *
	 * @return void
	 */
	public function product_bar() {
		if ( self::shows_in( 'product' ) ) {
			echo '<div class="csbmw-goals-product">' . self::render( 'product' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * WC AJAX endpoint printing the product page bar for the cart now.
	 *
	 * @return void
	 */
	public function ajax_bar() {
		wp_send_json_success( array( 'html' => self::shows_in( 'product' ) ? self::render( 'product' ) : '' ) );
	}

	/**
	 * Styles for the classic bar, and the scripts that refresh or draw it.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$product = self::shows_in( 'product' ) && is_product();
		$blocks  = false;
		$classic = false;

		foreach ( array( 'cart', 'checkout' ) as $place ) {
			$page = 'cart' === $place ? is_cart() : is_checkout();

			if ( ! self::shows_in( $place ) || ! ( $page || has_block( 'woocommerce/' . $place ) ) ) {
				continue;
			}

			// Block themes draw the page from a template rather than the content.
			if ( has_block( 'woocommerce/' . $place ) || wp_is_block_theme() ) {
				$blocks = true;
			} else {
				$classic = true;
			}
		}

		if ( ! $product && ! $blocks && ! $classic ) {
			return;
		}

		Extra_Checkout_Fields_For_Brazil_Assets::register_style( self::HANDLE, 'goals' );
		wp_enqueue_style( self::HANDLE );

		if ( $product ) {
			Extra_Checkout_Fields_For_Brazil_Assets::register_script( self::HANDLE, 'goals' );
			wp_localize_script(
				self::HANDLE,
				'bmwGoalsParams',
				array(
					'url' => WC_AJAX::get_endpoint( self::ENDPOINT ),
				)
			);
			wp_enqueue_script( self::HANDLE );
		}

		if ( $blocks ) {
			Extra_Checkout_Fields_For_Brazil_Assets::register_script( self::HANDLE . '-blocks', 'goals-blocks' );
			Extra_Checkout_Fields_For_Brazil_Assets::set_translations( self::HANDLE . '-blocks' );
			wp_localize_script(
				self::HANDLE . '-blocks',
				'bmwGoalsBlocksParams',
				array(
					'places' => array_values( array_filter( array( 'cart', 'checkout' ), array( __CLASS__, 'shows_in' ) ) ),
				)
			);
			wp_enqueue_script( self::HANDLE . '-blocks' );
		}
	}

	/**
	 * Add the bar to the Store API cart, which the Cart and Checkout blocks
	 * read after every change.
	 *
	 * @return void
	 */
	public function register_cart_data() {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => self::NAMESPACE,
				'data_callback'   => array( $this, 'cart_data' ),
				'schema_callback' => array( $this, 'cart_schema' ),
				'schema_type'     => ARRAY_A,
			)
		);
	}

	/**
	 * Store API cart data.
	 *
	 * @return array
	 */
	public function cart_data() {
		return array(
			'bar' => self::is_enabled() ? self::get_bar() : null,
		);
	}

	/**
	 * Store API cart schema.
	 *
	 * @return array
	 */
	public function cart_schema() {
		return array(
			'bar' => array(
				'description' => __( 'Spending goals progress bar, or null with no goal to show.', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'type'        => array( 'object', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}
}

new Extra_Checkout_Fields_For_Brazil_Goals();
