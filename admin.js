jQuery(document).ready(function($) {
    console.log('BOGO Admin JS loaded');

    // Initialize product search for existing rows
    initializeProductSearch();

    // Handle adding new BOGO rules
    $('#add-bogo-rule').on('click', function() {
        addEmptyRule();
    });

    // Handle removing BOGO rules
    $(document).on('click', '.remove-bogo-rule', function() {
        var $row = $(this).closest('.bogo-rule-row');
        var $tbody = $('#bogo-rules-tbody');
        
        // If this is the last row, don't remove it
        if ($tbody.find('.bogo-rule-row').length <= 1) {
            alert('You must have at least one rule.');
            return;
        }
        
        $row.fadeOut(300, function() {
            $(this).remove();
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

    // Function to add empty rule row
    function addEmptyRule() {
    var $tbody = $('#bogo-rules-tbody');
    var newIndex = $tbody.find('.bogo-rule-row').length;
    
    // Clone the first row
    var $firstRow = $tbody.find('.bogo-rule-row').first();
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

        $select.attr('name', originalName.replace(/\[\d+\]/, '[' + newIndex + ']'));
    });

    // Update the data-index attribute
    $newRow.attr('data-index', newIndex);

    // Update all name attributes in the new row
    $newRow.find('input, select').each(function() {
        var name = $(this).attr('name');
        if (name) {
            $(this).attr('name', name.replace(/\[\d+\]/, '[' + newIndex + ']'));
        }
    });

    // Add the new row
    $tbody.append($newRow);

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
        $('.bogo-rule-row .select2-container').css({
            'min-width': '200px !important',
            'max-width': '300px !important',
            'width': 'auto !important',
            'display': 'inline-block !important'
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
});
