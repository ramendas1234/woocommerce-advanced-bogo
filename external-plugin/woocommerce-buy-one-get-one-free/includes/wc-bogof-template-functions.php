<?php
/**
 * Template Hooks and Functions.
 *
 * @package WC_BOGOF\Templates
 * @version 4.2.0
 */

defined( 'ABSPATH' ) || exit;

/*
|--------------------------------------------------------------------------
| Hooks
|--------------------------------------------------------------------------
*/
add_action( 'wc_bogof_before_gift_item_title', 'wc_bogof_gift_template_thumbnail' );
add_action( 'wc_bogof_gift_item_title', 'wc_bogof_gift_template_title' );
add_action( 'wc_bogof_after_gift_item_title', 'wc_bogof_gift_template_loop_short_description', 10 );
add_action( 'wc_bogof_after_gift_item_title', 'wc_bogof_gift_template_loop_price', 20 );
add_action( 'wc_bogof_after_gift_item', 'wc_bogof_gift_template_add_to_cart' );

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

if ( ! function_exists( 'wc_bogof_gift_modal_dialog' ) ) {

	/**
	 * Output the gift modal dialog.
	 */
	function wc_bogof_gift_modal_dialog() {

		wc_get_template(
			'choose-your-gift/tpl-modal-dialog.php',
			[],
			'',
			dirname( WC_BOGOF_PLUGIN_FILE ) . '/templates/'
		);
	}
}


if ( ! function_exists( 'wc_bogof_gift_modal_header_classes' ) ) {

	/**
	 * Returns the header classes.
	 */
	function wc_bogof_gift_modal_header_classes() {

		$classes = [];

		foreach ( [ 'font-weight', 'text-transform' ] as $prop ) {
			$value = WC_BOGOF_Mods::get( 'header_font', $prop );
			if ( ! empty( $value ) ) {
				$classes[] = "__set_{$prop}";
			}
		}

		return implode( ' ', $classes );
	}
}

if ( ! function_exists( 'wc_bogof_gift_modal_container_classes' ) ) {

	/**
	 * Returns the container classes.
	 */
	function wc_bogof_gift_modal_container_classes() {

		$classes = [];

		foreach ( [ 'desktop', 'tablet', 'mobile' ] as $device ) {
			$mod       = 'desktop' === $device ? 'loop_columns' : 'loop_columns_device';
			$classes[] = "-columns-{$device}-" . WC_BOGOF_Mods::get( $mod, $device );
		}

		foreach ( [ 'header_font', 'body_font', 'button_font' ] as $mod ) {

			foreach ( [ 'font-weight', 'text-transform' ] as $prop ) {

				$value = WC_BOGOF_Mods::get( $mod, $prop );

				if ( ! empty( $value ) ) {
					$classes[] = "-set_{$mod}_{$prop}";
				}
			}
		}

		$layout = WC_BOGOF_Mods::get( 'items_layout' );

		if ( 'default' !== $layout ) {
			$classes[] = "-layout_{$layout}";
		}

		return implode( ' ', $classes );
	}
}

if ( ! function_exists( 'wc_bogof_gift_item_classes' ) ) {

	/**
	 * Output the gift item clasess.
	 *
	 * @param WC_Product $product Product instance.
	 * @param int        $loop_index Loop index.
	 */
	function wc_bogof_gift_item_classes( $product, $loop_index ) {

		$classes = [
			'-product-' . $product->get_id(),
		];

		$columns = absint( WC_BOGOF_Mods::get( 'loop_columns' ) );

		if ( 0 === ( $loop_index - 1 ) % $columns || 1 === $columns ) {
			$classes[] = 'first';
		}

		if ( 0 === $loop_index % $columns ) {
			$classes[] = 'last';
		}

		/**
		 * Filter the gift loop item classes.
		 */
		return apply_filters( 'wc_bogof_gift_item_classes', implode( ' ', $classes ), $product->get_id(), $loop_index );
	}
}

