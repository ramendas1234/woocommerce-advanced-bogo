<?php
/**
 * Buy One Get One Free - Gift on modal. Handles gift popup actions.
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Choose_Gift Class
 */
class WC_BOGOF_Gifts_Modal {

	/**
	 * Init hooks
	 */
	public static function init() {
		add_action( 'woocommerce_set_cart_cookies', [ __CLASS__, 'maybe_set_gift_cookie' ] );
		add_action( 'wc_bogof_after_cart_rules_update', [ __CLASS__, 'maybe_set_gift_cookie' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'load_scripts' ], 20 );
		add_action( 'wc_ajax_bogof_get_gift_fragments', [ __CLASS__, 'get_gift_fragments' ] );
		add_action( 'wc_ajax_bogof_add_gift_to_cart', [ __CLASS__, 'add_gift_to_cart' ] );
	}


	/**
	 * Adds or removes gifts cookie.
	 */
	public static function maybe_set_gift_cookie() {
		if ( headers_sent() || ! did_action( 'wp_loaded' ) ) {
			return;
		}

		$gift_loop   = new WC_BOGOF_Gifts_Loop( wc_bogof_cart_rules() );
		$cookie_name = 'wc_bogof_gifts_hash';

		if ( $gift_loop->get_total() > 0 ) {
			wc_setcookie( $cookie_name, self::get_gifts_hash( $gift_loop ) );
		} else {
			wc_setcookie( $cookie_name, 0, time() - HOUR_IN_SECONDS );
			unset( $_COOKIE[ $cookie_name ] );
		}
	}

	/**
	 * Get the WooCommerce blocks from an array of blocks.
	 *
	 * @param array $page_blocks Array of blocks returns by parse_blocks.
	 */
	private static function get_woocommerce_blocks( $page_blocks ) {
		$woo_blocks = preg_grep( '/^woocommerce\//', wp_list_pluck( $page_blocks, 'blockName' ) );

		foreach ( $page_blocks as $block ) {

			$block = (array) $block;

			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			if ( preg_match( '/^woocommerce\//', $block['blockName'] ) ) {
				// Only returns the parent WooCommerce blockName.
				continue;
			}

			$inner_blocks = isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ? $block['innerBlocks'] : []; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			if ( count( $inner_blocks ) ) {
				$woo_blocks = array_merge( $woo_blocks, self::get_woocommerce_blocks( $inner_blocks ) );
			}
		}

		return array_unique( array_filter( $woo_blocks ) );
	}

