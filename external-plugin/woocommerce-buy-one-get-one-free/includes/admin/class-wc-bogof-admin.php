<?php
/**
 * Handle Admin resources.
 *
 * @package WC_BOGOF
 * @since 3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Admin Class.
 */
class WC_BOGOF_Admin {

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_filter( 'woocommerce_screen_ids', array( __CLASS__, 'screen_ids' ) );
		add_action( 'admin_menu', array( __CLASS__, 'connect_pages' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 20 );
		add_action( 'woocommerce_system_status_report', array( __CLASS__, 'system_status_report' ) );
	}

	/**
	 * Add the plugin screens to the WooCommerce screens
	 *
	 * @param array $ids Screen ids.
	 * @return array
	 */
	public static function screen_ids( $ids ) {
		$ids[] = 'shop_bogof_rule';
		$ids[] = 'edit-shop_bogof_rule';
		$ids[] = 'woocommerce_page_shop_bogof_rule_settings';
		return $ids;
	}

	/**
	 * Connect BOGO pages for display the WooCommerce admin header.
	 */
	public static function connect_pages() {
		if ( ! function_exists( 'wc_admin_connect_page' ) ) {
			return;
		}
		wc_admin_connect_page(
			array(
				'id'        => 'woocommerce-bogo-rules',
				'screen_id' => 'edit-shop_bogof_rule',
				'title'     => __( 'Buy One Get One Free', 'wc-buy-one-get-one-free' ),
				'path'      => add_query_arg( 'post_type', 'shop_bogof_rule', 'edit.php' ),
			)
		);

		wc_admin_connect_page(
			array(
				'id'        => 'woocommerce-add-bogo-rule',
				'parent'    => 'woocommerce-bogo-rules',
				'screen_id' => 'shop_bogof_rule-add',
				'title'     => __( 'Buy One Get One Free', 'wc-buy-one-get-one-free' ),
			)
		);

		wc_admin_connect_page(
			array(
				'id'        => 'woocommerce-edit-bogo-rule',
				'parent'    => 'woocommerce-bogo-rules',
				'screen_id' => 'shop_bogof_rule',
				'title'     => __( 'Buy One Get One Free', 'wc-buy-one-get-one-free' ),
			)
		);

		wc_admin_connect_page(
			array(
				'id'        => 'woocommerce-page-shop-bogof-rule-settings',
				'parent'    => 'woocommerce-bogo-rules',
				'screen_id' => 'woocommerce_page_shop_bogof_rule_settings',
				'title'     => __( 'Buy One Get One Free', 'wc-buy-one-get-one-free' ),
			)
		);
	}

	/**
	 * Returns the styles for the screen ID.
	 *
	 * @param string $screen_id Current screen ID.
	 * @return array
	 */
	private static function get_styles( $screen_id ) {
		$styles = array(
			'edit-shop_bogof_rule'                      => array( 'list-table', 'layout-tabs' ),
			'shop_bogof_rule'                           => array( 'metabox', 'layout-tabs', 'modal' ),
			'woocommerce_page_shop_bogof_rule_settings' => array( 'layout-tabs' ),
		);

		return isset( $styles[ $screen_id ] ) ? $styles[ $screen_id ] : array();
	}

	/**
	 * Returns the scripts for the screen ID.
	 *
	 * @param string $screen_id Current screen ID.
	 * @return array
	 */
	private static function get_scripts( $screen_id ) {
		$scripts = array(
			'edit-shop_bogof_rule'                      => array( 'post-status-display', 'layout-tabs', 'list-table', 'pointers' ),
			'shop_bogof_rule'                           => array( 'post-status-display', 'layout-tabs', 'modal', 'metabox', 'pointers' ),
			'woocommerce_page_shop_bogof_rule_settings' => array( 'layout-tabs', 'settings', 'pointers' ),
		);

		$enqueue_scripts = array();
		if ( isset( $scripts[ $screen_id ] ) ) {
			foreach ( $scripts[ $screen_id ] as $script_id ) {
				$data = self::get_script_data( $script_id, $screen_id );
				if ( ! empty( $data ) ) {
					$enqueue_scripts[ $script_id ] = $data;
				}
			}
		}

		return $enqueue_scripts;
	}

