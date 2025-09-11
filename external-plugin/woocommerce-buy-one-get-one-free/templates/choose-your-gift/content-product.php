<?php
/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/choose-your-gift/content-product.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WC_BOGOF\Templates
 * @version 4.2.0
 *
 * @param WC_Product $product Current loop product.
 * @param int        $loop_index Loop index.
 */

defined( 'ABSPATH' ) || exit;

// Ensure visibility.
if ( empty( $product ) ) {
	return;
}
?>
<div class="wc-bogof-product <?php echo esc_attr( wc_bogof_gift_item_classes( $product, $loop_index ) ); ?>" data-id="<?php echo esc_attr( $product->get_id() ); ?>" >
	<div class="wc-bogof-product-loop-item">
	<?php

	/**
	 * Hook: wc_bogof_before_gift_item_title.
	 *
	 * @hooked wc_bogof_gift_template_thumbnail - 10
	 */
	do_action( 'wc_bogof_before_gift_item_title', $product );
	?>
		<div class="wc-bogof-product-summary">
		<?php
		/**
		 * Hook: wc_bogof_gift_template_title.
		 *
		 * @hooked woocommerce_template_loop_product_title - 10
		 */
		do_action( 'wc_bogof_gift_item_title', $product );

		/**
		 * Hook: wc_bogof_after_gift_item_title.
		 *
		 * @hooked wc_bogof_gift_template_loop_short_description - 10
		 * @hooked wc_bogof_gift_template_loop_price - 20
		 */
		do_action( 'wc_bogof_after_gift_item_title', $product );

		/**
		 * Hook: wc_bogof_after_gift_item.
		 *
		 * @hooked wc_bogof_gift_template_add_to_cart - 10
		 */
		do_action( 'wc_bogof_after_gift_item', $product );
		?>
		</div>
	</div>
</div>
