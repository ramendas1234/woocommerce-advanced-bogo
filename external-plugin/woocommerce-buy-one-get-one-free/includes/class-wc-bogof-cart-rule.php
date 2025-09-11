<?php
/**
 * Buy One Get One Free Cart Rule. Handles BOGO rule actions.
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Cart_Rule Class
 */
class WC_BOGOF_Cart_Rule {

	/**
	 * Cart rule ID.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * BOGOF rule.
	 *
	 * @var WC_BOGOF_Rule
	 */
	protected $rule;

	/**
	 * Product ID - For "individual" rules.
	 *
	 * @var int
	 */
	protected $product_id;

	/**
	 * Array to store the more costly operations.
	 *
	 * @var array
	 */
	protected $cache;

	/**
	 * Constructor.
	 *
	 * @param WC_BOGOF_Rule $rule BOGOF rule.
	 * @param int           $product_id The product ID for "individual" rules.
	 */
	public function __construct( $rule, $product_id = 0 ) {
		$this->cache      = [];
		$this->product_id = 0;
		$this->rule       = $rule;

		if ( $this->rule->is_individual() ) {
			$this->set_product_id( $product_id );
		}

		$this->generate_id();
	}

	/**
	 * Set the product ID.
	 *
	 * @param int $product_id Product ID.
	 */
	protected function set_product_id( $product_id ) {
		$this->product_id = $product_id;
	}

	/**
	 * Set the ID of the cart Rule
	 */
	protected function generate_id() {
		$this->id = $this->rule->get_id();
		if ( $this->rule->is_individual() && $this->product_id ) {
			$this->id .= '-' . $this->product_id;
		}
	}

	/**
	 * Return the cart rule ID.
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Return the rule ID.
	 */
	final public function get_rule_id() {
		return $this->rule->get_id();
	}

	/**
	 * Return the rule ID.
	 */
	final public function get_rule() {
		return $this->rule;
	}

	/**
	 * Does the Cart Rule support gifts in the cart?
	 */
	public function support_gifts() {
		return true;
	}

	/**
	 * Does the Cart Rule support choose your gift?
	 */
	public function support_choose_your_gift() {
		return true;
	}

	/**
	 * Does the cart item match with the rule?
	 *
	 * @param array $cart_item Cart item.
	 * @return bool
	 */
	public function cart_item_match( $cart_item ) {
		if ( WC_BOGOF_Cart::is_free_item( $cart_item ) || wc_bogof_cart_item_match_skip( $this, $cart_item ) ) {
			return false;
		}

		$match = $this->rule->is_buy_product( $cart_item );

		if ( $match && $this->rule->is_individual() && $this->product_id ) {
			$match = $this->individual_product_match( $cart_item );
		}

		return $match;
	}

	/**
	 * Whether individual product matches or not.
	 *
	 * @since 5.1
	 * @param array $cart_item Cart item data.
	 * @return bool
	 */
	protected function individual_product_match( $cart_item ) {
		$product_id = isset( $cart_item['data'] ) && is_callable( [ $cart_item['data'], 'get_id' ] ) ? $cart_item['data']->get_id() : false;
		return $product_id === $this->product_id;
	}

	/**
	 * Whether should add the item to the cart automatic or not.
	 *
	 * @since 5.1
	 * @return bool
	 */
	protected function is_auto_add_to_cart() {
		return $this->rule->is_action( 'add_to_cart' );
	}

