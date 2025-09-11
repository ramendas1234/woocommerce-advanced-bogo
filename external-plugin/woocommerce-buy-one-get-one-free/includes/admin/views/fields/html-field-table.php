<?php
/**
 * Table field.
 *
 * @var array $field Field data.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $field['fields'] ) || ! is_array( $field['fields'] ) ) {
	return;
}
?>
<table class="wc-bogo-table">
	<tbody>
		<tr>
		<?php foreach ( $field['fields'] as $_id => $_field ) : ?>
			<th><?php echo ( empty( $_field['label'] ) ? '' : esc_html( $_field['label'] ) ); ?></td>
		<?php endforeach; ?>
		</tr>
		<tr class="row-input">
			<?php foreach ( $field['fields'] as $_id => $_field ) : ?>
			<td><?php self::output_input( $_field ); ?></td>
			<?php endforeach; ?>
		</tr>
	</tbody>
</table>
