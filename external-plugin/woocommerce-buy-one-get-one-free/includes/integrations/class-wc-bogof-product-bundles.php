<?php
/**
 * Buy One Get One Free - Product Bundles by SomewhereWarm
 *
 * @see https://woocommerce.com/products/product-bundles/
 * @since 2.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Product_Bundles Class
 */
class WC_BOGOF_Product_Bundles {

	/**
	 * Retrun the minimun version required.
	 */
	public static function min_version_required() {
		return '6.3.0';
	}

	/**
	 * Returns the extension name.
	 */
	public static function extension_name() {
		return 'Product Bundles';
	}

	/**
	 * Checks the minimum version required.
	 */
	public static function check_min_version() {
		return defined( 'WC_PB_VERSION' ) ? version_compare( WC_PB_VERSION, static::min_version_required(), '>=' ) : false;
	}

	/**
	 * Init hooks
	 */
	public static function init() {
		add_filter( 'wc_bogof_load_conditions', array( __CLASS__, 'load_conditions' ) );
		add_filter( 'wc_bogof_buy_a_get_a_add_cart_item_data', array( __CLASS__, 'buy_a_get_a_add_cart_item_data' ) );
		add_action( 'wc_bogof_auto_add_to_cart', array( __CLASS__, 'auto_add_to_cart' ), 10, 2 );
		add_action( 'wc_bogof_after_cart_set_item_quantity', array( __CLASS__, 'after_cart_set_item_quantity' ), 10, 2 );
		add_action( 'wc_bogof_after_cart_remove_item', array( __CLASS__, 'after_cart_remove_item' ), 10, 2 );
		add_action( 'wc_bogof_after_set_cart_item_discount', array( __CLASS__, 'cart_item_discount_init' ), 10, 2 );
		add_action( 'wc_bogof_init_cart_item_discount', array( __CLASS__, 'cart_item_discount_init' ), 10, 2 );
		add_filter( 'wc_bogof_cart_item_discount_sale_price', array( __CLASS__, 'cart_item_discount_sale_price' ), 10, 2 );
		add_filter( 'wc_bogof_discount_line_subtotal_prefix', array( __CLASS__, 'discount_line_subtotal_prefix' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_subtotal', array( __CLASS__, 'cart_item_subtotal' ), 9999, 2 );
	}

	/**
	 * Add the All Product for WooCommerce Subsctiption condition.
	 *
	 * @param array $conditions Conditions array.
	 * @return array
	 */
	public static function load_conditions( $conditions ) {
		$conditions = is_array( $conditions ) ? $conditions : array();

		if ( ! class_exists( 'WC_BOGOF_Condition_Product_Bundles' ) ) {
			include_once dirname( WC_BOGOF_PLUGIN_FILE ) . '/includes/conditions/class-wc-bogof-condition-product-bundles.php';
		}

		$conditions[] = new WC_BOGOF_Condition_Product_Bundles();

		return $conditions;
	}

	/**
	 * Unset specific cart item keys.
	 *
	 * @param array $cart_item_data Cart item data.
	 */
	public static function buy_a_get_a_add_cart_item_data( $cart_item_data ) {
		if ( isset( $cart_item_data['bundled_item_id'] ) ) {
			unset(
				$cart_item_data['stamp'],
				$cart_item_data['bundled_items']
			);
		} elseif ( isset( $cart_item_data['bundled_items'] ) ) {
			$cart_item_data['bundled_items'] = [];
		}

		unset(
			$cart_item_data['bundled_by'],
			$cart_item_data['bundled_item_id']
		);

		return $cart_item_data;
	}

	/**
	 * Calls to add_to_cart function of product bundles.
	 *
	 * @param array $cart_item_data Cart item data.
	 */
	public static function auto_add_to_cart( $cart_item_data ) {
		if ( ! is_callable( [ 'WC_PB_Cart', 'instance' ] ) ) {
			return;
		}
		if ( ! is_callable( [ WC_PB_Cart::instance(), 'bundle_add_to_cart' ] ) ) {
			return;
		}

		WC_PB_Cart::instance()->bundle_add_to_cart(
			$cart_item_data['key'],
			$cart_item_data['product_id'],
			$cart_item_data['quantity'],
			$cart_item_data['variation_id'],
			$cart_item_data['variation'],
			$cart_item_data
		);
	}

	/**
	 * Calls to update_quantity_in_cart function of product bundles.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $quantity Quantity.
	 */
	public static function after_cart_set_item_quantity( $cart_item_key, $quantity ) {
		if ( ! is_callable( [ 'WC_PB_Cart', 'instance' ] ) ) {
			return;
		}
		if ( ! is_callable( [ WC_PB_Cart::instance(), 'update_quantity_in_cart' ] ) ) {
			return;
		}

		WC_PB_Cart::instance()->update_quantity_in_cart( $cart_item_key, $quantity );
	}

	/**
	 * Calls to cart_item_remove function of product bundles.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $quantity Quantity.
	 */
	public static function after_cart_remove_item( $cart_item_key, $quantity ) {
		if ( ! is_callable( [ 'WC_PB_Cart', 'instance' ] ) ) {
			return;
		}
		if ( ! is_callable( [ WC_PB_Cart::instance(), 'update_qcart_item_removeuantity_in_cart' ] ) ) {
			return;
		}

		WC_PB_Cart::instance()->cart_item_remove( $cart_item_key, WC()->cart );
	}

	/**
	 * Recalculate the bundle base price.
	 *
	 * @param array                       $cart_item Cart item data.
	 * @param WC_BOGOF_Cart_Item_Discount $cart_discount The discount object.
	 */
	public static function cart_item_discount_init( $cart_item, $cart_discount ) {
		if ( wc_pb_is_bundle_container_cart_item( $cart_item ) && WC_Product_Bundle::group_mode_has( $cart_item['data']->get_group_mode(), 'aggregated_prices' ) && ! $cart_discount->get_extra_data( 'is_bundle' ) ) {
			// Calculate the price based on bundles items.
			$bundle_price  = $cart_discount->get_base_price();
			$base_price    = $bundle_price;
			$items_price   = 0;
			$bundled_items = wc_pb_get_bundled_cart_items( $cart_item, WC()->cart->cart_contents );

			foreach ( $bundled_items as $bundled_item ) {
				$base_price  += $bundled_item['data']->get_price() * absint( $bundled_item['quantity'] ) / absint( $cart_item['quantity'] );
				$items_price += $bundled_item['data']->get_price() * $bundled_item['quantity'];
			}

			$cart_discount->set_base_price( $base_price );
			$cart_discount->add_extra_data( 'is_bundle', true );
			$cart_discount->add_extra_data( 'bundle_price', $bundle_price );
			$cart_discount->add_extra_data( 'bundle_items_price', $items_price );
		}
	}

	/**
	 * Return the sale price for bundle.
	 *
	 * @param float                       $sale_price Discount sale price.
	 * @param WC_BOGOF_Cart_Item_Discount $cart_discount The discount object.
	 */
	public static function cart_item_discount_sale_price( $sale_price, $cart_discount ) {
		if ( $cart_discount->get_extra_data( 'is_bundle' ) ) {

			$bundle_price       = floatval( $cart_discount->get_extra_data( 'bundle_price' ) ) * $cart_discount->get_cart_quantity();
			$bundle_items_price = floatval( $cart_discount->get_extra_data( 'bundle_items_price' ) );

			$final_price = $bundle_price + $bundle_items_price - $cart_discount->get_discount();
			$sale_price  = ( $final_price - $bundle_items_price ) / $cart_discount->get_cart_quantity();

		}
		return $sale_price;
	}

	/**
	 * Removes the discount prefix for bundle items.
	 *
	 * @param string $prefix Discount prefix.
	 * @param string $cart_subtotal Subtotal to display after discount.
	 */
	public static function discount_line_subtotal_prefix( $prefix, $cart_subtotal ) {
		if ( false !== strpos( $cart_subtotal, 'bundled_table_item_subtotal' ) ) {
			$prefix = '';
		}
		return $prefix;
	}

	/**
	 * Cart item subtotal. Recalculate the subtotal for Bundle containers.
	 *
	 * @param string $cart_subtotal Subtotal to display.
	 * @param array  $cart_item Cart item.
	 */
	public static function cart_item_subtotal( $cart_subtotal, $cart_item ) {
		if ( WC_BOGOF_Cart::is_valid_discount( $cart_item ) && $cart_item['data']->_bogof_discount->get_extra_data( 'is_bundle' ) ) {
			$bundle_price  = wc_bogof_get_cart_product_price( $cart_item['data'], array( 'qty' => $cart_item['quantity'] ) );
			$bundled_items = wc_pb_get_bundled_cart_items( $cart_item, WC()->cart->cart_contents );

			foreach ( $bundled_items as $bundled_item ) {
				$bundle_price += wc_bogof_get_cart_product_price( $bundled_item['data'], array( 'qty' => $bundled_item['quantity'] ) );
			}
			$cart_subtotal = WC_PB_Display::instance()->format_subtotal( $cart_item['data'], $bundle_price );

		}
		return $cart_subtotal;
	}

}
