<?php
/**
 * Select field view.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Admin/View
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="bmw-select-field">
	<span class="bmw-select-content">
		<?php if ( isset( $args['title'] ) ) : ?>
			<h3><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $args['title'] ); ?></label></h3>
		<?php endif; ?>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $menu ); ?>[<?php echo esc_attr( $id ); ?>]">
			<?php foreach ( $args['options'] as $key => $value ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key, true ); ?>><?php echo esc_html( $value ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php if ( isset( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
	</span>
</div>