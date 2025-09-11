<?php
/**
 * Array condition functions.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Array Trait
 */
trait WC_BOGOF_Condition_Array {

	/**
	 * Returns a key => title array of modifiers.
	 *
	 * @return array
	 */
	public function get_modifiers() {
		return [
			'in'     => __( 'In list', 'wc-buy-one-get-one-free' ),
			'not-in' => __( 'Not in list', 'wc-buy-one-get-one-free' ),
		];
	}

	/**
	 * Returns an array with the proprerties of the metabox field.
	 *
	 * @return array
	 */
	public function get_value_metabox_field() {
		return [
			'type'        => 'enhanced-select',
			'options'     => $this->get_metabox_field_options(),
			'placeholder' => __( 'Choose', 'wc-buy-one-get-one-free' ) . '&hellip;',
		];
	}

	/**
	 * Returns the "options" of the metabox field.
	 *
	 * @return array
	 */
	protected function get_metabox_field_options() {
		return [];
	}

	/**
	 * Return the condition as string.
	 *
	 * @param array $data Condition field data.
	 * @return string
	 */
	public function to_string( $data ) {
		$data          = $this->sanitize( $data );
		$data['value'] = $this->get_formatted_values( $data['value'] );
		return parent::to_string( $data );
	}

	/**
	 * Retruns the formatted values.
	 *
	 * @param array $values Values to formatted.
	 * @return array
	 */
	protected function get_formatted_values( $values ) {
		return $values;
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
		$validate = wc_bogof_in_array_intersect( $value, $compare );

		if ( 'not-in' === $modifier ) {
			$validate = ! $validate;
		}
		return $validate;
	}
}

