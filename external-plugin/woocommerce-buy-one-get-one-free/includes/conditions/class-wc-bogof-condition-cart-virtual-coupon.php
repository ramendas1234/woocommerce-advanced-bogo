<?php
/**
 * Condition cart virtual coupon class.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Cart_Virtual_Coupon Class
 */
class WC_BOGOF_Condition_Cart_Virtual_Coupon extends WC_BOGOF_Abstract_Condition implements WC_BOGOF_Condition_Coupon_Interface {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id       = 'cart_virtual_coupon';
		$this->title    = sprintf(
			'%s - %s',
			__( 'Cart', 'wc-buy-one-get-one-free' ),
			__( 'Virtual coupon', 'wc-buy-one-get-one-free' )
		);
		$this->supports = array( '_conditions' );
	}

	/**
	 * Returns a key => title array of modifiers.
	 *
	 * @return array
	 */
	public function get_modifiers() {
		return [
			'is' => __( 'Is', 'wc-buy-one-get-one-free' ),
		];
	}

	/**
	 * Returns an array with the proprerties of the metabox field.
	 *
	 * @return array
	 */
	public function get_value_metabox_field() {
		return [
			'type'              => 'text',
			'custom_attributes' => [
				'placeholder'        => __( 'Enter the coupon code', 'wc-buy-one-get-one-free' ),
				'data-info-tooltips' => wp_json_encode(
					[
						[
							'message' => __( "A virtual coupon is a code that is valid as a coupon, but it's not stored in Marketing > Coupons. It's helpful if you want the user to add a code to get the promotion and don't want to create a discount coupon from Marketing > Coupons.", 'wc-buy-one-get-one-free' ),
						],
					]
				),
			],
		];
	}

	/**
	 * Evaluate a condition field.
	 *
	 * @param array $data   Condition field data.
	 * @param mixed $value  The WC_BOGOF_Rule instance.
	 * @return boolean
	 */
	public function check_condition( $data, $value = null ) {
		$data = $this->sanitize( $data );

		$applied_coupons = is_null( WC()->cart ) ? [] : array_map( 'strtolower', WC()->cart->get_applied_coupons() );

		return in_array( strtolower( $data['value'] ), $applied_coupons, true );
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
		if ( ! empty( $data['value'] ) ) {
			$coupons = [ strtolower( $data['value'] ) ];
		}
		return $coupons;
	}
}
