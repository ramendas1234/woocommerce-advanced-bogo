
( function( customize, $ ) {
	'use strict';

	// On panel expanded.
	customize.panel(wc_bogof_customizer_params.panel_id, function(panel){
		panel.expanded.bind(function(isExpanded){
			if (isExpanded){
				customize.previewer.previewUrl.set( wc_bogof_customizer_params.shop_page_url );
			}
		});
	});

	// On section expanded.
	['wc_bogof_mods_gifts_container', 'wc_bogof_mods_gifts_notice'].forEach( function(sectionId) {

		customize.section( sectionId, function( section ) {
			section.expanded.bind( function( isExpanded ) {
				let _url          = customize.previewer.previewUrl.get();
				const queryString = 'wc_bogof_mod=' + sectionId.replace('wc_bogof_mods_', '');

				if ( isExpanded ) {
					// Add the parameter.
					_url = _url + ( _url.includes('?') ? '&' : '?' ) + queryString;
					customize.previewer.previewUrl.set( _url );
				} else {
					_url = _url.replaceAll('?' + queryString, '').replaceAll('&' + queryString, '');
					customize.previewer.previewUrl.set( _url );
				}
			} );
		});

	});

	// Slider control.
	$.fn.slider_control = function() {
		this.each(function(){
			const _slider = $(this).find('.wc-bogof-slider'),
				 _input = $(this).find('input'),
				 _reset = $(this).find('button[data-reset-value]');
			_slider.slider({
				min: Number.parseFloat(_input.attr('min')),
				max: Number.parseFloat(_input.attr('max')),
				step: Number.parseFloat(_input.attr('step')),
				value: Number.parseFloat(_input.val()),
				isRTL: $('body').hasClass('rtl'),
				slide: function( event, ui ) {
					_input.val( ui.value ).change();
				}
			});

			_input.on('input', function() {
				_slider.slider('value', Number.parseFloat($(this).val()));
			});
		});

		return this;
	};

	// Toggle expanded.
	$.fn.toggle_expanded = function() {
		this.each(function(){
			const _that = $(this), target = _that.data('target');
			_that.on('click', function(e) {
				e.preventDefault();
				$(target).toggleClass('expanded');
			});
		});

		return this;
	};


	// True/False switch
	$.fn.true_false_switch = function() {
		function toogleClass( $toggleEl ) {
			var target        = $toggleEl.attr('href');
			var toggleclass   = 'yes' === $(target).val() ? '-enabled' : '-disabled';
			$toggleEl.find('span.wc-bogof-input-toggle').removeClass('-enabled').removeClass('-disabled').addClass(toggleclass);
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

	// Slider control.
	$.fn.reset_value = function() {

		function getInputs($button) {
			let $inputs   = [];
			const values = $button.data('reset-input');
			if ( Array.isArray(values) ) {
				values.forEach(function(val){
					const _input = $('#' + val);
					if ( _input.length ) {
						$inputs.push(_input);
					}
				});
			} else {
				const _input = $('#' + values);
				if ( _input.length ) {
					$inputs.push(_input);
				}
			}
			return $inputs;
		};

		function reset($button) {
			getInputs($button).forEach(function(_input){
				const resetValue = _input.data('reset-value');
				if ( typeof resetValue !== 'undefined' ) {
					_input.val( resetValue ).triggerHandler('input')
					_input.change();
				}
			});
			$button.attr('disabled', 'disabled');
		};

		function init( $button ) {
			getInputs($button).forEach(function(_input){
				_input.data('reset-value', _input.val());
				_input.on('change', function(){
					$button.removeAttr('disabled');
				});
			});

			$button.on('click', function(e) {
				e.preventDefault();
				reset($button);
			});
		};

		this.each(function(){
			init($(this));
		});

		return this;
	};

	// Add to cart button style dropdown.
	const button_style = {
		$select: null,
		parent: null,
		parent_id: '',
		onChange: function() {
			const display = 'custom' === button_style.$select.val();

			button_style.$parent.toggleClass('wc-bogof-divider', display);

			$('li[id^="customize-control-wc_bogof_mods_button_"]').each(function() {
				if ( $(this).attr('id') === button_style.parent_id ) {
					return;
				}
				$(this).toggle(display);
			});
		},

		init: function() {
			this.$select  = $('select[data-customize-setting-link="wc_bogof_mods[button_style]"]');
			this.$parent  = this.$select.closest('li[id^="customize-control-wc_bogof_mods_button_"')
			this.parent_id = this.$parent.attr('id');

			this.$select.on('change', this.onChange);
			this.onChange();
		}
	};

	// Notice Appearance dropdown.
	const notice_appearance = {

		onChange: function() {
			const display = 'announcement' === notice_appearance.$select.val();

			notice_appearance.$parent.toggleClass('wc-bogof-divider', display);

			$('li[id^="customize-control-wc_bogof_mods_notice_"].customize-control-color, #customize-control-wc_bogof_mods_notice_bar_sticky').each(function() {
				$(this).toggle(display);
			});
		},

		init: function() {
			this.$select  = $('select[data-customize-setting-link="wc_bogof_mods[notice_style]"]');
			this.$parent  = this.$select.closest('#customize-control-wc_bogof_mods_notice_style');
			this.$select.on('change', this.onChange);
			this.onChange();
		}
	};

	wp.customize.bind( 'ready', function() {
		$('.wc-bogof-slider-area').slider_control();
		$('.wc-bogof-toggle-expanded').toggle_expanded();
		$('a.wc-bogof-toggle-value').true_false_switch();
		$('button.wc-bogof-reset-control').reset_value();

		$('ul[id^="sub-accordion-section-wc_bogof_mods"]').each( function() {
			$(this).find('li.customize-control-wc-bogof-accordion').last().addClass('-last');
		});

		$('.wc-bogof-responsive-tools').on('click', 'button[data-device]', function(e){
			e.preventDefault();
			jQuery('.wp-full-overlay-footer .devices button[data-device="' + $(this).data('device') + '"]').trigger('click');
		});

		button_style.init();
		notice_appearance.init();
	});

}( wp.customize, jQuery ) );