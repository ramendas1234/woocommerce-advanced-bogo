;( function( $ ) {

	/**
	 * Removes the duplicate choose your gift notices.
	 */
	const remove_duplicate_notices = function() {
		$('.choose-your-gift-notice.wc-bogof-loaded').remove();
		$('.choose-your-gift-notice').addClass( 'wc-bogof-loaded' );
	};

	remove_duplicate_notices();

	$( document.body ).on( 'updated_wc_div', remove_duplicate_notices );
})( jQuery );