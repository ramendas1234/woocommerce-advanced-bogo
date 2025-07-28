<?php
/**
 * BOGO Template 6 - Modern Notification Strip
 * Sleek horizontal notification bar with modern design and subtle animations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="bogo-offer-container my-3 relative">
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-lg p-1 shadow-lg relative overflow-hidden group">
        
        <!-- Animated background -->
        <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 animate-pulse"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-10 transform -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
        
        <div class="bg-white rounded-md p-3 relative z-10">
            <div class="flex items-center justify-between">
                
                <!-- Left section - Deal info -->
                <div class="flex items-center space-x-3">
                    <div class="relative w-10 h-10 flex-shrink-0">
                        <?php echo str_replace('class="', 'class="w-10 h-10 object-cover rounded-lg shadow ', $get_image); ?>
                        <div class="absolute -top-1 -right-1 bg-gradient-to-r from-pink-500 to-rose-500 text-white text-xs px-1 py-0.5 rounded-full font-bold">
                            FREE
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex items-center space-x-2 mb-1">
                            <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent font-bold text-sm">
                                🎯 Special Offer
                            </span>
                            <div class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full font-medium">
                                Limited Time
                            </div>
                        </div>
                        <p class="text-gray-700 text-sm">
                            Buy <span class="font-semibold text-indigo-600"><?php echo $buy_qty; ?></span> items, get 
                            <span class="font-semibold text-purple-600"><?php echo $get_qty; ?></span> 
                            <span class="text-gray-500"><?php echo esc_html( $get_name ); ?></span> 
                            <span class="text-green-600 font-medium"><?php echo esc_html( $discount_text ); ?></span>
                        </p>
                    </div>
                </div>
                
                <!-- Right section - Action button -->
                <div class="flex items-center space-x-3">
                    <div class="hidden sm:flex flex-col items-end">
                        <span class="text-xs text-gray-500 line-through">Regular Price</span>
                        <span class="text-lg font-bold text-green-600">FREE!</span>
                    </div>
                    
                    <button 
                        class="grab-bogo-offer-btn bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-medium px-4 py-2 rounded-lg text-sm transition-all duration-200 flex items-center space-x-2 shadow-md hover:shadow-lg group"
                        <?php echo $common_button_data; ?>
                    >
                        <svg class="w-4 h-4 group-hover:rotate-12 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <span>Add to Cart</span>
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Progress indicator -->
        <div class="absolute bottom-0 left-0 right-0 h-1 bg-white bg-opacity-20">
            <div class="h-full bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full w-3/4 animate-pulse"></div>
        </div>
    </div>
    
    <!-- Loading state -->
    <div class="bogo-offer-loading hidden mt-2 text-center">
        <div class="inline-flex items-center text-purple-600 text-sm">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Processing your order...
        </div>
    </div>
</div>