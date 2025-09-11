<?php
/**
 * WooCommerce Buy One Get One Free capture email on checkout.
 * This is required for the customer email condition.
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Cart Class
 */
class WC_BOGOF_Checkout_Capture_Email {

	/**
	 * Init hooks
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'load_scripts' ], 20 );
		add_action( 'woocommerce_checkout_update_order_review', [ __CLASS__, 'capture_email' ] );
	}

	/**
	 * Returns whether or not to need to capture the email on the checkout page.
	 *
	 * @return bool
	 */
	private static function needs_capture() {
		$cache_key = strtolower( __METHOD__ ) . WC_Cache_Helper::get_transient_version( 'bogof_rules' );
		$value     = wp_cache_get( $cache_key, 'wc_bogof' );

		if ( false === $value ) {

			$data_store = WC_Data_Store::load( 'bogof-rule' );
			$rules      = array_filter( $data_store->get_rules(), [ __CLASS__, 'filter_callback' ] );
			$value      = count( $rules ) ? 'yes' : 'no';

			wp_cache_set( $cache_key, $value, 'wc_bogof' );
		}

		return wc_string_to_bool( $value );
	}

	/**
	 * Rules filter callback.
	 *
	 * @param WC_BOGOF_Rule $rule The rule instance.
	 */
	private static function filter_callback( $rule ) {
		if ( ! $rule->is_enabled() ) {
			return false;
		}

		$conditions = $rule->get_conditions();

		foreach ( $conditions as $group ) {
			if ( in_array( 'customer_email', wp_list_pluck( $group, 'type' ), true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Register/queue script.
	 */
	public static function load_scripts() {
		if ( ! ( is_checkout() && self::needs_capture() ) ) {
			return;
		}

		wp_register_script( 'wc-bogof-capture-email', plugins_url( 'assets/js/build/capture-email.js', WC_BOGOF_PLUGIN_FILE ), [ 'wc-checkout' ], WC_Buy_One_Get_One_Free::VERSION, true );
		wp_localize_script(
			'wc-bogof-capture-email',
			'wc_bogof_capture_email_params',
			[
				'emailSelectors' => apply_filters(
					'wc_bogof_capture_email_selectors',
					[
						'.woocommerce-checkout [type="email"]',
						'#billing_email',
						'input[name="billing_email"]',
					]
				),
			]
		);
		wp_enqueue_script( 'wc-bogof-capture-email' );
	}

	/**
	 * Capture billing email on "update order review".
	 *
	 * @param string $post_data Post data as query string.
	 */
	public static function capture_email( $post_data ) {

		if ( ! self::needs_capture() ) {
			return;
		}

		$data = $post_data;

		if ( is_string( $post_data ) ) {
			parse_str( $post_data, $data );
		}

		if ( isset( $data['billing_email'] ) ) {
			WC()->customer->set_billing_email( wc_clean( $data['billing_email'] ) );
		}
	}

}



