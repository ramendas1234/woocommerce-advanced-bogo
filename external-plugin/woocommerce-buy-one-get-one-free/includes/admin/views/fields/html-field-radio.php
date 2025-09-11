<?php
/**
 * Radio field.
 *
 * @var array $field Field data.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;
?>
<ul class="wc-bogo-radio-list wc-bogo-bl">
	<?php foreach ( $field['options'] as $value => $label ) : ?>
		<li>
			<label class="<?php echo ( $value === $field['value'] ? 'selected' : '' ); ?>">
				<input type="radio" id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $field['id'] ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $field['value'], $value ); ?> />
				<?php echo esc_html( $label ); ?>
			</label>
		</li>
	<?php endforeach; ?>
</ul>
