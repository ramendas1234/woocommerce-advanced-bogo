<?php
/**
 * BOGO Template 3 - Dynamic Burst Design
 * Bold and vibrant design with attention-grabbing colors and animations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<style>
@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
</style>

<div class="bogo-offer-container my-4 relative">
    <div class="p-1 rounded-2xl shadow-xl animate-pulse" style="background: linear-gradient(45deg, <?php echo esc_attr( $primary_color ); ?>, <?php echo esc_attr( $secondary_color ); ?>); background-size: 400% 400%; animation: gradientShift 3s ease infinite;">
        <div class="backdrop-filter backdrop-blur-xl rounded-2xl p-4 relative overflow-hidden" style="background: <?php echo esc_attr( $background_color ); ?>;">
            
            <!-- Animated Background Elements -->
            <div class="absolute inset-0 opacity-15">
                <div class="absolute top-2 left-4 w-4 h-4 rounded-full animate-bounce" style="background: <?php echo esc_attr( $primary_color ); ?>;"></div>
                <div class="absolute top-8 right-6 w-3 h-3 rounded-full animate-ping" style="background: <?php echo esc_attr( $secondary_color ); ?>;"></div>
                <div class="absolute bottom-4 left-8 w-6 h-6 rounded-full animate-pulse" style="background: <?php echo esc_attr( $primary_color ); ?>;"></div>
            </div>
            
            <div class="relative z-10">
                <div class="text-center mb-3">
                    <div class="inline-block px-3 py-1 rounded-full text-xs font-black mb-2 animate-bounce" style="background: <?php echo esc_attr( $secondary_color ); ?>; color: <?php echo esc_attr( $text_color ); ?>;">
                        🚀 MEGA BOGO BLAST!
                    </div>
                    <h3 class="text-lg font-black mb-1" style="color: <?php echo esc_attr( $text_color ); ?>;">
                        LIMITED TIME OFFER!
                    </h3>
                </div>
                
                <div class="flex items-center justify-center mb-4">
                    <div class="relative mr-3">
                        <?php echo str_replace('class="', 'class="rounded-xl shadow-lg border-2 ', $get_image); ?>
                    </div>
                    <div class="text-center">
                        <div class="text-base font-bold mb-1" style="color: <?php echo esc_attr( $text_color ); ?>;">
                            Buy <span class="text-xl font-black" style="color: <?php echo esc_attr( $primary_color ); ?>;"><?php echo $buy_qty; ?></span> 
                            → Get <span class="text-xl font-black" style="color: <?php echo esc_attr( $secondary_color ); ?>;"><?php echo $get_qty; ?></span>
                        </div>
                        <div class="text-xs" style="color: <?php echo esc_attr( $text_color ); ?>;">
                            <span class="font-semibold" style="color: <?php echo esc_attr( $primary_color ); ?>;"><?php echo esc_html( $get_name ); ?></span>
                            <br><strong style="color: <?php echo esc_attr( $secondary_color ); ?>;"><?php echo esc_html( $discount_text ); ?></strong>
                        </div>
                    </div>
                </div>
                
                <button 
                    class="grab-bogo-offer-btn w-full font-black py-3 px-6 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 text-sm relative overflow-hidden"
                    style="background: <?php echo esc_attr( $button_bg_color ); ?>; color: <?php echo esc_attr( $button_text_color ); ?>; background-size: 200% 200%; animation: gradientShift 2s ease infinite;"
                    <?php echo $common_button_data; ?>
                >
                    <div class="absolute inset-0 bg-white opacity-20 transform -skew-x-12 -translate-x-full hover:translate-x-full transition-transform duration-700"></div>
                    <div class="flex items-center justify-center relative z-10">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        🎯 GET THIS DEAL NOW!
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </div>
                </button>
            </div>
        </div>
    </div>
    <?php echo $loading_spinner; ?>
</div>