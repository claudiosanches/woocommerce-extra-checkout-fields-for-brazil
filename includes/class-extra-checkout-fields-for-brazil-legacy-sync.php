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

		// Both checkouts submit every document field they rendered, so an order
		// can arrive carrying the documents of the person type the customer
		// moved away from. Clear them before the order is saved.
		add_action( 'woocommerce_checkout_create_order', array( $this, 'clear_unused_documents' ), 20 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'clear_unused_documents' ), 20 );

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

		add_filter( 'woocommerce_billing_fields', array( $this, 'remove_duplicated_account_fields' ), 999 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'remove_duplicated_account_fields' ), 999 );
		add_filter( 'woocommerce_filter_fields_for_order_confirmation', array( $this, 'hide_address_fields_from_confirmation' ), 10, 2 );
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
		// The classic checkout never enforced a birthdate format, so a stored
		// value may be d/m/Y or Y-m-d. Normalise what is unambiguous and drop
		// what is not, rather than prefilling a value the block would reject.
		if ( 'birthdate' === $key ) {
			return self::normalize_birthdate( $value );
		}

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
	 * Put a historic birthdate into the dd/mm/yyyy format the block expects.
	 *
	 * @param string $value Stored birthdate.
	 *
	 * @return string Normalised date, or an empty string when unrecognisable.
	 */
	protected static function normalize_birthdate( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		foreach ( array( 'd/m/Y', 'j/n/Y', 'Y-m-d', 'd-m-Y' ) as $format ) {
			$date = DateTime::createFromFormat( $format, $value );

			if ( $date && $date->format( $format ) === $value ) {
				return $date->format( 'd/m/Y' );
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

		// The key becomes a meta key, so only the ones this plugin registers
		// are accepted. Another extension is free to use the namespace, but it
		// does not get to choose where this plugin writes.
		if ( ! in_array( $key, $this->get_keys(), true ) || ! $wc_object instanceof WC_Data ) {
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

		// Only orders that already carry block meta, so a save does not invent
		// it. A field left blank stores an empty value, which still counts.
		if ( ! $order->meta_exists( $block_key ) ) {
			return;
		}

		$value = self::to_block_value( $key, $order->get_meta( self::get_legacy_key( $key, $group, $order ) ) );

		if ( (string) $value !== (string) $order->get_meta( $block_key ) ) {
			$order->update_meta_data( $block_key, $value );
		}
	}

	/**
	 * Documents that do not belong to a person type.
	 *
	 * @var array
	 */
	const UNUSED_DOCUMENTS = array(
		'1' => array( 'cnpj', 'ie' ),
		'2' => array( 'cpf', 'rg' ),
	);

	/**
	 * Empty the documents of the person type the order is not for.
	 *
	 * A customer who fills in a CNPJ and then switches to Individuals still
	 * submits it, and so does a request crafted against the Store API. Leaving
	 * it stored hands gateways and ERPs a document that contradicts the order.
	 *
	 * @param WC_Order $order Order being created.
	 *
	 * @return void
	 */
	public function clear_unused_documents( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$settings    = (array) get_option( 'wcbcf_settings', array() );
		$person_type = isset( $settings['person_type'] ) ? intval( $settings['person_type'] ) : 0;

		if ( 0 === $person_type ) {
			return;
		}

		// A store that accepts one person type never asks, so the setting is
		// the answer: 2 means individuals, 3 means legal persons.
		if ( 1 === $person_type ) {
			$selected = (string) $order->get_meta( '_billing_persontype' );
		} else {
			$selected = 2 === $person_type ? '1' : '2';
		}

		if ( ! isset( self::UNUSED_DOCUMENTS[ $selected ] ) ) {
			return;
		}

		foreach ( self::UNUSED_DOCUMENTS[ $selected ] as $key ) {
			$order->update_meta_data( self::get_legacy_key( $key, 'other', $order ), '' );
			$order->update_meta_data( self::get_block_key( $key, 'other' ), '' );
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
	 * Drop the historic number and neighborhood fields on My Account.
	 *
	 * WooCommerce renders its own copy of every registered address field on the
	 * edit-address form, so the customer would otherwise see each label twice,
	 * with the two copies writing to different meta. WooCommerce's copy is the
	 * one kept: saving it also updates the historic meta through
	 * write_legacy_meta().
	 *
	 * This filters the field list rather than the rendered form, because
	 * WC_Form_Handler::save_address() validates and saves from the same list.
	 *
	 * @param array $fields Address fields.
	 *
	 * @return array
	 */
	public function remove_duplicated_account_fields( $fields ) {
		global $wp;

		if ( ! isset( $wp->query_vars['edit-address'] ) ) {
			return $fields;
		}

		foreach ( Extra_Checkout_Fields_For_Brazil_Blocks::ADDRESS_FIELDS as $key ) {
			unset( $fields[ 'billing_' . $key ], $fields[ 'shipping_' . $key ] );
		}

		return $fields;
	}

	/**
	 * Keep number and neighborhood out of the order confirmation's additional
	 * information, since the formatted address already shows them.
	 *
	 * @param bool  $show  Whether WooCommerce would show the field.
	 * @param array $field Field definition.
	 *
	 * @return bool
	 */
	public function hide_address_fields_from_confirmation( $show, $field ) {
		$key = Extra_Checkout_Fields_For_Brazil_Blocks::field_key( isset( $field['id'] ) ? $field['id'] : '' );

		if ( in_array( $key, Extra_Checkout_Fields_For_Brazil_Blocks::ADDRESS_FIELDS, true ) ) {
			return false;
		}

		return $show;
	}

	/**
	 * Drop this plugin's registered fields from the WooCommerce order screen.
	 *
	 * @param array $fields Fields WooCommerce is about to render.
	 *
	 * @return array
	 */
	public function remove_duplicated_admin_fields( $fields ) {
		$prefixes = array();

		foreach ( array( 'billing', 'shipping', 'other' ) as $group ) {
			foreach ( $this->get_keys() as $key ) {
				$prefixes[] = self::get_block_key( $key, $group );
			}
		}

		foreach ( $fields as $key => $field ) {
			// Address fields arrive through array_splice, which discards their
			// string keys, so the id carried in the field itself is the only
			// reliable way to spot them. Match it exactly: an unrelated field
			// whose id merely contains the namespace must keep saving.
			$id = isset( $field['id'] ) ? (string) $field['id'] : (string) $key;

			if ( in_array( $id, $prefixes, true ) ) {
				unset( $fields[ $key ] );
			}
		}

		return $fields;
	}
}

new Extra_Checkout_Fields_For_Brazil_Legacy_Sync();
