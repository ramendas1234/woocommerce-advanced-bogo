<?php
/**
 * BOGO Template 1 - Classic Design
 * Clean and professional template with gradient background
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="bogo-offer-container my-4 p-4 border border-gray-200 rounded-lg shadow-lg bg-white" style="background: linear-gradient(135deg, #fff 0%, #f8f9ff 100%);">
    <div class="flex items-center gap-4">
        <div class="relative w-24 h-24 flex-shrink-0">
            <?php echo $get_image; ?>
            <div class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-bl">
                🎁 Gift
            </div>
        </div>
        <div class="flex-grow">
            <h3 class="text-lg font-bold mb-1 text-gray-800">🎉 Special BOGO Offer!</h3>
            <p class="text-gray-700 text-sm mb-3">
                Buy <span class="font-semibold text-blue-600"><?php echo $buy_qty; ?></span> of this product and get 
                <span class="font-semibold text-green-600"><?php echo $get_qty; ?></span> of 
                <span class="font-semibold text-purple-600"><?php echo esc_html( $get_name ); ?></span> 
                <span class="font-bold text-red-600"><?php echo esc_html( $discount_text ); ?></span>
            </p>
            <button 
                class="grab-bogo-offer-btn inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-500 to-blue-600 hover:from-green-600 hover:to-blue-700 text-white font-bold rounded-lg shadow-md hover:shadow-lg transform hover:scale-105 transition-all duration-200 text-sm"
                <?php echo $common_button_data; ?>
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
    <?php echo $loading_spinner; ?>
</div>