<?php
/**
 * Condition customer history total orders class.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Customer_History_Total_Orders Class
 */
class WC_BOGOF_Condition_Customer_History_Total_Orders extends WC_BOGOF_Abstract_Condition {
	use WC_BOGOF_Condition_Integer;
	use WC_BOGOF_Condition_Customer_History;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id       = 'customer_history_orders_count';
		$this->title    = sprintf(
			'%s - %s',
			__( 'Customer', 'wc-buy-one-get-one-free' ),
			__( 'Total orders', 'wc-buy-one-get-one-free' )
		);
		$this->supports = array( '_conditions' );
		$this->property = 'orders_count';
	}
}
