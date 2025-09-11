<?php
/**
 * Condition All Product for WooCommerce Subscription class.
 *
 * @since 3.3.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Product Class
 */
class WC_BOGOF_Condition_WCS_ATT extends WC_BOGOF_Abstract_Condition {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id       = 'wcs_att';
		$this->title    = __( 'All Products for Subscription', 'wc-buy-one-get-one-free' );
		$this->supports = array( '_applies_to' );
	}

	/**
	 * Evaluate condition field.
	 *
	 * @param array $data Condition field data.
	 * @param mixed $value Value to check.
	 * @return boolean
	 */
	public function check_condition( $data, $value = null ) {
		if ( ! is_array( $value ) ) {
			return false;
		}

		return ( empty( $value['wcsatt_data']['active_subscription_scheme'] ) && $this->modifier_is( $data, 'one-time' ) ) ||
			( ! empty( $value['wcsatt_data']['active_subscription_scheme'] ) && $this->modifier_is( $data, 'subscription' ) );
	}

	/**
	 * Returns a key => title array of modifiers.
	 *
	 * @return array
	 */
	public function get_modifiers() {
		return array(
			'one-time'     => __( 'Is one-time purchase', 'wc-buy-one-get-one-free' ),
			'subscription' => __( 'Is a subscription plan', 'wc-buy-one-get-one-free' ),
		);
	}

	/**
	 * Returns an array with the proprerties of the metabox field.
	 *
	 * @return array
	 */
	public function get_value_metabox_field() {
		return array();
	}

	/**
	 * Is the condition data empty?
	 *
	 * @param array $data Array that contains the condition data.
	 * @return bool
	 */
	public function is_empty( $data ) {
		return empty( $data['type'] );
	}
}
