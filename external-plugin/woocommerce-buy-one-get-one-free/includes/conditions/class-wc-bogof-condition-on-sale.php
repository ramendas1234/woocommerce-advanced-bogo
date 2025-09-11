<?php
/**
 * Condition product is on sale.
 *
 * @since 4.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_On_Sale Class
 */
class WC_BOGOF_Condition_On_Sale extends WC_BOGOF_Abstract_Condition {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'product_on_sale';
		$this->title = __( 'Product is on-sale', 'wc-buy-one-get-one-free' );
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
			$check = in_array( $product_id, wc_get_product_ids_on_sale(), true );
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

		$product_ids = wc_get_product_ids_on_sale();
		$clause      = '';

		if ( empty( $product_ids ) ) {
			$clause = $this->modifier_is( $data, 'no' ) ? '1=1' : '1>1';
		} else {

			$operator = $this->modifier_is( $data, 'no' ) ? 'NOT IN' : 'IN';

			$clause = $wpdb->posts . '.ID ' . $operator . ' (' . implode( ',', $product_ids ) . ')';
		}

		return $clause;
	}
}
