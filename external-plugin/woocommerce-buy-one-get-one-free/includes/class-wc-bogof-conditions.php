<?php
/**
 * Handle the conditions.
 *
 * @since 3.0.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Conditions Class
 */
class WC_BOGOF_Conditions {

	/**
	 * Array of registered condition classes.
	 *
	 * @var array
	 */
	private static $conditions = array();

	/**
	 * Array of extra data.
	 *
	 * @var array
	 */
	private static $data = array();

	/**
	 * Init hooks.
	 */
	public static function init() {
		// Init conditions after register taxonomies and post types.
		add_action( 'init', [ __CLASS__, 'load_conditions' ], 9999 );
	}

	/**
	 * Init conditions.
	 */
	public static function load_conditions() {
		// Interfaces.
		include_once dirname( __FILE__ ) . '/interfaces/wc-bogof-condition-coupon-interface.php';

		// Traits.
		include_once dirname( __FILE__ ) . '/traits/trait-wc-bogof-condition-array.php';
		include_once dirname( __FILE__ ) . '/traits/trait-wc-bogof-condition-integer.php';
		include_once dirname( __FILE__ ) . '/traits/trait-wc-bogof-condition-string.php';
		include_once dirname( __FILE__ ) . '/traits/trait-wc-bogof-condition-price.php';
		include_once dirname( __FILE__ ) . '/traits/trait-wc-bogof-condition-customer-address.php';
		include_once dirname( __FILE__ ) . '/traits/trait-wc-bogof-condition-customer-history.php';

		// Load the abstract condition class.
		include_once dirname( __FILE__ ) . '/abstracts/class-wc-bogof-abstract-condition.php';
		include_once dirname( __FILE__ ) . '/abstracts/class-wc-bogof-abstract-condition-product-base.php';
		include_once dirname( __FILE__ ) . '/conditions/class-wc-bogof-condition-taxonomy.php';

		// Add the conditions to the array.
		self::$conditions = array();
		$load_conditions  = array(
			'WC_BOGOF_Condition_All_Products',
			'WC_BOGOF_Condition_Product',
			'WC_BOGOF_Condition_Product_Type',
			'WC_BOGOF_Condition_Variation_Attribute',
			'WC_BOGOF_Condition_Product_In_Cart',
			'WC_BOGOF_Condition_On_Sale',
			'WC_BOGOF_Condition_Customer_Is_Logged',
			'WC_BOGOF_Condition_Customer_Email',
			'WC_BOGOF_Condition_Customer_Role',
			'WC_BOGOF_Condition_Customer_Billing_Country',
			'WC_BOGOF_Condition_Customer_Billing_City',
			'WC_BOGOF_Condition_Customer_Billing_Postcode',
			'WC_BOGOF_Condition_Customer_Shipping_Country',
			'WC_BOGOF_Condition_Customer_Shipping_City',
			'WC_BOGOF_Condition_Customer_Shipping_Postcode',
			'WC_BOGOF_Condition_Customer_History_Total_Orders',
			'WC_BOGOF_Condition_Customer_History_Total_Spend',
			'WC_BOGOF_Condition_Customer_History_Avg_Order_Value',
			'WC_BOGOF_Condition_Cart_Subtotal',
			'WC_BOGOF_Condition_Cart_Virtual_Coupon',
			'WC_BOGOF_Condition_Cart_Coupons',
			'WC_BOGOF_Condition_Cart_Items_Count',
			'WC_BOGOF_Condition_Promotion_Usage_Limit',
			'WC_BOGOF_Condition_Promotion_Items_Subtotal',
		);

		foreach ( $load_conditions as $classname ) {
			$condition = self::load_condition( $classname );

			self::$conditions[ $condition->get_id() ] = $condition;
		}

		// Add the taxonomies conditions.
		foreach ( get_object_taxonomies( 'product', 'objects' ) as $taxonomy ) {
			if ( $taxonomy->show_ui && $taxonomy->show_in_menu ) {

				$condition = new WC_BOGOF_Condition_Taxonomy( $taxonomy->name, $taxonomy->labels->singular_name );

				self::$conditions[ $condition->get_id() ] = $condition;
			}
		}

		// Allow third-parties to add custom conditions.
		$third_party_conditons = apply_filters( 'wc_bogof_load_conditions', array() );
		if ( is_array( $third_party_conditons ) ) {
			foreach ( $third_party_conditons as $condition ) {
				if ( ! is_a( $condition, 'WC_BOGOF_Abstract_Condition' ) ) {
					continue;
				}
				self::$conditions[ $condition->get_id() ] = $condition;
			}
		}
	}

