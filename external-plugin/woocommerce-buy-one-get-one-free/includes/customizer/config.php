<?php
/**
 * Customizer settings.
 *
 * @since 4.2.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

return [

	/**
	 * Gifts container section.
	 */
	[
		'id'       => 'gifts_container',
		'title'    => __( 'Gifts Popup', 'wc-buy-one-get-one-free' ),
		'priority' => 10,
		'controls' => [

			/**
			 * Title Accordion.
			 */
			[
				'label'            => __( 'Title', 'wc-buy-one-get-one-free' ),
				'type'             => 'WC_BOGOF_Customize_Control_Accordion',
				'controls_to_wrap' => 3,
			],

			/**
			 * Title option.
			 */
			[
				'mod'           => 'header_title',
				'label'         => __( 'Title', 'wc-buy-one-get-one-free' ),
				'type'          => 'text',
				'description'   => __( 'Use [qty] for the number of items.', 'wc-buy-one-get-one-free' ),
				'wrapper_class' => 'wc-bogof-spacing wc-bogof-divider',
				'transport'     => 'postMessage',
			],

			/**
			 * Title font.
			 */
			[
				'label'         => __( 'Font', 'wc-buy-one-get-one-free' ),
				'type'          => 'WC_BOGOF_Customize_Control_Font_Manager',
				'mod'           =>
				[
					'font-weight'    => 'header_font.font-weight',
					'text-transform' => 'header_font.text-transform',
					'font-size'      => 'header_font.font-size',
				],
				'wrapper_class' => 'wc-bogof-spacing wc-bogof-divider',
			],

			/**
			 * Title text align.
			 */
			[
				'label'         => __( 'Text align', 'wc-buy-one-get-one-free' ),
				'mod'           => 'header_align',
				'type'          => 'WC_BOGOF_Customize_Control_Button_Group',
				'wrapper_class' => 'wc-bogof-spacing',
				'choices'       => [
					'left'   => 'Left',
					'center' => 'Center',
					'right'  => 'Right',
				],
				'icons'         => [
					'left'   => 'editor-alignleft',
					'center' => 'editor-aligncenter',
					'right'  => 'editor-alignright',
				],

			],

			/**
			 * Layout Accordion.
			 */
			[
				'label'            => __( 'Layout', 'wc-buy-one-get-one-free' ),
				'type'             => 'WC_BOGOF_Customize_Control_Accordion',
				'controls_to_wrap' => 7,
			],


			/**
			 * Items layout.
			 */
			[
				'mod'           => 'items_layout',
				'label'         => __( 'Items design', 'wc-buy-one-get-one-free' ),
				'type'          => 'WC_BOGOF_Customize_Control_Radio_Image',
				'wrapper_class' => 'wc-bogof-spacing wc-bogof-divider',
				'choices'       => [
					'default' => 'Design 1',
					'list'    => 'Design 2',
				],

			],

			/**
			 * Loop columns option.
			 */
			[
				'mod'           => 'loop_columns',
				'label'         => __( 'Columns', 'wc-buy-one-get-one-free' ),
				'type'          => 'WC_BOGOF_Customize_Control_Slider',
				'wrapper_class' => 'wc-bogof-top-spacing',
				'responsive'    => 'loop_columns_device',
				'input_attrs'   => [
					'min'  => 1,
					'max'  => 6,
					'step' => 1,
				],
			],

			/**
			 * Loop rows option.
			 */
			[
				'mod'           => 'loop_rows',
				'label'         => __( 'Rows per page', 'wc-buy-one-get-one-free' ),
				'type'          => 'WC_BOGOF_Customize_Control_Slider',
				'wrapper_class' => 'wc-bogof-bottom-spacing wc-bogof-divider',
				'input_attrs'   => [
					'min'  => 1,
					'max'  => 6,
					'step' => 1,
				],
			],

			/**
			 * Body font.
			 */
			[
				'label'         => __( 'Font', 'wc-buy-one-get-one-free' ),
				'type'          => 'WC_BOGOF_Customize_Control_Font_Manager',
				'mod'           =>
				[
					'font-weight'    => 'body_font.font-weight',
					'text-transform' => 'body_font.text-transform',
					'font-size'      => 'body_font.font-size',
				],
				'wrapper_class' => 'wc-bogof-spacing wc-bogof-divider',
			],

			/**
			 * Show single variations option.
			 */
			[
				'mod'           => 'show_single_variations',
				'label'         => 'Show single variations',
				'description'   => __( 'Display variations as individual products', 'wc-buy-one-get-one-free' ),
				'type'          => 'WC_BOGOF_Customize_Control_Toggle',
				'wrapper_class' => 'wc-bogof-spacing wc-bogof-divider',
			],

			/**
			 * Display price option.
			 */
			[
				'mod'           => 'display_price',
				'label'         => __( 'Display price', 'wc-buy-one-get-one-free' ),
				'type'          => 'WC_BOGOF_Customize_Control_Toggle',
				'wrapper_class' => 'wc-bogof-top-spacing',
			],

			/**
			 * Display price option.
			 */
			[
				'mod'           => 'display_short_description',
				'label'         => __( 'Display short description', 'wc-buy-one-get-one-free' ),
				'type'          => 'WC_BOGOF_Customize_Control_Toggle',
				'wrapper_class' => 'wc-bogof-bottom-spacing',
			],


			/**
			 * Add to cart button Accordion.
			 */
			[
				'label'            => __( 'Add to cart button', 'wc-buy-one-get-one-free' ),
				'type'             => 'WC_BOGOF_Customize_Control_Accordion',
				'controls_to_wrap' => 8,
			],

			/**
			 * Button style.
			 */
			[
				'mod'           => 'button_style',
				'label'         => __( 'Appearance', 'wc-buy-one-get-one-free' ),
				'type'          => 'select',
				'wrapper_class' => 'wc-bogof-spacing',
				'choices'       => [
					''       => 'Theme default',
					'custom' => 'Custom',
				],

			],

			/**
			 * Button color.
			 */
			[
				'mod'   => 'button_color',
				'label' => __( 'Text color', 'wc-buy-one-get-one-free' ),
				'type'  => 'WP_Customize_Color_Control',
			],


			/**
			 * Button Background color.
			 */
			[
				'mod'   => 'button_bg_color',
				'label' => __( 'Background color', 'wc-buy-one-get-one-free' ),
				'type'  => 'WP_Customize_Color_Control',
			],

			/**
			 * Button color hover.
			 */
			[
				'mod'   => 'button_color_h',
				'label' => __( 'Hover text color', 'wc-buy-one-get-one-free' ),
				'type'  => 'WP_Customize_Color_Control',
			],

			/**
			 * Button Background color hover.
			 */
			[
				'mod'   => 'button_bg_color_h',
				'label' => __( 'Hover background color', 'wc-buy-one-get-one-free' ),
				'type'  => 'WP_Customize_Color_Control',
			],

			/**
			 * Button font.
			 */
			[
				'label'         => __( 'Font', 'wc-buy-one-get-one-free' ),
				'type'          => 'WC_BOGOF_Customize_Control_Font_Manager',
				'mod'           =>
				[
					'font-weight'    => 'button_font.font-weight',
					'text-transform' => 'button_font.text-transform',
					'font-size'      => 'button_font.font-size',
				],
				'wrapper_class' => 'wc-bogof-spacing wc-bogof-divider',
			],

			/**
			 * Button padding.
			 */
			[
				'label'         => __( 'Padding', 'wc-buy-one-get-one-free' ),
				'type'          => 'WC_BOGOF_Customize_Control_Box_Sides',
				'unit'          => 'px',
				'mod'           =>
				[
					'top'    => 'button_padding.top',
					'right'  => 'button_padding.right',
					'bottom' => 'button_padding.bottom',
					'left'   => 'button_padding.left',
				],
				'wrapper_class' => 'wc-bogof-spacing wc-bogof-divider',
			],

			/**
			 * Button radius.
			 */
			[
				'label'         => __( 'Border radius', 'wc-buy-one-get-one-free' ),
				'type'          => 'WC_BOGOF_Customize_Control_Box_Sides',
				'unit'          => 'px',
				'mod'           =>
				[
					'top-left'     => 'button_radius.top',
					'top-right'    => 'button_radius.right',
					'bottom-right' => 'button_radius.bottom',
					'bottom-left'  => 'button_radius.left',
				],
				'wrapper_class' => 'wc-bogof-spacing',
			],

		],
	],

	[
		'id'       => 'gifts_notice',
		'title'    => __( 'Available Gift(s) Notice', 'wc-buy-one-get-one-free' ),
		'priority' => 20,
		'controls' => [

			/**
			 * Notice text.
			 */
			[
				'mod'           => 'notice_text',
				'label'         => __( 'Notice', 'wc-buy-one-get-one-free' ),
				'type'          => 'text',
				'wrapper_class' => 'wc-bogof-spacing',
				'description'   => __( 'This text will inform the customer that he can choose a gift. Use [qty] for the number of items.', 'wc-buy-one-get-one-free' ),
				'input_attrs'   => [
					'placeholder' => wc_bogof_gift_default_notice_text( '[qty]', [ 100 ] ),
				],
			],

			/**
			 * Notice button text.
			 */
			[
				'mod'           => 'notice_button_text',
				'label'         => __( 'Button text', 'wc-buy-one-get-one-free' ),
				'type'          => 'text',
				'wrapper_class' => 'wc-bogof-bottom-spacing wc-bogof-divider',
				'input_attrs'   => [
					'placeholder' => WC_BOGOF_Mods::default( 'notice_button_text' ),
				],
			],

			/**
			 * Display in shop.
			 */
			[
				'mod'           => 'notice_shop_display',
				'label'         => __( 'Show on the shop pages', 'wc-buy-one-get-one-free' ),
				'type'          => 'WC_BOGOF_Customize_Control_Toggle',
				'wrapper_class' => 'wc-bogof-spacing wc-bogof-divider',
				'description'   => __( 'The notice appears in the cart and checkout pages. Enable this option to display the notification on all shop pages.', 'wc-buy-one-get-one-free' ),
			],

			/**
			 * Notice style.
			 */
			[
				'mod'           => 'notice_style',
				'label'         => __( 'Appearance', 'wc-buy-one-get-one-free' ),
				'type'          => 'select',
				'wrapper_class' => 'wc-bogof-spacing',
				'choices'       => [
					'woo'          => 'WooCommerce notice',
					'announcement' => 'Announcement bar',
				],

			],

			/**
			 * Text color.
			 */
			[
				'mod'   => 'notice_text_color',
				'label' => __( 'Text color', 'wc-buy-one-get-one-free' ),
				'type'  => 'WP_Customize_Color_Control',
			],

			/**
			 * Background color.
			 */
			[
				'mod'   => 'notice_bg_color',
				'label' => __( 'Background color', 'wc-buy-one-get-one-free' ),
				'type'  => 'WP_Customize_Color_Control',
			],

			/**
			 * Text color.
			 */
			[
				'mod'   => 'notice_button_text_color',
				'label' => __( 'Button text color', 'wc-buy-one-get-one-free' ),
				'type'  => 'WP_Customize_Color_Control',
			],

			/**
			 * Background color.
			 */
			[
				'mod'   => 'notice_button_bg_color',
				'label' => __( 'Button background color', 'wc-buy-one-get-one-free' ),
				'type'  => 'WP_Customize_Color_Control',
			],

			/**
			 * Display in shop.
			 */
			[
				'mod'           => 'notice_bar_sticky',
				'label'         => __( 'Sticky', 'wc-buy-one-get-one-free' ),
				'type'          => 'WC_BOGOF_Customize_Control_Toggle',
				'wrapper_class' => 'wc-bogof-spacing',
				'description'   => __( 'Enable this option to stick the announcement bar to the top of the screen.', 'wc-buy-one-get-one-free' ),
			],
		],
	],

];
