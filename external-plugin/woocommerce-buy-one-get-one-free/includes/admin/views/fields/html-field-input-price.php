<?php
/**
 * Input price field.
 *
 * @var array $field Field data.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

$field['type']                        = 'text';
$field['value']                       = wc_format_localized_price( $field['value'] );
$field['custom_attributes']           = isset( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ? $field['custom_attributes'] : [];
$field['custom_attributes']['class']  = empty( $field['custom_attributes']['class'] ) ? '' : $field['custom_attributes']['class'] . ' ';
$field['custom_attributes']['class'] .= 'wc_input_price';

self::output_input( $field );


