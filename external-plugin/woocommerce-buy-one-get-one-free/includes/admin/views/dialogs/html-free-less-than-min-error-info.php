<?php
/**
 * Free less than min error more info dialog content.
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="wc-bogo-modal-header">
	<h3><?php esc_html_e( 'Get the cheapest product for free', 'wc-buy-one-get-one-free' ); ?></h3>
</div>
<div class="wc-bogo-modal-body">
	<p>
		<?php esc_html_e( 'This type of promotion applies the discount to the cheapest product the user added to the cart.', 'wc-buy-one-get-one-free' ); ?><br>
		<?php
		// Translators: HTML tags.
		printf( esc_html__( 'For example, if you set %1$sbuy 3 get 1%2$s, it works as: "If the customer adds 3 items to the cart, 1 of them will be free."', 'wc-buy-one-get-one-free' ), '<strong>', '</strong>' );
		?>
	</p>
	<p>
		<?php
		// Translators: HTML tags.
		printf( esc_html__( 'Read more about the %1$spromotion types%2$s', 'wc-buy-one-get-one-free' ), '<a href="https://woocommerce.com/document/buy-one-get-one-free/getting-started/#promotion-types" target="_blank" rel="noopener noreferrer" class="is-external">', '</a>' );
		?>
	</p>
</div>
<div class="wc-bogo-modal-footer">
	<button class="button button-primary" data-dismiss="wc-bogo-modal"><?php esc_html_e( 'OK', 'wc-buy-one-get-one-free' ); ?></button>
</div>
