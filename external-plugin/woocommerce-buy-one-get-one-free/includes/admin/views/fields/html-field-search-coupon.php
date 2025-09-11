<?php
/**
 * Product search field.
 *
 * @var array $field Field data.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

$field['class']                            = 'wc-product-search';
$field['placeholder']                      = __( 'Search for a coupon&hellip;', 'wc-buy-one-get-one-free' );
$field['custom_attributes']['data-action'] = 'wc_bogof_json_search_coupons';
$field['options']                          = array();

if ( is_array( $field['value'] ) ) {
	foreach ( $field['value'] as $object_id ) {
		try {
			$object = new WC_Coupon( $object_id );
		} catch ( \Throwable $th ) {
			$object = false;
		}

		if ( $object ) {
			$field['options'][ $object->get_id() ] = rawurldecode( wp_strip_all_tags( $object->get_code() ) );
		}
	}
}
include dirname( __FILE__ ) . '/html-field-enhanced-select.php'; // phpcs:ignore
