<?php
/**
 * Product filter table row.
 *
 * @var array $field Field data.
 * @var int   $index Row index.
 * @var array $condition Current condition filter.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;
?>
<tr class="row-input -row-<?php echo esc_attr( $index ); ?>" data-row-id="<?php echo esc_attr( $index ); ?>">
	<td class="col-input -<?php echo esc_attr( $field['id'] ); ?> -type">
		<div class="col-input-type-wrapper">
			<span class="conditional-logic-and"><?php esc_html_e( 'and', 'wc-buy-one-get-one-free' ); ?></span>
			<?php
			self::output_input(
				WC_BOGOF_Conditions::get_metabox_type_field( $group, $index, $field, $condition )
			);
			?>
		</div>
	</td>
	<td class="col-input -<?php echo esc_attr( $field['id'] ); ?> -modifier">
	<?php
		self::output_input(
			array(
				'type'   => 'group',
				'fields' => WC_BOGOF_Conditions::get_metabox_modifier_fields( $group, $index, $field, $condition ),
			)
		);
		?>
	</td>
	<td class="col-input -<?php echo esc_attr( $field['id'] ); ?> -value">
	<div class="col-input-value-wrapper">
	<?php
		self::output_input(
			array(
				'type'   => 'group',
				'fields' => WC_BOGOF_Conditions::get_metabox_value_fields( $group, $index, $field, $condition ),
			)
		);
		?>
	</div>
	</td>
	<td class="remove">
		<a class="wc-bogo-icon -minus remove-row" href="#"></a>
	</td>
</tr>