	/**
	 * Load a condition object from the class name.
	 *
	 * @param string $classname Class name.
	 */
	private static function load_condition( $classname ) {
		if ( ! class_exists( $classname ) ) {
			$file = 'class-' . strtolower( str_replace( '_', '-', $classname ) );
			include_once dirname( __FILE__ ) . "/conditions/{$file}.php"; // nosemgrep: audit.php.lang.security.file.inclusion-arg .
		}
		return new $classname();
	}

	/**
	 * Return condition by ID.
	 *
	 * @param string $id Condition ID.
	 * @return WC_BOGOF_Condition
	 */
	public static function get_condition( $id ) {
		return isset( self::$conditions[ $id ] ) && is_a( self::$conditions[ $id ], 'WC_BOGOF_Abstract_Condition' ) ? self::$conditions[ $id ] : false;
	}

	/**
	 * Return all conditions
	 *
	 * @return array
	 */
	public static function get_conditions() {
		return self::$conditions;
	}

	/**
	 * Evaluate and array of conditions.
	 *
	 * @param array $data Array of conditions.
	 * @param mixed $value Value to check.
	 * @return boolean
	 */
	public static function check_conditions( $data, $value ) {
		$check = false;
		if ( is_array( $data ) ) {
			foreach ( $data as $group ) {

				if ( ! is_array( $group ) ) {
					break;
				}

				$is_matching = true;

				foreach ( $group as $args ) {

					$condition = empty( $args['type'] ) ? false : self::get_condition( $args['type'] );

					if ( ! $condition ) {
						continue;
					}

					$is_matching = $is_matching && $condition->check_condition( $args, $value );
				}
				if ( $is_matching ) {
					$check = true;
					break;
				}
			}
		}
		return $check;
	}

	/**
	 * Return the WHERE clause that returns the products that meet the condition.
	 *
	 * @param array $data Array of conditions.
	 * @return string
	 */
	public static function get_where_clause( $data ) {
		$clause = array();

		if ( is_array( $data ) ) {
			foreach ( $data as $group ) {
				if ( ! is_array( $group ) ) {
					break;
				}

				$group_clause = array();

				foreach ( $group as $args ) {
					$condition = empty( $args['type'] ) ? false : self::get_condition( $args['type'] );
					if ( ! $condition ) {
						continue;
					}

					$condition_where = $condition->get_where_clause( $args );

					if ( ! $condition_where ) {
						continue;
					}

					$group_clause[] = '(' . $condition_where . ')';
				}

				$clause[] = '(' . implode( ' AND ', $group_clause ) . ')';
			}
		}

		$where_clause = '';

		if ( count( $clause ) ) {
			$where_clause = implode( ' OR ', $clause );
		}

		return $where_clause;
	}

	/**
	 * Return the conditions as string.
	 *
	 * @param array $data Condition field data.
	 * @return string
	 */
	public static function to_string( $data ) {
		$clause = array();

		if ( is_array( $data ) ) {
			foreach ( $data as $group ) {
				if ( ! is_array( $group ) ) {
					break;
				}

				$group_clause = array();

				foreach ( $group as $args ) {
					$condition = empty( $args['type'] ) ? false : self::get_condition( $args['type'] );
					if ( ! $condition ) {
						continue;
					}
					$group_clause[] = ' ' . $condition->to_string( $args ) . ' ';
				}

				$clause[] = implode( ' And', $group_clause );
			}
		}

		$to_string = '';

		if ( count( $clause ) ) {
			$to_string = implode( ' Or ', $clause );
		}

		return $to_string;
	}

