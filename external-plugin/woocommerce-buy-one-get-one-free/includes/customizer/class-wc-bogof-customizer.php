<?php
/**
 * Adds options to the customizer for WooCommerce.
 *
 * @since 4.2.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Customizer class.
 */
class WC_BOGOF_Customizer {

	/**
	 * Init hooks
	 */
	public static function init() {
		if ( ! self::is_customizer() ) {
			return;
		}

		if ( self::is_fse_theme() && self::is_bogo_customizer() ) {
			add_action( 'customize_register', [ __CLASS__, 'remove_sections' ], 199 );
			add_filter( 'customize_loaded_components', '__return_empty_array', 200 );
			add_action( 'customize_controls_print_footer_scripts', [ __CLASS__, 'footer_scripts' ], 5 );
		}

		add_action( 'customize_register', [ __CLASS__, 'customize_register' ], 200 );
		add_action( 'customize_controls_enqueue_scripts', [ __CLASS__, 'controls_enqueue_scripts' ] );
		add_action( 'customize_preview_init', [ __CLASS__, 'preview_init' ] );
	}

	/**
	 * Returns true if it is a customizer request.
	 */
	private static function is_customizer() {
		global $pagenow;
		return 'customize.php' === $pagenow || isset( $_REQUEST['customize_theme'] ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Returns whether the active theme is a block-based theme or not.
	 *
	 * @return boolean
	 */
	private static function is_fse_theme() {
		if ( function_exists( 'wp_is_block_theme' ) ) {
			return (bool) wp_is_block_theme();
		}
		if ( function_exists( 'gutenberg_is_fse_theme' ) ) {
			return (bool) gutenberg_is_fse_theme();
		}
	}

	/**
	 * Returns whether the request is from the BOGO pages or not.
	 *
	 * @return boolean
	 */
	private static function is_bogo_customizer() {
		$autofocus = empty( $_GET['autofocus'] ) ? false : wc_clean( wp_unslash( $_GET['autofocus'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		return is_array( $autofocus ) && in_array( WC_BOGOF_Mods::OPTION_NAME, $autofocus, true );
	}

	/**
	 * Removes unneeded Customizer sections.
	 *
	 * @param WP_Customize_Manager $manager Theme Customizer object.
	 */
	public static function remove_sections( $manager ) {
		$sections = $manager->sections();

		foreach ( $sections as $id => $section ) {
			$manager->remove_section( $id );
		}
	}

	/**
	 * Add settings to the customizer.
	 *
	 * @param WP_Customize_Manager $manager Theme Customizer object.
	 */
	public static function customize_register( $manager ) {

		$manager->add_panel(
			WC_BOGOF_Mods::OPTION_NAME,
			[
				'priority'       => 201,
				'capability'     => 'manage_woocommerce',
				'theme_supports' => '',
				'title'          => __( 'Buy One Get One Free', 'wc-buy-one-get-one-free' ),
			]
		);

		static::includes_control_classes();
		static::add_customizer_settings( $manager );
		static::add_customizer_controls( $manager );

		if ( self::is_bogo_customizer() ) {
			$manager->set_autofocus( [ 'panel' => WC_BOGOF_Mods::OPTION_NAME ] );
		}

	}

	/**
	 * Includes control classes.
	 */
	private static function includes_control_classes() {
		include_once dirname( __FILE__ ) . '/controls/class-wc-bogof-customize-control.php';
		include_once dirname( __FILE__ ) . '/controls/class-wc-bogof-customize-control-slider.php';
		include_once dirname( __FILE__ ) . '/controls/class-wc-bogof-customize-control-accordion.php';
		include_once dirname( __FILE__ ) . '/controls/class-wc-bogof-customize-control-font-manager.php';
		include_once dirname( __FILE__ ) . '/controls/class-wc-bogof-customize-control-radio-image.php';
		include_once dirname( __FILE__ ) . '/controls/class-wc-bogof-customize-control-toggle.php';
		include_once dirname( __FILE__ ) . '/controls/class-wc-bogof-customize-control-box-sides.php';
		include_once dirname( __FILE__ ) . '/controls/class-wc-bogof-customize-control-button-group.php';
	}

	/**
	 * Add the customizer settings.
	 *
	 * @param WP_Customize_Manager $manager Theme Customizer object.
	 */
	private static function add_customizer_settings( $manager ) {
		$defaults = [
			'type'       => 'option',
			'capability' => 'manage_woocommerce',
		];

		foreach ( WC_BOGOF_Mods::get_mod_ids() as $mod_id ) {

			$setting_id = static::get_setting_id( $mod_id );

			$manager->add_setting(
				$setting_id,
				array_merge(
					$defaults,
					[
						'default'           => WC_BOGOF_Mods::default( $mod_id ),
						'sanitize_callback' => WC_BOGOF_Mods::sanitize_callback( $mod_id ),
					]
				)
			);
		}
	}

	/**
	 * Add the customizer settings.
	 *
	 * @param WP_Customize_Manager $manager Theme Customizer object.
	 */
	private static function add_customizer_controls( $manager ) {

		$config = include dirname( __FILE__ ) . '/config.php';

		foreach ( $config as $section ) {

			$section_id = WC_BOGOF_Mods::OPTION_NAME . "_{$section['id']}";

			$manager->add_section(
				$section_id,
				[
					'title'    => $section['title'],
					'priority' => $section['priority'],
					'panel'    => WC_BOGOF_Mods::OPTION_NAME,
				]
			);

			foreach ( $section['controls'] as $control ) {

				if ( isset( $control['mod'] ) ) {
					$setting_id = static::get_setting_id( $control['mod'] );
				} else {
					$setting_id = $section_id . '_' . str_replace( '_', '-', sanitize_title( $control['label'] ) );
					$manager->add_setting( $setting_id );
				}

				$control_id          = static::get_control_id( $setting_id );
				$control['section']  = $section_id;
				$control['settings'] = $setting_id;

				$type = $control['type'];

				if ( class_exists( $type ) ) {

					unset( $control['type'] );

					$manager->add_control(
						new $type(
							$manager,
							$control_id,
							$control
						)
					);

				} else {

					$manager->add_control(
						new WC_BOGOF_Customize_Control(
							$manager,
							$control_id,
							$control
						)
					);
				}

				if ( isset( $control['transport'] ) ) {
					$manager->get_setting( $setting_id )->transport = $control['transport'];
				}
			}
		}
	}

	/**
	 * Returns the setting ID from the mod ID.
	 *
	 * @param string|array $mod_id Mod key value.
	 */
	public static function get_setting_id( $mod_id ) {
		$settings = '';

		if ( is_array( $mod_id ) ) {
			$settings = [];
			foreach ( $mod_id as $key => $id ) {
				$settings[ $key ] = static::get_setting_id( $id );
			}
		} else {
			$settings = WC_BOGOF_Mods::OPTION_NAME . '[' . str_replace( '.', '][', $mod_id ) . ']';
		}

		return $settings;
	}

	/**
	 * Returns the control ID from the settings.
	 *
	 * @param string|array $settings Settings value.
	 */
	private static function get_control_id( $settings ) {
		$control_id = '';

		if ( is_array( $settings ) ) {
			$control_id = implode(
				'',
				array_unique(
					array_filter(
						explode(
							']',
							str_replace(
								'[]',
								'',
								str_replace(
									array_keys(
										$settings
									),
									'',
									implode( '', $settings )
								)
							)
						)
					)
				)
			);
		} else {
			$control_id = $settings;
		}

		return str_replace( [ '[', ']' ], [ '_', '' ], $control_id );
	}

	/**
	 * Print customizer scripts and styles.
	 */
	public static function controls_enqueue_scripts() {
		wp_enqueue_style( 'wc-bogof-customizer', plugins_url( 'includes/customizer/css/customizer.css', WC_BOGOF_PLUGIN_FILE ), [], WC_Buy_One_Get_One_Free::VERSION );
		wp_enqueue_script( 'wc-bogof-customizer', plugins_url( 'includes/customizer/js/customizer.js', WC_BOGOF_PLUGIN_FILE ), [ 'jquery', 'jquery-ui-slider', 'customize-controls' ], WC_Buy_One_Get_One_Free::VERSION, true );
		wp_localize_script(
			'wc-bogof-customizer',
			'wc_bogof_customizer_params',
			[
				'panel_id'      => WC_BOGOF_Mods::OPTION_NAME,
				'shop_page_url' => get_permalink( wc_get_page_id( 'shop' ) ),
			]
		);
		if ( is_rtl() ) {
			wp_enqueue_style( 'wc-bogof-customizer-rtl', plugins_url( 'includes/customizer/css/customizer-rtl.css', WC_BOGOF_PLUGIN_FILE ), [], WC_Buy_One_Get_One_Free::VERSION );
		}
	}

	/**
	 * Output footer scrips
	 */
	public static function footer_scripts() {
		?>
		<script>
			document.body.classList.add('wc-bogo-fse-customizer');
		</script>
		<?php
	}
	/**
	 * Customizer preview init.
	 */
	public static function preview_init() {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'preview_scripts' ] );
	}

	/**
	 * Print preview scripts and styles.
	 */
	public static function preview_scripts() {

		$context = empty( $_GET['wc_bogof_mod'] ) ? false : wc_clean( wp_unslash( $_GET['wc_bogof_mod'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! $context ) {
			return;
		}

		$gift_loop = self::get_gift_loop();

		$suffix   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$localize = [
			'context'                 => $context,
			'available_free_quantity' => ( $gift_loop ? $gift_loop->get_available_free_quantity() : 1 ),
		];
		$deps     = [ 'customize-preview', 'jquery' ];

		wp_enqueue_style( 'wc-bogof-modal-gifts', plugins_url( 'assets/css/modal-gifts.css', WC_BOGOF_PLUGIN_FILE ), [], WC_Buy_One_Get_One_Free::VERSION );
		wp_add_inline_style(
			'wc-bogof-modal-gifts',
			WC_BOGOF_Mods::generate_css_vars()
		);

		if ( 'gifts_container' === $context ) {

			wp_enqueue_style( 'wc-bogof-modal', plugins_url( 'assets/css/modal.css', WC_BOGOF_PLUGIN_FILE ), [], WC_Buy_One_Get_One_Free::VERSION );
			wp_enqueue_script( 'wc-bogof-modal', plugins_url( 'assets/js/admin/modal' . $suffix . '.js', WC_BOGOF_PLUGIN_FILE ), [ 'jquery' ], WC_Buy_One_Get_One_Free::VERSION, true );

			if ( $gift_loop ) {
				$fragments = [
					'div.wc-bogof-products'       => $gift_loop->get_the_loop(),
					'div.wc-bogo-modal-header>h3' => esc_html( str_replace( '[qty]', $gift_loop->get_available_free_quantity(), WC_BOGOF_Mods::get( 'header_title' ) ) ),
				];
			} else {
				$fragments = [
					'div.wc-bogo-modal-body' => self::get_blank_state(),
				];
			}

			$localize['fragments'] = $fragments;
			$deps[]                = 'wc-bogof-modal';
		} else {
			$localize['fragments']    = wc_bogof_gift_notice_text( ( $gift_loop ? $gift_loop->get_available_free_quantity() : 1 ), [ 100 ] );
			$localize['notice_style'] = WC_BOGOF_Mods::get( 'notice_style' );
		}

		wp_enqueue_script( 'wc-bogof-customizer-preview', plugins_url( 'assets/js/build/customizer-preview.js', WC_BOGOF_PLUGIN_FILE ), $deps, WC_Buy_One_Get_One_Free::VERSION, true );
		wp_localize_script( 'wc-bogof-customizer-preview', 'wc_bogof_customizer_preview_params', $localize );

		// Output templates on footer.
		add_action( 'wp_footer', 'wc_bogof_gift_template_notice', 0 );
		add_action( 'wp_footer', 'wc_bogof_gift_modal_dialog', 0 );
	}

	/**
	 * Returns the loop fragment.
	 */
	private static function get_gift_loop() {

		$data_store = WC_Data_Store::load( 'bogof-rule' );
		$rules      = array_filter( $data_store->get_rules(), [ __CLASS__, 'filter_rules_callback' ] );
		$rule       = array_shift( $rules );
		$cart_rule  = $rule ? new WC_BOGOF_Cart_Rule( $rule ) : false;
		$loop       = false;

		if ( $cart_rule ) {

			add_filter( 'wc_bogof_free_item_quantity', [ __CLASS__, 'filter_available_free_quantity' ], 10000, 4 );

			$loop = new WC_BOGOF_Gifts_Loop( [ $cart_rule ], 1 );

			remove_filter( 'wc_bogof_free_item_quantity', [ __CLASS__, 'filter_available_free_quantity' ], 10000, 4 );
		}

		return $loop;
	}

	/**
	 * Filter rules callback to return only the "Choose a gift" rules.
	 *
	 * @param WC_BOGOF_Rule $rule Rule instance.
	 * @return bool
	 */
	private static function filter_rules_callback( $rule ) {
		return 'buy_a_get_b' === $rule->get_type() && 'choose_from' === $rule->get_action() && ( ! is_wp_error( $rule->validate_props() ) && apply_filters( 'wc_bogof_customizer_preview_rule', true, $rule ) );
	}

	/**
	 * Filter the available available free quantity.
	 *
	 * @param int                $free_quantity Available free quantity.
	 * @param int                $cart_quantity Cart quantity.
	 * @param WC_BOGOF_Rule      $rule Rule instance.
	 * @param WC_BOGOF_Cart_Rule $cart_rule instance.
	 */
	public static function filter_available_free_quantity( $free_quantity, $cart_quantity, $rule, $cart_rule ) {
		if ( $free_quantity - WC_BOGOF_Cart::get_free_quantity( $cart_rule->get_id() ) < 1 ) {
			$free_quantity = WC_BOGOF_Cart::get_free_quantity( $cart_rule->get_id() ) + 1;
		}

		return $free_quantity;
	}

	/**
	 * Returns blank state.
	 */
	private static function get_blank_state() {
		wp_enqueue_style( 'dashicons' );
		ob_start();
		?>
		<div class="wc-bogof-gifts-blank">
			<div class="wc-bogof-gifts-blank-icon">
				<span class="dashicons dashicons-format-image"></span>
			</div>
			<h2 class="wc-bogof-gifts-blank-message">
			<?php esc_html_e( 'The gifts will appear here once you have configured a "Product as a gift" promotion.', 'wc-buy-one-get-one-free' ); ?>
			</h2>
		</div>
		<style>.wc-bogof-gifts-blank{width:100%;text-align:center;margin:0 auto}.wc-bogof-gifts-blank-icon{margin-bottom:1.6rem}.wc-bogof-gifts-blank-icon span.dashicons{position:relative;box-sizing:content-box;width:96px;height:96px;white-space:nowrap;font-size:96px;line-height:1}.wc-bogof-gifts-blank-message{font-size:1.2rem;margin:0 auto 2rem;line-height:1.5em;max-width:600px;font-weight:500}</style>
		<?php

		return ob_get_clean();
	}

}
