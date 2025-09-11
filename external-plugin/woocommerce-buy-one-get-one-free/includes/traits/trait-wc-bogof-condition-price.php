<?php
/**
 * Price condition functions.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Price Trait
 */
trait WC_BOGOF_Condition_Price {

	/**
	 * Returns a key => title array of modifiers.
	 *
	 * @return array
	 */
	public function get_modifiers() {
		return [
			'greater_than' => __( 'Is greater than', 'wc-buy-one-get-one-free' ),
			'less_than'    => __( 'Is less than', 'wc-buy-one-get-one-free' ),
		];
	}

	/**
	 * Returns an array with the proprerties of the metabox field.
	 *
	 * @return array
	 */
	public function get_value_metabox_field() {
		return [
			'type'              => 'input-price',
			'custom_attributes' => [
				'placeholder' => wc_format_localized_price( '0.00' ),
			],
		];
	}

	/**
	 * Validate the rule.
	 *
	 * @param int    $value Condition value.
	 * @param int    $compare Compare value.
	 * @param string $modifier Comparison method.
	 * @return bool
	 */
	protected function validate( $value, $compare, $modifier ) {

		$value   = floatval( $value );
		$compare = floatval( $compare );

		switch ( $modifier ) {

			case 'greater_than':
				return ( $value > $compare );

			case 'less_than':
				return ( $value < $compare );
		}
		return false;
	}

	/**
	 * Sanitize the condition data array.
	 *
	 * @param array $data Array that contains the condition data.
	 * @return array
	 */
	public function sanitize( $data ) {
		$data          = parent::sanitize( $data );
		$data['value'] = isset( $data['value'] ) ? wc_format_decimal( $data['value'] ) : '';

		if ( ! is_numeric( $data['value'] ) ) {
			$data['value'] = '';
		}

		return $data;
	}

	/**
	 * Is the condition data empty?
	 *
	 * @param array $data Array that contains the condition data.
	 * @return bool
	 */
	public function is_empty( $data ) {
		return empty( $data['type'] ) || empty( $data['modifier'] ) || ! isset( $data['value'] ) || strlen( strval( $data['value'] ) ) < 1;
	}

	/**
	 * Convert the amount to the shop base currency is multicurrency is enable.
	 *
	 * @since 5.1.3
	 * @param double $amount Amount to filter.
	 */
	protected function multicurrency_to_base( $amount ) {
		return class_exists( 'WC_BOGOF_Multi_Currency' ) ? WC_BOGOF_Multi_Currency::to_base( $amount ) : $amount;
	}
}