if ( ! function_exists( 'wc_bogof_gift_template_thumbnail' ) ) {

	/**
	 * Outputs the product thumbnail in the product loop.
	 *
	 * @param WC_Product $product The product.
	 */
	function wc_bogof_gift_template_thumbnail( $product ) {

		$image_size = apply_filters( 'wc_bogof_gift_template_thumbnail_size', 'woocommerce_thumbnail' );

		printf(
			'<div class="wc-bogof-gift-item__image">%s</div>',
			wp_kses_post( $product ? $product->get_image( $image_size, [], true ) : '' )
		);
	}
}

if ( ! function_exists( 'wc_bogof_gift_template_title' ) ) {

	/**
	 * Outputs the product title in the product loop.
	 *
	 * @param WC_Product $product The product.
	 */
	function wc_bogof_gift_template_title( $product ) {
		$name           = $product->get_name();
		$variation_name = '';

		if ( is_a( $product, 'WC_Product_Variation' ) ) {
			$variation_name = trim( wc_get_formatted_variation( $product, true, false, true ) );
		}

		if ( ! empty( $variation_name ) ) {
			$name .= ' &ndash; ' . $variation_name;
		}

		printf(
			'<h5 class="%s">%s</h5>',
			esc_attr( apply_filters( 'wc_bogof_gift_template_title_classes', 'wc-bogof-gift-item__title' ) ),
			wp_kses_post( $name )
		);
	}
}


if ( ! function_exists( 'wc_bogof_gift_template_loop_short_description' ) ) {

	/**
	 * Outputs the product short description in the product loop.
	 *
	 * @param WC_Product $product The product.
	 */
	function wc_bogof_gift_template_loop_short_description( $product ) {
		if ( 'yes' !== WC_BOGOF_Mods::get( 'display_short_description' ) ) {
			return;
		}

		printf(
			'<div class="wc-bogof-gift-item__short-description">%s</div>',
			wp_kses_post(
				apply_filters(
					'woocommerce_short_description',
					$product->get_short_description()
				)
			)
		);
	}
}

