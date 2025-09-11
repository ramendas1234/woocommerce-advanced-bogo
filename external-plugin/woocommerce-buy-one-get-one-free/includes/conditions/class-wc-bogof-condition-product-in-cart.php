<?php
/**
 * Condition product is in cart class.
 *
 * @since 3.3.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Product_In_Cart Class
 */
class WC_BOGOF_Condition_Product_In_Cart extends WC_BOGOF_Abstract_Condition {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id       = 'product_in_cart';
		$this->title    = __( 'Product is in cart', 'wc-buy-one-get-one-free' );
		$this->supports = array( '_gift_products' );
	}

	/**
	 * Returns the ID of the products in the cart.
	 *
	 * @return array
	 */
	protected function get_cart_product_ids() {
		$ids = [];

		foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
			if ( WC_BOGOF_Cart::is_free_item( $cart_item ) || ! ( isset( $cart_item['data'] ) && is_callable( [ $cart_item['data'], 'get_id' ] ) ) ) {
				continue;
			}
			$ids[] = $cart_item['data']->get_id();
		}

		return $ids;
	}

	/**
	 * Is the product in the cart?
	 *
	 * @param int  $product_id The Product ID to check.
	 * @param bool $check_parent When true, the function returns true for any variation.
	 * @return bool
	 */
	protected function is_in_cart( $product_id, $check_parent = false ) {
		$in_cart   = false;
		$parent_id = $check_parent && 'product_variation' === get_post_type( $product_id ) ? wp_get_post_parent_id( $product_id ) : false;

		foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
			if ( WC_BOGOF_Cart::is_free_item( $cart_item ) || ! ( isset( $cart_item['data'] ) && is_callable( [ $cart_item['data'], 'get_id' ] ) ) ) {
				continue;
			}

			$in_cart = $cart_item['data']->get_id() === $product_id || $cart_item['data']->get_parent_id() === $parent_id;

			if ( $in_cart ) {
				break;
			}
		}

		return $in_cart;
	}

	/**
	 * Returns a key => title array of modifiers.
	 *
	 * @return array
	 */
	public function get_modifiers() {
		return array(
			'yes' => __( 'Yes', 'wc-buy-one-get-one-free' ),
			'no'  => __( 'No', 'wc-buy-one-get-one-free' ),
		);
	}

	/**
	 * Returns an array with the proprerties of the metabox field.
	 *
	 * @return array
	 */
	public function get_value_metabox_field() {
		return array(
			'type'       => 'true-false',
			'message'    => __( 'Include all product variations', 'wc-buy-one-get-one-free' ),
			'input-info' => __( 'If the product in the cart is a variation, the condition will include all variations of the same parent.', 'wc-buy-one-get-one-free' ),
		);
	}

	/**
	 * Is the condition data empty?
	 *
	 * @param array $data Array that contains the condition data.
	 * @return bool
	 */
	public function is_empty( $data ) {
		return empty( $data['type'] );
	}

	/**
	 * Evaluate condition field.
	 *
	 * @param array $data Condition field data.
	 * @param mixed $value Value to check.
	 * @return boolean
	 */
	public function check_condition( $data, $value = null ) {
		$product_id = false;
		$check      = false;

		if ( is_numeric( $value ) ) {
			$product_id = absint( $value );
		} elseif ( isset( $value['data'] ) && is_callable( [ $value['data'], 'get_id' ] ) ) {
			$product_id = $value['data']->get_id();
		}

		if ( $product_id ) {

			$check = $this->is_in_cart(
				$product_id,
				( isset( $data['value'] ) && wc_string_to_bool( $data['value'] ) )
			);

			if ( $this->modifier_is( $data, 'no' ) ) {
				$check = ! $check;
			}
		}

		return $check;
	}

	/**
	 * Return the WHERE clause that returns the products that meet the condition.
	 *
	 * @param array $data Condition field data.
	 * @return string
	 */
	public function get_where_clause( $data ) {
		global $wpdb;

		$cart_product_ids = $this->get_cart_product_ids();
		$check_parent     = isset( $data['value'] ) && wc_string_to_bool( $data['value'] );
		$product_ids      = [];

		if ( empty( $cart_product_ids ) ) {
			// This should not happen never.
			return '1>1';
		}

		foreach ( $cart_product_ids as $product_id ) {
			if ( 'product_variation' !== get_post_type( $product_id ) ) {
				$product_ids[] = $product_id;
			} elseif ( $this->modifier_is( $data, 'yes' ) || $check_parent ) {
				$product_ids[] = wp_get_post_parent_id( $product_id );
			}
		}

		$operator = $this->modifier_is( $data, 'no' ) ? 'NOT IN' : 'IN';

		return $wpdb->posts . '.ID ' . $operator . ' (' . implode( ',', $product_ids ) . ')';
	}
}
