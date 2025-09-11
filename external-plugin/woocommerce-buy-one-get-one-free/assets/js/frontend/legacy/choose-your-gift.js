;( function( $ ) {

	// wc_bogof_choose_your_gift_params is required to continue, ensure the object exists
	if ( typeof wc_bogof_choose_your_gift_params === 'undefined' ) {
		return false;
	}

	/**
	 * Object to handle choose_your_gift events.
	 */
	var choose_your_gift = {

		/**
		 * Initialize event handlers.
		 */
		init: function() {

			$( document.body )
			.on( 'click', '.choose-your-gift-notice .button-choose-your-gift', this.scroll_to_choose_your_gift )
			.on( 'added_to_cart', this.on_added_to_cart )
			.on( 'removed_from_cart', this.refresh_bogof_fragment )
			.on( 'updated_wc_div', this.refresh_bogof_fragment )
			.on( 'updated_wc_bogof_div', this.update_ajax_add_to_cart_buttons );

			this.update_ajax_add_to_cart_buttons();
		},

		/**
		 * Check if a node is blocked for processing.
		 *
		 * @return {bool} True if the DOM Element is UI Blocked, false if not.
		 */
		is_blocked: function() {
			return $( '#wc-choose-your-gift' ).is( '.processing' ) || $( '#wc-choose-your-gift' ).parents( '.processing' ).length;
		},

		/**
		 * Block a node visually for processing.
		 *
		 */
		block: function() {
			if ( ! choose_your_gift.is_blocked() ) {
				$( '#wc-choose-your-gift' ).addClass( 'processing' ).block( {
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				} );
			}
		},

		/**
		 * Unblock a node after processing is complete.
		 */
		unblock: function() {
			$( '#wc-choose-your-gift' ).removeClass( 'processing' ).unblock();
		},

		/**
		 * Is cart page?
		 */
		is_cart: function() {
			return 'undefined' !== typeof wc_bogof_choose_your_gift_params.is_cart && 'yes' === wc_bogof_choose_your_gift_params.is_cart && $( '.woocommerce-cart-form' ).length > 0;
		},

		/**
		 * Refresh AJAX add to cart data.
		 */
		update_ajax_add_to_cart_buttons: function() {
			$('#wc-choose-your-gift[data-parameters] .ajax_add_to_cart').data( 'wc_bogof_data',
				$.extend(
					$('#wc-choose-your-gift').data('parameters'),
					{is_cart: choose_your_gift.is_cart()}
				)
			);
		},

		/**
		 * Replace the #wc-choose-your-gift HTML content.
		 *
		 * @param {String} html_str The HTML string with which to replace the div.
		 */
		replace_content: function( html_str ) {
			try {

				choose_your_gift.block();

				var $html    = $.parseHTML( html_str );
				var $new_div = $( '#wc-choose-your-gift', $html );

				$( '#wc-choose-your-gift' ).replaceWith( $new_div );

				$( document.body ).trigger( 'updated_wc_bogof_div' );

				choose_your_gift.unblock();

			} catch ( error ) {
				window.console.log(error);
			}
		},

		/**
	 	 * Scroll down to the #wc-choose-your-gift.
		 */
		scroll_to_choose_your_gift: function(e) {
			if ( $( '#wc-choose-your-gift').length < 1) {
				return;
			}

			e.preventDefault();
			$( 'html, body' ).animate({
				scrollTop: $( '#wc-choose-your-gift' ).offset().top - 100
			}, 1000 );
		},

		/**
		 * On added to the cart.
		 */
		on_added_to_cart: function( e, fragments ) {
			if ( choose_your_gift.is_cart() ) {
				// Refresh on updated_wc_div.
				return;
			}

			if ( fragments && 'undefined' !== typeof fragments.wc_choose_your_gift_data && 'yes' === fragments.wc_choose_your_gift_data.is_choose_your_gift ) {
				// fragments includes the choose your gift data.
				choose_your_gift.update_fragments( fragments );
			} else {
				// Force update.
				choose_your_gift.refresh_bogof_fragment();
			}
		},

		/**
		 * Refresh choose your gift section.
		 */
		refresh_bogof_fragment: function() {
			var data = $.extend( $('#wc-choose-your-gift').data('parameters'), {is_cart: choose_your_gift.is_cart()} );

			choose_your_gift.block();

			$.post(
				wc_bogof_choose_your_gift_params.wc_ajax_url.toString(),
				data,
				function( response ) {
					choose_your_gift.update_fragments( response );
				}
			);
		},

		/**
		 * Update fragments after add to cart events.
		 */
		update_fragments: function( fragments ) {
			if ( fragments && 'undefined' !== typeof fragments.wc_choose_your_gift_data ) {
				var data = fragments.wc_choose_your_gift_data;

				if ( 'undefined' !== typeof data.cart_redirect && 'yes' === data.cart_redirect ) {
					window.location = wc_bogof_choose_your_gift_params.cart_url;
					return;
				}

				if ( 'undefined' !== typeof data.notice ){
					choose_your_gift.show_notice( data.notice );
				}

				if ( 'undefined' !== typeof data.content ){
					choose_your_gift.replace_content( '<div>' + data.content + '</div>' );
				}

				choose_your_gift.unblock();
			}
		},

		/**
		 * Shows new notices on the page.
		 *
		 * @param {Object} The Notice HTML Element in string or object form.
		 */
		show_notice: function( html_element ) {
			$target = $( '.woocommerce-choose-your-gift-notice-wrapper' );
			if ( $target.length > 0 ) {
				$target.html(
					$(html_element).html()
				);
			}
		},
	};

	choose_your_gift.init();

})( jQuery );