if ( ! function_exists( 'wc_bogof_gift_template_loop_price' ) ) {

	/**
	 * Outputs the product's price in the product loop.
	 *
	 * @param WC_Product $product The product.
	 */
	function wc_bogof_gift_template_loop_price( $product ) {

		$price_html = '';

		if ( 'yes' === WC_BOGOF_Mods::get( 'display_price' ) && ! empty( $product ) && $product->get_id() > 0 ) {

			$price_html = wc_format_sale_price(
				wc_get_price_to_display(
					$product,
					[
						'price' => $product->get_regular_price(),
					]
				),
				wc_get_price_to_display(
					$product,
					[
						'price' => $product->get_price( 'edit' ),
					]
				)
			);

			$price_html .= $product->get_price_suffix();

			if ( is_callable( [ 'WC_Subscriptions_Product', 'get_price_string' ] ) && in_array( $product->get_type(), [ 'subscription', 'subscription_variation' ], true ) ) {

				$price_html = WC_Subscriptions_Product::get_price_string( $product, [ 'price' => $price_html ] );
			}

			if ( $product instanceof WC_BOGOF_Gifts_Variable_Product && $product->show_from_text() ) {
				$price_html = wc_get_price_html_from_text() . ' ' . $price_html;
			}
		}

		printf( '<span class="price">%s</span>', $price_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'wc_bogof_gift_template_add_to_cart' ) ) {

	/**
	 * Outputs the product add to cart in the product loop.
	 *
	 * @param WC_Product $product The product.
	 */
	function wc_bogof_gift_template_add_to_cart( $product ) {

		if ( empty( $product ) ) {
			return;
		}

		$classes = array_filter(
			[
				'button',
				wc_wp_theme_get_element_class_name( 'button' ),
				'custom' === WC_BOGOF_Mods::get( 'button_style' ) ? 'wc-bogof-add-to-cart' : 'add_to_cart_button',
				'__product-' . $product->get_id(),
			]
		);

		if ( $product instanceof WC_BOGOF_Gifts_Variable_Product ) {

			wc_bogof_gift_template_add_to_cart_variable( $product, $classes );

		} else {
			wc_bogof_gift_template_add_to_cart_single( $product, $classes );
		}
	}
}

if ( ! function_exists( 'wc_bogof_gift_template_add_to_cart_single' ) ) {

	/**
	 * Outputs the product add to cart form for non-variable products.
	 *
	 * @param WC_Product $product The product.
	 * @param array      $button_classes Array of button classes.
	 */
	function wc_bogof_gift_template_add_to_cart_single( $product, $button_classes ) {
		$button_label = wc_bogof_gift_template_add_to_cart_button_label( $product );
		$message      = wc_bogof_gift_template_product_status_notice( $product );

		if ( ! empty( $message ) ) {
			$message = sprintf( '<span class="wc-bogof-error">%s</span>', $message );
		}

		$disabled = $product->get_id() < 1 ? ' disabled="disabled"' : '';

		printf(
			'<form class="wc-bogof-gift-cart"><div class="woocommerce"><button type="submit" name="product_id" value="%s" class="%s"%s>%s</button>%s</div></form>',
			esc_attr( $product->get_id() ),
			esc_attr( implode( ' ', $button_classes ) ),
			esc_attr( $disabled ),
			wp_kses_post( $button_label ),
			wp_kses_post( $message )
		);
	}
}

if ( ! function_exists( 'wc_bogof_gift_template_add_to_cart_variable' ) ) {

	/**
	 * Outputs the product add to cart form for variable products.
	 *
	 * @param WC_Product $product The product.
	 * @param array      $button_classes Array of button classes.
	 */
	function wc_bogof_gift_template_add_to_cart_variable( $product, $button_classes ) {

		$button_label = wc_bogof_gift_template_add_to_cart_button_label( $product );

		?>
		<form class="wc-bogof-gift-cart wc-bogof-gift-variations-form variations_form" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" data-product_variations="<?php echo esc_attr( wp_json_encode( $product->get_available_variations() ) ); ?>">
			<div class="variations">
			<?php foreach ( $product->get_variation_attributes() as $attribute_name => $options ) : ?>
				<div class="wc-bogof-gift-variation">
					<label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>">
						<?php echo wc_attribute_label( $attribute_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</label>
					<?php
						wc_dropdown_variation_attribute_options(
							[
								'options'   => $options,
								'attribute' => $attribute_name,
								'product'   => $product,
							]
						);
					?>
				</div>
			<?php endforeach; ?>
			</div>
			<div class="woocommerce">
				<button type="submit" name="product_id" class="<?php echo esc_attr( implode( ' ', $button_classes ) ); ?>">
				<?php echo wp_kses_post( $button_label ); ?>
				</button>
			</div>
		</form>
		<?php
	}
}

if ( ! function_exists( 'wc_bogof_gift_template_product_status_notice' ) ) {

	/**
	 * Returns the product status error message.
	 *
	 * @param WC_Product $product The product.
	 * @return string The status notice
	 */
	function wc_bogof_gift_template_product_status_notice( $product ) {
		$message = '';

		if ( 'publish' !== $product->get_status() ) {
			/* translators: %s: product name. */
			$message = sprintf( __( '%1$s is not visible to customers because the product\'s status is not "%2$s."', 'wc-buy-one-get-one-free' ), wp_kses_post( $product->get_formatted_name() ), esc_html( get_post_status_object( 'publish' )->label ) );
		} elseif ( ! $product->is_in_stock() ) {
			/* translators: %s: product name. */
			$message = sprintf( __( '%s is not visible to customers because it is "out of stock".', 'wc-buy-one-get-one-free' ), wp_kses_post( $product->get_formatted_name() ) );
		} elseif ( ! wc_bogof_product_type_supports_modal_gifts( $product->get_type() ) ) {
			/* translators: %s: product name. */
			$message = sprintf( __( '%s is not visible to customers because it does not support this feature.', 'wc-buy-one-get-one-free' ), wp_kses_post( $product->get_formatted_name() ) );
		}

		return $message;
	}
}

if ( ! function_exists( 'wc_bogof_gift_template_add_to_cart_button_label' ) ) {

	/**
	 * Returns the Add to cart button label.
	 *
	 * @param WC_Product $product Product instance.
	 */
	function wc_bogof_gift_template_add_to_cart_button_label( $product ) {

		$label = __( 'Add to cart', 'woocommerce' );

		if ( is_callable( [ 'WC_Subscriptions_Product', 'get_add_to_cart_text' ] ) && in_array( $product->get_type(), [ 'subscription', 'subscription_variation' ], true ) ) {
			$label = WC_Subscriptions_Product::get_add_to_cart_text();
		}

		$product_id = $product instanceof WC_Product_Variation && ! wc_string_to_bool( WC_BOGOF_Mods::get( 'show_single_variations' ) ) ? $product->get_parent_id() : $product->get_id();
		$quantity   = 0;

		foreach ( WC()->cart->get_cart() as $cart_item ) {

			if ( WC_BOGOF_Cart::is_free_item( $cart_item ) &&
				isset( $cart_item['product_id'], $cart_item['variation_id'], $cart_item['quantity'] ) &&
				in_array( absint( $product_id ), array_map( 'absint', [ $cart_item['product_id'], $cart_item['variation_id'] ] ), true )
			) {
				$quantity += absint( $cart_item['quantity'] );
			}
		}

		if ( $quantity > 0 ) {
			/* translators: %s: product quantity. */
			$label = '<span class="wc-bogof-added">' . sprintf( __( '%s in cart', 'woocommerce' ), $quantity ) . '</span>';
		}

		return $label;
	}
}

if ( ! function_exists( 'wc_bogof_gift_template_load_more' ) ) {

	/**
	 * Outputs the load more button.
	 *
	 * @param int $page Page to load.
	 */
	function wc_bogof_gift_template_load_more( $page ) {
		$classes = array_filter(
			[
				'button',
				wc_wp_theme_get_element_class_name( 'button' ),
				'alt',
				'load-more',
			]
		);

		return sprintf(
			'<button class="%s" data-page="%s" data-append-to="%s">%s</button>',
			esc_attr( apply_filters( 'wc_bogof_gift_template_load_more_classes', implode( ' ', $classes ) ) ),
			esc_attr( absint( $page ) ),
			esc_attr( 'div.wc-bogof-products' ),
			esc_html__( 'Load more', 'woocommerce' )
		);
	}
}

if ( ! function_exists( 'wc_bogof_gift_template_placeholder' ) ) {

	/**
	 * Returns the gift placeholder.
	 */
	function wc_bogof_gift_template_placeholder() {

		$product      = new WC_Product();
		$loop_columns = WC_BOGOF_Mods::get( 'loop_columns' );

		ob_start();

		for ( $loop_index = 1; $loop_index <= $loop_columns; $loop_index++ ) {
			// Render product template.
			wc_get_template(
				'choose-your-gift/content-product.php',
				[
					'product'    => $product,
					'loop_index' => $loop_index,
				],
				'',
				dirname( WC_BOGOF_PLUGIN_FILE ) . '/templates/'
			);
		}

		return ob_get_clean();
	}
}

if ( ! function_exists( 'wc_bogof_gift_template_announcement_bar' ) ) {

	/**
	 * Outputs the announcement bar template.
	 */
	function wc_bogof_gift_template_announcement_bar() {
		wc_get_template(
			'choose-your-gift/tpl-announcement-bar.php',
			[
				'sticky' => wc_string_to_bool( WC_BOGOF_Mods::get( 'notice_bar_sticky' ) ),
			],
			'',
			dirname( WC_BOGOF_PLUGIN_FILE ) . '/templates/'
		);
	}
}

if ( ! function_exists( 'wc_bogof_gift_template_woo_notice' ) ) {

	/**
	 * Outputs the WooCommerce notice template.
	 */
	function wc_bogof_gift_template_woo_notice() {
		?>
		<template id="wc-bogof-woo-notice-tpl">
			<?php wc_print_notice( '', 'notice' ); ?>
		</template>
		<?php
	}
}

if ( ! function_exists( 'wc_bogof_gift_template_notice' ) ) {

	/**
	 * Outputs the Available Gift(s) Notice template.
	 */
	function wc_bogof_gift_template_notice() {
		if ( 'woo' === WC_BOGOF_Mods::get( 'notice_style' ) ) {
			wc_bogof_gift_template_woo_notice();
		} else {
			wc_bogof_gift_template_announcement_bar();
		}
	}
}

if ( ! function_exists( 'wc_bogof_gift_notice_text' ) ) {

	/**
	 * Returns the gift notices text.
	 *
	 * @param int    $qty The number of gifts the user can add to the cart.
	 * @param array  $discounts Array of discounts of the available offers.
	 * @param string $gift_hash The gifts hash.
	 */
	function wc_bogof_gift_notice_text( $qty, $discounts, $gift_hash = '' ) {

		$notice_text = WC_BOGOF_Mods::get( 'notice_text' );

		if ( empty( $notice_text ) ) {
			$notice_text = wc_bogof_gift_default_notice_text( $qty, $discounts );
		} else {
			$notice_text = str_replace( '[qty]', $qty, $notice_text );
		}

		$notice_button_text = WC_BOGOF_Mods::get( 'notice_button_text' );

		if ( empty( $notice_button_text ) ) {

			/**
			 * Filters the default notice text.
			 *
			 * @param string $notice_button_text The notice button text.
			 */
			$notice_button_text = apply_filters( 'wc_bogof_default_notice_button_text', WC_BOGOF_Mods::default( 'notice_button_text' ) );
		}

		return sprintf(
			'%s <a href="#" tabindex="1" class="wc-bogof-announcement-action-button button-choose-your-gift" data-gift_hash="%s">%s</a>',
			esc_html( $notice_text ),
			esc_attr( $gift_hash ),
			esc_html( $notice_button_text )
		);
	}
}

if ( ! function_exists( 'wc_bogof_gift_default_notice_text' ) ) {

	/**
	 * Returns the default gift notices text.
	 *
	 * @param int   $qty The number of gifts the user can add to the cart.
	 * @param array $discounts Array of discounts of the available offers.
	 */
	function wc_bogof_gift_default_notice_text( $qty, $discounts ) {

		if ( 1 === count( $discounts ) ) {
			if ( 100 === absint( $discounts[0] ) ) {
				// translators: 1: free products qty.
				$message = sprintf( _n( 'You can now add %1$s product for free to the cart', 'You can now add %1$s products for free to the cart', $qty, 'wc-buy-one-get-one-free' ), $qty );
			} else {
				// translators: 1: free products qty, 2: percentage discount.
				$message = sprintf( _n( 'You can now add %1$s product with %2$s off', 'You can now add %1$s product with %2$s off', $qty, 'wc-buy-one-get-one-free' ), $qty, $discounts[0] . '%' );
			}
		} else {
			// translators: 1: free products qty.
			$message = sprintf( _n( 'You can now add %1$s products at a sale price', 'You can now add %1$s products at a sale price', $qty, 'wc-buy-one-get-one-free' ), $qty );
		}

		/**
		 * Filters the default notice text.
		 *
		 * @param string $message   The message.
		 * @param int    $qty       Free quantity.
		 * @param array  $discounts Array of percentage discounts.
		 */
		return apply_filters( 'wc_bogof_default_choose_gift_message', $message, $qty, $discounts );
	}
}