	/**
	 * Returns an array with the admin type field.
	 *
	 * @param int   $group Group index.
	 * @param int   $index Loop index.
	 * @param int   $field Field attributes.
	 * @param array $data Condition data.
	 * @return array
	 */
	public static function get_metabox_type_field( $group, $index, $field, $data ) {
		$options = [
			'' => '[' . esc_html__( 'Select a condition', 'wc-buy-one-get-one-free' ) . ']',
		];
		foreach ( self::get_conditions() as $condition ) {
			if ( $condition->supports( $field['id'] ) ) {

				$title    = $condition->get_title();
				$optgroup = explode( ' - ', $title );

				if ( count( $optgroup ) > 1 ) {
					$optgroup = $optgroup[0];
					if ( ! isset( $options[ $optgroup ] ) ) {
						$options[ $optgroup ] = [];
					}
					$options[ $optgroup ][ $condition->get_id() ] = $title;
				} else {
					$options[ $condition->get_id() ] = $title;
				}
			}
		}

		return array(
			'type'    => 'select',
			'options' => $options,
			'name'    => $field['name'] . "[type][{$group}][{$index}]",
			'id'      => $field['id'] . "_type_{$group}_{$index}",
			'value'   => $data['type'],
		);
	}

	/**
	 * Get the modifiers from a WC_BOGOF_Condition object.
	 *
	 * @param WC_BOGOF_Condition $condition The condition object.
	 * @param string             $callback Condition callback.
	 * @return array
	 */
	private static function get_condition_data( $condition, $callback ) {
		if ( empty( self::$data[ $callback ][ $condition->get_id() ] ) ) {
			self::$data[ $callback ][ $condition->get_id() ] = $condition->{$callback}();
		}
		return self::$data[ $callback ][ $condition->get_id() ];
	}

	/**
	 * Returns an array with the admin modifier fields.
	 *
	 * @param int   $group Group index.
	 * @param int   $index Loop index.
	 * @param int   $field Field attributes.
	 * @param array $data Condition data.
	 * @return array
	 */
	public static function get_metabox_modifier_fields( $group, $index, $field, $data ) {
		$fields = array();
		foreach ( self::get_conditions() as $condition ) {

			$options = self::get_condition_data( $condition, 'get_modifiers' );

			if ( empty( $options ) || ! $condition->supports( $field['id'] ) ) {
				continue;
			}
			$fields[] = array_merge(
				self::get_field_attrs( $group, $index, $field['id'], $field['name'], 'modifier', $condition->get_id(), $data ),
				array(
					'type'    => 'select',
					'options' => $options,
				)
			);
		}
		return $fields;
	}

	/**
	 * Returns an array with the admin value fields.
	 *
	 * @param int   $group Group index.
	 * @param int   $index Loop index.
	 * @param int   $field Field attributes.
	 * @param array $data Condition data.
	 * @return array
	 */
	public static function get_metabox_value_fields( $group, $index, $field, $data ) {
		$fields = array();
		foreach ( self::get_conditions() as $condition ) {
			$value_metabox_field = self::get_condition_data( $condition, 'get_value_metabox_field' );
			if ( empty( $value_metabox_field ) || ! $condition->supports( $field['id'] ) ) {
				continue;
			}
			$fields[] = array_merge(
				self::get_field_attrs( $group, $index, $field['id'], $field['name'], 'value', $condition->get_id(), $data ),
				$value_metabox_field
			);
		}
		return $fields;
	}

	/**
	 * Return the base field attributes
	 *
	 * @param int    $group Group index.
	 * @param int    $index Loop index.
	 * @param string $id Field ID to generate the attributes.
	 * @param string $name Field name to generate the attributes.
	 * @param string $prop "modifier" or "value".
	 * @param string $condition_id Condition ID.
	 * @param array  $data Condition data.
	 * @return array
	 */
	private static function get_field_attrs( $group, $index, $id, $name, $prop, $condition_id, $data ) {
		return array(
			'id'      => "{$id}_{$condition_id}_{$prop}_{$group}_{$index}",
			'name'    => $name . "[{$condition_id}][{$prop}][{$group}][{$index}]",
			'value'   => $data['type'] === $condition_id ? $data[ $prop ] : false,
			'show-if' => array(
				array(
					'field'    => $id . "_type_{$group}_{$index}",
					'operator' => '=',
					'value'    => $condition_id,
				),
			),
		);
	}
}
