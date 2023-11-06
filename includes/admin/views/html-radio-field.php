<?php
/**
 * Checkbox field view.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Admin/View
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p>
<?php foreach ( $args['options'] as $key => $value ) : ?>
	<label>
		<input name="<?php echo esc_attr( $menu ); ?>[<?php echo esc_attr( $id ); ?>]" type="radio" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $current, true ); ?> /> <?php echo esc_html( $value ); ?>
	</label>
	<br>
<?php endforeach; ?>
</p>
<?php if ( isset( $args['description'] ) ) : ?>
	<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
<?php endif; ?>
