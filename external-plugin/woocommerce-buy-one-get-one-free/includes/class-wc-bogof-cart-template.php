<?php
/**
 * WooCommerce Buy One Get One Free cart template actions.
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Cart_Template Class
 */
class WC_BOGOF_Cart_Template {

	/**
	 * Init
	 */
	public static function init() {
		self::extend_store_api();
		self::init_hooks();
	}

	/**
	 * Init hooks
	 */
	public static function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_scripts' ), 20 );
		add_action( 'wp_footer', [ __CLASS__, 'enqueue_scripts' ], 5 );
		add_action( 'woocommerce_before_cart', array( __CLASS__, 'before_cart' ) );
		add_filter( 'woocommerce_cart_item_quantity', array( __CLASS__, 'cart_item_quantity' ), 9999, 3 );
		add_filter( 'woocommerce_coupon_discount_amount_html', array( __CLASS__, 'coupon_discount_amount_html' ), 9999, 2 );
		add_filter( 'woocommerce_store_api_product_quantity_maximum', array( __CLASS__, 'store_api_product_quantity_maximum' ), 9999, 3 );
		add_filter( 'woocommerce_store_api_product_quantity_minimum', array( __CLASS__, 'store_api_product_quantity_maximum' ), 9999, 3 );
		add_filter( 'woocommerce_store_api_product_quantity_editable', array( __CLASS__, 'store_api_product_quantity_editable' ), 9999, 3 );
	}

	/**
	 * Extend the Store API Cart Item schema.
	 */
	public static function extend_store_api() {
		if ( ! ( function_exists( 'woocommerce_store_api_register_endpoint_data' ) && class_exists( '\Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema' ) ) ) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			[
				'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema::IDENTIFIER,
				'namespace'       => strtolower( __CLASS__ ) . '_data',
				'data_callback'   => [ __CLASS__, 'store_api_cart_item_data' ],
				'schema_callback' => [ __CLASS__, 'store_api_cart_item_schema' ],
				'schema_type'     => ARRAY_A,
			]
		);
	}

	/**
	 * Register schema into cart endpoint.
	 *
	 * @return array Registered schema.
	 */
	public static function store_api_cart_item_schema() {
		return [
			'cart_item_class' => [
				'description' => __( '"Buy One Get One Free" cart item classes', 'wc-buy-one-get-one-free' ),
				'type'        => [ 'string', 'null' ],
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			],
		];
	}

	/**
	 * Adds data to the Store API cart route responses.
	 *
	 * @param array $cart_item Current cart item data.
	 * @return array Registered data.
	 */
	public static function store_api_cart_item_data( $cart_item ) {
		$classes      = [];
		$class_prefix = 'wc-bogof-block-item_';

		if ( WC_BOGOF_Cart::is_valid_free_item( $cart_item ) ) {
			$classes[] = "{$class_prefix}_gift";
		}

		if ( WC_BOGOF_Cart::is_valid_discount( $cart_item['data'] ) ) {
			if ( $cart_item['data']->get_price() > 0 ) {
				$classes[] = "{$class_prefix}_has_discount";
			} else {
				$classes[] = "{$class_prefix}_is_free";
			}
		}

		return [
			'cart_item_class' => implode( ' ', $classes ),
		];
	}

	/**
	 * Enqueue cart shortcode styles and the register cart block scripts.
	 */
	public static function register_scripts() {
		if ( is_cart() ) {
			// Cart shortcode.
			wp_enqueue_style( 'cart', plugins_url( 'assets/css/cart.css', WC_BOGOF_PLUGIN_FILE ), array(), WC_Buy_One_Get_One_Free::VERSION );
		}

		wp_register_style( 'wc-bogof-cart-block-template', plugins_url( 'assets/css/cart-block.css', WC_BOGOF_PLUGIN_FILE ), [], WC_Buy_One_Get_One_Free::VERSION );
		wp_register_script( 'wc-bogof-cart-block-template', plugins_url( 'assets/js/build/cart-block-template.js', WC_BOGOF_PLUGIN_FILE ), [ 'wc-blocks-checkout' ], WC_Buy_One_Get_One_Free::VERSION, true );
	}

	/**
	 * Enqueue cart cart block scripts.
	 */
	public static function enqueue_scripts() {
		if ( wp_script_is( 'wc-blocks-checkout', 'registered' ) && ( wp_script_is( 'wc-mini-cart-block-frontend' ) || wp_script_is( 'wc-cart-block-frontend' ) ) ) {
			wp_enqueue_style( 'wc-bogof-cart-block-template' );
			wp_enqueue_script( 'wc-bogof-cart-block-template' );
		}
	}

	/**
	 * Add the filter for the cart items.
	 */
	public static function before_cart() {
		// Add the cart filters.
		add_filter( 'woocommerce_cart_item_price', array( __CLASS__, 'before_cart_item_price' ), -1, 2 );
		add_filter( 'woocommerce_cart_item_price', array( __CLASS__, 'after_cart_item_price' ), 99999, 2 );
		add_filter( 'woocommerce_cart_item_subtotal', array( __CLASS__, 'cart_item_subtotal' ), 99999, 2 );
	}

	/**
	 * Quantity of free items have not be able updated
	 *
	 * @param int    $product_quantity Product quantity.
	 * @param string $cart_item_key Cart item key.
	 * @param array  $cart_item Cart item.
	 * @return string
	 */
	public static function cart_item_quantity( $product_quantity, $cart_item_key, $cart_item = false ) {
		if ( ! $cart_item ) {
			$cart_item = WC()->cart->get_cart_item( $cart_item_key );
		}

		if ( $cart_item && WC_BOGOF_Cart::is_valid_free_item( $cart_item ) ) {
			$product_quantity = sprintf( '%s <input type="hidden" name="cart[%s][qty]" value="%s" />', $cart_item['quantity'], $cart_item_key, $cart_item['quantity'] );
		}
		return $product_quantity;
	}

	/**
	 * Cart item price. For BOGO offers returns the original price.
	 *
	 * @param string $cart_price Price to display.
	 * @param array  $cart_item Cart item.
	 */
	public static function before_cart_item_price( $cart_price, $cart_item ) {

		if ( WC_BOGOF_Cart::is_valid_discount( $cart_item ) ) {
			// Rename the BOGOF discount property to display the default price in the cart.
			$cart_item['data']->_bogof_discount_removed = $cart_item['data']->_bogof_discount;
			unset( $cart_item['data']->_bogof_discount );
		}

		return $cart_price;
	}

	/**
	 * After cart item price. Restore the BOGO discount.
	 *
	 * @param string $cart_price Price to display.
	 * @param array  $cart_item Cart item.
	 */
	public static function after_cart_item_price( $cart_price, $cart_item ) {

		if ( isset( $cart_item['data']->_bogof_discount_removed ) ) {

			$cart_price = wc_bogof_get_cart_product_price( $cart_item['data'] );

			// Restore the BOGOF discount.
			$cart_item['data']->_bogof_discount = $cart_item['data']->_bogof_discount_removed;

			unset( $cart_item['data']->_bogof_discount_removed );

			if ( 'yes' === get_option( 'wc_bogof_base_regular_price', 'no' ) ) {
				$cart_price = wc_bogof_get_cart_product_price(
					$cart_item['data'],
					array(
						'price' => $cart_item['data']->_bogof_discount->get_base_price(),
						'qty'   => 1,
					)
				);
			}

			$cart_price = wc_price( $cart_price );

		} elseif ( WC_BOGOF_Cart::is_valid_free_item( $cart_item ) && WC_BOGOF_Cart::is_valid_discount( $cart_item['data'] ) ) {

			if ( $cart_item['data']->get_price() > 0 ) {
				$cart_price = wc_format_sale_price(
					wc_bogof_get_cart_product_price(
						$cart_item['data'],
						array(
							'price' => $cart_item['data']->_bogof_discount->get_base_price(),
							'qty'   => 1,
						)
					),
					wc_bogof_get_cart_product_price(
						$cart_item['data'],
						array(
							'price' => $cart_item['data']->get_price(),
							'qty'   => 1,
						)
					)
				);
			} else {
				// Free!.
				$cart_price = apply_filters( 'wc_bogof_free_item_cart_price', __( 'Free!', 'woocommerce' ) );
			}
		}
		return $cart_price;
	}

	/**
	 * Cart item subtotal. For BOGO offers display the discount.
	 *
	 * @param string $cart_subtotal Subtotal to display.
	 * @param array  $cart_item Cart item.
	 */
	public static function cart_item_subtotal( $cart_subtotal, $cart_item ) {
		if ( WC_BOGOF_Cart::is_valid_discount( $cart_item ) ) {

			$free_quantity = $cart_item['data']->_bogof_discount->get_free_quantity();
			$base_price    = wc_bogof_get_cart_product_price( $cart_item['data'], array( 'price' => $cart_item['data']->_bogof_discount->get_base_price() ) );
			$raw_discount  = wc_bogof_get_cart_product_price( $cart_item['data'], array( 'price' => $cart_item['data']->_bogof_discount->get_discount() ) ) / $cart_item['data']->_bogof_discount->get_free_quantity();
			$raw_subtotal  = $base_price * $cart_item['quantity'];

			$line_subtotal = sprintf( '<span class="bogof_discount_line">%s</span>', wc_price( $raw_subtotal ) );
			$line_discount = sprintf( '<span class="bogof_discount_line discount">&ndash; %s  &times; %s</span>', $free_quantity, wc_price( $raw_discount ) );
			$line_total    = sprintf( '<span class="bogof_discount_line subtotal">%s%s</span>', apply_filters( 'wc_bogof_discount_line_subtotal_prefix', esc_html__( 'Subtotal', 'woocommerce' ) . ':&nbsp;', $cart_subtotal, $cart_item ), $cart_subtotal );
			$cart_subtotal = '<span class="bogof_discount_item_subtotal">' . $line_subtotal . $line_discount . $line_total . '</span>';
		}
		return $cart_subtotal;
	}

	/**
	 * Coupon amount HTML.
	 *
	 * @param string    $discount_amount_html Coupon amount HTML.
	 * @param WC_Coupon $coupon Coupon object.
	 * @return string
	 */
	public static function coupon_discount_amount_html( $discount_amount_html, $coupon ) {

		$bogo_coupon = WC_BOGOF_Coupon::get( $coupon->get_code() );

		if ( $bogo_coupon->is_in_rule() &&
			wc_bogof_coupon_is_empty( $coupon ) &&
			apply_filters( 'wc_bogof_hide_empty_coupon_amount_html', true, $coupon )
		) {
			// Do not display 0.00 on BOGO coupons.
			$discount_amount_html = '';
		}

		return $discount_amount_html;
	}

	/**
	 * Disable the input quantity field on cart and checkout blocks.
	 *
	 * @param mixed       $value The value being filtered.
	 * @param \WC_Product $product The product object.
	 * @param array       $cart_item The cart item if the product exists in the cart, or null.
	 * @return mixed
	 */
	public static function store_api_product_quantity_maximum( $value, $product, $cart_item ) {

		if ( isset( $cart_item['quantity'] ) && WC_BOGOF_Cart::is_free_item( $cart_item ) ) {
			$value = $cart_item['quantity'];
		}
		return $value;
	}

	/**
	 * Hides the input quantity field on cart and checkout blocks if the item quantity is 1.
	 *
	 * @param mixed       $value The value being filtered.
	 * @param \WC_Product $product The product object.
	 * @param array       $cart_item The cart item if the product exists in the cart, or null.
	 * @return mixed
	 */
	public static function store_api_product_quantity_editable( $value, $product, $cart_item ) {
		if ( isset( $cart_item['quantity'] ) && WC_BOGOF_Cart::is_free_item( $cart_item ) && 1 === absint( $cart_item['quantity'] ) ) {
			return false;
		}

		return $value;
	}
}
