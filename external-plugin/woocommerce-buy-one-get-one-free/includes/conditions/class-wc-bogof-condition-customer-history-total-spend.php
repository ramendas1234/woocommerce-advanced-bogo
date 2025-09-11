<?php
/**
 * Condition customer history total spend class.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Customer_History_Total_Spend Class
 */
class WC_BOGOF_Condition_Customer_History_Total_Spend extends WC_BOGOF_Abstract_Condition {
	use WC_BOGOF_Condition_Price;
	use WC_BOGOF_Condition_Customer_History;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id       = 'customer_history_total_spend';
		$this->title    = sprintf(
			'%s - %s',
			__( 'Customer', 'wc-buy-one-get-one-free' ),
			__( 'Total revenue', 'wc-buy-one-get-one-free' )
		);
		$this->supports = array( '_conditions' );
		$this->property = 'total_spend';
	}
}
