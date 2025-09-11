<?php
/**
 * Enhanced select field.
 *
 * @var array $field Field data.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wc-bogo-fields-groups">
<?php foreach ( $field['fields'] as $_id => $_field ) : ?>
	<div class="wc-bogo-field-row <?php echo ( empty( $_field['type'] ) ? '' : '-' . esc_attr( $_field['type'] ) ); ?> <?php echo ( empty( $_field['id'] ) ? '' : '-' . esc_attr( $_field['id'] ) ); ?>" <?php echo ! empty( $_field['show-if'] ) ? 'data-show-if="' . esc_attr( wp_json_encode( $_field['show-if'] ) ) . '"' : ''; ?>>
		<?php if ( ! empty( $_field['label'] ) ) : ?>
			<label><?php echo esc_html( $_field['label'] ); ?></label>
		<?php endif; ?>

		<?php self::output_input( $_field ); ?>

		<?php if ( ! empty( $_field['description'] ) ) : ?>
			<p class="description">
			<?php echo esc_html( $_field['description'] ); ?>
			</p>
		<?php endif; ?>
	</div>
<?php endforeach; ?>
</div>
