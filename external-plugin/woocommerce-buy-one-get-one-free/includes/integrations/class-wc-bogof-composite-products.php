<?php
/**
 * Buy One Get One Free - WooCommerce Composite Products by WooCommerce
 *
 * @see https://woocommerce.com/products/composite-products/
 * @since 3.6.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Composite_Products Class
 */
class WC_BOGOF_Composite_Products {

	/**
	 * Retrun the minimun version required.
	 */
	public static function min_version_required() {
		return '8.5.0';
	}

	/**
	 * Returns the extension name.
	 */
	public static function extension_name() {
		return 'Composite Products';
	}

	/**
	 * Checks the minimum version required.
	 */
	public static function check_min_version() {
		return defined( 'WC_CP_VERSION' ) ? version_compare( WC_CP_VERSION, static::min_version_required(), '>=' ) : false;
	}

	/**
	 * Init hooks
	 */
	public static function init() {
		add_filter( 'wc_bogof_load_conditions', array( __CLASS__, 'load_conditions' ) );

		add_action( 'wc_bogof_after_set_cart_item_discount', array( __CLASS__, 'cart_item_discount_init' ), 10, 2 );
		add_action( 'wc_bogof_init_cart_item_discount', array( __CLASS__, 'cart_item_discount_init' ), 10, 2 );
		add_action( 'woocommerce_cart_loaded_from_session', array( __CLASS__, 'composite_flags' ), 50 );
		add_action( 'wc_bogof_auto_add_to_cart', array( __CLASS__, 'auto_add_to_cart' ), 10, 2 );
		add_action( 'wc_bogof_after_cart_set_item_quantity', array( __CLASS__, 'bogof_after_cart_set_item_quantity' ), 10, 2 );
		add_action( 'wc_bogof_after_cart_remove_item', array( __CLASS__, 'bogof_after_cart_cart_remove_item' ) );

		add_action( 'woocommerce_after_cart_item_quantity_update', array( __CLASS__, 'sync_composite_children_quantity' ), 9999 );
		add_action( 'woocommerce_remove_cart_item', array( __CLASS__, 'sync_composite_children_quantity' ), 9999 );

		add_filter( 'wc_bogof_buy_a_get_a_add_cart_item_data', array( __CLASS__, 'add_cart_item_data' ), 5 );
		add_filter( 'wc_bogof_cart_item_discount_sale_price', array( __CLASS__, 'cart_item_discount_sale_price' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_subtotal', array( __CLASS__, 'cart_item_subtotal' ), 9999, 2 );
		add_filter( 'woocommerce_cart_item_price', array( __CLASS__, 'cart_item_price' ), 99999, 2 );
		add_filter( 'wc_bogof_cart_rule_cheapest_free_cart_item_price', array( __CLASS__, 'cheapest_free_cart_item_price' ), 10, 2 );
	}

	/**
	 * Add the All Product for WooCommerce Subsctiption condition.
	 *
	 * @param array $conditions Conditions array.
	 * @return array
	 */
	public static function load_conditions( $conditions ) {
		$conditions = is_array( $conditions ) ? $conditions : array();

		if ( ! class_exists( 'WC_BOGOF_Condition_Composite_Products' ) ) {
			include_once dirname( WC_BOGOF_PLUGIN_FILE ) . '/includes/conditions/class-wc-bogof-condition-composite-products.php';
		}

		$conditions[] = new WC_BOGOF_Condition_Composite_Products();

		return $conditions;
	}

	/**
	 * Recalculate the container base price.
	 *
	 * @param array                       $cart_item Cart item data.
	 * @param WC_BOGOF_Cart_Item_Discount $cart_discount The discount object.
	 */
	public static function cart_item_discount_init( $cart_item, $cart_discount ) {
		if ( wc_cp_is_composite_container_cart_item( $cart_item ) && ! $cart_discount->get_extra_data( 'is_composite_container' ) ) {
			// Calculate the price based on components items.
			$container_price = $cart_discount->get_base_price();
			$base_price      = $container_price;
			$items_price     = 0;
			$child_items     = wc_cp_get_composited_cart_items( $cart_item, WC()->cart->cart_contents, false, true );

			foreach ( $child_items as $child_item ) {
				$base_price  += $child_item['data']->get_price() * absint( $child_item['quantity'] ) / absint( $cart_item['quantity'] );
				$items_price += $child_item['data']->get_price() * $child_item['quantity'];
			}

			$cart_discount->set_base_price( $base_price );
			$cart_discount->add_extra_data( 'is_composite_container', true );
			$cart_discount->add_extra_data( 'container_price', $container_price );
			$cart_discount->add_extra_data( 'child_items_price', $items_price );
		}
	}

	/**
	 * Add flags to the composite free composite items.
	 */
	public static function composite_flags() {
		$cart_contents = WC()->cart->get_cart_contents();
		foreach ( $cart_contents as $key => $cart_item ) {

			if ( WC_BOGOF_Cart::is_valid_free_item( $cart_item ) && wc_cp_is_composite_container_cart_item( $cart_item ) ) {
				// No editable in the cart.
				WC()->cart->cart_contents[ $key ]['data']->set_editable_in_cart( false );

				// Update the children component.
				foreach ( $cart_item['composite_children'] as $children_key ) {
					if ( ! isset( WC()->cart->cart_contents[ $children_key ]['composite_data'] ) ) {
						continue;
					}

					// No edit quantity of children components.
					foreach ( WC()->cart->cart_contents[ $children_key ]['composite_data'] as $id => $data ) {
						WC()->cart->cart_contents[ $children_key ]['composite_data'][ $id ]['quantity_max'] = $data['quantity'];
						WC()->cart->cart_contents[ $children_key ]['composite_data'][ $id ]['quantity_min'] = $data['quantity'];
					}
				}
			}
		}
	}

	/**
	 * Calls to add_items_to_cart function of product composite.
	 *
	 * @param array $cart_item_data Cart item data.
	 */
	public static function auto_add_to_cart( $cart_item_data ) {
		if ( ! is_callable( [ 'WC_CP_Cart', 'instance' ] ) ) {
			return;
		}
		if ( ! is_callable( [ WC_CP_Cart::instance(), 'add_items_to_cart' ] ) ) {
			return;
		}

		WC_CP_Cart::instance()->add_items_to_cart(
			$cart_item_data['key'],
			$cart_item_data['product_id'],
			$cart_item_data['quantity'],
			$cart_item_data['variation_id'],
			$cart_item_data['variation'],
			$cart_item_data
		);
	}

	/**
	 * Calls to update_quantity_in_cart function of product composite.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $quantity Quantity.
	 */
	public static function bogof_after_cart_set_item_quantity( $cart_item_key, $quantity ) {
		if ( ! is_callable( [ 'WC_CP_Cart', 'instance' ] ) ) {
			return;
		}
		if ( ! is_callable( [ WC_CP_Cart::instance(), 'update_quantity_in_cart' ] ) ) {
			return;
		}

		remove_action( 'woocommerce_after_cart_item_quantity_update', array( __CLASS__, 'sync_composite_children_quantity' ), 9999 );

		WC_CP_Cart::instance()->update_quantity_in_cart( $cart_item_key, $quantity );

		add_action( 'woocommerce_after_cart_item_quantity_update', array( __CLASS__, 'sync_composite_children_quantity' ), 9999 );
	}

	/**
	 * Remove the children keys.
	 *
	 * @param string $cart_item_key Cart item key.
	 */
	public static function bogof_after_cart_cart_remove_item( $cart_item_key ) {
		if ( ! wc_cp_is_composite_container_cart_item( WC()->cart->removed_cart_contents[ $cart_item_key ] ) ) {
			return;
		}

		foreach ( WC()->cart->removed_cart_contents[ $cart_item_key ]['composite_children'] as $children_key ) {
			unset( WC()->cart->cart_contents[ $children_key ] );
		}
	}

	/**
	 * Sync quantity of components child items for the Buy A get A rule.
	 *
	 * @param string $cart_item_key Cart item key.
	 */
	public static function sync_composite_children_quantity( $cart_item_key ) {
		static $avoid_recursion = false;

		if ( $avoid_recursion ) {
			return;
		}

		$avoid_recursion = true;
		$cart_item       = isset( WC()->cart->cart_contents[ $cart_item_key ] ) ? WC()->cart->cart_contents[ $cart_item_key ] : array();
		$parent_key      = isset( $cart_item['composite_parent'], $cart_item['composite_item'], $cart_item['composite_data'] ) ? $cart_item['composite_parent'] : false;

		if ( ! $parent_key || self::is_container_discount_cart_item( WC()->cart->cart_contents[ $parent_key ] ) ) {
			// Exit if no childern item or is parent is discount item.
			return;
		}

		$cart_rules = wc_bogof_cart_rules()->get_by_cart_item_key( $parent_key );

		foreach ( $cart_rules as $cart_rule ) {

			if ( 'buy_a_get_a' !== $cart_rule->get_rule()->get_type() ) {
				continue;
			}

			// Sync the free items.
			$free_items = WC_BOGOF_Cart::get_free_items( $cart_rule->get_id() );

			foreach ( $free_items as $free_item_key => $free_item ) {
				if ( ! isset( $free_item['composite_children'] ) ) {
					continue;
				}

				foreach ( $free_item['composite_children'] as $children_key ) {
					if ( ! isset( WC()->cart->cart_contents[ $children_key ]['composite_item'] ) ) {
						continue;
					}

					$component_id   = WC()->cart->cart_contents[ $children_key ]['composite_item'];
					$composite_data = self::get_children_component_data( $parent_key, $component_id );

					if ( is_array( $composite_data ) ) {
						WC()->cart->cart_contents[ $children_key ]['composite_data'] = $composite_data;
						WC()->cart->cart_contents[ $children_key ]['quantity']       = $composite_data[ $component_id ]['quantity'] * $free_item['quantity'];
					} else {
						unset( WC()->cart->cart_contents[ $children_key ] );
					}
				}
			}
		}

		$avoid_recursion = false;
	}

	/**
	 * Returns the compoment data.
	 *
	 * @param string $parent_key Cart item key of a parent.
	 * @param string $composite_item Composite item ID.
	 */
	private static function get_children_component_data( $parent_key, $composite_item ) {
		$children_keys = isset( WC()->cart->cart_contents[ $parent_key ]['composite_children'] ) ? WC()->cart->cart_contents[ $parent_key ]['composite_children'] : [];
		foreach ( $children_keys as $children_key ) {
			if ( isset( WC()->cart->cart_contents[ $children_key ]['composite_item'] ) &&
				WC()->cart->cart_contents[ $children_key ]['composite_item'] === $composite_item
			) {
				return WC()->cart->cart_contents[ $children_key ]['composite_data'];
			}
		}
		return false;
	}

	/**
	 * Unset the cart CP cart data when is a free item.
	 *
	 * @param array $cart_item_data Cart item data.
	 */
	public static function add_cart_item_data( $cart_item_data ) {
		if ( wc_cp_is_composite_container_cart_item( $cart_item_data ) ) {
			$cart_item_data['composite_children'] = [];
		} elseif ( wc_cp_maybe_is_composited_cart_item( $cart_item_data ) ) {
			unset(
				$cart_item_data['composite_parent'],
				$cart_item_data['composite_item'],
				$cart_item_data['composite_data']
			);
		}
		return $cart_item_data;
	}

	/**
	 * Return the sale price for composite container.
	 *
	 * @param float                       $sale_price Discount sale price.
	 * @param WC_BOGOF_Cart_Item_Discount $cart_discount The discount object.
	 */
	public static function cart_item_discount_sale_price( $sale_price, $cart_discount ) {
		if ( $cart_discount->get_extra_data( 'is_composite_container' ) ) {

			$container_price   = floatval( $cart_discount->get_extra_data( 'container_price' ) ) * $cart_discount->get_cart_quantity();
			$child_items_price = floatval( $cart_discount->get_extra_data( 'child_items_price' ) );

			$final_price = $container_price + $child_items_price - $cart_discount->get_discount();
			$sale_price  = ( $final_price - $child_items_price ) / $cart_discount->get_cart_quantity();

		}
		return $sale_price;
	}

	/**
	 * Cart item subtotal. Recalculate the subtotal for Composite containers.
	 *
	 * @param string $cart_subtotal Subtotal to display.
	 * @param array  $cart_item Cart item.
	 */
	public static function cart_item_subtotal( $cart_subtotal, $cart_item ) {
		if ( self::is_container_discount_cart_item( $cart_item ) ) {
			$container_price  = wc_bogof_get_cart_product_price( $cart_item['data'], array( 'qty' => $cart_item['quantity'] ) );
			$container_price += self::get_cart_composited_cart_items_price( $cart_item );

			$cart_subtotal = WC_CP()->display->format_subtotal( $cart_item['data'], $container_price );

		} elseif ( self::is_component_free_cart_item( $cart_item ) ) {
			$cart_subtotal = '';
		}
		return $cart_subtotal;
	}

	/**
	 * Sum and returns the composited items price.
	 *
	 * @param array $cart_item Cart item.
	 */
	private static function get_cart_composited_cart_items_price( $cart_item ) {
		$child_price = 0;
		$child_items = wc_cp_get_composited_cart_items( $cart_item, WC()->cart->cart_contents );
		foreach ( $child_items as $child_item ) {
			$child_price += wc_bogof_get_cart_product_price( $child_item['data'], array( 'qty' => $child_item['quantity'] ) );
		}
		return $child_price;
	}

	/**
	 * Cart item price. Display empty price of the components whose parent is a free item..
	 *
	 * @param string $cart_item_price Price to display.
	 * @param array  $cart_item Cart item.
	 */
	public static function cart_item_price( $cart_item_price, $cart_item ) {
		if ( WC_BOGOF_Cart::is_valid_free_item( $cart_item ) && self::is_container_discount_cart_item( $cart_item ) ) {

			$container_price = wc_bogof_get_cart_product_price( $cart_item['data'], array( 'qty' => $cart_item['quantity'] ) );
			$children_price  = self::get_cart_composited_cart_items_price( $cart_item );

			$sale_price = ( $container_price + $children_price ) / absint( $cart_item['quantity'] );
			if ( $sale_price > 0 ) {
				$regular_price = wc_bogof_get_cart_product_price(
					$cart_item['data'],
					[
						'price' => $cart_item['data']->_bogof_discount->get_base_price(),
						'qty'   => $cart_item['quantity'],
					]
				);
				$regular_price = ( $regular_price + $children_price ) / absint( $cart_item['quantity'] );

				$cart_item_price = wc_format_sale_price( $regular_price, $sale_price );
			}
		} elseif ( self::is_component_free_cart_item( $cart_item ) ) {
			return '';
		}
		return $cart_item_price;
	}

	/**
	 * Check if the cart item is a container product with a discount.
	 *
	 * @param array $cart_item Cart item.
	 * @return true
	 */
	private static function is_container_discount_cart_item( $cart_item ) {
		return isset( $cart_item['data'] ) && is_object( $cart_item['data'] ) && isset( $cart_item['data']->_bogof_discount ) && $cart_item['data']->_bogof_discount->get_extra_data( 'is_composite_container' );
	}

	/**
	 * Check if the cart item is a component child product and its parent is a free item.
	 *
	 * @param array $cart_item Cart item.
	 * @return true
	 */
	private static function is_component_free_cart_item( $cart_item ) {
		$is_component_free_cart_item = false;
		return isset( $cart_item['composite_parent'] ) &&
			isset( WC()->cart->cart_contents[ $cart_item['composite_parent'] ] ) &&
			self::is_container_discount_cart_item( WC()->cart->cart_contents[ $cart_item['composite_parent'] ] ) &&
			WC_BOGOF_Cart::is_valid_free_item( WC()->cart->cart_contents[ $cart_item['composite_parent'] ] );
	}

	/**
	 * Returns the cart item price.
	 *
	 * @param bool  $value is zero price?.
	 * @param array $cart_item Cart item data.
	 */
	public static function cheapest_free_cart_item_price( $value, $cart_item ) {
		if ( ! $value || ! wc_cp_is_composite_container_cart_item( $cart_item ) ) {
			return $value;
		}

		$subtotal    = 0;
		$child_items = wc_cp_get_composited_cart_items( $cart_item, WC()->cart->cart_contents, false, true );
		foreach ( $child_items as $child_item ) {
			if ( ! isset( $child_item['data'], $child_item['line_subtotal'] ) ) {
				continue;
			}

			if ( WC_BOGOF_Cart::is_valid_discount( $child_item['data'] ) ) {
				$subtotal += $child_item['data']->_bogof_discount->get_base_price();
			} else {
				$subtotal += floatval( $child_item['line_subtotal'] ) / absint( $child_item['quantity'] );
			}
		}

		return $subtotal;
	}

}
