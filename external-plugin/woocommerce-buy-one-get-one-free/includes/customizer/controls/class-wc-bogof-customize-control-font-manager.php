<?php
/**
 * Font manager customizer control.
 *
 * @since 4.2.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Customize_Control_Font_Manager class.
 */
class WC_BOGOF_Customize_Control_Font_Manager extends WC_BOGOF_Customize_Control {

	/**
	 * The control type.
	 *
	 * @var string
	 */
	public $type = 'wc-bogof-font-manager';

	/**
	 * Control IDs to wrap.
	 *
	 * @var array
	 */
	protected $controls = [];


	/**
	 * Render the control's content.
	 */
	protected function render_content() {
		?>
		<div class="wc-bogof-font-manager-item" data-controls="<?php echo esc_attr( wp_json_encode( $this->controls ) ); ?>">
			<div class="wc-bogof-font-manager-header">
				<span class="wc-bogof-font-manager-label customize-control-title">
				<?php if ( ! empty( $this->label ) ) : ?>
					<?php echo esc_html( $this->label ); ?>
				<?php endif; ?>
				</span>
				<button class="components-button wc-bogof-font-manager-toogle wc-bogof-toggle-expanded" data-target="#customize-control-<?php echo esc_attr( $this->id ); ?>"></button>
			</div>
			<div class="wc-bogof-font-manager-controls-dropdown">
				<div class="wc-bogof-font-manager-controls-container">
					<?php
					foreach ( [ 'font-weight', 'text-transform', 'font-size' ] as $key ) {
						if ( ! isset( $this->settings[ $key ] ) ) {
							continue;
						}
						?>
						<div class="wc-bogof-font-manager-control -<?php echo esc_attr( $key ); ?>">
							<?php $this->render_font_control( $key ); ?>
						</div>
						<?php
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a select control.
	 *
	 * @param array $args Control args.
	 */
	protected function render_select_control( $args ) {
		?>
		<label class="customize-control-title"><?php echo esc_html( $args['label'] ); ?></label>
		<select <?php echo wp_kses_post( $args['link'] ); ?>>
		<?php
		foreach ( $args['choices'] as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"' . selected( $args['value'], $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		?>
		</select>
		<?php
	}

	/**
	 * Render a select control.
	 *
	 * @param string $key Font property key.
	 */
	protected function render_font_control( $key ) {
		$args = [];

		switch ( $key ) {
			case 'font-weight':
				$args = [
					'label'   => __( 'Font Weight', 'wc-buy-one-get-one-free' ),
					'choices' => [
						''    => __( 'Theme default', 'wc-buy-one-get-one-free' ),
						'200' => __( 'Extra-Light 200', 'wc-buy-one-get-one-free' ),
						'300' => __( 'Light 300', 'wc-buy-one-get-one-free' ),
						'400' => __( 'Normal 400', 'wc-buy-one-get-one-free' ),
						'600' => __( 'Semi-Bold 600', 'wc-buy-one-get-one-free' ),
						'700' => __( 'Bold 700', 'wc-buy-one-get-one-free' ),
						'900' => __( 'Ultra-Bold 900', 'wc-buy-one-get-one-free' ),
					],
				];
				break;
			case 'text-transform':
				$args = [
					'label'   => __( 'Transform', 'wc-buy-one-get-one-free' ),
					'choices' => [
						''           => __( 'Theme default', 'wc-buy-one-get-one-free' ),
						'capitalize' => __( 'Capitalize', 'wc-buy-one-get-one-free' ),
						'uppercase'  => __( 'Uppercase', 'wc-buy-one-get-one-free' ),
						'lowercase'  => __( 'Lowercase', 'wc-buy-one-get-one-free' ),
					],
				];
				break;
			case 'font-size':
				$args = [
					'label'       => __( 'Font Size', 'wc-buy-one-get-one-free' ),
					'unit'        => 'px',
					'input_attrs' => [
						'min'  => 8,
						'max'  => 50,
						'step' => 1,
					],
				];
				break;
		}

		$args['id']    = $this->id . '_' . $key;
		$args['link']  = $this->get_link( $key );
		$args['value'] = $this->value( $key );

		switch ( $key ) {
			case 'font-size':
				WC_BOGOF_Customize_Control_Slider::ouput( $args );
				break;
			default:
				$this->render_select_control( $args );
				break;
		}
	}

}

