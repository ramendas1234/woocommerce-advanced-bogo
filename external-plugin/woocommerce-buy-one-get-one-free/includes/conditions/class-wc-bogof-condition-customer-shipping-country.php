<?php
/**
 * Condition customer shipping country class.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Customer_Shipping_Country Class
 */
class WC_BOGOF_Condition_Customer_Shipping_Country extends WC_BOGOF_Condition_Customer_Billing_Country {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id           = 'customer_shipping_country';
		$this->title        = sprintf(
			'%s - %s',
			__( 'Customer', 'wc-buy-one-get-one-free' ),
			__( 'Shipping country', 'wc-buy-one-get-one-free' )
		);
		$this->supports     = array( '_conditions' );
		$this->address_type = 'shipping';
		$this->address_prop = 'country';
	}
}
