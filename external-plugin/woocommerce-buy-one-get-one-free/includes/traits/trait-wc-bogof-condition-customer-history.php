<?php
/**
 * Generic class fro customer history conditions.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\Customers\Query as CustomersQuery;
use Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;

/**
 * WC_BOGOF_Condition_Customer_History Class
 */
trait WC_BOGOF_Condition_Customer_History {

	/**
	 * Data property.
	 *
	 * @var string
	 */
	protected $property;

	/**
	 * Returns the wc_customer_lookup ID.
	 */
	protected function get_customer_id() {

		if ( ! WC()->customer ) {
			return false;
		}

		$customer_id = false;

		if ( WC()->customer->get_id() && WC()->customer->get_id() > 0 ) {

			$customer_id = CustomersDataStore::get_customer_id_by_user_id( WC()->customer->get_id() );

		} elseif ( WC()->customer->get_billing_email() ) {

			$customer_id = CustomersDataStore::get_guest_id_by_email( WC()->customer->get_billing_email() );

		} elseif ( WC()->customer->get_email() ) {

			$customer_id = CustomersDataStore::get_guest_id_by_email( WC()->customer->get_email() );
		}

		return $customer_id;
	}

	/**
	 * Returns the address property.
	 */
	protected function get_customer_history_value() {
		$customer_id = $this->get_customer_id();

		if ( ! $customer_id ) {
			return 0;
		}

		$value = 0;
		$query = new CustomersQuery(
			[
				'customers'    => array( $customer_id ),
				// If unset, these params have default values that affect the results.
				'order_after'  => null,
				'order_before' => null,
			]
		);

		$data = $query->get_data()->data;

		if ( isset( $data[0] ) && is_array( $data[0] ) && isset( $data[0][ $this->property ] ) ) {
			$value = floatval( $data[0][ $this->property ] );
		}

		return $value;
	}

	/**
	 * Evaluate a condition field.
	 *
	 * @param array $data   Condition field data.
	 * @param mixed $value  Value to check.
	 * @return boolean
	 */
	public function check_condition( $data, $value = null ) {
		$data = $this->sanitize( $data );
		return $this->validate( $this->get_customer_history_value(), $data['value'], $data['modifier'] );
	}
}
