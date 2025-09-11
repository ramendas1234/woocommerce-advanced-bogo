<?php
/**
 * Legacy Choose your gift shortcode
 *
 * @since 4.2
 * @package  WC_BOGOF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Choose your gift shortcode class.
 */
class WC_BOGOF_Shortcode_Legacy_Notice {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'admin_notices', [ __CLASS__, 'notice' ], 100 );
	}

	/**
	 * Display the notice.
	 */
	public static function notice() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		if ( ! in_array( $screen_id, [ 'edit-shop_bogof_rule', 'shop_bogof_rule' ], true ) ) {
			return;
		}
		self::ouput();
	}

	/**
	 * Ouput the notice.
	 */
	public static function ouput() {
		?>
		<div class="notice notice-warning is-dismissible">
			<p><strong>Buy One Get One Free</strong></p>
			<hr />
			<p>
				<?php esc_html_e( 'The "After cart" option and the [wc_choose_your_gift] shortcode are deprecated and have been replaced by a popup window.', 'wc-buy-one-get-one-free' ); ?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=shop_bogof_rule_settings' ) ); ?>">
					<?php esc_html_e( 'Update the plugin settings', 'wc-buy-one-get-one-free' ); ?>
				</a>
				<a class="button" rel="noopener noreferrer" target="_blank" href="https://woo.com/document/buy-one-get-one-free/setup-free-gifts-offer/">
					<?php esc_html_e( 'Learn more', 'wc-buy-one-get-one-free' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
