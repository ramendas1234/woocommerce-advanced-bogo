<?php
/**
 * Buy One Get One Free Helper Functions
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main function for returning WC_BOGOF_Rule.
 *
 * This function should only be called after 'init' action is finished, as there might be post types that are getting
 * registered during the init action.
 *
 * @since 2.1.5
 *
 * @param mixed $rule_id Rule ID of the BOGOF rule.
 * @return WC_BOGOF_Rule|false
 */
function wc_bogof_get_rule( $rule_id ) {
	if ( ! did_action( 'woocommerce_init' ) || ! did_action( 'wc_bogof_after_register_post_type' ) ) {
		/* translators: 1: wc_bogof_get_rule 2: woocommerce_init 3: wc_bogof_after_register_post_type 4: woocommerce_after_register_post_type */
		wc_doing_it_wrong( __FUNCTION__, sprintf( __( '%1$s should not be called before the %2$s, %3$s and %4$s action have finished.', 'wc-buy-one-get-one-free' ), 'wc_bogof_get_rule', 'woocommerce_init', 'wc_bogof_after_register_post_type' ), '2.1.5' );
		return false;
	}

	$rule_id = absint( $rule_id );
	try {
		return new WC_BOGOF_Rule( $rule_id );
	} catch ( Exception $e ) {
		return false;
	}
}


/**
 * Main function for returning WC_BOGOF_Cart_Rules.
 *
 * This function should only be called after 'woocommerce_cart_loaded_from_session' action is finished.
 *
 * @since 2.2.0
 *
 * @return WC_BOGOF_Cart_Rules|false
 */
function wc_bogof_cart_rules() {
	if ( ! did_action( 'woocommerce_cart_loaded_from_session' ) ) {
		/* translators: 1: wc_bogof_cart_rules 2: woocommerce_cart_loaded_from_session */
		wc_doing_it_wrong( __FUNCTION__, sprintf( __( '%1$s should not be called before the %2$s action have finished.', 'wc-buy-one-get-one-free' ), 'wc_bogof_cart_rules', 'woocommerce_init', 'wc_bogof_after_register_post_type' ), '2.2.0' );
		return false;
	}

	return WC_BOGOF_Cart::$cart_rules;
}

/**
 * Duplicate a BOGOF rule.
 *
 * @param int $post_id BOGOF Rule ID.
 * @return WC_BOGOF_Rule
 */
function wc_bogof_duplicate_rule( $post_id ) {
	$rule    = wc_bogof_get_rule( $post_id );
	$newrule = false;
	if ( $rule ) {
		$newrule = new WC_BOGOF_Rule();
		$data    = $rule->get_data();
		unset( $data['id'], $data['enabled'], $data['title'] );

		$newrule->set_props( $data );

		$newrule->set_enabled( false );
		$newrule->set_title( $rule->get_title() . ' (' . __( 'copy', 'wc-buy-one-get-one-free' ) . ')' );
		$newrule->save();

		do_action( 'wc_bogof_after_duplicate_rule', $newrule->get_id() );
	}
	return $newrule;
}

/*
|--------------------------------------------------------------------------
| Utils Functions
|--------------------------------------------------------------------------
*/

/**
 * Checks if the array insterset of two arrays is no empty.
 *
 * @param array $array1 The array with master values to check.
 * @param array $array2 An array to compare values against.
 * @return bool
 */
function wc_bogof_in_array_intersect( $array1, $array2 ) {
	$array1 = is_array( $array1 ) ? $array1 : [ $array1 ];
	$array2 = is_array( $array2 ) ? $array2 : [ $array2 ];
	return count( array_intersect( $array1, $array2 ) ) > 0;
}

/**
 * Return product categories IDs.
 *
 * @param int $product_id Product ID.
 * @return array
 */
