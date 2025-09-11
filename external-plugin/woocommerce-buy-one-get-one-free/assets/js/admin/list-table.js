/* global wc_admin_bogof_list_table_params */
;( function( $ ) {

	if ( 'undefined' === typeof wc_admin_bogof_list_table_params ) {
		return;
	}

	// Toggle rule on/off.
	$( 'tr.type-shop_bogof_rule' ).on( 'click', '.wc-bogof-rule-toggle-enabled', function( e ) {
		e.preventDefault();
		var $link   = $( this ),
			$tr     = $link.closest( 'tr' ),
			$toggle = $link.find( '.woocommerce-input-toggle' );

		var data = {
			action:  'wc_bogof_toggle_rule_enabled',
			security: wc_admin_bogof_list_table_params.nonces.rule_toggle,
			rule_id:  $link.data( 'rule_id' )
		};

		$toggle.addClass( 'woocommerce-input-toggle--loading' );

		$.ajax( {
			url:      wc_admin_bogof_list_table_params.ajax_url,
			data:     data,
			dataType : 'json',
			type     : 'POST',
			success:  function( response ) {
				if ( response.data ) {
					// Column name:
					$tr.find('td.column-name strong').html( response.data.name );
					$tr.find('td.column-enabled').html(response.data.enabled)
				}
			},
			error: function() {
				$toggle.removeClass( 'woocommerce-input-toggle--loading' );
			}
		} );

		return false;
	});
})( jQuery );