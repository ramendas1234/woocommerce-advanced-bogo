<?php
/**
 * BOGO rules metabox fields.
 *
 * Returns an array of fields.
 *
 * @var WC_BOGOF_Rule $rule The current BOGO rule.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $rule ) ) {
	return array();
}

return [

	/**
	 * Rules
	 */
	array(
		'id'          => '_conditions',
		'description' => __( 'Rules can be used to add conditional logic to promotions.', 'wc-buy-one-get-one-free' ),
		'type'        => 'conditional-logic',
		'value'       => $rule->get_conditions(),
		// Translators: 1, 2: HTML tags.
		'placeholder' => sprintf( __( 'Click the %1$sAdd condition%2$s button to create a rule.', 'wc-buy-one-get-one-free' ), '<strong> + ', '</strong>' ),
	),
];
