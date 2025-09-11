<?php
/**
 * WooCommerce Buy One Get One Free admin AJAX.
 *
 * @package  WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Admin_Ajax Class
 */
class WC_BOGOF_Admin_Ajax {

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		$ajax_events = array(
			'json_search_free_products',
			'json_search_coupons',
			'toggle_rule_enabled',
		);

		foreach ( $ajax_events as $ajax_event ) {
			add_action( 'wp_ajax_wc_bogof_' . $ajax_event, array( __CLASS__, $ajax_event ) );
		}
	}

	/**
	 * Search for products and echo json. Products must be purchasable.
	 *
	 * @param array $exclude_product_types Array of product types to exclude.
	 */
	public static function json_search_free_products( $exclude_product_types = false ) {
		check_ajax_referer( 'search-products', 'security' );

		$term = empty( $_GET['term'] ) ? '' : wc_clean( wp_unslash( $_GET['term'] ) );

		if ( empty( $term ) ) {
			wp_die();
		}

		if ( ! empty( $_GET['limit'] ) ) {
			$limit = absint( $_GET['limit'] );
		} else {
			$limit = absint( apply_filters( 'woocommerce_json_search_limit', 30 ) );
		}

		$exclude_product_types = isset( $_GET['exclude'] ) ? explode( ',', wc_clean( $_GET['exclude'] ) ) : array();

		$data_store = WC_Data_Store::load( 'product' );
		$ids        = $data_store->search_products( $term, '', true, false, $limit );

		$product_objects = array_filter( array_map( 'wc_get_product', $ids ), 'wc_products_array_filter_readable' );
		$products        = array();

		foreach ( $product_objects as $product_object ) {
			if ( ! $product_object->is_purchasable() || ( is_callable( array( $product_object, 'get_type' ) ) && in_array( $product_object->get_type(), $exclude_product_types, true ) ) ) {
				// Not available for not purchasable, and exclude product types.
				continue;
			}

			$formatted_name = wp_strip_all_tags( $product_object->get_formatted_name() );
			$managing_stock = $product_object->managing_stock();

			if ( $managing_stock && ! empty( $_GET['display_stock'] ) ) {
				$formatted_name .= ' &ndash; ' . wc_format_stock_for_display( $product_object );
			}

			$products[ $product_object->get_id() ] = rawurldecode( $formatted_name );
		}

		wp_send_json( apply_filters( 'woocommerce_json_search_found_products', $products ) );
	}

	/**
	 * Search for coupons and echo json
	 */
	public static function json_search_coupons() {
		check_ajax_referer( 'search-products', 'security' );

		$term = empty( $_GET['term'] ) ? '' : wc_clean( wp_unslash( $_GET['term'] ) );

		if ( empty( $term ) ) {
			wp_die();
		}

		if ( ! empty( $_GET['limit'] ) ) {
			$limit = absint( $_GET['limit'] );
		} else {
			$limit = absint( apply_filters( 'woocommerce_json_search_limit', 30 ) );
		}

		$posts   = get_posts(
			array(
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				's'              => $term,
			)
		);
		$coupons = array();

		foreach ( $posts as $post ) {
			$coupons[ $post->ID ] = rawurldecode( wp_strip_all_tags( $post->post_title ) );
		}

		wp_send_json( $coupons );
	}

	/**
	 * Toggle rule on or off via AJAX.
	 */
	public static function toggle_rule_enabled() {
		if ( current_user_can( 'manage_woocommerce' ) && check_ajax_referer( 'wc-bogof-toggle-rule-enabled', 'security' ) && isset( $_POST['rule_id'] ) ) {

			$rule = new WC_BOGOF_Rule( absint( $_POST['rule_id'] ) );
			if ( $rule->get_id() ) {
				$rule->set_enabled( ! $rule->get_enabled() );
				$rule->save();

				do_action( 'wc_bogof_after_ajax_toggle_enabled', $rule->get_id(), $rule->get_enabled() );

				if ( ! class_exists( 'WC_BOGOF_Admin_List_Table' ) ) {
					include_once dirname( __FILE__ ) . '/class-wc-bogof-admin-list-table.php';
				}

				$table = new WC_BOGOF_Admin_List_Table();
				$data  = array();

				foreach ( array( 'name', 'enabled' ) as $column_name ) {
					ob_start();
					$table->render_columns( $column_name, $rule->get_id() );
					$data[ $column_name ] = ob_get_clean();
				}

				wp_send_json_success( $data );
			}
		}

		wp_send_json_error( 'invalid_rule_id' );
		wp_die();
	}
}
