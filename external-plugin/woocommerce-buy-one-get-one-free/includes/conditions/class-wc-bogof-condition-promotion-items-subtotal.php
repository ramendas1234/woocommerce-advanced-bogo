<?php
/**
 * Condition promotion items subtotal class.
 *
 * @since 5.3.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Promotion_Items_Subtotal Class
 */
class WC_BOGOF_Condition_Promotion_Items_Subtotal extends WC_BOGOF_Abstract_Condition {
	use WC_BOGOF_Condition_Price { get_value_metabox_field as protected _get_value_metabox_field; }

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id       = 'promotion_items_subtotal';
		$this->title    = sprintf(
			'%s - %s',
			__( 'Promotion', 'wc-buy-one-get-one-free' ),
			__( 'Items subtotal', 'wc-buy-one-get-one-free' )
		);
		$this->supports = array( '_conditions' );
	}

	/**
	 * Returns an array with the proprerties of the metabox field.
	 *
	 * @return array
	 */
	public function get_value_metabox_field() {
		$field = $this->_get_value_metabox_field();
		$field['custom_attributes']['data-info-tooltips'] = wp_json_encode(
			[
				[
					'message' => __( 'The cart subtotal for only the products included in the "Apply promotion to" field.', 'wc-buy-one-get-one-free' ),
				],
			]
		);

		return $field;
	}

	/**
	 * Evaluate a condition field.
	 *
	 * @param array $data   Condition field data.
	 * @param mixed $value  Value to check.
	 * @return boolean
	 */
	public function check_condition( $data, $value = null ) {
		if ( ! is_a( $value, 'WC_BOGOF_Rule' ) ) {
			return false;
		}

		$data     = $this->sanitize( $data );
		$subtotal = $this->multicurrency_to_base(
			$this->get_subtotal( $value )
		);

		return $this->validate( $subtotal, $data['value'], $data['modifier'] );
	}

	/**
	 * Returns the subtotal for the promotion items.
	 *
	 * @param WC_BOGOF_Rule $rule Rule instance.
	 * @return float
	 */
	protected function get_subtotal( $rule ) {
		$subtotal       = 0;
		$items_subtotal = WC_BOGOF_Cart_Totals::instance()->get_cart_items_subtotal();
		$items_discount = WC_BOGOF_Cart_Totals::instance()->get_cart_items_discount();

		foreach ( WC()->cart->get_cart() as $key => $cart_item ) {

			if ( ! ( isset( $items_subtotal[ $key ] ) && $rule->is_buy_product( $cart_item ) ) ) {
				continue;
			}

			$subtotal += $items_subtotal[ $key ];

			if ( ! empty( $items_discount[ $key ] ) ) {
				$subtotal -= $items_discount[ $key ];
			}
		}

		return $subtotal;
	}
}