function wc_bogof_get_product_cats( $product_id ) {
	$cache_key    = WC_Cache_Helper::get_cache_prefix( 'product_' . $product_id ) . '_bogof_product_cat_' . $product_id;
	$product_cats = wp_cache_get( $cache_key, 'products' );
	if ( ! is_array( $product_cats ) ) {
		$product_cats = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
		wp_cache_set( $cache_key, $product_cats, 'products' );
	}
	return $product_cats;
}

/**
 * Check if the product belong to category.
 *
 * @param int   $product_id Product ID.
 * @param array $term_ids Array of product categories IDs to check.
 * @return bool.
 */
function wc_bogof_product_in_category( $product_id, $term_ids ) {
	$in_category = in_array( 'all', $term_ids, true );
	if ( ! $in_category ) {
		$product_cats = wc_bogof_get_product_cats( $product_id );
		$in_category  = wc_bogof_in_array_intersect( $term_ids, $product_cats );
	}
	return $in_category;
}

/**
 * Check if the current user has one or more roles.
 *
 * @param array $roles Array roles to check.
 * @return bool.
 */
function wc_bogof_current_user_has_role( $roles ) {
	$has_role   = false;
	$user       = wp_get_current_user();
	$user_roles = empty( $user->ID ) ? array( 'not-logged-in' ) : array_merge( $user->roles, array( 'logged-in' ) );
	$has_role   = wc_bogof_in_array_intersect( $user_roles, $roles );

	return $has_role;
}

/**
 * Check if the page content has the wc_choose_your_gift shortcode.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function wc_bogof_has_choose_your_gift_shortcode( $post_id ) {
	$post          = get_post( $post_id );
	$has_shortcode = false;

	if ( $post && isset( $post->post_content ) ) {
		$has_shortcode = has_shortcode( $post->post_content, 'wc_choose_your_gift' );
	}
	return $has_shortcode;
}

/**
 * Returns an array of variable product types.
 *
 * @return array
 */
function wc_bogof_variable_types() {
	return array( 'variable', 'variable-subscription' );
}

/**
 * Returns incompatible product types.
 *
 * @return array
 */
function wc_bogof_incompatible_product_types() {
	return array( 'mix-and-match', 'composite' );
}

/**
 * Returns true if the product type supports the modal gift.
 *
 * @since 4.2
 * @param string $product_type The product type.
 * @return bool
 */
function wc_bogof_product_type_supports_modal_gifts( $product_type ) {
	$classname = WC_Product_Factory::get_classname_from_product_type( $product_type );
	$supports  = in_array( $classname, [ 'WC_Product_Simple', 'WC_Product_Variation', 'WC_Product_Subscription', 'WC_Product_Subscription_Variation' ], true );

	if ( ! $supports ) {
		$product  = new $classname();
		$supports = $product->supports( 'ajax_add_to_cart' );
	}

	return $supports;
}

/**
 * Returns an array with the WC Customer emails and user ID.
 *
 * @return array
 */
function wc_bogof_user_ids() {
	$ids = array();
	if ( WC()->customer ) {
		foreach ( array( 'get_email', 'get_billing_email', 'get_id' ) as $getter ) {
			if ( is_callable( array( WC()->customer, $getter ) ) ) {
				$ids[] = WC()->customer->{$getter}();
			}
		}
	}

	return array_unique( array_filter( array_map( 'strtolower', $ids ) ) );
}

/**
 * Get the product row price per item.
 *
 * @param WC_Product $product Product object.
 * @param array      $args Optional arguments to pass product quantity and price.
 * @return float
 */
function wc_bogof_get_cart_product_price( $product, $args = array() ) {
	if ( WC()->cart->display_prices_including_tax() ) {
		$product_price = wc_get_price_including_tax( $product, $args );
	} else {
		$product_price = wc_get_price_excluding_tax( $product, $args );
	}
	return ( $product_price ? $product_price : 0 );
}

/**
 * Log a message as a debug log entry.
 *
 * @param string $message Log message.
 * @param string $method Method name that logs the message.
 * @param string $level Log level.
 * @since 4.0.4
 */
