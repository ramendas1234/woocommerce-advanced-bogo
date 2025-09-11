<?php
/**
 * Product search field.
 *
 * @var array $field Field data.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

$field['class']       = 'wc-product-search';
$field['placeholder'] = __( 'Search for a product&hellip;', 'wc-buy-one-get-one-free' );
$field['options']     = array();

if ( is_array( $field['value'] ) ) {
	foreach ( $field['value'] as $object_id ) {
		$object = wc_get_product( $object_id );
		if ( $object ) {
			$field['options'][ $object->get_id() ] = rawurldecode( wp_strip_all_tags( $object->get_formatted_name() ) );
		}
	}
}

include dirname( __FILE__ ) . '/html-field-enhanced-select.php'; // phpcs:ignore
