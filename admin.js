// WooCommerce Advanced BOGO - Admin JavaScript
// Using vanilla JavaScript instead of jQuery for individual save functionality

document.addEventListener('DOMContentLoaded', function() {
    console.log('BOGO Admin JS loaded');

    // Initialize jQuery-dependent features when jQuery is available
    if (typeof jQuery !== 'undefined') {
        initializeJQueryFeatures();
    } else {
        // Retry after a short delay if jQuery isn't loaded yet
        setTimeout(function() {
            if (typeof jQuery !== 'undefined') {
                initializeJQueryFeatures();
            }
        }, 500);
    }

    // Initialize vanilla JavaScript features
    initializeIndividualSave();
    initializeRowHighlighting();
});

// jQuery-dependent features (existing functionality)
function initializeJQueryFeatures() {
    const $ = jQuery;

    // Initialize product search for existing rows
    initializeProductSearch();

    // Handle adding new BOGO rules (updated for new structure)
    $(document).on('click', '.add-row[href="repeater-bogo-rules"]', function(e) {
        e.preventDefault();
        addEmptyRule();
    });

    // Handle removing BOGO rules (updated for new structure)
    $(document).on('click', '.wc-bogo-icon.-minus.remove-row', function(e) {
        e.preventDefault();
        var $row = $(this).closest('tr.row-input');
        var $tbody = $row.closest('tbody');
        
        // If this is the last data row, don't remove it (keeping header row)
        if ($tbody.find('tr.row-input').length <= 1) {
            alert('You must have at least one rule.');
            return;
        }
        
        $row.fadeOut(300, function() {
            $(this).remove();
            updateRowIndices();
        });
    });

    // Function to initialize product search
    function initializeProductSearch() {
        // Wait for jQuery and Select2 to be available
        if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
            setTimeout(initializeProductSearch, 100);
            return;
        }

        $('.wc-product-search').each(function() {
            var $select = $(this);
            
            // Destroy existing Select2 if it exists
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            // Initialize Select2 with AJAX
            $select.select2({
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            action: 'woocommerce_json_search_products',
                            term: params.term,
                            security: bogo_admin.search_products_nonce
                        };
                    },
                    processResults: function(data) {
                        var terms = [];
                        if (data) {
                            $.each(data, function(id, text) {
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
                placeholder: $select.data('placeholder') || 'Search for a product...',
                dropdownParent: $('body')
            });
        });

        // Ensure proper styling
        ensureSelect2Styling();
    }

    // Function to add empty rule row (updated for new structure)
    function addEmptyRule() {
        var $tbody = $('.wc-bogo-table tbody');
        var newIndex = $tbody.find('tr.row-input').length;
        
        // Clone the first data row (skip the header row)
        var $firstRow = $tbody.find('tr.row-input').first();
        var $newRow = $firstRow.clone();
        
        // Clear all values in the new row
        $newRow.find('input[type="number"]').val('');
        $newRow.find('input[type="date"]').val('');
        
        // Clear and reset select elements
        $newRow.find('select').each(function() {
            var $select = $(this);
            var originalName = $select.attr('name');

            // Only destroy if initialized
            if ($select.data('select2')) {
                $select.select2('destroy');
            }

            // Remove Select2 containers and dropdowns if present
            $select.siblings('.select2-container').remove();
            $select.siblings('.select2-dropdown').remove();
            $select.siblings('.select2-results').remove();

            $select.removeClass('select2-hidden-accessible');
            $select.empty();
            $select.append('<option value="">Search for a product...</option>');
            
            // Add "All Products" option for buy_product selects
            if ($select.hasClass('-buy_product')) {
                $select.append('<option value="all">— All Products —</option>');
            }

            $select.attr('name', originalName.replace(/\[\d+\]/, '[' + newIndex + ']'));
        });

        // Update the data-row-id attribute
        $newRow.attr('data-row-id', newIndex);
        $newRow.removeClass('-row-0 -row-1 -row-2 -row-3 -row-4 -row-5 -row-6 -row-7 -row-8 -row-9');
        $newRow.addClass('-row-' + newIndex);

        // Update all name attributes in the new row
        $newRow.find('input, select').each(function() {
            var name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace(/\[\d+\]/, '[' + newIndex + ']'));
            }
        });

        // Update the save button data attribute
        $newRow.find('.save-individual-rule').attr('data-rule-index', newIndex);

        // Add the new row
        $tbody.append($newRow);

        // Update row indices
        updateRowIndices();

        // ** FIX: Ensure Select2 AJAX is initialized after row is added **
        setTimeout(function() {
            $newRow.find('.wc-product-search').each(function() {
                var $select = $(this);
                $select.select2({
                    ajax: {
                        url: ajaxurl,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                action: 'woocommerce_json_search_products',
                                term: params.term,
                                security: bogo_admin.search_products_nonce
                            };
                        },
                        processResults: function(data) {
                            var terms = [];
                            if (data) {
                                $.each(data, function(id, text) {
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
                    placeholder: $select.data('placeholder') || 'Search for a product...',
                    dropdownParent: $('body')
                });
            });

            // Ensure proper styling for new row
            ensureSelect2Styling();
        }, 300);
    }

    // Function to ensure proper Select2 styling
    function ensureSelect2Styling() {
        $('.wc-bogo-table .select2-container').css({
            'min-width': '200px !important',
            'max-width': '300px !important',
            'width': 'auto !important',
            'display': 'inline-block !important'
        });
    }

    // Function to update row indices (updated for new structure)
    function updateRowIndices() {
        $('.wc-bogo-table tbody tr.row-input').each(function(index) {
            var $row = $(this);
            $row.attr('data-row-id', index);
            $row.removeClass('-row-0 -row-1 -row-2 -row-3 -row-4 -row-5 -row-6 -row-7 -row-8 -row-9');
            $row.addClass('-row-' + index);
            
            // Update all name attributes in the row
            $row.find('input, select').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                }
            });
            
            // Update the save button data attribute
            $row.find('.save-individual-rule').attr('data-rule-index', index);
        });
    }

    // Handle color picker changes with instant preview (no AJAX saving)
    $('input[type="color"]').on('input change', function() {
        var template = $(this).data('template');
        var colorType = $(this).data('color-type');
        var color = $(this).val();
        var templateOption = $(this).closest('.template-option');
        
        // Update the color preview span
        $(this).siblings('span').css('background-color', color);
        
        // Update the preview section based on color type
        var previewSection = templateOption.find('> div:last-child');
        var previewButton = previewSection.find('span[data-button-bg]');
        
        // Simplified color preview handling - only 3 colors to manage
        switch(colorType) {
            case 'background':
                previewSection.css('background-color', color);
                // Auto-calculate and update text color for contrast
                var textColor = getContrastingColor(color);
                previewSection.find('strong, span:not([data-button-bg])').css('color', textColor);
                break;
            case 'theme_color':
                // Theme color affects accents and highlights
                // Could be used for borders, special elements, etc.
                break;
            case 'button_color':
                previewButton.css('background-color', color);
                previewButton.attr('data-button-bg', color);
                // Auto-calculate and update button text color for contrast
                var buttonTextColor = getContrastingColor(color);
                previewButton.css('color', buttonTextColor);
                previewButton.attr('data-button-text', buttonTextColor);
                break;
        }
        
        // Add visual feedback
        $(this).closest('div').addClass('color-changed');
        setTimeout(function() {
            $(this).closest('div').removeClass('color-changed');
        }.bind(this), 200);
    });
    
    // Handle template selection changes (no AJAX saving)
    $('input[name="bogo_template"]').on('change', function() {
        var selectedTemplate = $(this).val();
        $('.template-option').removeClass('selected-template');
        $(this).closest('.template-option').addClass('selected-template');
    });
    
    // Handle reset colors button
    $('.reset-colors-btn').on('click', function() {
        var template = $(this).data('template');
        var templateOption = $(this).closest('.template-option');
        
        // Simplified default colors - Only 3 colors for easier management
        var defaultColors = {
            'template1': {
                'theme_color': '#3B82F6',
                'background': '#F8FAFC',
                'button_color': '#3B82F6'
            },
            'template2': {
                'theme_color': '#8B5CF6',
                'background': '#1E1B4B',
                'button_color': '#EC4899'
            },
            'template3': {
                'theme_color': '#F59E0B',
                'background': '#7C2D12',
                'button_color': '#F59E0B'
            }
        };
        
        // Reset each color input to its default
        templateOption.find('input[type="color"]').each(function() {
            var colorType = $(this).data('color-type');
            var defaultValue = defaultColors[template][colorType];
            
            if (defaultValue) {
                $(this).val(defaultValue);
                $(this).trigger('change');
            }
        });
        
        // Show feedback
        $(this).text('✅ Reset!').addClass('reset-success');
        setTimeout(function() {
            $(this).text('🔄 Reset Colors').removeClass('reset-success');
        }.bind(this), 1000);
    });

    // Helper function to get contrasting color (white or black) based on background
    function getContrastingColor(hexColor) {
        // Remove # if present
        hexColor = hexColor.replace('#', '');
        
        // Convert to RGB
        var r = parseInt(hexColor.substr(0, 2), 16);
        var g = parseInt(hexColor.substr(2, 2), 16);
        var b = parseInt(hexColor.substr(4, 2), 16);
        
        // Calculate luminance
        var luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        
        // Return contrasting color
        return luminance > 0.5 ? '#000000' : '#FFFFFF';
    }

}

