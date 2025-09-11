<?php
/**
 * Condition Coupon Interface
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Coupon_Interface
 */
interface WC_BOGOF_Condition_Coupon_Interface {

	/**
	 * Method that returns the coupon codes that need to be validate from a condition data.
	 *
	 * @param array $data Condition data.
	 * @return array
	 */
	public function get_coupons_from_data( $data );
}