	/**
	 * Check if it's a WooCommerce page.
	 *
	 * @return bool
	 */
	private static function is_woo() {

		if ( is_customize_preview() ) {
			return false;
		}

		if ( ! (
			did_action( 'before_woocommerce_init' ) &&
			function_exists( 'is_woocommerce' ) &&
			function_exists( 'is_cart' ) &&
			function_exists( 'is_checkout' )
		) ) {
			return false;
		}

		$notice_shop_display = wc_string_to_bool( WC_BOGOF_Mods::get( 'notice_shop_display' ) );

		if ( is_cart() || is_checkout() || ( $notice_shop_display && is_woocommerce() ) ) {
			return true;
		}

		if ( function_exists( 'parse_blocks' ) ) {

			$blocks = self::get_woocommerce_blocks( parse_blocks( get_the_content() ) );

			if ( count( array_intersect( $blocks, [ 'woocommerce/cart', 'woocommerce/checkout' ] ) ) || ( count( $blocks ) && $notice_shop_display ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Register/queue frontend scripts.
	 */
	public static function load_scripts() {

		if ( ! self::is_woo() ) {
			return;
		}

		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$site_hash = md5( get_current_blog_id() . '_' . get_site_url( get_current_blog_id(), '/' ) );
		$deps      = array_merge(
			[ 'jquery', 'wc-bogof-modal', 'js-cookie' ],
			wc_string_to_bool( WC_BOGOF_Mods::get( 'show_single_variations' ) ) ? [] : [ 'wc-add-to-cart-variation' ]
		);

		wp_enqueue_style( 'wc-bogof-modal-gifts', plugins_url( 'assets/css/modal-gifts.css', WC_BOGOF_PLUGIN_FILE ), [], WC_Buy_One_Get_One_Free::VERSION );
		wp_enqueue_style( 'wc-bogof-modal', plugins_url( 'assets/css/modal.css', WC_BOGOF_PLUGIN_FILE ), [], WC_Buy_One_Get_One_Free::VERSION );

		wp_register_script( 'wc-bogof-modal', plugins_url( 'assets/js/admin/modal' . $suffix . '.js', WC_BOGOF_PLUGIN_FILE ), [ 'jquery' ], WC_Buy_One_Get_One_Free::VERSION, true );
		wp_register_script( 'wc-bogof-modal-gifts', plugins_url( 'assets/js/build/modal-gifts.js', WC_BOGOF_PLUGIN_FILE ), $deps, WC_Buy_One_Get_One_Free::VERSION, true );
		wp_localize_script(
			'wc-bogof-modal-gifts',
			'wc_bogof_modal_gifts_params',
			array(
				'wc_ajax_url'   => WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'gift_hash_key' => "wc_bogof_gift_hash_key{$site_hash}",
				'fragment_name' => "wc_bogof_fragment{$site_hash}",
				'notice_style'  => WC_BOGOF_Mods::get( 'notice_style' ),
			)
		);
		wp_enqueue_script( 'wc-bogof-modal-gifts' );
		wp_add_inline_style(
			'wc-bogof-modal-gifts',
			WC_BOGOF_Mods::generate_css_vars()
		);

		// Output templates on footer.
		add_action( 'wp_footer', 'wc_bogof_gift_template_notice', 0 );
		add_action( 'wp_footer', 'wc_bogof_gift_modal_dialog', 0 );
	}

	/**
	 * Returns the loop fragment.
	 *
	 * @param WC_BOGOF_Gifts_Loop $loop Gift loop instance.
	 */
	private static function get_gift_loop_fragment( $loop ) {

		$fragments = [
			'div.wc-bogo-modal-header>h3'     => esc_html( str_replace( '[qty]', $loop->get_available_free_quantity(), WC_BOGOF_Mods::get( 'header_title' ) ) ),
			'div.wc-bogof-products'           => $loop->get_the_loop(),
			'div.wc-bogof-load-more-products' => '',
		];

		if ( $loop->get_page() < $loop->get_num_pages() ) {
			$fragments['div.wc-bogof-load-more-products'] = wc_bogof_gift_template_load_more( $loop->get_page() + 1 );
		}

		return $fragments;
	}

	/**
	 * Returns the gifts hash.
	 *
	 * @param WC_BOGOF_Gifts_Loop $loop Gift loop instance.
	 */
	private static function get_gifts_hash( $loop ) {
		return $loop->get_query_hash() . '.' . $loop->get_available_free_quantity() . '.' . WC_BOGOF_Mods::version();
	}

	/**
	 * Returns the loop fragment.
	 */
	private static function get_minicart_fragments() {
		ob_start();
		woocommerce_mini_cart();
		$mini_cart = ob_get_clean();

		return [
			'fragments' => apply_filters(
				'woocommerce_add_to_cart_fragments',
				array(
					'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
				)
			),
			'cart_hash' => WC()->cart->get_cart_hash(),
		];
	}

	/**
	 * AJAX gift fragments.
	 */
	public static function get_gift_fragments() {

		$postdata = wc_clean( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$page     = isset( $postdata['page'] ) ? absint( $postdata['page'] ) : 1;

		$gift_loop  = new WC_BOGOF_Gifts_Loop( wc_bogof_cart_rules(), $page );
		$gifts_hash = '';
		$fragments  = [];

		if ( $gift_loop->get_total() > 0 ) {

			$gifts_hash = self::get_gifts_hash( $gift_loop );

			$fragments = [
				'announcement' => wc_bogof_gift_notice_text(
					$gift_loop->get_available_free_quantity(),
					$gift_loop->get_product_percentage_discounts()
				),
				'modal'        => self::get_gift_loop_fragment( $gift_loop ),
			];
		}

		wp_send_json(
			[
				'fragments'  => $fragments,
				'gifts_hash' => $gifts_hash,
			]
		);
	}

	/**
	 * AJAX add gift to the cart.
	 */
	public static function add_gift_to_cart() {

		$postdata = wc_clean( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! isset( $postdata['product_id'] ) ) {
			wp_send_json_error( 'ID field is required.', 400 );
		}

		$product_id = absint( $postdata['product_id'] );
		$product    = wc_get_product( $product_id );

		if ( ! $product ) {
			wp_send_json_error( "Product #{$product_id} not found.", 400 );
		}

		$cart_item_data = [];

		foreach ( wc_bogof_cart_rules() as $cart_rule ) {
			if ( $cart_rule->is_shop_avilable_free_product( $product_id ) ) {
				// Add the reference.
				$cart_item_data['_bogof_free_item'] = $cart_rule->get_id();
				break;
			}
		}

		if ( empty( $cart_item_data ) ) {
			// It does not a gift. Exit.
			wp_send_json_error( 'Bad request.', 400 );
		}

		$variation_id = 0;
		$variation    = [];

		if ( 'variation' === $product->get_type() ) {
			$variation_id = $product_id;
			$product_id   = $product->get_parent_id();
			$variation    = $product->get_variation_attributes();
		}

		$cart_item_id = WC()->cart->add_to_cart( $product_id, 1, $variation_id, $variation, $cart_item_data );

		if ( false !== $cart_item_id ) {

			$gift_loop = new WC_BOGOF_Gifts_Loop( wc_bogof_cart_rules(), 1 );

			$response = [
				'destroy'        => false,
				'fragments'      => [
					'modal'        => [],
					'announcement' => '',
				],
				'cart_fragments' => self::get_minicart_fragments(),
			];

			if ( $gift_loop->get_available_free_quantity() < 1 ) {
				// Destroy.
				$response['destroy'] = true;

			} else {

				$response['gifts_hash']                = self::get_gifts_hash( $gift_loop );
				$response['fragments']['announcement'] = wc_bogof_gift_notice_text(
					$gift_loop->get_available_free_quantity(),
					$gift_loop->get_product_percentage_discounts()
				);

				$cookie_hash_pieces = explode( '.', isset( $_COOKIE['wc_bogof_gifts_hash'] ) ? wc_clean( wp_unslash( $_COOKIE['wc_bogof_gifts_hash'] ) ) : '' );

				if ( $cookie_hash_pieces[0] !== $gift_loop->get_query_hash() ) {
					// Refresh the gift products.
					$response['fragments']['modal'] = self::get_gift_loop_fragment( $gift_loop );
				} else {
					$response['fragments']['modal']['div.wc-bogo-modal-header>h3'] = esc_html( str_replace( '[qty]', $gift_loop->get_available_free_quantity(), WC_BOGOF_Mods::get( 'header_title' ) ) );
				}

				// Update the gift cookie.
				self::maybe_set_gift_cookie();
			}

			$response['fragments']['modal'][ sprintf( 'div.wc-bogof-products button.__product-%s', ( $product instanceof WC_Product_Variation && ! wc_string_to_bool( WC_BOGOF_Mods::get( 'show_single_variations' ) ) ? $product->get_parent_id() : $product->get_id() ) ) ] = wc_bogof_gift_template_add_to_cart_button_label( $product );

			wp_send_json( $response );

		} else {

			// Get the error.
			$all_notices = WC()->session->get( 'wc_notices', array() );
			$message     = __( 'There was an error adding the product to the cart. Please try again', 'wc-buy-one-get-one-free' );

			if ( isset( $all_notices['error'] ) && is_array( $all_notices['error'] ) ) {
				$notice  = array_pop( $all_notices['error'] );
				$message = $notice['notice'];

				WC()->session->set( 'wc_notices', $all_notices );
			}

			wp_send_json_error( $message, 500 );
		}
	}
}
