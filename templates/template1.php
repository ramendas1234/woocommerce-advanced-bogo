<?php
/**
 * BOGO Template 1 - Classic Design
 * Clean and professional template with gradient background
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="bogo-offer-container my-4 p-4 border border-gray-200 rounded-lg shadow-lg" style="background: <?php echo esc_attr( $background_color ); ?>;">
    <div class="flex items-center gap-4">
        <div class="relative w-24 h-24 flex-shrink-0">
            <?php echo $get_image; ?>
            <div class="absolute top-0 right-0 text-white text-xs font-bold px-2 py-1 rounded-bl" style="background: <?php echo esc_attr( $secondary_color ); ?>;">
                🎁 Gift
            </div>
        </div>
        <div class="flex-grow">
            <h3 class="text-lg font-bold mb-1" style="color: <?php echo esc_attr( $text_color ); ?>;">🎉 Special BOGO Offer!</h3>
            <p class="text-sm mb-3" style="color: <?php echo esc_attr( $text_color ); ?>;">
                Buy <span class="font-semibold" style="color: <?php echo esc_attr( $primary_color ); ?>;"><?php echo $buy_qty; ?></span> of this product and get 
                <span class="font-semibold" style="color: <?php echo esc_attr( $secondary_color ); ?>;"><?php echo $get_qty; ?></span> of 
                <span class="font-semibold" style="color: <?php echo esc_attr( $primary_color ); ?>;"><?php echo esc_html( $get_name ); ?></span> 
                <span class="font-bold" style="color: <?php echo esc_attr( $secondary_color ); ?>;"><?php echo esc_html( $discount_text ); ?></span>
            </p>
            <button 
                class="grab-bogo-offer-btn inline-flex items-center px-4 py-2 font-bold rounded-lg shadow-md hover:shadow-lg transform hover:scale-105 transition-all duration-200 text-sm"
                style="background: <?php echo esc_attr( $button_bg_color ); ?>; color: <?php echo esc_attr( $button_text_color ); ?>;"
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