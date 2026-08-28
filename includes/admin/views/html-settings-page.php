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
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors(); ?>

	<div class="bmw-settings-layout">
		<main class="bmw-settings-main">
			<form id="bmw-settings" method="post" action="options.php">
				<div class="bmw-settings-card">
					<?php
					settings_fields( 'wcbcf_settings' );
					do_settings_sections( 'wcbcf_settings' );
					?>
				</div>
				<?php submit_button(); ?>
			</form>
		</main>

		<aside class="bmw-settings-sidebar">
			<?php require __DIR__ . '/html-admin-announcements.php'; ?>
			<?php require __DIR__ . '/html-admin-support.php'; ?>
		</aside>
	</div>
</div>

<?php
