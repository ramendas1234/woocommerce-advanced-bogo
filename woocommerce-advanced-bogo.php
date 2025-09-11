<?php
/**
 * Plugin Name: WooCommerce Advanced BOGO
 * Description: Adds advanced BOGO (Buy One Get One) functionality to WooCommerce.
 * Version: 1.0.1
 * Author: StoreApps
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Advanced_BOGO {

    const OPTION_KEY = 'wc_advanced_bogo_rules';
    const TEMPLATE_OPTION_KEY = 'wc_advanced_bogo_template';
    const TABLE_NAME = 'wc_advanced_bogo_rules';

    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'wp_ajax_grab_bogo_offer', array( $this, 'handle_grab_bogo_offer' ) );
        add_action( 'wp_ajax_nopriv_grab_bogo_offer', array( $this, 'handle_grab_bogo_offer' ) );
        add_action( 'wp_ajax_get_bogo_hints', array( $this, 'get_bogo_hints' ) );
        add_action( 'wp_ajax_nopriv_get_bogo_hints', array( $this, 'get_bogo_hints' ) );
        add_action( 'wp_ajax_save_individual_bogo_rule', array( $this, 'handle_save_individual_bogo_rule' ) );

        // Database installation
        register_activation_hook( __FILE__, array( $this, 'create_database_table' ) );
        
        // Migration from options to database
        add_action( 'admin_init', array( $this, 'maybe_migrate_data' ) );
    }

	/**
	 * Initialize the plugin
	 */
	public function init() {
		// Only load if WooCommerce is active
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
			add_action( 'admin_init', [ $this, 'register_settings' ] );
			add_action( 'woocommerce_single_product_summary', [ $this, 'display_bogo_message' ], 25 );
			add_action( 'woocommerce_before_calculate_totals', [ $this, 'apply_bogo_discount' ], 10, 1 );
			add_filter( 'woocommerce_cart_item_remove_link', [ $this, 'maybe_remove_remove_link' ], 10, 2 );
			
			// Add BOGO hints inside cart line items (classic cart only)
			add_action( 'woocommerce_after_cart_item_name', [ $this, 'display_cart_item_bogo_hint' ], 10, 2 );
			
			// Add BOGO hints inside checkout cart items
			add_filter( 'woocommerce_checkout_cart_item_quantity', [ $this, 'display_checkout_item_bogo_hint' ], 10, 3 );
			
			// Track BOGO discounts in orders
			add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'save_bogo_order_item_meta' ], 10, 4 );
			add_action( 'woocommerce_checkout_order_processed', [ $this, 'save_bogo_order_meta' ], 10, 3 );
		}
	}

	/**
	 * Get BOGO rules for JavaScript
	 */
	private function get_bogo_rules_for_js() {
		$rules = $this->get_rules( [ 'enabled' => 1 ] );
		$now = date( 'Y-m-d' );
		$active_rules = array();
		
		foreach ( $rules as $index => $rule ) {
			if ( empty( $rule->get_product ) || empty( $rule->buy_qty ) ) {
				continue;
			}

			if ( !empty( $rule->start_date ) && $rule->start_date > $now ) continue;
			if ( !empty( $rule->end_date ) && $rule->end_date < $now ) continue;

			$active_rules[] = array(
				'index' => $rule->id,
				'buy_product' => $rule->buy_product,
				'buy_qty' => intval( $rule->buy_qty ),
				'get_product' => intval( $rule->get_product ),
				'get_qty' => intval( $rule->get_qty ) ?: 1,
				'discount' => intval( $rule->discount )
			);
		}
		
		return $active_rules;
	}

	public function enqueue_assets() {
		// Only load on product pages, cart, and checkout
		if ( is_product() || is_cart() || is_checkout() ) {
			// Enqueue local Tailwind CSS
			wp_enqueue_style(
				'wc-advanced-bogo-tailwind',
				plugin_dir_url( __FILE__ ) . 'assets/css/tailwind.min.css',
				array(),
				'2.2.19'
			);

			// Add custom CSS for spinner animation and BOGO styling
			wp_add_inline_style( 'wc-advanced-bogo-tailwind', '
				@keyframes spin {
					0% { transform: rotate(0deg); }
					100% { transform: rotate(360deg); }
				}
				
				/* BOGO Template Styling */
				.bogo-offer-container {
					margin: 1rem 0;
					padding: 1rem;
					border-radius: 0.5rem;
					box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
					transition: all 0.3s ease;
					border: 1px solid #e5e7eb;
				}
				
				.bogo-offer-container:hover {
					transform: translateY(-2px);
					box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
				}
				
				.bogo-offer-button {
					display: inline-block;
					padding: 0.5rem 1rem;
					border-radius: 0.375rem;
					font-weight: 600;
					text-decoration: none;
					transition: all 0.2s ease;
					cursor: pointer;
					border: none;
				}
				
				.bogo-offer-button:hover {
					transform: scale(1.05);
				}
			' );

			// Enqueue frontend JavaScript
			wp_enqueue_script(
				'wc-advanced-bogo-frontend',
				plugin_dir_url( __FILE__ ) . 'frontend.js',
				array( 'jquery' ),
				'1.0.0',
				true
			);

			// Localize script with AJAX data
			wp_localize_script( 'wc-advanced-bogo-frontend', 'wc_advanced_bogo_ajax', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'wc_advanced_bogo_nonce' ),
				'cartUrl' => wc_get_cart_url()
			) );
		}
	}

	public function enqueue_admin_scripts( $hook ) {
		// Only load on our BOGO settings page
		if ( $hook === 'woocommerce_page_wc-advanced-bogo' || $hook === 'toplevel_page_wc-advanced-bogo' || $hook === 'admin_page_wc-advanced-bogo-edit' ) {
			// Enqueue WordPress admin scripts first
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			
			// Enqueue WooCommerce admin scripts
			wp_enqueue_script( 'woocommerce_admin' );
			wp_enqueue_script( 'wc-enhanced-select' );
			wp_enqueue_style( 'woocommerce_admin_styles' );
			
			// Enqueue our admin script after WooCommerce scripts
			wp_enqueue_script( 
				'wc-advanced-bogo-admin', 
				plugin_dir_url(__FILE__) . 'admin.js', 
				['jquery', 'woocommerce_admin', 'wc-enhanced-select'], 
				filemtime( plugin_dir_path(__FILE__) . 'admin.js' ), 
				true 
			);
			
			// Localize our script with all necessary parameters
			wp_localize_script( 'wc-advanced-bogo-admin', 'bogo_admin', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'search-products' ),
				'search_products_nonce' => wp_create_nonce( 'search-products' ),
				'save_individual_rule_nonce' => wp_create_nonce( 'save_individual_bogo_rule' )
			) );
			
			// Add inline script to ensure ajaxurl is available globally
			wp_add_inline_script( 'wc-advanced-bogo-admin', 'var ajaxurl = "' . admin_url( 'admin-ajax.php' ) . '";', 'before' );
			
			// Enqueue admin CSS file
			wp_enqueue_style(
				'wc-advanced-bogo-admin-css',
				plugin_dir_url( __FILE__ ) . 'assets/css/admin.css',
				array(),
				'1.0.1'
			);
		}
	}

    /**
     * Create database table for BOGO rules
     */
    public function create_database_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL DEFAULT '',
            enabled tinyint(1) NOT NULL DEFAULT 1,
            buy_product varchar(255) NOT NULL DEFAULT '',
            buy_qty int(11) NOT NULL DEFAULT 1,
            get_product bigint(20) unsigned NOT NULL DEFAULT 0,
            get_qty int(11) NOT NULL DEFAULT 1,
            discount int(11) NOT NULL DEFAULT 0,
            start_date date DEFAULT NULL,
            end_date date DEFAULT NULL,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY enabled (enabled),
            KEY buy_product (buy_product),
            KEY get_product (get_product),
            KEY date_range (start_date, end_date)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        
        // Mark table as created
        update_option( 'wc_advanced_bogo_db_version', '1.0' );
    }

    /**
     * Maybe migrate data from options to database table
     */
    public function maybe_migrate_data() {
        // Check if migration is needed
        if ( get_option( 'wc_advanced_bogo_migrated' ) ) {
            return;
        }
        
        // Get old rules from options
        $old_rules = get_option( self::OPTION_KEY, [] );
        
        if ( empty( $old_rules ) ) {
            update_option( 'wc_advanced_bogo_migrated', true );
            return;
        }
        
        // Migrate each rule to database
        foreach ( $old_rules as $rule ) {
            $this->save_rule( [
                'title' => $this->generate_rule_title( $rule ),
                'enabled' => 1,
                'buy_product' => $rule['buy_product'] ?? '',
                'buy_qty' => intval( $rule['buy_qty'] ?? 1 ),
                'get_product' => intval( $rule['get_product'] ?? 0 ),
                'get_qty' => intval( $rule['get_qty'] ?? 1 ),
                'discount' => intval( $rule['discount'] ?? 0 ),
                'start_date' => !empty( $rule['start_date'] ) ? $rule['start_date'] : null,
                'end_date' => !empty( $rule['end_date'] ) ? $rule['end_date'] : null,
            ] );
        }
        
        // Mark migration as complete
        update_option( 'wc_advanced_bogo_migrated', true );
    }

    /**
     * Generate rule title from rule data
     */
    private function generate_rule_title( $rule ) {
        $buy_product_name = 'All Products';
        if ( !empty( $rule['buy_product'] ) && $rule['buy_product'] !== 'all' ) {
            $product = wc_get_product( $rule['buy_product'] );
            $buy_product_name = $product ? $product->get_name() : 'Product #' . $rule['buy_product'];
        }
        
        $get_product_name = 'Product';
        if ( !empty( $rule['get_product'] ) ) {
            $product = wc_get_product( $rule['get_product'] );
            $get_product_name = $product ? $product->get_name() : 'Product #' . $rule['get_product'];
        }
        
        return sprintf(
            'Buy %d %s, Get %d %s at %d%% off',
            $rule['buy_qty'] ?? 1,
            $buy_product_name,
            $rule['get_qty'] ?? 1,
            $get_product_name,
            $rule['discount'] ?? 0
        );
    }

    public function add_admin_menu() {
        // Main menu page (list view)
        add_submenu_page(
            'woocommerce',
            'Advanced BOGO',
            'Advanced BOGO',
            'manage_woocommerce',
            'wc-advanced-bogo',
            [ $this, 'list_page' ]
        );
        
        // Add/Edit rule page (hidden from menu)
        add_submenu_page(
            null, // Hidden from menu
            'Add BOGO Rule',
            'Add BOGO Rule',
            'manage_woocommerce',
            'wc-advanced-bogo-edit',
            [ $this, 'edit_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'wc_advanced_bogo', self::TEMPLATE_OPTION_KEY );
    }

    /**
     * Database methods for BOGO rules
     */
    
    /**
     * Get all rules from database
     */
    public function get_rules( $args = [] ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $defaults = [
            'enabled' => null,
            'orderby' => 'date_created',
            'order' => 'DESC',
            'limit' => -1,
            'offset' => 0
        ];
        
        $args = wp_parse_args( $args, $defaults );
        
        $where = [];
        if ( $args['enabled'] !== null ) {
            $where[] = $wpdb->prepare( 'enabled = %d', $args['enabled'] );
        }
        
        $where_clause = empty( $where ) ? '' : 'WHERE ' . implode( ' AND ', $where );
        
        $order_clause = sprintf( 'ORDER BY %s %s', 
            sanitize_sql_orderby( $args['orderby'] ), 
            strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC' 
        );
        
        $limit_clause = '';
        if ( $args['limit'] > 0 ) {
            $limit_clause = $wpdb->prepare( 'LIMIT %d OFFSET %d', $args['limit'], $args['offset'] );
        }
        
        $sql = "SELECT * FROM $table_name $where_clause $order_clause $limit_clause";
        
        return $wpdb->get_results( $sql );
    }
    
    /**
     * Get single rule by ID
     */
    public function get_rule( $id ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );
    }
    
    /**
     * Save rule to database
     */
    public function save_rule( $data, $id = 0 ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $defaults = [
            'title' => '',
            'enabled' => 1,
            'buy_product' => '',
            'buy_qty' => 1,
            'get_product' => 0,
            'get_qty' => 1,
            'discount' => 0,
            'start_date' => null,
            'end_date' => null
        ];
        
        $data = wp_parse_args( $data, $defaults );
        
        // Sanitize data
        $data['title'] = sanitize_text_field( $data['title'] );
        $data['enabled'] = intval( $data['enabled'] );
        $data['buy_product'] = sanitize_text_field( $data['buy_product'] );
        $data['buy_qty'] = intval( $data['buy_qty'] );
        $data['get_product'] = intval( $data['get_product'] );
        $data['get_qty'] = intval( $data['get_qty'] );
        $data['discount'] = intval( $data['discount'] );
        $data['start_date'] = !empty( $data['start_date'] ) ? sanitize_text_field( $data['start_date'] ) : null;
        $data['end_date'] = !empty( $data['end_date'] ) ? sanitize_text_field( $data['end_date'] ) : null;
        
        if ( $id > 0 ) {
            // Update existing rule
            $result = $wpdb->update( $table_name, $data, [ 'id' => $id ] );
            return $result !== false ? $id : false;
        } else {
            // Insert new rule
            $result = $wpdb->insert( $table_name, $data );
            return $result !== false ? $wpdb->insert_id : false;
        }
    }
    
    /**
     * Delete rule from database
     */
    public function delete_rule( $id ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        return $wpdb->delete( $table_name, [ 'id' => $id ] );
    }

    /**
     * List page for BOGO rules
     */
    public function list_page() {
        // Handle bulk actions
        if ( isset( $_POST['action'] ) && $_POST['action'] !== '-1' ) {
            $this->handle_bulk_actions();
        }
        
        // Handle individual actions
        if ( isset( $_GET['action'] ) ) {
            $this->handle_individual_actions();
        }
        
        $rules = $this->get_rules();
        
        include_once plugin_dir_path( __FILE__ ) . 'includes/admin/list-page.php';
    }
    
    /**
     * Edit page for BOGO rules
     */
    public function edit_page() {
        $rule_id = isset( $_GET['rule_id'] ) ? intval( $_GET['rule_id'] ) : 0;
        $rule = $rule_id > 0 ? $this->get_rule( $rule_id ) : null;
        
        // Handle form submission
        if ( isset( $_POST['save_rule'] ) ) {
            $this->handle_save_rule();
            return;
        }
        
        include_once plugin_dir_path( __FILE__ ) . 'includes/admin/edit-page.php';
    }
    
    /**
     * Handle bulk actions
     */
    private function handle_bulk_actions() {
        if ( ! isset( $_POST['bulk_action_nonce'] ) || ! wp_verify_nonce( $_POST['bulk_action_nonce'], 'bulk_action' ) ) {
            return;
        }
        
        $action = sanitize_text_field( $_POST['action'] );
        $rule_ids = isset( $_POST['rule_ids'] ) ? array_map( 'intval', $_POST['rule_ids'] ) : [];
        
        if ( empty( $rule_ids ) ) {
            return;
        }
        
        switch ( $action ) {
            case 'delete':
                foreach ( $rule_ids as $rule_id ) {
                    $this->delete_rule( $rule_id );
                }
                wp_redirect( add_query_arg( 'deleted', count( $rule_ids ), admin_url( 'admin.php?page=wc-advanced-bogo' ) ) );
                exit;
                break;
                
            case 'enable':
                foreach ( $rule_ids as $rule_id ) {
                    $this->save_rule( [ 'enabled' => 1 ], $rule_id );
                }
                wp_redirect( add_query_arg( 'enabled', count( $rule_ids ), admin_url( 'admin.php?page=wc-advanced-bogo' ) ) );
                exit;
                break;
                
            case 'disable':
                foreach ( $rule_ids as $rule_id ) {
                    $this->save_rule( [ 'enabled' => 0 ], $rule_id );
                }
                wp_redirect( add_query_arg( 'disabled', count( $rule_ids ), admin_url( 'admin.php?page=wc-advanced-bogo' ) ) );
                exit;
                break;
        }
    }
    
    /**
     * Handle individual actions
     */
    private function handle_individual_actions() {
        $action = sanitize_text_field( $_GET['action'] );
        $rule_id = isset( $_GET['rule_id'] ) ? intval( $_GET['rule_id'] ) : 0;
        
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'bogo_action_' . $rule_id ) ) {
            return;
        }
        
        switch ( $action ) {
            case 'delete':
                $this->delete_rule( $rule_id );
                wp_redirect( add_query_arg( 'deleted', 1, admin_url( 'admin.php?page=wc-advanced-bogo' ) ) );
                exit;
                break;
                
            case 'toggle':
                $rule = $this->get_rule( $rule_id );
                if ( $rule ) {
                    $this->save_rule( [ 'enabled' => $rule->enabled ? 0 : 1 ], $rule_id );
                }
                wp_redirect( admin_url( 'admin.php?page=wc-advanced-bogo' ) );
                exit;
                break;
        }
    }
    
    /**
     * Handle save rule form submission
     */
    private function handle_save_rule() {
        if ( ! isset( $_POST['bogo_rule_nonce'] ) || ! wp_verify_nonce( $_POST['bogo_rule_nonce'], 'save_bogo_rule' ) ) {
            wp_die( 'Security check failed' );
        }
        
        $rule_id = isset( $_POST['rule_id'] ) ? intval( $_POST['rule_id'] ) : 0;
        
        $data = [
            'title' => sanitize_text_field( $_POST['title'] ),
            'enabled' => isset( $_POST['enabled'] ) ? 1 : 0,
            'buy_product' => sanitize_text_field( $_POST['buy_product'] ),
            'buy_qty' => intval( $_POST['buy_qty'] ),
            'get_product' => intval( $_POST['get_product'] ),
            'get_qty' => intval( $_POST['get_qty'] ),
            'discount' => intval( $_POST['discount'] ),
            'start_date' => !empty( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : null,
            'end_date' => !empty( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : null,
        ];
        
        $saved_id = $this->save_rule( $data, $rule_id );
        
        if ( $saved_id ) {
            $redirect_url = add_query_arg( [
                'page' => 'wc-advanced-bogo-edit',
                'rule_id' => $saved_id,
                'updated' => 1
            ], admin_url( 'admin.php' ) );
        } else {
            $redirect_url = add_query_arg( [
                'page' => 'wc-advanced-bogo-edit',
                'rule_id' => $rule_id,
                'error' => 1
            ], admin_url( 'admin.php' ) );
        }
        
        wp_redirect( $redirect_url );
        exit;
    }

	public function display_bogo_message() {
		global $product;

		$rules = $this->get_rules( [ 'enabled' => 1 ] );
		$template_settings = get_option( self::TEMPLATE_OPTION_KEY, [] );
		
		// Ensure template_settings is an array (handle old string data)
		if ( !is_array( $template_settings ) ) {
			$template_settings = array();
		}
		
		$now = date( 'Y-m-d' );
		
		foreach ( $rules as $index => $rule ) {
			if ( ! empty( $rule->buy_product ) && ( $rule->buy_product === 'all' || intval( $rule->buy_product ) === $product->get_id() ) ) {
				$buy_qty     = intval( $rule->buy_qty );
				$get_qty     = intval( $rule->get_qty ) ?: 1;
				$get_product = wc_get_product( intval( $rule->get_product ) );
				$discount    = intval( $rule->discount );

				if ( !empty( $rule->start_date ) && $rule->start_date > $now ) continue;
            	if ( !empty( $rule->end_date ) && $rule->end_date < $now ) continue;

				if ( $get_product ) {
					$discount_text = ( $discount == 100 )
						? 'for free!'
						: "at {$discount}% off!";

					$get_image = $get_product->get_image( 'thumbnail' );
					$get_name  = $get_product->get_name();
					$current_product_id = $product->get_id();
					$buy_product_id = $rule->buy_product === 'all' ? $current_product_id : intval( $rule->buy_product );

					// Get the selected template (default to template1)
					$selected_template = isset( $template_settings['selected_template'] ) ? $template_settings['selected_template'] : 1;
					
					// Generate template based on selection
					echo '<div class="bogo-template-wrapper" data-product-id="' . $product->get_id() . '" data-rule-index="' . $rule->id . '">';
					echo $this->get_bogo_template( 
						$selected_template, 
						$buy_qty, 
						$get_qty, 
						$get_name, 
						$discount_text, 
						$get_image, 
						$buy_product_id, 
						$get_product->get_id(), 
						$discount, 
						$rule->id 
					);
					echo '</div>';
				}
			}
		}
	}

	private function get_bogo_template( $template, $buy_qty, $get_qty, $get_name, $discount_text, $get_image, $buy_product_id, $get_product_id, $discount, $index ) {
		// Get template settings
		$template_settings = get_option( self::TEMPLATE_OPTION_KEY, [] );
		
		// Ensure template_settings is an array (handle old string data)
		if ( !is_array( $template_settings ) ) {
			$template_settings = array();
		}
		
		$template_key = "template{$template}";
		
		// Simplified default colors for each template - Only 3 colors needed
		$default_colors = array(
			'template1' => array(
				'theme_color' => '#3B82F6',
				'background' => '#F8FAFC',
				'button_color' => '#3B82F6'
			),
			'template2' => array(
				'theme_color' => '#8B5CF6',
				'background' => '#1E1B4B',
				'button_color' => '#EC4899'
			),
			'template3' => array(
				'theme_color' => '#F59E0B',
				'background' => '#7C2D12',
				'button_color' => '#F59E0B'
			)
		);
		
		// Get saved colors or use defaults
		$saved_colors = isset( $template_settings[$template_key] ) && is_array( $template_settings[$template_key] ) ? $template_settings[$template_key] : array();
		$theme_color = isset( $saved_colors['theme_color'] ) ? $saved_colors['theme_color'] : $default_colors[$template_key]['theme_color'];
		$background_color = isset( $saved_colors['background'] ) ? $saved_colors['background'] : $default_colors[$template_key]['background'];
		$button_color = isset( $saved_colors['button_color'] ) ? $saved_colors['button_color'] : $default_colors[$template_key]['button_color'];

		// Auto-calculate all other colors for backward compatibility
		$colors = array(
			'theme_color' => $theme_color,
			'primary' => $theme_color, // For backward compatibility
			'secondary' => $this->adjust_color_brightness($theme_color, -20), // Slightly darker version
			'text' => $this->get_contrasting_color($background_color),
			'background' => $background_color,
			'button_bg' => $button_color, // For backward compatibility
			'button_text' => $this->get_contrasting_color($button_color),
			'button_color' => $button_color
		);
		
		// Common button data for AJAX
		$common_button_data = sprintf(
			'data-buy-product="%s" data-buy-qty="%d" data-get-product="%d" data-get-qty="%d" data-discount="%d" data-rule-index="%d"',
			esc_attr( $buy_product_id ),
			$buy_qty,
			$get_product_id,
			$get_qty,
			$discount,
			$index
		);
		
		// Loading spinner
		$loading_spinner = '<div class="bogo-loading-spinner" style="display: none; text-align: center; margin-top: 10px;">
			<div style="display: inline-block; width: 20px; height: 20px; border: 2px solid #f3f3f3; border-top: 2px solid ' . esc_attr( $colors['theme_color'] ) . '; border-radius: 50%; animation: spin 1s linear infinite;"></div>
		</div>';
		
		// Add template data attributes for dynamic updates
		$template_data_attrs = 'data-template="' . $template . '" data-template-key="template' . $template . '"';
		
		return $this->load_template( $template_key, [
			'buy_qty' => $buy_qty,
			'get_qty' => $get_qty,
			'get_name' => $get_name,
			'discount_text' => $discount_text,
			'get_image' => $get_image,
			'buy_product_id' => $buy_product_id,
			'get_product_id' => $get_product_id,
			'discount' => $discount,
			'index' => $index,
			'theme_color' => $colors['theme_color'],
			'primary_color' => $colors['primary'],
			'secondary_color' => $colors['secondary'],
			'text_color' => $colors['text'],
			'background_color' => $colors['background'],
			'button_bg_color' => $colors['button_bg'],
			'button_text_color' => $colors['button_text'],
			'button_color' => $colors['button_color'],
			'common_button_data' => $common_button_data,
			'loading_spinner' => $loading_spinner,
			'template_data_attrs' => $template_data_attrs
		] );
	}

	/**
	 * Get available templates from the templates folder
	 */
	private function get_available_templates() {
		$templates_dir = plugin_dir_path( __FILE__ ) . 'templates/';
		$templates = [];

		if ( is_dir( $templates_dir ) ) {
			$files = glob( $templates_dir . 'template*.php' );
			foreach ( $files as $file ) {
				$template_name = basename( $file, '.php' );
				$templates[] = $template_name;
			}
		}

		// Fallback to default templates if none found
		if ( empty( $templates ) ) {
			$templates = ['template1', 'template2', 'template3'];
		}

		return $templates;
	}

	/**
	 * Get template info (name, description, etc.)
	 */
	private function get_template_info( $template_name ) {
		$template_info = [
			'template1' => [
				'name' => '🎁 Classic Template',
				'description' => 'Clean gradient design with side-by-side layout. Professional and modern look.',
				'preview_text' => 'Buy 2 of this product and get 1 of Premium Headphones for free!',
				'button_text' => '🛒 Grab This Offer!',
				'style' => 'background: linear-gradient(135deg, #fff 0%, #f8f9ff 100%); border-radius: 6px;'
			],
			'template2' => [
				'name' => '💎 Premium Card',
				'description' => 'Elegant card design with glass-morphism effects and premium styling. Eye-catching and luxurious.',
				'preview_text' => 'Buy 2 → Get 1 FREE!',
				'button_text' => '✨ Claim Now!',
				'style' => 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px;'
			],
			'template3' => [
				'name' => '🚀 Dynamic Burst',
				'description' => 'Bold and vibrant design with attention-grabbing colors. Perfect for sales and promotions.',
				'preview_text' => 'Limited Time: Buy More, Save More!',
				'button_text' => '🎯 Get Deal!',
				'style' => 'background: linear-gradient(45deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3); border-radius: 15px;'
			]
		];

		return isset( $template_info[$template_name] ) ? $template_info[$template_name] : $template_info['template1'];
	}

	/**
	 * Global template loader function
	 * Loads template files from the templates folder
	 */
	private function load_template( $template_name, $variables = [] ) {
		// Get available templates dynamically
		$available_templates = $this->get_available_templates();
		
		// Validate template name (security)
		if ( ! in_array( $template_name, $available_templates ) ) {
			$template_name = 'template1'; // fallback to default
		}

		// Build template file path
		$template_file = plugin_dir_path( __FILE__ ) . 'templates/' . $template_name . '.php';
		
		// Check if template file exists
		if ( ! file_exists( $template_file ) ) {
			// Return error message if template not found
			return '<div class="bogo-error" style="background: #ffebee; border: 1px solid #f44336; color: #d32f2f; padding: 10px; border-radius: 4px; margin: 10px 0;">
				<strong>BOGO Template Error:</strong> Template "' . esc_html( $template_name ) . '" not found.
				<br><small>Available templates: ' . implode( ', ', $available_templates ) . '</small>
			</div>';
		}

		// Extract variables to make them available in template
		extract( $variables );

		// Capture template output
		ob_start();
		include $template_file;
		$template_content = ob_get_clean();

		return $template_content;
	}

    public function apply_bogo_discount( $cart ) {

	    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
	        return;
	    }

	    $rules = $this->get_rules( [ 'enabled' => 1 ] );
	    $now = date( 'Y-m-d' );

		foreach ( $rules as $index => $rule ) {
			if ( empty( $rule->get_product ) || empty( $rule->buy_qty ) ) {
				continue;
			}

			if ( !empty( $rule->start_date ) && $rule->start_date > $now ) continue;
            if ( !empty( $rule->end_date ) && $rule->end_date < $now ) continue;

			$buy_product_id = $rule->buy_product; // may be 'all'
			$get_product_id = intval( $rule->get_product );
			$buy_qty        = intval( $rule->buy_qty );
			$get_qty        = intval( $rule->get_qty ) ?: 1;
			$discount       = intval( $rule->discount );

			// Count eligible BUY items (excluding gift lines)
			$buy_count = 0;
			foreach ( $cart->get_cart() as $cart_item ) {
				if ( ! empty( $cart_item['wc_advanced_bogo_gift'] ) ) {
					continue;
				}

				if ( $buy_product_id === 'all' || $cart_item['product_id'] == $buy_product_id ) {
					$buy_count += $cart_item['quantity'];
				}
			}

			if ( $buy_count < $buy_qty ) {
				continue;
			}

			// Define unique gift hash key to allow multiple gift lines for same get_product
			$gift_key = 'wc_advanced_bogo_gift_' . $rule->id;

			// Check if gift already exists for this rule
			$gift_found = false;

			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
				if (
					isset( $cart_item[ $gift_key ] ) &&
					$cart_item['product_id'] == $get_product_id
				) {
					// Update quantity if not same
					if ( $cart_item['quantity'] != $get_qty ) {
						$cart->set_quantity( $cart_item_key, $get_qty );
					}

					// Apply discount safely
					$product = wc_get_product( $get_product_id );
					if ( $product && is_object( $cart_item['data'] ) ) {
						$price = $product->get_price();
						$new_price = $price * ( 100 - $discount ) / 100;
						$cart_item['data']->set_price( $new_price );
					}

					$gift_found = true;
					break;
				}
			}

			if ( ! $gift_found ) {
				// Add the gift product with a unique key for this rule
				$cart->add_to_cart(
					$get_product_id,
					$get_qty,
					0,
					[],
					[
						'wc_advanced_bogo_gift' => true,
						$gift_key => true
					]
				);
			}
		}
	}

	public function maybe_remove_remove_link( $link, $cart_item_key ) {
		$cart = WC()->cart;
		$cart_item = $cart->get_cart_item( $cart_item_key );

		if ( isset( $cart_item['wc_advanced_bogo_gift'] ) && $cart_item['wc_advanced_bogo_gift'] === true ) {
			return ''; // Hide the remove link
		}

		return $link;
	}

	public function handle_grab_bogo_offer() {
		check_ajax_referer( 'wc_advanced_bogo_nonce', 'nonce' );

		$buy_product = sanitize_text_field( $_POST['buy_product'] );
		$buy_qty = intval( $_POST['buy_qty'] );
		$get_product = intval( $_POST['get_product'] );
		$get_qty = intval( $_POST['get_qty'] );
		$discount = intval( $_POST['discount'] );
		$rule_index = intval( $_POST['rule_index'] );

		try {
			// Add the required quantity of buy product to cart
			if ( $buy_product === 'all' ) {
				// For 'all' products, we need to get the current product ID
				$current_product_id = get_queried_object_id();
				if ( ! $current_product_id ) {
					wp_send_json_error( array( 'message' => 'Product not found. Please refresh the page and try again.' ) );
				}
				$product_id = $current_product_id;
			} else {
				$product_id = intval( $buy_product );
			}

			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				wp_send_json_error( array( 'message' => 'Product not found. Please try again.' ) );
			}

			// Check if product is in stock
			if ( ! $product->is_in_stock() ) {
				wp_send_json_error( array( 'message' => 'Product is out of stock. Please try a different product.' ) );
			}

			// Check if product is purchasable
			if ( ! $product->is_purchasable() ) {
				wp_send_json_error( array( 'message' => 'Product is not available for purchase.' ) );
			}

			// Add the required quantity to cart
			$cart_item_key = WC()->cart->add_to_cart( $product_id, $buy_qty );

			if ( $cart_item_key ) {
				// Store the BOGO rule information in the cart item
				WC()->cart->cart_contents[ $cart_item_key ]['wc_advanced_bogo_rule'] = [
					'buy_product' => $buy_product,
					'buy_qty' => $buy_qty,
					'get_product' => $get_product,
					'get_qty' => $get_qty,
					'discount' => $discount,
					'rule_index' => $rule_index,
				];

				wp_send_json_success( array(
					'message' => 'BOGO offer added to cart successfully!',
					'cart_count' => WC()->cart->get_cart_contents_count(),
					'cart_url' => wc_get_cart_url()
				) );
			} else {
				wp_send_json_error( array( 'message' => 'Failed to add product to cart. Please try again.' ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => 'An error occurred: ' . $e->getMessage() ) );
		}
	}

	/**
	 * Display BOGO hint inside cart line items
	 */
	public function display_cart_item_bogo_hint( $cart_item, $cart_item_key ) {
		$rules = $this->get_rules( [ 'enabled' => 1 ] );
		$now = date( 'Y-m-d' );
		
		foreach ( $rules as $index => $rule ) {
			if ( empty( $rule->get_product ) || empty( $rule->buy_qty ) ) {
				continue;
			}

			if ( !empty( $rule->start_date ) && $rule->start_date > $now ) continue;
			if ( !empty( $rule->end_date ) && $rule->end_date < $now ) continue;

			$buy_product_id = $rule->buy_product; // may be 'all'
			$get_product_id = intval( $rule->get_product );
			$buy_qty = intval( $rule->buy_qty );
			$get_qty = intval( $rule->get_qty ) ?: 1;
			$discount = intval( $rule->discount );

			// Check if this cart item matches the buy product
			if ( $buy_product_id === 'all' || $cart_item['product_id'] == $buy_product_id ) {
				// Count current BUY items in cart
				$buy_count = 0;
				foreach ( WC()->cart->get_cart() as $item ) {
					if ( ! empty( $item['wc_advanced_bogo_gift'] ) ) {
						continue;
					}

					if ( $buy_product_id === 'all' || $item['product_id'] == $buy_product_id ) {
						$buy_count += $item['quantity'];
					}
				}

				// Check if customer is close to qualifying
				if ( $buy_count > 0 && $buy_count < $buy_qty ) {
					$remaining_qty = $buy_qty - $buy_count;
					$get_product = wc_get_product( $get_product_id );
					
					if ( $get_product ) {
						$discount_text = ( $discount == 100 ) ? 'for free!' : "at {$discount}% off!";
						
						echo '<div style="margin-top: 8px; padding: 8px; background: #f0f9ff; border-left: 3px solid #3b82f6; border-radius: 4px; font-size: 12px; color: #1e40af; font-weight: 600;">
							🎁 Add <strong>' . $remaining_qty . ' more</strong> and get <strong>' . $get_qty . 'x ' . esc_html( $get_product->get_name() ) . '</strong> ' . esc_html( $discount_text ) . '
						</div>';
					}
				}
			}
		}
	}

	/**
	 * Display BOGO hint inside checkout cart items
	 */
	public function display_checkout_item_bogo_hint( $quantity_html, $cart_item, $cart_item_key ) {
		$rules = $this->get_rules( [ 'enabled' => 1 ] );
		$now = date( 'Y-m-d' );
		$hint_html = '';
		
		foreach ( $rules as $index => $rule ) {
			if ( empty( $rule->get_product ) || empty( $rule->buy_qty ) ) {
				continue;
			}

			if ( !empty( $rule->start_date ) && $rule->start_date > $now ) continue;
			if ( !empty( $rule->end_date ) && $rule->end_date < $now ) continue;

			$buy_product_id = $rule->buy_product; // may be 'all'
			$get_product_id = intval( $rule->get_product );
			$buy_qty = intval( $rule->buy_qty );
			$get_qty = intval( $rule->get_qty ) ?: 1;
			$discount = intval( $rule->discount );

			// Check if this cart item matches the buy product
			if ( $buy_product_id === 'all' || $cart_item['product_id'] == $buy_product_id ) {
				// Count current BUY items in cart
				$buy_count = 0;
				foreach ( WC()->cart->get_cart() as $item ) {
					if ( ! empty( $item['wc_advanced_bogo_gift'] ) ) {
						continue;
					}

					if ( $buy_product_id === 'all' || $item['product_id'] == $buy_product_id ) {
						$buy_count += $item['quantity'];
					}
				}

				// Check if customer is close to qualifying
				if ( $buy_count > 0 && $buy_count < $buy_qty ) {
					$remaining_qty = $buy_qty - $buy_count;
					$get_product = wc_get_product( $get_product_id );
					
					if ( $get_product ) {
						$discount_text = ( $discount == 100 ) ? 'for free!' : "at {$discount}% off!";
						
						$hint_html .= '<div style="margin-top: 8px; padding: 8px; background: #f0f9ff; border-left: 3px solid #3b82f6; border-radius: 4px; font-size: 12px; color: #1e40af; font-weight: 600;">
							🎁 Add <strong>' . $remaining_qty . ' more</strong> and get <strong>' . $get_qty . 'x ' . esc_html( $get_product->get_name() ) . '</strong> ' . esc_html( $discount_text ) . '
						</div>';
					}
				}
			}
		}
		
		return $quantity_html . $hint_html;
	}

	/**
	 * AJAX handler for getting BOGO hints
	 */
	public function get_bogo_hints() {
		check_ajax_referer( 'wc_advanced_bogo_nonce', 'nonce' );
		
		$product_id = intval( $_POST['product_id'] );
		$rules = $this->get_rules( [ 'enabled' => 1 ] );
		$now = date( 'Y-m-d' );
		$hint = '';
		$hint_data = array();
		
		foreach ( $rules as $index => $rule ) {
			if ( empty( $rule->get_product ) || empty( $rule->buy_qty ) ) {
				continue;
			}

			if ( !empty( $rule->start_date ) && $rule->start_date > $now ) continue;
			if ( !empty( $rule->end_date ) && $rule->end_date < $now ) continue;

			$buy_product_id = $rule->buy_product; // may be 'all'
			$get_product_id = intval( $rule->get_product );
			$buy_qty = intval( $rule->buy_qty );
			$get_qty = intval( $rule->get_qty ) ?: 1;
			$discount = intval( $rule->discount );

			// Check if this product matches the buy product
			if ( $buy_product_id === 'all' || $product_id == $buy_product_id ) {
				// Count current BUY items in cart
				$buy_count = 0;
				foreach ( WC()->cart->get_cart() as $item ) {
					if ( ! empty( $item['wc_advanced_bogo_gift'] ) ) {
						continue;
					}

					if ( $buy_product_id === 'all' || $item['product_id'] == $buy_product_id ) {
						$buy_count += $item['quantity'];
					}
				}

				// Check if customer is close to qualifying
				if ( $buy_count > 0 && $buy_count < $buy_qty ) {
					$remaining_qty = $buy_qty - $buy_count;
					$get_product = wc_get_product( $get_product_id );
					
					if ( $get_product ) {
						$discount_text = ( $discount == 100 ) ? 'for free!' : "at {$discount}% off!";
						
						$hint = '<div style="margin-top: 8px; padding: 8px; background: #f0f9ff; border-left: 3px solid #3b82f6; border-radius: 4px; font-size: 12px; color: #1e40af; font-weight: 600;">
							🎁 Add <strong>' . $remaining_qty . ' more</strong> and get <strong>' . $get_qty . 'x ' . esc_html( $get_product->get_name() ) . '</strong> ' . esc_html( $discount_text ) . '
						</div>';
						
						$hint_data = array(
							'remaining_qty' => $remaining_qty,
							'get_qty' => $get_qty,
							'get_product_name' => $get_product->get_name(),
							'discount_text' => $discount_text,
							'rule_index' => $rule->id,
							'html' => $hint
						);
						break;
					}
				}
			}
		}
		
		wp_send_json_success( array( 
			'hint' => $hint,
			'remaining_qty' => isset( $hint_data['remaining_qty'] ) ? $hint_data['remaining_qty'] : 0,
			'get_qty' => isset( $hint_data['get_qty'] ) ? $hint_data['get_qty'] : 0,
			'get_product_name' => isset( $hint_data['get_product_name'] ) ? $hint_data['get_product_name'] : '',
			'discount_text' => isset( $hint_data['discount_text'] ) ? $hint_data['discount_text'] : ''
		) );
	}

	/**
	 * Save BOGO discount data to order line item meta
	 */
	public function save_bogo_order_item_meta( $item, $cart_item_key, $values, $order ) {
		// Save BOGO gift item meta
		if ( isset( $values['wc_advanced_bogo_gift'] ) && $values['wc_advanced_bogo_gift'] === true ) {
			$item->add_meta_data( '_wc_advanced_bogo_gift', 'yes', true );
		}
		
		// Save BOGO rule information
		if ( isset( $values['wc_advanced_bogo_rule'] ) ) {
			$item->add_meta_data( '_wc_advanced_bogo_rule', $values['wc_advanced_bogo_rule'], true );
		}
	}

	/**
	 * Save BOGO discount summary to order meta
	 */
	public function save_bogo_order_meta( $order_id, $posted_data, $order ) {
		$bogo_items = [];
		$total_bogo_discount = 0;
		$bogo_rules_applied = [];

		foreach ( $order->get_items() as $item_id => $item ) {
			$is_gift = $item->get_meta( '_wc_advanced_bogo_gift' );
			$rule_data = $item->get_meta( '_wc_advanced_bogo_rule' );
			
			if ( $is_gift === 'yes' ) {
				$bogo_items[] = [
					'item_id' => $item_id,
					'product_id' => $item->get_product_id(),
					'product_name' => $item->get_name(),
					'quantity' => $item->get_quantity(),
					'line_total' => $item->get_total(),
					'type' => 'gift'
				];
				
				// Calculate discount amount (original price - discounted price)
				$product = $item->get_product();
				if ( $product ) {
					$original_price = $product->get_regular_price() * $item->get_quantity();
					$discounted_price = $item->get_total();
					$discount_amount = $original_price - $discounted_price;
					$total_bogo_discount += $discount_amount;
				}
			}
			
			if ( $rule_data ) {
				$bogo_rules_applied[] = $rule_data;
				$bogo_items[] = [
					'item_id' => $item_id,
					'product_id' => $item->get_product_id(),
					'product_name' => $item->get_name(),
					'quantity' => $item->get_quantity(),
					'line_total' => $item->get_total(),
					'type' => 'trigger',
					'rule' => $rule_data
				];
			}
		}

		if ( !empty( $bogo_items ) ) {
			$order->update_meta_data( '_wc_advanced_bogo_items', $bogo_items );
			$order->update_meta_data( '_wc_advanced_bogo_discount_total', $total_bogo_discount );
			$order->update_meta_data( '_wc_advanced_bogo_rules_applied', $bogo_rules_applied );
			$order->update_meta_data( '_wc_advanced_bogo_order_date', current_time( 'Y-m-d H:i:s' ) );
			$order->save();
		}
	}

	/**
	 * AJAX handler for saving individual BOGO rule
	 */
	public function handle_save_individual_bogo_rule() {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'], 'save_individual_bogo_rule' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed. Please refresh the page and try again.' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to perform this action.' ) );
		}

		$rule_index = intval( $_POST['rule_index'] );
		$rule_data = $_POST['rule_data'];

		// Validate rule data
		if ( empty( $rule_data['buy_product'] ) || empty( $rule_data['get_product'] ) || empty( $rule_data['buy_qty'] ) ) {
			wp_send_json_error( array( 'message' => 'Please fill in all required fields: Buy Product, Get Product, and Buy Quantity.' ) );
		}

		// Sanitize rule data
		$sanitized_rule = array(
			'buy_product' => sanitize_text_field( $rule_data['buy_product'] ),
			'buy_qty'     => intval( $rule_data['buy_qty'] ),
			'get_product' => intval( $rule_data['get_product'] ),
			'get_qty'     => intval( $rule_data['get_qty'] ) ?: 1,
			'discount'    => intval( $rule_data['discount'] ),
			'start_date'  => sanitize_text_field( $rule_data['start_date'] ?? '' ),
			'end_date'    => sanitize_text_field( $rule_data['end_date'] ?? '' ),
		);

		// Add title
		$sanitized_rule['title'] = $this->generate_rule_title( $sanitized_rule );

		// Save rule to database
		$saved_id = $this->save_rule( $sanitized_rule, $rule_index );

		if ( $saved_id ) {
			// Get product names for response
			$buy_product_name = 'All Products';
			if ( $sanitized_rule['buy_product'] !== 'all' ) {
				$buy_product = wc_get_product( $sanitized_rule['buy_product'] );
				$buy_product_name = $buy_product ? $buy_product->get_name() : 'Unknown Product';
			}

			$get_product = wc_get_product( $sanitized_rule['get_product'] );
			$get_product_name = $get_product ? $get_product->get_name() : 'Unknown Product';

			wp_send_json_success( array(
				'message' => 'Rule saved successfully!',
				'rule_id' => $saved_id,
				'rule_data' => $sanitized_rule,
				'buy_product_name' => $buy_product_name,
				'get_product_name' => $get_product_name
			) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to save rule. Please try again.' ) );
		}
	}

	/**
	 * Get contrasting color (white or black) based on background
	 */
	private function get_contrasting_color($hex_color) {
		// Remove # if present and ensure valid hex color
		$hex_color = ltrim($hex_color, '#');
		
		// Ensure we have a valid 6-character hex color
		if (strlen($hex_color) !== 6) {
			return '#000000'; // Default to black for invalid colors
		}
		
		// Convert to RGB with proper integer casting
		$r = (int) hexdec(substr($hex_color, 0, 2));
		$g = (int) hexdec(substr($hex_color, 2, 2));
		$b = (int) hexdec(substr($hex_color, 4, 2));
		
		// Calculate luminance
		$luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
		
		// Return contrasting color
		return $luminance > 0.5 ? '#000000' : '#FFFFFF';
	}

	/**
	 * Adjust color brightness
	 */
	private function adjust_color_brightness($hex_color, $percent) {
		// Remove # if present and ensure valid hex color
		$hex_color = ltrim($hex_color, '#');
		
		// Ensure we have a valid 6-character hex color
		if (strlen($hex_color) !== 6) {
			return $hex_color; // Return original if invalid
		}
		
		// Convert to RGB with proper integer casting
		$r = (int) hexdec(substr($hex_color, 0, 2));
		$g = (int) hexdec(substr($hex_color, 2, 2));
		$b = (int) hexdec(substr($hex_color, 4, 2));
		
		// Calculate new RGB values with proper integer casting
		$r = (int) max(0, min(255, $r + ($r * $percent / 100)));
		$g = (int) max(0, min(255, $g + ($g * $percent / 100)));
		$b = (int) max(0, min(255, $b + ($b * $percent / 100)));
		
		// Convert back to hex with proper integer values
		return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . 
					 str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . 
					 str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
	}
}

new WC_Advanced_BOGO();