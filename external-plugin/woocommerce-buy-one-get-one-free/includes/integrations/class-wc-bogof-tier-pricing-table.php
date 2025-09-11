<?php
/**
 * Buy One Get One Free - Tiered Pricing Table for WooCommerce by u2code - Kolya Lukin
 *
 * @see https://woocommerce.com/products/tiered-pricing-table-for-woocommerce/
 * @since 4.1.4
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Tier_Pricing_Table Class
 */
class WC_BOGOF_Tier_Pricing_Table {

	/**
	 * Retrun the minimun version required.
	 */
	public static function min_version_required() {
		return '5.5.0';
	}

	/**
	 * Returns the extension name.
	 */
	public static function extension_name() {
		return 'Tiered Pricing Table for WooCommerce';
	}

	/**
	 * Checks the minimum version required.
	 */
	public static function check_min_version() {
		return defined( 'TierPricingTable\TierPricingTablePlugin::VERSION' ) ? version_compare( TierPricingTable\TierPricingTablePlugin::VERSION, static::min_version_required(), '>=' ) : false;
	}

	/**
	 * Init integration.
	 */
	public static function init() {
		self::change_hook_priority();
		self::add_hooks();
	}

	/**
	 * Change the calculate totals hook priority of Tier Pricing Table
	 */
	private static function change_hook_priority() {
		global $wp_filter;
		if ( empty( $wp_filter['woocommerce_before_calculate_totals'] ) ) {
			return;
		}

		$remove_hook = false;

		foreach ( $wp_filter['woocommerce_before_calculate_totals']->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( isset( $callback['function'] ) && is_array( $callback['function'] ) && isset( $callback['function'][0] ) && is_a( $callback['function'][0], 'TierPricingTable\Frontend\CartPriceManager' ) ) {
					$remove_hook = [
						'callback' => $callback['function'],
						'priority' => $priority,
					];
					break 2;
				}
			}
		}

		if ( $remove_hook ) {
			remove_action( 'woocommerce_before_calculate_totals', $remove_hook['callback'], $remove_hook['priority'] );
			add_action( 'woocommerce_before_calculate_totals', $remove_hook['callback'], 10000 ); // After WC_BOGOF_Cart::cart_update.
		}
	}

	/**
	 * Add action and filters
	 */
	private static function add_hooks() {
		add_filter( 'tiered_pricing_table/cart/need_price_recalculation', [ __CLASS__, 'need_price_recalculation' ], 10, 2 );
		add_filter( 'tiered_pricing_table/cart/need_price_recalculation/item', [ __CLASS__, 'need_price_recalculation' ], 10, 2 );
	}

	/**
	 * Exclude free items from TierPricingTable.
	 *
	 * @param bool  $value Value to return.
	 * @param array $cart_item Cart item data.
	 */
	public static function need_price_recalculation( $value, $cart_item ) {
		return ! ( WC_BOGOF_Cart::is_valid_free_item( $cart_item ) || WC_BOGOF_Cart::is_valid_discount( $cart_item ) );
	}

}
