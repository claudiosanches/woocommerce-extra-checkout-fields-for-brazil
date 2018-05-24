<?php
/**
 * Billing data view.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Admin/View
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="clear"></div>

<div class="wcbcf-address">

	<h4><?php esc_html_e( 'Customer data', 'woocommerce-extra-checkout-fields-for-brazil' ); ?></h4>

	<p>
		<?php if ( 0 !== $person_type ) : ?>
			<?php if ( ( 1 === intval( $order->get_meta( '_billing_persontype' ) ) && 1 === $person_type ) || 2 === $person_type ) : ?>
				<strong><?php esc_html_e( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ); ?>: </strong><?php echo esc_html( $order->get_meta( '_billing_cpf' ) ); ?><br />
				<?php if ( isset( $settings['rg'] ) ) : ?>
					<strong><?php esc_html_e( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ); ?>: </strong><?php echo esc_html( $order->get_meta( '_billing_rg' ) ); ?><br />
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( ( 2 === intval( $order->get_meta( '_billing_persontype' ) ) && 1 === $person_type ) || 3 === $person_type ) : ?>
				<strong><?php esc_html_e( 'Company Name', 'woocommerce-extra-checkout-fields-for-brazil' ); ?>: </strong><?php echo esc_html( $order->get_billing_company() ); ?><br />
				<strong><?php esc_html_e( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ); ?>: </strong><?php echo esc_html( $order->get_meta( '_billing_cnpj' ) ); ?><br />

				<?php if ( isset( $settings['ie'] ) ) : ?>
					<strong><?php esc_html_e( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ); ?>: </strong><?php echo esc_html( $order->get_meta( '_billing_ie' ) ); ?><br />
				<?php endif; ?>
			<?php endif; ?>
		<?php else : ?>
				<strong><?php esc_html_e( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ); ?>: </strong><?php echo esc_html( $order->get_billing_company() ); ?><br />
		<?php endif; ?>

		<?php if ( isset( $settings['birthdate_sex'] ) ) : ?>
			<strong><?php esc_html_e( 'Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' ); ?>: </strong><?php echo esc_html( $order->get_meta( '_billing_birthdate' ) ); ?><br />
			<strong><?php esc_html_e( 'Sex', 'woocommerce-extra-checkout-fields-for-brazil' ); ?>: </strong><?php echo esc_html( $order->get_meta( '_billing_sex' ) ); ?><br />
		<?php endif; ?>

		<strong><?php esc_html_e( 'Phone', 'woocommerce-extra-checkout-fields-for-brazil' ); ?>: </strong><?php echo esc_html( $order->get_billing_phone() ); ?><br />

		<?php if ( '' !== $order->get_meta( '_billing_cellphone' ) ) : ?>
			<strong><?php esc_html_e( 'Cell Phone', 'woocommerce-extra-checkout-fields-for-brazil' ) ?>: </strong><?php echo esc_html( $order->get_meta( '_billing_cellphone' ) ); ?><br />
		<?php endif; ?>

		<strong><?php esc_html_e( 'Email', 'woocommerce-extra-checkout-fields-for-brazil' ) ?>: </strong><?php echo wp_kses_post( make_clickable( $order->get_billing_email() ) ); ?><br />
	</p>

</div>
<?php
