<?php
/**
 * Buy One Get One Free rule class
 *
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Rule Class
 */
class WC_BOGOF_Rule extends WC_Data {

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'bogof_rule';

	/**
	 * Post status.
	 *
	 * @var string
	 */
	protected $post_status = '';

	/**
	 * Stores data.
	 *
	 * @var array
	 */
	protected $data = array(
		'title'                     => '',
		'date_created'              => null,
		'date_modified'             => null,
		'enabled'                   => true,
		'type'                      => '',
		'applies_to'                => '',
		'action'                    => '',
		'free_product_id'           => '',
		'gift_products'             => array(),
		'individual'                => false,
		'select_variation'          => false,
		'quantity_rules'            => array(),
		'conditions'                => array(),
		'start_date'                => null,
		'end_date'                  => null,
		'exclude_other_rules'       => false,
		'exclude_coupon_validation' => true,
	);

	/**
	 * Rule constructor. Loads rule data.
	 *
	 * @param int|WC_BOGOF_Rule|object $data WC_BOGOF_Rule to init.
	 */
	public function __construct( $data = 0 ) {
		parent::__construct();

		if ( is_numeric( $data ) && $data > 0 ) {
			$this->set_id( $data );
		} elseif ( $data instanceof self ) {
			$this->set_id( absint( $data->get_id() ) );
		} elseif ( ! empty( $data->ID ) ) {
			$this->set_id( absint( $data->ID ) );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = WC_Data_Store::load( 'bogof-rule' );
		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	|
	*/

	/**
	 * Get post status.
	 *
	 * @return string
	 */
	public function get_post_status() {
		return $this->post_status;
	}

	/**
	 * Get rule title.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_title( $context = 'view' ) {
		return $this->get_prop( 'title', $context );
	}

	/**
	 * Get rule created date.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get rule modified date.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Get enabled.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return bool
	 */
	public function get_enabled( $context = 'view' ) {
		return $this->get_prop( 'enabled', $context );
	}

	/**
	 * Returns the type.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_type( $context = 'view' ) {
		return $this->get_prop( 'type', $context );
	}

	/**
	 * Returns the applies to.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_applies_to( $context = 'view' ) {
		return $this->get_prop( 'applies_to', $context );
	}

	/**
	 * Get individual.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return bool
	 */
	public function get_individual( $context = 'view' ) {
		return $this->get_prop( 'individual', $context );
	}

	/**
	 * Get select variation.
	 *
	 * @since 5.1
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return bool
	 */
	public function get_select_variation( $context = 'view' ) {
		return $this->get_prop( 'select_variation', $context );
	}

	/**
	 * Returns the action.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_action( $context = 'view' ) {
		return $this->get_prop( 'action', $context );
	}

	/**
	 * Returns the free product id.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_free_product_id( $context = 'view' ) {
		return $this->get_prop( 'free_product_id', $context );
	}

	/**
	 * Returns the gift products.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return array
	 */
	public function get_gift_products( $context = 'view' ) {
		if ( $this->is_action( 'add_to_cart' ) ) {
			return array(
				array(
					array(
						'type'     => 'product',
						'modifier' => 'in',
						'value'    => array( $this->get_free_product_id() ),
					),
				),
			);
		} else {
			return $this->get_prop( 'gift_products', $context );
		}
	}

	/**
	 * Returns the quantity_ ules. How many units does the customer have to buy to get X units at a special price?
	 *
	 * @since 4.0
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return array
	 */
	public function get_quantity_rules( $context = 'view' ) {
		return $this->get_prop( 'quantity_rules', $context );
	}

	/**
	 * Returns the conditions.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_conditions( $context = 'view' ) {
		return $this->get_prop( 'conditions', $context );
	}

	/**
	 * Returns the start date.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return WC_DateTime|NULL
	 */
	public function get_start_date( $context = 'view' ) {
		return $this->get_prop( 'start_date', $context );
	}

	/**
	 * Returns the end date.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return WC_DateTime|NULL
	 */
	public function get_end_date( $context = 'view' ) {
		return $this->get_prop( 'end_date', $context );
	}

	/**
	 * Returns exclude other rules property.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return bool
	 */
	public function get_exclude_other_rules( $context = 'view' ) {
		return $this->get_prop( 'exclude_other_rules', $context );
	}

	/**
	 * Returns exclude other rules property.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return bool
	 */
	public function get_exclude_coupon_validation( $context = 'view' ) {
		return $this->get_prop( 'exclude_coupon_validation', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	*/

	/**
	 * Set post status.
	 *
	 * The post status is handle by the enabled property. No update this property after object is read.
	 *
	 * @param string $post_status Post status.
	 */
	public function set_post_status( $post_status ) {
		if ( ! $this->get_object_read() ) {
			$this->post_status = $post_status;
		}
	}

	/**
	 * Set rule title.
	 *
	 * @param string $title Rule name.
	 */
	public function set_title( $title ) {
		$this->set_prop( 'title', trim( $title ) );
	}

	/**
	 * Set date_created
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 */
	public function set_date_created( $date ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set date_modified
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 */
	public function set_date_modified( $date ) {
		$this->set_date_prop( 'date_modified', $date );
	}

	/**
	 * Set if the rule is enabled.
	 *
	 * @param bool|string $enabled Whether rule is enabled or not.
	 */
	public function set_enabled( $enabled ) {
		$this->set_prop( 'enabled', wc_string_to_bool( $enabled ) );
	}

	/**
	 * Set the type.
	 *
	 * @param string $type Rule type.
	 */
	public function set_type( $type ) {
		$this->set_prop( 'type', $type );
	}

	/**
	 * Set the applies to.
	 *
	 * @param array $applies_to Applies to conditions.
	 */
	public function set_applies_to( $applies_to ) {
		$this->set_prop( 'applies_to', $applies_to );
	}

	/**
	 * The rule should be applied individually to each product of the category.
	 *
	 * @param bool|string $individual yes or no.
	 */
	public function set_individual( $individual ) {
		$this->set_prop( 'individual', wc_string_to_bool( $individual ) );
	}

	/**
	 * Allows customer to choose the variation (only for buy a get a promotions).
	 *
	 * @since 5.1
	 * @param bool|string $select_variation yes or no.
	 */
	public function set_select_variation( $select_variation ) {
		$this->set_prop( 'select_variation', wc_string_to_bool( $select_variation ) );
	}

	/**
	 * Set the action.
	 *
	 * @param string $action The action trigger after the customer buys the min qty.
	 */
	public function set_action( $action ) {
		$this->set_prop( 'action', $action );
	}

	/**
	 * Set the free product id.
	 *
	 * @param int|string $free_product_id Product to add to cart.
	 */
	public function set_free_product_id( $free_product_id ) {
		$this->set_prop( 'free_product_id', '' === $free_product_id ? '' : absint( $free_product_id ) );
	}

	/**
	 * Set the gift products.
	 *
	 * @param array $gift_products Gift products conditions.
	 */
	public function set_gift_products( $gift_products ) {
		$this->set_prop( 'gift_products', $gift_products );
	}

	/**
	 * Set the quantity rules.
	 *
	 * @since 4.0
	 * @param array $quantity_rules The quantity rules. How many units does the customer have to buy to get X units at a special price?.
	 */
	public function set_quantity_rules( $quantity_rules ) {
		if ( ! is_array( $quantity_rules ) ) {
			return;
		}

		$data = [];

		foreach ( $quantity_rules as $row ) {

			if ( ! isset( $row['cart_quantity'], $row['free_quantity'] ) ) {
				continue;
			}

			if ( '' === $row['cart_quantity'] || '' === $row['free_quantity'] ) {
				continue;
			}

			$cart_quantity = absint( $row['cart_quantity'] );

			$data[ $cart_quantity ] = [
				'cart_quantity' => $cart_quantity,
				'free_quantity' => absint( $row['free_quantity'] ),
				'discount'      => empty( $row['discount'] ) ? '100' : absint( $row['discount'] ),
				'cart_limit'    => empty( $row['cart_limit'] ) ? '' : absint( $row['cart_limit'] ),
			];
		}

		ksort( $data, SORT_NUMERIC );

		$this->set_prop( 'quantity_rules', $data );
	}

	/**
	 * Set the conditions.
	 *
	 * @param array $conditions Array of conditions.
	 */
	public function set_conditions( $conditions ) {
		$this->set_prop( 'conditions', $conditions );
	}

	/**
	 * Set the start date.
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. The deal will begin at 00:00 of this date.
	 */
	public function set_start_date( $date ) {
		$this->set_date_prop( 'start_date', $date );
	}

	/**
	 * Set the end date.
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. The deal will end at 23:59 of this date.
	 */
	public function set_end_date( $date ) {
		$this->set_date_prop( 'end_date', $date );
	}

	/**
	 * Set exclude other rules property.
	 *
	 * @param bool|string $exclude_other_rules Whether rule excludes the other rules or not.
	 */
	public function set_exclude_other_rules( $exclude_other_rules ) {
		$this->set_prop( 'exclude_other_rules', wc_string_to_bool( $exclude_other_rules ) );
	}

	/**
	 * Set exclude coupon validation property.
	 *
	 * @param bool|string $exclude_coupon_validation Should the free item be excluded from the coupon validations?.
	 */
	public function set_exclude_coupon_validation( $exclude_coupon_validation ) {
		$this->set_prop( 'exclude_coupon_validation', wc_string_to_bool( $exclude_coupon_validation ) );
	}

	/*
	|--------------------------------------------------------------------------
	| No CRUD Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Returns the number of times a user used a rule.
	 *
	 * @since 3.2.0 Returns the total usages if $used_by param is omitted.
	 *
	 * @param string|array $used_by Either user ID or billing email.
	 */
	public function get_usage_count( $used_by = null ) {
		$value = $this->data_store->get_usage_count( $used_by, $this );
		return apply_filters( 'wc_bogof_rule_get_usage_count', $value, $used_by, $this );
	}

	/**
	 * Returns the rule priority as int.
	 *
	 * @return int
	 */
	public function get_priority() {
		$priority  = 0;
		$priority += 'cheapest_free' === $this->get_type() ? 10 : 0;
		$priority += $this->get_exclude_other_rules() ? 20 : 0;

		return apply_filters( 'wc_bogof_rule_get_priority', $priority, $this );
	}

	/**
	 * Returns the min quantity.
	 *
	 * @return string|int
	 */
	public function get_min_cart_quantity() {
		$min = '';
		if ( count( $this->get_quantity_rules() ) ) {
			$offers = $this->get_quantity_rules();
			$min    = key( $offers );
		}

		return $min;
	}

	/**
	 * Returns the free quantity.
	 *
	 * @return string|int
	 */
	public function get_min_free_quantity() {
		$free_quantity = '';
		$min_quantity  = $this->get_min_cart_quantity();

		if ( $min_quantity ) {
			$offer         = $this->get_quantity_rules();
			$free_quantity = $offer[ $min_quantity ]['free_quantity'];
		}

		return $free_quantity;
	}

	/**
	 * Returns the discount.
	 *
	 * @since 3.0
	 * @return string
	 */
	public function get_min_discount() {
		$discount     = '';
		$min_quantity = $this->get_min_cart_quantity();

		if ( $min_quantity ) {
			$offer    = $this->get_quantity_rules();
			$discount = $offer[ $min_quantity ]['discount'];
		}

		return $discount;
	}

	/*
	|--------------------------------------------------------------------------
	| Validate properties.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Validate the properites of the object and retrun the errors.
	 *
	 * @return WP_Error
	 */
	public function validate_props() {
		$error = new WP_Error();

		if ( ! $this->get_enabled() ) {
			// No validations if disabled.
			return true;
		}

		if ( ! is_array( $this->get_applies_to() ) || ! count( $this->get_applies_to() ) || ! count( $this->get_applies_to()[0] ) ) {
			// Please add at least one condition.
			$error->add( 'applies_to_empty', __( 'Please add at least one condition', 'wc-buy-one-get-one-free' ), 'applies_to' );
		}

		if ( 'buy_a_get_b' === $this->get_type() ) {

			if ( 'add_to_cart' === $this->get_action() && empty( $this->get_free_product_id() ) ) {
				// Please add at least one item.
				$error->add( 'free_product_id_empty', __( 'Please add at least one item', 'wc-buy-one-get-one-free' ), 'free_product_id' );

			} elseif ( ! is_array( $this->get_gift_products() ) || ! count( $this->get_gift_products() ) || ! count( $this->get_gift_products()[0] ) ) {
				// Please add at least one condition.
				$error->add( 'gift_products_empty', __( 'Please add at least one condition', 'wc-buy-one-get-one-free' ), 'gift_products' );
			}
		}

		if ( 1 > count( $this->get_quantity_rules() ) ) {
			// Please enter the offer details.
			$error->add( 'quantity_rules_empty', __( 'Please enter the offer details', 'wc-buy-one-get-one-free' ), 'quantity_rules' );
		}

		foreach ( $this->get_quantity_rules() as $quantity_rule ) {
			// Please enter a value greater than 0.
			foreach ( array( 'cart_quantity', 'free_quantity', 'discount' ) as $prop ) {
				if ( 1 > $quantity_rule[ $prop ] ) {
					$error->add( "{$prop}_empty", __( 'Please enter a value greater than 0', 'wc-buy-one-get-one-free' ), "quantity_rules .-{$prop}" );
					break 2;
				}
			}

			// Please enter a value greater than or equal to %s. Or leave the field empty.
			if ( '' !== $quantity_rule['cart_limit'] && $quantity_rule['cart_limit'] < $quantity_rule['free_quantity'] ) {
				// translators: %s: number of free items.
				$error->add( 'cart_limit_less_than_free_quantity', sprintf( __( 'Please enter a value greater than or equal to %s. Or leave the field empty', 'wc-buy-one-get-one-free' ), $quantity_rule['free_quantity'] ), 'quantity_rules .-cart_limit' );
			}

			// Please enter a value less than 100.
			if ( 100 < $quantity_rule['discount'] ) {
				$error->add( 'discount_greater_100', __( 'Please enter a discount less than 100', 'wc-buy-one-get-one-free' ), 'quantity_rules .-discount' );
				break;
			}

			// Please enter a value less than the buy quantity.
			if ( 'cheapest_free' === $this->get_type() && $quantity_rule['cart_quantity'] <= $quantity_rule['free_quantity'] ) {
				$error->add( 'less_than_min', __( 'Please enter a value less than the buy quantity', 'wc-buy-one-get-one-free' ), 'quantity_rules .-free_quantity' );
				break;
			}
		}

		// Please enter a date greater than or equal to the start date.
		if ( $this->get_start_date() && $this->get_end_date() && $this->get_end_date()->getTimestamp() < $this->get_start_date()->getTimestamp() ) {
			$error->add( 'end_date_fail', __( 'Please enter a date greater than or equal to the start date', 'wc-buy-one-get-one-free' ), 'end_date' );
		}

		return count( $error->get_error_codes() ) ? $error : true;
	}

	/*
	|--------------------------------------------------------------------------
	| Conditionals
	|--------------------------------------------------------------------------
	*/

	/**
	 * Checks if a rule enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$enabled = $this->get_enabled();
		if ( $enabled && $this->get_end_date() && $this->get_end_date()->getTimestamp() < current_time( 'timestamp', true ) ) {
			$enabled = false;
		}
		if ( $enabled && $this->get_start_date() && $this->get_start_date()->getTimestamp() > current_time( 'timestamp', true ) ) {
			$enabled = false;
		}
		return $enabled;
	}

	/**
	 * Checks the rule action.
	 *
	 * @param string $action Action to check.
	 * @return bool
	 */
	public function is_action( $action ) {
		$is_action = false;
		switch ( $action ) {
			case 'add_to_cart':
				$is_action = 'buy_a_get_a' === $this->get_type() || $action === $this->get_action();
				break;
			default:
				$is_action = $action === $this->get_action();
				break;
		}
		return $is_action;
	}

	/**
	 * Should the rule be applied individually?
	 *
	 * @return bool
	 */
	public function is_individual() {
		return 'buy_a_get_a' === $this->get_type() || $this->get_individual();
	}

	/**
	 * Is a free product?
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public function is_free_product( $product_id ) {
		return WC_BOGOF_Conditions::check_conditions( $this->get_gift_products(), $product_id );
	}

	/**
	 * Is a buy product?
	 *
	 * @param array $cart_item Cart item to check.
	 * @return bool
	 */
	public function is_buy_product( $cart_item ) {
		return WC_BOGOF_Conditions::check_conditions( $this->get_applies_to(), $cart_item );
	}

	/**
	 * Whether rule meets the conditions or not.
	 *
	 * @return bool
	 */
	public function validate() {
		$conditions = $this->get_conditions();
		if ( empty( $conditions ) ) {
			$validate = true;
		} else {
			$validate = WC_BOGOF_Conditions::check_conditions( $conditions, $this );
		}
		return $validate;
	}

	/*
	|--------------------------------------------------------------------------
	| Other Actions
	|--------------------------------------------------------------------------
	*/

	/**
	 * Increase usage count for current rule.
	 *
	 * @param WC_Order $order Order object.
	 */
	public function increase_usage_count( $order ) {
		$this->data_store->increase_usage_count( $this, $order );
	}

	/*
	|--------------------------------------------------------------------------
	| Deprecated
	|--------------------------------------------------------------------------
	*/

	/**
	 * Returns the min quantity.
	 *
	 * @deprecated 4.0
	 * @return string|int
	 */
	public function get_min_quantity() {
		wc_deprecated_function( 'WC_BOGOF_Rule::get_min_quantity', '4.0.0', 'WC_BOGOF_Rule::get_min_cart_quantity' );
		return $this->get_min_cart_quantity();
	}

	/**
	 * Returns the free quantity.
	 *
	 * @deprecated 4.0
	 * @return string|int
	 */
	public function get_free_quantity() {
		wc_deprecated_function( 'WC_BOGOF_Rule::get_free_quantity', '4.0.0', 'WC_BOGOF_Rule::get_min_free_quantity' );
		return $this->get_min_free_quantity();
	}

	/**
	 * Returns the discount.
	 *
	 * @deprecated 4.0
	 * @return string
	 */
	public function get_discount() {
		wc_deprecated_function( 'WC_BOGOF_Rule::get_discount', '4.0.0', 'WC_BOGOF_Rule::get_min_discount' );
		return $this->get_min_discount();
	}

	/**
	 * Returns the cart limit.
	 *
	 * @deprecated 4.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_cart_limit( $context = 'view' ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::get_cart_limit', '4.0.0', 'WC_BOGOF_Rule::get_quantity_rules' );
		return '';
	}


	/**
	 * Returns the buy product ids.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @deprecated 3.0
	 * @return array
	 */
	public function get_buy_product_ids( $context = 'view' ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::get_buy_product_ids', '3.0' );
		return array();
	}

	/**
	 * Returns the buy category ids.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @deprecated 3.0
	 * @return array
	 */
	public function get_buy_category_ids( $context = 'view' ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::get_buy_category_ids', '3.0' );
		return array();
	}

	/**
	 * Returns the exclude product ids.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @deprecated 3.0
	 * @return array
	 */
	public function get_exclude_product_ids( $context = 'view' ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::get_exclude_product_ids', '3.0' );
		return array();
	}

	/**
	 * Returns the gift products.
	 *
	 * @deprecated
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return array
	 */
	public function get_free_product_ids( $context = 'view' ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::get_free_product_ids', '3.0' );
		return array();
	}

	/**
	 * Returns the free category ids.
	 *
	 * @deprecated
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return array
	 */
	public function get_free_category_ids( $context = 'view' ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::get_free_category_ids', '3.0' );
		return array();
	}

	/**
	 * Get minimum spend amount.
	 *
	 * @deprecated  5.1.0
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return float
	 */
	public function get_minimum_amount( $context = 'view' ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::get_minimum_amount', '5.1' );
		return 0;
	}

	/**
	 * Returns the allowed user roles.
	 *
	 * @deprecated  5.1.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return array
	 */
	public function get_allowed_user_roles( $context = 'view' ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::get_allowed_user_roles', '5.1' );
		return [];
	}

	/**
	 * Returns the usage limit per user.
	 *
	 * @deprecated  5.1.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_usage_limit_per_user( $context = 'view' ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::get_usage_limit_per_user', '5.1' );
		return 0;
	}

	/**
	 * Is the role available for the current user.
	 *
	 * @deprecated  5.1.0
	 * @return bool
	 */
	public function is_available_for_current_user_role() {
		wc_deprecated_function( 'WC_BOGOF_Rule::is_available_for_current_user_role', '5.1' );
		return true;
	}

	/**
	 * Is usage per user under the limit?.
	 *
	 * @deprecated  5.1.0
	 * @param array $user_ids Array of user IDs (ID and|or emails).
	 * @return bool
	 */
	public function is_usage_per_user_under_limit( $user_ids = array() ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::is_usage_per_user_under_limit', '5.1' );
		return true;
	}

	/**
	 * Set the buy product ids.
	 *
	 * @deprecated 3.0
	 * @param array $buy_product_ids The product that the rule applies to.
	 */
	public function set_buy_product_ids( $buy_product_ids ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::set_buy_product_ids', '3.0' );
	}

	/**
	 * Set the buy category ids.
	 *
	 * @deprecated 3.0
	 * @param array $buy_category_ids The product that the rule applies to.
	 */
	public function set_buy_category_ids( $buy_category_ids ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::set_buy_category_ids', '3.0' );
	}

	/**
	 * Set the exclude product ids.
	 *
	 * @deprecated 3.0
	 * @param array $exclude_product_ids Products that the rule will not be applied to.
	 */
	public function set_exclude_product_ids( $exclude_product_ids ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::set_exclude_product_ids', '3.0' );
	}

	/**
	 * Set the free product ids.
	 *
	 * @deprecated 3.0
	 * @param array $free_product_ids List of products from the customer can choosee for free.
	 */
	public function set_free_product_ids( $free_product_ids ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::set_free_product_ids', '3.0' );
	}

	/**
	 * Set the free category ids.
	 *
	 * @deprecated 3.0
	 * @param array $free_category_ids List of categories from the customer can choosee for free.
	 */
	public function set_free_category_ids( $free_category_ids ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::set_free_category_ids', '3.0' );
	}

	/**
	 * Set the minimum spend amount.
	 *
	 * @deprecated 5.1
	 * @param float $amount Minium amount.
	 */
	public function set_minimum_amount( $amount ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::set_minimum_amount', '5.1' );
	}

	/**
	 * Is a exclude product?
	 *
	 * @since 2.0.5
	 * @deprecated 3.0
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public function is_exclude_product( $product_id ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::is_exclude_product', '3.0' );
		return false;
	}

	/**
	 * Returns the number of times a user used a rule.
	 *
	 * @deprecated 3.0
	 * @param string|array $used_by Either user ID or billing email.
	 */
	public function get_used_by_count( $used_by ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::get_used_by_count', '3.0.0', 'WC_BOGOF_Rule::get_usage_count' );
		return $this->data_store->get_usage_count( $used_by, $this );
	}

	/**
	 * Set the min quantity.
	 *
	 * @deprecated 4.0
	 * @param int|string $min_quantity Min quantity the customer must buy to get the gift.
	 */
	public function set_min_quantity( $min_quantity ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::set_min_quantity', '4.0' );
	}

	/**
	 * Set the free quantity.
	 *
	 * @deprecated 4.0
	 * @param int|string $free_quantity Free qty.
	 */
	public function set_free_quantity( $free_quantity ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::set_free_quantity', '4.0' );
	}

	/**
	 * Set the discount.
	 *
	 * @deprecated 4.0
	 * @param int|string $discount Discount.
	 */
	public function set_discount( $discount ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::set_discount', '4.0' );
	}

	/**
	 * Set the cart limit.
	 *
	 * @deprecated 4.0
	 * @param int|string $cart_limit Free items limit in the cart.
	 */
	public function set_cart_limit( $cart_limit ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::set_cart_limit', '4.0', 'WC_BOGOF_Rule::set_quantity_rules' );
	}

	/**
	 * Set the allowed user roles.
	 *
	 * @deprecated 5.1
	 * @param array $allowed_user_roles User roles that the rule will be available.
	 */
	public function set_allowed_user_roles( $allowed_user_roles ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::set_allowed_user_role', '5.1' );
	}

	/**
	 * Set the usage limit per user.
	 *
	 * @deprecated 5.1
	 * @param string $usage_limit_per_user Limit of free items the user can get.
	 */
	public function set_usage_limit_per_user( $usage_limit_per_user ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::set_usage_limit_per_user', '5.1' );
	}

	/**
	 * Set the coupon ids.
	 *
	 * @deprecated 5.1
	 * @param array $coupon_ids Coupons that enable the rule.
	 */
	public function set_coupon_ids( $coupon_ids ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::set_coupon_ids', '5.1' );
	}

	/**
	 * Returns the coupon codes of a rule.
	 *
	 * @deprecated  5.1.0
	 * @return array
	 */
	public function get_coupon_codes() {
		wc_deprecated_function( 'WC_BOGOF_Rule::get_coupon_codes', '5.1' );
		return [];
	}

	/**
	 * Returns the coupon ids.
	 *
	 * @deprecated  5.1.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return array
	 */
	public function get_coupon_ids( $context = 'view' ) {
		wc_deprecated_function( 'WC_BOGOF_Rule::get_coupon_ids', '5.1' );
		return [];
	}
}
