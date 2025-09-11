<?php
/**
 * Add the items to the new WooCommerce navigation.
 *
 * @package  WC_BOGOF
 */

use Automattic\WooCommerce\Admin\Features\Navigation\Menu;
use Automattic\WooCommerce\Admin\Features\Navigation\Screen;

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Admin_Navigation Class.
 */
class WC_BOGOF_Admin_Navigation {

	/**
	 * Init hooks
	 */
	public static function init() {
		if (
			! class_exists( '\Automattic\WooCommerce\Admin\Features\Navigation\Menu' ) ||
			! class_exists( '\Automattic\WooCommerce\Admin\Features\Navigation\Screen' )
		) {
			return;
		}

		add_action( 'admin_menu', array( __CLASS__, 'init_navigation_menu' ) );
		add_filter( 'woocommerce_navigation_core_excluded_items', array( __CLASS__, 'navigation_core_excluded_items' ) );
	}

	/**
	 * Adds the menu items.
	 */
	public static function init_navigation_menu() {
		Screen::register_post_type( 'shop_bogof_rule' );

		Menu::add_plugin_category(
			array(
				'id'     => 'wc-buy-one-get-one-free',
				'title'  => _x( 'Buy One Get One', 'Admin menu name', 'wc-buy-one-get-one-free' ),
				'parent' => 'woocommerce',
			)
		);

		$post_type_items = Menu::get_post_type_items( 'shop_bogof_rule', array( 'parent' => 'wc-buy-one-get-one-free' ) );
		if( is_array($post_type_items) ) {
			Menu::add_plugin_item( array_merge( $post_type_items['all'], array( 'title' => __( 'BOGO Promotions', 'wc-buy-one-get-one-free' ) ) ) );
			Menu::add_plugin_item( $post_type_items['new'] );
		}
		

		Menu::add_plugin_item(
			array(
				'id'         => 'wc-buy-one-get-one-free-settings',
				'title'      => __( 'Settings', 'wc-buy-one-get-one-free' ),
				'capability' => 'manage_woocommerce',
				'url'        => 'shop_bogof_rule_settings',
				'parent'     => 'wc-buy-one-get-one-free',
			)
		);
	}

	/**
	 * Remove BOGO from the Core menu.
	 *
	 * @param array $excluded_items Core menu items to exclude.
	 */
	public static function navigation_core_excluded_items( $excluded_items ) {
		$excluded_items   = is_array( $excluded_items ) ? $excluded_items : array();
		$excluded_items[] = 'edit.php?post_type=shop_bogof_rule';

		return $excluded_items;
	}
}

