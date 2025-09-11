<?php
/**
 * BOGO legacy metabox fields.
 *
 * Returns an array of fields.
 *
 * @var WC_BOGOF_Rule $rule The current BOGO rule.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $rule ) ) {
	return [];
}

return [

	/**
	 * Coupon validations
	 */
	array(
		'id'      => '_exclude_coupon_validation',
		'label'   => __( 'No coupon validations', 'wc-buy-one-get-one-free' ),
		'message' => __( 'Check this box to do not apply the coupon restrictions to the free items (recommended)', 'wc-buy-one-get-one-free' ),
		'value'   => $rule->get_exclude_coupon_validation(),
		'type'    => 'true-false',
	),
];
