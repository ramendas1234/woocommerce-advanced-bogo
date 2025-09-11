<?php
/**
 * Global Condition Taxonomy class.
 *
 * @since 4.0.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Taxonomy Class
 */
class WC_BOGOF_Condition_Taxonomy extends WC_BOGOF_Abstract_Condition_Product_Base {

	/**
	 * Constructor.
	 *
	 * @param string $taxonomy_name Taxonomy name.
	 * @param string $taxonomy_label Taxonomy label.
	 */
	public function __construct( $taxonomy_name = '', $taxonomy_label = '' ) {
		$this->id    = $taxonomy_name;
		$this->title = $taxonomy_label;
	}

	/**
	 * Returns the taxonomy name.
	 *
	 * @return string|array
	 */
	protected function get_taxonomy_name() {
		return $this->id;
	}

	/**
	 * Checks if a value exists in an array.
	 *
	 * @param int   $product_id Product ID to check.
	 * @param array $haystack The array.
	 * @return bool
	 */
	protected function in_array( $product_id, $haystack ) {
		$key       = md5( $product_id . implode( '-', $haystack ) . WC_Cache_Helper::get_transient_version( 'product_query' ) );
		$cache_key = $this->get_cache_key( "in_array_{$key}" );
		$value     = $this->cache_get( $cache_key );

		if ( false === $value ) {
			$posts = get_posts(
				[
					'posts_per_page' => 1,
					'post__in'       => [ $product_id ],
					'post_type'      => [ 'product', 'product_variation' ],
					'tax_query'      => [
						[
							'taxonomy' => $this->get_taxonomy_name(),
							'field'    => 'term_id',
							'terms'    => $haystack,
						],
					],
				]
			);

			$value = wc_bool_to_string( count( $posts ) > 0 );

			$this->cache_set( $cache_key, $value );
		}

		return wc_string_to_bool( $value );
	}

	/**
	 * Returns the "value" metabox field options.
	 *
	 * @return array
	 */
	protected function get_metabox_field_options() {
		$raw_terms = get_terms(
			array(
				'taxonomy'   => $this->get_taxonomy_name(),
				'hide_empty' => 0,
			)
		);
		$terms     = array();
		$options   = array();

		foreach ( $raw_terms as $term ) {
			$terms[ $term->term_id ] = array(
				'name'   => $term->name,
				'parent' => isset( $term->parent ) ? $term->parent : 0,
			);
		}

		foreach ( $terms as $term_id => $term ) {
			$options[ $term_id ] = $this->get_metabox_field_option_name( $term_id, $terms );
		}
		asort( $options, SORT_NATURAL | SORT_FLAG_CASE );

		return $options;
	}

	/**
	 * Retruns the term name for the metabox field.
	 *
	 * @param int   $term_id Term ID.
	 * @param array $terms Array of terms.
	 */
	protected function get_metabox_field_option_name( $term_id, $terms ) {

		if ( empty( $terms[ $term_id ]['parent'] ) ) {
			return $terms[ $term_id ]['name'];
		} else {
			return $this->get_metabox_field_option_name( $terms[ $term_id ]['parent'], $terms ) . '&nbsp;>&nbsp;' . $terms[ $term_id ]['name'];
		}
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
		$operator          = $this->modifier_is( $data, 'not-in' ) ? 'NOT IN' : 'IN';
		$term_taxonomy_ids = array_map( 'absint', $data['value'] );

		$args = [
			'taxonomy' => $this->get_taxonomy_name(),
			'field'    => 'term_id',
			'terms'    => $term_taxonomy_ids,
			'operator' => $operator,
		];

		$tax_query = new WP_Tax_Query( [ $args ] );
		$clauses   = $tax_query->get_sql( $wpdb->posts, 'ID' );

		return "{$wpdb->posts}.ID {$operator} ( SELECT DISTINCT {$wpdb->posts}.ID FROM {$wpdb->posts} {$clauses['join']} WHERE 1=1 {$clauses['where']} )";
	}

	/**
	 * Get formatted values.
	 *
	 * @see WC_BOGOF_Abstract_Condition_Product_Base::to_string
	 * @param array $values Values to formatted.
	 * @return array
	 */
	protected function get_formatted_values( $values ) {
		$terms            = $this->get_metabox_field_options();
		$formatted_values = array();
		foreach ( $values as $term_id ) {
			if ( ! empty( $terms[ $term_id ] ) ) {
				$formatted_values[] = $terms[ $term_id ];
			}
		}
		return $formatted_values;
	}
}
