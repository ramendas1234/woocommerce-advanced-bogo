/* global wc_admin_bogof_metabox_params */
;( function( $ ) {

	if ( 'undefined' === typeof wc_admin_bogof_post_status_display_params ) {
		return;
	}

	if ($('#post-status-display').length && $('#_enabled').length ) {

		var status = 'yes' === $('#_enabled').val() ? 'publish' : 'wc-bogof-disabled';

		// Replace the status display label.
		$('#post-status-display').html(wc_admin_bogof_post_status_display_params.i18n[status]);

		// Add the new option to the post status select.
		if ($('select#post_status').length) {
			$('select#post_status').append('<option value="wc-bogof-disabled"></option>');
			if ( ! $('select#post_status option [value="publish"]').length ) {
				$('select#post_status').append('<option value="publish"></option>');
			}
			//$('select#post_status').val(status);

			$('#_enabled').on('change', function(){
				var status = 'yes' === $(this).val() ? 'publish' : 'wc-bogof-disabled';
				$('select#post_status').val(status);
			}).triggerHandler('change');

		}
	}

	// The same for the promotions table list.
	var children = $('ul.subsubsub li.publish a').children();
	$('ul.subsubsub li.publish a').text(wc_admin_bogof_post_status_display_params.i18n.active).append(children);
})( jQuery );