	/**
	 * Return a script data by the script ID.
	 *
	 * @param string $id Script ID.
	 * @param string $screen_id Current screen ID.
	 * @return array
	 */
	private static function get_script_data( $id, $screen_id ) {
		$data = array();
		switch ( $id ) {
			case 'list-table':
				$data = array(
					'deps'   => array( 'jquery', 'woocommerce_admin' ),
					'params' => array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'nonces'   => array(
							'rule_toggle' => wp_create_nonce( 'wc-bogof-toggle-rule-enabled' ),
						),
					),
				);
				break;

			case 'metabox':
				$data = array(
					'deps'   => array( 'jquery', 'jquery-ui-datepicker', 'woocommerce_admin', 'wp-util' ),
					'params' => array(
						'i18n'               => array(
							'free_less_than_min_error'  => __( 'Please enter a value less than the buy quantity', 'wc-buy-one-get-one-free' ),
							'field_requires_attention'  => __( 'Validation failed. 1 field requires attention', 'wc-buy-one-get-one-free' ),
							// Translators: %s: number of fields.
							'fields_requires_attention' => __( 'Validation failed. %s fields require attention', 'wc-buy-one-get-one-free' ),
							'more_info'                 => __( 'More info', 'wc-buy-one-get-one-free' ),
						),
						'incompatible_types' => implode( ',', wc_bogof_incompatible_product_types() ),
						'ajaxurl'            => admin_url( 'admin-ajax.php' ),
					),
				);
				break;

			case 'layout-tabs':
				$tabs = [
					[
						'id'    => 'shop_bogof_rule',
						'href'  => esc_attr( admin_url( 'edit.php?post_type=shop_bogof_rule' ) ),
						'title' => __( 'Promotions', 'wc-buy-one-get-one-free' ),
					],
					[
						'id'    => 'woocommerce_page_shop_bogof_rule_settings',
						'href'  => admin_url( 'admin.php?page=shop_bogof_rule_settings' ),
						'title' => __( 'Settings', 'wc-buy-one-get-one-free' ),
					],
				];

				if ( '' === get_option( 'wc_bogof_cyg_display_on', '' ) ) {
					$tabs[] = [
						'id'    => 'wc_bogof_customize_page',
						'href'  => add_query_arg(
							array(
								'autofocus[panel]' => WC_BOGOF_Mods::OPTION_NAME,
								'return'           => admin_url( 'edit.php?post_type=shop_bogof_rule' ),
							),
							wp_customize_url()
						),
						'title' => __( 'Appearance', 'wc-buy-one-get-one-free' ),
					];
				}

				$data = array(
					'deps'   => array( 'jquery', 'woocommerce_admin' ),
					'params' => array(
						'tabs'   => $tabs,
						'active' => str_replace( 'edit-', '', $screen_id ),
					),
				);
				break;

			case 'post-status-display':
				$data = array(
					'deps'   => array( 'jquery' ),
					'params' => array(
						'i18n' => array(
							'publish'           => _x( 'Active', 'post status', 'wc-buy-one-get-one-free' ),
							'wc-bogof-disabled' => _x( 'Disabled', 'post status', 'wc-buy-one-get-one-free' ),
						),
					),
				);
				break;

			case 'settings':
				$data = array(
					'deps'   => array( 'jquery', 'woocommerce_admin' ),
					'params' => array(
						'i18n' => array(
							'save_changes_alert'    => __( 'Please save the settings before continuing', 'wc-buy-one-get-one-free' ),
							// Translators: %s: new line char.
							'remove_legacy_confirm' => sprintf( __( 'Are you sure you want to remove the legacy options? This action cannot be undone.%sYou can test the new Popup by updating the "Display eligible gift(s) on" option.', 'wc-buy-one-get-one-free' ), "\n" ),
							'legacy'                => __( 'Legacy', 'wc-buy-one-get-one-free' ),
							'new'                   => __( 'New', 'wc-buy-one-get-one-free' ),
						),
					),
				);
				break;

			case 'pointers':
				$show = get_option( 'wc_bogof_show_new_features', null );

				if ( isset( $show[ $screen_id ] ) ) {

					$data = array(
						'deps'   => array( 'jquery', 'wp-pointer' ),
						'params' => array(
							'pointers' => self::get_pointers( $screen_id ),
							'i18n'     => array(
								'dismiss' => __( 'Dismiss', 'wc-buy-one-get-one-free' ),
								'next'    => __( 'Next', 'wc-buy-one-get-one-free' ),
							),
						),
						'styles' => array( 'wp-pointer' ),
					);

					// Display new features only once.
					unset( $show[ $screen_id ] );
					if ( count( $show ) ) {
						update_option( 'wc_bogof_show_new_features', $show );
					} else {
						delete_option( 'wc_bogof_show_new_features' );
					}
				}
				break;
			case 'modal':
				$data = array(
					'deps' => array( 'jquery' ),
				);
				break;
		}
		return $data;
	}

	/**
	 * Returns pointers for current screen.
	 *
	 * @param string $screen_id Current screen ID.
	 * @return array
	 */
	private static function get_pointers( $screen_id ) {
		$data = array();
		switch ( $screen_id ) {
			case 'shop_bogof_rule':
				$data = array(
					'start'      => array(
						'target'       => '#_applies_to a.button.add-group',
						'next'         => 'conditions',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html__( 'New: Conditions groups', 'wc-buy-one-get-one-free' ) . '</h3>' .
											'<p>' . esc_html__( 'You can now create more than one group of conditions to filter products. The product will be included if any of the conditions groups are satisfied.', 'wc-buy-one-get-one-free' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'conditions' => array(
						'target'         => '#_conditions',
						'next'           => '',
						'next_trigger'   => [],
						'scrollToTarget' => 1,
						'options'        => array(
							'content'  => '<h3>' . esc_html__( 'New: Rules', 'wc-buy-one-get-one-free' ) . '</h3>' .
											// Translators: 1 and 2: HTML tags.
											'<p>' . esc_html__( 'Version 5.1 introduces the promotion rules. The rules include all the restrictions you could use before and many new conditions.', 'wc-buy-one-get-one-free' ) . '</p>',
							'position' => array(
								'edge'  => 'bottom',
								'align' => 'left',
							),
						),
					),
				);
				break;
			case 'woocommerce_page_shop_bogof_rule_settings':
				$data = array(
					'design' => array(
						'target'       => '#wc-bogo-tab-wc_bogof_customize_page',
						'next'         => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html__( 'Appearance', 'wc-buy-one-get-one-free' ) . '</h3>' .
											'<p>' . esc_html__( 'You can customize the Popup and the available gift notice from the "Appearance" menu.', 'wc-buy-one-get-one-free' ) . '</p>',
							'position' => array(
								'edge'  => 'top',
								'align' => 'left',
							),
						),
					),
				);
				break;
		}
		return $data;
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public static function enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Enqueue scripts.
		$scripts = self::get_scripts( $screen_id );

		foreach ( $scripts as $id => $data ) {
			$js_id = 'wc-admin-bogof-' . $id;

			wp_register_script( $js_id, plugin_dir_url( WC_BOGOF_PLUGIN_FILE ) . 'assets/js/admin/' . $id . $suffix . '.js', $data['deps'], WC_Buy_One_Get_One_Free::VERSION, true );

			if ( ! empty( $data['params'] ) ) {
				$param_name = str_replace( '-', '_', $js_id ) . '_params';
				wp_localize_script( $js_id, $param_name, $data['params'] );
			}

			wp_enqueue_script( $js_id );

			if ( isset( $data['styles'] ) && is_array( $data['styles'] ) ) {
				array_map( 'wp_enqueue_style', $data['styles'] );
			}
		}

		// Enqueue styles.
		$styles = self::get_styles( $screen_id );
		foreach ( $styles as $file ) {
			$style_id = 'wc-admin-bogo-' . $file;
			wp_enqueue_style( $style_id, plugin_dir_url( WC_BOGOF_PLUGIN_FILE ) . 'assets/css/' . $file . '.css', array(), WC_Buy_One_Get_One_Free::VERSION );
		}
	}

	/**
	 * Add plugin info to the system status report.
	 */
	public static function system_status_report() {
		// Generate BOGO rules output.
		$data_store   = WC_Data_Store::load( 'bogof-rule' );
		$active_rules = $data_store->get_rules();
		$rules        = array();
		$categories   = wp_list_pluck( get_terms( 'product_cat', array( 'hide_empty' => 0 ) ), 'slug', 'term_id' );
		$cont         = 0;

		foreach ( $active_rules as $rule ) {
			$data = array_filter( $rule->get_data() );

			unset( $data['id'], $data['date_created'], $data['date_modified'], $data['enabled'] );

			// Built a printable version of the rule properties.
			$text = array();
			foreach ( $data as $prop => $value ) {
				if ( 'meta_data' === $prop ) {
					continue;
				}

				if ( in_array( $prop, array( 'applies_to', 'gift_products', 'conditions' ), true ) ) {
					$value = WC_BOGOF_Conditions::to_string( $value );
				}

				if ( 'quantity_rules' === $prop ) {
					$value = wp_json_encode( $value );
				}

				if ( is_a( $value, 'WC_DateTime' ) ) {
					$value_text = $value->format( 'Y-m-d H:i:s' );
				} elseif ( is_array( $value ) ) {
					$value_text = implode( '; ', $value );
				} else {
					$value_text = $value;
				}

				$text[] = sprintf( '%s: "%s"', $prop, $value_text );
			}

			$rules[ $rule->get_id() ] = implode( '; ', $text );

			$cont++;
			if ( 10 === $cont ) {
				// Only display the first 10 rules.
				break;
			}
		}

		// Choose your gift page.
		$wc_gift_page = false;
		if ( 'custom_page' === get_option( 'wc_bogof_cyg_display_on' ) ) {
			$wc_gift_page = array(
				'page_id'           => get_option( 'wc_bogof_cyg_page_id' ),
				'page_set'          => false,
				'page_exists'       => false,
				'page_visible'      => false,
				'shortcode_present' => false,
			);

			if ( $wc_gift_page['page_id'] ) {
				$wc_gift_page['page_set'] = true;
			}
			if ( get_post( $wc_gift_page['page_id'] ) ) {
				$wc_gift_page['page_exists'] = true;
			}
			if ( 'publish' === get_post_status( $wc_gift_page['page_id'] ) ) {
				$wc_gift_page['page_visible'] = true;
			}
			if ( wc_bogof_has_choose_your_gift_shortcode( $wc_gift_page['page_id'] ) ) {
				$wc_gift_page['shortcode_present'] = true;
			}
		}

		include dirname( __FILE__ ) . '/views/hml-system-status-report.php';
	}
}
