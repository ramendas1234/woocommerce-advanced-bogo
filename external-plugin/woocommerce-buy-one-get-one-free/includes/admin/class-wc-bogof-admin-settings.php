<?php
/**
 * WooCommerce Buy One Get One Free admin settings
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Admin_Settings Class
 */
class WC_BOGOF_Admin_Settings {

	/**
	 * Init hooks
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'create_page' ) );
	}

	/**
	 * Add settings page to the dashboard menu.
	 */
	public static function create_page() {
		if ( ! is_admin() || empty( $_GET['page'] ) || 'shop_bogof_rule_settings' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			// Create page only for valid ID.
			return;
		}

		$page = add_submenu_page(
			'woocommerce',
			__( 'Settings', 'wc-buy-one-get-one-free' ),
			__( 'Settings', 'wc-buy-one-get-one-free' ),
			'manage_woocommerce',
			'shop_bogof_rule_settings',
			array( __CLASS__, 'output' )
		);

		// Save settings.
		add_action( 'admin_init', array( __CLASS__, 'save' ) );

		// Remove the page after create it.
		add_action( 'admin_head', array( __CLASS__, 'remove_page' ) );

		// WC_Admin_Settings is required.
		if ( ! class_exists( 'WC_Admin_Settings', false ) ) {
			include WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';
		}

		add_action( 'woocommerce_admin_field_wc_bogof_customize_gifts_link', array( __CLASS__, 'customize_gifts_link' ) );
		add_action( 'woocommerce_admin_field_wc_bogof_legacy_notice', array( __CLASS__, 'legacy_notice' ) );
	}

