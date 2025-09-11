<?php
/**
 * System status report.
 *
 * @var array $rules Array of the active BOGO rules data in json format.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;
?>

<table class="wc_status_table widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Buy One Get One Free"><h2>Buy One Get One Free <?php echo wc_help_tip( esc_html__( 'This section shows details of the Buy One Get One Free plugin.', 'wc-buy-one-get-one-free' ) ); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-lable="Database version">Database version</td>
			<td>&nbsp;</td>
			<td>
				<?php echo esc_html( get_option( 'wc_bogof_version' ) ); ?>
			</td>
		</tr>
		<tr>
			<td data-export-lable="Legacy options">Legacy options</td>
			<td>&nbsp;</td>
			<td>
				<?php echo esc_html( wc_bool_to_string( ( 'no' !== WC_BOGOF_Mods::get( 'show_legacy_options' ) || '' !== get_option( 'wc_bogof_cyg_display_on', '' ) ) ) ); ?>
			</td>
		</tr>
		<tr>
			<td data-export-lable="<?php esc_html_e( 'Disable coupons', 'wc-buy-one-get-one-free' ); ?>">
			<?php esc_html_e( 'Disable coupons', 'wc-buy-one-get-one-free' ); ?>
			</td>
			<td>&nbsp;</td>
			<td>
				<?php echo esc_html( get_option( 'wc_bogof_disable_coupons', 'no' ) ); ?>
			</td>
		</tr>
		<tr>
			<td data-export-lable="<?php esc_html_e( 'Custom attributes', 'wc-buy-one-get-one-free' ); ?>">
			<?php esc_html_e( 'Custom attributes', 'wc-buy-one-get-one-free' ); ?>
			</td>
			<td>&nbsp;</td>
			<td>
				<?php echo esc_html( get_option( 'wc_bogof_include_custom_attributes', 'no' ) ); ?>
			</td>
		</tr>
		<?php
		foreach ( $rules as $wc_bogo_id => $wc_bogo_data ) {
			printf(
				'<tr><td>%1$s</td><td>&nbsp;</td><td>%2$s</td></tr>',
				esc_html( 'Rule #' . $wc_bogo_id ),
				esc_html( $wc_bogo_data )
			);
		}
		?>
	</tbody>
</table>
