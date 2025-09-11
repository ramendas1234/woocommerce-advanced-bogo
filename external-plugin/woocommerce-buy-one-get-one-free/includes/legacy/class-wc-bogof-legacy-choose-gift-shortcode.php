<?php
/**
 * Legacy Choose your gift shortcode
 *
 * @since 4.2
 * @package  WC_BOGOF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Choose your gift shortcode class.
 */
class WC_BOGOF_Legacy_Choose_Gift_Shortcode extends WC_Shortcode_Products {

	/**
	 * BOGO hash.
	 *
	 * @var   string
	 */
	protected $bogo_hash = '';

	/**
	 * Default attributes.
	 *
	 * @var array
	 */
	protected $default_attr = array(
		'limit'      => '', // Results limit.
		'columns'    => '', // Number of columns.
		'rows'       => '', // Number of rows. If defined, limit will be ignored.
		'orderby'    => '', // menu_order, title, date, rand, price, popularity, rating, or id.
		'order'      => '', // ASC or DESC.
		'class'      => '', // HTML class.
		'page'       => 1,  // Page for pagination.
		'title'      => '', // Choose your gift title.
		'no_results' => true, // Display no results HTML.
	);

	/**
	 * Initialize shortcode.
	 *
	 * @param array $attributes Shortcode attributes.
	 */
	public function __construct( $attributes = array() ) {
		parent::__construct( $attributes, 'choose_your_gift' );

		add_action( 'woocommerce_shortcode_choose_your_gift_loop_no_results', array( $this, 'no_results' ) );
	}

	/**
	 * Parse attributes.
	 *
	 * @param  array $attributes Shortcode attributes.
	 * @return array
	 */
	protected function parse_attributes( $attributes ) {

		$attributes = shortcode_atts(
			$this->default_attr,
			$attributes,
			$this->type
		);

		if ( ! absint( $attributes['columns'] ) ) {
			$attributes['columns'] = wc_get_default_products_per_row();
		}
		if ( empty( $attributes['limit'] ) ) {
			$attributes['limit'] = absint( $attributes['columns'] ) * wc_get_default_product_rows_per_page();
		}

		// Cast no results attribute to boolean.
		$attributes['no_results'] = wc_string_to_bool( $attributes['no_results'] );

		// Set attributes of the shortcode.
		$attributes['ids']            = '';
		$attributes['skus']           = '';
		$attributes['category']       = '';
		$attributes['cat_operator']   = 'IN';
		$attributes['attribute']      = '';
		$attributes['terms']          = '';
		$attributes['terms_operator'] = 'IN';
		$attributes['tag']            = '';
		$attributes['tag_operator']   = 'IN';
		$attributes['paginate']       = true;
		$attributes['cache']          = ! ( defined( 'WP_DEBUG' ) && WP_DEBUG );
		$attributes['visibility']     = 'choose_your_gift';
		$attributes['post_where']     = $this->get_post_where();

		return $attributes;
	}

	/**
	 * Get post where.
	 *
	 * @return string
	 */
	protected function get_post_where() {
		global $wpdb;
		$filters = array();

		foreach ( wc_bogof_cart_rules() as $cart_rule ) {
			$post_id_in = $cart_rule->get_free_products_in();
			if ( $post_id_in ) {
				$filters[] = '(' . $post_id_in . ')';
			}
		}

		if ( ! empty( $filters ) ) {
			$where = ' AND (' . implode( ' OR ', $filters ) . ')';
		} else {
			$where = ' AND 1=0';
		}

		return $where;
	}

	/**
	 * Set visibility as "choose_your_gift" (all products).
	 *
	 * @param array $query_args Query args.
	 */
	protected function set_visibility_choose_your_gift_query_args( &$query_args ) {
		$this->custom_visibility = true;

		// Display non-public products.
		$query_args['post_status'] = array( 'publish', 'private', 'draft', 'trash' );

		// Hide external products.
		$query_args['tax_query'][] = array(
			'taxonomy'         => 'product_type',
			'terms'            => array( 'external' ),
			'field'            => 'name',
			'operator'         => 'NOT IN',
			'include_children' => false,
		);

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			// Hide out of stock products.
			if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {

				$product_visibility_terms  = wc_get_product_visibility_term_ids();
				$query_args['tax_query'][] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $product_visibility_terms['outofstock'],
					'operator' => 'NOT IN',
				);
			}

