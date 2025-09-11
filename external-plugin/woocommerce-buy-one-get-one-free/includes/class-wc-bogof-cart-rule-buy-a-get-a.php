<?php
/**
 * Buy One Get One Free Cart Rule Buy A Get A. Handles BOGO rule actions.
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Cart_Rule_Buy_A_Get_A Class
 */
class WC_BOGOF_Cart_Rule_Buy_A_Get_A extends WC_BOGOF_Cart_Rule {

	/**
	 * Parent ID.
	 *
	 * @var int
	 */
	protected $parent_id;

	/**
	 * Does the Cart Rule support choose your gift?
	 */
	public function support_choose_your_gift() {
		return $this->rule->get_select_variation() && 'product_variation' === get_post_type( $this->product_id );
	}

	/**
	 * Set the product ID.
	 *
	 * @param int $product_id Product ID.
	 */
	protected function set_product_id( $product_id ) {
		parent::set_product_id( $product_id );

		if ( $this->support_choose_your_gift() ) {
			$this->parent_id = wp_get_post_parent_id( $this->product_id );
		}
	}

	/**
	 * Set the ID of the cart Rule
	 */
	protected function generate_id() {
		if ( $this->support_choose_your_gift() ) {
			$this->id = $this->rule->get_id() . '-' . $this->parent_id;
		} else {
			parent::generate_id();
		}
	}

	/**
	 * Whether should add the item to the cart automatic or not.
	 *
	 * @since 5.1
	 * @return bool
	 */
	protected function is_auto_add_to_cart() {
		return ! $this->support_choose_your_gift();
	}

	/**
	 * Whether indivdual product matches or not.
	 *
	 * @since 5.1
	 * @param array $cart_item Cart item data.
	 * @return bool
	 */
	protected function individual_product_match( $cart_item ) {
		if ( ! $this->support_choose_your_gift() ) {

			return parent::individual_product_match( $cart_item );
		}

		$parent_id = isset( $cart_item['data'] ) && is_callable( [ $cart_item['data'], 'get_parent_id' ] ) ? $cart_item['data']->get_parent_id() : false;

		return $this->parent_id === $parent_id;
	}

	/**
	 * Is the product avilable for free in the shop.
	 *
	 * @param int|WC_Product $product Product ID or Product object.
	 * @return bool
	 */
	public function is_shop_avilable_free_product( $product ) {

		if ( $this->get_shop_free_quantity() < 1 ) {
			return false;
		}

		$parent_id = is_callable( [ $product, 'get_parent_id' ] ) ? $product->get_parent_id() : wp_get_post_parent_id( $product );

		return $this->parent_id === $parent_id;
	}

	/**
	 * Returns SQL string of the free avilable products to be use in a SELECT.
	 *
	 * @see WC_BOGOF_Choose_Gift::posts_where
	 * @return string
	 */
	public function get_free_products_in() {

		if ( $this->get_shop_free_quantity() < 1 ) {
			return false;
		}

		return WC_BOGOF_Conditions::get_where_clause(
			[
				[
					[
						'type'     => 'product',
						'modifier' => 'in',
						'value'    => [ $this->product_id ],
					],
				],
			]
		);
	}

	/**
	 * Add the free product to the cart.
	 *
	 * @param int $qty The quantity of the item to add.
	 */
	protected function add_gift_to_cart( $qty ) {
		$cart_item_data = false;
		$cart_item_key  = false;

		foreach ( WC()->cart->get_cart_contents() as $cart_item_key => $cart_item ) {
			if ( $this->cart_item_match( $cart_item ) ) {
				$cart_item_data = $cart_item;
				break;
			}
		}

		if ( false !== $cart_item_data ) {

			$product_id = $cart_item_data['data']->get_id();

			unset(
				$cart_item_data['key'],
				$cart_item_data['product_id'],
				$cart_item_data['variation_id'],
				$cart_item_data['variation'],
				$cart_item_data['quantity'],
				$cart_item_data['data'],
				$cart_item_data['data_hash'],
				$cart_item_data['_bogof_free_item'],
				$cart_item_data['_bogof_discount']
			);

			/**
			* Filters the cart item data before add to the cart.
			*
			* @since 4.0.0
			* @param array $cart_item_data Cart Item data.
			*/
			$cart_item_data = (array) apply_filters( 'wc_bogof_buy_a_get_a_add_cart_item_data', $cart_item_data );

			/**
			 * Add the product to the cart.
			 */
			$cart_item_key = $this->cart_add_item(
				$product_id,
				$qty,
				$cart_item_data
			);
		}

		return $cart_item_key;
	}
}
