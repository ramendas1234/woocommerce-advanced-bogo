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
class WC_BOGOF_Condition_Product_Type extends WC_BOGOF_Condition_Taxonomy {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'product_type';
		$this->title = __( 'Product type', 'wc-buy-one-get-one-free' );
	}

	/**
	 * Returns the "value" metabox field options.
	 *
	 * @return array
	 */
	protected function get_metabox_field_options() {
		return wc_get_product_types();
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
		if ( isset( $cart_item['data'] ) && is_callable( [ $cart_item['data'], 'get_type' ] ) ) {
			$is_matching = in_array( $cart_item['data']->get_type(), $data, true );
			if ( ! $is_matching && $this->check_parent() && 'variation' === $cart_item['data']->get_type() ) {
				$is_matching = in_array( 'variable', $data, true );
			}
		}
		return $is_matching;
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

		$operator = $this->modifier_is( $data, 'not-in' ) ? 'NOT IN' : 'IN';
		$args     = [
			'taxonomy' => $this->get_taxonomy_name(),
			'field'    => 'slug',
			'terms'    => $data['value'],
			'operator' => $operator,
		];

		$tax_query = new WP_Tax_Query( [ $args ] );
		$clauses   = $tax_query->get_sql( $wpdb->posts, 'ID' );

		return "{$wpdb->posts}.ID {$operator} ( SELECT DISTINCT {$wpdb->posts}.ID FROM {$wpdb->posts} {$clauses['join']} WHERE 1=1 {$clauses['where']} )";
	}
}
