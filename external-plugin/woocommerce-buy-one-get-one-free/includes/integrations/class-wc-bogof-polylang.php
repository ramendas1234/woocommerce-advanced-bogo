<?php
/**
 * Buy One Get One Free Polylang compatibilty.
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_BOGOF_Abstract_Multilingual_Sync' ) ) {
	require dirname( WC_BOGOF_PLUGIN_FILE ) . '/includes/abstracts/class-wc-bogof-abstract-multilingual-sync.php';
}

/**
 * WC_BOGOF_Polylang Class
 */
class WC_BOGOF_Polylang extends WC_BOGOF_Abstract_Multilingual_Sync {

	/**
	 * Translate a object ID.
	 *
	 * @param int    $object_id The ID of the post type (post, page, attachment, custom post) or taxonomy term.
	 * @param string $type The type of element the ID belongs to.
	 * @return int
	 */
	protected function translate_object_id( $object_id, $type ) {

		if ( in_array( $type, array( 'product', 'product_variation' ), true ) ) {

			$tr_object_id = pll_get_post( $object_id, $this->tr_lang );

		} else {
			$tr_object_id = pll_get_term( $object_id, $this->tr_lang );

		}
		return $tr_object_id;
	}


	/**
	 * Init hooks
	 */
	public static function init() {
		if ( ! ( function_exists( 'pll_get_post' ) && function_exists( 'pll_set_post_language' ) && function_exists( 'pll_save_post_translations' ) && function_exists( 'pll_get_post_translations' ) ) ) {
			return false;
		}

		add_action( 'pll_save_post', array( __CLASS__, 'sync_bogof_data' ), 20, 3 );
		add_action( 'wc_bogof_after_duplicate_rule', array( __CLASS__, 'after_duplicate_rule' ) );
		add_action( 'wc_bogof_before_get_all_rules', array( __CLASS__, 'before_get_all_rules' ) );
		add_filter( 'woocommerce_json_search_found_products', array( __CLASS__, 'json_search_found_products' ) );
		add_filter( 'option_wc_bogof_cyg_page_id', array( __CLASS__, 'choose_your_gift_page_id' ) );
		add_filter( 'pllwc_translate_cart_item', array( __CLASS__, 'translate_cart_item' ), 100 );
		add_filter( 'wc_bogof_rule_get_usage_count', array( __CLASS__, 'rule_get_usage_count' ), 10, 3 );
		add_filter( 'wc_bogof_should_add_cart_rule', array( __CLASS__, 'should_add_cart_rule' ), 10, 3 );
		add_filter( 'wc_bogof_customizer_preview_rule', array( __CLASS__, 'customizer_preview_rule' ), 10000, 2 );

		// Hyyan WooCommerce Polylang Integration.
		add_filter( 'woo-poly.Cart.switchedItem', array( __CLASS__, 'woo_poly_cart_switched_item' ), 10, 2 );
	}

	/**
	 * BOGO data synchronization between languages.
	 *
	 * @param int     $post_id      Post id.
	 * @param WP_Post $post         Post object.
	 * @param int[]   $translations The list of translations post ids.
	 */
	public static function sync_bogof_data( $post_id, $post, $translations ) {
		static $avoid_recursion = false;

		if ( $avoid_recursion || 'shop_bogof_rule' !== get_post_type( $post ) || in_array( get_post_status( $post ), array( 'auto-draft', 'draft' ), true ) ) {
			return;
		}

		$avoid_recursion = true;

		$post_status = get_post_status( $post );

		$translations = array_merge( array_fill_keys( pll_languages_list(), false ), $translations ); // Fill translations with all languages.
		$translations = array_diff( $translations, array( $post_id ) ); // Just remove this post from the list.

		$lang = pll_get_post_language( $post_id );

		foreach ( $translations as $tr_lang => $tr_id ) {

			if ( 'trash' === $post_status ) {
				if ( ! ( isset( $_REQUEST['action'] ) && 'trash' === $_REQUEST['action'] && isset( $_REQUEST['post'] ) && is_array( $_REQUEST['post'] ) && in_array( $tr_id, $_REQUEST['post'] ) ) ) { // phpcs:ignore
					// If not in bulk delete, delete the translate.
					$tr_rule = $tr_id ? wc_bogof_get_rule( $tr_id ) : false;
					if ( $tr_rule ) {
						$tr_rule->delete();
					}
				}
			} else {
				// Sycnc data.

				if ( ! $tr_id ) {
					// Make a duplicate.
					$tr_id = self::make_duplicate( $post_id, $tr_lang );
				}

				if ( $tr_id ) {
					// Translate the rule.
					$sync = new self( $post_id, $tr_id, $lang, $tr_lang );
					$sync->translate();
				}
			}
		}

		$avoid_recursion = false;
	}