	/**
	 * Add the free product to the cart.
	 *
	 * @param int $quantity The quantity to add.
	 */
	protected function add_to_cart( $quantity = 1 ) {
		$cart_item_key  = false;
		$quantity_added = $quantity;
		$items          = WC_BOGOF_Cart::get_free_items( $this->get_id() );

		if ( count( $items ) ) {
			// Set the qty.
			$cart_item_key = key( $items );
			$cart_item     = $items[ $cart_item_key ];
			$old_quantity  = $cart_item['quantity'];

			// Set the quantity.
			$this->cart_set_item_quantity( $cart_item_key, $quantity );

			// Refresh the values.
			$cart_item_key = isset( WC()->cart->cart_contents[ $cart_item_key ] ) ? $cart_item_key : false;

			if ( $cart_item_key ) {
				$cart_item      = WC()->cart->cart_contents[ $cart_item_key ];
				$quantity_added = $cart_item['quantity'] - $old_quantity > 0 ? $old_quantity - $cart_item['quantity'] : 0;
			}
		} else {

			/**
			 * Before adding the gift item to the cart.
			 *
			 * @since 4.1.5
			 */
			do_action( 'wc_bogof_before_add_gift_item_to_cart', $this );

			try {
				// Add the item to the cart.
				$cart_item_key = $this->add_gift_to_cart( $quantity );

				$cart_item      = WC()->cart->get_cart_item( $cart_item_key );
				$quantity_added = $cart_item['quantity'];

			} catch ( Exception $e ) {
				if ( $e->getMessage() && current_user_can( 'manage_woocommerce' ) ) {
					$message  = __( 'The Buy One Get One Free plugin was unable to add the product to the cart', 'wc-buy-one-get-one-free' );
					$message .= ': ' . $e->getMessage();
					wc_add_notice( $message, 'error' );
				}
			}

			/**
			 * After adding the gift item to the cart.
			 *
			 * @since 4.1.5
			 */
			do_action( 'wc_bogof_after_add_gift_item_to_cart', $this );
		}

		if ( $cart_item_key ) {
			// Update the discount.
			WC_BOGOF_Cart::set_cart_item_discount( $cart_item_key, $this->get_id(), $cart_item['quantity'], true );

			if ( $quantity_added > 0 ) {
				// Add the message.
				$this->add_free_product_to_cart_message( $cart_item['product_id'], $quantity_added );
			}
		}
	}

	/**
	 * Add the free product to the cart.
	 *
	 * @param int $quantity The quantity of the item to add.
	 * @throws Exception If product can't be added.
	 * @return string|bool $cart_item_key
	 */
	protected function add_gift_to_cart( $quantity ) {

		$product_id = $this->rule->get_free_product_id();

		/**
		 * Load cart item data - may be added by other plugins.
		 */
		$cart_item_data = (array) apply_filters( 'woocommerce_add_cart_item_data', [], $product_id, 0, $quantity );

		return $this->cart_add_item(
			$product_id,
			$quantity,
			$cart_item_data
		);
	}

	/**
	 * Add a "gift" to the cart. Must be done without updating session data, recalculating totals or calling 'woocommerce_add_to_cart' recursively.
	 *
	 * @param int   $product_id contains the id of the product to add to the cart.
	 * @param int   $quantity contains the quantity of the item to add.
	 * @param array $cart_item_data extra cart item data we want to pass into the item.
	 * @throws Exception If product can't be added.
	 * @return string
	 */
	protected function cart_add_item( $product_id, $quantity = 1, $cart_item_data = [] ) {
		if ( $quantity <= 0 || ! $product_id ) {
			return false;
		}

		$product_id   = absint( $product_id );
		$product_data = wc_get_product( $product_id );
		$variation_id = 0;
		$variation    = [];

		if ( ! $product_data ) {
			// Translators: %s: Product ID.
			throw new Exception( sprintf( __( 'The product "#%s" does not exits.', 'wc-buy-one-get-one-free' ), $product_id ) );
		}

		if ( ! ( $product_data->is_purchasable() && 'publish' === $product_data->get_status() ) ) {
			// Translators: %s: Product name.
			throw new Exception( sprintf( __( '"%s" must be public for the BOGO promotion to work for customer.', 'wc-buy-one-get-one-free' ), $product_data->get_name() ) );
		}

		if ( ! $product_data->is_in_stock() ) {
			/* translators: %s: product name */
			throw new Exception( sprintf( __( 'The product "%s" is out of stock.', 'wc-buy-one-get-one-free' ), $product_data->get_name() ) );
		}

		$quantity = $this->get_available_stock_quantity( $product_data, $quantity );
		if ( $quantity < 1 ) {
			/* translators: %s: product name */
			throw new Exception( sprintf( __( 'The product "%s" has not enough stock.', 'wc-buy-one-get-one-free' ), $product_data->get_name() ) );
		}

		if ( $product_data->is_type( 'variation' ) ) {
			$product_id   = $product_data->get_parent_id();
			$variation_id = $product_data->get_id();
			$variation    = $product_data->get_variation_attributes();
		}

		// Add the cart rule ID to the item data.
		$cart_item_data['_bogof_free_item'] = $this->get_id();

		// Generate a ID based on product ID, variation ID, variation data, and other cart item data.
		$cart_id = WC()->cart->generate_cart_id( $product_id, $variation_id, $variation, $cart_item_data );

		// Add item after merging with $cart_item_data.
		WC()->cart->cart_contents[ $cart_id ] = array_merge(
			$cart_item_data,
			array(
				'key'          => $cart_id,
				'product_id'   => $product_id,
				'variation_id' => $variation_id,
				'variation'    => $variation,
				'quantity'     => $quantity,
				'data'         => $product_data,
				'data_hash'    => wc_get_cart_item_data_hash( $product_data ),
			)
		);

		/**
		 * Trigger after adding the fee item to the cart automatically.
		 *
		 * @since 3.7.0
		 */
		do_action( 'wc_bogof_auto_add_to_cart', WC()->cart->cart_contents[ $cart_id ], $this );

		return $cart_id;
	}

