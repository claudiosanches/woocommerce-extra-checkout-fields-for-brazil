<?php
/**
 * Spending goals progress bar. The Cart and Checkout blocks draw the same
 * markup from the same data.
 *
 * @package Extra_Checkout_Fields_For_Brazil/View
 *
 * @var array  $bar   Style, message, progress and milestones.
 * @var string $place Where it shows: cart, checkout or product.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="csbmw-goals csbmw-goals-style-<?php echo esc_attr( $bar['style'] ); ?>" data-place="<?php echo esc_attr( $place ); ?>">
	<p class="csbmw-goals-message"><?php echo esc_html( $bar['message'] ); ?></p>
	<div class="csbmw-goals-bar" role="progressbar" aria-label="<?php esc_attr_e( 'Spending goals', 'woocommerce-extra-checkout-fields-for-brazil' ); ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( (string) round( $bar['progress'] ) ); ?>" aria-valuetext="<?php echo esc_attr( $bar['message'] ); ?>">
		<span class="csbmw-goals-fill" style="--csbmw-progress: <?php echo esc_attr( (string) $bar['progress'] ); ?>"></span>
		<?php foreach ( $bar['milestones'] as $milestone ) : ?>
			<span class="csbmw-goals-mark<?php echo $milestone['reached'] ? ' is-reached' : ''; ?>" style="--csbmw-position: <?php echo esc_attr( (string) $milestone['position'] ); ?>"></span>
		<?php endforeach; ?>
	</div>
	<?php if ( $bar['milestones'] ) : ?>
		<ul class="csbmw-goals-milestones">
			<?php foreach ( $bar['milestones'] as $milestone ) : ?>
				<li class="csbmw-goals-milestone<?php echo $milestone['reached'] ? ' is-reached' : ''; ?>">
					<span class="csbmw-goals-label"><?php echo esc_html( $milestone['label'] ); ?></span>
					<span class="csbmw-goals-amount"><?php echo esc_html( $milestone['amount'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
