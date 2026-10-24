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
	 * Shortcode printing the bar.
	 *
	 * @var string
	 */
	const SHORTCODE = 'csbmw_spending_goals';

	/**
	 * Ways the bar shows several goals.
	 *
	 * @var string[]
	 */
	const STYLES = array( 'milestones', 'next' );

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
	 * Whether WooCommerce is taking coupons off for an individual use one.
	 *
	 * @var bool
	 */
	protected $making_room = false;

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'apply_reached_coupons' ), 20 );
		add_action( 'woocommerce_removed_coupon', array( $this, 'remember_declined_coupon' ) );
		add_filter( 'woocommerce_apply_individual_use_coupon', array( $this, 'start_making_room' ) );
		add_action( 'woocommerce_applied_coupon', array( $this, 'stop_making_room' ) );
		add_action( 'woocommerce_add_to_cart', array( $this, 'forget_declined_coupons' ) );
		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'forget_declined_coupons_on_increase' ), 10, 3 );

		add_action( 'init', array( $this, 'register_block' ) );
		add_shortcode( self::SHORTCODE, array( $this, 'shortcode' ) );

		add_action( 'woocommerce_coupon_options', array( $this, 'coupon_field' ), 10, 2 );
		add_action( 'woocommerce_coupon_options_save', array( $this, 'save_coupon_field' ), 10, 2 );

		add_action( 'woocommerce_before_cart_totals', array( $this, 'cart_bar' ) );
		add_filter( 'render_block_woocommerce/cart-order-summary-heading-block', array( $this, 'cart_summary_bar' ) );
		add_action( 'woocommerce_blocks_cart_block_registration', array( $this, 'register_integration' ) );
		add_action( 'woocommerce_blocks_checkout_block_registration', array( $this, 'register_integration' ) );
		add_filter( 'render_block_woocommerce/checkout-order-summary-cart-items-block', array( $this, 'checkout_summary_bar' ) );
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
	 * Whether the bar shows anywhere on its own, without a block or shortcode.
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
	 * @param string $choice A block's or shortcode's own choice, empty to
	 *                       follow the setting.
	 *
	 * @return string milestones or next.
	 */
	public static function style( $choice = '' ) {
		if ( in_array( $choice, self::STYLES, true ) ) {
			return $choice;
		}

		$settings = self::settings();

		return isset( $settings['goals_style'] ) && 'next' === $settings['goals_style'] ? 'next' : 'milestones';
	}

	/**
	 * Bar and track colors from the settings, empty to follow the theme.
	 *
	 * @return array
	 */
	public static function colors() {
		$settings = self::settings();

		return array(
			'bar'   => isset( $settings['goals_bar_color'] ) ? (string) sanitize_hex_color( $settings['goals_bar_color'] ) : '',
			'track' => isset( $settings['goals_track_color'] ) ? (string) sanitize_hex_color( $settings['goals_track_color'] ) : '',
		);
	}

	/**
	 * Custom properties giving a placed bar its own colors.
	 *
	 * @param string $bar   Bar color.
	 * @param string $track Track color.
	 *
	 * @return string
	 */
	protected static function color_vars( $bar, $track ) {
		$vars = '';

		foreach ( array(
			'--csbmw-goals-bar'   => $bar,
			'--csbmw-goals-track' => $track,
		) as $name => $color ) {
			$color = sanitize_hex_color( (string) $color );

			if ( $color ) {
				$vars .= $name . ':' . $color . ';';
			}
		}

		return $vars;
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

		if ( $coupon->is_type( 'percent' ) ) {
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
	 * Whether a goal coupon is on offer: eligible, and not removed by the
	 * customer unless they applied it again.
	 *
	 * @param WC_Coupon $coupon Coupon.
	 * @param WC_Cart   $cart   Cart.
	 *
	 * @return bool
	 */
	protected static function is_offered( $coupon, $cart ) {
		$code     = wc_format_coupon_code( $coupon->get_code() );
		$declined = WC()->session ? (array) WC()->session->get( self::DECLINED, array() ) : array();

		return ( ! in_array( $code, $declined, true ) || $cart->has_discount( $code ) ) && self::is_eligible( $coupon, $cart );
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
		$goals    = array();

		foreach ( self::goal_coupons() as $coupon ) {
			// Free shipping alone depends on the zone, which the free shipping
			// goal weighs.
			if ( ( 0 >= (float) $coupon->get_amount() && $coupon->get_free_shipping() ) || ! self::is_offered( $coupon, $cart ) ) {
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
	 * Subtotal from which the cart holds a coupon that gives free shipping.
	 *
	 * @param WC_Cart $cart Cart.
	 *
	 * @return float|null Null when no coupon will.
	 */
	protected static function free_shipping_coupon_at( $cart ) {
		$at    = null;
		$codes = array();

		foreach ( self::goal_coupons() as $coupon ) {
			$codes[] = wc_format_coupon_code( $coupon->get_code() );

			if ( $coupon->get_free_shipping() && self::is_offered( $coupon, $cart ) ) {
				$minimum = (float) $coupon->get_minimum_amount();
				$at      = null === $at ? $minimum : min( $at, $minimum );
			}
		}

		foreach ( $cart->get_coupons() as $code => $coupon ) {
			if ( $coupon->get_free_shipping() && $coupon->is_valid() && ! in_array( wc_format_coupon_code( $code ), $codes, true ) ) {
				return 0.0;
			}
		}

		return $at;
	}

	/**
	 * Subtotal from which a free shipping method's minimum is met, as
	 * WooCommerce measures it. Percentage discounts take their share of what
	 * the cart adds, so it takes more than the minimum still missing, and so
	 * do the goal coupons the cart reaches on the way.
	 *
	 * @param WC_Shipping_Free_Shipping $method Free shipping method.
	 * @param WC_Cart                   $cart   Cart.
	 *
	 * @return float|null Null when the discounts keep it out of reach.
	 */
	protected static function amount_at( $method, $cart ) {
		$subtotal = (float) $cart->get_displayed_subtotal();
		$minimum  = (float) $method->min_amount;
		$discount = 0.0;
		$rate     = 0.0;

		if ( 'no' !== $method->ignore_discounts ) {
			return self::reach( $minimum, $subtotal, $discount, $rate );
		}

		$with_tax = $cart->display_prices_including_tax();
		$discount = (float) $cart->get_discount_total() + ( $with_tax ? (float) $cart->get_discount_tax() : 0.0 );

		// What a coupon limited to some items takes from what the customer
		// adds is unknown, so only coupons on the whole cart grow with it.
		foreach ( $cart->get_coupons() as $code => $coupon ) {
			if ( $coupon->is_type( 'percent' ) && self::covers_whole_cart( $coupon ) && 0 < $subtotal ) {
				$rate += (float) $cart->get_coupon_discount_amount( $code, ! $with_tax ) / $subtotal;
			}
		}

		$at      = self::reach( $minimum, $subtotal, $discount, $rate );
		$pending = array_filter(
			self::goal_coupons(),
			static fn( $coupon ) => $coupon->is_type( array( 'percent', 'fixed_cart' ) )
				&& self::covers_whole_cart( $coupon )
				&& $subtotal < (float) $coupon->get_minimum_amount()
				&& ! $cart->has_discount( wc_format_coupon_code( $coupon->get_code() ) )
				&& self::is_offered( $coupon, $cart )
		);

		usort( $pending, static fn( $a, $b ) => (float) $a->get_minimum_amount() <=> (float) $b->get_minimum_amount() );

		// Each one applied pushes the minimum further, which may reach the next.
		foreach ( $pending as $coupon ) {
			if ( null === $at || (float) $coupon->get_minimum_amount() > $at ) {
				break;
			}

			if ( $coupon->is_type( 'percent' ) ) {
				$share     = min( 1.0, (float) $coupon->get_amount() / 100 );
				$rate     += $share;
				$discount += $share * $subtotal;
			} else {
				$discount += (float) $coupon->get_amount();
			}

			$at = self::reach( $minimum, $subtotal, $discount, $rate );
		}

		return $at;
	}

	/**
	 * Whether a coupon discounts every item, whatever the customer adds.
	 *
	 * @param WC_Coupon $coupon Coupon.
	 *
	 * @return bool
	 */
	protected static function covers_whole_cart( $coupon ) {
		return ! $coupon->get_product_ids()
			&& ! $coupon->get_excluded_product_ids()
			&& ! $coupon->get_product_categories()
			&& ! $coupon->get_excluded_product_categories()
			&& ! $coupon->get_exclude_sale_items()
			&& ! $coupon->get_limit_usage_to_x_items()
			&& ! $coupon->get_meta( 'product_brands' )
			&& ! $coupon->get_meta( 'exclude_product_brands' );
	}

	/**
	 * Subtotal at which a minimum is met, from the discounts the cart takes
	 * now and the share of what it adds that percentage discounts take.
	 *
	 * @param float $minimum  Minimum.
	 * @param float $subtotal Cart subtotal.
	 * @param float $discount Discount on the subtotal.
	 * @param float $rate     Share of each amount added that goes in discounts.
	 *
	 * @return float|null Null when the discounts keep it out of reach.
	 */
	protected static function reach( $minimum, $subtotal, $discount, $rate ) {
		$decimals = wc_get_price_decimals();
		$missing  = $minimum - round( $subtotal - $discount, $decimals );

		if ( 1 <= $rate ) {
			return 0 >= $missing ? 0.0 : null;
		}

		// Rounded up to the cent, as less would fall short.
		$factor = 10 ** $decimals;

		return round( $subtotal + ceil( round( $missing / ( 1 - $rate ) * $factor, 6 ) ) / $factor, $decimals );
	}

	/**
	 * Subtotal from which a zone ships for free.
	 *
	 * @param WC_Shipping_Zone $zone      Zone.
	 * @param WC_Cart          $cart      Cart.
	 * @param float|null       $coupon_at Subtotal from which the cart holds a
	 *                                    free shipping coupon, or null.
	 * @param bool             $by_amount Whether the zone's minimum counts as
	 *                                    a way there, or only as a condition
	 *                                    the coupon also needs.
	 *
	 * @return float|null Null when the zone has no free shipping to reach.
	 */
	protected static function zone_free_shipping( $zone, $cart, $coupon_at, $by_amount ) {
		$best = null;

		foreach ( $zone->get_shipping_methods( true ) as $method ) {
			if ( ! $method instanceof WC_Shipping_Free_Shipping ) {
				continue;
			}

			$requires  = (string) $method->requires;
			$amount_at = in_array( $requires, array( 'min_amount', 'either', 'both' ), true ) ? self::amount_at( $method, $cart ) : null;
			$way       = $by_amount ? $amount_at : null;

			if ( '' === $requires ) {
				$at = 0.0;
			} elseif ( 'coupon' === $requires ) {
				$at = $coupon_at;
			} elseif ( 'min_amount' === $requires ) {
				$at = $way;
			} elseif ( 'either' === $requires ) {
				$ways = array_filter( array( $coupon_at, $way ), static fn( $value ) => null !== $value );
				$at   = $ways ? min( $ways ) : null;
			} elseif ( 'both' === $requires && null !== $coupon_at && null !== $amount_at ) {
				$at = max( $coupon_at, $amount_at );
			} else {
				$at = null;
			}

			if ( null !== $at && ( null === $best || $at < $best ) ) {
				$best = $at;
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
	 * Whether the customer chose to pick the order up, which leaves no
	 * shipping to make free.
	 *
	 * @return bool
	 */
	protected static function picks_up() {
		$methods = WC()->shipping()->get_shipping_methods();
		/** This filter is documented in woocommerce/includes/class-wc-customer.php */
		$pickups = (array) apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) );

		foreach ( wc_get_chosen_shipping_method_ids() as $id ) {
			if ( in_array( $id, $pickups, true ) || ( isset( $methods[ $id ] ) && $methods[ $id ]->supports( 'local-pickup' ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Free shipping goal for the cart's destination.
	 *
	 * Before a Brazilian address has a CEP, the zone is unknown, so the goal
	 * shows only when every zone the address may fall in has the same one.
	 *
	 * @param WC_Cart $cart      Cart.
	 * @param bool    $by_amount Whether the zones' minimums count, or only
	 *                           free shipping coupons.
	 *
	 * @return array|null
	 */
	protected static function free_shipping_goal( $cart, $by_amount ) {
		$customer = WC()->customer;

		// An empty cart, as on a product page, is yet to hold what ships.
		if ( ! $customer instanceof WC_Customer || ( ! $cart->is_empty() && ! $cart->needs_shipping() ) || self::picks_up() ) {
			return null;
		}

		$coupon_at = self::free_shipping_coupon_at( $cart );

		if ( ! $by_amount && null === $coupon_at ) {
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
				$zone_goal = self::zone_free_shipping( $zone, $cart, $coupon_at, $by_amount );

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
			$goal = self::zone_free_shipping( $zone, $cart, $coupon_at, $by_amount );
		}

		// Free whatever the cart holds, which leaves nothing to reach.
		if ( null === $goal || 0 >= $goal ) {
			return null;
		}

		return array(
			'type'      => 'free_shipping',
			'label'     => __( 'free shipping', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'threshold' => $goal,
			'remaining' => round( $goal - (float) $cart->get_displayed_subtotal(), wc_get_price_decimals() ),
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
		$free  = self::free_shipping_goal( $cart, ! empty( self::settings()['goals_free_shipping'] ) );

		if ( null !== $free ) {
			$goals[] = $free;
		}

		usort( $goals, static fn( $a, $b ) => $a['remaining'] <=> $b['remaining'] );

		// Coupons giving the same reward, such as two for 10% off, show once,
		// by the nearest.
		$rewards = array();

		foreach ( $goals as $index => $goal ) {
			if ( isset( $rewards[ $goal['label'] ] ) ) {
				unset( $goals[ $index ] );
			}

			$rewards[ $goal['label'] ] = true;
		}

		$goals = array_values( $goals );

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
	 * @param WC_Cart|null $cart  Cart.
	 * @param string       $style A block's or shortcode's own style, empty to
	 *                            follow the setting.
	 *
	 * @return array|null Style, message and its parts, progress, milestones
	 *                    and colors, or null with no goal to show.
	 */
	public static function get_bar( $cart = null, $style = '' ) {
		$cart  = $cart ? $cart : WC()->cart;
		$goals = self::get_goals( $cart );

		if ( empty( $goals ) ) {
			return null;
		}

		return self::build_bar( $goals, (float) $cart->get_displayed_subtotal(), self::style( $style ) );
	}

	/**
	 * A piece of the message.
	 *
	 * @param string $text   Text.
	 * @param bool   $strong Whether it is a reward, shown in bold.
	 *
	 * @return array
	 */
	protected static function part( $text, $strong = false ) {
		return array(
			'text'   => $text,
			'strong' => $strong,
		);
	}

	/**
	 * Fill a translated message with parts, keeping the translation's word
	 * order.
	 *
	 * @param string  $format Message with %s or %1$s placeholders.
	 * @param array[] $values Parts for each placeholder, in order.
	 *
	 * @return array[]
	 */
	protected static function fill( $format, $values ) {
		$parts = array();
		$next  = 0;

		foreach ( (array) preg_split( '/(%(?:\d+\$)?s)/', $format, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY ) as $piece ) {
			if ( preg_match( '/^%(?:(\d+)\$)?s$/', $piece, $match ) ) {
				$index = empty( $match[1] ) ? $next : (int) $match[1] - 1;
				$parts = array_merge( $parts, $values[ $index ] ?? array() );
				++$next;
			} else {
				$parts[] = self::part( $piece );
			}
		}

		return $parts;
	}

	/**
	 * Rewards as a list, such as "free shipping and 10% off", each in bold,
	 * joined as WordPress joins lists in the site's language.
	 *
	 * @param string[] $rewards Rewards.
	 *
	 * @return array[]
	 */
	protected static function rewards_list( $rewards ) {
		/** This filter is documented in wp-includes/formatting.php */
		$separators = apply_filters(
			'wp_sprintf_l',
			array(
				/* translators: Used to join items in a list with more than 2 items. */
				'between'          => sprintf( __( '%1$s, %2$s' ), '', '' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				/* translators: Used to join last two items in a list with more than 2 times. */
				'between_last_two' => sprintf( __( '%1$s, and %2$s' ), '', '' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				/* translators: Used to join items in a list with only 2 items. */
				'between_only_two' => sprintf( __( '%1$s and %2$s' ), '', '' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			)
		);
		$count      = count( $rewards );
		$parts      = array();

		foreach ( array_values( $rewards ) as $index => $reward ) {
			if ( $index > 0 ) {
				if ( 2 === $count ) {
					$parts[] = self::part( $separators['between_only_two'] );
				} else {
					$parts[] = self::part( $index === $count - 1 ? $separators['between_last_two'] : $separators['between'] );
				}
			}

			$parts[] = self::part( $reward, true );
		}

		return $parts;
	}

	/**
	 * What the bar shows for some goals.
	 *
	 * @param array[] $goals    Goals, nearest first.
	 * @param float   $subtotal Cart subtotal.
	 * @param string  $style    milestones or next.
	 *
	 * @return array
	 */
	protected static function build_bar( $goals, $subtotal, $style ) {
		// Where each goal falls on a bar measured in the cart subtotal. A goal
		// still ahead sits where the cart will reach it. One reached stays at
		// its own amount, so the bar holds still as the cart grows past it.
		foreach ( $goals as $index => $goal ) {
			$goals[ $index ]['position'] = 0 >= $goal['remaining'] ? $goal['threshold'] : $subtotal + $goal['remaining'];
		}

		usort( $goals, static fn( $a, $b ) => $a['position'] <=> $b['position'] );

		$next    = null;
		$reached = array();

		foreach ( $goals as $goal ) {
			if ( 0 >= $goal['remaining'] ) {
				$reached[] = $goal['label'];
			} elseif ( null === $next ) {
				$next = $goal;
			}
		}

		if ( null !== $next ) {
			$parts = self::fill(
				/* translators: 1: amount still to spend, 2: what the customer gets, such as free shipping */
				__( 'Add %1$s more to get %2$s.', 'woocommerce-extra-checkout-fields-for-brazil' ),
				array(
					array( self::part( self::price( $next['remaining'] ) ) ),
					array( self::part( $next['label'], true ) ),
				)
			);
		} else {
			$parts = self::fill(
				/* translators: %s: what the customer got, such as free shipping and 10% off */
				__( 'You got %s.', 'woocommerce-extra-checkout-fields-for-brazil' ),
				array( self::rewards_list( $reached ) )
			);
		}

		$message = implode( '', wp_list_pluck( $parts, 'text' ) );

		// The bar ends at the next goal, or at the last one, and stays full past it.
		$end      = 'next' === $style && null !== $next ? $next['position'] : max( wp_list_pluck( $goals, 'position' ) );
		$progress = $end > 0 ? min( 100, $subtotal / $end * 100 ) : 100;

		$milestones = array();

		if ( 'milestones' === $style ) {
			foreach ( $goals as $goal ) {
				$milestones[] = array(
					'label'    => $goal['label'],
					'amount'   => self::price( $goal['threshold'] ),
					'position' => round( $end > 0 ? min( 100, $goal['position'] / $end * 100 ) : 100, 2 ),
					'reached'  => 0 >= $goal['remaining'],
				);
			}
		}

		return array(
			'style'      => $style,
			'message'    => $message,
			'parts'      => $parts,
			'progress'   => round( $progress, 2 ),
			'milestones' => $milestones,
			'colors'     => self::colors(),
		);
	}

	/**
	 * Apply each goal coupon the cart reaches, and take back the ones it no
	 * longer reaches or qualifies for, such as past a maximum spend. A coupon
	 * the customer removed stays removed until the cart grows or drops below
	 * its minimum.
	 *
	 * @param WC_Cart $cart Cart.
	 *
	 * @return void
	 */
	public function apply_reached_coupons( $cart ) {
		$session = WC()->session;

		// While an individual use coupon is going on, the goal coupons it
		// replaces would come straight back.
		if ( $this->applying || $this->making_room || ! $session ) {
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
				if ( in_array( $code, $applied, true ) && ( ! $reached || ! self::is_eligible( $coupon, $cart ) ) ) {
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
				$applied = array_values( array_unique( array_merge( $applied, array( $code ) ) ) );
				$changed = true;
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
	 * WooCommerce also removes a coupon the cart no longer qualifies for, and
	 * the coupons an individual use one replaces. Neither is the customer's
	 * choice.
	 *
	 * @param string $code Coupon code.
	 *
	 * @return void
	 */
	public function remember_declined_coupon( $code ) {
		$session = WC()->session;
		$cart    = WC()->cart;

		if ( $this->applying || $this->making_room || ! $session || ! $cart ) {
			return;
		}

		$code = wc_format_coupon_code( $code );

		foreach ( self::goal_coupons() as $coupon ) {
			if ( ! wc_is_same_coupon( $coupon->get_code(), $code ) ) {
				continue;
			}

			$session->set( self::APPLIED, array_values( array_diff( (array) $session->get( self::APPLIED, array() ), array( $code ) ) ) );

			if ( (float) $cart->get_displayed_subtotal() < (float) $coupon->get_minimum_amount() || ! self::is_eligible( $coupon, $cart ) ) {
				return;
			}

			$declined   = (array) $session->get( self::DECLINED, array() );
			$declined[] = $code;
			$session->set( self::DECLINED, array_values( array_unique( $declined ) ) );

			return;
		}
	}

	/**
	 * Note that the coupons about to come off make room for an individual use
	 * one, which is not the customer removing them, nor a reason to apply them
	 * again.
	 *
	 * @param array $keep Coupons to keep.
	 *
	 * @return array
	 */
	public function start_making_room( $keep ) {
		$this->making_room = true;

		return $keep;
	}

	/**
	 * The individual use coupon is on.
	 *
	 * @return void
	 */
	public function stop_making_room() {
		$this->making_room = false;
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
	 * Spending goal checkbox in the coupon's general options.
	 *
	 * @param int       $coupon_id Coupon ID.
	 * @param WC_Coupon $coupon    Coupon.
	 *
	 * @return void
	 */
	public function coupon_field( $coupon_id, $coupon ) {
		woocommerce_wp_checkbox(
			array(
				'id'          => 'csbmw_spending_goal',
				'label'       => __( 'Spending goal', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'description' => __( 'Show how much more the cart needs to reach the minimum spend set in Usage restriction, and apply the coupon once it does.', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'value'       => wc_bool_to_string( 'yes' === $coupon->get_meta( self::COUPON_META ) ),
			)
		);
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
	 * @param string $place Key of PLACES, or block for a placed bar.
	 * @param string $style A placed bar's own style, empty to follow the
	 *                      setting.
	 *
	 * @return string Empty with no goal to show.
	 */
	public static function render( $place, $style = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed -- The view prints it.
		$bar = self::get_bar( null, $style );

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
	 * Where the Cart and Checkout blocks draw the bar in their order summary,
	 * which the blocks fill with the inner block of that name.
	 *
	 * @param string $place cart or checkout.
	 *
	 * @return string
	 */
	protected static function summary_bar( $place ) {
		return '<div data-block-name="csbmw/spending-goals-summary" data-place="' . esc_attr( $place ) . '" class="wp-block-csbmw-spending-goals-summary"></div>';
	}

	/**
	 * Bar right after the Cart block's Cart totals heading.
	 *
	 * @param string $content Heading markup.
	 *
	 * @return string
	 */
	public function cart_summary_bar( $content ) {
		return self::shows_in( 'cart' ) ? $content . self::summary_bar( 'cart' ) : $content;
	}

	/**
	 * Bar right after the Checkout block's Order summary title, ahead of the
	 * items.
	 *
	 * @param string $content Items markup.
	 *
	 * @return string
	 */
	public function checkout_summary_bar( $content ) {
		return self::shows_in( 'checkout' ) ? self::summary_bar( 'checkout' ) . $content : $content;
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
			echo '<div class="csbmw-goals-holder is-automatic" aria-live="polite">' . self::render( 'product' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * WC AJAX endpoint printing a product page or placed bar for the cart now.
	 *
	 * @return void
	 */
	public function ajax_bar() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read only, for the visitor's own cart.
		$style  = isset( $_GET['style'] ) ? sanitize_key( wp_unslash( $_GET['style'] ) ) : '';
		$placed = ! empty( $_GET['placed'] );
		// phpcs:enable

		wp_send_json_success( array( 'html' => $placed || self::shows_in( 'product' ) ? self::render( $placed ? 'block' : 'product', $style ) : '' ) );
	}

	/**
	 * Register the bar's style and refresh script, and the block.
	 *
	 * @return void
	 */
	public function register_block() {
		Extra_Checkout_Fields_For_Brazil_Assets::register_script( self::HANDLE, 'goals' );
		Extra_Checkout_Fields_For_Brazil_Assets::register_style( self::HANDLE, 'goals' );
		wp_localize_script(
			self::HANDLE,
			'bmwGoalsParams',
			array(
				'url' => WC_AJAX::get_endpoint( self::ENDPOINT ),
			)
		);

		$block = register_block_type(
			dirname( CSBMW_PLUGIN_FILE ) . '/build/blocks/spending-goals',
			array(
				'render_callback' => array( $this, 'render_block' ),
			)
		);

		if ( $block ) {
			foreach ( $block->editor_script_handles as $handle ) {
				Extra_Checkout_Fields_For_Brazil_Assets::set_translations( $handle );
				wp_add_inline_script( $handle, 'window.bmwGoalsEditor = ' . wp_json_encode( self::editor_preview() ) . ';', 'before' );
			}
		}
	}

	/**
	 * Sample bars the editor shows, as the store would draw them.
	 *
	 * @return array
	 */
	protected static function editor_preview() {
		$goals = array(
			array(
				'type'      => 'free_shipping',
				'label'     => __( 'free shipping', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'threshold' => 150.0,
				'remaining' => 50.0,
			),
			array(
				'type'      => 'coupon',
				/* translators: %s: discount, such as 10% */
				'label'     => sprintf( __( '%s off', 'woocommerce-extra-checkout-fields-for-brazil' ), '10%' ),
				'threshold' => 300.0,
				'remaining' => 200.0,
			),
		);
		$bars  = array();

		foreach ( self::STYLES as $style ) {
			$bars[ $style ] = self::build_bar( $goals, 100.0, $style );
		}

		return array(
			'style' => self::style(),
			'bars'  => $bars,
		);
	}

	/**
	 * A placed bar, kept in the page while empty so a cart change can fill it.
	 *
	 * @param string $wrapper Attributes of the wrapper element.
	 * @param string $style   Its own style, empty to follow the setting.
	 *
	 * @return string
	 */
	protected static function placed( $wrapper, $style ) {
		wp_enqueue_style( self::HANDLE );
		wp_enqueue_script( self::HANDLE );

		if ( self::has_cart_blocks() ) {
			Extra_Checkout_Fields_For_Brazil_Assets::register_script( self::HANDLE . '-sync', 'goals-sync' );
			wp_enqueue_script( self::HANDLE . '-sync' );
		}

		return '<div ' . $wrapper . ' data-goals-style="' . esc_attr( $style ) . '" aria-live="polite">' . self::render( 'block', $style ) . '</div>';
	}

	/**
	 * Whether the page has the Cart or Checkout block, which block themes
	 * draw from a template rather than the content.
	 *
	 * @return bool
	 */
	protected static function has_cart_blocks() {
		return has_block( 'woocommerce/cart' ) || has_block( 'woocommerce/checkout' ) || ( ( is_cart() || is_checkout() ) && wp_is_block_theme() );
	}

	/**
	 * Render the Spending Goals block.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string
	 */
	public function render_block( $attributes ) {
		$style = isset( $attributes['goalsStyle'] ) && in_array( $attributes['goalsStyle'], self::STYLES, true ) ? $attributes['goalsStyle'] : '';
		$vars  = self::color_vars( $attributes['barColor'] ?? '', $attributes['trackColor'] ?? '' );
		$extra = array( 'class' => 'csbmw-goals-holder is-placed' );

		if ( '' !== $vars ) {
			$extra['style'] = $vars;
		}

		return self::placed( get_block_wrapper_attributes( $extra ), $style );
	}

	/**
	 * Shortcode printing the bar.
	 *
	 * @param array|string $atts Shortcode attributes: style, bar_color and
	 *                           track_color.
	 *
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts  = shortcode_atts(
			array(
				'style'       => '',
				'bar_color'   => '',
				'track_color' => '',
			),
			$atts,
			self::SHORTCODE
		);
		$style = in_array( $atts['style'], self::STYLES, true ) ? $atts['style'] : '';
		$vars  = self::color_vars( $atts['bar_color'], $atts['track_color'] );

		return self::placed( 'class="csbmw-goals-holder is-placed"' . ( '' !== $vars ? ' style="' . esc_attr( $vars ) . '"' : '' ), $style );
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

		wp_enqueue_style( self::HANDLE );

		if ( $product ) {
			wp_enqueue_script( self::HANDLE );
		}
	}

	/**
	 * Load the order summary inner block with the Cart and Checkout blocks.
	 *
	 * @param Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry $registry Integration registry.
	 *
	 * @return void
	 */
	public function register_integration( $registry ) {
		include_once __DIR__ . '/class-extra-checkout-fields-for-brazil-goals-integration.php';

		if ( ! $registry->is_registered( 'csbmw-goals' ) ) {
			$registry->register( new Extra_Checkout_Fields_For_Brazil_Goals_Integration() );
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
