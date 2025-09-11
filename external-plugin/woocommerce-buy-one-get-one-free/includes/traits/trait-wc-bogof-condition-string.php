<?php
/**
 * String condition functions.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_String Trait
 */
trait WC_BOGOF_Condition_String {

	/**
	 * Returns a key => title array of modifiers.
	 *
	 * @return array
	 */
	public function get_modifiers() {
		return array(
			'is'           => __( 'Is', 'wc-buy-one-get-one-free' ),
			'is_not'       => __( 'Is not', 'wc-buy-one-get-one-free' ),
			'contains'     => __( 'Contains', 'wc-buy-one-get-one-free' ),
			'not_contains' => __( 'Does not contain', 'wc-buy-one-get-one-free' ),
			'starts_with'  => __( 'Starts with', 'wc-buy-one-get-one-free' ),
			'ends_with'    => __( 'Ends with', 'wc-buy-one-get-one-free' ),
		);
	}

	/**
	 * Returns an array with the proprerties of the metabox field.
	 *
	 * @return array
	 */
	public function get_value_metabox_field() {
		$help_tip = __( 'It supports multiple values separated by commas. You can also use an asterisk (*) to match part of the text. For example, "wo*com*"  would match with "woocommerce" and "woo.com".', 'wc-buy-one-get-one-free' );
		$show_if  = [];
		foreach ( array_keys( $this->get_modifiers() ) as $key ) {
			if ( ! in_array( $key, [ 'is', 'is_not' ], true ) ) {
				$show_if[] = [
					'operator' => '!=',
					'value'    => $key,
				];
			}
		}

		return [
			'type'              => 'text',
			'custom_attributes' => [
				'data-info-tooltips' => wp_json_encode(
					[
						[
							'message' => $help_tip,
							'show_if' => $show_if,
						],
					]
				),
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

		$value   = strtolower( strval( $value ) );
		$compare = strtolower( strval( $compare ) );

		if ( 0 === strlen( $compare ) ) {
			return false;
		}

		switch ( $modifier ) {

			case 'is':
				return $this->csv_match( $compare, $value );

			case 'is_not':
				return ! $this->csv_match( $compare, $value );

			case 'contains':
				return strstr( $value, $compare ) !== false;

			case 'not_contains':
				return strstr( $value, $compare ) === false;

			case 'starts_with':
				return $this->starts_with( $value, $compare );

			case 'ends_with':
				return $this->ends_with( $value, $compare );
		}

		return false;
	}

	/**
	 * Checks if a string starts with a given substring
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle The substring to search for in the haystack.
	 * @return bool
	 */
	protected function starts_with( $haystack, $needle ) {
		if ( is_callable( 'str_starts_with' ) ) {
			return str_starts_with( $haystack, $needle );
		} else {
			return substr( $haystack, 0, strlen( $needle ) ) === $needle;
		}
	}

	/**
	 * Checks if a string ends with a given substring
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle The substring to search for in the haystack.
	 * @return bool
	 */
	protected function ends_with( $haystack, $needle ) {
		if ( is_callable( 'str_ends_with' ) ) {
			return str_ends_with( $haystack, $needle );
		} else {

			$length = strlen( $needle );

			if ( 0 === $length ) {
				return true;
			}

			return substr( $haystack, -$length ) === $needle;
		}
	}

	/**
	 * Checks if a string match in a CSV string. Supports "*" as wildcard.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle The substring to search for in the haystack.
	 * @return bool
	 */
	protected function csv_match( $haystack, $needle ) {
		$values = array_map( 'trim', explode( ',', $haystack ) );

		foreach ( $values as $value ) {
			// Convert to PHP-regex syntax.
			$regex = '/^' . str_replace( '*', '(.+)?', $value ) . '$/';
			preg_match( $regex, $needle, $match );

			if ( ! empty( $match ) ) {
				return true;
			}
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
		$data['value'] = isset( $data['value'] ) ? strval( $data['value'] ) : '';

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
