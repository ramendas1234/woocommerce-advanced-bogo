<?php
/**
 * The template for displaying the gifts in a modal dialog.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/choose-your-gift/modal-dialog.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WC_BOGOF\Templates
 * @version 4.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<template id="wc-bogof-gift-modal-tpl">
<div class="wc-bogo-modal hidden <?php echo esc_attr( wc_bogof_gift_modal_container_classes() ); ?>" id="choose-your-gift-dialog" role="dialog" aria-hidden="true">
	<div class="wc-bogo-modal-dialog">
		<div class="wc-bogo-modal-content">
			<span class="wc-bogo-modal-close" data-dismiss="wc-bogo-modal">&times;</span>
			<div class="wc-bogo-modal-header">
				<h3></h3>
			</div>
			<div class="wc-bogo-modal-body">
				<div class="wc-bogof-products"></div>
				<div class="wc-bogof-load-more-products"></div>
			</div>
		</div>
	</div>
</div>
</template>
