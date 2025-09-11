<?php
/**
 * Show "Choose your gift" message.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/notices/choose-your-gift.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WC_BOGOF\Templates
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $notices ) {
	return;
}

?>

<div class="woocommerce-choose-your-gift-notice-wrapper">
<?php foreach ( $notices as $notice ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals ?>
	<div class="woocommerce-message choose-your-gift-notice"<?php echo wc_get_notice_data_attr( $notice ); ?> role="alert">
		<?php echo wc_kses_notice( $notice['notice'] ); ?>
	</div>
<?php endforeach; ?>
</div>
