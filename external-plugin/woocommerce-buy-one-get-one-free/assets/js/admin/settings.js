;( function( $ ) {

	if ( 'undefined' === typeof wc_admin_bogof_settings_params ) {
		return;
	}

	/**
	 * Settings actions
	 */
	var wc_bogof_settings = {

		init: function() {
			$('input[name="wc_bogof_cyg_display_on"]').on('click', this.on_display_change );
			this.on_display_change();

			$('form').on('change', 'input', function() {
				$(this).closest('form').data('edited', true);
			});

			$('a.button').on('click', function(e){
				if ( true === $(this).closest('form').data('edited') ) {
					e.preventDefault();
					alert(wc_admin_bogof_settings_params.i18n.save_changes_alert);
					return false;
				}
			});

			$('#remove-legacy-options').on('click', function(e) {
				if ( e.isDefaultPrevented() ) {
					return false;
				}
				if ( true !== confirm(wc_admin_bogof_settings_params.i18n.remove_legacy_confirm) ) {
					e.preventDefault();
					return false;
				}
			});

			if ( $('[name="wc_bogof_cyg_display_on"]').length > 0 ) {
				const cssText = '.wc-bogo-setting-badged {margin: 0 4px;display: inline-block;padding: .35em .65em;font-size: .85em;font-weight: 500;line-height: 1;color: #fff;text-align: center;white-space: nowrap;vertical-align: baseline;border-radius: .25rem;background-color:rgb(108, 117, 125);}.wc-bogo-setting-badged.-primary{background-color:rgb(13, 110, 253);}'
				$('<style>').text(cssText).appendTo(document.body);

				const legacyBadged = `<span class="wc-bogo-setting-badged">${wc_admin_bogof_settings_params.i18n.legacy}</span>`;
				const newBadged = `<span class="wc-bogo-setting-badged -primary">${wc_admin_bogof_settings_params.i18n.new}</span>`;

				$('[name="wc_bogof_cyg_display_on"][value="after_cart"],[name="wc_bogof_cyg_display_on"][value="custom_page"]').closest('label').append(legacyBadged);
				$('[name="wc_bogof_cyg_display_on"][value=""]').closest('label').append(newBadged);
			}
		},

		on_display_change: function() {
			const value = $('input[name="wc_bogof_cyg_display_on"]:checked').val();
			$('#wc_bogof_cyg_page_id').closest('tr').toggle( 'custom_page' === value );
			$('#wc_bogof_cyg_title').closest('tr').toggle( 'after_cart' === value );

			$('#wc_bogof_cyg_notice').closest('tr').toggle( 'after_cart' === value || 'custom_page' === value  );
			$('#wc_bogof_cyg_notice_button_text').closest('tr').toggle( 'after_cart' === value || 'custom_page' === value  );
		}
	};
	wc_bogof_settings.init();
})( jQuery );