<?php
/**
 * Condition usage limit per user class.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Promotion_Usage_Limit Class
 */
class WC_BOGOF_Condition_Promotion_Usage_Limit extends WC_BOGOF_Abstract_Condition {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id       = 'promotion_usage_limit';
		$this->title    = sprintf(
			'%s - %s',
			__( 'Promotion', 'wc-buy-one-get-one-free' ),
			__( 'Usage limit', 'wc-buy-one-get-one-free' )
		);
		$this->supports = array( '_conditions' );
	}

	/**
	 * Returns a key => title array of modifiers.
	 *
	 * @return array
	 */
	public function get_modifiers() {
		return [
			'per_user'      => __( 'Per user', 'wc-buy-one-get-one-free' ),
			'per_promotion' => __( 'Per promotion', 'wc-buy-one-get-one-free' ),
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
				'step'               => 1,
				'min'                => 0,
				'data-info-tooltips' => wp_json_encode(
					[
						[
							'message' => __( 'How many times this promotion can be used by an individual user.', 'wc-buy-one-get-one-free' ) . ' ' . __( 'Uses billing email for guests, and user ID for logged in users', 'wc-buy-one-get-one-free' ),
							'show_if' => [
								[
									'operator' => '=',
									'value'    => 'per_user',
								],
							],
						],
						[
							'message' => __( 'How many times this promotion can be used.', 'wc-buy-one-get-one-free' ),
							'show_if' => [
								[
									'operator' => '=',
									'value'    => 'per_promotion',
								],
							],
						],
					]
				),
			],
		];
	}

	/**
	 * Evaluate a condition field.
	 *
	 * @param array $data   Condition field data.
	 * @param mixed $value  The WC_BOGOF_Rule instance.
	 * @return boolean
	 */
	public function check_condition( $data, $value = null ) {
		$data = $this->sanitize( $data );

		if ( $this->is_empty( $data ) || ! is_a( $value, 'WC_BOGOF_Rule' ) ) {
			return false;
		}

		$total_uses = 0;

		if ( 'per_user' === $data['modifier'] ) {

			$user_ids = wc_bogof_user_ids();

			if ( ! empty( $user_ids ) ) {
				$total_uses = $value->get_usage_count( $user_ids );
			}
		} else {
			$total_uses = $value->get_usage_count( null );
		}

		return $total_uses < $data['value'];
	}

	/**
	 * Sanitize the condition data array.
	 *
	 * @param array $data Array that contains the condition data.
	 * @return array
	 */
	public function sanitize( $data ) {
		$data          = parent::sanitize( $data );
		$data['value'] = isset( $data['value'] ) ? absint( $data['value'] ) : '';

		return $data;
	}

	/**
	 * Is the condition data empty?
	 *
	 * @param array $data Array that contains the condition data.
	 * @return bool
	 */
	public function is_empty( $data ) {
		return empty( $data['type'] ) || empty( $data['modifier'] ) || ! isset( $data['value'] ) || strlen( $data['value'] ) < 1;
	}
}
