<?php
/**
 * Condition customer billing country class.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Customer_Billing_Country Class
 */
class WC_BOGOF_Condition_Customer_Billing_Country extends WC_BOGOF_Abstract_Condition {
	use WC_BOGOF_Condition_Array;
	use WC_BOGOF_Condition_Customer_Address;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id           = 'customer_billing_country';
		$this->title        = sprintf(
			'%s - %s',
			__( 'Customer', 'wc-buy-one-get-one-free' ),
			__( 'Billing country', 'wc-buy-one-get-one-free' )
		);
		$this->supports     = array( '_conditions' );
		$this->address_type = 'billing';
		$this->address_prop = 'country';
	}

	/**
	 * Returns the "options" of the metabox field.
	 *
	 * @return array
	 */
	protected function get_metabox_field_options() {
		return WC()->countries->countries;
	}
}

