<?php
/**
 * Date picker field.
 *
 * @var array $field Field data.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

$placeholder                = isset( $field['custom_attributes']['placeholder'] ) ? $field['custom_attributes']['placeholder'] : '';
$field['type']              = 'text';
$field['value']             = is_numeric( $field['value'] ) ? date_i18n( 'Y-m-d', $field['value'] ) : '';
$field['custom_attributes'] = array(
	'class'       => 'date-picker',
	'placeholder' => $placeholder . '&hellip;&nbsp;YYYY-MM-DD',
	'pattern'     => apply_filters( 'woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ),
);
self::output_input( $field );
