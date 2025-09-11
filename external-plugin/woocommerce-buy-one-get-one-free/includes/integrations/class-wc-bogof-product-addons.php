<?php
/**
 * Buy One Get One Free - WooCommerce Product Add-ons
 *
 * @see https://woocommerce.com/products/product-add-ons/
 * @since 4.1.5
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Tier_Pricing_Table Class
 */
class WC_BOGOF_Product_Addons {

	/**
	 * Retrun the minimun version required.
	 */
	public static function min_version_required() {
		return '3.0.14';
	}

	/**
	 * Returns the extension name.
	 */
	public static function extension_name() {
		return 'WooCommerce Product Add-ons';
	}

	/**
	 * Checks the minimum version required.
	 */
	public static function check_min_version() {
		return defined( 'WC_PRODUCT_ADDONS_VERSION' ) ? version_compare( WC_PRODUCT_ADDONS_VERSION, static::min_version_required(), '>=' ) : false;
	}

	/**
	 * Init integration.
	 */
	public static function init() {
		add_action( 'wc_bogof_before_add_gift_item_to_cart', array( __CLASS__, 'before_add_gift_item_to_cart' ), 10, 5 );
		add_action( 'wc_bogof_after_add_gift_item_to_cart', array( __CLASS__, 'after_add_gift_item_to_cart' ), 10, 5 );
	}

	/**
	 * Runs before adding a the gift item to the cart.
	 */
	public static function before_add_gift_item_to_cart() {
		if ( ! empty( $GLOBALS['Product_Addon_Cart'] ) ) {
			remove_filter( 'woocommerce_add_cart_item_data', [ $GLOBALS['Product_Addon_Cart'], 'add_cart_item_data' ], 10, 2 );
		}
	}

	/**
	 * Runs after adding a the gift item to the cart.
	 */
	public static function after_add_gift_item_to_cart() {
		if ( ! empty( $GLOBALS['Product_Addon_Cart'] ) ) {
			add_filter( 'woocommerce_add_cart_item_data', [ $GLOBALS['Product_Addon_Cart'], 'add_cart_item_data' ], 10, 2 );
		}
	}
}
