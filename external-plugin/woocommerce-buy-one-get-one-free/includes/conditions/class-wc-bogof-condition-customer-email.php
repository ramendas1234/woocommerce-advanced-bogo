<?php
/**
 * Condition customer email class.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Customer_Email Class
 */
class WC_BOGOF_Condition_Customer_Email extends WC_BOGOF_Abstract_Condition {
	use WC_BOGOF_Condition_String;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id       = 'customer_email';
		$this->title    = sprintf(
			'%s - %s',
			__( 'Customer', 'wc-buy-one-get-one-free' ),
			__( 'Email', 'wc-buy-one-get-one-free' )
		);
		$this->supports = array( '_conditions' );
	}

	/**
	 * Evaluate a condition field.
	 *
	 * @param array $data   Condition field data.
	 * @param mixed $value  Value to check.
	 * @return boolean
	 */
	public function check_condition( $data, $value = null ) {
		if ( ! WC()->customer ) {
			return false;
		}

		$data  = $this->sanitize( $data );
		$check = false;
		foreach ( [ 'get_email', 'get_billing_email' ] as $getter ) {
			if ( is_callable( [ WC()->customer, $getter ] ) ) {

				$email = WC()->customer->{$getter}();

				if ( $email ) {

					$check = $this->validate( $email, $data['value'], $data['modifier'] );

					if ( $check ) {
						break;
					}
				}
			}
		}

		return $check;
	}
}
