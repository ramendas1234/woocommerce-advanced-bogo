<?php
/**
 * Settings page.
 *
 * @var array $settings Array of options.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap woocommerce">
	<h1 class=""><?php esc_html_e( 'Settings', 'wc-buy-one-get-one-free' ); ?></h1>
	<form method="post" id="mainform" action="" enctype="multipart/form-data">
		<?php

			WC_Admin_Settings::show_messages();

			woocommerce_admin_fields( $settings );
		?>
		<p class="submit">
			<button name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
			<?php wp_nonce_field( 'wc-bogo-settings' ); ?>
		</p>
	</form>
</div>
