<?php
/**
 * Radio image customizer control.
 *
 * @since 4.2.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Customize_Control_Radio_Image class.
 */
class WC_BOGOF_Customize_Control_Radio_Image extends WC_BOGOF_Customize_Control {

	/**
	 * The control type.
	 *
	 * @var string
	 */
	public $type = 'wc-bogof-radio-image';

	/**
	 * Render the control's content.
	 */
	protected function render_content() {
		?>
		<?php if ( ! empty( $this->label ) ) : ?>
			<label class="customize-control-title"><?php echo esc_html( $this->label ); ?></label>
		<?php endif; ?>
			<div id="<?php echo esc_attr( $this->id ); ?>" class="wc-bogof-img-choices">
			<?php foreach ( $this->choices as $value => $label ) : ?>
				<input type="radio" <?php $this->link(); ?> id="<?php echo esc_attr( $this->id . '_' . $value ); ?>" name="<?php echo esc_attr( $this->id ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $this->value(), $value ); ?>/>
				<label for="<?php echo esc_attr( $this->id . '_' . $value ); ?>" class="wc-bogof-radio-label-svg" title="<?php echo esc_html( $label ); ?>">
					<?php $this->svg( $value ); ?>
				</label>
			<?php endforeach; ?>
			</div>
		<?php
	}

	/**
	 * Render the svg image.
	 *
	 * @param string $key Image key.
	 */
	protected function svg( $key ) {
		switch ( $key ) {
			case 'default':
				?>
				<svg width="100" height="70" viewBox="0 0 100 70" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="6" width="88" height="58" fill="#ffffff" rx="3" /><rect x="14" y="16" width="34" height="25" fill="#DADDE2" /><rect x="14" y="45" width="34" height="3" fill="#E9EAEE" /><rect x="14" y="50" width="34" height="3" fill="#E9EAEE" /><rect x="52" y="16" width="34" height="25" fill="#DADDE2" /><rect x="52" y="45" width="34" height="3" fill="#E9EAEE" /><rect x="52" y="50" width="34" height="3" fill="#E9EAEE" /></svg>
				<?php
				break;
			case 'list':
				?>
				<svg width="100" height="70" viewBox="0 0 100 70" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="6" width="88" height="58" fill="#ffffff" rx="3" /><rect x="14" y="16" width="20" height="15" fill="#DADDE2" /><rect x="37" y="17" width="11" height="3" fill="#E9EAEE" /><rect x="37" y="22" width="11" height="3" fill="#E9EAEE" /><rect x="14" y="38" width="20" height="15" fill="#DADDE2" /><rect x="37" y="39" width="11" height="3" fill="#E9EAEE" /><rect x="37" y="44" width="11" height="3" fill="#E9EAEE" /><rect x="52" y="16" width="20" height="15" fill="#DADDE2" /><rect x="74" y="17" width="11" height="3" fill="#E9EAEE" /><rect x="74" y="22" width="11" height="3" fill="#E9EAEE" /><rect x="52" y="38" width="20" height="15" fill="#DADDE2" /><rect x="74" y="39" width="11" height="3" fill="#E9EAEE" /><rect x="74" y="44" width="11" height="3" fill="#E9EAEE" /></svg>
				<?php
				break;
		}
	}
}

