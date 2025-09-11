<?php
/**
 * Buy One Get One Free - WooCommerce Quick View by WooCommerce
 *
 * @see https://woocommerce.com/products/woocommerce-quick-view/
 * @since 2.2.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Quick_View Class
 */
class WC_BOGOF_Quick_View {

	/**
	 * Retrun the minimun version required.
	 */
	public static function min_version_required() {
		return '1.4.0';
	}

	/**
	 * Returns the extension name.
	 */
	public static function extension_name() {
		return 'WooCommerce Quick View';
	}

	/**
	 * Checks the minimum version required.
	 */
	public static function check_min_version() {
		return defined( 'WC_QUICK_VIEW_VERSION' ) ? version_compare( WC_QUICK_VIEW_VERSION, static::min_version_required(), '>=' ) : false;
	}

	/**
	 * Init hooks
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 20 );
	}

	/**
	 * Enqueue the Quick View script on choose your gift shortcode.
	 */
	public static function enqueue_scripts() {
		if ( isset( $GLOBALS['WC_Quick_View'] ) && is_a( $GLOBALS['WC_Quick_View'], 'WC_Quick_View' ) ) {
			add_action( 'wc_bogof_before_choose_your_gift_loop', array( $GLOBALS['WC_Quick_View'], 'enqueue_scripts' ) );
		}
	}
}
