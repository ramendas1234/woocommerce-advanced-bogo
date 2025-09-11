<?php
/**
 * Buy One Get One Free - Multicurrency support.
 * Support for the most popular Multicurrency plugins.
 *
 * @since 5.1.3
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Multi_Currency Class
 */
class WC_BOGOF_Multi_Currency {

	/**
	 * Init hooks.
	 */
	public static function init() {

		$integration = self::get_integration();

		if ( ! $integration ) {
			return;
		}

		$callbacks = self::get_callbacks();

		if ( isset( $callbacks[ $integration ] ) ) {

			foreach ( $callbacks[ $integration ] as $hook => $callback ) {
				add_filter( "wc_bogof_multicurrency_{$hook}", $callback );
			}
		}
	}

	/**
	 * Is multicurrency enabled?
	 *
	 * @return bool
	 */
	private static function is_enabled() {
		return has_filter( 'wc_bogof_multicurrency_base_currency' ) && apply_filters( 'wc_bogof_multicurrency_base_currency', false ) !== get_woocommerce_currency();
	}

	/**
	 * Convert the amount to the shop base currency is multicurrency is enable.
	 *
	 * @param double $amount Amount to convert.
	 */
	public static function to_base( $amount ) {
		if ( self::is_enabled() ) {
			$amount = apply_filters( 'wc_bogof_multicurrency_to_base', $amount );
		}
		return $amount;
	}

	/**
	 * Returns the integration.
	 */
	private static function get_integration() {
		$integration  = false;
		$integrations = [
			'WC_Product_Price_Based_Country',
			'woocommerce_wpml',
			'WOOMULTI_CURRENCY_F',
			'WOOCS',
		];

		foreach ( $integrations as $classname ) {
			if ( class_exists( $classname ) ) {
				$integration = strtolower( $classname );
				break;
			}
		}
		return $integration;
	}

	/**
	 * Returns the callbacks.
	 */
	private static function get_callbacks() {
		return [

			/**
			 * Price Based on Country by Oscar Gare.
			 */
			'wc_product_price_based_country' => [
				'to_base'       => function( $amount ) {
					return ( ( wcpbc_the_zone() && wcpbc_get_base_currency() !== get_woocommerce_currency() ) ? wcpbc_the_zone()->get_base_currency_amount( $amount ) : $amount );
				},
				'base_currency' => 'wcpbc_get_base_currency',
			],

			/**
			 * WooCommerce Multilingual & Multicurrency with WPML by OnTheGoSystems.
			 */
			'woocommerce_wpml'               => [
				'to_base'       => function( $amount ) {
					global $woocommerce_wpml;
					$wpml_currency = wcml_is_multi_currency_on()
						&& is_callable( [ $woocommerce_wpml, 'get_multi_currency' ] )
						&& is_callable( [ $woocommerce_wpml->get_multi_currency(), 'get_client_currency' ] )
						&& isset( $woocommerce_wpml->get_multi_currency()->prices )
						&& is_callable( [ $woocommerce_wpml->get_multi_currency()->prices, 'convert_price_amount_by_currencies' ] )
						? $woocommerce_wpml->get_multi_currency()
						: false;

					return ( ( $wpml_currency && wcml_get_woocommerce_currency_option() !== $wpml_currency->get_client_currency() ) ? $wpml_currency->prices->convert_price_amount_by_currencies( $amount, $wpml_currency->get_client_currency(), wcml_get_woocommerce_currency_option() ) : $amount );
				},
				'base_currency' => 'wcml_get_woocommerce_currency_option',
			],

			/**
			 * CURCY – Multi Currency for WooCommerce by VillaTheme.
			 */
			'woomulti_currency_f'            => [
				'to_base'       => 'wmc_revert_price',
				'base_currency' => function() {
					if ( is_callable( [ 'WOOMULTI_CURRENCY_F_Data', 'get_ins' ] ) ) {
						return WOOMULTI_CURRENCY_F_Data::get_ins()->get_default_currency();
					}
					return false;
				},
			],

			/**
			 * FOX - Currency Switcher Professional for WooCommerce by realmag777.
			 */
			'woocs'                          => [
				'to_base'       => function( $amount ) {
					$woocs = isset( $GLOBALS['WOOCS'] ) ? $GLOBALS['WOOCS'] : false;
					if ( $woocs ) {
						$currencies = $woocs->get_currencies();
						if ( isset( $currencies[ get_woocommerce_currency() ] ) ) {
							$amount = $amount / floatval( $currencies[ get_woocommerce_currency() ]['rate'] );
						}
					}
					return $amount;
				},
				'base_currency' => function() {
					return isset( $GLOBALS['WOOCS'] ) ? $GLOBALS['WOOCS']->default_currency : '';
				},
			],
		];
	}
}
