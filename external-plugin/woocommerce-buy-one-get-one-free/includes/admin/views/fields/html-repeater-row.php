<?php
/**
 * Repeater field row.
 *
 * @var array $field Field data.
 * @var int   $row_id Row index.
 * @var array $row Row values pair array
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;
?>
<tr class="row-input -row-<?php echo esc_attr( $row_id ); ?>" data-row-id="<?php echo esc_attr( $row_id ); ?>">
	<?php foreach ( $field['fields'] as $_field ) : ?>
	<td>
		<?php
		$_field['custom_attributes']           = is_array( $_field['custom_attributes'] ) ? $_field['custom_attributes'] : [];
		$_field['custom_attributes']['class']  = empty( $_field['custom_attributes']['class'] ) ? '' : $_field['custom_attributes']['class'];
		$_field['custom_attributes']['class'] .= $field['id'] . ' -' . $_field['id'];

		$_field['value'] = isset( $row[ $_field['id'] ] ) ? $row[ $_field['id'] ] : '';
		$_field['name']  = sprintf( '%s[%s][%s]', $field['id'], $row_id, $_field['id'] );
		$_field['id']    = str_replace( array( '[', ']' ), array( '_', '' ), $_field['name'] );

		self::output_input( $_field );
		?>
	</td>
	<?php endforeach; ?>
	<td class="remove">
		<a class="wc-bogo-icon -minus remove-row" href="#"></a>
	</td>
</tr>
