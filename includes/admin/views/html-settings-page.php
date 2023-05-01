<?php
/**
 * Settings page view.
 *
 * @package Extra_Checkout_Fields_For_Brazil/Admin/View
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<?php settings_errors(); ?>

	<form id="bmw-settings" method="post" action="options.php">
		<div class="box">
			<?php
				settings_fields( 'wcbcf_settings' );
				do_settings_sections( 'wcbcf_settings' );
				submit_button();
			?>
		</div>
		<div class="box">
			<?php require dirname( __FILE__ ) . '/html-admin-support.php'; ?>
		</div>
	</form>
</div>

<?php
