jQuery(document).ready(function($) {
    console.log('BOGO Frontend JS loaded');

    // Check if Tailwind CSS is loaded and apply fallback styles if needed
    function checkTailwindCSS() {
        // Test if Tailwind classes are working
        const testElement = $('<div class="flex items-center" style="display: none;"></div>');
        $('body').append(testElement);
        
        const computedStyle = window.getComputedStyle(testElement[0]);
        const isTailwindWorking = computedStyle.display === 'flex';
        
        testElement.remove();
        
        if (!isTailwindWorking) {
            console.log('BOGO: Tailwind CSS not detected, applying fallback styles');
            applyFallbackStyles();
        } else {
            console.log('BOGO: Tailwind CSS is working properly');
        }
    }

    // Apply fallback styles for BOGO templates
    function applyFallbackStyles() {
        const fallbackCSS = `
            <style id="bogo-fallback-styles">
                .bogo-offer-container {
                    margin: 1rem 0 !important;
                    padding: 1rem !important;
                    border: 1px solid #e5e7eb !important;
                    border-radius: 0.5rem !important;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
                    background: #ffffff !important;
                }
                
                .bogo-offer-container .flex {
                    display: flex !important;
                }
                
                .bogo-offer-container .items-center {
                    align-items: center !important;
                }
                
                .bogo-offer-container .gap-4 {
                    gap: 1rem !important;
                }
                
                .bogo-offer-container .w-24 {
                    width: 6rem !important;
                }
                
                .bogo-offer-container .h-24 {
                    height: 6rem !important;
                }
                
                .bogo-offer-container .flex-shrink-0 {
                    flex-shrink: 0 !important;
                }
                
                .bogo-offer-container .flex-grow {
                    flex-grow: 1 !important;
                }
                
                .bogo-offer-container .text-lg {
                    font-size: 1.125rem !important;
                }
                
                .bogo-offer-container .font-bold {
                    font-weight: 700 !important;
                }
                
                .bogo-offer-container .mb-1 {
                    margin-bottom: 0.25rem !important;
                }
                
                .bogo-offer-container .text-sm {
                    font-size: 0.875rem !important;
                }
                
                .bogo-offer-container .mb-3 {
                    margin-bottom: 0.75rem !important;
                }
                
                .bogo-offer-container .font-semibold {
                    font-weight: 600 !important;
                }
                
                .bogo-offer-container .inline-flex {
                    display: inline-flex !important;
                }
                
                .bogo-offer-container .px-4 {
                    padding-left: 1rem !important;
                    padding-right: 1rem !important;
                }
                
                .bogo-offer-container .py-2 {
                    padding-top: 0.5rem !important;
                    padding-bottom: 0.5rem !important;
                }
                
                .bogo-offer-container .rounded-lg {
                    border-radius: 0.5rem !important;
                }
                
                .bogo-offer-container .shadow-md {
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
                }
                
                .bogo-offer-container .w-4 {
                    width: 1rem !important;
                }
                
                .bogo-offer-container .h-4 {
                    height: 1rem !important;
                }
                
                .bogo-offer-container .mr-2 {
                    margin-right: 0.5rem !important;
                }
                
                .bogo-offer-container .ml-2 {
                    margin-left: 0.5rem !important;
                }
                
                .bogo-offer-container .grab-bogo-offer-btn {
                    cursor: pointer !important;
                    border: none !important;
                    font-weight: 600 !important;
                    transition: all 0.2s ease !important;
                }
                
                .bogo-offer-container .grab-bogo-offer-btn:hover {
                    transform: scale(1.05) !important;
                }
                
                .bogo-offer-container .grab-bogo-offer-btn:disabled {
                    opacity: 0.5 !important;
                    cursor: not-allowed !important;
                }
            </style>
        `;
        
        $('head').append(fallbackCSS);
    }

    // Check Tailwind CSS on page load
    checkTailwindCSS();

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