<?php
/**
 * Plugin Name: WooCommerce Advanced BOGO
 * Description: Adds advanced BOGO (Buy One Get One) functionality to WooCommerce.
 * Version: 1.0.1
 * Author: Your Name
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
			
			// Add cart blocks compatibility
			add_action( 'init', [ $this, 'register_cart_blocks_hints' ] );
			
			// Add content filters for cart blocks
			add_filter( 'woocommerce_blocks_cart_item_block_content', [ $this, 'add_cart_blocks_hint_content' ], 10, 2 );
			
			// Add render hooks for cart blocks
			add_action( 'woocommerce_blocks_cart_item_block_render', [ $this, 'add_cart_blocks_hint_render' ], 10, 2 );
			
			// Add content filter for cart item names (works with blocks)
			add_filter( 'woocommerce_cart_item_name', [ $this, 'add_cart_item_hint_to_name' ], 10, 3 );
		}
	}

	/**
	 * Register cart blocks hints
	 */
	public function register_cart_blocks_hints() {
		// Add BOGO hints to cart blocks using Store API extensions
		add_filter( 'woocommerce_store_api_cart_item_data', [ $this, 'add_bogo_data_to_cart_item' ], 10, 2 );
		
		// Add JavaScript for cart blocks
		add_action( 'wp_footer', [ $this, 'add_cart_blocks_js' ] );
	}

	/**
	 * Add BOGO data to cart item for Store API
	 */
	public function add_bogo_data_to_cart_item( $cart_item_data, $cart_item ) {
		$rules = get_option( self::OPTION_KEY, [] );
		$now = date( 'Y-m-d' );
		$bogo_hint = '';
		
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
						
						$bogo_hint = array(
							'remaining_qty' => $remaining_qty,
							'get_qty' => $get_qty,
							'get_product_name' => $get_product->get_name(),
							'discount_text' => $discount_text,
							'rule_index' => $index,
							'html' => '<div style="margin-top: 8px; padding: 8px; background: #f0f9ff; border-left: 3px solid #3b82f6; border-radius: 4px; font-size: 12px; color: #1e40af; font-weight: 600;">
								🎁 Add <strong>' . $remaining_qty . ' more</strong> and get <strong>' . $get_qty . 'x ' . esc_html( $get_product->get_name() ) . '</strong> ' . esc_html( $discount_text ) . '
							</div>'
						);
						break;
					}
				}
			}
		}
		
		if ( $bogo_hint ) {
			// Add to extensions field for Store API
			if ( ! isset( $cart_item_data['extensions'] ) ) {
				$cart_item_data['extensions'] = array();
			}
			$cart_item_data['extensions']['wc_advanced_bogo'] = $bogo_hint;
		}
		
		return $cart_item_data;
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
	 * Add cart blocks hints
	 */
	public function add_cart_blocks_hints( $registry ) {
		// This method is kept for compatibility but not used
	}

	/**
	 * Add cart item blocks hints
	 */
	public function add_cart_item_blocks_hints( $registry ) {
		// This method is kept for compatibility but not used
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
			// Enqueue Tailwind CSS from CDN
			wp_enqueue_style(
				'wc-advanced-bogo-tailwind',
				'https://cdn.tailwindcss.com',
				array(),
				'3.0.0'
			);

			// Enqueue frontend JavaScript
			wp_enqueue_script(
				'wc-advanced-bogo-frontend',
				plugin_dir_url( __FILE__ ) . 'frontend.js',
				array( 'jquery' ),
				'1.0.0',
				true
			);

			// Enqueue cart blocks script for cart and checkout pages
			if ( is_cart() || is_checkout() ) {
				wp_enqueue_script(
					'wc-advanced-bogo-cart-blocks',
					plugin_dir_url( __FILE__ ) . 'cart-blocks.js',
					array( 'jquery' ),
					'1.0.0',
					true
				);
			}

			// Localize script with AJAX data
			wp_localize_script( 'wc-advanced-bogo-frontend', 'wc_advanced_bogo_ajax', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'wc_advanced_bogo_nonce' ),
				'cartUrl' => wc_get_cart_url()
			) );

			// Also localize for cart blocks script
			if ( is_cart() || is_checkout() ) {
				wp_localize_script( 'wc-advanced-bogo-cart-blocks', 'wc_advanced_bogo_ajax', array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'wc_advanced_bogo_nonce' ),
					'cartUrl' => wc_get_cart_url()
				) );
			}
		}
	}

	public function enqueue_admin_scripts( $hook ) {
		// Only load on our BOGO settings page
		if ( $hook === 'woocommerce_page_wc-advanced-bogo' ) {
			wp_enqueue_script( 
				'wc-advanced-bogo-admin', 
				plugin_dir_url(__FILE__) . 'admin.js', 
				['jquery'], 
				filemtime( plugin_dir_path(__FILE__) . 'admin.js' ), 
				true 
			);
			
			// Enqueue WooCommerce product search scripts
			wp_enqueue_script( 'woocommerce_admin' );
			wp_enqueue_script( 'wc-enhanced-select' );
			wp_enqueue_style( 'woocommerce_admin_styles' );
		}
	}


    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Advanced BOGO',
            'Advanced BOGO',
            'manage_woocommerce',
            'wc-advanced-bogo',
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
                        $filtered_rules[] = [
                            'buy_product' => sanitize_text_field( $rule['buy_product'] ),
                            'buy_qty'     => intval( $rule['buy_qty'] ),
                            'get_product' => intval( $rule['get_product'] ),
                            'get_qty'     => intval( $rule['get_qty'] ) ?: 1,
                            'discount'    => intval( $rule['discount'] ),
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
            update_option( self::TEMPLATE_OPTION_KEY, sanitize_text_field( $_POST['bogo_template'] ) );
            
            // Save color palette settings
            if ( isset( $_POST['bogo_template_colors'] ) && is_array( $_POST['bogo_template_colors'] ) ) {
                foreach ( $_POST['bogo_template_colors'] as $template_name => $colors ) {
                    if ( is_array( $colors ) ) {
                        foreach ( $colors as $color_type => $color_value ) {
                            $option_name = "bogo_template_{$template_name}_{$color_type}_color";
                            update_option( $option_name, sanitize_hex_color( $color_value ) );
                        }
                    }
                }
            }
            
            echo '<div class="updated"><p>BOGO message template and color settings saved successfully!</p></div>';
        }

        $rules = get_option( self::OPTION_KEY, [] );
        $selected_template = get_option( self::TEMPLATE_OPTION_KEY, 'template1' );
        
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
        <div class="wrap">
            <h1>Advanced BOGO</h1>
            
            <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
                <a href="<?php echo admin_url( 'admin.php?page=wc-advanced-bogo&tab=rules' ); ?>" 
                   class="nav-tab <?php echo $current_tab === 'rules' ? 'nav-tab-active' : ''; ?>">
                    Discount Rules
                </a>
                <a href="<?php echo admin_url( 'admin.php?page=wc-advanced-bogo&tab=ui-settings' ); ?>" 
                   class="nav-tab <?php echo $current_tab === 'ui-settings' ? 'nav-tab-active' : ''; ?>">
                    UI Settings
                </a>
            </nav>

            <?php if ( $current_tab === 'rules' ) : ?>
                <form method="post" id="bogo-rules-form">
                    <?php wp_nonce_field( 'save_bogo_rules' ); ?>
                    <div class="bogo-rules-container">
                        <h2>💰 BOGO Discount Rules</h2>
                        <p style="margin-bottom: 20px;">Create rules in plain language. Example: <em>Buy 2 units of T-shirt and get 2 Hat at 50% off</em></p>
                        
                        <table class="widefat bogo-rules-sentence-table" id="bogo-rules-table" style="margin-left: 20px;">
                            <thead>
                                <tr>
                                    <th style="width: 100%;">Rule</th>
                                    <th style="min-width: 60px;"></th>
                                </tr>
                            </thead>
                            <tbody id="bogo-rules-tbody">
                                <?php foreach ( $rules as $index => $rule ) : ?>
                                <tr class="bogo-rule-row" data-index="<?php echo $index; ?>">
                                    <td style="font-size: 16px; font-weight: 500; padding: 20px 0;">
                                        <span style="margin-right: 8px;">🛒 Buy</span>
                                        <input type="number" name="bogo_rules[<?php echo $index; ?>][buy_qty]" value="<?php echo esc_attr( $rule['buy_qty'] ); ?>" min="1" required style="width: 70px; display: inline-block; height: 35px; padding: 8px; font-size: 14px;" placeholder="e.g. 2" />
                                        <span style="margin: 0 8px;">units of</span>
                                        <select name="bogo_rules[<?php echo $index; ?>][buy_product]" class="wc-product-search" data-placeholder="Search for a product..." required style="min-width: 200px; display: inline-block; height: 35px;">
                                            <option value="">Search for a product...</option>
                                            <option value="all" <?php selected( $rule['buy_product'], 'all' ); ?>>— All Products —</option>
                                            <?php if ( !empty( $rule['buy_product'] ) && $rule['buy_product'] !== 'all' ) : 
                                                $buy_product = wc_get_product( $rule['buy_product'] );
                                                if ( $buy_product ) : ?>
                                                <option value="<?php echo esc_attr( $rule['buy_product'] ); ?>" selected><?php echo esc_html( $buy_product->get_name() ); ?></option>
                                            <?php endif; endif; ?>
                                        </select>
                                        <span style="margin: 0 8px;">and get</span>
                                        <input type="number" name="bogo_rules[<?php echo $index; ?>][get_qty]" value="<?php echo esc_attr( $rule['get_qty'] ?: '1' ); ?>" min="1" required style="width: 70px; display: inline-block; height: 35px; padding: 8px; font-size: 14px;" placeholder="e.g. 2" />
                                        <select name="bogo_rules[<?php echo $index; ?>][get_product]" class="wc-product-search" data-placeholder="Search for a product..." required style="min-width: 200px; display: inline-block; height: 35px;">
                                            <option value="">Search for a product...</option>
                                            <?php if ( !empty( $rule['get_product'] ) ) : 
                                                $get_product = wc_get_product( $rule['get_product'] );
                                                if ( $get_product ) : ?>
                                                <option value="<?php echo esc_attr( $rule['get_product'] ); ?>" selected><?php echo esc_html( $get_product->get_name() ); ?></option>
                                            <?php endif; endif; ?>
                                        </select>
                                        <span style="margin: 0 8px;">at</span>
                                        <input type="number" name="bogo_rules[<?php echo $index; ?>][discount]" value="<?php echo esc_attr( $rule['discount'] ); ?>" min="0" max="100" required style="width: 70px; display: inline-block; height: 35px; padding: 8px; font-size: 14px;" placeholder="e.g. 50" />
                                        <span style="margin-left: 4px;">% off</span>
                                        <span style="margin: 0 8px; font-size: 14px; color: #666;">📅 Start:</span>
                                        <input type="date" name="bogo_rules[<?php echo $index; ?>][start_date]" value="<?php echo esc_attr( $rule['start_date'] ?? '' ); ?>" style="width: 150px; display: inline-block; height: 35px; padding: 8px; font-size: 14px; margin-right: 8px;" />
                                        <span style="margin: 0 8px; font-size: 14px; color: #666;">📅 End:</span>
                                        <input type="date" name="bogo_rules[<?php echo $index; ?>][end_date]" value="<?php echo esc_attr( $rule['end_date'] ?? '' ); ?>" style="width: 150px; display: inline-block; height: 35px; padding: 8px; font-size: 14px;" />
                                    </td>
                                    <td style="text-align: center; vertical-align: top; padding-top: 20px;">
                                        <button type="button" class="button remove-bogo-rule" title="Remove this rule" style="color: #dc3545; border-color: #dc3545; background: transparent; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="bogo-actions" style="margin-top: 20px; margin-left: 20px;">
                        <button type="button" id="add-bogo-rule" class="button add-bogo-rule">
                            + Add New Rule
                        </button>
                        <input type="submit" class="button-primary" value="Save Discount Rules" style="margin-left: 10px; background: #28a745; border-color: #28a745; color: white;">
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
                                    
                                    // Default attractive color combinations for each template
                                    $default_colors = array(
                                        'template1' => array(
                                            'primary' => '#3B82F6',
                                            'secondary' => '#10B981', 
                                            'text' => '#1F2937',
                                            'background' => '#F8FAFC',
                                            'button_bg' => '#3B82F6',
                                            'button_text' => '#FFFFFF'
                                        ),
                                        'template2' => array(
                                            'primary' => '#8B5CF6',
                                            'secondary' => '#EC4899',
                                            'text' => '#FFFFFF',
                                            'background' => '#1E1B4B',
                                            'button_bg' => '#EC4899',
                                            'button_text' => '#FFFFFF'
                                        ),
                                        'template3' => array(
                                            'primary' => '#F59E0B',
                                            'secondary' => '#EF4444',
                                            'text' => '#FFFFFF',
                                            'background' => '#7C2D12',
                                            'button_bg' => '#F59E0B',
                                            'button_text' => '#FFFFFF'
                                        )
                                    );
                                    
                                    // Get saved colors or use defaults
                                    $primary_color = get_option( "bogo_template_{$template_name}_primary_color", $default_colors[$template_name]['primary'] );
                                    $secondary_color = get_option( "bogo_template_{$template_name}_secondary_color", $default_colors[$template_name]['secondary'] );
                                    $text_color = get_option( "bogo_template_{$template_name}_text_color", $default_colors[$template_name]['text'] );
                                    $background_color = get_option( "bogo_template_{$template_name}_background_color", $default_colors[$template_name]['background'] );
                                    $button_bg_color = get_option( "bogo_template_{$template_name}_button_bg_color", $default_colors[$template_name]['button_bg'] );
                                    $button_text_color = get_option( "bogo_template_{$template_name}_button_text_color", $default_colors[$template_name]['button_text'] );
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
                                    
                                    <!-- Color Palette Settings -->
                                    <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                                        <h4 style="margin: 0 0 15px 0; font-size: 14px; color: #333;">🎨 Color Settings</h4>
                                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                                            <div>
                                                <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">Primary Color:</label>
                                                <div style="position: relative; display: inline-block;">
                                                    <span style="display: inline-block; width: 30px; height: 30px; border-radius: 4px; border: 2px solid #ddd; background-color: <?php echo esc_attr( $primary_color ); ?>; vertical-align: middle; margin-right: 8px;"></span>
                                                    <input type="color" name="bogo_template_colors[<?php echo $template_name; ?>][primary]" 
                                                           value="<?php echo esc_attr( $primary_color ); ?>" 
                                                           style="width: 100%; height: 35px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; font-size: 12px;"
                                                           data-template="<?php echo esc_attr( $template_name ); ?>"
                                                           data-color-type="primary"
                                                           data-default="<?php echo esc_attr( $default_colors[$template_name]['primary'] ); ?>">
                                                </div>
                                            </div>
                                            <div>
                                                <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">Secondary Color:</label>
                                                <div style="position: relative; display: inline-block;">
                                                    <span style="display: inline-block; width: 30px; height: 30px; border-radius: 4px; border: 2px solid #ddd; background-color: <?php echo esc_attr( $secondary_color ); ?>; vertical-align: middle; margin-right: 8px;"></span>
                                                    <input type="color" name="bogo_template_colors[<?php echo $template_name; ?>][secondary]" 
                                                           value="<?php echo esc_attr( $secondary_color ); ?>" 
                                                           style="width: 100%; height: 35px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; font-size: 12px;"
                                                           data-template="<?php echo esc_attr( $template_name ); ?>"
                                                           data-color-type="secondary"
                                                           data-default="<?php echo esc_attr( $default_colors[$template_name]['secondary'] ); ?>">
                                                </div>
                                            </div>
                                            <div>
                                                <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">Text Color:</label>
                                                <div style="position: relative; display: inline-block;">
                                                    <span style="display: inline-block; width: 30px; height: 30px; border-radius: 4px; border: 2px solid #ddd; background-color: <?php echo esc_attr( $text_color ); ?>; vertical-align: middle; margin-right: 8px;"></span>
                                                    <input type="color" name="bogo_template_colors[<?php echo $template_name; ?>][text]" 
                                                           value="<?php echo esc_attr( $text_color ); ?>" 
                                                           style="width: 100%; height: 35px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; font-size: 12px;"
                                                           data-template="<?php echo esc_attr( $template_name ); ?>"
                                                           data-color-type="text"
                                                           data-default="<?php echo esc_attr( $default_colors[$template_name]['text'] ); ?>">
                                                </div>
                                            </div>
                                            <div>
                                                <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">Background Color:</label>
                                                <div style="position: relative; display: inline-block;">
                                                    <span style="display: inline-block; width: 30px; height: 30px; border-radius: 4px; border: 2px solid #ddd; background-color: <?php echo esc_attr( $background_color ); ?>; vertical-align: middle; margin-right: 8px;"></span>
                                                    <input type="color" name="bogo_template_colors[<?php echo $template_name; ?>][background]" 
                                                           value="<?php echo esc_attr( $background_color ); ?>" 
                                                           style="width: 100%; height: 35px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; font-size: 12px;"
                                                           data-template="<?php echo esc_attr( $template_name ); ?>"
                                                           data-color-type="background"
                                                           data-default="<?php echo esc_attr( $default_colors[$template_name]['background'] ); ?>">
                                                </div>
                                            </div>
                                            <div>
                                                <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">Button Background:</label>
                                                <div style="position: relative; display: inline-block;">
                                                    <span style="display: inline-block; width: 30px; height: 30px; border-radius: 4px; border: 2px solid #ddd; background-color: <?php echo esc_attr( $button_bg_color ); ?>; vertical-align: middle; margin-right: 8px;"></span>
                                                    <input type="color" name="bogo_template_colors[<?php echo $template_name; ?>][button_bg]" 
                                                           value="<?php echo esc_attr( $button_bg_color ); ?>" 
                                                           style="width: 100%; height: 35px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; font-size: 12px;"
                                                           data-template="<?php echo esc_attr( $template_name ); ?>"
                                                           data-color-type="button_bg"
                                                           data-default="<?php echo esc_attr( $default_colors[$template_name]['button_bg'] ); ?>">
                                                </div>
                                            </div>
                                            <div>
                                                <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">Button Text:</label>
                                                <div style="position: relative; display: inline-block;">
                                                    <span style="display: inline-block; width: 30px; height: 30px; border-radius: 4px; border: 2px solid #ddd; background-color: <?php echo esc_attr( $button_text_color ); ?>; vertical-align: middle; margin-right: 8px;"></span>
                                                    <input type="color" name="bogo_template_colors[<?php echo $template_name; ?>][button_text]" 
                                                           value="<?php echo esc_attr( $button_text_color ); ?>" 
                                                           style="width: 100%; height: 35px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; font-size: 12px;"
                                                           data-template="<?php echo esc_attr( $template_name ); ?>"
                                                           data-color-type="button_text"
                                                           data-default="<?php echo esc_attr( $default_colors[$template_name]['button_text'] ); ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-top: 15px; padding: 15px; background: <?php echo esc_attr( $background_color ); ?>; border-radius: 6px; position: relative;">
                                        <?php if ( $template_name === 'template2' || $template_name === 'template3' ) : ?>
                                            <div style="position: absolute; top: -5px; right: -5px; background: #ff4757; color: white; padding: 2px 6px; border-radius: 10px; font-size: 9px;">🔥 SPECIAL</div>
                                        <?php endif; ?>
                                        <strong style="color: <?php echo esc_attr( $text_color ); ?>;"><?php echo $template_name === 'template2' ? '💎 Exclusive BOGO Deal!' : ($template_name === 'template3' ? '🚀 MEGA BOGO BLAST!' : '🎉 Special BOGO Offer!'); ?></strong><br>
                                        <span style="color: <?php echo esc_attr( $text_color ); ?>; font-size: 11px;"><?php echo esc_html( $template_info['preview_text'] ); ?></span><br>
                                        <span style="background: <?php echo esc_attr( $button_bg_color ); ?>; color: <?php echo esc_attr( $button_text_color ); ?>; padding: 6px 12px; border-radius: 4px; margin-top: 8px; display: inline-block; font-size: 11px; font-weight: bold;"
                                              data-button-bg="<?php echo esc_attr( $button_bg_color ); ?>"
                                              data-button-text="<?php echo esc_attr( $button_text_color ); ?>">
                                            <?php echo esc_html( $template_info['button_text'] ); ?>
                                        </span>
                                    </div>
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
                        <input type="submit" class="button-primary" value="Save UI Settings" style="background: #28a745; border-color: #28a745; color: white;">
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
                });
                </script>
                
                <style>
                .template-option {
                    transition: all 0.3s ease;
                }
                .template-option.selected-template {
                    border-color: #007cba !important;
                    box-shadow: 0 0 0 1px #007cba;
                }
                .color-changed {
                    animation: colorPulse 0.2s ease-in-out;
                }
                @keyframes colorPulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.02); }
                    100% { transform: scale(1); }
                }
                input[type="color"] {
                    cursor: pointer;
                }
                input[type="color"]:hover {
                    transform: scale(1.05);
                    transition: transform 0.2s ease;
                }
                </style>
            <?php endif; ?>
        </div>
        <style>
            .remove-bogo-rule { transition: all 0.2s ease; border-radius: 4px !important; }
            .remove-bogo-rule:hover { cursor: pointer; box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3); }
            .add-bogo-rule { background: #007cba !important; border-color: #007cba !important; color: white !important; margin-left: 10px; }
            .add-bogo-rule:hover { background: #005a87 !important; border-color: #005a87 !important; }
            .bogo-rules-container { margin-bottom: 20px; }
            .no-rules-message { text-align: center; padding: 20px; background: #f9f9f9; border: 1px dashed #ccc; margin: 10px 0; }
        </style>
        <?php
    }


	public function display_bogo_message() {
		global $product;

		$rules = get_option( self::OPTION_KEY, [] );
		$template = get_option( self::TEMPLATE_OPTION_KEY, 'template1' );
		$now = date( 'Y-m-d' );
		
		foreach ( $rules as $index => $rule ) {
			if ( ! empty( $rule['buy_product'] ) && ( $rule['buy_product'] === 'all' || intval( $rule['buy_product'] ) === $product->get_id() ) ) {
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

					// Generate template based on selection
					echo $this->get_bogo_template( 
						$template, 
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
				}
			}
		}
	}

	private function get_bogo_template( $template, $buy_qty, $get_qty, $get_name, $discount_text, $get_image, $buy_product_id, $get_product_id, $discount, $index ) {
		// Get saved color settings for this template
		$primary_color = get_option( "bogo_template_{$template}_primary_color", '#3B82F6' );
		$secondary_color = get_option( "bogo_template_{$template}_secondary_color", '#10B981' );
		$text_color = get_option( "bogo_template_{$template}_text_color", '#1F2937' );
		$background_color = get_option( "bogo_template_{$template}_background_color", '#F8FAFC' );
		$button_bg_color = get_option( "bogo_template_{$template}_button_bg_color", '#3B82F6' );
		$button_text_color = get_option( "bogo_template_{$template}_button_text_color", '#FFFFFF' );
		
		// Default colors for each template if no custom colors are set
		$default_colors = array(
			'template1' => array(
				'primary' => '#3B82F6',
				'secondary' => '#10B981', 
				'text' => '#1F2937',
				'background' => '#F8FAFC',
				'button_bg' => '#3B82F6',
				'button_text' => '#FFFFFF'
			),
			'template2' => array(
				'primary' => '#8B5CF6',
				'secondary' => '#EC4899',
				'text' => '#FFFFFF',
				'background' => '#1E1B4B',
				'button_bg' => '#EC4899',
				'button_text' => '#FFFFFF'
			),
			'template3' => array(
				'primary' => '#F59E0B',
				'secondary' => '#EF4444',
				'text' => '#FFFFFF',
				'background' => '#7C2D12',
				'button_bg' => '#F59E0B',
				'button_text' => '#FFFFFF'
			)
		);
		
		// Use default colors if no custom colors are saved
		if ( !get_option( "bogo_template_{$template}_primary_color" ) ) {
			$primary_color = $default_colors[$template]['primary'];
			$secondary_color = $default_colors[$template]['secondary'];
			$text_color = $default_colors[$template]['text'];
			$background_color = $default_colors[$template]['background'];
			$button_bg_color = $default_colors[$template]['button_bg'];
			$button_text_color = $default_colors[$template]['button_text'];
		}
		
		return $this->load_template( $template, [
			'buy_qty' => $buy_qty,
			'get_qty' => $get_qty,
			'get_name' => $get_name,
			'discount_text' => $discount_text,
			'get_image' => $get_image,
			'buy_product_id' => $buy_product_id,
			'get_product_id' => $get_product_id,
			'discount' => $discount,
			'index' => $index,
			'primary_color' => $primary_color,
			'secondary_color' => $secondary_color,
			'text_color' => $text_color,
			'background_color' => $background_color,
			'button_bg_color' => $button_bg_color,
			'button_text_color' => $button_text_color,
			'common_button_data' => 'data-buy-product="' . esc_attr( $buy_product_id ) . '"
				data-buy-qty="' . esc_attr( $buy_qty ) . '"
				data-get-product="' . esc_attr( $get_product_id ) . '"
				data-get-qty="' . esc_attr( $get_qty ) . '"
				data-discount="' . esc_attr( $discount ) . '"
				data-rule-index="' . esc_attr( $index ) . '"',
			'loading_spinner' => '<div class="bogo-offer-loading hidden mt-3 text-center">
				<div class="inline-flex items-center" style="color: ' . esc_attr( $primary_color ) . ';">
					<svg class="animate-spin -ml-1 mr-3 h-5 w-5" style="color: ' . esc_attr( $primary_color ) . ';" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
						<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
						<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
					</svg>
					Adding to cart...
				</div>
			</div>'
		]);
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




    /*public function display_bogo_message() {
        global $product;

        $rules = get_option( self::OPTION_KEY, [] );
        foreach ( $rules as $rule ) {
            if ( ! empty( $rule['buy_product'] ) && intval( $rule['buy_product'] ) === $product->get_id() ) {
                $buy_qty = intval( $rule['buy_qty'] );
				$get_qty = intval( $rule['get_qty'] ) ?: 1;
				$get_product = wc_get_product( intval( $rule['get_product'] ) );
				$discount = intval( $rule['discount'] );

				if ( $get_product ) {
					$discount_text = ( $discount == 100 )
						? 'for free'
						: "at {$discount}% off";

					echo '<div class="woocommerce-message">';
					echo sprintf(
						'Buy %d of this product and get %d of %s %s.',
						$buy_qty,
						$get_qty,
						$get_product->get_name(),
						$discount_text
					);
					echo '</div>';
				}
            }
        }
    }*/

    public function apply_bogo_discount( $cart ) {

	    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
	        return;
	    }

	    $rules = get_option( self::OPTION_KEY, [] );
	    $now = date( 'Y-m-d' );
		// echo '<pre>';
		// print_r($rules);
		// exit();

		foreach ( $rules as $index => $rule ) {
			if ( empty( $rule['get_product'] ) || empty( $rule['buy_qty'] ) ) {
				continue;
			}

			if ( isset( $rule['start_date'] ) && !empty( $rule['start_date'] ) && $rule['start_date'] > $now ) continue;
            if ( isset( $rule['end_date'] ) && !empty( $rule['end_date'] ) && $rule['end_date'] < $now ) continue;

			$buy_product_id = $rule['buy_product']; // may be 'all'
			$get_product_id = intval( $rule['get_product'] );
			$buy_qty        = intval( $rule['buy_qty'] );
			$get_qty        = intval( $rule['get_qty'] ) ?: 1;
			$discount       = intval( $rule['discount'] );

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
			$gift_key = 'wc_advanced_bogo_gift_' . $index;

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
					wp_send_json_error( array( 'message' => 'Product not found' ) );
				}
				$product_id = $current_product_id;
			} else {
				$product_id = intval( $buy_product );
			}

			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				wp_send_json_error( array( 'message' => 'Product not found' ) );
			}

			// Check if product is in stock
			if ( ! $product->is_in_stock() ) {
				wp_send_json_error( array( 'message' => 'Product is out of stock' ) );
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
				wp_send_json_error( array( 'message' => 'Failed to add product to cart' ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
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
	 * Add BOGO hint to cart item name (works with blocks)
	 */
	public function add_cart_item_hint_to_name( $name, $cart_item, $cart_item_key ) {
		// Only add hints for cart blocks, not classic cart
		if ( ! $this->is_cart_blocks() ) {
			return $name;
		}
		
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
						
						$name .= $hint;
						break;
					}
				}
			}
		}
		
		return $name;
	}

	/**
	 * Check if we're using cart blocks
	 */
	private function is_cart_blocks() {
		// Check if cart blocks are being used
		return has_block( 'woocommerce/cart' ) || 
			   has_block( 'woocommerce/checkout' ) || 
			   ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'cart' ) && has_block( 'woocommerce/cart' ) );
	}

	/**
	 * Add cart blocks hint render
	 */
	public function add_cart_blocks_hint_render( $block_content, $block ) {
		// This is a placeholder for render hooks
		return $block_content;
	}

	/**
	 * Add cart blocks hint to block content
	 */
	public function add_cart_blocks_hint_to_block( $block_content, $block ) {
		// Only process cart item blocks
		if ( $block['blockName'] !== 'woocommerce/cart-item' ) {
			return $block_content;
		}
		
		// Extract product ID from block attributes or content
		preg_match( '/data-product-id="(\d+)"/', $block_content, $matches );
		$product_id = isset( $matches[1] ) ? intval( $matches[1] ) : 0;
		
		if ( ! $product_id ) {
			return $block_content;
		}
		
		// Get BOGO hint for this product
		$hint = $this->get_bogo_hint_for_product( $product_id );
		
		if ( $hint ) {
			// Insert hint after the product name
			$block_content = preg_replace(
				'/(<div[^>]*class="[^"]*cart-item-name[^"]*"[^>]*>.*?<\/div>)/s',
				'$1' . $hint,
				$block_content
			);
		}
		
		return $block_content;
	}

	/**
	 * Get BOGO hint for a specific product
	 */
	private function get_bogo_hint_for_product( $product_id ) {
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
						
						return '<div style="margin-top: 8px; padding: 8px; background: #f0f9ff; border-left: 3px solid #3b82f6; border-radius: 4px; font-size: 12px; color: #1e40af; font-weight: 600;">
							🎁 Add <strong>' . $remaining_qty . ' more</strong> and get <strong>' . $get_qty . 'x ' . esc_html( $get_product->get_name() ) . '</strong> ' . esc_html( $discount_text ) . '
						</div>';
					}
				}
			}
		}
		
		return '';
	}

}

new WC_Advanced_BOGO();
