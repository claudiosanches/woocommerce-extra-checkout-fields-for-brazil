<?php
/**
 * Spending goals progress bar. The Cart and Checkout blocks draw the same
 * markup from the same data.
 *
 * @package Extra_Checkout_Fields_For_Brazil/View
 *
 * @var array  $bar   Style, message and its parts, progress, milestones and
 *                    colors.
 * @var string $place Where it shows: cart, checkout, product or block.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php
// The store's colors, which a placed bar's own colors override.
$csbmw_colors = '';

foreach ( array_filter( $bar['colors'] ) as $csbmw_part => $csbmw_color ) {
	$csbmw_colors .= '--csbmw-goals-default-' . $csbmw_part . ':' . $csbmw_color . ';';
}
?>
<div class="csbmw-goals csbmw-goals-style-<?php echo esc_attr( $bar['style'] ); ?>" data-place="<?php echo esc_attr( $place ); ?>"<?php echo '' !== $csbmw_colors ? ' style="' . esc_attr( $csbmw_colors ) . '"' : ''; ?>>
	<p class="csbmw-goals-message">
		<?php
		foreach ( $bar['parts'] as $csbmw_part ) {
			echo $csbmw_part['strong'] ? '<strong>' . esc_html( $csbmw_part['text'] ) . '</strong>' : esc_html( $csbmw_part['text'] );
		}
		?>
	</p>
	<div class="csbmw-goals-bar" role="progressbar" aria-label="<?php esc_attr_e( 'Spending goals', 'woocommerce-extra-checkout-fields-for-brazil' ); ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( (string) round( $bar['progress'] ) ); ?>" aria-valuetext="<?php echo esc_attr( $bar['message'] ); ?>">
		<span class="csbmw-goals-fill" style="--csbmw-progress: <?php echo esc_attr( (string) $bar['progress'] ); ?>"></span>
		<?php foreach ( $bar['milestones'] as $milestone ) : ?>
			<span class="csbmw-goals-mark<?php echo $milestone['reached'] ? ' is-reached' : ''; ?>" style="--csbmw-position: <?php echo esc_attr( (string) $milestone['position'] ); ?>"></span>
		<?php endforeach; ?>
	</div>
</div>
