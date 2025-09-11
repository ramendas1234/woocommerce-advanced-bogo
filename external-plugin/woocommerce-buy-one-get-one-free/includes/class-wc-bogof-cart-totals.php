<?php
/**
 * Calculate cart subtotal functions.
 *
 * It's not possible to use the WooCommerce class. Built custom functions to calculate the cart subtotal.
 *
 * @since 2.1.0
 * @version 5.3.0 Persistent cart totals in session. Singleton pattern.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Cart_Totals Trait
 */
final class WC_BOGOF_Cart_Totals {

	/**
	 * Cart Items
	 *
	 * @var array
	 */
	protected $items = [];

	/**
	 * Items subtotal.
	 *
	 * @var float
	 */
	protected $items_subtotal = 0;

	/**
	 * Cart Discounts
	 *
	 * @var array
	 */
	protected $discounts = [];

	/**
	 * Discounts total.
	 *
	 * @var float
	 */
	protected $discounts_total = 0;

	/**
	 * Singleton instance
	 *
	 * @var WC_BOGOF_Cart_Totals
	 */
	private static $instance = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @return WC_BOGOF_Cart_Totals
	 */
	public static function instance() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'woocommerce_load_cart_from_session', [ $this, 'get_from_session' ] );
		add_action( 'woocommerce_before_calculate_totals', [ $this, 'calculate' ], 9998 );
		add_action( 'woocommerce_removed_coupon', [ $this, 'calculate' ], 9999 );
		add_action( 'wc_bogof_after_set_cart_item_discount', [ $this, 'calculate' ], 9999 );
		add_action( 'woocommerce_cart_emptied', [ $this, 'destroy_session' ] );
		add_action( 'woocommerce_cart_item_removed', [ $this, 'cart_item_removed' ], 9999, 0 );
	}

	/**
	 * Get data from session.
	 */
	public function get_from_session() {

		$data = WC()->session->get( strtolower( __CLASS__ ), [] );

		$this->items           = isset( $data['items'] ) && is_array( $data['items'] ) ? array_map( 'floatval', $data['items'] ) : [];
		$this->discounts       = isset( $data['discounts'] ) && is_array( $data['discounts'] ) ? array_map( 'floatval', $data['discounts'] ) : [];
		$this->items_subtotal  = array_sum( $this->items );
		$this->discounts_total = array_sum( $this->discounts );
	}

	/**
	 * Run all calculations methods.
	 */
	public function calculate() {

		if ( ! ( isset( WC()->cart ) && is_a( WC()->cart, 'WC_Cart' ) ) ) {
			return;
		}

		do_action( 'wc_bogof_before_calculate_totals' );

		$this->calculate_items_subtotal();
		$this->calculate_discounts();
		$this->set_session();

		do_action( 'wc_bogof_after_calculate_totals' );
	}

	/**
	 * Returns the cart subtotal.
	 *
	 * @return float
	 */
	public function get_subtotal() {
		return $this->items_subtotal - $this->discounts_total;
	}

	/**
	 * Returns the cart items subtotal.
	 *
	 * @return array
	 */
	public function get_cart_items_subtotal() {
		return $this->items;
	}

	/**
	 * Returns the cart items discount.
	 *
	 * @return array
	 */
	public function get_cart_items_discount() {
		return $this->discounts;
	}

	/**
	 * Put data to session.
	 */
	public function set_session() {
		WC()->session->set(
			strtolower( __CLASS__ ),
			[
				'items'     => $this->items,
				'discounts' => $this->discounts,
			]
		);
	}

	/**
	 * Destroy cart session data.
	 */
	public function destroy_session() {
		WC()->session->set(
			strtolower( __CLASS__ ),
			null
		);
	}

	/**
	 * Destroy session if cart is empty.
	 */
	public function cart_item_removed() {
		if ( WC()->cart->is_empty() ) {
			$this->destroy_session();
		}
	}

	/**
	 * Calculate items total.
	 */
	protected function calculate_items_subtotal() {

		$this->items = [];

		foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
			if ( WC_BOGOF_Cart::is_free_item( $cart_item ) ) {
				continue;
			}

			$this->items[ $key ] = wc_bogof_get_cart_product_price( $cart_item['data'], array( 'qty' => $cart_item['quantity'] ) );
		}

		$this->items_subtotal = array_sum( $this->items );
	}


	/**
	 * Calculate COUPON based discounts which change item prices.
	 *
	 * @uses  WC_Discounts class.
	 */
	protected function calculate_discounts() {
		$this->discounts       = [];
		$this->discounts_total = 0;

		$coupons = $this->get_coupons_from_cart();

		if ( empty( $coupons ) ) {
			return;
		}

		$price_include_tax = wc_prices_include_tax();
		$discounts         = new WC_Discounts( WC()->cart );

		$discounts->set_items_from_cart( WC()->cart );

		foreach ( $coupons as $coupon ) {
			$discounts->apply_coupon( $coupon, false );
		}

		$this->discounts = $discounts->get_discounts_by_item();

		if ( WC()->cart->display_prices_including_tax() !== wc_prices_include_tax() ) {
			$this->adjust_discount_taxes();
		}

		$this->discounts_total = array_sum( $this->discounts );
	}

	/**
	 * Adjusts discount based on taxes.
	 */
	protected function adjust_discount_taxes() {
		$discounts         = $this->discounts;
		$price_include_tax = wc_prices_include_tax();

		foreach ( $discounts as $item_key => $coupon_discount ) {

			$item = WC()->cart->get_cart_item( $item_key );

			if ( ! empty( $item ) && isset( $item['data'] ) && $item['data']->is_taxable() ) {

				$tax_rates = $this->get_product_tax_rates( $item['data'] );
				$item_tax  = wc_round_tax_total( array_sum( WC_Tax::calc_tax( $coupon_discount, $tax_rates, $price_include_tax ) ) );

				if ( $price_include_tax ) {
					$this->discounts[ $item_key ] -= $item_tax;
				} else {
					$this->discounts[ $item_key ] += $item_tax;
				}
			}
		}
	}

	/**
	 * Return array of coupon objects from the cart.
	 */
	protected function get_coupons_from_cart() {
		$coupons = WC()->cart->get_coupons();

		foreach ( $coupons as $coupon ) {
			switch ( $coupon->get_discount_type() ) {
				case 'fixed_product':
					$coupon->sort = 1;
					break;
				case 'percent':
					$coupon->sort = 2;
					break;
				case 'fixed_cart':
					$coupon->sort = 3;
					break;
				default:
					$coupon->sort = 0;
					break;
			}

			// Allow plugins to override the default order.
			$coupon->sort = apply_filters( 'woocommerce_coupon_sort', $coupon->sort, $coupon );
		}

		uasort( $coupons, array( $this, 'sort_coupons_callback' ) );
		return $coupons;
	}

	/**
	 * Sort coupons so discounts apply consistently across installs.
	 *
	 * In order of priority;
	 *  - sort param
	 *  - usage restriction
	 *  - coupon value
	 *  - ID
	 *
	 * @param WC_Coupon $a Coupon object.
	 * @param WC_Coupon $b Coupon object.
	 * @return int
	 */
	protected function sort_coupons_callback( $a, $b ) {
		if ( $a->sort === $b->sort ) {
			if ( $a->get_limit_usage_to_x_items() === $b->get_limit_usage_to_x_items() ) {
				if ( $a->get_amount() === $b->get_amount() ) {
					return $b->get_id() - $a->get_id();
				}
				return ( $a->get_amount() < $b->get_amount() ) ? -1 : 1;
			}
			return ( $a->get_limit_usage_to_x_items() < $b->get_limit_usage_to_x_items() ) ? -1 : 1;
		}
		return ( $a->sort < $b->sort ) ? -1 : 1;
	}

	/**
	 * Get tax rates for an product.
	 *
	 * @param  WC_Product $product Product to get tax rates for.
	 * @return array of taxes
	 */
	protected function get_product_tax_rates( $product ) {
		if ( ! wc_tax_enabled() ) {
			return array();
		}
		return WC_Tax::get_rates( $product->get_tax_class(), WC()->cart->get_customer() );
	}
}
