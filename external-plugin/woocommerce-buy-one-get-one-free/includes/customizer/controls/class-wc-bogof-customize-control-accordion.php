<?php
/**
 * Accordion customizer control.
 *
 * @since 4.2.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Customize_Control_Accordion class.
 */
class WC_BOGOF_Customize_Control_Accordion extends WP_Customize_Control {

	/**
	 * The control type.
	 *
	 * @var string
	 */
	public $type = 'wc-bogof-accordion';

	/**
	 * Controls to wrap.
	 *
	 * @var int
	 */
	protected $controls_to_wrap = 0;

	/**
	 * Render the control's content.
	 */
	protected function render_content() {
		?>
		<div class="wc-bogof-accordion-title wc-bogof-toggle-expanded" data-target="#customize-control-<?php echo esc_attr( $this->id ); ?>">
		<?php if ( ! empty( $this->label ) ) : ?>
			<?php echo esc_html( $this->label ); ?>
		<?php endif; ?>
		</div>
		<?php
		if ( $this->controls_to_wrap > 0 ) {
			$this->print_styles();
		}
	}

	/**
	 * Print the styles to wrap the controls.
	 */
	protected function print_styles() {
		$sibling = ' + li'
		?>
		<style>
			<?php
			for ( $i = 0; $i < $this->controls_to_wrap;$i++ ) {
				echo esc_attr( ( 0 === $i ? '' : ',' ) . '#customize-control-' . $this->id . ':not(.expanded)' . str_repeat( $sibling, $i + 1 ) );
			}
			?>
			{ display: none !important; }
		</style>
		<?php
	}
}

