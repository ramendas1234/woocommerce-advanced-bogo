<?php
/**
 * The template for displaying the choose your gift notice.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/choose-your-gift/announcement-bar.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WC_BOGOF\Templates
 * @version 4.2.0
 */

defined( 'ABSPATH' ) || exit;
?>

<template id="wc-bogof-announcement-tpl">
	<div id="wc-bogof-announcement-bar" class="<?php echo esc_attr( empty( $sticky ) ? '' : 'wc-bogof-sticky' ); ?>">
		<div class="wc-bogof-announcement-container close-container">
			<a href="#" class="toggle-close">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="m5.84 9.59l5.66 5.66l5.66-5.66l-.71-.7l-4.95 4.95l-4.95-4.95z"/></svg>
			</a>
		</div>
		<div class="wc-bogof-announcement-container">
			<ul>
				<li class="wc-bogof-announcement-notice">
				</li>
			</ul>
			<a href="#" class="wc-bogof-announcement-close">&times;</a>
		</div>
	</div>
</template>
