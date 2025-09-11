<?php
/**
 * BOGO settings metabox fields.
 *
 * Returns an array of fields for the current rule.
 *
 * @var WC_BOGOF_Rule $rule The current BOGO rule.
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $rule ) ) {
	return array();
}

return array(

	/**
	 * Nonce
	 */
	array(
		'id'    => 'woocommerce_meta_nonce',
		'value' => 'woocommerce_save_data',
		'type'  => 'nonce',
	),

	/**
	 * Enabled
	 */
	array(
		'id'    => '_enabled',
		'label' => __( 'Active', 'wc-buy-one-get-one-free' ),
		'value' => $rule->get_enabled(),
		'type'  => 'true-false',
	),

	/**
	 * Promotion type.
	 */
	array(
		'id'     => 'promotion_type',
		'label'  => __( 'Promotion type', 'wc-buy-one-get-one-free' ),
		'type'   => 'group',
		'fields' => array(
			array(
				'id'      => '_type',
				'options' => wc_bogof_rule_type_options(),
				'value'   => $rule->get_type(),
				'type'    => 'select',
			),

			array(
				'type'    => 'message',
				'value'   => __( 'Use this type to create offers "Buy one, get one free," 3x2, ...', 'wc-buy-one-get-one-free' ),
				'show-if' => array(
					array(
						'field'    => '_type',
						'operator' => '=',
						'value'    => 'cheapest_free',
					),
				),
			),

			array(
				'type'    => 'message',
				'value'   => __( 'Reward the customer with a gift when he purchases other products', 'wc-buy-one-get-one-free' ),
				'show-if' => array(
					array(
						'field'    => '_type',
						'operator' => '=',
						'value'    => 'buy_a_get_b',
					),
				),
			),

			array(
				'type'    => 'message',
				'value'   => __( 'Automatically add the same product that the customer buys at a special price to the cart.', 'wc-buy-one-get-one-free' ),
				'show-if' => array(
					array(
						'field'    => '_type',
						'operator' => '=',
						'value'    => 'buy_a_get_a',
					),
				),
			),
		),
	),

	/**
	 * Ignore other rules
	 */
	array(
		'id'      => '_exclude_other_rules',
		'label'   => __( 'Priority', 'wc-buy-one-get-one-free' ),
		'message' => __( 'Ignore the other Buy One Get One Free promotions if this promotion is active in the cart', 'wc-buy-one-get-one-free' ),
		'value'   => $rule->get_exclude_other_rules(),
		'type'    => 'true-false',
	),

	/**
	 * Apply promotion to
	 */
	array(
		'id'          => '_applies_to',
		'label'       => __( 'Apply promotion to', 'wc-buy-one-get-one-free' ),
		'description' => __( 'Select the products that the customer has to buy to get the promotion', 'wc-buy-one-get-one-free' ),
		'type'        => 'conditional-logic',
		'value'       => $rule->get_applies_to(),
		'default'     => [ [ [ 'type' => 'all_products' ] ] ],
	),

	/**
	 * Gift action
	 */
	array(
		'id'          => 'action_group',
		'label'       => __( 'Gift', 'wc-buy-one-get-one-free' ),
		'description' => __( 'Select the products the customer will receive as a gift', 'wc-buy-one-get-one-free' ),
		'type'        => 'group',
		'fields'      => array(
			array(
				'id'      => '_action',
				'options' => array(
					'add_to_cart' => __( 'Add the gift automatically to the cart', 'wc-buy-one-get-one-free' ),
					'choose_from' => __( 'Customers can choose the gift', 'wc-buy-one-get-one-free' ),
				),
				'value'   => $rule->is_action( 'add_to_cart' ) ? 'add_to_cart' : 'choose_from',
				'type'    => 'select',
			),
			array(
				'id'                => '_free_product_id',
				'multiple'          => false,
				'value'             => array( $rule->get_free_product_id() ),
				'custom_attributes' => array(
					'data-action'  => 'wc_bogof_json_search_free_products',
					'data-exclude' => implode( ',', array_merge( wc_bogof_variable_types(), wc_bogof_incompatible_product_types() ) ),
				),
				'type'              => 'search-product',
				'show-if'           => array(
					array(
						'field'    => '_action',
						'operator' => '=',
						'value'    => 'add_to_cart',
					),
				),
			),
			array(
				'type'    => 'message',
				'value'   => __( "Can't find a product? Only supported products can be selected", 'wc-buy-one-get-one-free' ),
				'show-if' => array(
					array(
						'field'    => '_action',
						'operator' => '=',
						'value'    => 'add_to_cart',
					),
				),
			),
			array(
				'id'      => '_gift_products',
				'label'   => __( 'Choose from', 'wc-buy-one-get-one-free' ),
				'type'    => 'conditional-logic',
				'value'   => $rule->get_gift_products(),
				'show-if' => array(
					array(
						'field'    => '_type',
						'operator' => '=',
						'value'    => 'buy_a_get_b',
					),
					array(
						'field'    => '_action',
						'operator' => '=',
						'value'    => 'choose_from',
					),
				),
			),
		),
		'show-if'     => array(
			array(
				'field'    => '_type',
				'operator' => '=',
				'value'    => 'buy_a_get_b',
			),
		),
	),

	/**
	 * Quantity rules
	 */
	array(
		'id'          => '_quantity_rules',
		'label'       => __( 'Offer details', 'wc-buy-one-get-one-free' ),
		'description' => __( 'How many units does the customer have to buy to get X units at a special price?', 'wc-buy-one-get-one-free' ),
		'type'        => 'repeater',
		'value'       => $rule->get_quantity_rules(),
		'fields'      => array(
			array(
				'id'                => 'cart_quantity',
				'label'             => __( 'If customer buys', 'wc-buy-one-get-one-free' ) . ':',
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => 1,
					'min'  => 1,
				),
			),
			array(
				'id'                => 'free_quantity',
				'label'             => __( 'Gets', 'wc-buy-one-get-one-free' ) . ':',
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => 1,
					'min'  => 1,
				),
			),
			array(
				'id'                => 'discount',
				'label'             => __( 'Discount (%)', 'wc-buy-one-get-one-free' ) . ':',
				'type'              => 'number',
				'default'           => 100,
				'custom_attributes' => array(
					'step' => 1,
					'min'  => 1,
					'max'  => 100,
				),
			),
			array(
				'id'                => 'cart_limit',
				'label'             => __( 'Offer limit', 'wc-buy-one-get-one-free' ),
				'description'       => __( 'The plugin calculates the number of products the customer will get using multiples of the "buy quantity." For example, if you set buy 3 get 2 and the customer purchases 6, he will get 4. Use this field to limit the number of products the customer can get', 'wc-buy-one-get-one-free' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'step'        => 1,
					'min'         => 0,
					'placeholder' => esc_attr__( 'Unlimited', 'wc-buy-one-get-one-free' ),
				),
			),
		),
		'btn_label'   => __( 'Add quantity rule', 'wc-buy-one-get-one-free' ),
	),

	/**
	 * Individual
	 */
	array(
		'id'      => '_individual',
		'label'   => __( 'Individual', 'wc-buy-one-get-one-free' ),
		'message' => __( 'Count the quantities per product', 'wc-buy-one-get-one-free' ),
		'value'   => $rule->get_individual(),
		'type'    => 'true-false',
		'show-if' => array(
			array(
				'field'    => '_type',
				'operator' => '!=',
				'value'    => 'buy_a_get_a',
			),
		),
	),

	/**
	 * Individual
	 */
	array(
		'id'          => '_select_variation',
		'label'       => __( 'Variations', 'wc-buy-one-get-one-free' ),
		'description' => __( 'How should the promotion work for variations?', 'wc-buy-one-get-one-free' ),
		'message'     => __( 'Group the variations of the same product.', 'wc-buy-one-get-one-free' ),
		'input-info'  => __( 'Turn on this option to the promotion considers all variations of the same product as one unique product. This will allow the user to select the variation.', 'wc-buy-one-get-one-free' ),
		'value'       => $rule->get_select_variation(),
		'type'        => 'true-false',
		'show-if'     => array(
			array(
				'field'    => '_type',
				'operator' => '=',
				'value'    => 'buy_a_get_a',
			),
		),
	),

	/**
	 * Schedule
	 */
	array(
		'id'     => 'schedule_switch',
		'type'   => 'group',
		'label'  => __( 'Schedule', 'wc-buy-one-get-one-free' ),
		'fields' => array(
			array(
				'id'    => 'schedule_switch',
				'value' => is_a( $rule->get_start_date( 'edit' ), 'WC_DateTime' ) || is_a( $rule->get_end_date( 'edit' ), 'WC_DateTime' ),
				'type'  => 'true-false',
			),
			array(
				'id'      => 'schedule_dates',
				'type'    => 'table',
				'fields'  => array(
					array(
						'id'                => '_start_date',
						'type'              => 'date-picker',
						'label'             => __( 'Start date', 'wc-buy-one-get-one-free' ),
						'value'             => $rule->get_start_date( 'edit' ) ? $rule->get_start_date( 'edit' )->getOffsetTimestamp() : false,
						'custom_attributes' => array(
							'placeholder' => esc_attr__( 'From', 'wc-buy-one-get-one-free' ),
						),
					),
					array(
						'id'                => '_end_date',
						'type'              => 'date-picker',
						'label'             => __( 'End date', 'wc-buy-one-get-one-free' ),
						'value'             => $rule->get_end_date( 'edit' ) ? $rule->get_end_date( 'edit' )->getOffsetTimestamp() : false,
						'custom_attributes' => array(
							'placeholder' => esc_attr__( 'To', 'wc-buy-one-get-one-free' ),
						),
					),
				),
				'show-if' => array(
					array(
						'field'    => 'schedule_switch',
						'operator' => '=',
						'value'    => 'yes',
					),
				),
			),
		),
	),

);
