<?php
/**
 * Button group customizer control.
 *
 * @since 4.2.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Customize_Control_Button_Group class.
 */
class WC_BOGOF_Customize_Control_Button_Group extends WC_BOGOF_Customize_Control {

	/**
	 * The control type.
	 *
	 * @var string
	 */
	public $type = 'wc-bogof-btn-group';

	/**
	 * Icons.
	 *
	 * @var array
	 */
	protected $icons = [];

	/**
	 * Render the control's content.
	 */
	protected function render_content() {
		?>
		<?php if ( ! empty( $this->label ) ) : ?>
			<label class="customize-control-title"><?php echo esc_html( $this->label ); ?></label>
		<?php endif; ?>
		<div id="<?php echo esc_attr( $this->id ); ?>" class="wc-bogof-btn-group-choices">
		<?php foreach ( $this->choices as $value => $label ) : ?>
			<input type="radio" <?php $this->link(); ?> id="<?php echo esc_attr( $this->id . '_' . $value ); ?>" name="<?php echo esc_attr( $this->id ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $this->value(), $value ); ?>/>
			<label for="<?php echo esc_attr( $this->id . '_' . $value ); ?>" class="wc-bogof-btn-group-label" title="<?php echo esc_html( $label ); ?>">
				<?php $this->icon( $value ); ?>
			</label>
		<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render the icon.
	 *
	 * @param string $key Image key.
	 */
	protected function icon( $key ) {
		if ( isset( $this->icons[ $key ] ) ) {
			printf( '<span class="dashicons dashicons-%s"></span>', esc_attr( $this->icons[ $key ] ) );
		}
	}
}
