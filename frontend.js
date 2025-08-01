jQuery(document).ready(function($) {
    console.log('BOGO Frontend JS loaded');

    // Handle grab offer button clicks
    $(document).on('click', '.grab-bogo-offer-btn', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var buyProduct = $button.data('buy-product');
        var buyQty = $button.data('buy-qty');
        var getProduct = $button.data('get-product');
        var getQty = $button.data('get-qty');
        var discount = $button.data('discount');
        var ruleIndex = $button.data('rule-index');
        
        if (!buyProduct || !getProduct) {
            console.error('BOGO: Missing product data');
            return;
        }
        
        // Show loading state
        $button.prop('disabled', true);
        $button.html(`
            <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Adding to Cart...
        `);
        
        // Add to cart via AJAX
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
                    $button.removeClass('hover:scale-105').addClass('bg-green-600');
                    $button.html(`
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        ✅ Added to Cart!
                    `);
                    
                    // Show success notification
                    showNotification('BOGO offer added successfully!', 'success');
                    
                    // Redirect to cart after a short delay
                    setTimeout(function() {
                        window.location.href = wc_advanced_bogo_ajax.cartUrl;
                    }, 1500);
                } else {
                    // Show error message
                    $button.removeClass('hover:scale-105').addClass('bg-red-600');
                    $button.html(`
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        ❌ Error
                    `);
                    
                    showNotification(response.data.message || 'Failed to add offer to cart', 'error');
                    
                    // Reset button after delay
                    setTimeout(function() {
                        resetButton($button);
                    }, 3000);
                }
            },
            error: function(xhr, status, error) {
                console.error('BOGO AJAX Error:', error);
                
                // Show error message
                $button.removeClass('hover:scale-105').addClass('bg-red-600');
                $button.html(`
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    ❌ Error
                `);
                
                showNotification('Network error. Please try again.', 'error');
                
                // Reset button after delay
                setTimeout(function() {
                    resetButton($button);
                }, 3000);
            }
        });
    });
    
    // Function to reset button to original state
    function resetButton($button) {
        $button.prop('disabled', false);
        $button.removeClass('bg-green-600 bg-red-600').addClass('hover:scale-105');
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
    
    // Function to show notifications
    function showNotification(message, type) {
        var bgColor = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
        var icon = type === 'success' ? '✅' : '❌';
        
        var $notification = $(`
            <div class="fixed top-4 right-4 z-50 ${bgColor} border px-4 py-3 rounded shadow-lg transform translate-x-full transition-transform duration-300 max-w-sm">
                <div class="flex items-center">
                    <span class="mr-2">${icon}</span>
                    <span class="text-sm font-medium">${message}</span>
                </div>
            </div>
        `);
        
        $('body').append($notification);
        
        // Slide in
        setTimeout(function() {
            $notification.removeClass('translate-x-full');
        }, 100);
        
        // Slide out and remove after delay
        setTimeout(function() {
            $notification.addClass('translate-x-full');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 4000);
    }
});