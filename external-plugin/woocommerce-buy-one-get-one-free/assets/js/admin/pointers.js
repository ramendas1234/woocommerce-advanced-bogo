/* global wc_admin_bogof_pointers_params */
;( function( $ ) {

	if ( 'undefined' === typeof wc_admin_bogof_pointers_params ) {
		return;
	}

	setTimeout( init_wc_pointers, 800 );

	function init_wc_pointers() {
		$.each( wc_admin_bogof_pointers_params.pointers, function( i ) {
			show_wc_pointer( i );
			return false;
		});
	}

	function show_wc_pointer( id ) {
		if ( 'undefined' === typeof wc_admin_bogof_pointers_params.pointers[ id ] ) {
			return;
		}

		var pointer = wc_admin_bogof_pointers_params.pointers[ id ];
		var options = $.extend( pointer.options, {
			pointerClass: 'wp-pointer wc-pointer',
			open: function() {
				if ( 'undefined' !== typeof pointer.scrollToTarget ) {
					document.querySelector(pointer.target).scrollIntoView();
				}
			},
			close: function() {
				do {
					var next_id = pointer.next;
					pointer     = next_id ? wc_admin_bogof_pointers_params.pointers[ pointer.next ] : false
				} while ( next_id && ! ( pointer && $(pointer.target).length > 0 ) );

				if ( next_id ) {
					show_wc_pointer( next_id );
				}
			},
			buttons: function( event, t ) {
				var close   = wc_admin_bogof_pointers_params.i18n.dismiss,
					next    = wc_admin_bogof_pointers_params.i18n.next,
					dismissBtn = $( '<a class="close" href="#">' + close + '</a>' ),
					nextBtn    = $( '<a class="button button-primary" href="#">' + next + '</a>' ),
					wrapper    = $( '<div class="wc-pointer-buttons" />' );

					dismissBtn.on( 'click.pointer', function(e) {
						e.preventDefault();
						t.element.pointer('destroy');
					});

				nextBtn.on( 'click.pointer', function(e) {
					e.preventDefault();
					t.element.pointer('close');
				});

				wrapper.append( dismissBtn );
				if ( pointer.next ) {
					wrapper.append( nextBtn );
				}

				return wrapper;
			},
		} );
		var this_pointer = $( pointer.target ).pointer( options );
		this_pointer.pointer( 'open' );
		if ( 'undefined' !== typeof pointer.css ) {
			$(pointer.css.target).css(pointer.css.style);
		}

		if ( pointer.next_trigger ) {
			$( pointer.next_trigger.target ).on( pointer.next_trigger.event, function() {
				setTimeout( function() { this_pointer.pointer( 'close' ); }, 400 );
			});
		}
	}

})( jQuery );
