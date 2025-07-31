/**
 * WooCommerce Advanced BOGO - Cart Blocks React Component
 * Proper implementation following WooCommerce blocks documentation
 */

// Wait for WordPress libraries to be available
(function() {
    'use strict';

    // Function to initialize when wp is available
    function initBogoCartBlocks() {
        // Check if wp is available
        if (typeof wp === 'undefined') {
            console.log('BOGO Cart Blocks: wp not available, retrying...');
            setTimeout(initBogoCartBlocks, 100);
            return;
        }

        const { createElement, Fragment } = wp.element;
        const { addFilter } = wp.hooks;
        const { createHigherOrderComponent } = wp.compose;
        const { useSelect } = wp.data;

        // BOGO Hint Component
        const BogoHint = ({ hint }) => {
            if (!hint) return null;
            
            return createElement('div', {
                className: 'bogo-cart-hint',
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
                
                // Get cart item data from Store API
                const cartItem = useSelect((select) => {
                    const store = select('wc/store/cart');
                    if (store) {
                        return store.getCartItem(props.attributes?.cartItemKey);
                    }
                    return null;
                }, [props.attributes?.cartItemKey]);
                
                const bogoHint = cartItem?.extensions?.wc_advanced_bogo;
                
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

        console.log('BOGO Cart Blocks: React components initialized');
    }

    // Frontend cart blocks integration
    document.addEventListener('DOMContentLoaded', function() {
        // Function to add BOGO hints to cart blocks on frontend
        function addBogoHintsToCartBlocks() {
            // Get all cart items
            const cartItems = document.querySelectorAll('.wp-block-woocommerce-cart-item, .wc-block-components-cart-item');
            
            if (cartItems.length === 0) {
                return;
            }

            cartItems.forEach(cartItem => {
                // Skip if hint already exists
                if (cartItem.querySelector('.bogo-cart-hint')) {
                    return;
                }

                // Find product name element
                const productNameElement = cartItem.querySelector('.wc-block-components-cart-item__name, .cart-item-name, h4, h3');
                
                if (!productNameElement) {
                    return;
                }

                // Get BOGO hint from Store API extensions
                const bogoHint = getBogoHintFromStoreAPI(cartItem);
                
                if (bogoHint && bogoHint.html) {
                    const hintElement = document.createElement('div');
                    hintElement.innerHTML = bogoHint.html;
                    hintElement.className = 'bogo-cart-hint';
                    
                    // Insert after product name
                    productNameElement.parentNode.insertBefore(hintElement, productNameElement.nextSibling);
                }
            });
        }

        // Function to get BOGO hint from Store API
        function getBogoHintFromStoreAPI(cartItem) {
            // Try to get cart data from WooCommerce blocks
            if (window.wc_cart_data && window.wc_cart_data.items) {
                const cartItemKey = cartItem.dataset.cartItemKey;
                if (cartItemKey && window.wc_cart_data.items[cartItemKey]) {
                    const itemData = window.wc_cart_data.items[cartItemKey];
                    if (itemData.extensions && itemData.extensions.wc_advanced_bogo) {
                        return itemData.extensions.wc_advanced_bogo;
                    }
                }
            }

            // Try to get from cart item data attributes
            const extensionsData = cartItem.dataset.extensions;
            if (extensionsData) {
                try {
                    const extensions = JSON.parse(extensionsData);
                    if (extensions.wc_advanced_bogo) {
                        return extensions.wc_advanced_bogo;
                    }
                } catch (e) {
                    console.log('Error parsing extensions data:', e);
                }
            }

            // Fallback: Get product ID and fetch via AJAX
            const productId = getProductIdFromCartItem(cartItem);
            if (productId) {
                return fetchBogoHintViaAjax(productId);
            }

            return null;
        }

        // Function to get product ID from cart item
        function getProductIdFromCartItem(cartItem) {
            // Try data attributes first
            let productId = cartItem.dataset.productId || 
                           cartItem.querySelector('[data-product-id]')?.dataset.productId ||
                           cartItem.querySelector('[data-product_id]')?.dataset.product_id;

            // Try input fields
            if (!productId) {
                const input = cartItem.querySelector('input[name*="product_id"], input[name*="product-id"]');
                if (input) {
                    productId = input.value;
                }
            }

            // Try link href
            if (!productId) {
                const link = cartItem.querySelector('a[href*="product"]');
                if (link) {
                    const match = link.href.match(/product_id=(\d+)/);
                    if (match) {
                        productId = match[1];
                    }
                }
            }

            // Try from WooCommerce cart data
            if (!productId && window.wc_cart_data) {
                const cartItemKey = cartItem.dataset.cartItemKey;
                if (cartItemKey && window.wc_cart_data.items && window.wc_cart_data.items[cartItemKey]) {
                    productId = window.wc_cart_data.items[cartItemKey].id;
                }
            }

            return productId;
        }

        // Function to fetch BOGO hint via AJAX
        function fetchBogoHintViaAjax(productId) {
            return fetch(wc_advanced_bogo_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_bogo_hints',
                    product_id: productId,
                    nonce: wc_advanced_bogo_ajax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.hint) {
                    return {
                        html: data.data.hint,
                        remaining_qty: data.data.remaining_qty,
                        get_qty: data.data.get_qty,
                        get_product_name: data.data.get_product_name,
                        discount_text: data.data.discount_text
                    };
                }
                return null;
            })
            .catch(error => {
                console.error('Error loading BOGO hints:', error);
                return null;
            });
        }

        // Run on page load with delay
        setTimeout(addBogoHintsToCartBlocks, 2000);

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

        // Run when quantity is updated
        document.body.addEventListener('cart_item_updated', function() {
            setTimeout(addBogoHintsToCartBlocks, 1000);
        });

        // Observer for dynamic content changes
        const observer = new MutationObserver(function(mutations) {
            let shouldUpdate = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // Check if any cart-related elements were added
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            if (node.classList && (
                                node.classList.contains('wp-block-woocommerce-cart-item') ||
                                node.classList.contains('wc-block-components-cart-item') ||
                                node.querySelector('.wp-block-woocommerce-cart-item') ||
                                node.querySelector('.wc-block-components-cart-item')
                            )) {
                                shouldUpdate = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldUpdate) {
                setTimeout(addBogoHintsToCartBlocks, 500);
            }
        });

        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        // Also run periodically to catch any missed updates
        setInterval(addBogoHintsToCartBlocks, 5000);

        // Debug: Log cart items found
        console.log('BOGO Cart Blocks: Found', document.querySelectorAll('.wp-block-woocommerce-cart-item, .wc-block-components-cart-item').length, 'cart items');
    });

    // Initialize React components when wp is available
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBogoCartBlocks);
    } else {
        initBogoCartBlocks();
    }

    // Export for external use
    window.WCAdvancedBogoCart = {
        addBogoHintsToCartBlocks: function() {
            // This function can be called externally if needed
            setTimeout(function() {
                const cartItems = document.querySelectorAll('.wp-block-woocommerce-cart-item, .wc-block-components-cart-item');
                cartItems.forEach(cartItem => {
                    if (!cartItem.querySelector('.bogo-cart-hint')) {
                        // Trigger the hint loading logic
                        const productNameElement = cartItem.querySelector('.wc-block-components-cart-item__name, .cart-item-name');
                        if (productNameElement) {
                            const bogoHint = getBogoHintFromStoreAPI(cartItem);
                            if (bogoHint && bogoHint.html) {
                                const hintElement = document.createElement('div');
                                hintElement.innerHTML = bogoHint.html;
                                hintElement.className = 'bogo-cart-hint';
                                productNameElement.parentNode.insertBefore(hintElement, productNameElement.nextSibling);
                            }
                        }
                    }
                });
            }, 1000);
        }
    };

})();