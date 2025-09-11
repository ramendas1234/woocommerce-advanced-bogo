;( function( $ ) {

	if ( typeof wc_bogof_single_product_params === 'undefined' ) {
		return false;
	}

	// Redirect on added to cart.
	$( document.body ).on( 'added_to_cart', function( e, fragments ) {
		if ( fragments && 'undefined' !== typeof fragments.wc_choose_your_gift_data ) {
			var data = fragments.wc_choose_your_gift_data;
			if ( 'undefined' !== typeof data.cart_redirect && 'yes' === data.cart_redirect ) {
				if ( false !== data.cart_redirect_url ) {
					window.location = data.cart_redirect_url ? data.cart_redirect_url : wc_bogof_single_product_params.cart_url;
				}
				return;
			} else if ( 'undefined' !== typeof data.choose_your_gift_redirect && 'yes' === data.choose_your_gift_redirect ) {
				window.location = wc_bogof_single_product_params.choose_your_gift_url;
				return;
			}
		}
	} );

})( jQuery );