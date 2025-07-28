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

<div class="bogo-offer-container my-6 relative">
    <div class="bg-gradient-to-r from-pink-500 via-red-500 to-yellow-500 p-1 rounded-3xl shadow-2xl animate-pulse" style="background: linear-gradient(45deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3); background-size: 400% 400%; animation: gradientShift 3s ease infinite;">
        <div class="bg-black bg-opacity-80 backdrop-filter backdrop-blur-xl rounded-3xl p-6 relative overflow-hidden">
            
            <!-- Animated Background Elements -->
            <div class="absolute inset-0 opacity-20">
                <div class="absolute top-4 left-6 w-6 h-6 bg-yellow-400 rounded-full animate-bounce"></div>
                <div class="absolute top-12 right-8 w-4 h-4 bg-pink-400 rounded-full animate-ping"></div>
                <div class="absolute bottom-8 left-12 w-8 h-8 bg-blue-400 rounded-full animate-pulse"></div>
            </div>
            
            <div class="relative z-10">
                <div class="text-center mb-4">
                    <div class="inline-block bg-gradient-to-r from-yellow-400 to-red-500 text-black px-4 py-2 rounded-full text-xs font-black mb-3 animate-bounce">
                        🚀 MEGA BOGO BLAST!
                    </div>
                    <h3 class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 via-red-500 to-pink-500 mb-2">
                        LIMITED TIME OFFER!
                    </h3>
                </div>
                
                <div class="flex items-center justify-center mb-6">
                    <div class="relative mr-4">
                        <?php echo str_replace('class="', 'class="rounded-2xl shadow-2xl border-4 border-yellow-400 ', $get_image); ?>
                        <div class="absolute -top-3 -right-3 bg-gradient-to-r from-green-400 to-blue-500 text-white text-xs font-bold px-3 py-1 rounded-full animate-spin" style="animation: spin 3s linear infinite;">
                            🎯 FREE!
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-white text-lg font-bold mb-2">
                            Buy <span class="text-yellow-400 text-2xl font-black"><?php echo $buy_qty; ?></span> 
                            → Get <span class="text-green-400 text-2xl font-black"><?php echo $get_qty; ?></span>
                        </div>
                        <div class="text-gray-300 text-sm">
                            <span class="text-cyan-400 font-semibold"><?php echo esc_html( $get_name ); ?></span>
                            <br><strong class="text-yellow-400"><?php echo esc_html( $discount_text ); ?></strong>
                        </div>
                    </div>
                </div>
                
                <button 
                    class="grab-bogo-offer-btn w-full bg-gradient-to-r from-red-500 via-yellow-500 to-pink-500 text-white font-black py-4 px-8 rounded-2xl shadow-2xl hover:shadow-3xl transform hover:scale-110 transition-all duration-300 text-lg relative overflow-hidden"
                    <?php echo $common_button_data; ?>
                    style="background: linear-gradient(45deg, #ff6b6b, #4ecdc4); background-size: 200% 200%; animation: gradientShift 2s ease infinite;"
                >
                    <div class="absolute inset-0 bg-white opacity-20 transform -skew-x-12 -translate-x-full hover:translate-x-full transition-transform duration-700"></div>
                    <div class="flex items-center justify-center relative z-10">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        🎯 GET THIS DEAL NOW!
                        <svg class="w-6 h-6 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </div>
                </button>
            </div>
        </div>
    </div>
    <?php echo str_replace('text-blue-600', 'text-yellow-500', $loading_spinner); ?>
</div>