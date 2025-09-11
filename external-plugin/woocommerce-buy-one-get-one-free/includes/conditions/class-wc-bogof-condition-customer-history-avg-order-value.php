<?php
/**
 * Condition customer history Avg Order Value class.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Customer_History_Avg_Order_Value Class
 */
class WC_BOGOF_Condition_Customer_History_Avg_Order_Value extends WC_BOGOF_Abstract_Condition {
	use WC_BOGOF_Condition_Price;
	use WC_BOGOF_Condition_Customer_History;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id       = 'customer_history_avg_order_value';
		$this->title    = sprintf(
			'%s - %s',
			__( 'Customer', 'wc-buy-one-get-one-free' ),
			__( 'Average order value', 'wc-buy-one-get-one-free' )
		);
		$this->supports = array( '_conditions' );
		$this->property = 'avg_order_value';
	}
}