function wc_bogof_log( $message, $method = '', $level = false ) {
	if ( ! function_exists( 'function_exists' ) || ! class_exists( 'WC_Log_Levels' ) ) {
		return;
	}
	static $logger = false;
	if ( false === $logger && function_exists( 'wc_get_logger' ) ) {
		$logger = wc_get_logger();
	}

	$level   = false === $level ? WC_Log_Levels::DEBUG : $level;
	$message = is_scalar( $message ) ? $message : wp_json_encode( $message, JSON_PRETTY_PRINT );

	$logger->log(
		$level,
		sprintf( '%s %s', $method, $message ),
		[
			'source' => 'wc-buy-one-get-one-free',
		]
	);
}

/**
 * Check if a coupon is empty.
 * The coupon is empty if the amount is zero, the type is one of the default coupon types and are in a BOGO rule.
 *
 * @param WC_Coupon $coupon The coupon instance.
 * @since 5.2.4
 */
function wc_bogof_coupon_is_empty( $coupon ) {
	return is_a( $coupon, 'WC_Coupon' ) && $coupon->get_amount() <= 0 && $coupon->is_type( [ 'percent', 'fixed_cart', 'fixed_product' ] ) && ! $coupon->get_free_shipping();
}

/*
|--------------------------------------------------------------------------
| Transients Functions
|--------------------------------------------------------------------------
*/

/**
 * Delete product transients.
 *
 * @param int $post_id (default: 0) The product ID.
 */
function wc_bogof_delete_product_transients( $post_id ) {
	if ( $post_id ) {
		delete_transient( 'wc_bogof_rules_' . $post_id );
	}
}
add_action( 'woocommerce_delete_product_transients', 'wc_bogof_delete_product_transients' );

/**
 * Delete used by transient on order status change.
 *
 * @param int      $post_id The product ID.
 * @param string   $from From status.
 * @param string   $to To status.
 * @param WC_Order $order Order instance.
 */
function wc_bogof_delete_used_by_transient( $post_id, $from, $to, $order ) {
	$metas = $order->get_meta( '_wc_bogof_rule_id', false );

	if ( is_array( $metas ) && ! empty( $metas ) ) {
		foreach ( $metas as $meta ) {
			delete_transient( 'wc_bogof_uses_' . $meta->value );
		}
	}
}
add_action( 'woocommerce_order_status_changed', 'wc_bogof_delete_used_by_transient', 10, 4 );

/**
 * Run after clear transients action of the WooCommerce System Status Tool.
 *
 * @param array $tool Details about the tool that has been executed.
 */
function wc_bogof_clear_transients( $tool ) {
	global $wpdb;

	$id = isset( $tool['id'] ) ? $tool['id'] : false;
	if ( 'clear_transients' === $id && ! empty( $tool['success'] ) ) {

		WC_Cache_Helper::get_transient_version( 'bogof_rules', true );

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_timeout_wc_bogof_%',
				'_transient_wc_bogof_%'
			)
		);
	}
}
add_action( 'woocommerce_system_status_tool_executed', 'wc_bogof_clear_transients' );
add_action( 'woocommerce_rest_insert_system_status_tool', 'wc_bogof_clear_transients' );

/**
 * Delete transients on BOGO rule delete.
 *
 * @param mixed $id ID of post being deleted.
 */
function wc_bogof_delete_post( $id ) {
	if ( ! $id ) {
		return;
	}

	$post_type = get_post_type( $id );

	if ( 'shop_bogof_rule' === $post_type ) {
		WC_Cache_Helper::get_transient_version( 'bogof_rules', true );
	} elseif ( 'shop_order' === $post_type ) {
		$order = wc_get_order( $id );
		if ( $order ) {
			wc_bogof_delete_used_by_transient( $id, '', '', $order );
		}
	} elseif ( 'shop_coupon' === $post_type ) {
		WC_Cache_Helper::invalidate_cache_group( 'bogof_coupon_codes' );
	}
}
add_action( 'delete_post', 'wc_bogof_delete_post' );
add_action( 'wp_trash_post', 'wc_bogof_delete_post' );
add_action( 'untrashed_post', 'wc_bogof_delete_post' );

