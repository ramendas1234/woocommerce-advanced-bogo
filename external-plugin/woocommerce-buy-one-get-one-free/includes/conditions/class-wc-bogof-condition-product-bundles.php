<?php
/**
 * Condition Product Bundles class.
 *
 * @since 4.0.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Product Class
 */
class WC_BOGOF_Condition_Product_Bundles extends WC_BOGOF_Abstract_Condition {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id       = 'wc_bundles';
		$this->title    = __( 'Product Bundles', 'wc-buy-one-get-one-free' );
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

		if ( empty( $value['bundled_item_id'] ) && empty( $value['bundled_items'] ) ) {
			return true;
		}

		return ( $this->modifier_is( $data, 'child' ) && ! empty( $value['bundled_item_id'] ) ) ||
				( ! $this->modifier_is( $data, 'child' ) && ! empty( $value['bundled_items'] ) );
	}

	/**
	 * Returns a key => title array of modifiers.
	 *
	 * @return array
	 */
	public function get_modifiers() {
		return array(
			'parent' => __( 'Is the parent product', 'wc-buy-one-get-one-free' ),
			'child'  => __( 'Is bundled (child) item', 'wc-buy-one-get-one-free' ),
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
