<?php
/**
 * Customize Control Base class.
 *
 * @since 4.2.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Customize_Control class.
 */
class WC_BOGOF_Customize_Control extends WP_Customize_Control {

	/**
	 * Control classes.
	 *
	 * @var string
	 */
	protected $wrapper_class = '';

	/**
	 * Renders the control wrapper and calls $this->render_content() for the internals.
	 *
	 * @since 3.4.0
	 */
	protected function render() {
		$id    = 'customize-control-' . str_replace( array( '[', ']' ), array( '-', '' ), $this->id );
		$class = 'customize-control wc-bogof-customize-control customize-control-' . $this->type . ' ' . $this->wrapper_class;

		printf( '<li id="%s" class="%s">', esc_attr( $id ), esc_attr( $class ) );
		printf( '<div class="%s">', esc_attr( 'wc-bogof-control-wrap' ) );
		$this->render_content();
		echo '</div>';
		echo '</li>';
	}
}
