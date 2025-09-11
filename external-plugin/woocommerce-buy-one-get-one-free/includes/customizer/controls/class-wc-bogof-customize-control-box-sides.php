<?php
/**
 * Box Sides customizer control.
 *
 * @since 4.2.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Customize_Control_Box_Sides class.
 */
class WC_BOGOF_Customize_Control_Box_Sides extends WC_BOGOF_Customize_Control {

	/**
	 * Unit text.
	 *
	 * @var string
	 */
	protected $unit = '';

	/**
	 * The control type.
	 *
	 * @var string
	 */
	public $type = 'wc-bogof-box-sides';

	/**
	 * Render the control's content.
	 */
	protected function render_content() {
		?>
		<div class="wc-bogof-box-sides-control">
			<div class="wc-bogof-control-label -has-tools">
				<?php if ( ! empty( $this->label ) ) : ?>
				<label class="customize-control-title"><?php echo esc_html( $this->label ); ?></label>
				<?php endif; ?>
				<div class="wc-bogof-label-tools">
				<?php if ( ! empty( $this->unit ) ) : ?>
					<span class="wc-bogof-tools-unit">
						<?php echo esc_html( $this->unit ); ?>
					</span>
					<button data-reset-input="<?php echo esc_attr( wp_json_encode( $this->get_reset_inputs() ) ); ?>" title="<?php echo esc_html_e( 'Reset', 'wc-buy-one-get-one-free' ); ?>" class="components-button wc-bogof-reset-control" disabled="disabled">
						<span class="dashicons dashicons-image-rotate"></span>
					</button>
				<?php endif; ?>
				</div>
			</div>
			<div class="wc-bogof-box-sides-controls-wrapper">
			<?php foreach ( array_keys( $this->settings ) as $key ) : ?>
				<div class="wc-bogof-box-side-input -<?php echo esc_attr( $key ); ?>">
					<input  <?php $this->link( $key ); ?> value="<?php echo esc_attr( $this->value( $key ) ); ?>" id="<?php echo esc_attr( $this->id . '_' . $key ); ?>" type="number" />
					<label for="<?php echo esc_attr( $this->id . '_' . $key ); ?>" class="wc-bogof-box-side-label"><?php echo esc_html( str_replace( '-', '&nbsp;', $key ) ); ?></label>
				</div>
			<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Returns the values for the reset button.
	 */
	protected function get_reset_inputs() {
		$inputs = [];
		foreach ( array_keys( $this->settings ) as $key ) {
			$inputs[] = $this->id . '_' . $key;
		}

		return $inputs;
	}
}