	/**
	 * Set the quantity for an item in the cart using it's key. Must be done without recalculating totals or calling woocommerce hooks.
	 *
	 * @param string $cart_item_key contains the id of the cart item.
	 * @param int    $quantity contains the quantity of the item.
	 */
	protected function cart_set_item_quantity( $cart_item_key, $quantity = 1 ) {
		$old_quantity = WC()->cart->cart_contents[ $cart_item_key ]['quantity'];

		if ( $quantity > $old_quantity ) {

			WC()->cart->cart_contents[ $cart_item_key ]['quantity'] = 0;

			$quantity = $this->get_available_stock_quantity(
				WC()->cart->cart_contents[ $cart_item_key ]['data'],
				$quantity
			);
		}

		if ( $quantity <= 0 ) {

			WC()->cart->removed_cart_contents[ $cart_item_key ] = WC()->cart->cart_contents[ $cart_item_key ];

			unset( WC()->cart->cart_contents[ $cart_item_key ] );

			/**
			 * Trigger after removing the item.
			 *
			 * @since 4.0.0
			 */
			do_action( 'wc_bogof_after_cart_remove_item', $cart_item_key, $this );

			unset( WC()->cart->removed_cart_contents[ $cart_item_key ] );

		} else {
			WC()->cart->cart_contents[ $cart_item_key ]['quantity'] = $quantity;

			/**
			 * Trigger after setting the item quantity.
			 *
			 * @since 4.0.0
			 */
			do_action( 'wc_bogof_after_cart_set_item_quantity', $cart_item_key, $quantity, $this );
		}
	}

	/**
	 * Returns the available stock quantity.
	 *
	 * @param WC_Product $product_data The product instance.
	 * @param int        $quantity Quantity that we want to add to the cart.
	 * @return int Quantity that we can add to the cart.
	 */
	protected function get_available_stock_quantity( $product_data, $quantity ) {
		if ( ! $product_data->managing_stock() || $product_data->backorders_allowed() ) {
			return $quantity;
		}

		$products_qty_in_cart = WC()->cart->get_cart_item_quantities();
		$items_in_cart        = isset( $products_qty_in_cart[ $product_data->get_stock_managed_by_id() ] ) ? $products_qty_in_cart[ $product_data->get_stock_managed_by_id() ] : 0;
		$available_stock      = $product_data->get_stock_quantity() - $items_in_cart;

		if ( $available_stock < 1 ) {
			$quantity = 0;
		} elseif ( $available_stock < $quantity ) {
			$quantity = $available_stock;
		}

		return $quantity;
	}

