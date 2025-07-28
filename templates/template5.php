<?php
/**
 * BOGO Template 5 - Modern Ribbon Style
 * Vibrant ribbon-style banner with modern gradient and bold call-to-action
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="bogo-offer-modern-ribbon my-4 relative flex items-center justify-center">
    <div class="ribbon-bg px-8 py-4 rounded-lg shadow-lg flex items-center space-x-4 relative overflow-hidden" style="background: linear-gradient(90deg, #ff8a00 0%, #e52e71 100%); color: #fff;">
        <!-- Decorative Ribbon Edge -->
        <div class="absolute left-0 top-0 h-full w-3 bg-gradient-to-b from-pink-500 to-orange-400 rounded-l-lg"></div>
        <!-- Product Image -->
        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-white bg-opacity-20 flex items-center justify-center overflow-hidden border-2 border-white">
            <?php echo str_replace('class="', 'class="w-12 h-12 object-cover rounded-full ', $get_image); ?>
        </div>
        <!-- Deal Text -->
        <div class="flex flex-col">
            <span class="text-lg font-bold tracking-wide drop-shadow">Buy <?php echo $buy_qty; ?>, Get <?php echo $get_qty; ?> Free!</span>
            <span class="text-xs opacity-90 truncate max-w-xs">on <?php echo esc_html( $get_name ); ?></span>
        </div>
        <!-- Action Button -->
        <button 
            class="grab-bogo-offer-btn ml-6 bg-white bg-opacity-20 hover:bg-opacity-30 backdrop-blur-sm text-white font-semibold px-5 py-2 rounded-full text-sm transition-all duration-200 border border-white border-opacity-30 flex items-center space-x-2 shadow-md"
            <?php echo $common_button_data; ?>
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            <span>Unlock Deal</span>
        </button>
    </div>
    <!-- Loading State -->
    <div class="bogo-offer-loading hidden ml-3">
        <div class="inline-flex items-center text-pink-600 text-xs">
            <svg class="animate-spin -ml-1 mr-1 h-3 w-3 text-pink-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Adding...
        </div>
    </div>
</div>