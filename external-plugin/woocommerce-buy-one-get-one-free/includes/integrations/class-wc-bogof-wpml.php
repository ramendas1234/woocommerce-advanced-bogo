<?php
/**
 * Buy One Get One Free WPML compatibilty.
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_BOGOF_Abstract_Multilingual_Sync' ) ) {
	require dirname( WC_BOGOF_PLUGIN_FILE ) . '/includes/abstracts/class-wc-bogof-abstract-multilingual-sync.php';
}

/**
 * WC_BOGOF_WPML Class
 */
class WC_BOGOF_WPML extends WC_BOGOF_Abstract_Multilingual_Sync {

	/**
	 * Translate an object ID.
	 *
	 * @param int    $object_id The ID of the post type (post, page, attachment, custom post) or taxonomy term.
	 * @param string $type The type of element the ID belongs to.
	 * @return int
	 */
	protected function translate_object_id( $object_id, $type ) {
		return self::get_translate_object_id( $object_id, $type, $this->tr_lang );
	}

	/**
	 * Returns a terms without lang filter.
	 *
	 * @param int $term_id Term ID.
	 * @return WP_Term
	 */
	protected function get_term( $term_id ) {
		add_filter( 'wpml_disable_term_adjust_id', '__return_true', 100 );
		$term = get_term( $term_id );
		remove_filter( 'wpml_disable_term_adjust_id', '__return_true', 100 );
		return $term;
	}

