/* global wc_admin_bogof_metabox_params */
;( function( $ ) {

	if ( 'undefined' === typeof wc_admin_bogof_metabox_params ) {
		return;
	}

	// True/False switch
	$.fn.true_false_switch = function() {
		function toogleClass( $toggleEl ) {
			var target        = $toggleEl.attr('href');
			var toggleclass   = 'yes' === $(target).val() ? 'enabled' : 'disabled';
			$toggleEl.find('span.woocommerce-input-toggle').removeClass('woocommerce-input-toggle--enabled').removeClass('woocommerce-input-toggle--disabled').addClass('woocommerce-input-toggle--' + toggleclass);
		};

		this.each( function(){
			$(this).on('click', function(e) {
				e.preventDefault();
				var target = $(this).attr('href');
				value      = 'yes' == $(target).val() ? 'no' : 'yes';
				$(target).val(value);
				toogleClass($(this));
				// Change envent.
				$(target).trigger('change');
			});

			toogleClass($(this));
		});

		return this;
	};

	// Hide/Show fields by conditions.
	$.fn.show_hide_fields = function()  {
		//Evaluate a condition.
		function conditionEval(field, operator, value) {
			var eval      = false;
			var $field    = $('[name="' + field + '"]').length ? $('[name="' + field + '"]') : $('#' + field);
			var field_val = $field.val();
			if ('=' == operator) {
				eval = (field_val == value);
			} else {
				// operator !=
				eval = (field_val != value);
			}
			return eval;
		}

		// On field change.
		$(this).on('wc_bogo_field_change', function() {
			var conditions = $(this).data('show-if');
			if ( conditions instanceof Array ) {
				var showHide = true;
				conditions.forEach(function(condition){
					showHide = showHide && conditionEval( condition.field, condition.operator, condition.value);
				});
				$(this).toggle(showHide);
			}
		});

		// Listen to the change event.
		this.each(function(){
			var $that = $(this);
			var conditions = $that.data('show-if');
			if ( conditions instanceof Array ) {
				conditions.forEach(function(condition){
					var $field = $('[name="' + condition.field + '"]').length ? $('[name="' + condition.field + '"]') : $('#' + condition.field);
					$field.on('change', function(){
						$that.triggerHandler('wc_bogo_field_change');
					});
				});
			}
		});

		// Show/Hide the element.
		this.each(function(){
			$(this).triggerHandler('wc_bogo_field_change');
		});

		return this;
	};

	// Info tips for conditional logic.
	$.fn.wc_bogo_input_tooltip = function() {

		const tipTemplate = '<span class="tips"><span class="dashicons dashicons-info-outline"></span></span>';

		function addTips($input) {
			const tips = $input.data('info-tooltips');

			if ( ! Array.isArray(tips) ) {
				return;
			}

			tips.forEach(function(tip){
				const $tip       = $(tipTemplate);
				const modifierId = $input.attr('id').replace('value', 'modifier');
				const $modifier  = $('#'+modifierId);
				const tipAgrs = {
					attribute: 'data-tip',
					fadeIn: 50,
					fadeOut: 50,
					delay: 200,
					keepAlive: true,
				};

				$tip.attr('data-tip', tip.message);

				if (tip.show_if && Array.isArray( tip.show_if ) ) {

					$tip.data('show-if', tip.show_if.map(function(con){
						con.field = modifierId;
						return con;
					}));
				}

				$tip.insertAfter($modifier);
				$tip.show_hide_fields();
				$tip.tipTip(tipAgrs);

				const $parent = $input.parent();
				if ( ! $parent.hasClass('-has-input-tooptip') ) {
					$parent.addClass('-has-input-tooptip');
				}
			});
		};

		this.each(function(){
			addTips( $(this) );
		});

		return this;
	};

	// Repeater.
	$.fn.wc_bogo_repeater = function(method) {

		/**
		 * Toogle class
		 *
		 * @param {object} $repeater
		 */
		function toogleClass($repeater) {
			const $table = $repeater.find('table.wc-bogo-table>tbody').first();
			$repeater.removeClass( 'wc-bogo-row-multiple -is-empty');

			if ( $table.find('>tr.row-input').length > 1 ) {
				$repeater.addClass( 'wc-bogo-row-multiple');
			} else if ( $table.find('>tr.row-input').length < 1 ) {
				$repeater.addClass( '-is-empty');
			}

			placeholder($repeater);

			toogleGroupClass($repeater.closest('.wc-bogo-conditional-logic'));
		};

		/**
		 * Toogle class
		 *
		 * @param {object} $parent
		 */
		function toogleGroupClass($parent) {
			if ( $parent.length < 1 ) {
				return;
			}

			const $repeaters = $parent.find('.wc-bogo-repeater');

			$repeaters.removeClass('wc-bogo-group-multiple -last-group -first-group');
			$repeaters.last().addClass('-last-group');
			$repeaters.first().addClass('-first-group');

			if ( $repeaters.length > 1 ) {
				$repeaters.addClass('wc-bogo-group-multiple');
			}
		}

		/**
		 * Add a new row
		 *
		 * @param {object} $repeater
		 * @param {string} templateId
		 */
		function addRow($repeater, templateId) {

			const event = jQuery.Event('addRow.repeater.wc-bogo');
			$repeater.trigger(event);

			if ( event.isDefaultPrevented() ) {
				return;
			}
			const $table     = $repeater.find('table.wc-bogo-table>tbody').first();
			const trTemplate = wp.template( templateId );
			let data         = $repeater.data();
			let lastRowId    = $table.find('>tr.row-input').last().data('rowId') + 1;
			if ( isNaN( lastRowId ) ) {
				lastRowId = 0;
			}

			const row  = trTemplate($.extend(data, {rowId: lastRowId }));
			const $row = $(row).appendTo($table);

			$row.find('[data-show-if]').show_hide_fields();	// Show-hide fields.
			$row.find('.wc-bogo-true-false').true_false_switch(); // True/false
			$row.find('[data-info-tooltips]').wc_bogo_input_tooltip(); // Tooltips
			$row.find('.tips').tipTip({
				attribute: 'data-tip',
				fadeIn: 50,
				fadeOut: 50,
				delay: 200,
				keepAlive: true,
			});
			$( document.body ).trigger('wc-enhanced-select-init'); // init enhanced-select.

			toogleClass($repeater);
		};

		/**
		 * Duplicate
		 *
		 * @param {object} $repeater
		 */
		function duplicate($repeater) {
			const $parent = $repeater.closest('.wc-bogo-conditional-logic');
			if ( ! $repeater.hasClass('-last-group') || $parent.length < 1 ) {
				return;
			}
			const $copy = $repeater.clone();
			$copy.find('table.wc-bogo-table>tbody').empty();
			let groupId = $repeater.data('groupId')+1;
			if ( isNaN( groupId ) ) {
				groupId = 0;
			}
			//$repeater.append('');

			$copy.data('groupId', groupId);
			$copy.insertAfter($repeater);
			$copy.wc_bogo_repeater('addRow');

			toogleGroupClass($parent);
		};

		/**
		 * Remove row
		 *
		 * @param {object} $repeater
		 * @param {object} $row
		 */
		function remove($repeater, $row) {
			const $tbody = $row.closest('tbody');
			const $parent = $repeater.closest('.wc-bogo-conditional-logic');

			if ( $parent.length && $repeater.hasClass('wc-bogo-group-multiple') && 1 === $tbody.find('>tr.row-input').length ) {
				$repeater.remove();
				toogleGroupClass($parent);
			} else {
				$row.remove();
				toogleClass($repeater);
			}
		}

		/**
		 * Add the placeholder.
		 *
		 * @param {object} $repeater
		 */
		function placeholder($repeater) {
			const placeholder = $repeater.data('placeholder');
			if ( ! placeholder ) {
				return;
			}
			const $tbody = $repeater.find('table.wc-bogo-table>tbody').first();
			if ( $tbody.find('>tr.row-input').length < 1 ) {
				$tbody.append(`<tr class="row-placeholder"><td>${placeholder}</td></tr>`);
			} else {
				$tbody.find('>tr.row-placeholder').remove();
			}
		}

		/**
		 * Init.
		 */
		this.each( function(){

			const $that = $(this);

			/**
			 * Init
			 */
			function init() {

				if ( true === $that.data('wc-bogo.repeater.initialized') ) {
					return;
				}

				$that.on('click', 'a.add-row', function(e){
					e.preventDefault();
					addRow( $that, $(this).attr('href') );
				});

				$that.on('click', 'a.remove-row', function(e){
					e.preventDefault();
					// Remove the row.
					remove($that, $(this).closest('tr'));
				});

				$that.on('click', 'a.add-group', function(e){
					e.preventDefault();
					// Remove the row.
					duplicate($that);
				});

				toogleClass($that);

				$that.data('wc-bogo.repeater.initialized', true);
			};

			init();

			if ( 'addRow' === method ) {
				$that.find('a.add-row').trigger('click');
			}

		});

		return this;
	};

	// Display errors.
	$.fn.wc_bogo_display_error = function( error_message, hide_on_focus ) {
		if (this.parent().find( '.wc-bogo-error-tip' ).length > 0) {
			this.wc_bogo_hide_errors();
		}

		// Add the wrapper.
		if (! this.parent().hasClass('wc-bogo-error-tip-wrap')) {
			this.parent().addClass('wc-bogo-error-tip-wrap');
		}

		// Get dimensions.
		var $el = this.hasClass('select2-hidden-accessible') ? this.parent().find('span.select2.select2-container') : this;
		var position = $.extend( $el.position(), {
			width: $el.width(),
			height: $el.height()
		});

		// Display the error tip.
		var $tip = $( '<div class="wc-bogo-error-tip" style="display:none;">' + error_message + '</div>' );
		this.parent().append($tip);
		$tip.css( 'left', position.left + position.width - ( position.width / 2 ) - ( $( '.wc-bogo-error-tip' ).width() / 2 ) )
			.css( 'top', position.top + position.height + 4 )
			.fadeIn( '100' );

		if ( true === hide_on_focus ) {
			$el.addClass('-hide-error-on-focus');
		}
		return this;
	};

	// Hide errors.
	$.fn.wc_bogo_hide_errors = function() {
		var $parent = this.parent();
		$parent.find('.wc-bogo-error-tip').fadeOut( '100', function() {
			$(this).remove();
		} );
		this.removeClass('-hide-error-on-focus');
		return this;
	};

	// Hide all errors.
	wc_bogo_hide_all_errors = function() {
		$('.wc-bogo-error-tip').remove();
	};

	// Quantities rules.
	$.fn.wc_bogo_quantities = function() {
		const $quantities = this;
		const selectors = {
			free_quantity: '[name$="[free_quantity]"]',
			cart_quantity: '[name$="[cart_quantity]"]'
		};
		const $warning_dialog = $('#add-quantity-rule-warning-dialog');

		/**
		 * Check the min quantity is greater than the free quantitie.
		 *
		 * @param {*} $row The row contains the input texts.
		 */
		function checkQuantities($row) {
			var $free_qty_input = $row.find(selectors.free_quantity);
			var free_qty        = parseInt($free_qty_input.val(), 10);
			var min_qty         = parseInt($row.find(selectors.cart_quantity).val(), 10);
			if ( isNaN(free_qty) || min_qty > free_qty ) {
				$free_qty_input.trigger('wc_bogo_remove_quantity_error');
			} else {
				$free_qty_input.trigger('wc_bogo_add_quantity_error');
			}
		};

		/**
		 * On quantity change.
		 */
		function onQuantityChange(){
			if ( 'checking' == $quantities.data('quantities_status') ) {
				var $row = $(this).closest('tr');
				checkQuantities($row);
			}
		};

		/**
		 * Remove all error tips.
		 */
		function removeAllErrors() {
			$quantities.find('.wc-bogo-error-tip').remove();
		};

		/**
		 * Toggle filled class.
		 */
		function toggleFilledClass( $row ) {
			const $cart_qty_input = $row.find( selectors.cart_quantity );
			const $free_qty_input = $row.find( selectors.free_quantity );

			$row.toggleClass('filled', $cart_qty_input.val()+$free_qty_input.val()>1);
		};

		/**
		 * Init.
		 */
		function init() {
			// Quantity error tip.
			$quantities.on( 'wc_bogo_add_quantity_error', selectors.free_quantity, function(){
				$(this).wc_bogo_display_error('<span>' + wc_admin_bogof_metabox_params.i18n.free_less_than_min_error + '</span><span data-toggle="wc-bogo-modal" data-target="#free-less-than-min-error-info-dialog" class="error-tip-info">' + wc_admin_bogof_metabox_params.i18n.more_info + '</span>');
			});

			// Remove error tip.
			$quantities.on( 'wc_bogo_remove_quantity_error', selectors.free_quantity, function(){
				$(this).wc_bogo_hide_errors();
			});

			// Custom event to change the quantity checking status.
			$quantities.on('wc_bogo_checking_quantities', function(event, check){
				$quantities.data('quantities_status', (check ? 'checking' : 'off'));
				if ( check ) {
					$quantities.find(selectors.cart_quantity).each(onQuantityChange);
				} else {
					removeAllErrors();
				}
			});

			// Check the quantities on changes.
			$quantities.on('keyup mouseup', selectors.free_quantity, onQuantityChange);
			$quantities.on('change', selectors.cart_quantity, onQuantityChange);

			// Toggle the .filled class.
			$quantities.on('change', selectors.cart_quantity + ',' + selectors.free_quantity, function() {
				toggleFilledClass($(this).closest('tr.row-input'))
			});
			$quantities.find('tr.row-input').each(function(){
				toggleFilledClass($(this));
			});
		};

		init();

		return this;
	};


	// Post Box.
	$.fn.wc_bogo_postbox = function() {

		var $postbox = this;

		// Return the promotion type.
		function getType () {
			return $postbox.find('#_type').val();
		};

		// Check type of promotion.
		function isType( typeToCheck ) {
			return typeToCheck === getType();
		}

		// Is the promotion enabled?
		function isEnabled() {
			return 'yes' === $('#_enabled').val();
		}

		// True/False switchs.
		$postbox.find('.wc-bogo-true-false').true_false_switch();

		// Table input.
		$postbox.find('.wc-bogo-repeater').wc_bogo_repeater();

		// Quantities error tips
		$postbox.find('#_quantity_rules')
			.wc_bogo_quantities()
			.trigger('wc_bogo_checking_quantities', [isType('cheapest_free') && isEnabled()]);

		// On type change.
		$postbox.on('change', '#_type', function(){
			// Check quantities.
			$postbox.find('#_quantity_rules').trigger('wc_bogo_checking_quantities', [isType('cheapest_free') && isEnabled()] );
		});

		// On enable change.
		$postbox.on('change', '#_enabled', function(){
			// Check quantities.
			$postbox.find('#_quantity_rules').trigger('wc_bogo_checking_quantities', [isType('cheapest_free') && isEnabled()] );
			// Remove all errors after disable the promiton.
			if ( ! isEnabled() ) {
				wc_bogo_hide_all_errors();
			}
		});

		// Init date picker.
		$postbox.find('.date-picker').datepicker({
			dateFormat: 'yy-mm-dd',
			numberOfMonths: 1,
			showButtonPanel: true
		});

		// Info tooltips.
		$postbox.find('[data-info-tooltips]').wc_bogo_input_tooltip();

		// Conditional Logic.
		$postbox.find('[data-show-if]').show_hide_fields();

		// Remove placeholder.
		$postbox.find('.wc-bogo-fields').removeClass('-loading');
		$postbox.find('.wc-bogo-field.-placeholder').remove();

		return $postbox;
	}

	// Post Box.
	$.fn.wc_bogo_submitpost = function() {
		var $submitpost = this;
		var xhr         = false;
		var validForm   = false;

		// Show Spinner.
		function showSpinner($spinner) {
			$spinner.addClass('is-active'); // add class (WP > 4.2)
			$spinner.css('display', 'inline-block'); // css (WP < 4.2)
			return $spinner;
		};

		// Hide Spinner.
		function hideSpinner($spinner) {
			$spinner.removeClass('is-active'); // add class (WP > 4.2)
			$spinner.css('display', 'none'); // css (WP < 4.2)
			return $spinner;
		};

		// Lock Form.
		function lockForm() {
			var $wrap = $submitpost.find('#submitpost');
			var $submit = $wrap.find('.button, [type="submit"]');
			var $spinner = $wrap.find('.spinner'); // hide all spinners (hides the preview spinner)

			// lock
			$submit.addClass('disabled');
			showSpinner($spinner.last());

			// Hide previous errors.
			wc_bogo_hide_all_errors();
			hideNotice();
		  };

		// UnLock Form.
		function unlockForm() {
			var $wrap = $submitpost.find('#submitpost');
			var $submit = $wrap.find('.button, [type="submit"]');
			var $spinner = $wrap.find('.spinner, .acf-spinner'); // unlock

			$submit.removeClass('disabled');
			hideSpinner($spinner);
		};

		// Hide errors on focus.
		$submitpost.on('change focus', '.-hide-error-on-focus', function(){
			$(this).wc_bogo_hide_errors();
		});

		// Retrun AJAX data.
		function getAjaxData() {
			var actionName = 'wc_bogof_validate_save'
			var actionSet    = false;
			var ajaxData     = $submitpost.serializeArray();

			ajaxData.forEach(function(value, index){
				if ('action' === value.name ) {
					ajaxData[index].value = actionName;
					actionSet = true;
				}
			});
			if ( ! actionSet ) {
				ajaxData.push({
					name: 'action',
					value: actionName
				});
			}
			return ajaxData;
		};

		// Add or update a notice.
		function notice( message ) {
			var $notice;

			if ( $('#wc-bogo-validation-notice').length ) {
				$notice = $('#wc-bogo-validation-notice');
				$notice.hide();
			} else {
				$notice = $('<div id="wc-bogo-validation-notice" class="notice notice-error is-dismissible" style="display:none;"><p><strong></strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
				$notice.on('click', 'button.notice-dismiss', function(){
					$(this).closest('.notice').fadeOut();
				});
				$notice.insertBefore($submitpost);
			}
			$notice.find('p>strong').text(message);
			$notice.fadeIn();
		}

		// Hide the notice.
		function hideNotice() {
			return $('#wc-bogo-validation-notice').hide();
		}

		// Display errors.
		function showErrors(errors) {
      		var $scrollTo = false;

			errors.forEach(function(error){
				var $input = $('#_'+error.field).last();
				$input.wc_bogo_display_error(error.message, true);
				if ( !$scrollTo ) {
					$scrollTo = $input;
				}
			});

			var errorCount = $('.-hide-error-on-focus').length;
			if ( errorCount>1 ) {
				notice(wc_admin_bogof_metabox_params.i18n.fields_requires_attention.replace('%s', errorCount));
			} else {
				notice(wc_admin_bogof_metabox_params.i18n.field_requires_attention);
			}

			if ( $scrollTo && $scrollTo.length ) {
				setTimeout(function () {
					$('html, body').animate({
						scrollTop: $scrollTo.offset().top - $(window).height() / 2
					}, 500);
				}, 10);
			}
		};

		// Validate the form via AJAX.
		$submitpost.find( ':submit' ).on( 'click.edit-post', function( event ) {

			if ( false === $submitpost[0].checkValidity() ) {
				return true;
			}

			if ( false !== xhr || event.isDefaultPrevented() || validForm ) {
				return;
			}

			lockForm();
			validForm = false;
			var ajaxData = getAjaxData();

			xhr = $.ajax({
				url: wc_admin_bogof_metabox_params.ajaxurl,
				dataType: 'json',
				type: 'post',
				data: $.param(ajaxData),
				success: function( response ){
					if ( Array.isArray( response ) && response.length ) {
						// Errors.
						showErrors(response);
						unlockForm();
					} else {
						// No errors. Submit post.
						validForm = true;
					}
				},
				complete: function() {
					xhr = false;
					if ( validForm ) {
						$submitpost.submit();
					}
				},
				error: function() {
					// Delegate validation.
					$submitpost.submit();
				}
			});

			return false;

		});
	};

	// Init.
	$(document).ready(function(){
		$('.postbox.wc-bogo-postbox').wc_bogo_postbox();
		$('form#post').wc_bogo_submitpost();
		$('.wc-bogo-modal').wc_bogo_modal_dialog();
	});

})( jQuery );