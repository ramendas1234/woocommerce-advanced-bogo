<?php
/**
 * Plugin Name: Smart BOGO
 * Plugin URI: https://woocommerce.com/products/smart-bogo/
 * Description: <strong>WooCommerce Smart BOGO</strong> lets you create flexible “Buy One, Get One” deals to boost sales and reward customers. Configure simple or advanced rules like Buy X, Get Y with custom quantities, discounts, date ranges, and even storewide offers.
 * Version: 1.0.0
 * Author: StoreApps
 * Author URI: https://www.storeapps.org/
 * Developer: StoreApps
 * Developer URI: https://www.storeapps.org/
 * Requires PHP: 5.8
 * Requires at least: 4.4
 * Tested up to: 6.8.1
 * WC requires at least: 3.0.0
 * WC tested up to: 9.9.5
 * Requires Plugins: woocommerce
 * Text Domain: woocommerce-smart-bogo
 * Domain Path: /languages/
 * Copyright (c) 2014-2025 WooCommerce, StoreApps All rights reserved.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package woocommerce-smart-bogo
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables', 
            __FILE__,  
            true  
        );
    }
});


class WC_Advanced_BOGO {

    const OPTION_KEY = 'wc_advanced_bogo_rules';
    const TEMPLATE_OPTION_KEY = 'wc_advanced_bogo_template';

    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'wp_ajax_grab_bogo_offer', array( $this, 'handle_grab_bogo_offer' ) );
        add_action( 'wp_ajax_nopriv_grab_bogo_offer', array( $this, 'handle_grab_bogo_offer' ) );
        add_action( 'wp_ajax_get_bogo_hints', array( $this, 'get_bogo_hints' ) );
        add_action( 'wp_ajax_nopriv_get_bogo_hints', array( $this, 'get_bogo_hints' ) );
        add_action( 'wp_ajax_save_individual_bogo_rule', array( $this, 'handle_save_individual_bogo_rule' ) );

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
			add_filter( 'woocommerce_cart_item_quantity', [ $this, 'wc_advanced_bogo_lock_gift_qty' ] , 10, 3 );
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
		$rules = get_option( self::OPTION_KEY, [] );
		$now = date( 'Y-m-d' );
		$active_rules = array();
		
		foreach ( $rules as $index => $rule ) {
			if ( empty( $rule['get_product'] ) || empty( $rule['buy_qty'] ) ) {
				continue;
			}

			if ( isset( $rule['start_date'] ) && !empty( $rule['start_date'] ) && $rule['start_date'] > $now ) continue;
			if ( isset( $rule['end_date'] ) && !empty( $rule['end_date'] ) && $rule['end_date'] < $now ) continue;

			$active_rules[] = array(
				'index' => $index,
				'buy_product' => $rule['buy_product'],
				'buy_qty' => intval( $rule['buy_qty'] ),
				'get_product' => intval( $rule['get_product'] ),
				'get_qty' => intval( $rule['get_qty'] ) ?: 1,
				'discount' => intval( $rule['discount'] )
			);
		}
		
		return $active_rules;
	}

	
	/**
	 * Add BOGO hint content to cart blocks
	 */
	public function add_cart_blocks_hint_content( $content, $cart_item ) {
		$rules = get_option( self::OPTION_KEY, [] );
		$now = date( 'Y-m-d' );
		
		foreach ( $rules as $index => $rule ) {
			if ( empty( $rule['get_product'] ) || empty( $rule['buy_qty'] ) ) {
				continue;
			}

			if ( isset( $rule['start_date'] ) && !empty( $rule['start_date'] ) && $rule['start_date'] > $now ) continue;
			if ( isset( $rule['end_date'] ) && !empty( $rule['end_date'] ) && $rule['end_date'] < $now ) continue;

			$buy_product_id = $rule['buy_product']; // may be 'all'
			$get_product_id = intval( $rule['get_product'] );
			$buy_qty = intval( $rule['buy_qty'] );
			$get_qty = intval( $rule['get_qty'] ) ?: 1;
			$discount = intval( $rule['discount'] );

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
						
						$hint = '<div style="margin-top: 8px; padding: 8px; background: #f0f9ff; border-left: 3px solid #3b82f6; border-radius: 4px; font-size: 12px; color: #1e40af; font-weight: 600;">
							🎁 Add <strong>' . $remaining_qty . ' more</strong> and get <strong>' . $get_qty . 'x ' . esc_html( $get_product->get_name() ) . '</strong> ' . esc_html( $discount_text ) . '
						</div>';
						
						$content .= $hint;
						break;
					}
				}
			}
		}
		
		return $content;
	}

	/**
	 * AJAX handler for getting BOGO hints
	 */
	public function get_bogo_hints() {
		check_ajax_referer( 'wc_advanced_bogo_nonce', 'nonce' );
		
		$product_id = intval( $_POST['product_id'] );
		$rules = get_option( self::OPTION_KEY, [] );
		$now = date( 'Y-m-d' );
		$hint = '';
		$hint_data = array();
		
		foreach ( $rules as $index => $rule ) {
			if ( empty( $rule['get_product'] ) || empty( $rule['buy_qty'] ) ) {
				continue;
			}

			if ( isset( $rule['start_date'] ) && !empty( $rule['start_date'] ) && $rule['start_date'] > $now ) continue;
			if ( isset( $rule['end_date'] ) && !empty( $rule['end_date'] ) && $rule['end_date'] < $now ) continue;

			$buy_product_id = $rule['buy_product']; // may be 'all'
			$get_product_id = intval( $rule['get_product'] );
			$buy_qty = intval( $rule['buy_qty'] );
			$get_qty = intval( $rule['get_qty'] ) ?: 1;
			$discount = intval( $rule['discount'] );

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
							'rule_index' => $index,
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
	 * Add JavaScript for cart blocks compatibility
	 */
	public function add_cart_blocks_js() {
		if ( !is_cart() && !is_checkout() ) {
			return;
		}
		?>
		<script>
		jQuery(document).ready(function($) {
			// Function to add BOGO hints to cart blocks
			function addBogoHintsToCartBlocks() {
				// Check for cart blocks - multiple possible selectors
				var selectors = [
					'.wp-block-woocommerce-cart-item',
					'.wc-block-components-cart-item',
					'[data-block-name="woocommerce/cart-item"]'
				];
				
				selectors.forEach(function(selector) {
					$(selector).each(function() {
						var $cartItem = $(this);
						var $productName = $cartItem.find('.wc-block-components-cart-item__name, .cart-item-name');
						
						// Only add hint if not already added and product name exists
						if ($productName.length > 0 && $cartItem.find('.bogo-cart-hint').length === 0) {
							// Get product ID from data attribute or find it
							var productId = $cartItem.data('product-id') || 
										   $cartItem.find('[data-product-id]').data('product-id') ||
										   $cartItem.find('input[name*="product_id"]').val();
							
							if (productId) {
								// Get BOGO rules via AJAX
								$.ajax({
									url: wc_advanced_bogo_ajax.ajax_url,
									type: 'POST',
									data: {
										action: 'get_bogo_hints',
										product_id: productId,
										nonce: wc_advanced_bogo_ajax.nonce
									},
									success: function(response) {
										if (response.success && response.data.hint) {
											$productName.after(response.data.hint);
										}
									}
								});
							}
						}
					});
				});
			}
			
			// Run on page load
			setTimeout(addBogoHintsToCartBlocks, 1000);
			
			// Run when cart is updated
			$(document.body).on('updated_cart_totals', function() {
				setTimeout(addBogoHintsToCartBlocks, 1000);
			});
			
			// Run when blocks are rendered
			$(document.body).on('wc-blocks-cart-updated', function() {
				setTimeout(addBogoHintsToCartBlocks, 1000);
			});
			
			// Run when cart items are updated
			$(document.body).on('cart_item_removed', function() {
				setTimeout(addBogoHintsToCartBlocks, 1000);
			});
			
			// Run when cart items are added
			$(document.body).on('cart_item_added', function() {
				setTimeout(addBogoHintsToCartBlocks, 1000);
			});
		});
		</script>
		<?php
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
				
				/* Template 3 (Dynamic Burst) specific styling */
				.bogo-template-3 .bogo-offer-container {
					max-width: 15rem;
					margin: 0.5rem auto;
				}
				
				.bogo-template-3 .bogo-offer-content {
					padding: 0.75rem;
				}
				
				.bogo-template-3 .bogo-offer-title {
					font-size: 0.875rem;
					margin-bottom: 0.5rem;
				}
				
				.bogo-template-3 .bogo-offer-description {
					font-size: 0.75rem;
					margin-bottom: 0.5rem;
				}
				
				.bogo-template-3 .bogo-offer-button {
					font-size: 0.75rem;
					padding: 0.375rem 0.75rem;
				}
				
				/* Dynamic update feedback */
				.bogo-updated {
					animation: bogoUpdate 0.3s ease-in-out;
				}
				
				@keyframes bogoUpdate {
					0% { transform: scale(1); }
					50% { transform: scale(1.02); }
					100% { transform: scale(1); }
				}
				
				/* Loading state */
				.bogo-loading {
					opacity: 0.7;
					pointer-events: none;
				}
				
				.bogo-loading-indicator {
					position: absolute;
					top: 50%;
					left: 50%;
					transform: translate(-50%, -50%);
					background: rgba(255, 255, 255, 0.9);
					padding: 10px 20px;
					border-radius: 5px;
					box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
					z-index: 1000;
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
		if ( $hook === 'woocommerce_page_wc-smart-bogo' || $hook === 'toplevel_page_wc-smart-bogo' ) {
			// Enqueue WordPress admin scripts first
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			
			// Enqueue WordPress datepicker styles
			wp_enqueue_style( 'jquery-ui-datepicker' );
			
			// Enqueue WooCommerce admin scripts
			wp_enqueue_script( 'woocommerce_admin' );
			wp_enqueue_script( 'wc-enhanced-select' );
			wp_enqueue_style( 'woocommerce_admin_styles' );
			
			// Enqueue our admin script after WooCommerce scripts
			wp_enqueue_script( 
				'wc-advanced-bogo-admin', 
				plugin_dir_url(__FILE__) . 'admin.js', 
				['jquery', 'jquery-ui-datepicker', 'woocommerce_admin', 'wc-enhanced-select'], 
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


    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Smart BOGO',
            'Smart BOGO',
            'manage_woocommerce',
            'wc-smart-bogo',
            [ $this, 'settings_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'wc_advanced_bogo', self::OPTION_KEY );
        register_setting( 'wc_advanced_bogo', self::TEMPLATE_OPTION_KEY );
    }

    public function settings_page() {
        $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'rules';
        
        // Handle form submissions
        if ( isset( $_POST['bogo_rules'] ) && $current_tab === 'rules' ) {
            check_admin_referer( 'save_bogo_rules' );
            $filtered_rules = [];
            if ( is_array( $_POST['bogo_rules'] ) ) {
                foreach ( $_POST['bogo_rules'] as $rule ) {
                    if ( !empty( $rule['buy_product'] ) && !empty( $rule['get_product'] ) && !empty( $rule['buy_qty'] ) ) {
                        // CRITICAL SECURITY VALIDATION: Cap discount at 100%
                        $discount_value = intval( $rule['discount'] );
                        if ( $discount_value > 100 ) {
                            echo '<div class="error"><p><strong>Security Error:</strong> Discount for one of the rules exceeded 100% and was automatically capped at 100% to prevent business losses.</p></div>';
                            $discount_value = 100;
                        }
                        if ( $discount_value < 0 ) {
                            echo '<div class="error"><p><strong>Error:</strong> Negative discount detected and was set to 0%.</p></div>';
                            $discount_value = 0;
                        }
                        
                        $filtered_rules[] = [
                            'buy_product' => sanitize_text_field( $rule['buy_product'] ),
                            'buy_qty'     => intval( $rule['buy_qty'] ),
                            'get_product' => intval( $rule['get_product'] ),
                            'get_qty'     => intval( $rule['get_qty'] ) ?: 1,
                            'discount'    => $discount_value, // Use validated discount
                            'start_date'  => sanitize_text_field( $rule['start_date'] ?? '' ),
                            'end_date'    => sanitize_text_field( $rule['end_date'] ?? '' ),
                        ];
                    }
                }
            }
            update_option( self::OPTION_KEY, $filtered_rules );
            echo '<div class="updated"><p>BOGO discount rules saved successfully!</p></div>';
        }

        if ( isset( $_POST['bogo_template'] ) && $current_tab === 'ui-settings' ) {
            check_admin_referer( 'save_bogo_template' );
            
            // Save template selection
            $selected_template = sanitize_text_field( $_POST['bogo_template'] );
            
            // Get current template settings
            $template_settings = get_option( self::TEMPLATE_OPTION_KEY, array() );
            
            // Ensure template_settings is an array (handle old string data)
            if ( !is_array( $template_settings ) ) {
                $template_settings = array();
            }
            
            // Update selected template
            $template_settings['selected_template'] = intval( str_replace( 'template', '', $selected_template ) );
            
            // Save color palette settings
            if ( isset( $_POST['bogo_template_colors'] ) && is_array( $_POST['bogo_template_colors'] ) ) {
                foreach ( $_POST['bogo_template_colors'] as $template_name => $colors ) {
                    if ( is_array( $colors ) ) {
                        // Initialize template colors if not exists
                        if ( !isset( $template_settings[$template_name] ) ) {
                            $template_settings[$template_name] = array();
                        }
                        
                        foreach ( $colors as $color_type => $color_value ) {
                            $template_settings[$template_name][$color_type] = sanitize_hex_color( $color_value );
                        }
                    }
                }
            }
            
            // Save all template settings
            update_option( self::TEMPLATE_OPTION_KEY, $template_settings );
            
            echo '<div class="updated"><p>BOGO message template and color settings saved successfully!</p></div>';
        }

        $rules = get_option( self::OPTION_KEY, [] );
        $template_settings = get_option( self::TEMPLATE_OPTION_KEY, [] );
        
        // Ensure template_settings is an array (handle old string data)
        if ( !is_array( $template_settings ) ) {
            $template_settings = array();
        }
        
        $selected_template = isset( $template_settings['selected_template'] ) ? 'template' . $template_settings['selected_template'] : 'template1';
        
        if ( empty( $rules ) ) {
            $rules = [
                [
                    'buy_product' => '',
                    'buy_qty'     => '',
                    'get_product' => '',
                    'get_qty'     => '1',
                    'discount'    => '',
                    'start_date'  => '',
                    'end_date'    => '',
                ]
            ];
        }
        ?>
        <meta name="woocommerce-search-products-nonce" content="<?php echo wp_create_nonce( 'search-products' ); ?>">
        <div class="wrap">
            <h1>Smart BOGO</h1>
            
            <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
                <a href="<?php echo admin_url( 'admin.php?page=wc-smart-bogo&tab=rules' ); ?>" 
                   class="nav-tab <?php echo $current_tab === 'rules' ? 'nav-tab-active' : ''; ?>">
                    BOGO Offers
                </a>
                <a href="<?php echo admin_url( 'admin.php?page=wc-smart-bogo&tab=ui-settings' ); ?>" 
                   class="nav-tab <?php echo $current_tab === 'ui-settings' ? 'nav-tab-active' : ''; ?>">
                    BOGO Template Settings
                </a>
                <a href="<?php echo admin_url( 'admin.php?page=wc-smart-bogo&tab=reports' ); ?>" 
                   class="nav-tab <?php echo $current_tab === 'reports' ? 'nav-tab-active' : ''; ?>">
                    📊 Reports
                </a>
            </nav>

            <?php if ( $current_tab === 'rules' ) : ?>
                <form method="post" id="bogo-rules-form">
                    <?php wp_nonce_field( 'save_bogo_rules' ); ?>
                    <div class="bogo-rules-container">
                        <h2>💰 BOGO Discount Rules</h2>
                        <p style="margin-bottom: 20px;">Create rules in plain language. Example: <em>Buy 2 units of T-shirt and get 2 Hat at 50% off</em></p>
                        <div class="security-warning" style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 12px 16px; border-radius: 4px; margin: 10px 0; font-weight: 600;">
                            ⚠️ <strong>Security Notice:</strong> Discount values are automatically capped at 100% to prevent negative pricing and business losses. Values above 100% will be reduced to 100%.
                        </div>
                        <div class="bogo-actions" style="margin: 10px 2px;">
							<button type="button" id="add-bogo-rule" class="button add-bogo-rule">
								+ Add New BOGO
							</button>
							<input type="submit" class="button-primary" value="Save BOGO Rules" style="margin-left: 10px; background: #085d1b; border-color: #085d1b; color: #fff; font-weight: bold;">
						</div>
                        <table class="widefat bogo-rules-sentence-table" id="bogo-rules-table" style="padding-left: 10px;">
                            <thead>
                                <tr>
                                    <th style="width: calc(100% - 120px);">BOGO Rules</th>
                                    <th style="width: 120px; text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="bogo-rules-tbody">
                                <?php foreach ( $rules as $index => $rule ) : ?>
                                <tr class="bogo-rule-row" data-index="<?php echo $index; ?>">
                                    <td style="font-size: 16px; font-weight: 500; padding: 20px 0;">
                                        <div class="bogo-rule-content">
                                            <div class="bogo-rule-main">
                                                <span style="margin-right: 8px; margin-left: 8px;">🛒 Buy</span>
                                                
                                                <input type="text" name="bogo_rules[<?php echo $index; ?>][buy_qty]" value="<?php echo esc_attr( $rule['buy_qty'] ); ?>" min="1" required class="bogo-input-field bogo-number-field" placeholder="e.g. 2" />
                                                <span style="margin: 0 8px;">units of</span>
                                                <select name="bogo_rules[<?php echo $index; ?>][buy_product]" class="wc-product-search bogo-select-field" data-placeholder="Search for a product..." required>
                                                    <option value="">Search for a product...</option>
                                                    <option value="all" <?php selected( $rule['buy_product'], 'all' ); ?>>— All Products —</option>
                                                    <?php if ( !empty( $rule['buy_product'] ) && $rule['buy_product'] !== 'all' ) : 
                                                        $buy_product = wc_get_product( $rule['buy_product'] );
                                                        if ( $buy_product ) : ?>
                                                        <option value="<?php echo esc_attr( $rule['buy_product'] ); ?>" selected><?php echo esc_html( $buy_product->get_name() ); ?></option>
                                                    <?php endif; endif; ?>
                                                </select>
                                                
                                                <span style="margin: 0 8px;">, and get</span>
                                                <input type="text" name="bogo_rules[<?php echo $index; ?>][get_qty]" value="<?php echo esc_attr( $rule['get_qty'] ?: '1' ); ?>" min="1" required class="bogo-input-field bogo-number-field" placeholder="e.g. 1" />
                                                <select name="bogo_rules[<?php echo $index; ?>][get_product]" class="wc-product-search bogo-select-field" data-placeholder="Search for a product..." required>
                                                    <option value="">Search for a product...</option>
                                                    <?php if ( !empty( $rule['get_product'] ) ) : 
                                                        $get_product = wc_get_product( $rule['get_product'] );
                                                        if ( $get_product ) : ?>
                                                        <option value="<?php echo esc_attr( $rule['get_product'] ); ?>" selected><?php echo esc_html( $get_product->get_name() ); ?></option>
                                                    <?php endif; endif; ?>
                                                </select>
                                                
                                                <span style="margin: 0 8px;">at</span>
                                                <input type="text" name="bogo_rules[<?php echo $index; ?>][discount]" value="<?php echo esc_attr( $rule['discount'] ); ?>" min="0" max="100" required class="bogo-input-field bogo-number-field" placeholder="e.g. 50" />
                                                <span style="margin-left: 4px; font-weight: bold;">% off</span>
                                                
                                                <span style="color: #666; font-size: 14px; margin: 0 8px;">Valid from</span>
                                                <input type="text" name="bogo_rules[<?php echo $index; ?>][start_date]" value="<?php echo esc_attr( $rule['start_date'] ?? '' ); ?>" class="bogo-datepicker bogo-input-field" placeholder="YYYY-MM-DD" />
                                                <span style="color: #666; font-size: 14px; margin: 0 8px;">to</span>
                                                <input type="text" name="bogo_rules[<?php echo $index; ?>][end_date]" value="<?php echo esc_attr( $rule['end_date'] ?? '' ); ?>" class="bogo-datepicker bogo-input-field" placeholder="YYYY-MM-DD" />
                                            </div>
                                        </div>
                                    </td>
                                    <td style="text-align: center; vertical-align: top; padding-top: 20px; width: 120px;">
                                        <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                            <button type="button" class="button save-individual-rule" title="Save this rule" data-rule-index="<?php echo $index; ?>" style="color: #00a32a; border-color: #00a32a; background: transparent; height: 35px; width: 35px; display: flex; align-items: center; justify-content: center; border-radius: 4px; position: relative;">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                                                </svg>
                                                <span class="save-loading" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="animation: spin 1s linear infinite;">
                                                        <path d="M12,4V2A10,10 0 0,0 2,12H4A8,8 0 0,1 12,4Z"/>
                                                    </svg>
                                                </span>
                                            </button>
                                            <button type="button" class="button remove-bogo-rule" title="Remove this rule" style="color: #dc3545; border-color: #dc3545; background: transparent; height: 35px; width: 35px; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                </form>
            <?php endif; ?>

            <?php if ( $current_tab === 'ui-settings' ) : ?>
                <form method="post" id="bogo-template-form">
                    <?php wp_nonce_field( 'save_bogo_template' ); ?>
                    <div class="bogo-template-section" style="margin-bottom: 30px; padding: 20px; background: #f9f9f9; border-radius: 8px;">
                        <h2>🎨 BOGO Message Template</h2>
                        <p>Choose how your BOGO offers will appear to customers:</p>
                        <div class="template-options" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-top: 15px;">
                            <?php 
                                $available_templates = $this->get_available_templates();
                                $template_count = 0;
                                foreach ( $available_templates as $template_name ) :
                                    $template_count++;
                                    if ($template_count > 3) break; // Only show first 3 templates
                                    $template_info = $this->get_template_info( $template_name );
                                    
                                    // Simplified color system - Only 3 colors for easier admin management
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
                                    
                                    // Get saved colors or use defaults - Simplified 3-color system
                                    $template_colors = isset( $template_settings[$template_name] ) && is_array( $template_settings[$template_name] ) ? $template_settings[$template_name] : array();
                                    $theme_color = isset( $template_colors['theme_color'] ) ? $template_colors['theme_color'] : $default_colors[$template_name]['theme_color'];
                                    $background_color = isset( $template_colors['background'] ) ? $template_colors['background'] : $default_colors[$template_name]['background'];
                                    $button_color = isset( $template_colors['button_color'] ) ? $template_colors['button_color'] : $default_colors[$template_name]['button_color'];

                                    // Auto-calculate contrasting and complementary colors
                                    $text_color = $this->get_contrasting_color($background_color);
                                    $button_text_color = $this->get_contrasting_color($button_color);
                                    $primary_color = $theme_color; // For backward compatibility
                                    $secondary_color = $this->adjust_color_brightness($theme_color, -20); // Slightly darker version
                                    $button_bg_color = $button_color; // For backward compatibility
                                ?>
                                <div class="template-option" style="border: 2px solid <?php echo $selected_template === $template_name ? '#007cba' : '#ddd'; ?>; border-radius: 8px; padding: 20px; background: white;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <label style="display: block; cursor: pointer; margin: 0;">
                                            <input type="radio" name="bogo_template" value="<?php echo esc_attr( $template_name ); ?>" <?php checked( $selected_template, $template_name ); ?> style="margin-right: 8px;">
                                            <strong><?php echo esc_html( $template_info['name'] ); ?></strong>
                                        </label>
                                        <button type="button" class="button reset-colors-btn" 
                                                data-template="<?php echo esc_attr( $template_name ); ?>"
                                                style="font-size: 11px; padding: 4px 8px; background: #f8f9fa; border: 1px solid #ddd; color: #666; border-radius: 3px;">
                                            🔄 Reset Colors
                                        </button>
                                    </div>
                                    <div style="font-size: 12px; color: #666; margin-top: 8px;">
                                        <?php echo esc_html( $template_info['description'] ); ?>
                                    </div>
                                    
                                    <!-- Simplified Color Settings - Only 3 colors for easier admin management -->
                                    <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                                            <div>
                                                <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">🎨 Theme Color:</label>
                                                <div style="position: relative; display: inline-block;">
                                                    <input type="color" name="bogo_template_colors[<?php echo $template_name; ?>][theme_color]" 
                                                           value="<?php echo esc_attr( $theme_color ); ?>" 
                                                           style="width: 4rem; height: 40px; border: 1px solid #ddd; border-radius: 6px; padding: 5px;"
                                                           data-template="<?php echo esc_attr( $template_name ); ?>"
                                                           data-color-type="theme_color"
                                                           data-default="<?php echo esc_attr( $default_colors[$template_name]['theme_color'] ); ?>"
                                                           class="template-color-input">
                                                </div>
                                                <div style="font-size: 10px; color: #999; margin-top: 3px;">Used for accents & highlights</div>
                                            </div>
                                            <div>
                                                <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">🖼️ Background:</label>
                                                <div style="position: relative; display: inline-block;">
                                                    <input type="color" name="bogo_template_colors[<?php echo $template_name; ?>][background]" 
                                                           value="<?php echo esc_attr( $background_color ); ?>" 
                                                           style="width: 4rem; height: 40px; border: 1px solid #ddd; border-radius: 6px; padding: 5px;"
                                                           data-template="<?php echo esc_attr( $template_name ); ?>"
                                                           data-color-type="background"
                                                           data-default="<?php echo esc_attr( $default_colors[$template_name]['background'] ); ?>"
                                                           class="template-color-input">
                                                </div>
                                                <div style="font-size: 10px; color: #999; margin-top: 3px;">Main background color</div>
                                            </div>
                                            <div>
                                                <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">🔘 Button Color:</label>
                                                <div style="position: relative; display: inline-block;">
                                                    <input type="color" name="bogo_template_colors[<?php echo $template_name; ?>][button_color]" 
                                                           value="<?php echo esc_attr( $button_color ); ?>" 
                                                           style="width: 4rem; height: 40px; border: 1px solid #ddd; border-radius: 6px; padding: 5px;"
                                                           data-template="<?php echo esc_attr( $template_name ); ?>"
                                                           data-color-type="button_color"
                                                           data-default="<?php echo esc_attr( $default_colors[$template_name]['button_color'] ); ?>"
                                                           class="template-color-input">
                                                </div>
                                                <div style="font-size: 10px; color: #999; margin-top: 3px;">Call-to-action button</div>
                                            </div>
                                        </div>
                                        <div style="margin-top: 10px; padding: 8px; background: #e3f2fd; border-radius: 4px; font-size: 11px; color: #1976d2;">
                                            💡 <strong>Smart Colors:</strong> Text colors are automatically calculated for optimal readability based on your choices.
                                        </div>
                                    </div>
                                    
                                    <?php
                                    // Include preview helper
                                    include_once plugin_dir_path(__FILE__) . 'templates/preview-helper.php';
                                    
                                    // Generate realistic frontend preview with unique ID
                                    $preview_id = 'preview-' . $template_name . '-' . $template_count;
                                    echo generate_bogo_preview(
                                        $template_name,
                                        $background_color,
                                        $text_color,
                                        $primary_color,
                                        $secondary_color,
                                        $button_bg_color,
                                        $button_text_color,
                                        $preview_id
                                    );
                                    ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- Commented out templates for future use -->
                            <?php /*
                            <?php foreach ( $available_templates as $template_name ) :
                                $template_count++;
                                if ($template_count <= 3) continue; // Skip first 3 templates
                                $template_info = $this->get_template_info( $template_name );
                            ?>
                            <div class="template-option" style="border: 2px solid #ddd; border-radius: 8px; padding: 20px; background: white; opacity: 0.5;">
                                <label style="display: block; cursor: pointer;">
                                    <input type="radio" name="bogo_template" value="<?php echo esc_attr( $template_name ); ?>" disabled style="margin-bottom: 10px;">
                                    <strong><?php echo esc_html( $template_info['name'] ); ?> (Coming Soon)</strong>
                                </label>
                                <div style="font-size: 12px; color: #666; margin-top: 8px;">
                                    <?php echo esc_html( $template_info['description'] ); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            */ ?>
                        </div>
                    </div>
                    <div class="bogo-actions">
                        <input type="submit" class="button-primary" value="Save UI Settings" style="background: #085d1b; border-color: #085d1b; color: #fff; font-weight: bold;">
                    </div>
                </form>
                
                <script>
                jQuery(document).ready(function($) {
                    // Handle color picker changes with instant preview
                    $('input[type="color"]').on('input change', function() {
                        var template = $(this).data('template');
                        var colorType = $(this).data('color-type');
                        var color = $(this).val();
                        var templateOption = $(this).closest('.template-option');
                        
                        // Update the color preview span
                        $(this).siblings('span').css('background-color', color);
                        
                        // Get all current color values
                        var themeColor = templateOption.find('input[data-color-type="theme_color"]').val();
                        var backgroundColor = templateOption.find('input[data-color-type="background"]').val();
                        var buttonColor = templateOption.find('input[data-color-type="button_color"]').val();
                        
                        // Calculate contrasting colors
                        var textColor = getContrastingColor(backgroundColor);
                        var buttonTextColor = getContrastingColor(buttonColor);
                        var primaryColor = themeColor;
                        var secondaryColor = adjustColorBrightness(themeColor, -20);
                        
                        // Update the preview container directly
                        var previewContainer = templateOption.find('.bogo-preview-container');
                        
                        if (previewContainer.length > 0) {
                            // Update background colors
                            previewContainer.find('[style*="background:"]').each(function() {
                                var currentStyle = $(this).attr('style');
                                if (currentStyle.includes('background:') && !currentStyle.includes('background: #fff') && !currentStyle.includes('background: #f8f9fa')) {
                                    $(this).attr('style', currentStyle.replace(/background:[^;]+;?/g, 'background: ' + backgroundColor + ';'));
                                }
                            });
                            
                            // Update text colors
                            previewContainer.find('h4, p').css('color', textColor);
                            
                            // Update primary colors (Buy/Get text)
                            previewContainer.find('strong').each(function() {
                                var text = $(this).text();
                                if (text.includes('Buy') || text.includes('Get') || text.includes('Bluetooth') || text.includes('Leather') || text.includes('Gaming')) {
                                    $(this).css('color', primaryColor);
                                }
                            });
                            
                            // Update secondary colors (Gift, FREE, off text)
                            previewContainer.find('strong, div').each(function() {
                                var text = $(this).text();
                                if (text.includes('Gift') || text.includes('FREE') || text.includes('off')) {
                                    $(this).css('color', secondaryColor);
                                }
                            });
                            
                            // Update button colors
                            previewContainer.find('button').css({
                                'background-color': buttonColor,
                                'color': buttonTextColor
                            });
                            
                            // Update border colors
                            previewContainer.find('img').each(function() {
                                var currentStyle = $(this).attr('style');
                                if (currentStyle.includes('border:')) {
                                    $(this).attr('style', currentStyle.replace(/border:[^;]+;?/g, 'border: 2px solid ' + primaryColor + ';'));
                                }
                            });
                        }
                        
                        // Add visual feedback
                        $(this).closest('div').addClass('color-changed');
                        setTimeout(function() {
                            $(this).closest('div').removeClass('color-changed');
                        }.bind(this), 200);
                    });
                    
                    // Function to get contrasting color
                    function getContrastingColor(hexColor) {
                        // Remove # if present
                        hexColor = hexColor.replace('#', '');
                        
                        // Convert to RGB
                        var r = parseInt(hexColor.substr(0, 2), 16);
                        var g = parseInt(hexColor.substr(2, 2), 16);
                        var b = parseInt(hexColor.substr(4, 2), 16);
                        
                        // Calculate luminance
                        var luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
                        
                        // Return black or white based on luminance
                        return luminance > 0.5 ? '#000000' : '#FFFFFF';
                    }
                    
                    // Function to adjust color brightness
                    function adjustColorBrightness(hex, percent) {
                        // Remove # if present
                        hex = hex.replace('#', '');
                        
                        // Convert to RGB
                        var r = parseInt(hex.substr(0, 2), 16);
                        var g = parseInt(hex.substr(2, 2), 16);
                        var b = parseInt(hex.substr(4, 2), 16);
                        
                        // Adjust brightness
                        r = Math.max(0, Math.min(255, r + (r * percent / 100)));
                        g = Math.max(0, Math.min(255, g + (g * percent / 100)));
                        b = Math.max(0, Math.min(255, b + (b * percent / 100)));
                        
                        // Convert back to hex
                        return '#' + Math.round(r).toString(16).padStart(2, '0') + 
                               Math.round(g).toString(16).padStart(2, '0') + 
                               Math.round(b).toString(16).padStart(2, '0');
                    }
                    
                    // Handle template selection changes
                    $('input[name="bogo_template"]').on('change', function() {
                        var selectedTemplate = $(this).val();
                        $('.template-option').removeClass('selected-template');
                        $(this).closest('.template-option').addClass('selected-template');
                    });
                    
                    // Initialize selected template
                    $('input[name="bogo_template"]:checked').closest('.template-option').addClass('selected-template');

                    // Handle reset colors button
                    $('.reset-colors-btn').on('click', function() {
                        var templateName = $(this).data('template');
                        var templateOption = $(this).closest('.template-option');
                        
                        // Default colors for each template
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
                        
                        var colors = defaultColors[templateName];
                        
                        // Reset all color inputs
                        templateOption.find('input[data-color-type="primary"]').val(colors.primary).trigger('change');
                        templateOption.find('input[data-color-type="secondary"]').val(colors.secondary).trigger('change');
                        templateOption.find('input[data-color-type="text"]').val(colors.text).trigger('change');
                        templateOption.find('input[data-color-type="background"]').val(colors.background).trigger('change');
                        templateOption.find('input[data-color-type="button_bg"]').val(colors.button_bg).trigger('change');
                        templateOption.find('input[data-color-type="button_text"]').val(colors.button_text).trigger('change');
                        
                        // Update color preview spans
                        templateOption.find('input[data-color-type="primary"]').siblings('span').css('background-color', colors.primary);
                        templateOption.find('input[data-color-type="secondary"]').siblings('span').css('background-color', colors.secondary);
                        templateOption.find('input[data-color-type="text"]').siblings('span').css('background-color', colors.text);
                        templateOption.find('input[data-color-type="background"]').siblings('span').css('background-color', colors.background);
                        templateOption.find('input[data-color-type="button_bg"]').siblings('span').css('background-color', colors.button_bg);
                        templateOption.find('input[data-color-type="button_text"]').siblings('span').css('background-color', colors.button_text);
                        
                        // Update preview section
                        var previewSection = templateOption.find('> div:last-child');
                        var previewButton = previewSection.find('span[data-button-bg]');
                        
                        previewSection.css('background-color', colors.background);
                        previewSection.find('strong, span:not([data-button-bg])').css('color', colors.text);
                        previewButton.css('background-color', colors.button_bg);
                        previewButton.css('color', colors.button_text);
                        
                        // Add visual feedback
                        $(this).text('✅ Reset!').css('background', '#d4edda').css('color', '#155724');
                        setTimeout(function() {
                            $(this).text('🔄 Reset Colors').css('background', '#f8f9fa').css('color', '#666');
                        }.bind(this), 1000);
                    });

                    // Add color change handlers for dynamic preview
                    $('.template-color-input').on('input change', function() {
                        var templateOption = $(this).closest('.template-option');
                        var templateKey = templateOption.data('template');
                        var colorType = $(this).data('color-type');
                        var colorValue = $(this).val();
                        
                        // Update the preview immediately
                        var previewSection = templateOption.find('> div:last-child');
                        var previewButton = previewSection.find('span[data-button-bg]');
                        
                        if (colorType === 'background') {
                            previewSection.css('background-color', colorValue);
                        } else if (colorType === 'text') {
                            previewSection.find('strong, span:not([data-button-bg])').css('color', colorValue);
                        } else if (colorType === 'button_bg') {
                            previewButton.css('background-color', colorValue);
                        } else if (colorType === 'button_text') {
                            previewButton.css('color', colorValue);
                        }
                        
                        // Add visual feedback for color changes
                        $(this).closest('.color-group').addClass('color-changed');
                        setTimeout(function() {
                            $(this).closest('.color-group').removeClass('color-changed');
                        }.bind(this), 200);
                        
                        // Note: Colors are saved when the form is submitted, no AJAX needed
                    });

                    // Add template selection handler
                    $('input[name="bogo_template"]').on('change', function() {
                        var selectedTemplate = $(this).val();
                        
                        // Update visual selection
                        $('.template-option').css('border-color', '#ddd');
                        $(this).closest('.template-option').css('border-color', '#007cba');
                    });

                    // Add color change handlers for dynamic preview
                });
                </script>
                
                <!-- Template and color picker styles are now in admin.css -->
            <?php endif; ?>

            <?php if ( $current_tab === 'reports' ) : ?>
                <div class="bogo-reports-container">
                    <h2>📊 BOGO Discount Reports</h2>
                    <p style="margin-bottom: 20px;">Track your BOGO discount performance, orders, and revenue analytics.</p>
                    
                    <?php echo $this->render_bogo_reports(); ?>
                </div>
            <?php endif; ?>
        </div>
        <!-- BOGO rules styles are now in admin.css -->
        <?php
    }


	public function display_bogo_message() {
		global $product;
		$should_bogo_message_display = false;
		$rules = get_option( self::OPTION_KEY, [] );
		$template_settings = get_option( self::TEMPLATE_OPTION_KEY, [] );
		
		// Ensure template_settings is an array (handle old string data)
		if ( !is_array( $template_settings ) ) {
			$template_settings = array();
		}
		
		$now = date( 'Y-m-d' );
		
		foreach ( $rules as $index => $rule ) {
			if ( ! empty( $rule['buy_product'] ) && ( $rule['buy_product'] === 'all' || intval( $rule['buy_product'] ) === $product->get_id() || in_array( $rule['buy_product'], $product->get_children() ) ) ) {
				$buy_qty     = intval( $rule['buy_qty'] );
				$get_qty     = intval( $rule['get_qty'] ) ?: 1;
				$get_product = wc_get_product( intval( $rule['get_product'] ) );
				$discount    = intval( $rule['discount'] );

				if ( isset( $rule['start_date'] ) && !empty( $rule['start_date'] ) && $rule['start_date'] > $now ) continue;
            	if ( isset( $rule['end_date'] ) && !empty( $rule['end_date'] ) && $rule['end_date'] < $now ) continue;

				if ( $get_product ) {
					$discount_text = ( $discount == 100 )
						? 'for free!'
						: "at {$discount}% off!";

					$get_image = $get_product->get_image( 'thumbnail' );
					$get_name  = $get_product->get_name();
					$current_product_id = $product->get_id();
					$buy_product_id = $rule['buy_product'] === 'all' ? $current_product_id : intval( $rule['buy_product'] );

					// Get the selected template (default to template1)
					$selected_template = isset( $template_settings['selected_template'] ) ? $template_settings['selected_template'] : 1;
					
					// Generate template based on selection
					echo '<div class="bogo-template-wrapper" data-product-id="' . $product->get_id() . '" data-rule-index="' . $index . '">';
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
						$index 
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
				'description' => 'Elegant card design and premium styling. Eye-catching and luxurious.',
				'preview_text' => 'Buy 2 → Get 1 FREE!',
				'button_text' => '✨ Claim Now!',
				'style' => 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px;'
			],
			'template3' => [
				'name' => '🚀 Dynamic Burst',
				'description' => 'Bold and vibrant design with beautiful colors. Perfect for sales and promotions.',
				'preview_text' => 'Limited Time: Buy More, Save More!',
				'button_text' => '🎯 Get Deal!',
				'style' => 'background: linear-gradient(45deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3); border-radius: 15px;'
			],
			'template4' => [
				'name' => '✨ Compact Elegant',
				'description' => 'Small, professional card with subtle animations. Perfect for minimal designs.',
				'preview_text' => 'Buy 2 → Get 1 | Premium Headphones',
				'button_text' => 'Claim Deal',
				'style' => 'background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%); border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px;'
			],
			'template5' => [
				'name' => '🌈 Modern Ribbon',
				'description' => 'A vibrant ribbon-style banner with a modern gradient and bold call-to-action. Stands out on any product page.',
				'preview_text' => 'Buy 2, Get 1 Free – Limited Time!',
				'button_text' => 'Unlock Deal',
				'style' => 'background: linear-gradient(90deg, #ff8a00 0%, #e52e71 100%); color: #fff; border-radius: 8px; padding: 14px 28px; box-shadow: 0 4px 16px rgba(229,46,113,0.15); font-weight: 600; letter-spacing: 0.5px;'
			],
			'template6' => [
				'name' => '🎯 Notification Strip',
				'description' => 'Sleek horizontal notification bar with modern design. Great for highlighting deals.',
				'preview_text' => 'Buy 2 items, get 1 Premium Headphones for free!',
				'button_text' => 'Add to Cart',
				'style' => 'background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);'
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

		$rules = get_option( self::OPTION_KEY, [] );
		$now   = date( 'Y-m-d' );

		foreach ( $rules as $index => $rule ) {
			if ( empty( $rule['get_product'] ) || empty( $rule['buy_qty'] ) ) {
				continue;
			}

			// Date validation
			if ( ! empty( $rule['start_date'] ) && $rule['start_date'] > $now ) {
				continue;
			}
			if ( ! empty( $rule['end_date'] ) && $rule['end_date'] < $now ) {
				continue;
			}

			$buy_product_id = $rule['buy_product']; // int or "all"
			$get_product_id = intval( $rule['get_product'] );
			$buy_qty        = intval( $rule['buy_qty'] );
			$get_qty        = intval( $rule['get_qty'] ) ?: 1;
			$discount       = intval( $rule['discount'] );

			$buy_count  = 0;
			$gift_key   = null;
			$gift_items = [];

			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
				// Identify gifts for this rule
				if ( isset( $cart_item['wc_advanced_bogo_gift'] ) && intval( $cart_item['wc_advanced_bogo_gift'] ) === $index ) {
					$gift_items[ $cart_item_key ] = $cart_item;
					continue;
				}

				// Count buy items
				$cart_product_id   = $cart_item['product_id'];
				$cart_variation_id = $cart_item['variation_id'];

				$matches_buy = (
					$buy_product_id === 'all'
					|| intval( $buy_product_id ) === $cart_product_id
					|| intval( $buy_product_id ) === $cart_variation_id
				);

				if ( $matches_buy ) {
					$buy_count += $cart_item['quantity'];
				}
			}

			// ✅ If buy condition is met
			if ( $buy_count >= $buy_qty ) {
				if ( empty( $gift_items ) ) {
					// Add gift
					$variation_id = 0;
					$variation    = [];

					$get_product = wc_get_product( $get_product_id );
					if ( $get_product && $get_product->is_type( 'variation' ) ) {
						$variation_id   = $get_product_id;
						$get_product_id = $get_product->get_parent_id();
						$variation      = $get_product->get_variation_attributes();
					}

					$cart->add_to_cart(
						$get_product_id,
						$get_qty,
						$variation_id,
						$variation,
						[ 'wc_advanced_bogo_gift' => $index ]
					);
				} else {
					// Update existing gifts (qty + price)
					foreach ( $gift_items as $cart_item_key => $cart_item ) {
						if ( $cart_item['quantity'] != $get_qty ) {
							$cart->set_quantity( $cart_item_key, $get_qty );
						}

						$product = wc_get_product( $cart_item['variation_id'] ?: $cart_item['product_id'] );
						if ( $product && is_object( $cart_item['data'] ) ) {
							$price         = $product->get_price();
							$safe_discount = min( 100, max( 0, $discount ) );
							$new_price     = max( 0, $price * ( 100 - $safe_discount ) / 100 );
							$cart_item['data']->set_price( $new_price );
						}
					}
				}
			}
			// ❌ If buy condition is NOT met
			else {
				if ( ! empty( $gift_items ) ) {
					// Remove all gifts for this rule
					foreach ( $gift_items as $cart_item_key => $cart_item ) {
						$cart->remove_cart_item( $cart_item_key );
					}
				}
			}
		}
	}




	public function maybe_remove_remove_link( $link, $cart_item_key ) {
		$cart = WC()->cart;
		$cart_item = $cart->get_cart_item( $cart_item_key );

		if ( isset( $cart_item['wc_advanced_bogo_gift'] ) ) {
			return ''; // Hide the remove link
		}

		return $link;
	}

	public function wc_advanced_bogo_lock_gift_qty( $product_quantity, $cart_item_key, $cart_item ) {
		if ( isset( $cart_item['wc_advanced_bogo_gift'] ) ) {
			return '<span class="bogo-gift-qty">' . esc_html( $cart_item['quantity'] ) . '</span>';
		}
		return $product_quantity;
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
			$product_id = intval( $buy_product );

			$product = wc_get_product( $product_id );
			if( $product->is_type('variable') ){
				$children   = $product->get_children();
				if( empty( $children ) ){
					wp_send_json_error( array( 'message' => 'Product not found. Please try again.' ) );
				}
				
				$product_id = $children[ array_rand( $children ) ];
				$product = wc_get_product( $product_id );
				
			}
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
		$rules = get_option( self::OPTION_KEY, [] );
		$now = date( 'Y-m-d' );
		
		foreach ( $rules as $index => $rule ) {
			if ( empty( $rule['get_product'] ) || empty( $rule['buy_qty'] ) ) {
				continue;
			}

			if ( isset( $rule['start_date'] ) && !empty( $rule['start_date'] ) && $rule['start_date'] > $now ) continue;
			if ( isset( $rule['end_date'] ) && !empty( $rule['end_date'] ) && $rule['end_date'] < $now ) continue;

			$buy_product_id = $rule['buy_product']; // may be 'all'
			$get_product_id = intval( $rule['get_product'] );
			$buy_qty = intval( $rule['buy_qty'] );
			$get_qty = intval( $rule['get_qty'] ) ?: 1;
			$discount = intval( $rule['discount'] );

			// Check if this cart item matches the buy product
			$cart_product_id   = $cart_item['product_id'];
            $cart_variation_id = $cart_item['variation_id'];

            $matches_buy = (
                $buy_product_id === 'all'
                || intval( $buy_product_id ) === $cart_product_id
                || intval( $buy_product_id ) === $cart_variation_id
            );
			if ( $matches_buy ) {
				// Count current BUY items in cart
				if( isset( $cart_item['wc_advanced_bogo_gift'] ) ) continue;
				

				// Check if customer is close to qualifying
				if ( $cart_item['quantity'] > 0 && $cart_item['quantity'] < $buy_qty ) {
					$remaining_qty = $buy_qty - $cart_item['quantity'];
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
		$rules = get_option( self::OPTION_KEY, [] );
		$now = date( 'Y-m-d' );
		$hint_html = '';
		
		foreach ( $rules as $index => $rule ) {
			if ( empty( $rule['get_product'] ) || empty( $rule['buy_qty'] ) ) {
				continue;
			}

			if ( isset( $rule['start_date'] ) && !empty( $rule['start_date'] ) && $rule['start_date'] > $now ) continue;
			if ( isset( $rule['end_date'] ) && !empty( $rule['end_date'] ) && $rule['end_date'] < $now ) continue;

			$buy_product_id = $rule['buy_product']; // may be 'all'
			$get_product_id = intval( $rule['get_product'] );
			$buy_qty = intval( $rule['buy_qty'] );
			$get_qty = intval( $rule['get_qty'] ) ?: 1;
			$discount = intval( $rule['discount'] );

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
	 * Render BOGO reports page content
	 */
	public function render_bogo_reports() {
		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : date( 'Y-m-01' );
		$date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : date( 'Y-m-d' );
		
		// Get BOGO orders data
		$bogo_data = $this->get_bogo_orders_data( $date_from, $date_to );
		
		ob_start();
		?>
		<div class="bogo-reports-wrapper">
			<!-- Date Range Filter -->
			<div class="bogo-date-filter" style="background: #fff; padding: 20px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 8px;">
				<h3>📅 Date Range Filter</h3>
				<form method="get" style="display: flex; align-items: center; gap: 15px;">
					<input type="hidden" name="page" value="wc-smart-bogo">
					<input type="hidden" name="tab" value="reports">
					<label>From: <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" style="margin-left: 5px;"></label>
					<label>To: <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" style="margin-left: 5px;"></label>
					<input type="submit" class="button-primary" value="Filter Reports" style="background: #3b82f6; border-color: #3b82f6;">
				</form>
			</div>

			<!-- Summary Cards -->
			<div class="bogo-summary-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
				<div class="summary-card" style="background: #f8f9fa; color: #333; padding: 20px; border-radius: 4px; border: 1px solid #dee2e6; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<div style="display: flex; align-items: center; justify-content: space-between;">
						<div>
							<h3 style="margin: 0; font-size: 14px; color: #666;">Total BOGO Orders</h3>
							<p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold; color: #333;"><?php echo number_format( $bogo_data['total_orders'] ); ?></p>
						</div>
						<div style="font-size: 32px; opacity: 0.8;">🛍️</div>
					</div>
				</div>

				<div class="summary-card" style="background: #e8f5e8; color: #333; padding: 20px; border-radius: 4px; border: 1px solid #c3e6c3; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<div style="display: flex; align-items: center; justify-content: space-between;">
						<div>
							<h3 style="margin: 0; font-size: 14px; color: #666;">Total Revenue</h3>
							<p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold; color: #333;"><?php echo wc_price( $bogo_data['total_revenue'] ); ?></p>
						</div>
						<div style="font-size: 32px; opacity: 0.8;">💰</div>
					</div>
				</div>

				<div class="summary-card" style="background: #fff3cd; color: #333; padding: 20px; border-radius: 4px; border: 1px solid #ffeaa7; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<div style="display: flex; align-items: center; justify-content: space-between;">
						<div>
							<h3 style="margin: 0; font-size: 14px; color: #666;">Total Discount Given</h3>
							<p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold; color: #333;"><?php echo wc_price( $bogo_data['total_discount'] ); ?></p>
						</div>
						<div style="font-size: 32px; opacity: 0.8;">🎁</div>
					</div>
				</div>

				<div class="summary-card" style="background: #d1ecf1; color: #333; padding: 20px; border-radius: 4px; border: 1px solid #bee5eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<div style="display: flex; align-items: center; justify-content: space-between;">
						<div>
							<h3 style="margin: 0; font-size: 14px; color: #666;">Gift Items Given</h3>
							<p style="margin: 5px 0 0 0; font-size: 28px; font-weight: bold; color: #333;"><?php echo number_format( $bogo_data['total_gift_items'] ); ?></p>
						</div>
						<div style="font-size: 32px; opacity: 0.8;">🎉</div>
					</div>
				</div>
			</div>

			<!-- Detailed Reports -->
			<div class="bogo-detailed-reports" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
				<!-- Top BOGO Rules -->
				<div class="report-section" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
					<h3>🏆 Top Performing BOGO Rules</h3>
					<table class="widefat striped">
						<thead>
							<tr>
								<th>Rule</th>
								<th>Orders</th>
								<th>Revenue</th>
								<th>Discount</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $bogo_data['top_rules'] as $rule_stat ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $rule_stat['rule_description'] ); ?></strong>
								</td>
								<td><?php echo number_format( $rule_stat['order_count'] ); ?></td>
								<td><?php echo wc_price( $rule_stat['revenue'] ); ?></td>
								<td><?php echo wc_price( $rule_stat['discount'] ); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<!-- Recent BOGO Orders -->
				<div class="report-section" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
					<h3>🕒 Recent BOGO Orders</h3>
					<table class="widefat striped">
						<thead>
							<tr>
								<th>Order #</th>
								<th>Date</th>
								<th>Total</th>
								<th>Discount</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $bogo_data['recent_orders'] as $order_data ) : ?>
							<tr>
								<td>
									<a href="<?php echo admin_url( 'post.php?post=' . $order_data['id'] . '&action=edit' ); ?>" target="_blank">
										#<?php echo $order_data['id']; ?>
									</a>
								</td>
								<td><?php echo date( 'M j, Y', strtotime( $order_data['date'] ) ); ?></td>
								<td><?php echo wc_price( $order_data['total'] ); ?></td>
								<td><?php echo wc_price( $order_data['discount'] ); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Monthly Performance Chart -->
			<div class="report-section" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px;">
				<h3>📈 Monthly BOGO Performance</h3>
				<canvas id="bogoChart" width="400" height="200"></canvas>
			</div>

			<!-- Detailed Orders List -->
			<div class="report-section" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
				<h3>📋 All BOGO Orders (<?php echo $date_from; ?> to <?php echo $date_to; ?>)</h3>
				<table class="widefat striped">
					<thead>
						<tr>
							<th>Order #</th>
							<th>Customer</th>
							<th>Date</th>
							<th>BOGO Items</th>
							<th>Order Total</th>
							<th>BOGO Discount</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $bogo_data['all_orders'] as $order_data ) : ?>
						<tr>
							<td>
								<a href="<?php echo admin_url( 'post.php?post=' . $order_data['id'] . '&action=edit' ); ?>" target="_blank">
									#<?php echo $order_data['id']; ?>
								</a>
							</td>
							<td><?php echo esc_html( $order_data['customer_name'] ); ?></td>
							<td><?php echo date( 'M j, Y H:i', strtotime( $order_data['date'] ) ); ?></td>
							<td>
								<div style="font-size: 12px;">
									<?php foreach ( $order_data['bogo_items'] as $bogo_item ) : ?>
										<div style="margin-bottom: 3px;">
											<?php if ( $bogo_item['type'] === 'gift' ) : ?>
												🎁 <strong><?php echo esc_html( $bogo_item['product_name'] ); ?></strong> (×<?php echo $bogo_item['quantity']; ?>) - Gift
											<?php else : ?>
												🛒 <?php echo esc_html( $bogo_item['product_name'] ); ?> (×<?php echo $bogo_item['quantity']; ?>) - Trigger
											<?php endif; ?>
										</div>
									<?php endforeach; ?>
								</div>
							</td>
							<td><?php echo wc_price( $order_data['total'] ); ?></td>
							<td style="color: #28a745; font-weight: bold;"><?php echo wc_price( $order_data['discount'] ); ?></td>
							<td>
								<span class="order-status status-<?php echo esc_attr( $order_data['status'] ); ?>" style="padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;">
									<?php echo esc_html( $order_data['status'] ); ?>
								</span>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>

		<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			const ctx = document.getElementById('bogoChart').getContext('2d');
			const chartData = <?php echo json_encode( $bogo_data['chart_data'] ); ?>;
			
			new Chart(ctx, {
				type: 'line',
				data: {
					labels: chartData.labels,
					datasets: [{
						label: 'BOGO Orders',
						data: chartData.orders,
						borderColor: '#3b82f6',
						backgroundColor: 'rgba(59, 130, 246, 0.1)',
						tension: 0.4
					}, {
						label: 'Revenue (<?php echo get_woocommerce_currency_symbol(); ?>)',
						data: chartData.revenue,
						borderColor: '#10b981',
						backgroundColor: 'rgba(16, 185, 129, 0.1)',
						tension: 0.4,
						yAxisID: 'y1'
					}]
				},
				options: {
					responsive: true,
					scales: {
						y: {
							beginAtZero: true,
							title: {
								display: true,
								text: 'Orders'
							}
						},
						y1: {
							type: 'linear',
							display: true,
							position: 'right',
							title: {
								display: true,
								text: 'Revenue'
							},
							grid: {
								drawOnChartArea: false,
							},
						}
					},
					plugins: {
						title: {
							display: true,
							text: 'BOGO Performance Over Time'
						}
					}
				}
			});
		});
		</script>

		<!-- Reports styles are now in admin.css -->
		<?php
		return ob_get_clean();
	}

	/**
	 * Get BOGO orders data for reports
	 */
	public function get_bogo_orders_data( $date_from, $date_to ) {
		global $wpdb;
		
		// Query orders with BOGO data
		$orders = wc_get_orders( [
			'limit' => -1,
			'status' => ['completed', 'processing', 'pending', 'on-hold'],
			'date_created' => $date_from . '...' . $date_to,
			'meta_key' => '_wc_advanced_bogo_items',
			'meta_compare' => 'EXISTS'
		] );

		$total_orders = count( $orders );
		$total_revenue = 0;
		$total_discount = 0;
		$total_gift_items = 0;
		$rule_stats = [];
		$recent_orders = [];
		$all_orders = [];
		$monthly_data = [];

		foreach ( $orders as $order ) {
			$order_total = $order->get_total();
			$bogo_discount = $order->get_meta( '_wc_advanced_bogo_discount_total' ) ?: 0;
			$bogo_items = $order->get_meta( '_wc_advanced_bogo_items' ) ?: [];
			$rules_applied = $order->get_meta( '_wc_advanced_bogo_rules_applied' ) ?: [];

			$total_revenue += $order_total;
			$total_discount += $bogo_discount;

			// Count gift items
			foreach ( $bogo_items as $item ) {
				if ( $item['type'] === 'gift' ) {
					$total_gift_items += $item['quantity'];
				}
			}

			// Track rule performance
			foreach ( $rules_applied as $rule ) {
				$rule_key = $rule['buy_product'] . '_' . $rule['get_product'];
				if ( !isset( $rule_stats[ $rule_key ] ) ) {
					$buy_product = wc_get_product( $rule['buy_product'] );
					$get_product = wc_get_product( $rule['get_product'] );
					$rule_stats[ $rule_key ] = [
						'rule_description' => sprintf( 
							'Buy %d %s, Get %d %s at %d%% off',
							$rule['buy_qty'],
							$buy_product ? $buy_product->get_name() : 'Product',
							$rule['get_qty'],
							$get_product ? $get_product->get_name() : 'Product',
							$rule['discount']
						),
						'order_count' => 0,
						'revenue' => 0,
						'discount' => 0
					];
				}
				$rule_stats[ $rule_key ]['order_count']++;
				$rule_stats[ $rule_key ]['revenue'] += $order_total;
				$rule_stats[ $rule_key ]['discount'] += $bogo_discount;
			}

			// Prepare order data
			$order_data = [
				'id' => $order->get_id(),
				'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				'date' => $order->get_date_created()->date( 'Y-m-d H:i:s' ),
				'total' => $order_total,
				'discount' => $bogo_discount,
				'status' => $order->get_status(),
				'bogo_items' => $bogo_items
			];

			$all_orders[] = $order_data;

			// Keep recent orders (last 10)
			if ( count( $recent_orders ) < 10 ) {
				$recent_orders[] = $order_data;
			}

			// Monthly data for chart
			$month_key = $order->get_date_created()->date( 'Y-m' );
			if ( !isset( $monthly_data[ $month_key ] ) ) {
				$monthly_data[ $month_key ] = [
					'orders' => 0,
					'revenue' => 0
				];
			}
			$monthly_data[ $month_key ]['orders']++;
			$monthly_data[ $month_key ]['revenue'] += $order_total;
		}

		// Sort rule stats by order count
		uasort( $rule_stats, function( $a, $b ) {
			return $b['order_count'] - $a['order_count'];
		} );

		// Sort orders by date (newest first)
		usort( $all_orders, function( $a, $b ) {
			return strtotime( $b['date'] ) - strtotime( $a['date'] );
		} );

		// Prepare chart data
		ksort( $monthly_data );
		$chart_labels = [];
		$chart_orders = [];
		$chart_revenue = [];

		foreach ( $monthly_data as $month => $data ) {
			$chart_labels[] = date( 'M Y', strtotime( $month . '-01' ) );
			$chart_orders[] = $data['orders'];
			$chart_revenue[] = round( $data['revenue'], 2 );
		}

		return [
			'total_orders' => $total_orders,
			'total_revenue' => $total_revenue,
			'total_discount' => $total_discount,
			'total_gift_items' => $total_gift_items,
			'top_rules' => array_slice( $rule_stats, 0, 5 ),
			'recent_orders' => $recent_orders,
			'all_orders' => $all_orders,
			'chart_data' => [
				'labels' => $chart_labels,
				'orders' => $chart_orders,
				'revenue' => $chart_revenue
			]
		];
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

		// CRITICAL SECURITY VALIDATION: Ensure discount never exceeds 100%
		$discount_value = intval( $rule_data['discount'] );
		if ( $discount_value > 100 ) {
			wp_send_json_error( array( 'message' => 'Security Error: Discount cannot exceed 100%. This prevents business losses from negative pricing.' ) );
		}
		if ( $discount_value < 0 ) {
			wp_send_json_error( array( 'message' => 'Discount cannot be negative. Please enter a value between 0 and 100.' ) );
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

		// Get existing rules
		$rules = get_option( self::OPTION_KEY, array() );

		// Update or add the rule
		if ( $rule_index >= 0 && $rule_index < count( $rules ) ) {
			// Update existing rule
			$rules[$rule_index] = $sanitized_rule;
			$action = 'updated';
		} else {
			// Add new rule
			$rules[] = $sanitized_rule;
			$action = 'added';
			$rule_index = count( $rules ) - 1;
		}

		// Save updated rules
		update_option( self::OPTION_KEY, $rules );

		// Get product names for response
		$buy_product_name = 'All Products';
		if ( $sanitized_rule['buy_product'] !== 'all' ) {
			$buy_product = wc_get_product( $sanitized_rule['buy_product'] );
			$buy_product_name = $buy_product ? $buy_product->get_name() : 'Unknown Product';
		}

		$get_product = wc_get_product( $sanitized_rule['get_product'] );
		$get_product_name = $get_product ? $get_product->get_name() : 'Unknown Product';

		wp_send_json_success( array(
			'message' => sprintf( 'Rule %s successfully!', $action ),
			'rule_index' => $rule_index,
			'rule_data' => $sanitized_rule,
			'buy_product_name' => $buy_product_name,
			'get_product_name' => $get_product_name,
			'action' => $action
		) );
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
