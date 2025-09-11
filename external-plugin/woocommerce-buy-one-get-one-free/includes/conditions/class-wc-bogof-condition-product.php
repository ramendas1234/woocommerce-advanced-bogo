<?php
/**
 * Condition Product class.
 *
 * @since 3.0.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Product Class
 */
class WC_BOGOF_Condition_Product extends WC_BOGOF_Abstract_Condition_Product_Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'product';
		$this->title = __( 'Product', 'wc-buy-one-get-one-free' );
	}

	/**
	 * Checks if a value exists in an array.
	 *
	 * @param int   $product_id Product ID to check.
	 * @param array $haystack The array.
	 * @return bool
	 */
	protected function in_array( $product_id, $haystack ) {
		return in_array( $product_id, $haystack ); // phpcs:ignore WordPress.PHP.StrictInArray
	}

	/**
	 * Return the WHERE clause that returns the products that meet the condition.
	 *
	 * @param array $data Condition field data.
	 * @return string
	 */
	public function get_where_clause( $data ) {
		global $wpdb;
		// Empty conditions always return ''.
		if ( empty( $data['value'] ) || ! is_array( $data['value'] ) ) {
			return false;
		}
		$product_ids = array_map( 'absint', $data['value'] );
		$parents     = array();
		foreach ( $product_ids as $product_id ) {
			if ( 'product_variation' === get_post_type( $product_id ) ) {
				$parents[] = wp_get_post_parent_id( $product_id );
			}
		}
		$product_ids = array_merge( $product_ids, $parents );
		$operator    = $this->modifier_is( $data, 'not-in' ) ? 'NOT IN' : 'IN';

		return $wpdb->posts . '.ID ' . $operator . ' (' . implode( ',', $product_ids ) . ')';
	}

	/**
	 * Get formatted values.
	 *
	 * @param array $values Values to formatted.
	 * @return array
	 */
	protected function get_formatted_values( $values ) {
		$product_ids   = array_map( 'absint', $values );
		$product_names = array();
		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$product_names[] = wp_strip_all_tags( $product->get_formatted_name() );
			}
		}
		return $product_names;
	}


	/**
	 * Returns an array with the proprerties of the metabox field.
	 *
	 * @return array
	 */
	public function get_value_metabox_field() {
		return array(
			'type' => 'search-product',
		);
	}
}