			// Hide non-public products.
			$query_args['post_status'] = 'publish';
		}
	}

	/**
	 * Get wrapper classes.
	 *
	 * @param  array $columns Number of columns.
	 * @return array
	 */
	protected function get_wrapper_classes( $columns ) {

		$wrapperclasses = apply_filters(
			'wc_bogof_choose_your_gift_wrappper_classes',
			array(
				'et_smooth_scroll_disabled', // Disable DIVI smooth scroll.
			)
		);

		$classes   = parent::get_wrapper_classes( $columns );
		$classes[] = 'choose-your-gift-default' . ! empty( $wrapperclasses ) && is_array( $wrapperclasses ) ? ' ' . implode( ' ', $wrapperclasses ) : '';

		return $classes;
	}

	/**
	 * Generate and return the transient name for this shortcode based on the query args.
	 *
	 * @return string
	 */
	protected function get_transient_name() {
		$transient_args = array(
			$this->query_args,
			$this->type,
			$this->attributes['post_where'],
		);

		if ( version_compare( WC_VERSION, '3.6', '<' ) ) {
			$transient_args[] = WC_Cache_Helper::get_transient_version( 'product_query' );
		}

		$transient_name = 'wc_bogof_cyg_' . md5( wp_json_encode( $transient_args ) );

		if ( 'rand' === $this->query_args['orderby'] ) {
			// When using rand, we'll cache a number of random queries and pull those to avoid querying rand on each page load.
			$rand_index      = wp_rand( 0, max( 1, absint( apply_filters( 'woocommerce_product_query_max_rand_cache_count', 5 ) ) ) );
			$transient_name .= $rand_index;
		}

		return $transient_name;
	}

	/**
	 * Run the query and return an array of data, including queried ids and pagination information.
	 *
	 * @return object Object with the following props; ids, per_page, found_posts, max_num_pages, current_page
	 */
	protected function get_query_results() {
		add_filter( 'posts_where', array( $this, 'posts_where' ), 10, 2 );

		$results = parent::get_query_results();

		remove_filter( 'posts_where', array( $this, 'posts_where' ), 10, 2 );

		return $results;
	}

	/**
	 * Add the choose your gift product filter.
	 *
	 * @param string   $where The WHERE clause of the query.
	 * @param WP_Query $q The WP_Query instance (passed by reference).
	 */
	public function posts_where( $where, $q ) {
		$where .= $this->attributes['post_where'];
		return $where;
	}

	/**
	 * Add product filters to modify the product properties in the loop.
	 */
	protected function add_product_filters() {
		// Set custom product visibility when quering hidden products.
		add_action( 'woocommerce_product_is_visible', array( $this, 'set_product_as_visible' ) );

		// Add the BOGO refer the product link.
		add_filter( 'post_type_link', array( $this, 'product_link' ) );
		add_filter( 'woocommerce_loop_product_link', array( $this, 'product_link' ) );
		add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'product_link' ), 100 );
	}

	/**
	 * Restore product properties.
	 */
	protected function remove_product_filters() {
		remove_action( 'woocommerce_product_is_visible', array( $this, 'set_product_as_visible' ) );
		remove_filter( 'post_type_link', array( $this, 'product_link' ) );
		remove_filter( 'woocommerce_loop_product_link', array( $this, 'product_link' ) );
		remove_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'product_link' ), 100 );
	}

	/**
	 * Add the bogof parameter to the URL.
	 *
	 * @param string $product_link Product link.
	 */
	public function product_link( $product_link ) {
		if ( false === strpos( $product_link, 'wc_bogo_refer' ) ) {
			$product_link = add_query_arg( 'wc_bogo_refer', WC_BOGOF_Cart::get_hash(), $product_link );
		}
		return $product_link;
	}

	/**
	 * Loop over found products.
	 *
	 * @return string
	 */
	protected function product_loop() {
		$columns  = absint( $this->attributes['columns'] );
		$classes  = $this->get_wrapper_classes( $columns );
		$products = $this->get_query_results();

		ob_start();

		$this->store_managers_alerts();

		if ( $products && $products->ids ) {
			// Prime caches to reduce future queries.
			if ( is_callable( '_prime_post_caches' ) ) {
				_prime_post_caches( $products->ids );
			}

			// Setup the loop.
			wc_setup_loop(
				array(
					'columns'      => $columns,
					'name'         => $this->type,
					'is_shortcode' => true,
					'is_search'    => false,
					'is_paginated' => true,
					'total'        => $products->total,
					'total_pages'  => $products->total_pages,
					'per_page'     => $products->per_page,
					'current_page' => $products->current_page,
				)
			);

			$original_post = $GLOBALS['post'];

			do_action( 'wc_bogof_before_choose_your_gift_loop', $this->attributes );

			// Output the title.
			if ( ! empty( $this->attributes['title'] ) ) {
				echo '<h2>' . esc_html( $this->attributes['title'] ) . '</h2>';
			}

			woocommerce_product_loop_start();

			if ( wc_get_loop_prop( 'total' ) ) {

				foreach ( $products->ids as $product_id ) {
					$GLOBALS['post'] = get_post( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					setup_postdata( $GLOBALS['post'] );

					// Set custom product product properties.
					$this->add_product_filters();

					// Render product template.
					wc_get_template_part( 'content', 'product' );

					// Restore product propertiles.
					$this->remove_product_filters();
				}
			}

			$GLOBALS['post'] = $original_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			woocommerce_product_loop_end();

			// Standard pagination.
			$this->pagination();

			do_action( 'wc_bogof_after_choose_your_gift_loop', $this->attributes );

			wp_reset_postdata();
			wc_reset_loop();

		} elseif ( $this->attributes['no_results'] ) {
			do_action( "woocommerce_shortcode_{$this->type}_loop_no_results", $this->attributes );
		}

		return $this->shortcode_wrapper( ob_get_clean() );
	}

	/**
	 * Display alert notices to store managers.
	 */
	protected function store_managers_alerts() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		global $wpdb;

		$query = "SELECT count(*) FROM {$wpdb->posts} WHERE {$wpdb->posts}.post_type = 'product' " . $this->attributes['post_where'];

		// Check outstock products.
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$product_visibility_terms = wc_get_product_visibility_term_ids();
			$outstock_query           = $query . $wpdb->prepare( " AND {$wpdb->posts}.post_status = 'publish' AND ( {$wpdb->posts}.ID IN ( SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN (%d) ) )", $product_visibility_terms['outofstock'] );
			$outstock                 = $wpdb->get_var( $outstock_query ); // phpcs:ignore: WordPress.DB.PreparedSQL

			if ( $outstock ) {
				// Translators: %s: number of products.
				wc_print_notice( sprintf( __( 'Warning: %s products listed below will not be visible to users because they are out of stock.', 'wc-buy-one-get-one-free' ), $outstock ), 'error' );
			}
		}

		// Check non-public products.
		$no_public_query = $query . " AND {$wpdb->posts}.post_status != 'publish'";
		$no_public       = $wpdb->get_var( $no_public_query ); // phpcs:ignore: WordPress.DB.PreparedSQL
		if ( $no_public ) {
			// Translators: %s: number of products.
			wc_print_notice( sprintf( __( 'Warning: %s products listed below will not be visible to users because they are not public.', 'wc-buy-one-get-one-free' ), $no_public ), 'error' );
		}
	}

	/**
	 * Shortcode Wrapper.
	 *
	 * @param string $content HTML content.
	 * @return string
	 */
	protected function shortcode_wrapper( $content ) {
		$data            = array_intersect_key( $this->attributes, $this->default_attr );
		$data['hash']    = WC_BOGOF_Cart::get_hash();
		$data['is_cart'] = wc_bool_to_string( is_cart() );
		$classes         = $this->get_wrapper_classes( absint( $this->attributes['columns'] ) );

		return sprintf(
			'<div id="wc-choose-your-gift" class="%s" data-parameters="%s">%s</div>',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( wp_json_encode( $data ) ),
			$content
		);
	}

	/**
	 * WooCommerce pagination.
	 */
	protected function pagination() {
		add_filter( 'paginate_links', array( $this, 'paginate_link_anchor' ) );
		woocommerce_pagination();
		remove_filter( 'paginate_links', array( $this, 'paginate_link_anchor' ) );
	}

	/**
	 * Add anchor to the paginate links.
	 *
	 * @param string $link Link.
	 */
	public static function paginate_link_anchor( $link ) {
		if ( false === strpos( $link, '#' ) ) {
			$link .= '#wc-choose-your-gift';
		}
		return $link;
	}

	/**
	 * No eligible gifts.
	 */
	public function no_results() {
		echo '<p>' . esc_html__( 'There are no gifts for you yet.', 'wc-buy-one-get-one-free' ) . '<p>';
		if ( wc_get_page_id( 'shop' ) > 0 ) {
			echo '<a class="button wc-backward" href="' . esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ) . '">';
			esc_html_e( 'Return to shop', 'wc-buy-one-get-one-free' );
			echo '</a>';
		}
	}
}
