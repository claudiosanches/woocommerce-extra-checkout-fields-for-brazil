<?php
/**
 * Checkbox field view.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Admin/View
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_disabled = ! empty( $args['disabled'] );
?>
<?php if ( $is_disabled && 1 === $current ) : ?>
	<input type="hidden" name="<?php echo esc_attr( $menu ); ?>[<?php echo esc_attr( $id ); ?>]" value="1" />
<?php endif; ?>
<label class="bmw-switch-field<?php echo $is_disabled ? ' is-disabled' : ''; ?>" for="<?php echo esc_attr( $id ); ?>">
	<input class="bmw-switch-input" type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $menu ); ?>[<?php echo esc_attr( $id ); ?>]" value="1" <?php checked( 1, $current, true ); ?> <?php disabled( $is_disabled ); ?> />
	<span class="bmw-switch-track" aria-hidden="true"><span class="bmw-switch-thumb"></span></span>
	<span class="bmw-switch-content">
		<?php if ( isset( $args['title'] ) ) : ?>
			<h3><?php echo esc_html( $args['title'] ); ?></h3>
		<?php endif; ?>
		<?php if ( isset( $args['label'] ) ) : ?>
			<p class="label"><?php echo esc_html( $args['label'] ); ?></p>
		<?php endif; ?>
		<?php if ( isset( $args['description'] ) ) : ?>
			<p class="description"><?php echo wp_kses_post( $args['description'] ); ?></p>
		<?php endif; ?>
	</span>
</label>