/**
 * WooCommerce Advanced BOGO - Cart Blocks React Component
 */

const { createElement, Fragment } = wp.element;
const { addFilter } = wp.hooks;
const { createHigherOrderComponent } = wp.compose;

// BOGO Hint Component
const BogoHint = ({ hint }) => {
    if (!hint) return null;
    
    return createElement('div', {
        style: {
            marginTop: '8px',
            padding: '8px',
            background: '#f0f9ff',
            borderLeft: '3px solid #3b82f6',
            borderRadius: '4px',
            fontSize: '12px',
            color: '#1e40af',
            fontWeight: '600'
        }
    }, `🎁 Add ${hint.remaining_qty} more and get ${hint.get_qty}x ${hint.get_product_name} ${hint.discount_text}`);
};

// Higher Order Component to add BOGO hints to cart items
const withBogoHints = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        // Only apply to cart item blocks
        if (props.name !== 'woocommerce/cart-item') {
            return createElement(BlockEdit, props);
        }
        
        const cartItem = props.attributes?.cartItem || {};
        const bogoHint = cartItem.bogo_hint;
        
        return createElement(Fragment, {},
            createElement(BlockEdit, props),
            createElement(BogoHint, { hint: bogoHint })
        );
    };
}, 'withBogoHints');

// Add BOGO hints to cart item blocks
addFilter(
    'editor.BlockEdit',
    'wc-advanced-bogo/cart-item-hints',
    withBogoHints
);

// Frontend cart blocks integration
document.addEventListener('DOMContentLoaded', function() {
    // Function to add BOGO hints to cart blocks on frontend
    function addBogoHintsToCartBlocks() {
        const cartItems = document.querySelectorAll('.wp-block-woocommerce-cart-item, .wc-block-components-cart-item');
        
        cartItems.forEach(cartItem => {
            // Check if hint already exists
            if (cartItem.querySelector('.bogo-cart-hint')) {
                return;
            }
            
            const productNameElement = cartItem.querySelector('.wc-block-components-cart-item__name, .cart-item-name');
            if (!productNameElement) {
                return;
            }
            
            // Get product ID from data attributes
            const productId = cartItem.dataset.productId || 
                             cartItem.querySelector('[data-product-id]')?.dataset.productId ||
                             cartItem.querySelector('input[name*="product_id"]')?.value;
            
            if (!productId) {
                return;
            }
            
            // Get BOGO hint via AJAX
            fetch(wcAdvancedBogoCart.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_bogo_hints',
                    product_id: productId,
                    nonce: wcAdvancedBogoCart.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.hint) {
                    const hintElement = document.createElement('div');
                    hintElement.innerHTML = data.data.hint;
                    hintElement.className = 'bogo-cart-hint';
                    productNameElement.parentNode.insertBefore(hintElement, productNameElement.nextSibling);
                }
            })
            .catch(error => {
                console.error('Error loading BOGO hints:', error);
            });
        });
    }
    
    // Run on page load
    setTimeout(addBogoHintsToCartBlocks, 1000);
    
    // Run when cart is updated
    document.body.addEventListener('updated_cart_totals', function() {
        setTimeout(addBogoHintsToCartBlocks, 1000);
    });
    
    // Run when blocks are rendered
    document.body.addEventListener('wc-blocks-cart-updated', function() {
        setTimeout(addBogoHintsToCartBlocks, 1000);
    });
    
    // Run when cart items are updated
    document.body.addEventListener('cart_item_removed', function() {
        setTimeout(addBogoHintsToCartBlocks, 1000);
    });
    
    document.body.addEventListener('cart_item_added', function() {
        setTimeout(addBogoHintsToCartBlocks, 1000);
    });
    
    // Observer for dynamic content
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                setTimeout(addBogoHintsToCartBlocks, 500);
            }
        });
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});

// Export for potential use in other scripts
window.WCAdvancedBogoCart = {
    addBogoHintsToCartBlocks: function() {
        // Re-export the function for external use
        const cartItems = document.querySelectorAll('.wp-block-woocommerce-cart-item, .wc-block-components-cart-item');
        
        cartItems.forEach(cartItem => {
            if (cartItem.querySelector('.bogo-cart-hint')) {
                return;
            }
            
            const productNameElement = cartItem.querySelector('.wc-block-components-cart-item__name, .cart-item-name');
            if (!productNameElement) {
                return;
            }
            
            const productId = cartItem.dataset.productId || 
                             cartItem.querySelector('[data-product-id]')?.dataset.productId ||
                             cartItem.querySelector('input[name*="product_id"]')?.value;
            
            if (!productId) {
                return;
            }
            
            fetch(wcAdvancedBogoCart.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_bogo_hints',
                    product_id: productId,
                    nonce: wcAdvancedBogoCart.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.hint) {
                    const hintElement = document.createElement('div');
                    hintElement.innerHTML = data.data.hint;
                    hintElement.className = 'bogo-cart-hint';
                    productNameElement.parentNode.insertBefore(hintElement, productNameElement.nextSibling);
                }
            })
            .catch(error => {
                console.error('Error loading BOGO hints:', error);
            });
        });
    }
};