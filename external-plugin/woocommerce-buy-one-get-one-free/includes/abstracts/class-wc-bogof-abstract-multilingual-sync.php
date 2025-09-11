<?php
/**
 * Sync a BOGO rule with a translation.
 *
 * @since 3.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Multilingual_Sync Class
 */
abstract class WC_BOGOF_Abstract_Multilingual_Sync {

	/**
	 * Original Post ID.
	 *
	 * @var int
	 */
	protected $original_id;

	/**
	 * Translate Post ID.
	 *
	 * @var int
	 */
	protected $tr_id;

	/**
	 * Original Post lang.
	 *
	 * @var string
	 */
	protected $original_lang;

	/**
	 * Translate Post lang.
	 *
	 * @var string
	 */
	protected $tr_lang;

	/**
	 * Constructor.
	 *
	 * @param int    $original_id Original ID.
	 * @param int    $tr_id Translate ID.
	 * @param string $original_lang Language to translate.
	 * @param string $tr_lang Original language.
	 */
	public function __construct( $original_id, $tr_id, $original_lang, $tr_lang ) {
		$this->original_id   = $original_id;
		$this->tr_id         = $tr_id;
		$this->original_lang = $original_lang;
		$this->tr_lang       = $tr_lang;
	}

	/**
	 * Translate a object ID.
	 *
	 * @param int    $object_id The ID of the post type (post, page, attachment, custom post) or taxonomy term.
	 * @param string $type The type of element the ID belongs to.
	 * @return int
	 */
	abstract protected function translate_object_id( $object_id, $type );

	/**
	 * Translate the rule data.
	 */
	public function translate() {
		if ( $this->original_id === $this->tr_id ) {
			return;
		}

		$rule    = wc_bogof_get_rule( $this->original_id );
		$tr_rule = wc_bogof_get_rule( $this->tr_id );

		if ( $rule && $tr_rule ) {

			// Translate the free product ID.
			if ( $rule->get_free_product_id() ) {

				$type = get_post_type( $rule->get_free_product_id() );

				$tr_product_id = $this->translate_object_id( $rule->get_free_product_id(), $type );

				if ( $tr_product_id ) {
					$tr_rule->set_free_product_id( $tr_product_id );
				}
			}

			// Translate conditions.
			foreach ( array( 'applies_to', 'gift_products' ) as $prop ) {
				$getter = 'get_' . $prop;
				$setter = 'set_' . $prop;

				$value    = $rule->{$getter}();
				$tr_value = $value;

				if ( ! is_array( $value ) ) {
					continue;
				}

				foreach ( $value as $id_group => $group ) {
					foreach ( $group as $id_condition => $condition ) {
						if ( empty( $condition['type'] ) ) {
							continue;
						}

						$tr_function = "translate_{$condition['type']}_condition";

						if ( is_callable( array( $this, $tr_function ) ) ) {
							$tr_value[ $id_group ][ $id_condition ] = call_user_func( array( $this, $tr_function ), $condition );
						}
					}
				}

				$tr_rule->{$setter}( $tr_value );
			}

			// Save the rule.
			$tr_rule->save();
		}
	}

	/**
	 * Translate product condition.
	 *
	 * @param array $data Condition data.
	 * @return array
	 */
	protected function translate_product_condition( $data ) {
		$tr_data          = $data;
		$tr_data['value'] = array();

		if ( is_array( $data['value'] ) ) {

			foreach ( $data['value'] as $product_id ) {

				$type = get_post_type( $product_id );

				$tr_product_id = $this->translate_object_id( $product_id, $type );

				if ( $tr_product_id ) {
					$tr_data['value'][] = $tr_product_id;
				}
			}
		}

		return $tr_data;
	}

	/**
	 * Translate product category condition.
	 *
	 * @param array $data Condition data.
	 * @return array
	 */
	protected function translate_product_cat_condition( $data ) {
		$tr_data          = $data;
		$tr_data['value'] = array();
		$type             = $data['type'];

		if ( is_array( $data['value'] ) ) {

			foreach ( $data['value'] as $term_id ) {

				$tr_term_id = $this->translate_object_id( $term_id, $type );

				if ( $tr_term_id ) {
					$tr_data['value'][] = $tr_term_id;
				}
			}
		}

		return $tr_data;
	}

	/**
	 * Translate product tag condition.
	 *
	 * @param array $data Condition data.
	 * @return array
	 */
	protected function translate_product_tag_condition( $data ) {
		return $this->translate_product_cat_condition( $data );
	}

	/**
	 * Translate Variation attribute condition.
	 *
	 * @param array $data Condition data.
	 * @return array
	 */
	protected function translate_variation_attribute_condition( $data ) {
		$tr_data          = $data;
		$tr_data['value'] = array();

		if ( is_array( $data['value'] ) ) {

			foreach ( $data['value'] as $attribute ) {
				$meta = is_string( $attribute ) ? explode( ':', $attribute ) : array();
				if ( 2 > count( $meta ) ) {
					continue;
				}
				$taxonomy = $meta[0];
				$slug     = $meta[1];

				$term = $this->get_term_by_slug( $slug, $taxonomy );

				if ( $term ) {

					$tr_term_id = $this->translate_object_id( $term->term_id, $taxonomy );

					$tr_term = is_numeric( $tr_term_id ) ? $this->get_term( $tr_term_id ) : false;

					if ( is_a( $tr_term, 'WP_Term' ) ) {
						$tr_data['value'][] = $tr_term->taxonomy . ':' . $tr_term->slug;
					}
				}
			}
		}

		return $tr_data;
	}

	/**
	 * Returns a terms by slug filtering by lang.
	 *
	 * @param string $slug Term slug.
	 * @param string $taxonomy Taxonomy slug.
	 */
	protected function get_term_by_slug( $slug, $taxonomy ) {
		$args = array(
			'slug'                   => $slug,
			'get'                    => 'all',
			'number'                 => 1,
			'taxonomy'               => $taxonomy,
			'update_term_meta_cache' => false,
			'orderby'                => 'none',
			'suppress_filter'        => true,
			'lang'                   => $this->original_lang,
		);

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return false;
		}

		return array_shift( $terms );
	}

	/**
	 * Returns a terms without lang filter.
	 *
	 * @param int $term_id Term ID.
	 * @return WP_Term
	 */
	protected function get_term( $term_id ) {
		return get_term( $term_id );
	}
}
