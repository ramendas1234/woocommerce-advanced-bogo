<?php
/**
 * Condition Variation Attribute class.
 *
 * @since 3.0.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Attribute Class
 */
class WC_BOGOF_Condition_Variation_Attribute extends WC_BOGOF_Abstract_Condition {
	use WC_BOGOF_Condition_Array;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'variation_attribute';
		$this->title = __( 'Variation attribute', 'wc-buy-one-get-one-free' );
	}

	/**
	 * Returns the custom product attributes.
	 *
	 * @return array
	 */
	protected function get_custom_product_attributes() {
		global $wpdb;

		$cache_key = $this->get_cache_key( 'custom_attributes' );

		if ( wp_using_ext_object_cache() ) {
			$cache_key .= WC_Cache_Helper::get_transient_version( 'product' );
		}

		$cache_value = $this->cache_get( $cache_key );

		if ( false !== $cache_value && is_array( $cache_value ) ) {
			return $cache_value;
		}

		$options           = array();
		$variable_term     = get_term_by( 'slug', 'variable', 'product_type' );
		$custom_attributes = array();
		$raw_attributes    = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT _product_attributes.meta_value
				FROM {$wpdb->posts} as posts
				INNER JOIN {$wpdb->postmeta} as _product_attributes ON posts.ID = _product_attributes.post_id
					AND _product_attributes.meta_key = %s
				INNER JOIN wp_term_relationships as term_relationships ON posts.ID = term_relationships.object_id
					AND term_relationships.term_taxonomy_id = %d
				WHERE posts.post_type = %s AND post_status = %s AND _product_attributes.meta_value != %s",
				'_product_attributes',
				$variable_term->term_taxonomy_id,
				'product',
				'publish',
				'a:0:{}'
			)
		);

		foreach ( $raw_attributes as $raw_attribute ) {

			$product_attributes = maybe_unserialize( $raw_attribute );

			if ( ! is_array( $product_attributes ) ) {
				continue;
			}

			foreach ( $product_attributes as $attribute_key => $attribute_data ) {
				if ( ! empty( $attribute_data['is_taxonomy'] ) || empty( $attribute_data['is_variation'] ) || ! isset( $attribute_data['value'], $attribute_data['name'] ) ) {
					continue;
				}

				foreach ( wc_get_text_attributes( $attribute_data['value'] ) as $label ) {
					$term = (object) array(
						'taxonomy' => $attribute_key,
						'slug'     => $label,
						'name'     => $label,
					);

					$terms[ $term->slug ] = $term;
				}

				if ( ! empty( $terms ) ) {
					$options += $this->parse_field_options( $terms, $attribute_data['name'] );
				}
			}
		}

		$options = array_unique( $options );

		$this->cache_set( $cache_key, $options );

		return $options;
	}

	/**
	 * Returns the taxonomy attributes.
	 *
	 * @return array.
	 */
	protected function get_taxonomy_attributes() {
		$cache_key   = $this->get_cache_key( 'taxonomy_attributes', 'woocommerce-attributes' );
		$cache_value = $this->cache_get( $cache_key );

		if ( false !== $cache_value && is_array( $cache_value ) ) {
			return $cache_value;
		}

		$options              = array();
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		if ( $attribute_taxonomies ) {
			foreach ( $attribute_taxonomies as $tax ) {
				$taxonomy_name = wc_attribute_taxonomy_name( $tax->attribute_name );
				$terms         = get_terms( $taxonomy_name, array( 'hide_empty' => 0 ) );
				$options      += $this->parse_field_options( $terms, $tax->attribute_label );
			}
		}

		$this->cache_set( $cache_key, $options );

		return $options;
	}

	/**
	 * Returns the "value" metabox field options.
	 *
	 * @return array
	 */
	protected function get_metabox_field_options() {
		$options = $this->get_taxonomy_attributes();
		if ( 'yes' === get_option( 'wc_bogof_include_custom_attributes', 'no' ) ) {
			$options += $this->get_custom_product_attributes();
		}

		return $options;
	}

	/**
	 * Generate an array of options from the terms array.
	 *
	 * @param WP_Term $terms Array of WP_Term Object.
	 * @param string  $attribute_label Attribute label.
	 * @return array
	 */
	protected function parse_field_options( $terms, $attribute_label ) {
		$options = array();
		foreach ( $terms as $term ) {
			$key   = "{$term->taxonomy}:{$term->slug}";
			$value = "{$attribute_label}: {$term->name}";

			$options[ $key ] = $value;
		}
		return $options;
	}

	/**
	 * Build product terms to evaluate conditions.
	 *
	 * @param array $variation_attributtes Array of product attributes.
	 * @return array
	 */
	protected function buid_product_terms_from_attributes( $variation_attributtes ) {
		$terms = array();
		foreach ( $variation_attributtes as $key => $value ) {
			$taxonomy = substr( $key, 0, 10 ) === 'attribute_' ? substr( $key, 10 ) : $key;
			$terms[]  = "{$taxonomy}:{$value}";
		}
		return $terms;
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
		if ( ! empty( $cart_item['variation_id'] ) ) {
			$terms       = $this->get_product_terms( absint( $cart_item['variation_id'] ) );
			$is_matching = wc_bogof_in_array_intersect( $data, $terms );
		}
		return $is_matching;
	}

	/**
	 * Return the product taxonomy terms IDs.
	 *
	 * @param int $product_id Product ID.
	 * @return array
	 */
	protected function get_product_terms( $product_id ) {
		$cache_key   = $this->get_cache_key( "terms_{$product_id}", "product_{$product_id}" );
		$cache_value = $this->cache_get( $cache_key );

		if ( is_array( $cache_value ) ) {
			return $cache_value;
		}

		$terms = array();
		if ( 'product_variation' === get_post_type( $product_id ) ) {
			$variation_attributtes = wc_get_product_variation_attributes( $product_id );
			$terms                 = $this->buid_product_terms_from_attributes( $variation_attributtes );
		}
		$this->cache_set( $cache_key, $terms );

		return $terms;
	}

	/**
	 * Evaluate a condition field.
	 *
	 * @param array $data   Condition field data.
	 * @param mixed $value  Value to check.
	 * @return boolean
	 */
	public function check_condition( $data, $value = null ) {
		$product_id = false;

		if ( is_numeric( $value ) ) {
			$product_id = absint( $value );
		} elseif ( ! empty( $value['variation_id'] ) ) {
			// Validate cart item.
			$product_id = absint( $value['variation_id'] );
		}

		if ( ! $product_id ) {
			return false;
		}

		$data  = $this->sanitize( $data );
		$terms = $this->get_product_terms( $product_id );

		return $this->validate( $terms, $data['value'], $data['modifier'] );
	}

	/**
	 * Returns the WHERE clause that filters the products that meet the condition.
	 *
	 * @param array $data Condition field data.
	 * @return string
	 */
	public function get_where_clause( $data ) {
		global $wpdb;

		// Empty conditions always return false.
		if ( empty( $data['value'] ) || ! is_array( $data['value'] ) ) {
			return false;
		}

		if ( $this->modifier_is( $data, 'not-in' ) ) {
			/**
			 * We can't exclude the product based on their children. Delay the check to the gift loop.
			 *
			 * @see WC_BOGOF_Gifts_Loop::get_products
			 */
			return '1=1';
		}

		$args    = [];
		$clauses = false;

		foreach ( $data['value'] as $attribute ) {
			$meta = explode( ':', $attribute );
			if ( 2 > count( $meta ) ) {
				continue;
			}

			$args[] = [
				'key'     => 'attribute_' . $meta[0],
				'value'   => trim( $meta[1] ),
				'compare' => '=',
			];
		}

		if ( count( $args ) ) {
			$args['relation'] = 'OR';

			$meta_query = new WP_Meta_Query( $args );
			$clauses    = $meta_query->get_sql( 'post', $wpdb->posts, 'ID' );
		}

		if ( count( $clauses ) ) {
			$where = "{$wpdb->posts}.ID IN ( SELECT DISTINCT {$wpdb->posts}.post_parent FROM {$wpdb->posts} {$clauses['join']} WHERE 1=1 {$clauses['where']} )";
		} else {
			$where = false;
		}

		return $where;
	}

	/**
	 * Sanitize a condition data array.
	 *
	 * @since 3.3.2
	 * @param array $data Array that contains the condition data.
	 * @return bool
	 */
	public function sanitize( $data ) {
		$data             = is_array( $data ) ? $data : [];
		$data['value']    = empty( $data['value'] ) || ! is_array( $data['value'] ) ? [] : array_intersect( $data['value'], array_keys( $this->get_metabox_field_options() ) );
		$data['modifier'] = sanitize_text_field( ( isset( $data['modifier'] ) ? $data['modifier'] : '' ) );
		return $data;
	}
}
