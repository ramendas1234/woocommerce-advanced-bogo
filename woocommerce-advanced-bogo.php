<?php
/**
 * Plugin Name: WooCommerce Advanced BOGO
 * Description: Adds advanced BOGO (Buy One Get One) functionality to WooCommerce.
 * Version: 1.0
 * Author: Your Name
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Advanced_BOGO {

    const OPTION_KEY = 'wc_advanced_bogo_rules';

    public function __construct() {
        // Only load if WooCommerce is active
        if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
            add_action( 'admin_init', [ $this, 'register_settings' ] );
            add_action( 'woocommerce_single_product_summary', [ $this, 'display_bogo_message' ], 25 );
            add_action( 'woocommerce_before_calculate_totals', [ $this, 'apply_bogo_discount' ], 10, 1 );
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
			add_filter( 'woocommerce_cart_item_remove_link', [ $this, 'maybe_remove_remove_link' ], 10, 2 );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
			
			// AJAX handlers for grab offer functionality
			add_action( 'wp_ajax_grab_bogo_offer', [ $this, 'handle_grab_bogo_offer' ] );
			add_action( 'wp_ajax_nopriv_grab_bogo_offer', [ $this, 'handle_grab_bogo_offer' ] );

        }
    }

	public function enqueue_assets() {
		wp_enqueue_style(
			'wc-advanced-bogo-tailwind',
			'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css',
			[],
			'2.2.19'
		);
		
		// Only enqueue on product pages
		if ( is_product() ) {
			$frontend_js_path = plugin_dir_path(__FILE__) . 'frontend.js';
			$version = file_exists( $frontend_js_path ) ? filemtime( $frontend_js_path ) : '1.0.0';
			
			wp_enqueue_script(
				'wc-advanced-bogo-frontend',
				plugin_dir_url(__FILE__) . 'frontend.js',
				['jquery'],
				$version,
				true
			);
			
			// Localize script for AJAX
			wp_localize_script( 'wc-advanced-bogo-frontend', 'bogoAjax', [
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'bogo_grab_offer_nonce' ),
				'cartUrl' => wc_get_cart_url(),
			]);
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
    }

    public function settings_page() {
        $rules = get_option( self::OPTION_KEY, [] );

        if ( isset( $_POST['bogo_rules'] ) ) {
            check_admin_referer( 'save_bogo_rules' );
            
            // Filter out empty rules and reindex
            $filtered_rules = [];
            if ( is_array( $_POST['bogo_rules'] ) ) {
                foreach ( $_POST['bogo_rules'] as $rule ) {
                    // Only save rules that have at least buy_product and get_product set
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
            echo '<div class="updated"><p>BOGO rules saved successfully!</p></div>';
        }

        $products = wc_get_products([
            'limit' => -1,
            'status' => 'publish',
        ]);
        
        // Ensure we have at least one empty rule for display
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
        <style>
            .remove-bogo-rule {
                transition: all 0.2s ease;
                border-radius: 4px !important;
            }
            .remove-bogo-rule:hover {
                cursor: pointer;
                box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
            }
            .add-bogo-rule {
                background: #007cba !important;
                border-color: #007cba !important;
                color: white !important;
                margin-left: 10px;
            }
            .add-bogo-rule:hover {
                background: #005a87 !important;
                border-color: #005a87 !important;
            }
            .bogo-rules-container {
                margin-bottom: 20px;
            }
            .no-rules-message {
                text-align: center;
                padding: 20px;
                background: #f9f9f9;
                border: 1px dashed #ccc;
                margin: 10px 0;
            }
        </style>
        <div class="wrap">
            <h1>WooCommerce Advanced BOGO</h1>
            <form method="post" id="bogo-rules-form">
                <?php wp_nonce_field( 'save_bogo_rules' ); ?>

                <div class="bogo-rules-container">
                    <table class="widefat" id="bogo-rules-table">
                        <thead>
                            <tr>
                                <th>Buy Product</th>
                                <th>Buy Quantity</th>
                                <th>Get Product</th>
                                <th>Get Quantity</th>
                                <th>Discount (%)</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="bogo-rules-tbody">
                            <?php foreach ( $rules as $index => $rule ) : ?>
                            <tr class="bogo-rule-row" data-index="<?php echo $index; ?>">
                                <td>
                                    <select name="bogo_rules[<?php echo $index; ?>][buy_product]" required>
                                        <option value="">— Select Product —</option>
                                        <option value="all" <?php selected( $rule['buy_product'], 'all' ); ?>>— All Products —</option>
                                        <?php foreach ( $products as $product ) : ?>
                                            <option value="<?php echo $product->get_id(); ?>"
                                                <?php selected( $rule['buy_product'], $product->get_id() ); ?>>
                                                <?php echo esc_html( $product->get_name() ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="bogo_rules[<?php echo $index; ?>][buy_qty]"
                                        value="<?php echo esc_attr( $rule['buy_qty'] ); ?>" min="1" required />
                                </td>
                                <td>
                                    <select name="bogo_rules[<?php echo $index; ?>][get_product]" required>
                                        <option value="">— Select Product —</option>
                                        <?php foreach ( $products as $product ) : ?>
                                            <option value="<?php echo $product->get_id(); ?>"
                                                <?php selected( $rule['get_product'], $product->get_id() ); ?>>
                                                <?php echo esc_html( $product->get_name() ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="bogo_rules[<?php echo $index; ?>][get_qty]"
                                        value="<?php echo esc_attr( $rule['get_qty'] ?: '1' ); ?>" min="1" />
                                </td>
                                <td>
                                    <input type="number" name="bogo_rules[<?php echo $index; ?>][discount]"
                                        value="<?php echo esc_attr( $rule['discount'] ); ?>" min="0" max="100" required />
                                </td>
                                <td>
                                    <input type="date" name="bogo_rules[<?php echo $index; ?>][start_date]" 
                                        value="<?php echo esc_attr( $rule['start_date'] ?? '' ); ?>" />
                                </td>
                                <td>
                                    <input type="date" name="bogo_rules[<?php echo $index; ?>][end_date]" 
                                        value="<?php echo esc_attr( $rule['end_date'] ?? '' ); ?>" />
                                </td>
                                <td>
                                    <button type="button" class="button remove-bogo-rule" title="Remove this rule" 
                                        style="color: #dc3545; border-color: #dc3545; background: transparent; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                        <span style="font-size: 16px; font-weight: bold;">×</span>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="bogo-actions">
                    <button type="button" id="add-bogo-rule" class="button add-bogo-rule">
                        + Add New Rule
                    </button>
                    <input type="submit" class="button-primary" value="Save Rules" style="margin-left: 10px;">
                </div>
            </form>
                </div>


 
        <?php
    }


	public function display_bogo_message() {
		global $product;

		$rules = get_option( self::OPTION_KEY, [] );
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

					echo '
						<div class="bogo-offer-container my-4 p-4 border border-gray-200 rounded-lg shadow-lg bg-white" style="background: linear-gradient(135deg, #fff 0%, #f8f9ff 100%);">
							<div class="flex items-center gap-4">
								<div class="relative w-24 h-24 flex-shrink-0">
									' . $get_image . '
									<div class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-bl">
										🎁 Gift
									</div>
								</div>
								<div class="flex-grow">
									<h3 class="text-lg font-bold mb-1 text-gray-800">🎉 Special BOGO Offer!</h3>
									<p class="text-gray-700 text-sm mb-3">
										Buy <span class="font-semibold text-blue-600">' . $buy_qty . '</span> of this product and get 
										<span class="font-semibold text-green-600">' . $get_qty . '</span> of 
										<span class="font-semibold text-purple-600">' . esc_html( $get_name ) . '</span> 
										<span class="font-bold text-red-600">' . esc_html( $discount_text ) . '</span>
									</p>
									<button 
										class="grab-bogo-offer-btn inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-500 to-blue-600 hover:from-green-600 hover:to-blue-700 text-white font-bold rounded-lg shadow-md hover:shadow-lg transform hover:scale-105 transition-all duration-200 text-sm"
										data-buy-product="' . esc_attr( $buy_product_id ) . '"
										data-buy-qty="' . esc_attr( $buy_qty ) . '"
										data-get-product="' . esc_attr( $get_product->get_id() ) . '"
										data-get-qty="' . esc_attr( $get_qty ) . '"
										data-discount="' . esc_attr( $discount ) . '"
										data-rule-index="' . esc_attr( $index ) . '"
									>
										<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
										</svg>
										🛒 Grab This Offer!
										<svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
										</svg>
									</button>
								</div>
							</div>
							<div class="bogo-offer-loading hidden mt-3 text-center">
								<div class="inline-flex items-center text-blue-600">
									<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
										<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
										<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
									</svg>
									Adding to cart...
								</div>
							</div>
						</div>
					';
				}
			}
		}
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
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'bogo_grab_offer_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$buy_product_id = intval( $_POST['buy_product'] );
		$buy_qty = intval( $_POST['buy_qty'] );
		$get_product_id = intval( $_POST['get_product'] );
		$get_qty = intval( $_POST['get_qty'] );
		$discount = intval( $_POST['discount'] );

		// Validate products exist
		$buy_product = wc_get_product( $buy_product_id );
		$get_product = wc_get_product( $get_product_id );

		if ( ! $buy_product || ! $get_product ) {
			wp_send_json_error( [
				'message' => 'Invalid products specified.'
			] );
		}

		// Check if products are in stock
		if ( ! $buy_product->is_in_stock() || ! $get_product->is_in_stock() ) {
			wp_send_json_error( [
				'message' => 'One or more products are out of stock.'
			] );
		}

		try {
			// Add buy product to cart
			$buy_cart_item_key = WC()->cart->add_to_cart( $buy_product_id, $buy_qty );
			
			if ( ! $buy_cart_item_key ) {
				wp_send_json_error( [
					'message' => 'Failed to add main product to cart.'
				] );
			}

			// Add get product to cart (this will be handled by our BOGO logic automatically)
			// The apply_bogo_discount function will detect the buy product and add the gift

			// Get cart contents count for response
			$cart_count = WC()->cart->get_cart_contents_count();
			
			wp_send_json_success( [
				'message' => 'BOGO offer added to cart successfully!',
				'cart_count' => $cart_count,
				'buy_product_name' => $buy_product->get_name(),
				'get_product_name' => $get_product->get_name(),
				'cart_url' => wc_get_cart_url()
			] );

		} catch ( Exception $e ) {
			wp_send_json_error( [
				'message' => 'Error adding products to cart: ' . $e->getMessage()
			] );
		}
	}

}

new WC_Advanced_BOGO();
