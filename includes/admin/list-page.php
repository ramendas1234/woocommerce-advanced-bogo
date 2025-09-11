<?php
/**
 * BOGO Rules List Page
 * 
 * @package WC_Advanced_BOGO
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Display admin notices
if ( isset( $_GET['deleted'] ) ) {
    $count = intval( $_GET['deleted'] );
    echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( _n( '%d rule deleted.', '%d rules deleted.', $count ), $count ) . '</p></div>';
}

if ( isset( $_GET['enabled'] ) ) {
    $count = intval( $_GET['enabled'] );
    echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( _n( '%d rule enabled.', '%d rules enabled.', $count ), $count ) . '</p></div>';
}

if ( isset( $_GET['disabled'] ) ) {
    $count = intval( $_GET['disabled'] );
    echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( _n( '%d rule disabled.', '%d rules disabled.', $count ), $count ) . '</p></div>';
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e( 'BOGO Rules', 'wc-advanced-bogo' ); ?>
    </h1>
    
    <a href="<?php echo admin_url( 'admin.php?page=wc-advanced-bogo-edit' ); ?>" class="page-title-action">
        <?php _e( 'Add New Rule', 'wc-advanced-bogo' ); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <?php if ( empty( $rules ) ) : ?>
        <div class="no-items">
            <p><?php _e( 'No BOGO rules found.', 'wc-advanced-bogo' ); ?></p>
            <p>
                <a href="<?php echo admin_url( 'admin.php?page=wc-advanced-bogo-edit' ); ?>" class="button button-primary">
                    <?php _e( 'Create your first BOGO rule', 'wc-advanced-bogo' ); ?>
                </a>
            </p>
        </div>
    <?php else : ?>
        <form method="post" id="bogo-rules-filter">
            <?php wp_nonce_field( 'bulk_action', 'bulk_action_nonce' ); ?>
            
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select name="action" id="bulk-action-selector-top">
                        <option value="-1"><?php _e( 'Bulk Actions', 'wc-advanced-bogo' ); ?></option>
                        <option value="enable"><?php _e( 'Enable', 'wc-advanced-bogo' ); ?></option>
                        <option value="disable"><?php _e( 'Disable', 'wc-advanced-bogo' ); ?></option>
                        <option value="delete"><?php _e( 'Delete', 'wc-advanced-bogo' ); ?></option>
                    </select>
                    <input type="submit" id="doaction" class="button action" value="<?php _e( 'Apply', 'wc-advanced-bogo' ); ?>">
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th scope="col" class="manage-column column-title column-primary">
                            <?php _e( 'Title', 'wc-advanced-bogo' ); ?>
                        </th>
                        <th scope="col" class="manage-column column-rule">
                            <?php _e( 'Rule Details', 'wc-advanced-bogo' ); ?>
                        </th>
                        <th scope="col" class="manage-column column-status">
                            <?php _e( 'Status', 'wc-advanced-bogo' ); ?>
                        </th>
                        <th scope="col" class="manage-column column-date">
                            <?php _e( 'Date', 'wc-advanced-bogo' ); ?>
                        </th>
                    </tr>
                </thead>
                
                <tbody id="the-list">
                    <?php foreach ( $rules as $rule ) : 
                        // Ensure rule object has required properties
                        if ( ! $rule || ! isset( $rule->id ) ) continue;
                        
                        $rule_id = intval( $rule->id );
                        $rule_title = ! empty( $rule->title ) ? $rule->title : __( 'Untitled Rule', 'wc-advanced-bogo' );
                        $rule_enabled = ! empty( $rule->enabled );
                    ?>
                        <tr id="post-<?php echo esc_attr( $rule_id ); ?>" class="iedit author-self level-0 post-<?php echo esc_attr( $rule_id ); ?> type-rule status-<?php echo $rule_enabled ? 'enabled' : 'disabled'; ?>">
                            <th scope="row" class="check-column">
                                <input id="cb-select-<?php echo esc_attr( $rule_id ); ?>" type="checkbox" name="rule_ids[]" value="<?php echo esc_attr( $rule_id ); ?>">
                            </th>
                            
                            <td class="title column-title has-row-actions column-primary">
                                <strong>
                                    <a class="row-title" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-advanced-bogo-edit&rule_id=' . $rule_id ) ); ?>">
                                        <?php echo esc_html( $rule_title ); ?>
                                    </a>
                                    <?php if ( ! $rule_enabled ) : ?>
                                        <span class="post-state"> — <?php _e( 'Disabled', 'wc-advanced-bogo' ); ?></span>
                                    <?php endif; ?>
                                </strong>
                                
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-advanced-bogo-edit&rule_id=' . $rule_id ) ); ?>">
                                            <?php _e( 'Edit', 'wc-advanced-bogo' ); ?>
                                        </a> |
                                    </span>
                                    
                                    <span class="toggle">
                                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wc-advanced-bogo&action=toggle&rule_id=' . $rule_id ), 'bogo_action_' . $rule_id ) ); ?>">
                                            <?php echo $rule_enabled ? __( 'Disable', 'wc-advanced-bogo' ) : __( 'Enable', 'wc-advanced-bogo' ); ?>
                                        </a> |
                                    </span>
                                    
                                    <span class="trash">
                                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wc-advanced-bogo&action=delete&rule_id=' . $rule_id ), 'bogo_action_' . $rule_id ) ); ?>" 
                                           class="submitdelete" 
                                           onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this rule?', 'wc-advanced-bogo' ); ?>')">
                                            <?php _e( 'Delete', 'wc-advanced-bogo' ); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            
                            <td class="column-rule">
                                <?php
                                $buy_product_name = 'All Products';
                                $buy_product = ! empty( $rule->buy_product ) ? $rule->buy_product : '';
                                if ( $buy_product && $buy_product !== 'all' ) {
                                    $product = wc_get_product( $buy_product );
                                    $buy_product_name = $product ? $product->get_name() : 'Product #' . $buy_product;
                                }
                                
                                $get_product_name = 'Product';
                                $get_product = ! empty( $rule->get_product ) ? intval( $rule->get_product ) : 0;
                                if ( $get_product ) {
                                    $product = wc_get_product( $get_product );
                                    $get_product_name = $product ? $product->get_name() : 'Product #' . $get_product;
                                }
                                
                                $buy_qty = ! empty( $rule->buy_qty ) ? intval( $rule->buy_qty ) : 1;
                                $get_qty = ! empty( $rule->get_qty ) ? intval( $rule->get_qty ) : 1;
                                $discount = ! empty( $rule->discount ) ? intval( $rule->discount ) : 0;
                                
                                printf(
                                    __( 'Buy %d × %s<br>Get %d × %s at %d%% off', 'wc-advanced-bogo' ),
                                    $buy_qty,
                                    esc_html( $buy_product_name ),
                                    $get_qty,
                                    esc_html( $get_product_name ),
                                    $discount
                                );
                                
                                $start_date = ! empty( $rule->start_date ) ? $rule->start_date : '';
                                $end_date = ! empty( $rule->end_date ) ? $rule->end_date : '';
                                
                                if ( $start_date || $end_date ) {
                                    echo '<br><small>';
                                    if ( $start_date && $end_date ) {
                                        printf( __( 'Active: %s to %s', 'wc-advanced-bogo' ), 
                                            date_i18n( get_option( 'date_format' ), strtotime( $start_date ) ),
                                            date_i18n( get_option( 'date_format' ), strtotime( $end_date ) )
                                        );
                                    } elseif ( $start_date ) {
                                        printf( __( 'Active from: %s', 'wc-advanced-bogo' ), 
                                            date_i18n( get_option( 'date_format' ), strtotime( $start_date ) )
                                        );
                                    } elseif ( $end_date ) {
                                        printf( __( 'Active until: %s', 'wc-advanced-bogo' ), 
                                            date_i18n( get_option( 'date_format' ), strtotime( $end_date ) )
                                        );
                                    }
                                    echo '</small>';
                                }
                                ?>
                            </td>
                            
                            <td class="column-status">
                                <?php if ( $rule_enabled ) : ?>
                                    <span class="status-enabled">
                                        <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                        <?php _e( 'Enabled', 'wc-advanced-bogo' ); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="status-disabled">
                                        <span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span>
                                        <?php _e( 'Disabled', 'wc-advanced-bogo' ); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="column-date">
                                <?php 
                                $date_created = ! empty( $rule->date_created ) ? $rule->date_created : current_time( 'mysql' );
                                $date_modified = ! empty( $rule->date_modified ) ? $rule->date_modified : $date_created;
                                ?>
                                <abbr title="<?php echo esc_attr( $date_created ); ?>">
                                    <?php echo date_i18n( get_option( 'date_format' ), strtotime( $date_created ) ); ?>
                                </abbr>
                                <br>
                                <?php if ( $date_modified !== $date_created ) : ?>
                                    <small>
                                        <?php printf( __( 'Modified: %s', 'wc-advanced-bogo' ), 
                                            date_i18n( get_option( 'date_format' ), strtotime( $date_modified ) ) 
                                        ); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                
                <tfoot>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input id="cb-select-all-2" type="checkbox">
                        </td>
                        <th scope="col" class="manage-column column-title column-primary">
                            <?php _e( 'Title', 'wc-advanced-bogo' ); ?>
                        </th>
                        <th scope="col" class="manage-column column-rule">
                            <?php _e( 'Rule Details', 'wc-advanced-bogo' ); ?>
                        </th>
                        <th scope="col" class="manage-column column-status">
                            <?php _e( 'Status', 'wc-advanced-bogo' ); ?>
                        </th>
                        <th scope="col" class="manage-column column-date">
                            <?php _e( 'Date', 'wc-advanced-bogo' ); ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
            
            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <select name="action2" id="bulk-action-selector-bottom">
                        <option value="-1"><?php _e( 'Bulk Actions', 'wc-advanced-bogo' ); ?></option>
                        <option value="enable"><?php _e( 'Enable', 'wc-advanced-bogo' ); ?></option>
                        <option value="disable"><?php _e( 'Disable', 'wc-advanced-bogo' ); ?></option>
                        <option value="delete"><?php _e( 'Delete', 'wc-advanced-bogo' ); ?></option>
                    </select>
                    <input type="submit" id="doaction2" class="button action" value="<?php _e( 'Apply', 'wc-advanced-bogo' ); ?>">
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<style>
.wp-list-table .column-cb { width: 2.2em; }
.wp-list-table .column-title { width: 25%; }
.wp-list-table .column-rule { width: 35%; }
.wp-list-table .column-status { width: 12%; }
.wp-list-table .column-date { width: 15%; }

.status-enabled, .status-disabled {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.no-items {
    text-align: center;
    padding: 60px 0;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin-top: 20px;
}

.no-items p {
    font-size: 18px;
    color: #646970;
    margin-bottom: 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle select all checkboxes
    $('#cb-select-all-1, #cb-select-all-2').on('click', function() {
        var isChecked = $(this).prop('checked');
        $('input[name="rule_ids[]"]').prop('checked', isChecked);
    });
    
    // Sync bulk action selectors
    $('#bulk-action-selector-top').on('change', function() {
        $('#bulk-action-selector-bottom').val($(this).val());
    });
    
    $('#bulk-action-selector-bottom').on('change', function() {
        $('#bulk-action-selector-top').val($(this).val());
    });
    
    // Handle bulk actions
    $('#doaction, #doaction2').on('click', function(e) {
        var action = $(this).attr('id') === 'doaction' ? 
            $('#bulk-action-selector-top').val() : 
            $('#bulk-action-selector-bottom').val();
            
        if (action === '-1') {
            e.preventDefault();
            alert('Please select an action.');
            return false;
        }
        
        var checkedBoxes = $('input[name="rule_ids[]"]:checked');
        if (checkedBoxes.length === 0) {
            e.preventDefault();
            alert('Please select at least one rule.');
            return false;
        }
        
        if (action === 'delete') {
            if (!confirm('Are you sure you want to delete the selected rules?')) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>