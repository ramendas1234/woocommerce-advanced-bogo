<?php
/**
 * Toggle customizer control.
 *
 * @since 4.2.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Customize_Control_Toggle class.
 */
class WC_BOGOF_Customize_Control_Toggle extends WC_BOGOF_Customize_Control {

	/**
	 * The control type.
	 *
	 * @var string
	 */
	public $type = 'wc-bogof-toggle';

	/**
	 * Render the control's content.
	 */
	protected function render_content() {
		?>
		<div class="wc-bogof-toggle-wrapper">
		<?php if ( ! empty( $this->label ) ) : ?>
			<label class="customize-control-title"><?php echo esc_html( $this->label ); ?></label>
		<?php endif; ?>
			<input type="hidden" <?php $this->link(); ?> id="<?php echo esc_attr( $this->id ); ?>" name="<?php echo esc_attr( $this->id ); ?>" value="<?php echo esc_attr( $this->value() ); ?>"/>
			<a href="#<?php echo esc_attr( $this->id ); ?>" class="wc-bogof-toggle-value">
				<span class="wc-bogof-input-toggle -<?php echo ( wc_string_to_bool( $this->value() ) ? 'enabled' : 'disabled' ); ?>" aria-label="<?php echo esc_attr( $this->label ); ?>"></span>
			</a>
		</div>
		<?php if ( ! empty( $this->description ) ) : ?>
			<span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
		<?php endif; ?>
		<?php
	}
}
