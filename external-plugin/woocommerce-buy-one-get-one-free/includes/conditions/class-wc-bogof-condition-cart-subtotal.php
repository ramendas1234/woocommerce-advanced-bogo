<?php
/**
 * Condition cart item subtotal class.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Cart_Subtotal Class
 */
class WC_BOGOF_Condition_Cart_Subtotal extends WC_BOGOF_Abstract_Condition {
	use WC_BOGOF_Condition_Price;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id       = 'cart_subtotal';
		$this->title    = sprintf(
			'%s - %s',
			__( 'Cart', 'wc-buy-one-get-one-free' ),
			__( 'Subtotal', 'wc-buy-one-get-one-free' )
		);
		$this->supports = array( '_conditions' );
	}

	/**
	 * Evaluate a condition field.
	 *
	 * @param array $data   Condition field data.
	 * @param mixed $value  Value to check.
	 * @return boolean
	 */
	public function check_condition( $data, $value = null ) {
		$data     = $this->sanitize( $data );
		$subtotal = $this->multicurrency_to_base(
			WC_BOGOF_Cart_Totals::instance()->get_subtotal()
		);

		return $this->validate( $subtotal, $data['value'], $data['modifier'] );
	}

}
