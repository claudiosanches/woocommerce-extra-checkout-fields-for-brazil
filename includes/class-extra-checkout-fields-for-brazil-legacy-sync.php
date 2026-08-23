<?php
/**
 * Keeps block checkout fields and the historic meta keys in sync.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extra_Checkout_Fields_For_Brazil_Legacy_Sync class.
 *
 * The checkout block stores additional fields under its own namespaced meta
 * (`_wc_billing/csbmw/number`, `_wc_other/csbmw/cpf`). Payment gateways, ERPs,
 * shipping plugins and this plugin's own admin and REST code all read the
 * historic keys (`_billing_number`, `_billing_cpf`). Both are kept populated so
 * an order behaves the same whichever checkout produced it.
 */
class Extra_Checkout_Fields_For_Brazil_Legacy_Sync {

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_set_additional_field_value', array( $this, 'write_legacy_meta' ), 10, 4 );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'write_block_meta' ), 20 );

		foreach ( $this->get_keys() as $key ) {
			add_filter(
				'woocommerce_get_default_value_for_' . Extra_Checkout_Fields_For_Brazil_Blocks::field_id( $key ),
				array( $this, 'read_legacy_meta' ),
				10,
				3
			);
		}

		// WooCommerce renders the registered fields on the order screen while
		// this plugin already renders the same values from the legacy meta.
		add_filter( 'woocommerce_admin_billing_fields', array( $this, 'remove_duplicated_admin_fields' ), 999 );
		add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'remove_duplicated_admin_fields' ), 999 );
	}

	/**
	 * All field keys handled by this plugin.
	 *
	 * @return array
	 */
	protected function get_keys() {
		return array_merge(
			Extra_Checkout_Fields_For_Brazil_Blocks::CONTACT_FIELDS,
			Extra_Checkout_Fields_For_Brazil_Blocks::ADDRESS_FIELDS
		);
	}

	/**
	 * Historic meta key for a field.
	 *
	 * Orders prefix their private meta with an underscore; customers do not.
	 * Contact fields live in the `other` group and have always been stored
	 * against billing.
	 *
	 * @param string  $key      Field key without the namespace.
	 * @param string  $group    Field group (billing|shipping|other).
	 * @param WC_Data $wc_object Object the meta belongs to.
	 *
	 * @return string
	 */
	public static function get_legacy_key( $key, $group, $wc_object ) {
		$group  = 'shipping' === $group ? 'shipping' : 'billing';
		$prefix = $wc_object instanceof WC_Order ? '_' : '';

		return $prefix . $group . '_' . $key;
	}

	/**
	 * Meta key the checkout block stores a field under.
	 *
	 * @param string $key   Field key without the namespace.
	 * @param string $group Field group (billing|shipping|other).
	 *
	 * @return string
	 */
	public static function get_block_key( $key, $group ) {
		$prefixes = array(
			'billing'  => '_wc_billing/',
			'shipping' => '_wc_shipping/',
			'other'    => '_wc_other/',
		);
		$prefix   = isset( $prefixes[ $group ] ) ? $prefixes[ $group ] : $prefixes['other'];

		return $prefix . Extra_Checkout_Fields_For_Brazil_Blocks::field_id( $key );
	}

	/**
	 * Convert a block value into what the classic checkout stores.
	 *
	 * Gender is the only field that differs: the block uses stable keys while
	 * the historic meta has always held the translated label.
	 *
	 * @param string $key   Field key without the namespace.
	 * @param mixed  $value Block value.
	 *
	 * @return mixed
	 */
	public static function to_legacy_value( $key, $value ) {
		if ( 'gender' !== $key ) {
			return $value;
		}

		$options = Extra_Checkout_Fields_For_Brazil_Blocks::get_gender_options();

		return isset( $options[ $value ] ) ? $options[ $value ] : $value;
	}

	/**
	 * Convert a historic value into the block representation.
	 *
	 * @param string $key   Field key without the namespace.
	 * @param mixed  $value Historic value.
	 *
	 * @return mixed
	 */
	public static function to_block_value( $key, $value ) {
		if ( 'gender' !== $key ) {
			return $value;
		}

		$options = Extra_Checkout_Fields_For_Brazil_Blocks::get_gender_options();

		if ( isset( $options[ $value ] ) ) {
			return $value;
		}

		// Match the label of the store's current language first, then the
		// untranslated source labels, which is what a store that has since
		// changed language will have stored.
		foreach ( array( $options, Extra_Checkout_Fields_For_Brazil_Blocks::GENDER_SOURCE_LABELS ) as $labels ) {
			$found = array_search( strtolower( (string) $value ), array_map( 'strtolower', $labels ), true );

			if ( false !== $found ) {
				return $found;
			}
		}

		return '';
	}

	/**
	 * Mirror a value saved by the checkout block into the historic meta key.
	 *
	 * @param string  $field_id  Namespaced field id.
	 * @param mixed   $value     Value being saved.
	 * @param string  $group     Field group (billing|shipping|other).
	 * @param WC_Data $wc_object Order or customer being saved.
	 *
	 * @return void
	 */
	public function write_legacy_meta( $field_id, $value, $group, $wc_object ) {
		$key = Extra_Checkout_Fields_For_Brazil_Blocks::field_key( $field_id );

		if ( '' === $key || ! $wc_object instanceof WC_Data ) {
			return;
		}

		$wc_object->update_meta_data( self::get_legacy_key( $key, $group, $wc_object ), self::to_legacy_value( $key, $value ) );
	}

	/**
	 * Copy the historic meta back into the block meta after an admin order save.
	 *
	 * Without this, editing an order in wp-admin leaves the block checkout, the
	 * Store API and the order confirmation page showing stale values.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return void
	 */
	public function write_block_meta( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		foreach ( Extra_Checkout_Fields_For_Brazil_Blocks::CONTACT_FIELDS as $key ) {
			$this->copy_to_block_meta( $order, $key, 'other' );
		}

		foreach ( Extra_Checkout_Fields_For_Brazil_Blocks::ADDRESS_FIELDS as $key ) {
			$this->copy_to_block_meta( $order, $key, 'billing' );
			$this->copy_to_block_meta( $order, $key, 'shipping' );
		}

		$order->save();
	}

	/**
	 * Copy one historic value into its block meta key when they differ.
	 *
	 * @param WC_Order $order Order being saved.
	 * @param string   $key   Field key without the namespace.
	 * @param string   $group Field group (billing|shipping|other).
	 *
	 * @return void
	 */
	protected function copy_to_block_meta( $order, $key, $group ) {
		$block_key = self::get_block_key( $key, $group );

		// Only fields the block checkout knows about, and only ones the order
		// already carries, so a save does not invent empty meta.
		if ( '' === (string) $order->get_meta( $block_key ) ) {
			return;
		}

		$value = self::to_block_value( $key, $order->get_meta( self::get_legacy_key( $key, $group, $order ) ) );

		if ( (string) $value !== (string) $order->get_meta( $block_key ) ) {
			$order->update_meta_data( $block_key, $value );
		}
	}

	/**
	 * Fall back to the historic meta when a field has no block value yet.
	 *
	 * This is what prefills the checkout block for customers whose data was
	 * captured by the classic checkout.
	 *
	 * @param mixed   $value     Always null when this runs.
	 * @param string  $group     Field group (billing|shipping|other).
	 * @param WC_Data $wc_object Order or customer being read.
	 *
	 * @return mixed
	 */
	public function read_legacy_meta( $value, $group, $wc_object ) {
		if ( ! $wc_object instanceof WC_Data ) {
			return $value;
		}

		$key = Extra_Checkout_Fields_For_Brazil_Blocks::field_key(
			str_replace( 'woocommerce_get_default_value_for_', '', current_filter() )
		);

		if ( '' === $key ) {
			return $value;
		}

		$legacy = $wc_object->get_meta( self::get_legacy_key( $key, $group, $wc_object ) );

		return '' === (string) $legacy ? $value : self::to_block_value( $key, $legacy );
	}

	/**
	 * Drop this plugin's registered fields from the WooCommerce order screen.
	 *
	 * @param array $fields Fields WooCommerce is about to render.
	 *
	 * @return array
	 */
	public function remove_duplicated_admin_fields( $fields ) {
		$namespace = Extra_Checkout_Fields_For_Brazil_Blocks::FIELD_NAMESPACE . '/';

		foreach ( $fields as $key => $field ) {
			// Address fields arrive through array_splice, which discards their
			// string keys, so the id carried in the field itself is the only
			// reliable way to spot them.
			$id = isset( $field['id'] ) ? (string) $field['id'] : (string) $key;

			if ( false !== strpos( $id, $namespace ) ) {
				unset( $fields[ $key ] );
			}
		}

		return $fields;
	}
}

new Extra_Checkout_Fields_For_Brazil_Legacy_Sync();
