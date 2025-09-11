<?php
/**
 * Abstract Condition class.
 *
 * @since 3.0.0
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Condition Class
 */
abstract class WC_BOGOF_Abstract_Condition {

	/**
	 * Unique ID for the condition - must be set.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Condition title - must be set.
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * WC_BOGOF_Rule fields this condition supports. Possible values: _applies_to, _gift_products.
	 *
	 * @since 3.3.0
	 * @var array
	 */
	protected $supports = array( '_applies_to', '_gift_products' );

	/**
	 * Returns the ID.
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Returns the title.
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Returns the cache key.
	 *
	 * @param string $key   Cache key.
	 * @param string $scope Cache scope.
	 */
	protected function get_cache_key( $key, $scope = '' ) {
		$prefix = $scope ? WC_Cache_Helper::get_cache_prefix( $scope ) : '';
		return $prefix . '_wc_bogof_condition_' . WC_Buy_One_Get_One_Free::VERSION . $this->get_id() . $scope . $key;
	}

	/**
	 * Returns a value from cache.
	 *
	 * @param string $cache_key Cache key.
	 */
	protected function cache_get( $cache_key ) {
		return wp_cache_get( $cache_key, 'wc-bogof-condition' );
	}

	/**
	 * Set a value to cache.
	 *
	 * @param string $cache_key Cache key.
	 * @param string $value Value to set.
	 */
	protected function cache_set( $cache_key, $value ) {
		wp_cache_set( $cache_key, $value, 'wc-bogof-condition' );
	}

	/**
	 * Evaluate a condition field.
	 *
	 * @param array $data        Condition field data.
	 * @param mixed $value       Value to check.
	 * @return boolean
	 */
	abstract public function check_condition( $data, $value = null );

	/**
	 * Returns a key => title array of modifiers.
	 *
	 * @return array
	 */
	abstract public function get_modifiers();

	/**
	 * Returns an array with the proprerties of the metabox field.
	 *
	 * @return array
	 */
	abstract public function get_value_metabox_field();

	/**
	 * Returns the condition as string.
	 *
	 * @param array $data Condition field data.
	 * @return string
	 */
	public function to_string( $data ) {
		$modifier = isset( $data['modifier'] ) ? $data['modifier'] : '';
		$value    = isset( $data['value'] ) ? $data['value'] : '';

		if ( is_array( $value ) ) {
			$value = ' ( ' . implode( ' | ', $data['value'] ) . ' )';
		}

		return $this->title . ' ' . $modifier . ' ' . $value;
	}

	/**
	 * Returns the WHERE clause that returns the products that meet the condition.
	 *
	 * @param array $data Condition field data.
	 * @return string
	 */
	public function get_where_clause( $data ) {
		if ( $this->supports( '_gift_products' ) ) {
			return '1=1';
		} else {
			return false;
		}
	}

	/**
	 * Checks if the provided modifier is inside the modifiers haystack.
	 *
	 * @param array  $data Array that contains the modifier key.
	 * @param string $haystack Modifier to check.
	 * @return bool
	 */
	protected function modifier_is( $data, $haystack = '' ) {
		return empty( $data['modifier'] ) || $data['modifier'] !== $haystack ? false : true;
	}

	/**
	 * Is the condition data empty?
	 *
	 * @param array $data Array that contains the condition data.
	 * @return bool
	 */
	public function is_empty( $data ) {
		return empty( $data['type'] ) || empty( $data['modifier'] ) || empty( $data['value'] );
	}

	/**
	 * Sanitize a condition data array.
	 *
	 * @since 3.3.2
	 * @param array $data Array that contains the condition data.
	 * @return bool
	 */
	public function sanitize( $data ) {
		return wc_clean(
			wp_parse_args(
				$data,
				[
					'type'     => '',
					'modifier' => '',
					'value'    => '',
				]
			)
		);
	}

	/**
	 * Check if the condition supports a given field.
	 *
	 * @param string $field The name of the field to test support for.
	 * @return bool
	 */
	public function supports( $field ) {
		return in_array( $field, $this->supports, true );
	}
}
