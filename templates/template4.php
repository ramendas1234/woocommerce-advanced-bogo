<?php
/**
 * BOGO Template 4 - Compact Elegant Card
 * Small, professional card with subtle animations and clean design
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="bogo-offer-container max-w-sm mx-auto my-3 relative group">
    <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 p-4 border border-gray-100 relative overflow-hidden group-hover:transform group-hover:scale-105">
        
        <!-- Subtle gradient overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-purple-50 opacity-70"></div>
        
        <!-- Content -->
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-gradient-to-r from-emerald-400 to-blue-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-bold">🎁</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-800">BOGO Deal</span>
                </div>
                <div class="bg-red-500 text-white text-xs px-2 py-1 rounded-full font-bold animate-pulse">
                    HOT
                </div>
            </div>
            
            <div class="flex items-center space-x-3 mb-3">
                <div class="relative w-12 h-12 flex-shrink-0">
                    <?php echo str_replace('class="', 'class="w-12 h-12 object-cover rounded-lg shadow-sm ', $get_image); ?>
                    <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-xs">✓</span>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-700 leading-tight">
                        Buy <span class="font-bold text-blue-600"><?php echo $buy_qty; ?></span> → Get <span class="font-bold text-green-600"><?php echo $get_qty; ?></span>
                        <br><span class="text-xs text-gray-500"><?php echo esc_html( $get_name ); ?></span>
                    </p>
                </div>
            </div>
            
            <button 
                class="grab-bogo-offer-btn w-full bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-all duration-200 flex items-center justify-center space-x-2 shadow-md hover:shadow-lg"
                <?php echo $common_button_data; ?>
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span>Claim Deal</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
        
        <!-- Decorative elements -->
        <div class="absolute top-2 right-2 w-16 h-16 bg-gradient-to-br from-yellow-200 to-pink-200 rounded-full opacity-20 -translate-y-8 translate-x-8"></div>
        <div class="absolute bottom-2 left-2 w-8 h-8 bg-gradient-to-br from-green-200 to-blue-200 rounded-full opacity-30 translate-y-4 -translate-x-4"></div>
    </div>
    
    <!-- Loading state -->
    <div class="bogo-offer-loading hidden mt-2 text-center">
        <div class="inline-flex items-center text-blue-600 text-sm">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Processing...
        </div>
    </div>
</div>