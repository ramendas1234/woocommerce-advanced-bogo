<?php
/**
 * Select field.
 *
 * @var array $field Field data.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

$field['custom_attributes'] = array_merge(
	$field['custom_attributes'],
	array(
		'id'   => $field['id'],
		'name' => empty( $field['name'] ) ? $field['id'] : $field['name'],
	)
);
if ( isset( $field['custom_attributes']['multiple'] ) ) {
	$field['custom_attributes']['name'] .= '[]';
}
?>
<select <?php echo wc_implode_html_attributes( $field['custom_attributes'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php self::output_options( $field['options'], $field['value'] ); ?>
</select>
<?php if ( ! empty( $field['message'] ) ) : ?>
<p class="description">
	<?php echo esc_html( $field['message'] ); ?>
</p>
<?php endif; ?>
