<?php
/**
 * BOGO Template Preview Helper
 * Generates realistic frontend previews for admin template selection
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Generate realistic frontend preview for BOGO templates
 */
function generate_bogo_preview( $template_name, $background_color, $text_color, $primary_color, $secondary_color, $button_bg_color, $button_text_color ) {
    // Dummy product data for realistic preview
    $dummy_products = array(
        'template1' => array(
            'buy_product' => 'Premium Wireless Headphones',
            'get_product' => 'Bluetooth Earbuds',
            'get_image' => 'https://images.unsplash.com/photo-1606220945770-b5b6c2c55bf1?w=80&h=80&fit=crop&crop=center'
        ),
        'template2' => array(
            'buy_product' => 'Luxury Watch',
            'get_product' => 'Leather Strap',
            'get_image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=80&h=80&fit=crop&crop=center'
        ),
        'template3' => array(
            'buy_product' => 'Gaming Laptop',
            'get_product' => 'Gaming Mouse',
            'buy_image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=80&h=80&fit=crop&crop=center',
            'get_image' => 'https://images.unsplash.com/photo-1527864550417-7f91c4a76ddd?w=80&h=80&fit=crop&crop=center'
        )
    );
    
    $dummy_data = $dummy_products[$template_name];
    $buy_qty = 2;
    $get_qty = 1;
    $discount_text = 'at 50% off';
    
    ob_start();
    ?>
    <!-- Realistic Frontend Preview -->
    <div style="margin-top: 15px; padding: 0; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; background: #fff;">
        <div style="padding: 12px; background: #f8f9fa; border-bottom: 1px solid #e0e0e0; font-size: 11px; color: #666; font-weight: 600;">
            📱 Frontend Preview
        </div>
        <div style="padding: 15px; background: #fff;">
            <?php if ( $template_name === 'template1' ) : ?>
                <!-- Template 1 Preview -->
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: <?php echo esc_attr( $background_color ); ?>; border-radius: 6px;">
                    <div style="position: relative; width: 60px; height: 60px; flex-shrink: 0;">
                        <img src="<?php echo $dummy_data['get_image']; ?>" alt="Product" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px; border: 2px solid <?php echo esc_attr( $primary_color ); ?>;">
                        <div style="position: absolute; top: -2px; right: -2px; background: <?php echo esc_attr( $secondary_color ); ?>; color: white; font-size: 8px; padding: 2px 4px; border-radius: 3px; font-weight: bold;">🎁 Gift</div>
                    </div>
                    <div style="flex-grow: 1;">
                        <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: bold; color: <?php echo esc_attr( $text_color ); ?>;">🎉 Special BOGO Offer!</h4>
                        <p style="margin: 0 0 8px 0; font-size: 11px; color: <?php echo esc_attr( $text_color ); ?>; line-height: 1.3;">
                            Buy <strong style="color: <?php echo esc_attr( $primary_color ); ?>;"><?php echo $buy_qty; ?></strong> of this product and get 
                            <strong style="color: <?php echo esc_attr( $secondary_color ); ?>;"><?php echo $get_qty; ?></strong> of 
                            <strong style="color: <?php echo esc_attr( $primary_color ); ?>;"><?php echo $dummy_data['get_product']; ?></strong> 
                            <strong style="color: <?php echo esc_attr( $secondary_color ); ?>;"><?php echo $discount_text; ?></strong>
                        </p>
                        <button style="background: <?php echo esc_attr( $button_bg_color ); ?>; color: <?php echo esc_attr( $button_text_color ); ?>; padding: 6px 12px; border: none; border-radius: 4px; font-size: 10px; font-weight: bold; cursor: pointer;">
                            🛒 Grab This Offer!
                        </button>
                    </div>
                </div>
                
            <?php elseif ( $template_name === 'template2' ) : ?>
                <!-- Template 2 Preview -->
                <div style="padding: 16px; background: <?php echo esc_attr( $background_color ); ?>; border-radius: 8px; border: 2px solid <?php echo esc_attr( $primary_color ); ?>; position: relative;">
                    <div style="position: absolute; top: -2px; right: -2px; background: #ff4757; color: white; padding: 2px 6px; border-radius: 8px; font-size: 8px; font-weight: bold;">🔥 SPECIAL</div>
                    <div style="display: flex; align-items: center; margin-bottom: 12px;">
                        <div style="position: relative; width: 50px; height: 50px; margin-right: 12px;">
                            <img src="<?php echo $dummy_data['get_image']; ?>" alt="Product" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
                            <div style="position: absolute; -top-2px; -right-2px; background: <?php echo esc_attr( $secondary_color ); ?>; color: <?php echo esc_attr( $text_color ); ?>; font-size: 7px; padding: 1px 3px; border-radius: 4px; font-weight: bold;">💎 FREE</div>
                        </div>
                        <div style="flex-grow: 1;">
                            <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: bold; color: <?php echo esc_attr( $text_color ); ?>;">💎 Exclusive BOGO Deal!</h4>
                            <p style="margin: 0; font-size: 11px; color: <?php echo esc_attr( $text_color ); ?>; line-height: 1.3;">
                                Buy <strong style="color: <?php echo esc_attr( $primary_color ); ?>;"><?php echo $buy_qty; ?></strong> → Get <strong style="color: <?php echo esc_attr( $secondary_color ); ?>;"><?php echo $get_qty; ?></strong><br>
                                <span style="color: <?php echo esc_attr( $text_color ); ?>;"><?php echo $dummy_data['get_product']; ?></span> <strong style="color: <?php echo esc_attr( $secondary_color ); ?>;"><?php echo $discount_text; ?></strong>
                            </p>
                        </div>
                    </div>
                    <button style="width: 100%; background: <?php echo esc_attr( $button_bg_color ); ?>; color: <?php echo esc_attr( $button_text_color ); ?>; padding: 8px; border: none; border-radius: 6px; font-size: 11px; font-weight: bold; cursor: pointer;">
                        ✨ Claim Now!
                    </button>
                </div>
                
            <?php elseif ( $template_name === 'template3' ) : ?>
                <!-- Template 3 Preview -->
                <div style="padding: 12px; background: <?php echo esc_attr( $background_color ); ?>; border-radius: 8px; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: -2px; right: -2px; background: #ff4757; color: white; padding: 2px 6px; border-radius: 8px; font-size: 8px; font-weight: bold;">🔥 SPECIAL</div>
                    <div style="text-align: center; margin-bottom: 12px;">
                        <h4 style="margin: 0 0 8px 0; font-size: 16px; font-weight: bold; color: <?php echo esc_attr( $text_color ); ?>;">🚀 MEGA BOGO BLAST!</h4>
                        <div style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; border: 2px solid <?php echo esc_attr( $primary_color ); ?>;">
                                <img src="<?php echo $dummy_data['buy_image']; ?>" alt="Buy Product" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <span style="font-size: 12px; color: <?php echo esc_attr( $text_color ); ?>; font-weight: bold;">+</span>
                            <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; border: 2px solid <?php echo esc_attr( $secondary_color ); ?>;">
                                <img src="<?php echo $dummy_data['get_image']; ?>" alt="Get Product" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        </div>
                        <p style="margin: 0 0 8px 0; font-size: 11px; color: <?php echo esc_attr( $text_color ); ?>;">
                            Buy <strong style="color: <?php echo esc_attr( $primary_color ); ?>;"><?php echo $buy_qty; ?></strong> items, get <strong style="color: <?php echo esc_attr( $secondary_color ); ?>;"><?php echo $get_qty; ?></strong> <strong style="color: <?php echo esc_attr( $primary_color ); ?>;"><?php echo $dummy_data['get_product']; ?></strong> <strong style="color: <?php echo esc_attr( $secondary_color ); ?>;"><?php echo $discount_text; ?></strong>
                        </p>
                    </div>
                    <button style="width: 100%; background: <?php echo esc_attr( $button_bg_color ); ?>; color: <?php echo esc_attr( $button_text_color ); ?>; padding: 8px; border: none; border-radius: 6px; font-size: 11px; font-weight: bold; cursor: pointer;">
                        🎯 Get Deal!
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}