<?php
/**
 * Admin options view.
 *
 * @package   Extra_Checkout_Fields_For_Brazil_Admin
 * @author    Claudio Sanches <contato@claudiosmweb.com>
 * @license   GPL-2.0+
 * @copyright 2013 Claudio Sanches
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<?php settings_errors(); ?>
	<form method="post" action="options.php">

		<?php
			settings_fields( 'wcbcf_settings' );
			do_settings_sections( 'wcbcf_settings' );

			submit_button();
		?>

	</form>

</div>
