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
    <div class="bg-gradient-to-br from-purple-600 via-blue-600 to-purple-800 rounded-2xl shadow-2xl p-6 text-white relative overflow-hidden transform hover:scale-105 transition-all duration-300" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        
        <!-- Special Badge -->
        <div class="absolute top-0 right-0 bg-gradient-to-r from-red-500 to-pink-500 text-white px-3 py-1 rounded-bl-xl text-xs font-bold animate-pulse">
            🔥 SPECIAL
        </div>
        
        <!-- Decorative Elements -->
        <div class="absolute top-4 left-4 w-8 h-8 bg-white bg-opacity-20 rounded-full"></div>
        <div class="absolute bottom-6 right-6 w-12 h-12 bg-white bg-opacity-10 rounded-full"></div>
        
        <div class="relative z-10">
            <div class="flex items-center mb-4">
                <div class="relative w-20 h-20 mr-4">
                    <?php echo str_replace('class="', 'class="rounded-xl shadow-lg ', $get_image); ?>
                    <div class="absolute -top-2 -right-2 bg-yellow-400 text-purple-800 text-xs font-bold px-2 py-1 rounded-full">
                        💎 FREE
                    </div>
                </div>
                <div class="flex-grow">
                    <h3 class="text-xl font-bold mb-2 text-white">💎 Exclusive BOGO Deal!</h3>
                    <p class="text-purple-100 text-sm">
                        Buy <span class="font-bold text-yellow-300"><?php echo $buy_qty; ?></span> → Get <span class="font-bold text-green-300"><?php echo $get_qty; ?></span>
                        <br><span class="text-purple-200"><?php echo esc_html( $get_name ); ?></span> <strong><?php echo esc_html( $discount_text ); ?></strong>
                    </p>
                </div>
            </div>
            
            <button 
                class="grab-bogo-offer-btn w-full bg-white bg-opacity-20 backdrop-filter backdrop-blur-lg border border-white border-opacity-30 text-white font-bold py-3 px-6 rounded-xl shadow-lg hover:bg-opacity-30 transform hover:scale-105 transition-all duration-200 text-sm"
                <?php echo $common_button_data; ?>
                style="backdrop-filter: blur(10px);"
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
    <?php echo str_replace('text-blue-600', 'text-purple-600', $loading_spinner); ?>
</div>