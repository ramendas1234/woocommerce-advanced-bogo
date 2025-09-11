<?php
/**
 * BOGO Rule Edit Page
 * 
 * @package WC_Advanced_BOGO
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$is_new = empty( $rule );
$page_title = $is_new ? __( 'Add New BOGO Rule', 'wc-advanced-bogo' ) : __( 'Edit BOGO Rule', 'wc-advanced-bogo' );

// Display admin notices
if ( isset( $_GET['updated'] ) ) {
    echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Rule updated successfully.', 'wc-advanced-bogo' ) . '</p></div>';
}

if ( isset( $_GET['error'] ) ) {
    echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Error saving rule. Please try again.', 'wc-advanced-bogo' ) . '</p></div>';
}

// Set default values
$defaults = [
    'id' => 0,
    'title' => '',
    'enabled' => 1,
    'buy_product' => '',
    'buy_qty' => 1,
    'get_product' => '',
    'get_qty' => 1,
    'discount' => 100,
    'start_date' => '',
    'end_date' => ''
];

if ( $rule ) {
    foreach ( $defaults as $key => $default_value ) {
        $defaults[$key] = isset( $rule->$key ) ? $rule->$key : $default_value;
    }
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html( $page_title ); ?>
    </h1>
    
    <a href="<?php echo admin_url( 'admin.php?page=wc-advanced-bogo' ); ?>" class="page-title-action">
        <?php _e( '← Back to Rules', 'wc-advanced-bogo' ); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <form method="post" id="bogo-rule-form" novalidate="novalidate">
        <?php wp_nonce_field( 'save_bogo_rule', 'bogo_rule_nonce' ); ?>
        <input type="hidden" name="rule_id" value="<?php echo esc_attr( $defaults['id'] ); ?>">
        <input type="hidden" name="save_rule" value="1">
        
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div id="titlediv">
                        <div id="titlewrap">
                            <label class="screen-reader-text" id="title-prompt-text" for="title">
                                <?php _e( 'Enter title here', 'wc-advanced-bogo' ); ?>
                            </label>
                            <input type="text" name="title" size="30" value="<?php echo esc_attr( $defaults['title'] ); ?>" 
                                   id="title" spellcheck="true" autocomplete="off" 
                                   placeholder="<?php _e( 'Enter rule title here', 'wc-advanced-bogo' ); ?>" required>
                        </div>
                    </div>
                    
                    <!-- Main Settings Metabox -->
                    <div id="bogo-rule-settings" class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle ui-sortable-handle">
                                <span><?php _e( 'Rule Settings', 'wc-advanced-bogo' ); ?></span>
                            </h2>
                        </div>
                        <div class="inside">
                            <div class="wc-bogo-fields -left">
                                
                                <!-- Rule Status -->
                                <div class="wc-bogo-field">
                                    <div class="wc-bogo-label">
                                        <label for="enabled"><?php _e( 'Active', 'wc-advanced-bogo' ); ?></label>
                                        <p class="description"><?php _e( 'Enable or disable this BOGO rule', 'wc-advanced-bogo' ); ?></p>
                                    </div>
                                    <div class="wc-bogo-input">
                                        <label class="wc-bogo-true-false">
                                            <input type="checkbox" name="enabled" id="enabled" value="1" 
                                                   <?php checked( $defaults['enabled'], 1 ); ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Buy Product -->
                                <div class="wc-bogo-field">
                                    <div class="wc-bogo-label">
                                        <label for="buy_product"><?php _e( 'Buy Product', 'wc-advanced-bogo' ); ?></label>
                                        <p class="description"><?php _e( 'Select the product(s) customer needs to buy', 'wc-advanced-bogo' ); ?></p>
                                    </div>
                                    <div class="wc-bogo-input">
                                        <select name="buy_product" id="buy_product" class="wc-product-search" 
                                                data-placeholder="<?php _e( 'Search for a product...', 'wc-advanced-bogo' ); ?>" required>
                                            <option value=""><?php _e( 'Search for a product...', 'wc-advanced-bogo' ); ?></option>
                                            <option value="all" <?php selected( $defaults['buy_product'], 'all' ); ?>>
                                                <?php _e( '— All Products —', 'wc-advanced-bogo' ); ?>
                                            </option>
                                            <?php if ( $defaults['buy_product'] && $defaults['buy_product'] !== 'all' ) :
                                                $product = wc_get_product( $defaults['buy_product'] );
                                                if ( $product ) : ?>
                                                <option value="<?php echo esc_attr( $defaults['buy_product'] ); ?>" selected>
                                                    <?php echo esc_html( $product->get_name() ); ?>
                                                </option>
                                            <?php endif; endif; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Buy Quantity -->
                                <div class="wc-bogo-field">
                                    <div class="wc-bogo-label">
                                        <label for="buy_qty"><?php _e( 'Buy Quantity', 'wc-advanced-bogo' ); ?></label>
                                        <p class="description"><?php _e( 'Number of items customer needs to buy', 'wc-advanced-bogo' ); ?></p>
                                    </div>
                                    <div class="wc-bogo-input">
                                        <input type="number" name="buy_qty" id="buy_qty" 
                                               value="<?php echo esc_attr( $defaults['buy_qty'] ); ?>" 
                                               min="1" step="1" required class="small-text">
                                    </div>
                                </div>
                                
                                <!-- Get Product -->
                                <div class="wc-bogo-field">
                                    <div class="wc-bogo-label">
                                        <label for="get_product"><?php _e( 'Get Product', 'wc-advanced-bogo' ); ?></label>
                                        <p class="description"><?php _e( 'Select the product customer will get as gift', 'wc-advanced-bogo' ); ?></p>
                                    </div>
                                    <div class="wc-bogo-input">
                                        <select name="get_product" id="get_product" class="wc-product-search" 
                                                data-placeholder="<?php _e( 'Search for a product...', 'wc-advanced-bogo' ); ?>" required>
                                            <option value=""><?php _e( 'Search for a product...', 'wc-advanced-bogo' ); ?></option>
                                            <?php if ( $defaults['get_product'] ) :
                                                $product = wc_get_product( $defaults['get_product'] );
                                                if ( $product ) : ?>
                                                <option value="<?php echo esc_attr( $defaults['get_product'] ); ?>" selected>
                                                    <?php echo esc_html( $product->get_name() ); ?>
                                                </option>
                                            <?php endif; endif; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Get Quantity -->
                                <div class="wc-bogo-field">
                                    <div class="wc-bogo-label">
                                        <label for="get_qty"><?php _e( 'Get Quantity', 'wc-advanced-bogo' ); ?></label>
                                        <p class="description"><?php _e( 'Number of gift items customer will receive', 'wc-advanced-bogo' ); ?></p>
                                    </div>
                                    <div class="wc-bogo-input">
                                        <input type="number" name="get_qty" id="get_qty" 
                                               value="<?php echo esc_attr( $defaults['get_qty'] ); ?>" 
                                               min="1" step="1" required class="small-text">
                                    </div>
                                </div>
                                
                                <!-- Discount Percentage -->
                                <div class="wc-bogo-field">
                                    <div class="wc-bogo-label">
                                        <label for="discount"><?php _e( 'Discount (%)', 'wc-advanced-bogo' ); ?></label>
                                        <p class="description"><?php _e( 'Discount percentage for the gift product (100% = free)', 'wc-advanced-bogo' ); ?></p>
                                    </div>
                                    <div class="wc-bogo-input">
                                        <input type="number" name="discount" id="discount" 
                                               value="<?php echo esc_attr( $defaults['discount'] ); ?>" 
                                               min="0" max="100" step="1" required class="small-text">
                                        <span class="description">%</span>
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                    
                    <!-- Schedule Metabox -->
                    <div id="bogo-rule-schedule" class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle ui-sortable-handle">
                                <span><?php _e( 'Schedule', 'wc-advanced-bogo' ); ?></span>
                            </h2>
                        </div>
                        <div class="inside">
                            <div class="wc-bogo-fields -left">
                                
                                <!-- Start Date -->
                                <div class="wc-bogo-field">
                                    <div class="wc-bogo-label">
                                        <label for="start_date"><?php _e( 'Start Date', 'wc-advanced-bogo' ); ?></label>
                                        <p class="description"><?php _e( 'When should this rule become active? Leave empty for immediate activation.', 'wc-advanced-bogo' ); ?></p>
                                    </div>
                                    <div class="wc-bogo-input">
                                        <input type="date" name="start_date" id="start_date" 
                                               value="<?php echo esc_attr( $defaults['start_date'] ); ?>" 
                                               class="regular-text">
                                    </div>
                                </div>
                                
                                <!-- End Date -->
                                <div class="wc-bogo-field">
                                    <div class="wc-bogo-label">
                                        <label for="end_date"><?php _e( 'End Date', 'wc-advanced-bogo' ); ?></label>
                                        <p class="description"><?php _e( 'When should this rule expire? Leave empty for no expiration.', 'wc-advanced-bogo' ); ?></p>
                                    </div>
                                    <div class="wc-bogo-input">
                                        <input type="date" name="end_date" id="end_date" 
                                               value="<?php echo esc_attr( $defaults['end_date'] ); ?>" 
                                               class="regular-text">
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div id="postbox-container-1" class="postbox-container">
                    <div id="side-sortables" class="meta-box-sortables ui-sortable">
                        
                        <!-- Publish Box -->
                        <div id="submitdiv" class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle ui-sortable-handle">
                                    <span><?php _e( 'Save Rule', 'wc-advanced-bogo' ); ?></span>
                                </h2>
                            </div>
                            <div class="inside">
                                <div class="submitbox" id="submitpost">
                                    <div id="major-publishing-actions">
                                        <div id="delete-action">
                                            <?php if ( ! $is_new ) : ?>
                                                <a class="submitdelete deletion" 
                                                   href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=wc-advanced-bogo&action=delete&rule_id=' . $defaults['id'] ), 'bogo_action_' . $defaults['id'] ); ?>"
                                                   onclick="return confirm('<?php _e( 'Are you sure you want to delete this rule?', 'wc-advanced-bogo' ); ?>')">
                                                    <?php _e( 'Delete Rule', 'wc-advanced-bogo' ); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div id="publishing-action">
                                            <span class="spinner"></span>
                                            <input name="save" type="submit" class="button-primary" id="publish" 
                                                   value="<?php echo $is_new ? __( 'Create Rule', 'wc-advanced-bogo' ) : __( 'Update Rule', 'wc-advanced-bogo' ); ?>">
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Rule Preview -->
                        <div id="rule-preview" class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle ui-sortable-handle">
                                    <span><?php _e( 'Rule Preview', 'wc-advanced-bogo' ); ?></span>
                                </h2>
                            </div>
                            <div class="inside">
                                <div id="rule-preview-content">
                                    <p class="description"><?php _e( 'Fill in the rule details to see a preview', 'wc-advanced-bogo' ); ?></p>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
/* Toggle switch for enabled field */
.wc-bogo-true-false {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}

