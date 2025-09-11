<?php
/**
 * Coupon actions.
 *
 * @since 5.1
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Coupon Class
 */
class WC_BOGOF_Coupon {

	/**
	 * Cache coupons.
	 *
	 * @var array
	 */
	private static $cache = [];

	/**
	 * Coupons factory getter.
	 *
	 * @param string $coupon ID or code.
	 * @return WC_BOGOF_Coupon
	 */
	public static function get( $coupon ) {
		if ( empty( static::$cache[ $coupon ] ) ) {
			static::$cache[ $coupon ] = new static( $coupon );
		}

		return static::$cache[ $coupon ];
	}

	/**
	 * Coupon ID or code.
	 *
	 * @var string
	 */
	protected $coupon;

	/**
	 * Array of WC_BOGOF_Rules.
	 *
	 * @var array
	 */
	protected $rules;

	/**
	 * Whether the coupon in at least one rule or not.
	 *
	 * @var bool
	 */
	protected $is_in_rule;

	/**
	 * Conditions that has the coupon.
	 *
	 * @var bool
	 */
	protected $conditions;

	/**
	 * Constructor
	 *
	 * @param string $coupon ID or code.
	 */
	private function __construct( $coupon ) {
		$this->coupon     = strtolower( strval( $coupon ) );
		$this->rules      = $this->get_rules();
		$this->is_in_rule = count( $this->rules ) > 0;
	}

	/**
	 * Is in rule getter.
	 *
	 * @return bool
	 */
	public function is_in_rule() {
		return $this->is_in_rule;
	}

	/**
	 * Whether the coupon is applicable for the current cart contents or not.
	 *
	 * @return bool
	 */
	public function is_applicable() {
		$validate = false;

		foreach ( $this->rules as $rule ) {

			if ( ! ( $rule->is_enabled() && $rule->validate() ) ) {
				continue;
			}

			$cart = is_callable( [ WC()->cart, 'get_cart_contents' ] ) ? WC()->cart->get_cart_contents() : [];

			foreach ( $cart as $cart_item ) {

				$product_id = isset( $cart_item['data'] ) && is_callable( array( $cart_item['data'], 'get_id' ) ) ? $cart_item['data']->get_id() : false;
				if ( ! $product_id || WC_BOGOF_Cart::is_free_item( $cart_item ) ) {
					continue;
				}

				$cart_rule = WC_BOGOF_Cart_Rules::create( $rule, $product_id );

				if ( $cart_rule->match() ) {
					$validate = true;
					break 2;
				}
			}
		}

		return $validate;
	}

	/**
	 * Returns the rules that have this coupon as condition.
	 * Removes the "coupon condition" before return it.
	 */
	protected function get_rules() {
		$data_store   = WC_Data_Store::load( 'bogof-rule' );
		$rules        = $data_store->get_rules();
		$coupon_rules = array();

		foreach ( $rules as $rule ) {

			if ( ! ( $rule->is_enabled() ) ) {
				continue;
			}

			$conditions = $rule->get_conditions();
			$delete     = [];

			foreach ( $conditions as $group_id => $group ) {
				if ( ! is_array( $group ) ) {
					continue;
				}

				foreach ( $group as $condition_id => $data ) {
					$condition = empty( $data['type'] ) || empty( $data['modifier'] ) ? false : WC_BOGOF_Conditions::get_condition( $data['type'] );

					if ( ! $condition || ! is_callable( [ $condition, 'get_coupons_from_data' ] ) ) {
						continue;
					}

					$coupons = $condition->get_coupons_from_data( $data );

					if ( in_array( $this->coupon, $coupons, true ) ) {
						$delete[] = [
							'group'     => $group_id,
							'condition' => $condition_id,
						];
					}
				}
			}

			if ( count( $delete ) ) {
				foreach ( $delete as $unset ) {
					unset( $conditions[ $unset['group'] ][ $unset['condition'] ] );
				}

				$rule->set_conditions( $conditions );

				$coupon_rules[] = $rule;
			}
		}

		return $coupon_rules;
	}
}
