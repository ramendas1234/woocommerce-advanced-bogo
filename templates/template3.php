<?php
/**
 * BOGO Template 3 - Modern Square Card Design
 * Compact, modern square card with attractive styling
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

<div class="bogo-offer-container my-3 relative" style="max-width: 15rem; margin-left: auto; margin-right: auto;">
    <div class="w-full">
        <div class="p-1 rounded-xl shadow-lg animate-pulse" style="background: linear-gradient(45deg, <?php echo esc_attr( $primary_color ); ?>, <?php echo esc_attr( $secondary_color ); ?>); background-size: 200% 200%; animation: gradientShift 2s ease infinite;">
            <div class="backdrop-filter backdrop-blur-sm rounded-xl p-4 relative overflow-hidden" style="background: <?php echo esc_attr( $background_color ); ?>;">
                
                <!-- Subtle Background Elements -->
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute top-2 right-2 w-2 h-2 rounded-full animate-pulse" style="background: <?php echo esc_attr( $primary_color ); ?>;"></div>
                    <div class="absolute bottom-2 left-2 w-1 h-1 rounded-full animate-ping" style="background: <?php echo esc_attr( $secondary_color ); ?>;"></div>
                </div>
                
                <div class="relative z-10">
                    <!-- Header -->
                    <div class="text-center mb-3">
                        <div class="inline-block px-2 py-1 rounded-full text-xs font-bold mb-1" style="background: <?php echo esc_attr( $secondary_color ); ?>; color: <?php echo esc_attr( $text_color ); ?>;">
                            🚀 SPECIAL OFFER
                        </div>
                        <h3 class="text-sm font-bold" style="color: <?php echo esc_attr( $text_color ); ?>;">
                            LIMITED TIME!
                        </h3>
                    </div>
                    
                    <!-- Main Content -->
                    <div class="flex items-center justify-center mb-3">
                        <div class="relative mr-3">
                            <?php echo str_replace('class="', 'class="rounded-lg shadow-md w-16 h-16 object-cover ', $get_image); ?>
                        </div>
                        <div class="text-center flex-1">
                            <div class="text-xs font-bold mb-1" style="color: <?php echo esc_attr( $text_color ); ?>;">
                                Buy <span class="text-sm" style="color: <?php echo esc_attr( $primary_color ); ?>;"><?php echo $buy_qty; ?></span> 
                                → Get <span class="text-sm" style="color: <?php echo esc_attr( $secondary_color ); ?>;"><?php echo $get_qty; ?></span>
                            </div>
                            <div class="text-xs" style="color: <?php echo esc_attr( $text_color ); ?>;">
                                <span class="font-semibold" style="color: <?php echo esc_attr( $primary_color ); ?>;"><?php echo esc_html( $get_name ); ?></span>
                                <br><strong style="color: <?php echo esc_attr( $secondary_color ); ?>;"><?php echo esc_html( $discount_text ); ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Button -->
                    <button 
                        class="grab-bogo-offer-btn w-full font-bold py-2 px-4 rounded-lg shadow-md hover:shadow-lg transform hover:scale-105 transition-all duration-200 text-xs relative overflow-hidden"
                        style="background: <?php echo esc_attr( $button_bg_color ); ?>; color: <?php echo esc_attr( $button_text_color ); ?>;"
                        <?php echo $common_button_data; ?>
                    >
                        <div class="flex items-center justify-center">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            🎯 CLAIM NOW!
                            <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php echo $loading_spinner; ?>
</div>