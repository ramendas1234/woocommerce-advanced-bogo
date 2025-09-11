<?php
/**
 * True/False field.
 *
 * @var array $field Field data.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;
?>
<input type="hidden" id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( ( empty( $field['name'] ) ? $field['id'] : $field['name'] ) ); ?>" value="<?php echo esc_attr( ( wc_string_to_bool( $field['value'] ) ? 'yes' : 'no' ) ); ?>" />
<a href="#<?php echo esc_attr( $field['id'] ); ?>" class="wc-bogo-true-false"><span class="woocommerce-input-toggle woocommerce-input-toggle--<?php echo esc_attr( ( $field['value'] ? 'enabled' : 'disabled' ) ); ?>" aria-label="<?php echo ( empty( $field['label'] ) ? '' : esc_attr( $field['label'] ) ); ?>"></span></a>
<?php if ( ! empty( $field['message'] ) ) : ?>
<span class="message"><?php echo esc_html( $field['message'] ); ?></span>
<?php endif; ?>
<?php if ( ! empty( $field['input-info'] ) ) : ?>
	<?php self::output_tooltip_info( $field['input-info'] ); ?>
<?php endif; ?>
