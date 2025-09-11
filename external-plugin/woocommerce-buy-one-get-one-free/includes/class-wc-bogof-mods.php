<?php
/**
 * Template mods wrapper.
 *
 * @since 4.2.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Mods class.
 */
class WC_BOGOF_Mods {

	/**
	 * Option name.
	 */
	const OPTION_NAME = 'wc_bogof_mods';

	/**
	 * Mods values.
	 *
	 * @var array Values cached.
	 */
	private static $cache = false;

	/**
	 * Available mods.
	 *
	 * @var array Values cached.
	 */
	private static $mod_ids = [
		'show_legacy_options',
		'loop_columns',
		'loop_columns_device.tablet',
		'loop_columns_device.mobile',
		'loop_rows',
		'items_layout',
		'show_single_variations',
		'display_price',
		'display_short_description',
		'header_title',
		'header_align',
		'header_font.font-weight',
		'header_font.text-transform',
		'header_font.font-size',
		'body_font.font-weight',
		'body_font.text-transform',
		'body_font.font-size',
		'button_style',
		'button_color',
		'button_color_h',
		'button_bg_color',
		'button_bg_color_h',
		'button_font.font-weight',
		'button_font.text-transform',
		'button_font.font-size',
		'button_padding.top',
		'button_padding.right',
		'button_padding.bottom',
		'button_padding.left',
		'button_radius.top',
		'button_radius.right',
		'button_radius.bottom',
		'button_radius.left',
		'notice_text',
		'notice_button_text',
		'notice_shop_display',
		'notice_style',
		'notice_text_color',
		'notice_bg_color',
		'notice_button_text_color',
		'notice_button_bg_color',
		'notice_bar_sticky',
	];

	/**
	 * Clears the mods cache.
	 */
	public static function clear_cache() {
		static::$cache = false;
	}

	/**
	 * Returns the mod version.
	 */
	public static function version() {
		return md5( wp_json_encode( get_option( static::OPTION_NAME, [] ) ) );
	}

	/**
	 * Returns the available mods.
	 */
	public static function get_mod_ids() {
		return static::$mod_ids;
	}

	/**
	 * Get theme mod.
	 *
	 * @param string $mod Mod key value.
	 * @param string $key If the mod is a group, the key of the group. Optional.
	 * @return mixed Mod value.
	 */
	public static function get( $mod, $key = false ) {
		if ( false === static::$cache ) {
			static::$cache = get_option( static::OPTION_NAME, [] );
		}

		$value = false;

		if ( isset( static::$cache[ $mod ] ) ) {
			$value = static::$cache[ $mod ];
		} else {
			$value = static::default( $mod );
		}

		if ( is_array( $value ) && false !== $key ) {
			$value = isset( $value[ $key ] ) ? $value[ $key ] : static::default( "$mod.$key" );
		}

		return $value;
	}

	/**
	 * Returns the default value.
	 *
	 * @param string $mod Mod key value.
	 * @return mixed
	 */
	public static function default( $mod ) {
		$default = '';

		switch ( $mod ) {
			case 'show_legacy_options':
				$default = 'no';
				break;
			case 'loop_rows':
				$default = 2;
				break;
			case 'loop_columns':
				$default = 4;
				break;
			case 'loop_columns_device.tablet':
				$default = 3;
				break;
			case 'loop_columns_device.mobile':
				$default = 2;
				break;
			case 'items_layout':
				$default = 'default';
				break;
			case 'show_single_variations':
				$default = 'yes';
				break;
			case 'display_price':
				$default = 'yes';
				break;
			case 'display_short_description':
				$default = 'no';
				break;
			case 'header_font.font-size':
				$default = 22;
				break;
			case 'header_align':
				$default = 'center';
				break;
			case 'body_font.font-size':
			case 'button_font.font-size':
				$default = 14;
				break;
			case 'header_font':
			case 'body_font':
			case 'button_font':
			case 'button_padding':
			case 'button_radius':
			case 'loop_columns_device':
				$default = [];
				break;
			case 'header_title':
				$default = __( 'Choose [qty] product(s)', 'wc-buy-one-get-one-free' );
				break;
			case 'notice_button_text':
				$default = __( 'Click here to choose your gift', 'wc-buy-one-get-one-free' );
				break;
			case 'notice_shop_display':
				$default = 'no';
				break;
			case 'notice_style':
				$default = 'woo';
				break;
			case 'notice_text_color':
				$default = '#111';
				break;
			case 'notice_bg_color':
				$default = '#fff8c1';
				break;
			case 'notice_button_text_color':
				$default = '#fff';
				break;
			case 'notice_button_bg_color':
				$default = '#e74c3c';
				break;
			case 'notice_bar_sticky':
				$default = 'yes';
				break;
		}
		return $default;
	}