.wc-bogo-true-false input {
    opacity: 0;
    width: 0;
    height: 0;
}

.wc-bogo-true-false .slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.wc-bogo-true-false .slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

.wc-bogo-true-false input:checked + .slider {
    background-color: #2271b1;
}

.wc-bogo-true-false input:checked + .slider:before {
    transform: translateX(20px);
}

/* Form validation styles */
.form-invalid input, .form-invalid select {
    border-color: #d63638 !important;
    box-shadow: 0 0 2px rgba(214, 54, 56, 0.8) !important;
}

.form-invalid .wc-bogo-label::after {
    content: " *";
    color: #d63638;
    font-weight: bold;
}

/* Rule preview styles */
#rule-preview-content {
    font-size: 13px;
    line-height: 1.5;
}

#rule-preview-content .rule-summary {
    background: #f0f6fc;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 12px;
    margin: 8px 0;
}

#rule-preview-content .rule-summary strong {
    color: #1d2327;
}

#rule-preview-content .rule-dates {
    margin-top: 8px;
    font-style: italic;
    color: #646970;
}
</style>

<script>
jQuery(document).ready(function($) {
    
    // Initialize product search
    initializeProductSearch();
    
    // Form validation
    $('#bogo-rule-form').on('submit', function(e) {
        var isValid = true;
        var requiredFields = ['title', 'buy_product', 'buy_qty', 'get_product', 'get_qty', 'discount'];
        
        // Remove previous validation classes
        $('.form-invalid').removeClass('form-invalid');
        
        // Validate required fields
        requiredFields.forEach(function(field) {
            var $field = $('[name="' + field + '"]');
            var value = $field.val();
            
            if (!value || value === '' || value === '0') {
                $field.closest('.wc-bogo-field').addClass('form-invalid');
                isValid = false;
            }
        });
        
        // Validate numeric fields
        var buyQty = parseInt($('#buy_qty').val());
        var getQty = parseInt($('#get_qty').val());
        var discount = parseInt($('#discount').val());
        
        if (buyQty < 1) {
            $('#buy_qty').closest('.wc-bogo-field').addClass('form-invalid');
            isValid = false;
        }
        
        if (getQty < 1) {
            $('#get_qty').closest('.wc-bogo-field').addClass('form-invalid');
            isValid = false;
        }
        
        if (discount < 0 || discount > 100) {
            $('#discount').closest('.wc-bogo-field').addClass('form-invalid');
            isValid = false;
        }
        
        // Validate date range
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();
        
        if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
            $('#end_date').closest('.wc-bogo-field').addClass('form-invalid');
            alert('<?php _e( 'End date must be after start date.', 'wc-advanced-bogo' ); ?>');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $('.form-invalid').first().offset().top - 100
            }, 500);
            return false;
        }
        
        // Show loading state
        $('#publish').prop('disabled', true).val('<?php _e( 'Saving...', 'wc-advanced-bogo' ); ?>');
        $('.spinner').addClass('is-active');
    });
    
    // Update rule preview when fields change
    function updateRulePreview() {
        var buyProduct = $('#buy_product option:selected').text();
        var buyQty = $('#buy_qty').val();
        var getProduct = $('#get_product option:selected').text();
        var getQty = $('#get_qty').val();
        var discount = $('#discount').val();
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();
        
        if (buyProduct && buyQty && getProduct && getQty && discount) {
            var summary = '<div class="rule-summary">';
            summary += '<strong><?php _e( 'Rule Summary:', 'wc-advanced-bogo' ); ?></strong><br>';
            summary += '<?php _e( 'Buy', 'wc-advanced-bogo' ); ?> <strong>' + buyQty + '</strong> × <strong>' + buyProduct + '</strong><br>';
            summary += '<?php _e( 'Get', 'wc-advanced-bogo' ); ?> <strong>' + getQty + '</strong> × <strong>' + getProduct + '</strong><br>';
            summary += '<?php _e( 'At', 'wc-advanced-bogo' ); ?> <strong>' + discount + '%</strong> <?php _e( 'discount', 'wc-advanced-bogo' ); ?>';
            
            if (startDate || endDate) {
                summary += '<div class="rule-dates">';
                if (startDate && endDate) {
                    summary += '<?php _e( 'Active from', 'wc-advanced-bogo' ); ?> ' + startDate + ' <?php _e( 'to', 'wc-advanced-bogo' ); ?> ' + endDate;
                } else if (startDate) {
                    summary += '<?php _e( 'Active from', 'wc-advanced-bogo' ); ?> ' + startDate;
                } else if (endDate) {
                    summary += '<?php _e( 'Active until', 'wc-advanced-bogo' ); ?> ' + endDate;
                }
                summary += '</div>';
            }
            
            summary += '</div>';
            $('#rule-preview-content').html(summary);
        } else {
            $('#rule-preview-content').html('<p class="description"><?php _e( 'Fill in the rule details to see a preview', 'wc-advanced-bogo' ); ?></p>');
        }
    }
    
    // Update preview when fields change
    $('#buy_product, #buy_qty, #get_product, #get_qty, #discount, #start_date, #end_date').on('change', updateRulePreview);
    
    // Initialize preview
    setTimeout(updateRulePreview, 1000);
    
    // Auto-generate title if empty
    $('#buy_product, #buy_qty, #get_product, #get_qty, #discount').on('change', function() {
        if ($('#title').val() === '') {
            var buyProduct = $('#buy_product option:selected').text();
            var buyQty = $('#buy_qty').val();
            var getProduct = $('#get_product option:selected').text();
            var getQty = $('#get_qty').val();
            var discount = $('#discount').val();
            
            if (buyProduct && buyQty && getProduct && getQty && discount) {
                var title = 'Buy ' + buyQty + ' ' + buyProduct + ', Get ' + getQty + ' ' + getProduct + ' at ' + discount + '% off';
                $('#title').val(title);
            }
        }
    });
    
    function initializeProductSearch() {
        $('.wc-product-search').each(function() {
            var $select = $(this);
            
            if ($select.hasClass('select2-hidden-accessible')) {
                return;
            }
            
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
                allowClear: true
            });
        });
    }
});
</script>