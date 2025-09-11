<?php
/**
 * Condition customer is logged in class.
 *
 * @since 5.1.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition_Customer_Role Class
 */
class WC_BOGOF_Condition_Customer_Role extends WC_BOGOF_Abstract_Condition {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id       = 'customer_role';
		$this->title    = sprintf(
			'%s - %s',
			__( 'Customer', 'wc-buy-one-get-one-free' ),
			__( 'User role', 'wc-buy-one-get-one-free' )
		);
		$this->supports = array( '_conditions' );
	}

	/**
	 * Returns a key => title array of modifiers.
	 *
	 * @return array
	 */
	public function get_modifiers() {
		return array(
			'in'     => __( 'In list', 'wc-buy-one-get-one-free' ),
			'not-in' => __( 'Not in list', 'wc-buy-one-get-one-free' ),
		);
	}

	/**
	 * Returns an array with the proprerties of the metabox field.
	 *
	 * @return array
	 */
	public function get_value_metabox_field() {
		return [
			'type'    => 'enhanced-select',
			'options' => wp_list_pluck( get_editable_roles(), 'name' ),
		];
	}

	/**
	 * Evaluate a condition field.
	 *
	 * @param array $data   Condition field data.
	 * @param mixed $value  Value to check.
	 * @return boolean
	 */
	public function check_condition( $data, $value = null ) {
		$data['value'] = is_array( $data['value'] ) ? $data['value'] : [];
		return wc_bogof_current_user_has_role( $data['value'] );
	}
}

