<?php
/**
 * Conditional logic field.
 *
 * @var array $field Field data.
 * @package WC_BOGOF
 * https://twitter.com/PoeHaH/status/1758217386040180753
 */

defined( 'ABSPATH' ) || exit;

$field['default']     = empty( $field['default'] ) ? [ [] ] : $field['default'];
$field['value']       = ! empty( $field['value'] ) ? $field['value'] : $field['default'];
$field['placeholder'] = ! empty( $field['placeholder'] ) ? $field['placeholder'] : false;
$placeholder_class    = false !== $field['placeholder'] ? ' -has-placeholder' : '';
?>

<div id="<?php echo esc_attr( $field['id'] ); ?>" class="wc-bogo-conditional-logic">
<?php foreach ( $field['value'] as $group => $filters ) : ?>
	<div class="wc-bogo-repeater -group-<?php echo esc_attr( $group . $placeholder_class ); ?>" data-group-id="<?php echo esc_attr( $group ); ?>" data-placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>">
		<div class="wc-bogo-repeater__or"><span>or</span></div>
		<table class="wc-bogo-table">
			<tbody>
				<?php
				foreach ( $filters as $row_index => $filter ) {
					if ( empty( $filter['type'] ) || ! WC_BOGOF_Conditions::get_condition( $filter['type'] ) ) {
						continue;
					}
					self::output_conditional_logic_row( $field, $group, $row_index, $filter );
				}

				if ( empty( $filters ) && ! $field['placeholder'] ) {
					self::output_conditional_logic_row( $field );
				}
				?>
			</tbody>
		</table>
		<div class="wc-bogo-repeater-btns">
			<a class="button add-row" href="wc-bogo-conditional-logic-row<?php echo esc_attr( $field['id'] ); ?>">&plus;&nbsp;<?php esc_html_e( 'Add condition', 'wc-buy-one-get-one-free' ); ?></a>
			<a class="button add-group" href="#">&plus;&nbsp;<?php esc_html_e( 'Or add Condition Group', 'wc-buy-one-get-one-free' ); ?></a>
		</div>
	</div>
<?php endforeach; ?>
</div>
<script type="text/html" id="tmpl-wc-bogo-conditional-logic-row<?php echo esc_attr( $field['id'] ); ?>">
<?php self::output_conditional_logic_row( $field, '{{{data.groupId}}}', '{{{data.rowId}}}' ); ?>
</script>
