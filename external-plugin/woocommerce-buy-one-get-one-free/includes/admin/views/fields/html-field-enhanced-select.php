<?php
/**
 * Enhanced select field.
 *
 * @var array $field Field data.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;
$field['multiple']                              = isset( $field['multiple'] ) ? $field['multiple'] : true;
$field['custom_attributes']['class']            = empty( $field['class'] ) ? 'wc-enhanced-select' : $field['class'];
$field['custom_attributes']['data-placeholder'] = empty( $field['placeholder'] ) ? '' : $field['placeholder'];
$field['custom_attributes']['style']            = 'width:100%;';
if ( $field['multiple'] ) {
	$field['custom_attributes']['multiple'] = 'multiple';
}
include dirname( __FILE__ ) . '/html-field-select.php'; // phpcs:ignore
