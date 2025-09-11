<?php
/**
 * Buy One Get One Free Discount calculation.
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Choose_Discount Class
 */
class WC_BOGOF_Cart_Item_Discount {

	/**
	 * An array of cart rules for discount calculation.
	 *
	 * @var array
	 */
	protected $rules = array();

	/**
	 * Base price of the cart item.
	 *
	 * @var float
	 */
	protected $base_price;

	/**
	 * Cart quantity.
	 *
	 * @var string
	 */
	protected $cart_quantity;

	/**
	 * Extra data.
	 *
	 * @var array
	 */
	protected $extra_data;

	/**
	 * WC_BOGOF_Cart_Item_Discount Constructor.
	 *
	 * @param array $cart_item_data Cart item data.
	 * @param array $rules Array of free quantity in cart rule ID value pair.
	 */
	public function __construct( $cart_item_data = null, $rules = null ) {
		$this->rules         = array();
		$this->extra_data    = array();
		$this->base_price    = 0;
		$this->cart_quantity = 0;

		if ( is_array( $cart_item_data ) && isset( $cart_item_data['data'] ) && isset( $cart_item_data['quantity'] ) ) {

			if ( 'yes' === get_option( 'wc_bogof_base_regular_price', 'no' ) ) {
				$base_price = is_callable( [ $cart_item_data['data'], 'get_regular_price' ] ) ? $cart_item_data['data']->get_regular_price() : 0;
			} else {
				$base_price = is_callable( [ $cart_item_data['data'], 'get_price' ] ) ? $cart_item_data['data']->get_price() : 0;
			}

			$this->set_base_price( $base_price );
			$this->set_cart_quantity( $cart_item_data['quantity'] );

			if ( is_array( $rules ) ) {
				foreach ( $rules as $cart_rule_id => $free_qty ) {
					$this->set_free_quantity( $cart_rule_id, $free_qty );
				}
			}
		}
	}

	/**
	 * Set base price.
	 *
	 * @param float $base_price Base price.
	 */
	public function set_base_price( $base_price ) {
		$this->base_price = floatval( $base_price );
	}

	/**
	 * Set cart quantity.
	 *
	 * @param int $cart_quantity Base price.
	 */
	public function set_cart_quantity( $cart_quantity ) {
		$this->cart_quantity = absint( $cart_quantity );
	}

	/**
	 * Set free quantity.
	 *
	 * @param mixed $cart_rule_id Rule ID.
	 * @param int   $free_qty Free quantity.
	 */
	public function set_free_quantity( $cart_rule_id, $free_qty ) {
		if ( ! $free_qty ) {
			$this->remove_free_quantity( $cart_rule_id );
		} else {
			$this->rules[ $cart_rule_id ] = absint( $free_qty );
		}
	}

	/**
	 * Remove free quantity.
	 *
	 * @param mixed $cart_rule_id Rule ID.
	 */
	public function remove_free_quantity( $cart_rule_id ) {
		unset( $this->rules[ $cart_rule_id ] );
	}

	/**
	 * Add extra data.
	 *
	 * @param string $key Extra data key.
	 * @param mixed  $value Value.
	 */
	public function add_extra_data( $key, $value ) {
		$this->extra_data[ $key ] = $value;
	}

	/**
	 * Remove extra data.
	 *
	 * @param string $key Extra data key to remove.
	 */
	public function remove_extra_data( $key ) {
		unset( $this->extra_data[ $key ] );
	}

	/**
	 * Get extra data.
	 *
	 * @param string $key Extra data key to get.
	 */
	public function get_extra_data( $key ) {
		return isset( $this->extra_data[ $key ] ) ? $this->extra_data[ $key ] : false;
	}

	/**
	 * Check a cart rule ID or an array of cart rule IDs.
	 *
	 * @param array|string $cart_rule_id Cart rule ID.
	 * @return bool
	 */
	public function has_cart_rule( $cart_rule_id ) {
		$cart_rule_id = is_array( $cart_rule_id ) ? $cart_rule_id : array( $cart_rule_id );
		return wc_bogof_in_array_intersect( array_keys( $this->rules ), $cart_rule_id );
	}

	/**
	 * Returns if there are free items.
	 *
	 * @return bool
	 */
	public function has_discount() {
		return array_sum( $this->rules ) > 0;
	}

	/**
	 * Returns cart quantity.
	 */
	public function get_cart_quantity() {
		return $this->cart_quantity;
	}

	/**
	 * Returns total free quantity.
	 *
	 * @return int
	 */
	public function get_free_quantity() {
		return array_sum( $this->rules );
	}

	/**
	 * Retuns the rule array
	 *
	 * @return array
	 */
	public function get_rules() {
		return $this->rules;
	}

	/**
	 * Returns the base price of the cart item.
	 *
	 * @return float
	 */
	public function get_base_price() {
		return $this->base_price;
	}

	/**
	 * Returns the discount.
	 *
	 * @return float
	 */
	public function get_discount() {
		$discount = 0;
		foreach ( $this->rules as $id => $qty ) {
			$cart_rule = wc_bogof_cart_rules()->get( $id );
			if ( $cart_rule ) {
				$discount += $cart_rule->calculate_discount( $this->get_base_price(), $qty );
			}
		}
		return $discount;
	}

	/**
	 * Returns the sale price of the cart item or false if there is no discount.
	 *
	 * @return float|bool
	 */
	public function get_sale_price() {
		$discount   = $this->get_discount();
		$sale_price = false;
		if ( $discount > 0 ) {
			$base_price = $this->get_base_price() * $this->get_cart_quantity();
			$sale_price = ( $base_price - $discount ) / $this->get_cart_quantity();
			/**
			 * Allow plugins to filter the sale price.
			 */
			$sale_price = apply_filters( 'wc_bogof_cart_item_discount_sale_price', $sale_price, $this );
		}
		return $sale_price;
	}
}