	/**
	 * Returns the default value.
	 *
	 * @param string $mod Mod key value.
	 * @return mixed
	 */
	public static function sanitize_callback( $mod ) {
		$callback = 'sanitize_text_field';

		switch ( $mod ) {
			case 'loop_rows':
			case 'loop_columns':
			case 'header_font.font-size':
			case 'body_font.font-size':
				$callback = 'absint';
				break;
		}
		return $callback;
	}

	/**
	 * Returns the CSS vars.
	 */
	public static function generate_css_vars() {
		$css_vars = '';

		foreach ( [ 'header_font', 'body_font', 'button_font' ] as $mod ) {
			$css_vars .= static::get_font_css_vars( $mod );
		}

		foreach ( [ 'button_padding', 'button_radius' ] as $mod ) {
			$css_vars .= static::get_box_css_vars( $mod );
		}

		foreach ( [ 'header_align', 'button_color', 'button_bg_color', 'button_color_h', 'button_bg_color_h', 'notice_text_color', 'notice_bg_color', 'notice_button_text_color', 'notice_button_bg_color' ] as $mod ) {
			$value = static::get( $mod );

			if ( empty( $value ) ) {
				continue;
			}

			$css_vars .= "--wc-bogof-{$mod}: {$value};";
		}

		$css_vars .= static::get_icons_css_vars();

		return "body{{$css_vars}}";
	}

	/**
	 * Return the CSS vars for a font mod.
	 *
	 * @param string $mod Mod key value.
	 * @return string
	 */
	public static function get_font_css_vars( $mod ) {
		$css_vars = '';
		foreach ( [ 'font-weight', 'text-transform', 'font-size' ] as $key ) {
			$value = static::get( $mod, $key );

			if ( empty( $value ) ) {
				continue;
			}

			if ( 'font-size' === $key ) {
				$value .= 'px';
			}

			$var_name  = str_replace( '_font', '', $mod );
			$css_vars .= "--wc-bogof-{$var_name}-{$key}: {$value};";
		}
		return $css_vars;
	}

	/**
	 * Return the CSS vars for a font mod.
	 *
	 * @param string $mod Mod key value.
	 * @return string
	 */
	public static function get_box_css_vars( $mod ) {

		$css_value = [];

		foreach ( [ 'top', 'right', 'bottom', 'left' ] as $key ) {
			$value = static::get( $mod, $key );

			if ( empty( $value ) ) {
				$css_value[] = '0';
			} else {
				$css_value[] = "{$value}px";
			}
		}
		return "--wc-bogof-{$mod}: " . implode( ' ', $css_value ) . ';';
	}

	/**
	 * Return the CSS vars for a font mod.
	 *
	 * @return string
	 */
	public static function get_icons_css_vars() {
		$icons = [
			// https://icon-sets.iconify.design/svg-spinners/bars-rotate-fade/ .
			'bars-rotate'    => 'data:image/svg+xml,%3Csvg xmlns="http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg" width="24" height="24" viewBox="0 0 24 24"%3E%3Cg%3E%3Crect width="2" height="5" x="11" y="1" opacity=".14"%2F%3E%3Crect width="2" height="5" x="11" y="1" opacity=".29" transform="rotate(30 12 12)"%2F%3E%3Crect width="2" height="5" x="11" y="1" opacity=".43" transform="rotate(60 12 12)"%2F%3E%3Crect width="2" height="5" x="11" y="1" opacity=".57" transform="rotate(90 12 12)"%2F%3E%3Crect width="2" height="5" x="11" y="1" opacity=".71" transform="rotate(120 12 12)"%2F%3E%3Crect width="2" height="5" x="11" y="1" opacity=".86" transform="rotate(150 12 12)"%2F%3E%3Crect width="2" height="5" x="11" y="1" transform="rotate(180 12 12)"%2F%3E%3CanimateTransform attributeName="transform" calcMode="discrete" dur="0.75s" repeatCount="indefinite" type="rotate" values="0 12 12%3B30 12 12%3B60 12 12%3B90 12 12%3B120 12 12%3B150 12 12%3B180 12 12%3B210 12 12%3B240 12 12%3B270 12 12%3B300 12 12%3B330 12 12%3B360 12 12"%2F%3E%3C%2Fg%3E%3C%2Fsvg%3E',

			// https://icon-sets.iconify.design/ic/baseline-check/ .
			'baseline-check' => 'data:image/svg+xml,%3Csvg xmlns="http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg" width="24" height="24" viewBox="0 0 24 24"%3E%3Cpath d="M9 16.17L4.83 12l-1.42 1.41L9 19L21 7l-1.41-1.41z"%2F%3E%3C%2Fsvg%3E',
		];

		$css_vars = '';
		foreach ( $icons as $key => $value ) {
			$css_vars .= "--wc-bogof-icon-{$key}: url('{$value}');";
		}

		return $css_vars;
	}
}

add_action( 'update_option_' . WC_BOGOF_Mods::OPTION_NAME, [ 'WC_BOGOF_Mods', 'clear_cache' ] );
