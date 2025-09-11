/* global wc_admin_bogof_layout_tabs_params */
;( function( $ ) {

	if ( 'undefined' === typeof wc_admin_bogof_layout_tabs_params ) {
		return;
	}

	// Add tabs to the WooCommerce layout.
	$.fn.layoutTabs = function(){
		var $h1   = this.find('h1');
		var $tabs = $('<div class="wc-bogo-tabs-wrapper"></div>');

		wc_admin_bogof_layout_tabs_params.tabs.forEach( function(tab){
			var classes = 'wc-bogo-tab -' + tab.id;
			var target  = 'wc-bogo-tab-' + tab.id;
			if ( wc_admin_bogof_layout_tabs_params.active === tab.id ) {
				classes += ' -active';
			}
			$tabs.append('<a id="' + target + '" class="' + classes + '" href="' + tab.href + '">' + tab.title + '</a>');
		});
		$tabs.insertAfter($h1);
		$h1.css('flex-grow', '0');
		this.addClass('wc-bogo-header-loaded');
		return this;
	};

	// Add legacy tabs.
	$.fn.layoutTabsLegacy = function(){
		var $h1   = this.find('h1');
		var $tabs = $('<h2 class="nav-tab-wrapper" style="margin-bottom:1em;"></h2>');
		wc_admin_bogof_layout_tabs_params.tabs.forEach( function(tab){
			var classes = 'nav-tab';
			var target  = 'wc-bogo-tab-' + tab.id;
			if ( wc_admin_bogof_layout_tabs_params.active === tab.id ) {
				classes += ' nav-tab-active';
			}
			$tabs.append('<a id="' + target + '" class="' + classes + '" href="' + tab.href + '">' + tab.title + '</a>');
		});
		$tabs.insertBefore($h1);

		return this;
	};

	$(document).ready(function(){
		if ( $('.woocommerce-layout .woocommerce-layout__header-wrapper').length ) {
			$('.woocommerce-layout .woocommerce-layout__header-wrapper').layoutTabs();
		} else {
			$('div.wrap').layoutTabsLegacy();
		}
	});
})( jQuery );