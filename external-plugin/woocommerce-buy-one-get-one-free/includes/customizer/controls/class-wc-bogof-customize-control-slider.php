<?php
/**
 * Slider customizer control.
 *
 * @since 4.2.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Customizer class.
 */
class WC_BOGOF_Customize_Control_Slider extends WC_BOGOF_Customize_Control {

	/**
	 * The control type.
	 *
	 * @var string
	 */
	public $type = 'wc-bogof-slider';

	/**
	 * Unit text.
	 *
	 * @var string
	 */
	protected $unit = '';

	/**
	 * Is the control responsive?.
	 *
	 * @var string
	 */
	protected $responsive = false;

	/**
	 * Render the control's content.
	 */
	protected function render_content() {

		static::ouput(
			[
				'id'          => '_customize-input-' . $this->id,
				'label'       => $this->label,
				'value'       => $this->value(),
				'description' => $this->description,
				'unit'        => $this->unit,
				'input_attrs' => $this->input_attrs,
				'link'        => $this->get_link(),
				'responsive'  => $this->responsive,
			]
		);
	}

	/**
	 * Output the slider template.
	 *
	 * @param array $args Array of agrs.
	 */
	public static function ouput( $args ) {

		$args = (object) wp_parse_args(
			$args,
			[
				'id'          => '',
				'label'       => false,
				'description' => false,
				'value'       => '',
				'unit'        => '',
				'input_attrs' => [],
				'link'        => '',
				'responsive'  => false,
			]
		);

		if ( empty( $args->input_attrs['id'] ) ) {
			$args->input_attrs['id'] = $args->id;
		}

		$args->input_attrs['value'] = $args->value;
		$args->input_attrs['type']  = 'number';
		?>
		<div class="wc-bogof-slider-control<?php echo esc_attr( ( $args->responsive ? ' wc-bogof-responsive-control' : '' ) ); ?>">
			<div class="wc-bogof-control-label -has-tools">
				<?php if ( ! empty( $args->label ) ) : ?>
				<label class="customize-control-title"><?php echo esc_html( $args->label ); ?></label>
				<?php endif; ?>
				<?php if ( $args->responsive ) : ?>
					<?php self::responsive_toolbar(); ?>
				<?php endif; ?>
				<div class="wc-bogof-label-tools">
				<?php if ( ! empty( $args->unit ) ) : ?>
					<span class="wc-bogof-tools-unit">
						<?php echo esc_html( $args->unit ); ?>
					</span>
				<?php endif; ?>
				<?php self::reset_button( $args->input_attrs['id'], ( $args->responsive ? ' -show-if-desktop' : '' ) ); ?>
				<?php if ( $args->responsive ) : ?>
					<?php foreach ( [ 'tablet', 'mobile' ] as $device ) : ?>
						<?php self::reset_button( $args->input_attrs['id'] . '_' . $device, " -show-if-{$device}" ); ?>
					<?php endforeach; ?>
				<?php endif; ?>
				</div>
			</div>
			<?php if ( $args->responsive ) : ?>
				<?php foreach ( [ 'desktop', 'tablet', 'mobile' ] as $device ) : ?>
					<?php self::slider( $args, $device ); ?>
				<?php endforeach; ?>
			<?php else : ?>
				<?php self::slider( $args ); ?>
			<?php endif; ?>
			<?php if ( ! empty( $args->description ) ) : ?>
			<p class="description customize-control-description">
				<?php echo esc_html( $args->description ); ?>
			</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Output responsive toolbar.
	 */
	private static function responsive_toolbar() {
		?>
		<div class="wc-bogof-responsive-tools">
		<?php foreach ( [ 'desktop', 'tablet', 'mobile' ] as $device ) : ?>
			<button title="<?php echo esc_attr( ucfirst( $device ) ); ?>" class="components-button" data-device="<?php echo esc_attr( $device ); ?>">
			<span class="dashicons dashicons-<?php echo esc_attr( ( 'mobile' === $device ? 'smartphone' : $device ) ); ?>"></span>
			</button>
		<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Output the reset button.
	 *
	 * @param string $input_id Input ID.
	 * @param string $show_if Show If class.
	 */
	private static function reset_button( $input_id, $show_if = '' ) {
		?>
		<button data-reset-input="<?php echo esc_attr( $input_id ); ?>" title="<?php echo esc_html_e( 'Reset', 'wc-buy-one-get-one-free' ); ?>" class="components-button wc-bogof-reset-control<?php echo esc_attr( $show_if ); ?>" disabled="disabled">
			<span class="dashicons dashicons-image-rotate"></span>
		</button>
		<?php
	}

	/**
	 * Output the slider.
	 *
	 * @param stdClass $args Slider args.
	 * @param string   $device Device.
	 */
	private static function slider( $args, $device = false ) {
		$class       = $device ? " -show-if-{$device}" : '';
		$input_attrs = $args->input_attrs;
		$link        = $args->link;

		if ( in_array( $device, [ 'tablet', 'mobile' ], true ) ) {

			$input_attrs['value'] = WC_BOGOF_Mods::get( $args->responsive, $device );
			$input_attrs['id']    = $input_attrs['id'] . '_' . $device;

			$mod_id = $args->responsive . '.' . $device;
			$link   = ' data-customize-setting-link="' . WC_BOGOF_Customizer::get_setting_id( $mod_id ) . '"';
		}
		?>
		<div class="wc-bogof-slider-area<?php echo esc_attr( $class ); ?>">
			<div class="wc-bogof-slider"></div>
			<div class="wc-bogof-slider-input-wrapper<?php echo ( empty( $args->unit ) ? '' : ' -has-unit' ); ?>">
				<input <?php echo wp_kses_post( wc_implode_html_attributes( $input_attrs ) . ' ' . $link ); ?> />
			</div>
		</div>
		<?php
	}
}
