<?php
/**
 * Generic class fro customer address property conditions.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Customer_Address Class
 */
trait WC_BOGOF_Condition_Customer_Address {

	/**
	 * Address type.
	 *
	 * @var string
	 */
	protected $address_type;

	/**
	 * Address property.
	 *
	 * @var string
	 */
	protected $address_prop;

	/**
	 * Returns the address property.
	 */
	protected function get_address_property() {
		$value  = '';
		$getter = "get_{$this->address_type}_{$this->address_prop}";
		$value  = is_callable( [ WC()->customer, $getter ] ) ? WC()->customer->{$getter}() : '';

		if ( empty( $value ) && 'shipping' === $this->address_prop ) {
			// Fallback to billing.
			$getter = "get_billing_{$this->address_prop}";
			$value  = is_callable( [ WC()->customer, $getter ] ) ? WC()->customer->{$getter}() : '';
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

		if ( ! WC()->customer ) {
			return false;
		}

		$data = $this->sanitize( $data );

		return $this->validate( $this->get_address_property(), $data['value'], $data['modifier'] );
	}
}
