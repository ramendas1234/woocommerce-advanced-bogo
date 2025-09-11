<?php
/**
 * Repeater field.
 *
 * @var array $field Field data.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $field['fields'] ) || ! is_array( $field['fields'] ) ) {
	return;
}
$field['value']     = empty( $field['value'] ) || ! is_array( $field['value'] ) ? [ null ] : $field['value'];
$field['btn_label'] = empty( $field['btn_label'] ) ? __( 'Add row', 'wc-buy-one-get-one-free' ) : $field['btn_label'];
?>
<div class="wc-bogo-repeater" id="<?php echo esc_attr( $field['id'] ); ?>" <?php echo wc_implode_html_attributes( $field['custom_attributes'] ); ?>>
	<table class="wc-bogo-table">
		<tbody>
			<tr>
			<?php foreach ( $field['fields'] as $_id => $_field ) : ?>
				<th>
					<label>
						<?php echo ( empty( $_field['label'] ) ? '' : esc_html( $_field['label'] ) ); ?>
						<?php empty( $_field['description'] ) ? null : self::output_tooltip_info( $_field['description'] ); ?>
					</label>
				</td>
			<?php endforeach; ?>
				<td class="remove"></td>
			</tr>
			<?php foreach ( $field['value'] as $row_id => $row ) : ?>
				<?php include dirname( __FILE__ ) . '/html-repeater-row.php'; // phpcs:ignore ?>
			<?php endforeach; ?>
		</tbody>
	</table>
	<div class="wc-bogo-repeater-btns -repeater-btns-<?php echo esc_attr( $field['id'] ); ?>">
		<a class="button add-row" href="repeater-<?php echo esc_attr( $field['id'] ); ?>">&plus;&nbsp;<?php echo esc_html( $field['btn_label'] ); ?></a>
	</div>
</div>
<script type="text/html" id="tmpl-repeater-<?php echo esc_attr( $field['id'] ); ?>">
<?php
$row_id = '{{{data.rowId}}}';
$row    = [];
include dirname( __FILE__ ) . '/html-repeater-row.php'; // phpcs:ignore
?>
</script>
