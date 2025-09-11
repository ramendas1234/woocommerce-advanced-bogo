<?php
/**
 * Condition cart coupons class.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Cart_Coupons Class
 */
class WC_BOGOF_Condition_Cart_Coupons extends WC_BOGOF_Abstract_Condition implements WC_BOGOF_Condition_Coupon_Interface {
	use WC_BOGOF_Condition_Array;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id       = 'cart_coupons';
		$this->title    = sprintf(
			'%s - %s',
			__( 'Cart', 'wc-buy-one-get-one-free' ),
			__( 'Coupons', 'wc-buy-one-get-one-free' )
		);
		$this->supports = array( '_conditions' );
	}

	/**
	 * Returns an array with the proprerties of the metabox field.
	 *
	 * @return array
	 */
	public function get_value_metabox_field() {
		return [
			'type' => 'search-coupon',
		];
	}

	/**
	 * Evaluate a condition field.
	 *
	 * @param array $data   Condition field data.
	 * @param mixed $value  Value to check.
	 * @return boolean
	 */
	public function check_condition( $data, $value = null ) {
		$data            = $this->sanitize( $data );
		$coupons         = empty( $data['value'] ) ? [] : $this->get_formatted_values( $data['value'] );
		$applied_coupons = is_null( WC()->cart ) ? [] : WC()->cart->get_applied_coupons();

		return $this->validate( $applied_coupons, $coupons, $data['modifier'] );
	}

	/**
	 * Sanitize the condition data array.
	 *
	 * @param array $data Array that contains the condition data.
	 * @return array
	 */
	public function sanitize( $data ) {
		$data          = parent::sanitize( $data );
		$data['value'] = is_array( $data['value'] ) ? array_filter( array_map( 'absint', $data['value'] ) ) : [];

		return $data;
	}

	/**
	 * Returns the coupon codes from they IDs.
	 *
	 * @param array $ids Coupons IDs.
	 * @return array
	 */
	protected function get_formatted_values( $ids ) {
		$cache_key = WC_Cache_Helper::get_cache_prefix( 'bogof_coupon_codes' ) . implode( '.', $ids );
		$codes     = wp_cache_get( $cache_key );

		if ( false !== $codes && is_array( $codes ) ) {
			return $codes;
		}

		$codes = [];
		$posts = get_posts(
			array(
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post__in'       => $ids,
			)
		);

		foreach ( $posts as $post ) {
			$codes[] = function_exists( 'wc_format_coupon_code' ) ? wc_format_coupon_code( $post->post_title ) : $post->post_title;
		}

		wp_cache_set( $cache_key, $codes );

		return $codes;
	}

	/**
	 * Method that returns the coupons that need to be validate from a condition data.
	 *
	 * @param array $data Condition data.
	 * @return array
	 */
	public function get_coupons_from_data( $data ) {
		$data    = $this->sanitize( $data );
		$coupons = [];
		if ( $this->modifier_is( $data, 'in' ) && is_array( $data['value'] ) ) {
			$coupons = array_map( 'strtolower', $this->get_formatted_values( $data['value'] ) );
		}
		return $coupons;
	}
}