// Vanilla JavaScript features for individual save functionality
function initializeIndividualSave() {
    // Add event listeners for individual save buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.save-individual-rule')) {
            e.preventDefault();
            handleIndividualSave(e.target.closest('.save-individual-rule'));
        }
    });
}

function initializeRowHighlighting() {
    // CSS handles table row styling automatically
    // We just need to ensure no inline styles interfere
    document.addEventListener('mouseout', function(e) {
        if (e.target.closest('tr.row-input')) {
            const row = e.target.closest('tr.row-input');
            // Remove any inline styles to let CSS take over
            row.style.backgroundColor = '';
        }
    });
}

function handleIndividualSave(button) {
    const ruleIndex = button.getAttribute('data-rule-index');
    const row = button.closest('tr.row-input');
    
    // Collect rule data from the row
    const ruleData = {
        buy_product: row.querySelector(`select[name*="[buy_product]"]`).value,
        buy_qty: row.querySelector(`input[name*="[buy_qty]"]`).value,
        get_product: row.querySelector(`select[name*="[get_product]"]`).value,
        get_qty: row.querySelector(`input[name*="[get_qty]"]`).value,
        discount: row.querySelector(`input[name*="[discount]"]`).value,
        start_date: row.querySelector(`input[name*="[start_date]"]`).value,
        end_date: row.querySelector(`input[name*="[end_date]"]`).value
    };

    // Validate required fields
    if (!ruleData.buy_product || !ruleData.get_product || !ruleData.buy_qty) {
        showNotification('Please fill in all required fields: Buy Product, Get Product, and Buy Quantity.', 'error');
        return;
    }

    // Show loading state
    button.classList.add('saving');
    button.disabled = true;
    button.querySelector('.save-loading').style.display = 'block';

    // Prepare FormData for AJAX request
    const formData = new FormData();
    formData.append('action', 'save_individual_bogo_rule');
    formData.append('rule_index', ruleIndex);
    formData.append('nonce', bogo_admin.save_individual_rule_nonce);
    
    // Append rule data
    Object.keys(ruleData).forEach(key => {
        formData.append(`rule_data[${key}]`, ruleData[key]);
    });

    // Make AJAX request
    fetch(bogo_admin.ajaxurl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        // Hide loading state
        button.classList.remove('saving');
        button.disabled = false;
        button.querySelector('.save-loading').style.display = 'none';

        if (data.success) {
            // Show success state
            button.classList.add('success');
            showNotification(data.data.message, 'success');
            
            // Reset success state after 3 seconds
            setTimeout(() => {
                button.classList.remove('success');
            }, 3000);
        } else {
            // Show error state
            button.classList.add('error');
            showNotification(data.data.message || 'An error occurred while saving the rule.', 'error');
            
            // Reset error state after 3 seconds
            setTimeout(() => {
                button.classList.remove('error');
            }, 3000);
        }
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        
        // Hide loading state
        button.classList.remove('saving');
        button.disabled = false;
        button.querySelector('.save-loading').style.display = 'none';
        
        // Show error state
        button.classList.add('error');
        showNotification('Network error. Please check your connection and try again.', 'error');
        
        // Reset error state after 3 seconds
        setTimeout(() => {
            button.classList.remove('error');
        }, 3000);
    });
}

function showNotification(message, type) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.rule-save-notification');
    existingNotifications.forEach(notification => notification.remove());

    // Create new notification
    const notification = document.createElement('div');
    notification.className = `rule-save-notification ${type}`;
    notification.textContent = message;

    // Add to DOM
    document.body.appendChild(notification);

    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);

    // Hide and remove notification after 4 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 4000);
}