/**
 * Invalidate cache group on coupon code updated.
 *
 * @since 5.2.5
 * @param int     $post_id      Post ID.
 * @param WP_Post $post         Post object following the update.
 * @param WP_Post $post_before  Post object before the update.
 */
function wc_bogof_post_updated( $post_id, $post, $post_before ) {
	if ( 'shop_coupon' !== $post->post_type || $post->post_title === $post_before->post_title ) {
		return;
	}

	WC_Cache_Helper::invalidate_cache_group( 'bogof_coupon_codes' );
}
add_action( 'post_updated', 'wc_bogof_post_updated', 10, 3 );

/**
 * Delete product query transient after term updated. Prevent issues when a term parent changes.
 *
 * @param  int    $term_id  Term ID.
 * @param  int    $tt_id    Term taxonomy ID.
 * @param  string $taxonomy Taxonomy slug.
 */
function wc_bogof_edited_term( $term_id, $tt_id, $taxonomy ) {
	if ( is_callable( [ 'WC_Post_Data', 'delete_product_query_transients' ] ) && in_array( $taxonomy, get_object_taxonomies( 'product' ), true ) ) {
		WC_Post_Data::delete_product_query_transients();
	}
}
add_action( 'edited_term', 'wc_bogof_edited_term', 10, 4 );

/*
|--------------------------------------------------------------------------
| Compatibility Functions
|--------------------------------------------------------------------------
*/

/**
 * Skips cart item to not count to the rule.
 *
 * @param WC_BOGOF_Rule $cart_rule Cart rule.
 * @param array         $cart_item Cart item.
 * @return bool
 */
function wc_bogof_cart_item_match_skip( $cart_rule, $cart_item ) {
	// Skip bundle child items.
	return apply_filters(
		'wc_bogof_cart_item_match_skip',
		( class_exists( 'WC_Product_Woosb' ) && ! empty( $cart_item['woosb_parent_id'] ) ),
		$cart_rule,
		$cart_item
	);
}

/**
 * Advanced Dynamic Pricing for WooCommerce by AlgolPlus compatibility.
 */
if ( defined( 'WC_ADP_PLUGIN_FILE' ) ) {

	add_action(
		'wc_bogof_choose_your_gift_after_add_price_filters',
		function() {
			add_filter( 'adp_get_price_html_processed_product', '__return_null' );
		}
	);

	add_action(
		'wc_bogof_choose_your_gift_after_remove_price_filters',
		function() {
			remove_filter( 'adp_get_price_html_processed_product', '__return_null' );
		}
	);

	add_filter(
		'adp_product_get_price',
		function( $price, $product ) {
			// Set the product price before ADP calculate totals.
			if ( WC_BOGOF_Cart::is_valid_discount( $product ) && $product->_bogof_discount->has_discount() ) {
				$price         = $product->_bogof_discount->get_sale_price();
				$regular_price = $product->get_regular_price( 'edit' );

				$product->set_price( $price );
				$product->set_sale_price( $price );
				$product->set_regular_price( $regular_price );
			}
		},
		99999,
		2
	);
}

/**
 * Variation Swatches for WooCommerce by RadiusTheme
 */
if ( defined( 'RTWPVS_PLUGIN_FILE' ) ) {
	/**
	 * Turn off swatches for the Gift Pop-up.
	 */
	add_filter(
		'default_rtwpvs_variation_attribute_options_html',
		function( $value ) {
			global $wp_query;
			if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) && 'bogof_get_gift_fragments' === $wp_query->get( 'wc-ajax' ) ) {
				$value = true;
			}
			return $value;
		},
		99999
	);
}

