<?php
/**
 * Integer condition functions.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Integer Trait
 */
trait WC_BOGOF_Condition_Integer {

	/**
	 * Returns a key => title array of modifiers.
	 *
	 * @return array
	 */
	public function get_modifiers() {
		return [
			'greater_than'    => __( 'Is greater than', 'wc-buy-one-get-one-free' ),
			'less_than'       => __( 'Is less than', 'wc-buy-one-get-one-free' ),
			'is'              => __( 'Is', 'wc-buy-one-get-one-free' ),
			'is_not'          => __( 'Is not', 'wc-buy-one-get-one-free' ),
			'multiple_of'     => __( 'Is a multiple of', 'wc-buy-one-get-one-free' ),
			'not_multiple_of' => __( 'Is not a multiple of', 'wc-buy-one-get-one-free' ),
		];
	}

	/**
	 * Returns an array with the proprerties of the metabox field.
	 *
	 * @return array
	 */
	public function get_value_metabox_field() {
		return [
			'type'              => 'number',
			'custom_attributes' => [
				'step' => 1,
				'min'  => 0,
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

		$value   = intval( $value );
		$compare = intval( $compare );

		switch ( $modifier ) {

			case 'is':
				return ( $value === $compare );

			case 'is_not':
				return ( $value !== $compare );

			case 'greater_than':
				return ( $value > $compare );

			case 'less_than':
				return ( $value < $compare );

			case 'multiple_of':
				return ( $compare > 0 ? ( 0 === $value % $compare ) : false );

			case 'not_multiple_of':
				return ( $compare > 0 ? ( 0 !== $value % $compare ) : false );
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

		$data = parent::sanitize( $data );

		if ( ! is_numeric( $data['value'] ) ) {
			$data['value'] = '';
		} else {
			$data['value'] = intval( $data['value'] );
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
}
