<?php
/**
 * Condition Composite_Products class.
 *
 * @since 4.0.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Product Class
 */
class WC_BOGOF_Condition_Composite_Products extends WC_BOGOF_Abstract_Condition {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id       = 'wc_composite_products';
		$this->title    = __( 'Composite Products', 'wc-buy-one-get-one-free' );
		$this->supports = array( '_applies_to' );
	}

	/**
	 * Evaluate condition field.
	 *
	 * @param array $data Condition field data.
	 * @param mixed $value Value to check.
	 * @return boolean
	 */
	public function check_condition( $data, $value = null ) {
		if ( ! is_array( $value ) ) {
			return false;
		}
		$is_container_cart_item  = wc_cp_is_composite_container_cart_item( $value );
		$is_composited_cart_item = wc_cp_is_composited_cart_item( $value );
		$is_cp_cart_item         = $is_composited_cart_item || $is_container_cart_item;

		if ( ! $is_cp_cart_item ) {
			return true;
		}

		return ( $this->modifier_is( $data, 'child' ) && $is_composited_cart_item ) ||
			( $this->modifier_is( $data, 'parent' ) && $is_container_cart_item );
	}

	/**
	 * Returns a key => title array of modifiers.
	 *
	 * @return array
	 */
	public function get_modifiers() {
		return array(
			'parent' => __( 'Is the parent/container', 'wc-buy-one-get-one-free' ),
			'child'  => __( 'Is the component/children', 'wc-buy-one-get-one-free' ),
		);
	}

	/**
	 * Returns an array with the proprerties of the metabox field.
	 *
	 * @return array
	 */
	public function get_value_metabox_field() {
		return array();
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
}
