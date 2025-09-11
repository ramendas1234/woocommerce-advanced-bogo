<?php
/**
 * Modal dialog template.
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wc-bogo-modal hidden" id="<?php echo esc_attr( $dialog_id ); ?>-dialog" role="dialog" aria-hidden="true">
	<div class="wc-bogo-modal-dialog">
		<div class="wc-bogo-modal-content">
			<span class="wc-bogo-modal-close" data-dismiss="wc-bogo-modal">
				<span>&times;</span>
			</span>
			<?php require $filename; ?>
		</div>
	</div>
</div>

