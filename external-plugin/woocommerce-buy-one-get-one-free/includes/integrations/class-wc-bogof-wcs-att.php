<?php
/**
 * Buy One Get One Free - All Products For Subscriptions by SomewhereWarm
 *
 * @see https://woocommerce.com/es-es/products/all-products-for-woocommerce-subscriptions/
 * @since 1.3.7
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_WCS_ATT Class
 */
class WC_BOGOF_WCS_ATT {

	/**
	 * Retrun the minimun version required.
	 */
	public static function min_version_required() {
		return '2.1.5';
	}

	/**
	 * Returns the extension name.
	 */
	public static function extension_name() {
		return 'All Products for Subscriptions';
	}

	/**
	 * Checks the minimum version required.
	 */
	public static function check_min_version() {
		return version_compare( WCS_ATT::VERSION, static::min_version_required(), '>=' );
	}

	/**
	 * Init hooks
	 */
	public static function init() {
		add_filter( 'wcsatt_cart_item_options', array( __CLASS__, 'cart_item_options' ), 100, 3 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( __CLASS__, 'get_cart_item_from_session' ), 110 );
		add_filter( 'wc_bogof_load_conditions', array( __CLASS__, 'load_conditions' ) );
		add_filter( 'woocommerce_cart_item_price', array( __CLASS__, 'before_cart_item_price' ), 0, 3 );
		add_filter( 'woocommerce_cart_item_price', array( __CLASS__, 'after_cart_item_price' ), 10000, 3 );
		add_action( 'wc_bogof_before_choose_your_gift_loop', array( __CLASS__, 'disable_all_product_for_subscriptions' ) );
		add_action( 'wc_bogof_after_choose_your_gift_loop', array( __CLASS__, 'enable_all_product_for_subscriptions' ) );
		add_action( 'wc_bogof_choose_your_gift_init', array( __CLASS__, 'disable_all_product_for_subscriptions' ) );
	}

	/**
	 * Disable the subscriptions options for the free product.
	 *
	 * @param array $options Subscriptions options.
	 * @param array $subscription_schemes Subscription schemes.
	 * @param array $cart_item Cart item data.
	 * @return array
	 */
	public static function cart_item_options( $options, $subscription_schemes, $cart_item ) {
		if ( WC_BOGOF_Cart::is_free_item( $cart_item ) ) {
			$options = array( $options[0] );
		}
		return $options;
	}

	/**
	 * Set free product
	 *
	 * @param array $session_data Session data.
	 * @return array
	 */
	public static function get_cart_item_from_session( $session_data ) {
		if ( WC_BOGOF_Cart::is_free_item( $session_data ) ) {
			unset( $session_data['wcsatt_data'] );
		}
		return $session_data;
	}

	/**
	 * Add the All Product for WooCommerce Subsctiption condition.
	 *
	 * @param array $conditions Conditions array.
	 * @return array
	 */
	public static function load_conditions( $conditions ) {
		$conditions = is_array( $conditions ) ? $conditions : array();

		if ( ! class_exists( 'WC_BOGOF_Condition_WCS_ATT' ) ) {
			include_once dirname( WC_BOGOF_PLUGIN_FILE ) . '/includes/conditions/class-wc-bogof-condition-wcs-att.php';
		}

		$conditions[] = new WC_BOGOF_Condition_WCS_ATT();

		return $conditions;
	}

	/**
	 * Cart item price.
	 *
	 * @param string $cart_price Price to display.
	 * @param array  $cart_item Cart item.
	 * @param string $cart_item_key Cart item key.
	 */
	public static function before_cart_item_price( $cart_price, $cart_item, $cart_item_key ) {
		if ( ! isset( $cart_item['data']->_bogof_discount_removed ) ) {
			return $cart_price;
		}

		$product       = $cart_item['data'];
		$supports_args = array(
			'cart_item'     => $cart_item,
			'cart_item_key' => $cart_item_key,
		);

		if ( WCS_ATT_Product::supports_feature( $product, 'subscription_scheme_options_product_cart', $supports_args ) ) {
			$cart_item['data']->_bogof_discount_removed->add_extra_data(
				'att_cart_item_price',
				WC()->cart->get_product_price( $product )
			);
		}

		return $cart_price;
	}

	/**
	 * Restore the ATT Cart item price.
	 *
	 * @param string $cart_price Price to display.
	 * @param array  $cart_item Cart item.
	 * @param string $cart_item_key Cart item key.
	 */
	public static function after_cart_item_price( $cart_price, $cart_item, $cart_item_key ) {
		if ( WC_BOGOF_Cart::is_valid_discount( $cart_item ) && false !== $cart_item['data']->_bogof_discount->get_extra_data( 'att_cart_item_price' ) ) {
			$discount = $cart_item['data']->_bogof_discount;
			unset( $cart_item['data']->_bogof_discount );

			$cart_price = WCS_ATT_Display_Cart::show_cart_item_subscription_options(
				$discount->get_extra_data( 'att_cart_item_price' ),
				$cart_item,
				$cart_item_key
			);

			$discount->remove_extra_data( 'att_cart_item_price' );

			$cart_item['data']->_bogof_discount = $discount;
		}
		return $cart_price;
	}

	/**
	 * Disable the All Product for Subscription support on products.
	 */
	public static function disable_all_product_for_subscriptions() {
		add_filter( 'wcsatt_product_supports_feature', array( __CLASS__, 'product_supports_feature' ), 9999 );
	}

	/**
	 * Enable the All Product for Subscription support on products.
	 */
	public static function enable_all_product_for_subscriptions() {
		remove_filter( 'wcsatt_product_supports_feature', array( __CLASS__, 'product_supports_feature' ), 9999 );
	}

	/**
	 * Return false to disable the All Product for Subscription support on products.
	 *
	 * @param bool $is_feature_supported Is feature supported?.
	 */
	public static function product_supports_feature( $is_feature_supported ) {
		return false;
	}
}