	/**
	 * Returns the translate object ID.
	 *
	 * @param int    $object_id The ID of the post type (post, page, attachment, custom post) or taxonomy term.
	 * @param string $type The type of element the ID belongs to.
	 * @param string $lang Slug of the lang to translate.
	 * @return int
	 */
	private static function get_translate_object_id( $object_id, $type, $lang = null ) {
		return apply_filters( 'wpml_object_id', $object_id, $type, false, $lang ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
	}

	/**
	 * Returns the default WPML language.
	 *
	 * @return string
	 */
	private static function get_default_language() {
		global $sitepress;
		return is_callable( array( $sitepress, 'get_default_language' ) ) ? $sitepress->get_default_language() : '';
	}

	/**
	 * Returns the WPML languages.
	 *
	 * @return array
	 */
	private static function get_languages() {
		global $sitepress;
		return is_callable( array( $sitepress, 'get_active_languages' ) ) ? array_keys( $sitepress->get_active_languages() ) : array();
	}

	/**
	 * Returns post lang.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $type Post type.
	 * @return string
	 */
	private static function get_post_language( $post_id, $type ) {
		global $sitepress;
		return is_callable( array( $sitepress, 'get_language_for_element' ) ) ? $sitepress->get_language_for_element( $post_id, 'post_' . $type ) : '';
	}

	/**
	 * Make a duplicate.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $lang Language.
	 * @return int
	 */
	private static function make_duplicate( $post_id, $lang ) {
		global $sitepress;
		$tr_post_id = false;
		if ( is_callable( array( $sitepress, 'make_duplicate' ) ) ) {
			$tr_post_id = $sitepress->make_duplicate( $post_id, $lang );
		}
		return $tr_post_id;
	}

	/**
	 * Init hooks
	 */
	public static function init() {
		add_action( 'wpml_after_save_post', array( __CLASS__, 'after_save_post' ), 100 );
		add_action( 'wc_bogof_after_duplicate_rule', array( __CLASS__, 'after_save_post' ) );
		add_action( 'wc_bogof_after_ajax_toggle_enabled', array( __CLASS__, 'after_ajax_toggle_enabled' ), 10, 2 );
		add_action( 'wc_bogof_before_get_all_rules', array( __CLASS__, 'before_get_all_rules' ) );
		add_filter( 'woocommerce_get_cart_item_from_session', array( __CLASS__, 'translate_cart_item' ), 10000 );
		add_filter( 'option_wc_bogof_cyg_page_id', array( __CLASS__, 'choose_your_gift_page_id' ) );
		add_filter( 'wc_bogof_rule_get_usage_count', array( __CLASS__, 'rule_get_usage_count' ), 10, 3 );
		add_filter( 'wc_bogof_should_add_cart_rule', array( __CLASS__, 'should_add_cart_rule' ), 10, 3 );
		add_filter( 'wc_bogof_customizer_preview_rule', array( __CLASS__, 'customizer_preview_rule' ), 10000, 2 );
	}

	/**
	 * Synchronize BOGO data between languages.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function after_save_post( $post_id ) {
		static $avoid_recursion = false;

		if ( $avoid_recursion || 'shop_bogof_rule' !== get_post_type( $post_id ) || in_array( get_post_status( $post_id ), array( 'auto-draft', 'draft' ), true ) ) {
			return;
		}

		$avoid_recursion = true;

		$master_lang    = self::get_default_language();
		$master_post_id = self::get_translate_object_id( $post_id, 'shop_bogof_rule', $master_lang );

		if ( $post_id == $master_post_id ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			$langs = array_diff( self::get_languages(), array( $master_lang ) );
			foreach ( $langs as $lang ) {

				$tr_id = self::get_translate_object_id( $post_id, 'shop_bogof_rule', $lang );

				if ( ! $tr_id ) {
					$tr_id = self::make_duplicate( $post_id, $lang );
				}

				if ( $tr_id ) {
					$sync = new self( $post_id, $tr_id, $master_lang, $lang );
					$sync->translate();
				}
			}
		}

		$avoid_recursion = false;
	}

	/**
	 * Sync rule enabled on the AJAX action.
	 *
	 * @param int  $rule_id BOGOF Rule ID.
	 * @param bool $enabled True of False.
	 */
	public static function after_ajax_toggle_enabled( $rule_id, $enabled ) {

		$master_lang    = self::get_default_language();
		$master_post_id = self::get_translate_object_id( absint( $rule_id ), 'shop_bogof_rule', $master_lang );

		if ( absint( $rule_id ) !== absint( $master_post_id ) ) {
			return;
		}

		remove_action( 'wpml_after_save_post', array( __CLASS__, 'after_save_post' ), 100 );

		$langs = array_diff( self::get_languages(), array( $master_lang ) );

		foreach ( $langs as $lang ) {

			$tr_post_id = self::get_translate_object_id( $rule_id, 'shop_bogof_rule', $lang );

			if ( ! empty( $tr_post_id ) ) {

				$rule = wc_bogof_get_rule( absint( $tr_post_id ) );

				if ( $rule ) {
					$rule->set_enabled( $enabled );
					$rule->save();
				}
			}
		}

		add_action( 'wpml_after_save_post', array( __CLASS__, 'after_save_post' ), 100 );
	}

	/**
	 * Add pre_get_posts hook before get all rules.
	 */
	public static function before_get_all_rules() {
		add_action( 'pre_get_posts', array( __CLASS__, 'pre_get_posts' ), 9999 );
	}

	/**
	 * Supress filters to no lang filter.
	 *
	 * @param WP_Query $query WP_Query object.
	 */
	public static function pre_get_posts( $query ) {
		if ( ! $query->is_main_query() && 'shop_bogof_rule' === $query->get( 'post_type' ) ) {
			$query->set( 'suppress_filters', true );
			remove_action( 'pre_get_posts', array( __CLASS__, 'pre_get_posts' ), 9999 );
		}
	}

	/**
	 * Translate the cart item.
	 *
	 * @since 3.0.0
	 * @param array $item Session data.
	 * @return array
	 */
	public static function translate_cart_item( $item ) {

		if ( ! isset( $item['product_id'] ) || ! ( isset( $item['_bogof_discount'] ) || isset( $item['_bogof_discount'] ) ) ) {
			return $item;
		}

		$lang = self::get_post_language( $item['product_id'], 'product' );

		if ( isset( $item['_bogof_free_item'] ) ) {
			// Translate the BOGOF rule.
			$item['_bogof_free_item'] = self::translate_cart_rule_id( $item['_bogof_free_item'], $lang );
		}

		if ( isset( $item['_bogof_discount'] ) && is_array( $item['_bogof_discount'] ) ) {

			$tr_bogof_discount = array();

			foreach ( $item['_bogof_discount'] as $cart_rule_id => $free_qty ) {

				$tr_cart_rule_id = self::translate_cart_rule_id( $cart_rule_id, $lang );

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
	 * @param string $lang Lang to translate.
	 * @return string
	 */
	private static function translate_cart_rule_id( $cart_rule_id, $lang ) {
		$pieces = explode( '-', $cart_rule_id );

		if ( count( $pieces ) > 1 ) {
			$type = get_post_type( $pieces[0] );

			$tr_cart_rule_id = self::get_translate_object_id( $pieces[0], 'shop_bogof_rule', $lang ) . '-' . self::get_translate_object_id( $pieces[1], $type, $lang );

		} else {
			$tr_cart_rule_id = self::get_translate_object_id( $cart_rule_id, 'shop_bogof_rule', $lang );
		}
		return $tr_cart_rule_id;
	}

	/**
	 * Retruns the choose your gift page ID for the current language.
	 *
	 * @param string $value Option value.
	 */
	public static function choose_your_gift_page_id( $value ) {
		return self::get_translate_object_id( $value, 'page' );
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
		$post_id = $rule->get_id();

		$data_store = WC_Data_Store::load( 'bogof-rule' );

		$langs = self::get_languages();

		foreach ( $langs as $lang ) {
			$tr_id = self::get_translate_object_id( $post_id, 'shop_bogof_rule', $lang );
			if ( $tr_id !== $post_id && $tr_id ) {
				$count += $data_store->get_usage_count( $used_by, $tr_id );
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
		$product_lang = self::get_post_language( $product_id, 'product' );
		$rule_lang    = self::get_post_language( $rule->get_id(), 'shop_bogof_rule' );

		return $product_lang === $rule_lang;
	}

	/**
	 * Returns true if the rule is the current language.
	 *
	 * @param bool          $value Return value.
	 * @param WC_BOGOF_Rule $rule BOGO rule instance.
	 */
	public static function customizer_preview_rule( $value, $rule ) {
		return $value && self::get_translate_object_id( $rule->get_id(), 'shop_bogof_rule' ) === $rule->get_id();
	}

}
