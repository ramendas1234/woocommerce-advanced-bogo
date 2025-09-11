<?php
/**
 * Condition cart items count class.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Cart_Items_Count Class
 */
class WC_BOGOF_Condition_Cart_Items_Count extends WC_BOGOF_Abstract_Condition {
	use WC_BOGOF_Condition_Integer;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id       = 'cart_items_count';
		$this->title    = sprintf(
			'%s - %s',
			__( 'Cart', 'wc-buy-one-get-one-free' ),
			__( 'Line items count', 'wc-buy-one-get-one-free' )
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
		$data        = $this->sanitize( $data );
		$items_count = 0;
		$cart        = is_callable( [ WC()->cart, 'get_cart_contents' ] ) ? WC()->cart->get_cart_contents() : [];

		foreach ( $cart as $cart_item ) {
			if ( WC_BOGOF_Cart::is_free_item( $cart_item ) ) {
				continue;
			}

			$items_count++;
		}

		return $this->validate( $items_count, $data['value'], $data['modifier'] );
	}
}
