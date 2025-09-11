<?php
/**
 * Get the cheapest item free. Handles BOGO rule actions.
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Cart_Rule_Cheapest_Free Class
 */
class WC_BOGOF_Cart_Rule_Cheapest_Free extends WC_BOGOF_Cart_Rule {

	/**
	 * Does the Cart Rule support choose your gift?
	 */
	public function support_choose_your_gift() {
		return false;
	}

	/**
	 * Does the Cart Rule support gifts in the cart?
	 */
	public function support_gifts() {
		return false;
	}

	/**
	 * Returns the cart item price.
	 *
	 * @param array $cart_item Cart item.
	 * @return float
	 */
	protected function get_cart_item_price( $cart_item ) {
		if ( ! isset( $cart_item['data'] ) ) {
			return 0;
		}

		if ( WC_BOGOF_Cart::is_valid_discount( $cart_item['data'] ) ) {
			$cart_item_price = $cart_item['data']->_bogof_discount->get_base_price();
		} else {
			$cart_item_price = $cart_item['data']->get_price();
		}

		return apply_filters( 'wc_bogof_cart_rule_cheapest_free_cart_item_price', $cart_item_price, $cart_item );
	}

	/**
	 * Does the cart item match with the rule?
	 *
	 * @since 3.6
	 * @param array $cart_item Cart item.
	 * @return bool
	 */
	public function cart_item_match( $cart_item ) {
		return parent::cart_item_match( $cart_item ) && $this->get_cart_item_price( $cart_item ) > 0;
	}

	/**
	 * Returns cart items order by price.
	 */
	protected function get_cart_items() {
		$items_sorted  = array();
		$cart_contents = WC()->cart->get_cart_contents();

		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			if ( $this->cart_item_match( $cart_item ) ) {
				$items_sorted[ $cart_item_key ] = $cart_item;
			}
		}

		uasort( $items_sorted, array( $this, 'sort_by_price' ) );

		return $items_sorted;
	}

	/**
	 * Sort callback.
	 *
	 * @param array $a A element to compare.
	 * @param array $b B element to compare.
	 */
	protected function sort_by_price( $a, $b ) {
		$price_a = $this->get_cart_item_price( $a );
		$price_b = $this->get_cart_item_price( $b );

		if ( $price_a < $price_b ) {
			return -1;
		} elseif ( $price_a > $price_b ) {
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * Update the quantity of free items in the cart.
	 *
	 * @param bool $add_to_cart Add free items to cart?.
	 */
	public function update_free_items_qty( $add_to_cart = true ) {

		$this->cache = []; // clear cache.

		$max_qty = $this->get_max_free_quantity();

		if ( $max_qty > 0 ) {
			$cart_items = $this->get_cart_items();

			foreach ( $cart_items as $cart_item_key => $cart_item ) {
				$available_qty = WC_BOGOF_Cart::is_valid_discount( $cart_item ) ? $cart_item['quantity'] - $cart_item['data']->_bogof_discount->get_free_quantity() : $cart_item['quantity'];
				$free_qty      = $max_qty < $available_qty ? $max_qty : $available_qty;
				$max_qty      -= $free_qty;

				WC_BOGOF_Cart::set_cart_item_discount( $cart_item_key, $this->get_id(), $free_qty, false );

				if ( 0 >= $max_qty ) {
					break;
				}
			}
		}
	}
}
