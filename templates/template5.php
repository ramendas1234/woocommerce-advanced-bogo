<?php
/**
 * BOGO Template 5 - Minimalist Badge Style
 * Ultra-compact floating badge design with clean minimalist aesthetics
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="bogo-offer-container inline-flex items-center my-2 relative group">
    <div class="bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 rounded-full px-4 py-2 shadow-lg hover:shadow-xl transition-all duration-300 group-hover:scale-110 relative overflow-hidden">
        
        <!-- Shine effect -->
        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-20 transform -skew-x-12 group-hover:translate-x-full transition-transform duration-700"></div>
        
        <div class="relative z-10 flex items-center space-x-3">
            <!-- Product thumbnail -->
            <div class="relative w-8 h-8 flex-shrink-0">
                <?php echo str_replace('class="', 'class="w-8 h-8 object-cover rounded-full border-2 border-white shadow-sm ', $get_image); ?>
                <div class="absolute -top-1 -right-1 w-3 h-3 bg-yellow-400 rounded-full flex items-center justify-center">
                    <span class="text-xs">🎁</span>
                </div>
            </div>
            
            <!-- Deal text -->
            <div class="text-white">
                <span class="text-sm font-bold">
                    Buy <?php echo $buy_qty; ?> → Get <?php echo $get_qty; ?> FREE
                </span>
                <div class="text-xs opacity-90 truncate max-w-32">
                    <?php echo esc_html( $get_name ); ?>
                </div>
            </div>
            
            <!-- Action button -->
            <button 
                class="grab-bogo-offer-btn bg-white bg-opacity-20 hover:bg-opacity-30 backdrop-blur-sm text-white font-medium px-3 py-1 rounded-full text-xs transition-all duration-200 border border-white border-opacity-30 flex items-center space-x-1"
                <?php echo $common_button_data; ?>
            >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span>Grab</span>
            </button>
        </div>
        
        <!-- Floating particles -->
        <div class="absolute top-1 right-8 w-1 h-1 bg-white rounded-full opacity-60 animate-ping"></div>
        <div class="absolute bottom-1 left-8 w-1 h-1 bg-white rounded-full opacity-40 animate-pulse"></div>
    </div>
    
    <!-- Compact loading state -->
    <div class="bogo-offer-loading hidden ml-3">
        <div class="inline-flex items-center text-emerald-600 text-xs">
            <svg class="animate-spin -ml-1 mr-1 h-3 w-3 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Adding...
        </div>
    </div>
</div>