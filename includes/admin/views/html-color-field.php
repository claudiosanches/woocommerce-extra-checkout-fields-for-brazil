<?php
/**
 * Color field view.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Admin/View
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="bmw-select-field bmw-color-field">
	<span class="bmw-select-content">
		<h3><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $args['title'] ); ?></label></h3>
		<input type="text" class="bmw-color-picker" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $menu ); ?>[<?php echo esc_attr( $id ); ?>]" value="<?php echo esc_attr( $current ); ?>" />
		<?php if ( isset( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
	</span>
</div>