/*
|--------------------------------------------------------------------------
| Meta Box Functions
|--------------------------------------------------------------------------
*/

/**
 * Returns the display key for the _wc_bogof_rule_id meta data.
 *
 * @param string $display_key Display key.
 * @param object $meta Meta data.
 */
function wc_bogof_order_item_display_meta_key( $display_key, $meta ) {
	if ( '_wc_bogof_rule_id' === $meta->key ) {
		$display_key = __( 'BOGO Promotion', 'wc-buy-one-get-one-free' );
	}
	return $display_key;
}
add_filter( 'woocommerce_order_item_display_meta_key', 'wc_bogof_order_item_display_meta_key', 10, 2 );

/**
 * Returns the display value for the _wc_bogof_rule_id meta data.
 *
 * @param string                $display_value Display value.
 * @param object                $meta Meta data.
 * @param WC_Order_Item_Product $item Order Item.
 */
function wc_bogof_order_item_display_meta_value( $display_value, $meta, $item ) {
	if ( '_wc_bogof_rule_id' === $meta->key ) {

		$title = is_callable( array( $item, 'get_meta' ) ) ? $item->get_meta( '_wc_bogof_rule_name' ) : false;
		$title = is_array( $title ) && isset( $title[ $meta->value ] ) ? $title[ $meta->value ] : $title;
		$rule  = wc_bogof_get_rule( $meta->value );

		$title = ! $title && $rule ? $rule->get_title() : $title;

		if ( $rule ) {
			$display_value = sprintf( '<a href="%s">%s</a>', admin_url( '/post.php?action=edit&post=' . $rule->get_id() ), $title );
		} elseif ( $title ) {
			$display_value = $title;
		}
	}
	return $display_value;
}
add_filter( 'woocommerce_order_item_display_meta_value', 'wc_bogof_order_item_display_meta_value', 10, 3 );

/**
 * Hide the _wc_bogof_rule_name order item meta from the Order items metabox
 *
 * @param array $hidden_order_itemmeta Hidden order ite mmetas.
 */
function wc_bogof_hidden_order_itemmeta( $hidden_order_itemmeta ) {
	$hidden_order_itemmeta   = is_array( $hidden_order_itemmeta ) ? $hidden_order_itemmeta : array();
	$hidden_order_itemmeta[] = '_wc_bogof_rule_name';
	$hidden_order_itemmeta[] = '_wc_bogof_free_item';
	return $hidden_order_itemmeta;
}
add_filter( 'woocommerce_hidden_order_itemmeta', 'wc_bogof_hidden_order_itemmeta' );

/**
 * Returns an array with the BOGO rule type in type desc pair.
 *
 * @return array
 */
function wc_bogof_rule_type_options() {
	return array(
		'cheapest_free' => __( 'Get the cheapest product for free (Special offer)', 'wc-buy-one-get-one-free' ),
		'buy_a_get_b'   => __( 'Product as a gift', 'wc-buy-one-get-one-free' ),
		'buy_a_get_a'   => __( 'Get the same product you buy for free', 'wc-buy-one-get-one-free' ),
	);
}

/*
|--------------------------------------------------------------------------
| Order data store Functions
|--------------------------------------------------------------------------
*/

/**
 * Add the _wc_bogof_rule_id query to the meta query.
 *
 * @param array $wp_query_args Order query args.
 * @param array $query_vars order Query vars.
 */
add_filter(
	'woocommerce_order_data_store_cpt_get_orders_query',
	function( $wp_query_args, $query_vars ) {
		if ( isset( $query_vars['meta_query'] ) && is_array( $query_vars['meta_query'] ) ) {
			foreach ( $query_vars['meta_query'] as $meta_query ) {
				if ( isset( $meta_query['key'], $meta_query['value'] ) && '_wc_bogof_rule_id' === $meta_query['key'] ) {
					$wp_query_args['meta_query'][] = $meta_query;
				}
			}
		}
		return $wp_query_args;
	},
	10,
	2
);
