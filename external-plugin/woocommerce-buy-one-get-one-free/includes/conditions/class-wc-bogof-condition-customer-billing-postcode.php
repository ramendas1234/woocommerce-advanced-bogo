<?php
/**
 * Condition customer billing Postcode class.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Customer_Billing_Postcode Class
 */
class WC_BOGOF_Condition_Customer_Billing_Postcode extends WC_BOGOF_Abstract_Condition {
	use WC_BOGOF_Condition_String;
	use WC_BOGOF_Condition_Customer_Address;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id           = 'customer_billing_postcode';
		$this->title        = sprintf(
			'%s - %s',
			__( 'Customer', 'wc-buy-one-get-one-free' ),
			__( 'Billing postcode', 'wc-buy-one-get-one-free' )
		);
		$this->supports     = array( '_conditions' );
		$this->address_type = 'billing';
		$this->address_prop = 'postcode';
	}
}
