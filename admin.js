document.addEventListener('DOMContentLoaded', function () {
    let ruleIndex = 0;

    // Function to ensure proper Select2 styling
    function ensureSelect2Styling() {
        jQuery('.bogo-rule-row .select2-container').each(function() {
            jQuery(this).css({
                'min-width': '200px',
                'max-width': '300px',
                'width': 'auto',
                'display': 'inline-block'
            });
        });
    }

    // Initialize WooCommerce product search
    function initializeProductSearch() {
        // Wait for jQuery and Select2 to be available
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
            console.log('BOGO: Waiting for Select2 to be available...');
            setTimeout(initializeProductSearch, 100);
            return;
        }

        console.log('BOGO: Initializing product search...');

        // Initialize product search for all wc-product-search elements
        jQuery('.wc-product-search').each(function() {
            // Skip if already initialized
            if (jQuery(this).hasClass('select2-hidden-accessible')) {
                console.log('BOGO: Select2 already initialized for this element');
                return;
            }

            console.log('BOGO: Setting up Select2 for element:', this);

            // Get nonce from various possible sources
            let nonce = '';
            if (typeof bogo_admin !== 'undefined' && bogo_admin.nonce) {
                nonce = bogo_admin.nonce;
                console.log('BOGO: Using nonce from bogo_admin');
            } else if (typeof bogo_admin !== 'undefined' && bogo_admin.search_products_nonce) {
                nonce = bogo_admin.search_products_nonce;
                console.log('BOGO: Using nonce from bogo_admin.search_products_nonce');
            } else {
                // Fallback: create a nonce from meta tag
                nonce = jQuery('meta[name="woocommerce-search-products-nonce"]').attr('content') || '';
                console.log('BOGO: Using fallback nonce from meta tag:', nonce);
            }

            // If still no nonce, create a basic one
            if (!nonce) {
                nonce = 'bogo_search_nonce';
                console.log('BOGO: Using default nonce');
            }

            // Get AJAX URL
            let ajaxUrl = '';
            if (typeof ajaxurl !== 'undefined') {
                ajaxUrl = ajaxurl;
            } else if (typeof bogo_admin !== 'undefined' && bogo_admin.ajaxurl) {
                ajaxUrl = bogo_admin.ajaxurl;
            } else {
                ajaxUrl = '/wp-admin/admin-ajax.php';
            }

            console.log('BOGO: Using AJAX URL:', ajaxUrl);

            try {
                jQuery(this).select2({
                    ajax: {
                        url: ajaxUrl,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term,
                                action: 'woocommerce_json_search_products',
                                security: nonce
                            };
                        },
                        processResults: function(data) {
                            var terms = [];
                            if (data) {
                                jQuery.each(data, function(id, text) {
                                    terms.push({
                                        id: id,
                                        text: text
                                    });
                                });
                            }
                            return {
                                results: terms
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 2,
                    placeholder: jQuery(this).data('placeholder') || 'Search for a product...',
                    allowClear: true,
                    width: 'auto',
                    dropdownParent: jQuery('body'), // Ensure dropdown appears correctly
                    templateResult: function(data) {
                        if (data.loading) return data.text;
                        return jQuery('<span>').text(data.text);
                    },
                    templateSelection: function(data) {
                        if (data.id === '') return data.text;
                        return jQuery('<span>').text(data.text);
                    }
                });

                // Ensure the Select2 container has proper width
                const select2Container = jQuery(this).next('.select2-container');
                if (select2Container.length) {
                    select2Container.css({
                        'min-width': '200px',
                        'max-width': '300px',
                        'width': 'auto'
                    });
                }

                console.log('BOGO: Select2 initialized successfully');
            } catch (error) {
                console.error('BOGO: Error initializing Select2:', error);
            }
        });
        
        // Ensure proper styling after initialization
        setTimeout(ensureSelect2Styling, 100);
    }

    // Initialize rule counter based on existing rows
    function initializeRuleIndex() {
        const existingRows = document.querySelectorAll('.bogo-rule-row');
        ruleIndex = existingRows.length;
    }

    // Add hover effects to a button
    function addButtonHoverEffects(button) {
        button.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#dc3545';
            this.style.color = '#fff';
            this.style.transform = 'scale(1.1)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.backgroundColor = 'transparent';
            this.style.color = '#dc3545';
            this.style.transform = 'scale(1)';
        });
    }

    // Add remove functionality to a button
    function addRemoveHandler(button) {
        button.addEventListener('click', function () {
            if (confirm('Are you sure you want to clear this rule?')) {
                const row = this.closest('.bogo-rule-row');
                if (row) {
                    // Add fade out effect
                    row.style.transition = 'opacity 0.3s ease';
                    row.style.opacity = '0';
                    
                    setTimeout(() => {
                        row.remove();
                        updateRuleIndices();
                        
                        // Show message if no rules left
                        const tbody = document.getElementById('bogo-rules-tbody');
                        if (tbody.children.length === 0) {
                            addEmptyRule();
                        }
                    }, 300);
                }
            }
        });
    }

    // Update all rule indices after removal
    function updateRuleIndices() {
        const rows = document.querySelectorAll('.bogo-rule-row');
        rows.forEach((row, index) => {
            row.setAttribute('data-index', index);
            
            // Update all name attributes
            const inputs = row.querySelectorAll('input, select');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    const newName = name.replace(/\[\d+\]/, `[${index}]`);
                    input.setAttribute('name', newName);
                }
            });
        });
        
        ruleIndex = rows.length;
    }

    // Add a new empty rule
    function addEmptyRule() {
        const tbody = document.getElementById('bogo-rules-tbody');
        
        if (!tbody) {
            console.error('BOGO Error: Table body not found');
            return;
        }
        
        // Get existing row as template
        const existingRow = document.querySelector('.bogo-rule-row');
        if (!existingRow) {
            console.error('BOGO Error: No existing row found to clone');
            return;
        }

        const newRow = existingRow.cloneNode(true);
        
        // Clear all values
        const inputs = newRow.querySelectorAll('input');
        inputs.forEach(input => {
            if (input.type === 'number' && input.name.includes('get_qty')) {
                input.value = '1';
            } else {
                input.value = '';
            }
        });
        
        const selects = newRow.querySelectorAll('select');
        selects.forEach(select => {
            select.selectedIndex = 0;
            
            // Completely clean up any Select2 elements
            select.classList.remove('select2-hidden-accessible');
            select.removeAttribute('data-select2-id');
            select.removeAttribute('aria-hidden');
            select.removeAttribute('tabindex');
            
            // Remove any Select2 containers
            const select2Container = select.parentNode.querySelector('.select2-container');
            if (select2Container) {
                select2Container.remove();
            }
            
            // Remove any Select2 dropdowns
            const select2Dropdown = document.querySelector('.select2-dropdown');
            if (select2Dropdown) {
                select2Dropdown.remove();
            }
            
            // Remove any Select2 results
            const select2Results = document.querySelector('.select2-results');
            if (select2Results) {
                select2Results.remove();
            }
            
            // Reset the select element to its original state
            select.style.width = '';
            select.style.display = '';
            select.style.position = '';
            select.style.opacity = '';
        });
        
        // Update all name attributes with new index
        const allInputs = newRow.querySelectorAll('input, select');
        allInputs.forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                const newName = name.replace(/\[\d+\]/, `[${ruleIndex}]`);
                input.setAttribute('name', newName);
            }
        });
        
        newRow.setAttribute('data-index', ruleIndex);
        
        // Append to tbody
        tbody.appendChild(newRow);
        
        // Add event listeners to the new row's remove button
        const removeButton = newRow.querySelector('.remove-bogo-rule');
        if (removeButton) {
            addButtonHoverEffects(removeButton);
            addRemoveHandler(removeButton);
        }
        
        // Reinitialize product search for new row with delay
        setTimeout(() => {
            console.log('BOGO: Reinitializing product search for new row...');
            initializeProductSearch();
            
            // Ensure proper styling after a longer delay
            setTimeout(ensureSelect2Styling, 500);
        }, 300);
        
        ruleIndex++;
        
        // Add slide-in effect
        newRow.style.opacity = '0';
        newRow.style.transform = 'translateY(-10px)';
        newRow.style.transition = 'all 0.3s ease';
        
        setTimeout(() => {
            newRow.style.opacity = '1';
            newRow.style.transform = 'translateY(0)';
        }, 10);
    }

    // Initialize existing remove buttons
    function initializeExistingButtons() {
        document.querySelectorAll('.remove-bogo-rule').forEach(button => {
            addButtonHoverEffects(button);
            addRemoveHandler(button);
        });
    }

    // Add new rule button handler
    const addButton = document.getElementById('add-bogo-rule');
    
    if (addButton) {
        console.log('BOGO: Add button found, adding event listener');
        addButton.addEventListener('click', function(e) {
            console.log('BOGO: Add button clicked');
            e.preventDefault();
            addEmptyRule();
            
            // Scroll to the new rule
            setTimeout(() => {
                const tbody = document.getElementById('bogo-rules-tbody');
                const lastRow = tbody.lastElementChild;
                if (lastRow) {
                    lastRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
        });
    } else {
        console.error('BOGO Error: Add button not found');
        console.log('BOGO: Available buttons:', document.querySelectorAll('button'));
    }

    // Initialize everything
    console.log('BOGO: Initializing admin functionality...');
    initializeRuleIndex();
    initializeExistingButtons();
    
    // Initialize product search with delay to ensure WooCommerce scripts are loaded
    setTimeout(initializeProductSearch, 1000);
    
    // Add a fallback initialization in case the first one fails
    setTimeout(function() {
        console.log('BOGO: Running fallback initialization...');
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
            const uninitializedSelects = jQuery('.wc-product-search').not('.select2-hidden-accessible');
            if (uninitializedSelects.length > 0) {
                console.log('BOGO: Found uninitialized selects, retrying...');
                initializeProductSearch();
            }
        }
    }, 2000);
    
    console.log('BOGO: Admin functionality initialized');

    // Form validation before submit
    const form = document.getElementById('bogo-rules-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const rows = document.querySelectorAll('.bogo-rule-row');
            let hasValidRule = false;
            
            rows.forEach(row => {
                const buyProduct = row.querySelector('[name*="[buy_product]"]').value;
                const getProduct = row.querySelector('[name*="[get_product]"]').value;
                const buyQty = row.querySelector('[name*="[buy_qty]"]').value;
                const discount = row.querySelector('[name*="[discount]"]').value;
                
                if (buyProduct && getProduct && buyQty && discount) {
                    hasValidRule = true;
                }
            });
            
            if (!hasValidRule) {
                e.preventDefault();
                alert('Please add at least one complete BOGO rule before saving.');
                return false;
            }
        });
    }
});