	/**
	 * Add free product to cart message.
	 *
	 * @param int $product_id Product ID.
	 * @param int $qty Quantity.
	 */
	protected function add_free_product_to_cart_message( $product_id, $qty ) {
		global $wp_query;

		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) && 'add_to_cart' === $wp_query->get( 'wc-ajax' ) && 'yes' !== get_option( 'woocommerce_cart_redirect_after_add' ) ) {
			// No message when AJAX add to cart.
			return;
		}

		$discount = $this->get_rule_discount();

		/* translators: %s: product name */
		$title = apply_filters( 'woocommerce_add_to_cart_qty_html', absint( $qty ) . ' &times; ', $product_id ) . apply_filters( 'woocommerce_add_to_cart_item_name_in_quotes', sprintf( _x( '&ldquo;%s&rdquo;', 'Item name in quotes', 'wc-buy-one-get-one-free' ), wp_strip_all_tags( get_the_title( $product_id ) ) ), $product_id );
		if ( 100 === $discount ) {
			/* translators: %s: product name */
			$message = sprintf( _n( '%s has been added to your cart for free!', '%s have been added to your cart for free!', $qty, 'wc-buy-one-get-one-free' ), $title );
		} else {
			/* translators: 1: product name, 2: percentage discount */
			$message = sprintf( _n( '%1$s has been added to your cart with %2$s off!', '%1$s has been added to your cart with %2$s off!', $qty, 'wc-buy-one-get-one-free' ), $title, $discount . '%' );
		}

		$message = apply_filters( 'wc_bogof_add_free_product_to_cart_message_html', $message, $product_id, $qty );

		// Add the notices to the array.
		wc_add_notice( $message, apply_filters( 'woocommerce_add_to_cart_notice_type', 'success' ) );
	}

	/**
	 * Returns the quantity from a cart item.
	 *
	 * @param array $cart_item Cart item data.
	 * @param bool  $raw Get raw value? Do not remove discounts qty if raw is true.
	 * @return int
	 */
	protected function get_cart_item_quantity( $cart_item, $raw = false ) {

		/**
		 * Filters the cart item quantity.
		 *
		 * @since 5.1.0
		 *
		 * @param int           $quantity The cart item quantity.
		 * @param array         $cart_item The cart item data.
		 * @param WC_BOGOF_Rule $cart_rule This cart rule.
		 */
		$quantity = apply_filters(
			'wc_bogof_cart_rule_item_quantity',
			( isset( $cart_item['quantity'] ) ? absint( $cart_item['quantity'] ) : 0 ),
			$cart_item,
			$this
		);

		if ( ! $raw && WC_BOGOF_Cart::is_valid_discount( $cart_item ) ) {
			$quantity -= $cart_item['data']->_bogof_discount->get_free_quantity();
		}

		return 0 > $quantity ? 0 : $quantity;
	}

	/**
	 * Return the number of items in the cart that match the rule.
	 *
	 * @since 2.2.0
	 * @param bool $raw Get raw value? Do not remove discounts qty if raw is true.
	 * @return int
	 */
	protected function count_cart_quantity( $raw = false ) {

		$cache_key = __METHOD__ . wc_bool_to_string( $raw );

		if ( isset( $this->cache[ $cache_key ] ) ) {
			return $this->cache[ $cache_key ];
		}

		$cart_quantity = 0;
		$cart_contents = WC()->cart->get_cart_contents();

		foreach ( $cart_contents as $key => $cart_item ) {
			if ( $this->cart_item_match( $cart_item ) ) {
				$cart_quantity += $this->get_cart_item_quantity( $cart_item, $raw );
			}
		}

		$this->cache[ $cache_key ] = $cart_quantity;

		return $cart_quantity;
	}

	/**
	 * Returns the quantity rule for a specific cart quantity.
	 *
	 * @since 4.0
	 * @param int $cart_qty Number of items in the cart that match the rule.
	 * @return stdClass
	 */
	protected function get_quantity_rule( $cart_qty = false ) {
		$cart_qty      = false === $cart_qty ? $this->count_cart_quantity( true ) : $cart_qty;
		$quantity_rule = false;
		foreach ( $this->rule->get_quantity_rules() as $min_cart_qty => $data ) {

			if ( $min_cart_qty > 0 && $min_cart_qty <= $cart_qty && $data['free_quantity'] > 0 ) {
				$quantity_rule = (object) $data;
			}
		}
		return $quantity_rule;
	}

	/**
	 * Calculate the available number of free items.
	 *
	 * @since 2.2.0
	 * @param int $cart_qty Number of items that match the rule.
	 * @return int
	 */
	protected function calculate_free_items( $cart_qty ) {
		$free_qty = 0;
		$offer    = $this->get_quantity_rule( $cart_qty );

		if ( $offer && $this->rule->validate() ) {

			$free_qty = absint( ( floor( $cart_qty / $offer->cart_quantity ) * $offer->free_quantity ) );

			if ( $offer->cart_limit && $free_qty > $offer->cart_limit ) {
				$free_qty = $offer->cart_limit;
			}
		}
		return $free_qty;
	}

	/**
	 * Returns the rule discount.
	 *
	 * @return int
	 */
	public function get_rule_discount() {
		$quantity_rule = $this->get_quantity_rule();
		return $quantity_rule ? $quantity_rule->discount : 0;
	}

	/**
	 * Get the quantity of the free items based on rule and on the product quantity in the cart.
	 *
	 * @return int
	 */
	public function get_max_free_quantity() {
		$cart_quantity = $this->count_cart_quantity();
		$free_quantity = $this->calculate_free_items( $cart_quantity );

		return apply_filters( 'wc_bogof_free_item_quantity', $free_quantity, $cart_quantity, $this->rule, $this );
	}

	/**
	 * Returns the number of items available for free in the shop.
	 *
	 * @return int
	 */
	public function get_shop_free_quantity() {
		if ( ! isset( $this->cache[ __METHOD__ ] ) ) {
			$this->cache[ __METHOD__ ] = $this->support_choose_your_gift() ? $this->get_max_free_quantity() - WC_BOGOF_Cart::get_free_quantity( $this->get_id() ) : 0;
		}
		return $this->cache[ __METHOD__ ];
	}

	/**
	 * Is the product avilable for free in the shop.
	 *
	 * @param int|WC_Product $product Product ID or Product object.
	 * @return bool
	 */
	public function is_shop_avilable_free_product( $product ) {

		$product_id = is_callable( [ $product, 'get_id' ] ) ? $product->get_id() : $product;

		if ( isset( $this->cache[ __METHOD__ ][ $product_id ] ) ) {
			return $this->cache[ __METHOD__ ][ $product_id ];
		}

		$is_free = false;

		if ( $this->get_shop_free_quantity() > 0 ) {
			if ( is_numeric( $product ) ) {
				$is_free = $this->rule->is_free_product( $product );
			} elseif ( is_a( $product, 'WC_Product' ) ) {
				$is_free = $this->rule->is_free_product( $product->get_id() );
				if ( ! $is_free && 'variable' === $product->get_type() ) {
					foreach ( $product->get_children() as $child_id ) {
						$is_free = $this->rule->is_free_product( $child_id );
						if ( $is_free ) {
							break;
						}
					}
				}
			}
		}

		$this->cache[ __METHOD__ ][ $product_id ] = $is_free;

		return $is_free;
	}

	/**
	 * Calculate the discount amount.
	 *
	 * @param float $amount The base amount to calculate the discount.
	 * @param int   $quantity Quantity to which to apply the discount.
	 * @return float
	 */
	public function calculate_discount( $amount, $quantity = 1 ) {
		return $amount * $quantity * $this->get_rule_discount() / 100;
	}

	/**
	 * Update the quantity of free items in the cart.
	 *
	 * @param bool $add_to_cart Add free items to cart?.
	 */
	public function update_free_items_qty( $add_to_cart = true ) {
		$this->cache = []; // clear cache.

		$max_qty        = $this->get_max_free_quantity();
		$free_items_qty = WC_BOGOF_Cart::get_free_quantity( $this->get_id() );

		if ( $free_items_qty > $max_qty ) {

			$items    = WC_BOGOF_Cart::get_free_items( $this->get_id() );
			$over_qty = $free_items_qty - $max_qty;

			foreach ( $items as $key => $item ) {
				if ( 0 === $over_qty ) {
					break;
				}

				if ( $item['quantity'] > $over_qty ) {
					// Set the item quantity.
					$this->cart_set_item_quantity( $key, $item['quantity'] - $over_qty );
					// Update the discount.
					WC_BOGOF_Cart::set_cart_item_discount( $key, $this->get_id(), $item['quantity'] - $over_qty );
					// Exit.
					$over_qty = 0;
				} else {
					$this->cart_set_item_quantity( $key, 0 );
					$over_qty -= $item['quantity'];
				}
			}
		} elseif ( ( $max_qty - $free_items_qty ) > 0 && $add_to_cart && $this->is_auto_add_to_cart() ) {
			$this->add_to_cart( $max_qty );
		} else {
			// Refresh the discount.
			$items = WC_BOGOF_Cart::get_free_items( $this->get_id() );
			foreach ( $items as $key => $item ) {
				WC_BOGOF_Cart::set_cart_item_discount( $key, $this->get_id(), $item['quantity'] );
			}
		}
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
			$this->get_rule()->get_gift_products()
		);
	}

	/**
	 * Does the rule match?.
	 *
	 * @since 2.2.0
	 * @return bool
	 */
	public function match() {
		$match_items = $this->count_cart_quantity( true );
		$free_items  = $this->calculate_free_items( $match_items );
		return $free_items > 0;
	}

	/*
	|--------------------------------------------------------------------------
	| Deprecated
	|--------------------------------------------------------------------------
	*/

	/**
	 * Count numbers of products that match the rule.
	 *
	 * @deprecated 4.0
	 * @return int
	 */
	public function get_cart_quantity() {
		wc_deprecated_function( __CLASS__ . '::get_cart_quantity', '4.0.0', __CLASS__ . '::count_cart_quantity' );
		return $this->count_cart_quantity();
	}
}
