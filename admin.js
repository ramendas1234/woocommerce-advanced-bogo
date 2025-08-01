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
            
            // Remove any existing Select2
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            
            // Remove any Select2 containers and dropdowns
            $select.siblings('.select2-container').remove();
            $select.siblings('.select2-dropdown').remove();
            $select.siblings('.select2-results').remove();
            
            // Remove select2 classes
            $select.removeClass('select2-hidden-accessible');
            
            // Clear the select
            $select.empty();
            $select.append('<option value="">Search for a product...</option>');
            
            // Update the name attribute
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
        
        // Initialize product search for the new row after a delay
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
        
        switch(colorType) {
            case 'background':
                previewSection.css('background-color', color);
                break;
            case 'text':
                previewSection.find('strong, span:not([data-button-bg])').css('color', color);
                break;
            case 'primary':
            case 'secondary':
                // Update gradient if both colors are available
                var primaryColor = templateOption.find('input[data-color-type="primary"]').val();
                var secondaryColor = templateOption.find('input[data-color-type="secondary"]').val();
                if (primaryColor && secondaryColor) {
                    previewButton.css('background', 'linear-gradient(45deg, ' + primaryColor + ', ' + secondaryColor + ')');
                }
                break;
            case 'button_bg':
                previewButton.css('background-color', color);
                previewButton.attr('data-button-bg', color);
                break;
            case 'button_text':
                previewButton.css('color', color);
                previewButton.attr('data-button-text', color);
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
        
        // Get default colors for this template
        var defaultColors = {
            'template1': {
                'primary': '#3B82F6',
                'secondary': '#10B981',
                'text': '#1F2937',
                'background': '#F8FAFC',
                'button_bg': '#3B82F6',
                'button_text': '#FFFFFF'
            },
            'template2': {
                'primary': '#8B5CF6',
                'secondary': '#EC4899',
                'text': '#FFFFFF',
                'background': '#1E1B4B',
                'button_bg': '#EC4899',
                'button_text': '#FFFFFF'
            },
            'template3': {
                'primary': '#F59E0B',
                'secondary': '#EF4444',
                'text': '#FFFFFF',
                'background': '#7C2D12',
                'button_bg': '#F59E0B',
                'button_text': '#FFFFFF'
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
});
