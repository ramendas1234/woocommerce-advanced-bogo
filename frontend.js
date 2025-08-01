jQuery(document).ready(function($) {
    console.log('BOGO Frontend JS loaded');

    // Handle grab offer button clicks
    $(document).on('click', '.grab-bogo-offer-btn', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $container = $button.closest('.bogo-offer-container');
        var buyProductId = $container.data('buy-product-id');
        var getProductId = $container.data('get-product-id');
        var buyQty = $container.data('buy-qty');
        var getQty = $container.data('get-qty');
        var discount = $container.data('discount');
        var ruleIndex = $container.data('rule-index');
        
        if (!buyProductId || !getProductId) {
            console.error('BOGO: Missing product IDs');
            return;
        }
        
        // Show loading state
        $button.prop('disabled', true).text('Adding...');
        
        // Add to cart via AJAX
        $.ajax({
            url: wc_advanced_bogo_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'grab_bogo_offer',
                buy_product_id: buyProductId,
                get_product_id: getProductId,
                buy_qty: buyQty,
                get_qty: getQty,
                discount: discount,
                rule_index: ruleIndex,
                nonce: wc_advanced_bogo_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $button.text('Added!').addClass('success');
                    
                    // Redirect to cart after a short delay
                    setTimeout(function() {
                        window.location.href = wc_advanced_bogo_ajax.cartUrl;
                    }, 1000);
                } else {
                    // Show error message
                    $button.text('Error').addClass('error');
                    setTimeout(function() {
                        $button.prop('disabled', false).text('Grab This Offer').removeClass('error');
                    }, 2000);
                }
            },
            error: function() {
                // Show error message
                $button.text('Error').addClass('error');
                setTimeout(function() {
                    $button.prop('disabled', false).text('Grab This Offer').removeClass('error');
                }, 2000);
            }
        });
    });
});