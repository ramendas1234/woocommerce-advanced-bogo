jQuery(document).ready(function($) {
    console.log('BOGO Frontend JS loaded');

    // Function to update BOGO template colors dynamically
    function updateBogoTemplateColors(templateSettings) {
        $('.bogo-offer-container').each(function() {
            var $container = $(this);
            var templateNumber = $container.data('template') || 1;
            var templateKey = 'template' + templateNumber;
            
            if (templateSettings[templateKey]) {
                var colors = templateSettings[templateKey];
                
                // Update container background
                if (colors.background) {
                    $container.css('background-color', colors.background);
                }
                
                // Update text colors
                if (colors.text) {
                    $container.find('h3, p, span:not(.grab-bogo-offer-btn *)').css('color', colors.text);
                }
                
                // Update button colors
                if (colors.button_bg) {
                    $container.find('.grab-bogo-offer-btn').css('background-color', colors.button_bg);
                }
                
                if (colors.button_text) {
                    $container.find('.grab-bogo-offer-btn').css('color', colors.button_text);
                }
                
                // Update primary colors
                if (colors.primary) {
                    $container.find('span[style*="primary_color"], strong[style*="primary_color"]').each(function() {
                        var currentStyle = $(this).attr('style');
                        if (currentStyle && currentStyle.includes('primary_color')) {
                            var newStyle = currentStyle.replace(/color:\s*[^;]+;?/g, 'color: ' + colors.primary + ';');
                            $(this).attr('style', newStyle);
                        }
                    });
                }
                
                // Update secondary colors
                if (colors.secondary) {
                    $container.find('span[style*="secondary_color"], strong[style*="secondary_color"]').each(function() {
                        var currentStyle = $(this).attr('style');
                        if (currentStyle && currentStyle.includes('secondary_color')) {
                            var newStyle = currentStyle.replace(/color:\s*[^;]+;?/g, 'color: ' + colors.secondary + ';');
                            $(this).attr('style', newStyle);
                        }
                    });
                }
                
                // Add visual feedback for updates
                $container.addClass('bogo-updated');
                setTimeout(function() {
                    $container.removeClass('bogo-updated');
                }, 300);
            }
        });
    }

    // Function to check for admin changes and update frontend
    function checkForAdminChanges() {
        if (typeof wc_advanced_bogo_ajax !== 'undefined' && wc_advanced_bogo_ajax.is_admin) {
            $.ajax({
                url: wc_advanced_bogo_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_bogo_template_settings',
                    template: 'template1', // Default template
                    nonce: wc_advanced_bogo_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Compare with current settings to avoid unnecessary updates
                        var currentSettings = wc_advanced_bogo_ajax.template_settings || {};
                        var newSettings = response.data;
                        
                        // Check if settings have changed
                        var hasChanges = false;
                        for (var templateKey in newSettings) {
                            if (newSettings.hasOwnProperty(templateKey) && templateKey !== 'selected_template') {
                                var currentColors = currentSettings[templateKey] || {};
                                var newColors = newSettings[templateKey] || {};
                                
                                for (var colorKey in newColors) {
                                    if (newColors.hasOwnProperty(colorKey) && currentColors[colorKey] !== newColors[colorKey]) {
                                        hasChanges = true;
                                        break;
                                    }
                                }
                                if (hasChanges) break;
                            }
                        }
                        
                        if (hasChanges) {
                            console.log('BOGO: Template settings changed, updating frontend...');
                            updateBogoTemplateColors(newSettings);
                            // Update the stored settings
                            wc_advanced_bogo_ajax.template_settings = newSettings;
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('BOGO: Error getting template settings:', error);
                }
            });
        }
    }

    // Check for admin changes every 2 seconds if user is admin
    if (typeof wc_advanced_bogo_ajax !== 'undefined' && wc_advanced_bogo_ajax.is_admin) {
        setInterval(checkForAdminChanges, 2000);
        
        // Also check immediately on page load
        setTimeout(checkForAdminChanges, 1000);
    }

    // Handle grab offer button clicks
    $(document).on('click', '.grab-bogo-offer-btn', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $container = $button.closest('.bogo-offer-container');
        const $loading = $container.find('.bogo-offer-loading');
        
        // Get offer data from button attributes
        const offerData = {
            action: 'grab_bogo_offer',
            nonce: wc_advanced_bogo_ajax.nonce,
            buy_product: $button.data('buy-product'),
            buy_qty: $button.data('buy-qty'),
            get_product: $button.data('get-product'),
            get_qty: $button.data('get-qty'),
            discount: $button.data('discount'),
            rule_index: $button.data('rule-index')
        };

        console.log('Grabbing BOGO offer:', offerData);

        // Disable button and show loading
        $button.prop('disabled', true);
        $button.addClass('opacity-50 cursor-not-allowed');
        $loading.removeClass('hidden');

        // AJAX request
        $.ajax({
            url: wc_advanced_bogo_ajax.ajax_url,
            type: 'POST',
            data: offerData,
            dataType: 'json',
            success: function(response) {
                console.log('BOGO offer response:', response);
                
                if (response.success) {
                    // Show success message
                    showBogoMessage('success', response.data.message);
                    
                    // Transform button to success state
                    $button.html(`
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        ✅ Added to Cart!
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    `);
                    $button.removeClass('from-green-500 to-blue-600 hover:from-green-600 hover:to-blue-700');
                    $button.addClass('from-green-600 to-green-700 bg-gradient-to-r');
                    
                    // Update cart count if available
                    if (response.data.cart_count) {
                        updateCartCount(response.data.cart_count);
                    }
                    
                    // Show View Cart button after delay
                    setTimeout(function() {
                        $button.html(`
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 6M7 13l-1.5-6m0 0h16M16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM8.5 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                            </svg>
                            🛒 View Cart
                        `);
                        $button.removeClass('from-green-600 to-green-700');
                        $button.addClass('from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700');
                        $button.prop('disabled', false);
                        $button.removeClass('opacity-50 cursor-not-allowed');
                        
                        // Make button redirect to cart
                        $button.off('click').on('click', function() {
                            window.location.href = response.data.cart_url || wc_advanced_bogo_ajax.cart_url;
                        });
                    }, 2000);
                    
                } else {
                    // Show error message
                    showBogoMessage('error', response.data.message);
                    resetButton($button);
                }
            },
            error: function(xhr, status, error) {
                console.error('BOGO AJAX error:', error);
                showBogoMessage('error', 'Something went wrong. Please try again.');
                resetButton($button);
            },
            complete: function() {
                $loading.addClass('hidden');
            }
        });
    });

    // Function to reset button to original state
    function resetButton($button) {
        $button.prop('disabled', false);
        $button.removeClass('opacity-50 cursor-not-allowed');
        $button.html(`
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            🛒 Grab This Offer!
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
        `);
    }

    // Function to show success/error messages
    function showBogoMessage(type, message) {
        const bgColor = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
        const icon = type === 'success' ? '✅' : '❌';
        
        const $message = $(`
            <div class="bogo-message fixed top-4 right-4 z-50 ${bgColor} border px-4 py-3 rounded shadow-lg transform translate-x-full transition-transform duration-300">
                <div class="flex items-center">
                    <span class="mr-2">${icon}</span>
                    <span class="text-sm font-medium">${message}</span>
                </div>
            </div>
        `);
        
        $('body').append($message);
        
        // Slide in
        setTimeout(() => {
            $message.removeClass('translate-x-full');
        }, 100);
        
        // Slide out and remove after delay
        setTimeout(() => {
            $message.addClass('translate-x-full');
            setTimeout(() => {
                $message.remove();
            }, 300);
        }, 4000);
    }

    // Function to update cart count (if cart count element exists)
    function updateCartCount(count) {
        const $cartCount = $('.cart-contents-count, .cart-count');
        if ($cartCount.length) {
            $cartCount.text(count);
            // Add a little animation
            $cartCount.addClass('animate-pulse');
            setTimeout(() => {
                $cartCount.removeClass('animate-pulse');
            }, 1000);
        }
    }

    // Add some hover effects for better UX
    $(document).on('mouseenter', '.grab-bogo-offer-btn:not(:disabled)', function() {
        $(this).addClass('shadow-xl');
    }).on('mouseleave', '.grab-bogo-offer-btn:not(:disabled)', function() {
        $(this).removeClass('shadow-xl');
    });

    // Cart BOGO hints quick add functionality
    $('.bogo-quick-add-btn').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var buyProduct = $btn.data('buy-product');
        var buyQty = parseInt($btn.data('buy-qty'));
        var getProduct = $btn.data('get-product');
        var getQty = parseInt($btn.data('get-qty'));
        var discount = parseInt($btn.data('discount'));
        var ruleIndex = $btn.data('rule-index');
        
        // Show loading state
        $btn.prop('disabled', true).text('Adding...');
        
        $.ajax({
            url: wc_advanced_bogo_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'grab_bogo_offer',
                buy_product: buyProduct,
                buy_qty: buyQty,
                get_product: getProduct,
                get_qty: getQty,
                discount: discount,
                rule_index: ruleIndex,
                nonce: wc_advanced_bogo_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $btn.text('Added!').css('background', '#10B981');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('Error: ' + response.data);
                    $btn.prop('disabled', false).text('🚀 Grab This Offer!');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $btn.prop('disabled', false).text('🚀 Grab This Offer!');
            }
        });
    });

    // Hide BOGO hints when customer has already qualified
    function checkBogoQualification() {
        $('.bogo-cart-hint').each(function() {
            var $hint = $(this);
            var buyProduct = $hint.find('.bogo-quick-add-btn').data('buy-product');
            var buyQty = parseInt($hint.find('.bogo-quick-add-btn').data('buy-qty'));
            
            // Check if customer has enough items in cart
            var cartItems = [];
            $('.woocommerce-cart-form__cart-item').each(function() {
                var productId = $(this).find('input[name="cart[0][product_id]"]').val();
                var quantity = parseInt($(this).find('input[name="cart[0][quantity]"]').val());
                if (productId && quantity) {
                    cartItems.push({product_id: productId, quantity: quantity});
                }
            });
            
            var totalQty = 0;
            cartItems.forEach(function(item) {
                if (buyProduct === 'all' || item.product_id == buyProduct) {
                    totalQty += item.quantity;
                }
            });
            
            // Hide hint if customer has qualified
            if (totalQty >= buyQty) {
                $hint.fadeOut();
            }
        });
    }
    
    // Check qualification on page load and cart updates
    checkBogoQualification();
    
    // Re-check when cart is updated
    $(document.body).on('updated_cart_totals', function() {
        setTimeout(checkBogoQualification, 500);
    });
});