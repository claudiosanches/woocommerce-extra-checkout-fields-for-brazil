<?php
/**
 * Text field view.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Admin/View
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="bmw-select-field bmw-text-field">
	<span class="bmw-select-content">
		<?php if ( isset( $args['title'] ) ) : ?>
			<h3><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $args['title'] ); ?></label></h3>
		<?php endif; ?>
		<input type="text" class="regular-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $menu ); ?>[<?php echo esc_attr( $id ); ?>]" value="<?php echo esc_attr( $current ); ?>" maxlength="<?php echo esc_attr( (string) Extra_Checkout_Fields_For_Brazil_Blocks::MAX_LENGTHS['number'] ); ?>" />
		<?php if ( isset( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
	</span>
</div>
