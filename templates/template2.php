<?php
/**
 * BOGO Template 2 - Premium Card Design
 * Elegant card design with glass-morphism effects and premium styling
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="bogo-offer-container my-6 mx-auto max-w-md relative" style="perspective: 1000px;">
    <div class="rounded-2xl shadow-2xl p-6 text-white relative overflow-hidden transform hover:scale-105 transition-all duration-300" style="background: <?php echo esc_attr( $background_color ); ?>;">
        
        <!-- Special Badge -->
        <div class="absolute top-0 right-0 text-white px-3 py-1 rounded-bl-xl text-xs font-bold animate-pulse" style="background: <?php echo esc_attr( $secondary_color ); ?>;">
            🔥 SPECIAL
        </div>
        
        <!-- Decorative Elements -->
        <div class="absolute top-4 left-4 w-8 h-8 rounded-full" style="background: <?php echo esc_attr( $primary_color ); ?>; opacity: 0.2;"></div>
        <div class="absolute bottom-6 right-6 w-12 h-12 rounded-full" style="background: <?php echo esc_attr( $secondary_color ); ?>; opacity: 0.1;"></div>
        
        <div class="relative z-10">
            <div class="flex items-center mb-4">
                <div class="relative w-20 h-20 mr-4">
                    <?php echo str_replace('class="', 'class="rounded-xl shadow-lg ', $get_image); ?>
                    <div class="absolute -top-2 -right-2 text-xs font-bold px-2 py-1 rounded-full" style="background: <?php echo esc_attr( $secondary_color ); ?>; color: <?php echo esc_attr( $text_color ); ?>;">
                        💎 FREE
                    </div>
                </div>
                <div class="flex-grow">
                    <h3 class="text-xl font-bold mb-2" style="color: <?php echo esc_attr( $text_color ); ?>;">💎 Exclusive BOGO Deal!</h3>
                    <p class="text-sm" style="color: <?php echo esc_attr( $text_color ); ?>;">
                        Buy <span class="font-bold" style="color: <?php echo esc_attr( $primary_color ); ?>;"><?php echo $buy_qty; ?></span> → Get <span class="font-bold" style="color: <?php echo esc_attr( $secondary_color ); ?>;"><?php echo $get_qty; ?></span>
                        <br><span style="color: <?php echo esc_attr( $text_color ); ?>;"><?php echo esc_html( $get_name ); ?></span> <strong style="color: <?php echo esc_attr( $secondary_color ); ?>;"><?php echo esc_html( $discount_text ); ?></strong>
                    </p>
                </div>
            </div>
            
            <button 
                class="grab-bogo-offer-btn w-full font-bold py-3 px-6 rounded-xl shadow-lg transform hover:scale-105 transition-all duration-200 text-sm"
                style="background: <?php echo esc_attr( $button_bg_color ); ?>; color: <?php echo esc_attr( $button_text_color ); ?>; backdrop-filter: blur(10px);"
                <?php echo $common_button_data; ?>
            >
                <div class="flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    ✨ Claim Now!
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </button>
        </div>
    </div>
    <?php echo $loading_spinner; ?>
</div>