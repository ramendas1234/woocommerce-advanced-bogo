<?php
/**
 * Metabox field.
 *
 * @var array $field Field data.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wc-bogo-field -<?php echo esc_attr( $field['type'] ); ?> -<?php echo esc_attr( $field['id'] ); ?>" <?php echo ! empty( $field['show-if'] ) ? 'data-show-if="' . esc_attr( wp_json_encode( $field['show-if'] ) ) . '"' : ''; ?>>
	<div class="wc-bogo-label">
		<?php if ( ! empty( $field['label'] ) ) : ?>
		<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
		<?php endif; ?>
		<?php if ( ! empty( $field['description'] ) ) : ?>
			<p class="description">
				<?php echo esc_html( $field['description'] ); ?>
				<?php if ( ! empty( $field['tip'] ) ) : ?>
					<?php printf( '<a class="tips" data-tip="%s">%s</a>', esc_attr( $field['tip'] ), esc_html__( 'More info', 'wc-buy-one-get-one-free' ) ); ?>
				<?php endif; ?>
			</p>
		<?php endif; ?>
	</div>
	<div class="wc-bogo-input">
		<?php self::output_input( $field ); ?>
	</div>
</div>