	/**
	 * Handle saving of settings.
	 *
	 * @return void
	 */
	public static function save() {
		// We should only save on the settings page.
		if ( empty( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		check_admin_referer( 'wc-bogo-settings' );

		WC_Admin_Settings::add_message( __( 'Your settings have been saved.', 'woocommerce' ) );

		$settings = self::get_settings();
		$data     = wc_clean( $_POST );

		woocommerce_update_options( $settings, $data );
	}

	/**
	 * Remove the page from menu.
	 */
	public static function remove_page() {
		remove_submenu_page( 'woocommerce', 'shop_bogof_rule_settings' );
	}

	/**
	 * Output the HTML for the settings.
	 */
	public static function output() {
		$settings = self::get_settings();
		include dirname( __FILE__ ) . '/views/html-settings-page.php';
	}

	/**
	 * Return the legacy settings array.
	 *
	 * @return array
	 */
	private static function get_legacy_settings() {
		return array(
			array(
				'title' => __( 'Choose a gift(s)', 'wc-buy-one-get-one-free' ),
				'type'  => 'title',
			),
			array(
				'title'    => __( 'Display eligible free gift(s) on', 'wc-buy-one-get-one-free' ),
				'desc_tip' => __( 'Where do you want to show the free eligible products?', 'wc-buy-one-get-one-free' ),
				'id'       => 'wc_bogof_cyg_display_on',
				'type'     => 'radio',
				'options'  => [
					'after_cart'  => __( 'After the cart', 'wc-buy-one-get-one-free' ),
					/* Translators: %s Page contents. */
					'custom_page' => sprintf( __( 'A page that contains the %s shortcode', 'wc-buy-one-get-one-free' ), '[wc_choose_your_gift]' ),
					''            => __( 'Popup window', 'wc-buy-one-get-one-free' ),
				],
				'default'  => 'after_cart',
			),
			array(
				'title'       => __( 'Title', 'wc-buy-one-get-one-free' ),
				'desc'        => __( 'The title of the "choose your gift" area.', 'wc-buy-one-get-one-free' ),
				'id'          => 'wc_bogof_cyg_title',
				'type'        => 'text',
				'css'         => 'min-width:300px;',
				'placeholder' => __( 'Choose your gift', 'wc-buy-one-get-one-free' ),
			),
			array(
				'title'    => __( 'Choose your gift page', 'wc-buy-one-get-one-free' ),
				/* Translators: %s Page contents. */
				'desc_tip' => sprintf( __( 'Page contents: %s', 'wc-buy-one-get-one-free' ), '[wc_choose_your_gift]' ),
				'id'       => 'wc_bogof_cyg_page_id',
				'type'     => 'single_select_page',
				'default'  => '',
				'class'    => 'wc-enhanced-select-nostd',
				'css'      => 'min-width:300px;',
				'desc'     => __( 'This page needs to be set so that WooCommerce knows where to send users to choose the free products.', 'wc-buy-one-get-one-free' ),
			),
			array(
				'title'       => __( 'Notice', 'wc-buy-one-get-one-free' ),
				'desc'        => __( 'This text will inform the customer that he can choose a gift. Use [qty] for the number of items.', 'wc-buy-one-get-one-free' ),
				'id'          => 'wc_bogof_cyg_notice',
				'type'        => 'text',
				'css'         => 'min-width:300px;',
				/* Translators: %s [qty] placeholder. */
				'placeholder' => sprintf( __( 'You can now add %s product(s) for free to the cart.', 'wc-buy-one-get-one-free' ), '[qty]' ),
			),
			array(
				'title'       => __( 'Button text', 'wc-buy-one-get-one-free' ),
				'id'          => 'wc_bogof_cyg_notice_button_text',
				'type'        => 'text',
				'css'         => 'min-width:300px;',
				'placeholder' => __( 'Choose your gift', 'wc-buy-one-get-one-free' ),
			),
			array(
				'type' => 'wc_bogof_legacy_notice',
			),
			array(
				'type' => 'sectionend',
			),
		);
	}

	/**
	 * Whether we should display the legacy options.
	 *
	 * @return boolean
	 */
	private static function should_display_legacy_options() {
		return wc_string_to_bool( WC_BOGOF_Mods::get( 'show_legacy_options' ) ) || '' !== get_option( 'wc_bogof_cyg_display_on', '' );
	}

	/**
	 * Return settings array.
	 *
	 * @return array
	 */
	private static function get_settings() {

		if ( self::should_display_legacy_options() ) {
			$gifts_display_settings = self::get_legacy_settings();
		} else {
			$gifts_display_settings = [ [] ];
		}

		return array_merge(
			$gifts_display_settings,
			array(
				array(
					'title' => self::should_display_legacy_options() ? __( 'Advanced', 'wc-buy-one-get-one-free' ) : '',
					'type'  => 'title',
				),
				array(
					'title'   => __( 'On sale products', 'wc-buy-one-get-one-free' ),
					'desc'    => __( 'Calculate the discount using the "regular price".', 'wc-buy-one-get-one-free' ),
					'id'      => 'wc_bogof_base_regular_price',
					'default' => 'no',
					'type'    => 'checkbox',
				),
				array(
					'title'   => __( 'Disable coupons', 'wc-buy-one-get-one-free' ),
					'desc'    => __( 'Disable coupons usage if there is a free BOGO item in the cart.', 'wc-buy-one-get-one-free' ),
					'id'      => 'wc_bogof_disable_coupons',
					'default' => 'no',
					'type'    => 'checkbox',
				),
				array(
					'title'   => __( 'Custom attributes', 'wc-buy-one-get-one-free' ),
					'desc'    => __( 'Include the custom product attributes in the "Variation attribute" condition.', 'wc-buy-one-get-one-free' ),
					'id'      => 'wc_bogof_include_custom_attributes',
					'default' => 'no',
					'type'    => 'checkbox',
				),
				array(
					'type' => 'sectionend',
				),
			)
		);
	}

	/**
	 * Ouput the legacy notice.
	 */
	public static function legacy_notice() {
		?>
		<tr>
			<th style="padding-top:0;padding-bottom:0;"></th>
			<td style="padding-top:0;">
				<div class="notice notice-warning inline" style="max-width: 400px;margin:0;padding: 16px">
					<div style="display:flex;">
						<p><span style="font-size: 24px;width: 24px;height: 24px;margin-right: 8px;" class="dashicons dashicons-warning"></span></p>
						<p style="font-size:13px; margin-bottom:16px;">
							<?php esc_html_e( 'The "After cart" option and the [wc_choose_your_gift] shortcode are deprecated and have been replaced by a popup window.', 'wc-buy-one-get-one-free' ); ?>
						</p>
					</div>
					<p>
						<a id="remove-legacy-options" class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=shop_bogof_rule_settings' ), 'wc_bogof_remove_legacy_options', 'wc_bogof_remove_legacy_options_nonce' ) ); ?>">
							<?php esc_html_e( 'Remove legacy options', 'wc-buy-one-get-one-free' ); ?>
						</a>
						<a rel="noopener noreferrer" target="_blank" href="https://woo.com/document/buy-one-get-one-free/setup-free-gifts-offer/" style="display: inline-block;font-size: 13px;line-height: 2.15384615;min-height: 30px;margin: 0;padding: 0 10px;">
							<?php esc_html_e( 'Learn more', 'wc-buy-one-get-one-free' ); ?>
						</a>
					</p>
				</div>
			</td>
		</tr>
		<?php
	}
}