	/**
	 * Duplicate the post in a new language.
	 *
	 * @param int    $post_id    Post id of the source post.
	 * @param string $lang       Target language slug.
	 * @return int Id of the target post.
	 */
	private static function make_duplicate( $post_id, $lang ) {

		$rule    = wc_bogof_get_rule( $post_id );
		$newrule = false;
		$tr_id   = false;
		if ( $rule ) {

			$tr_rule = new WC_BOGOF_Rule();
			$data    = $rule->get_data();
			unset( $data['id'], $data['title'] );

			$tr_rule->set_props( $data );

			$tr_rule->set_title( $rule->get_title() . ' (' . $lang . ')' );
			$tr_rule->save();

			$tr_id = $tr_rule->get_id();

			pll_set_post_language( $tr_id, $lang );

			$translations          = pll_get_post_translations( $post_id );
			$translations[ $lang ] = $tr_id;

			pll_save_post_translations( $translations );
		}

		return $tr_id;
	}

	/**
	 * Sync translation after duplicate rule
	 *
	 * @param int $post_id Post ID.
	 */
	public static function after_duplicate_rule( $post_id ) {
		$post         = get_post( $post_id );
		$translations = pll_get_post_translations( $post_id );

		do_action( 'pll_save_post', $post_id, $post, $translations ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
	}

	/**
	 * Add parse_query hook before get all rules.
	 */
	public static function before_get_all_rules() {
		add_action( 'parse_query', array( __CLASS__, 'remove_lang_from_query' ), -100 );
	}

	/**
	 * Remove the lang from the query.
	 *
	 * @param WP_Query $query WordPress Query object.
	 */
	public static function remove_lang_from_query( $query ) {
		if ( isset( $query->query_vars['post_type'] ) && 'shop_bogof_rule' === $query->query_vars['post_type'] ) {
			// No lang. Return all records.
			$query->query_vars['lang'] = '';

			remove_action( 'parse_query', array( __CLASS__, 'remove_lang_from_query' ), -100 );
		}
	}

	/**
	 * Filter the list of products of the json search.
	 *
	 * @param array $products Arry of products.
	 * @return array
	 */
	public static function json_search_found_products( $products ) {
		$referer  = wp_get_referer();
		$is_bogof = false !== strpos( $referer, 'post_type=shop_bogof_rule' );

		if ( ! $is_bogof && false !== strpos( $referer, 'post.php?post=' ) ) {
			// Edit post, get post ID.
			$referer = wp_parse_url( $referer, PHP_URL_QUERY );
			$pieces  = explode( '&', $referer );
			if ( isset( $pieces[0] ) ) {
				$post_id  = absint( str_replace( 'post=', '', $pieces[0] ) );
				$is_bogof = 'shop_bogof_rule' === get_post_type( ( $post_id ) );
			}
		}
		if ( $is_bogof ) {
			$current_language = pll_current_language();
			$_products        = $products;
			foreach ( $_products as $id => $product ) {
				$product_language = pll_get_post_language( $id );
				if ( $product_language && $product_language !== $current_language ) {
					unset( $products[ $id ] );
				}
			}
		}

		return $products;
	}

	/**
	 * Retruns the choose your gift page ID for the current language.
	 *
	 * @param string $value Option value.
	 */
	public static function choose_your_gift_page_id( $value ) {
		if ( ! is_admin() ) {
			$value = pll_get_post( $value );
		}
		return $value;
	}

	/**
	 * Translate the cart item.
	 *
	 * @since 2.0.12
	 * @param array $item Session data.
	 * @return array
	 */
	public static function translate_cart_item( $item ) {

		if ( isset( $item['_bogof_free_item'] ) ) {
			// Translate the BOGOF rule.
			$item['_bogof_free_item'] = self::translate_cart_rule_id( $item['_bogof_free_item'] );
		}

		if ( isset( $item['_bogof_discount'] ) && is_array( $item['_bogof_discount'] ) ) {

			$tr_bogof_discount = array();

			foreach ( $item['_bogof_discount'] as $cart_rule_id => $free_qty ) {

				$tr_cart_rule_id = self::translate_cart_rule_id( $cart_rule_id );

				$tr_bogof_discount[ $tr_cart_rule_id ] = $free_qty;
			}

			$item['_bogof_discount'] = $tr_bogof_discount;

			if ( isset( $item['data'] ) && is_object( $item['data'] ) ) {
				$item['data']->_bogof_discount = new WC_BOGOF_Cart_Item_Discount( $item, $tr_bogof_discount );
			}
		}
		return $item;
	}

	/**
	 * Translate cart rule ID.
	 *
	 * @param string $cart_rule_id Cart rule ID to translate.
	 * @return string
	 */
	private static function translate_cart_rule_id( $cart_rule_id ) {
		$pieces = explode( '-', $cart_rule_id );

		if ( count( $pieces ) > 1 ) {
			$tr_cart_rule_id = pll_get_post( $pieces[0] ) . '-' . pll_get_post( $pieces[1] );
		} else {
			$tr_cart_rule_id = pll_get_post( $cart_rule_id );
		}
		return $tr_cart_rule_id;
	}

	/**
	 * Add the usage count of the translate rules.
	 *
	 * @param int           $count Usage count value.
	 * @param string|array  $used_by Either user ID or billing email.
	 * @param WC_BOGOF_Rule $rule The rule object.
	 * @return int
	 */
	public static function rule_get_usage_count( $count, $used_by, $rule ) {
		global $polylang;
		$post_id = $rule->get_id();

		if ( function_exists( 'pll_get_post_translations' ) ) {

			$data_store = WC_Data_Store::load( 'bogof-rule' );

			$translations = pll_get_post_translations( $post_id );
			$translations = array_diff( $translations, array( $post_id ) ); // Just remove this post from the list.

			foreach ( $translations as $tr_lang => $tr_id ) {
				if ( $tr_id !== $post_id && $tr_id ) {
					$count += $data_store->get_usage_count( $used_by, $tr_id );
				}
			}
		}

		return $count;
	}

	/**
	 * Check if the rule has the same language than the product in the cart.
	 *
	 * @since 3.2.1
	 * @param bool          $add Should add the cart rule?.
	 * @param array         $cart_item Cart item data.
	 * @param WC_BOGOF_Rule $rule The rule object.
	 * @return bool
	 */
	public static function should_add_cart_rule( $add, $cart_item, $rule ) {
		if ( ! $add ) {
			return false;
		}
		$product_id   = empty( $cart_item['product_id'] ) ? 0 : $cart_item['product_id'];
		$product_lang = pll_get_post_language( $product_id );
		$rule_lang    = pll_get_post_language( $rule->get_id() );

		return $product_lang === $rule_lang;
	}

	/**
	 * Hyyan WooCommerce Polylang Integration. Restore the custom properties after translate the product.
	 *
	 * @param WC_Product $cart_item_product_translation Translated product.
	 * @param WC_Product $cart_item_product Original product.
	 * @return WC_Product
	 */
	public static function woo_poly_cart_switched_item( $cart_item_product_translation, $cart_item_product ) {
		if ( isset( $cart_item_product->_bogof_discount ) && is_object( $cart_item_product_translation ) ) {
			$cart_item_product_translation->_bogof_discount = $cart_item_product->_bogof_discount;
		}
		return $cart_item_product_translation;
	}

	/**
	 * Returns true if the rule is the current language.
	 *
	 * @param bool          $value Return value.
	 * @param WC_BOGOF_Rule $rule BOGO rule instance.
	 */
	public static function customizer_preview_rule( $value, $rule ) {
		return $value && pll_get_post( $rule->get_id() ) === $rule->get_id();
	}
}
