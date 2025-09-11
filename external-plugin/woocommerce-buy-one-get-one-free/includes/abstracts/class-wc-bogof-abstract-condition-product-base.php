<?php
/**
 * Abstract Condition Product Base class.
 *
 * @since 4.0.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Base Class
 */
abstract class WC_BOGOF_Abstract_Condition_Product_Base extends WC_BOGOF_Abstract_Condition {
	use WC_BOGOF_Condition_Array;

	/**
	 * Evaluate a condition field.
	 *
	 * @param array $data   Condition field data.
	 * @param mixed $value  Value to check.
	 * @return boolean
	 */
	public function check_condition( $data, $value = null ) {
		// Empty conditions always return false (not evaluated).
		if ( empty( $data['value'] ) || ! is_array( $data['value'] ) ) {
			return false;
		}

		if ( is_numeric( $value ) ) {
			$is_matching = $this->product_in( $value, $data['value'] );
		} else {
			$is_matching = $this->check_cart_item( $value, $data['value'] );
		}

		if ( $this->modifier_is( $data, 'not-in' ) ) {
			$is_matching = ! $is_matching;
		}

		return $is_matching;
	}

	/**
	 * Check if the product is in the array, is not check the parent.
	 *
	 * @param int   $product_id Product ID to check.
	 * @param array $haystack The array.
	 */
	protected function product_in( $product_id, $haystack ) {
		$product_in = $this->in_array( $product_id, $haystack );
		if ( ! $product_in && $this->check_parent() && 'product_variation' === get_post_type( $product_id ) ) {
			$product_in = $this->product_in( wp_get_post_parent_id( $product_id ), $haystack );
		}
		return $product_in;
	}

	/**
	 * Evaluate if a cart item meets the condition.
	 *
	 * @param array $cart_item Cart item to check.
	 * @param array $data Condition field data.
	 * @return boolean
	 */
	protected function check_cart_item( $cart_item, $data ) {
		$is_matching = false;
		if ( isset( $cart_item['data'] ) && is_callable( array( $cart_item['data'], 'get_id' ) ) ) {
			$is_matching = $this->product_in( $cart_item['data']->get_id(), $data );
		}
		return $is_matching;
	}

	/**
	 * For product variations, check also if parent meet the condition.
	 *
	 * @return bool
	 */
	protected function check_parent() {
		return true;
	}

	/**
	 * Checks if a value exists in an array.
	 *
	 * @param int   $product_id Product ID to check.
	 * @param array $haystack The array.
	 * @return bool
	 */
	abstract protected function in_array( $product_id, $haystack );

	/**
	 * Get formatted values.
	 *
	 * @param array $values Values to formatted.
	 * @return array
	 */
	abstract protected function get_formatted_values( $values );

}
