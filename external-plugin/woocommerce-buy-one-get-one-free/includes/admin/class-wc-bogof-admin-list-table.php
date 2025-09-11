<?php
/**
 * List tables: BOGOF rules.
 *
 * @package  WC_BOGOF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_BOGOF_Admin_List_Table', false ) ) {
	return;
}

if ( ! class_exists( 'WC_Admin_List_Table', false ) ) {
	include_once WC_ABSPATH . 'includes/admin/list-tables/abstract-class-wc-admin-list-table.php';
}

/**
 * WC_Admin_List_Table_Coupons Class.
 */
class WC_BOGOF_Admin_List_Table extends WC_Admin_List_Table {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $list_table_type = 'shop_bogof_rule';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'admin_notices', array( $this, 'bulk_admin_notices' ) );
		add_filter( 'disable_months_dropdown', '__return_true' );
	}

	/**
	 * Handle any custom filters.
	 *
	 * @param array $query_vars Query vars.
	 * @return array
	 */
	protected function query_filters( $query_vars ) {
		// Default sort.
		if ( empty( $query_vars['orderby'] ) ) {
			add_filter( 'posts_clauses', array( $this, 'default_order_post_clauses' ), 5 );
		}
		return $query_vars;
	}

	/**
	 * Join wp_postmeta and set the default order.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function default_order_post_clauses( $args ) {
		global $wpdb;

		$args['join']    = empty( $args['join'] ) ? '' : $args['join'];
		$args['join']   .= " LEFT JOIN {$wpdb->postmeta} wc_bogof_default_order ON {$wpdb->posts}.ID = wc_bogof_default_order.post_id and wc_bogof_default_order.meta_key = '_default_order' ";
		$args['orderby'] = "  wc_bogof_default_order.meta_value+0 DESC,  {$wpdb->posts}.ID DESC ";

		remove_filter( 'posts_clauses', array( $this, 'default_order_post_clauses' ) ); // Do only once.

		return $args;
	}

	/**
	 * Render blank state.
	 */
	protected function render_blank_state() {
		echo '<div class="woocommerce-BlankState">';
		echo '<h2 class="woocommerce-BlankState-message">' . esc_html__( 'The "Buy One Get One Free" promotions are a great way to reward your customers and boost sales. They will appear here once created.', 'wc-buy-one-get-one-free' ) . '</h2>';
		echo '<a class="woocommerce-BlankState-cta button-primary button" href="' . esc_url( admin_url( 'post-new.php?post_type=shop_bogof_rule' ) ) . '">' . esc_html__( 'Create your first promotion', 'wc-buy-one-get-one-free' ) . '</a>';
		echo '</div>';
	}

	/**
	 * Define which columns to show on this screen.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function define_columns( $columns ) {
		$show_columns                  = array();
		$show_columns['cb']            = $columns['cb'];
		$show_columns['name']          = __( 'Title', 'wc-buy-one-get-one-free' );
		$show_columns['type']          = __( 'Promotion type', 'wc-buy-one-get-one-free' );
		$show_columns['quantity_rule'] = __( 'Offer details', 'wc-buy-one-get-one-free' );

		// Legacy (hidden).
		$show_columns['enabled']       = __( 'Active', 'wc-buy-one-get-one-free' );
		$show_columns['exclude_rules'] = __( 'Priority', 'wc-buy-one-get-one-free' );

		return $show_columns;
	}

	/**
	 * Define primary column.
	 *
	 * @return string
	 */
	protected function get_primary_column() {
		return 'name';
	}

	/**
	 * Define hidden columns.
	 *
	 * @return array
	 */
	protected function define_hidden_columns() {
		return array( 'enabled', 'cart_limit', 'exclude_rules' );
	}

	/**
	 * Define which columns are sortable.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function define_sortable_columns( $columns ) {
		return array( 'name' => 'name' );
	}

	/**
	 * Get row actions to show in the list table.
	 *
	 * @param array   $actions Array of actions.
	 * @param WP_Post $post Current post object.
	 * @return array
	 */
	protected function get_row_actions( $actions, $post ) {
		// Remove "Quick Edit" action.
		unset( $actions['inline'], $actions['inline hide-if-no-js'] );

		// Append "Duplicate" action.
		$duplicate_action_url = add_query_arg(
			array(
				'action'   => 'wc-bogo-duplicate',
				'post'     => array( $post->ID ),
				'_wpnonce' => wp_create_nonce( 'bulk-posts' ),
			),
			remove_query_arg(
				array( 'action', 'post', 'ids', '_wpnonce', 'wc-duplicate-completed' )
			)
		);

		$actions['wc-bogo-duplicate'] = '<a href="' . esc_url( $duplicate_action_url ) . '" aria-label="' . esc_attr__( 'Duplicate this item', 'wc-buy-one-get-one-free' ) . '">' . __( 'Duplicate', 'wc-buy-one-get-one-free' ) . '</a>';

		// Return actions in custom order.
		$order = array( 'edit', 'wc-bogo-duplicate', 'trash' );
		return array_merge( array_flip( $order ), $actions );
	}

	/**
	 * Handle bulk actions.
	 *
	 * @param  string $redirect_to URL to redirect to.
	 * @param  string $action      Action name.
	 * @param  array  $ids         List of ids.
	 * @return string
	 */
	public function handle_bulk_actions( $redirect_to, $action, $ids ) {
		if ( 'wc-bogo-duplicate' === $action ) {

			foreach ( $ids as $id ) {
				wc_bogof_duplicate_rule( $id );
			}

			$redirect_to = add_query_arg(
				array(
					'wc-duplicate-completed' => '1',
				),
				remove_query_arg(
					array( 'action', 'post', 'ids', '_wpnonce', 'wc-duplicate-completed' )
				)
			);
		}
		return esc_url_raw( $redirect_to );
	}

	/**
	 * Show confirmation messages.
	 */
	public function bulk_admin_notices() {
		global $post_type, $pagenow;

		// Bail out if not on shop order list page.
		if ( 'edit.php' !== $pagenow || $post_type !== $this->list_table_type || ! isset( $_REQUEST['wc-duplicate-completed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$message = __( 'Promotion duplicated.', 'wc-buy-one-get-one-free' );
		echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Pre-fetch any data for the row each column has access to it. the_coupon global is there for bw compat.
	 *
	 * @param int $post_id Post ID being shown.
	 */
	protected function prepare_row_data( $post_id ) {
		global $the_bogof_rule;

		if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
			$this->object   = new WC_BOGOF_Rule( $post_id );
			$the_bogof_rule = $this->object; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		}
	}

	/**
	 * Render columm: title.
	 */
	protected function render_name_column() {
		$edit_link  = get_edit_post_link( $this->object->get_id() );
		$title      = _draft_or_post_title( $this->object->get_id() );
		$post_state = '';
		if ( ! $this->object->get_enabled() ) {
			$post_state = ' &mdash; <span class="post-state"><span class="dashicons dashicons-hidden"></span> ' . _x( 'Disabled', 'post status', 'wc-buy-one-get-one-free' ) . '</span>';
		}

		echo '<strong><a class="row-title" href="' . esc_url( $edit_link ) . '">' . esc_html( $title ) . '</a>' . wp_kses_post( $post_state );
		echo '</strong>';
	}

	/**
	 * Render columm: type.
	 */
	protected function render_type_column() {
		$options = wc_bogof_rule_type_options();
		$desc    = isset( $options[ $this->object->get_type() ] ) ? $options[ $this->object->get_type() ] : '';
		echo wp_kses_post( $desc );
	}

	/**
	 * Render columm: quantity_rule.
	 */
	protected function render_quantity_rule_column() {
		$cont           = 0;
		$quantity_rules = [];
		foreach ( $this->object->get_quantity_rules() as $quantity_rule ) {
			// Translators: 1: quantity to buy to get a promotion, 2 number of items that the customer gets, 3 percentage discount.
			$desc = __( 'Buy %1$s gets %2$s (%3$s off)', 'wc-buy-one-get-one-free' );
			$desc = sprintf( esc_html( $desc ), '<strong>' . esc_html( $quantity_rule['cart_quantity'] ) . '</strong>', '<strong>' . esc_html( $quantity_rule['free_quantity'] ) . '</strong>', esc_html( $quantity_rule['discount'] . '%' ) );
			$cont++;
			if ( $cont < 2 ) {
				echo wp_kses_post( $desc );
			} else {
				$quantity_rules[] = $desc;
			}
		}

		if ( $quantity_rules ) {
			printf(
				'&nbsp;<a class="tips" data-tip="%s">%s<span>',
				esc_attr( implode( '<br>', $quantity_rules ) ),
				sprintf(
					// Translators: number.
					esc_html__( '+%s more', 'wc-buy-one-get-one-free' ),
					count( $quantity_rules )
				)
			);
		}
	}

	/**
	 * Generate list of objects for the condition.
	 *
	 * @param string $type Objects type.
	 * @param array  $ids Array of object IDs.
	 * @param string $glue Glue to implode.
	 * @param string $last_prefix Prefix of the last element.
	 */
	protected function object_list( $type, $ids, $glue = '', $last_prefix = '' ) {
		$names  = array();
		$hellip = array();
		$count  = 0;

		foreach ( $ids as $id ) {
			$name   = false;
			$object = 'product' === $type ? wc_get_product( $id ) : get_term( $id );

			if ( $object ) {
				$name = 'product' === $type ? $object->get_name() : $object->name;

			} elseif ( 'product' !== $type && 'all' === $id ) {
				$name = __( 'All Products', 'wc-buy-one-get-one-free' );
			}

			if ( $name ) {
				$count++;
				if ( $count > 3 ) {
					$hellip[] = $name;
				} else {
					$names[] = $name;
				}
			}
		}

		// Display only 3 elements.
		if ( count( $hellip ) ) {
			$names[] = '<span class="tips" data-tip="' . implode( ', ', $hellip ) . '">&hellip;</span>';
		}

		if ( $last_prefix && count( $names ) > 1 ) {
			$names[ count( $names ) - 1 ] = $last_prefix . $names[ count( $names ) - 1 ];
		}

		return '<strong>' . implode( $glue, $names ) . '</strong>';
	}

	/**
	 * Render columm: enabled.
	 */
	protected function render_enabled_column() {
		echo '<a class="wc-bogof-rule-toggle-enabled" href="#" data-rule_id="' . esc_attr( $this->object->get_id() ) . '">';
		if ( $this->object->get_enabled() ) {
			/* Translators: %s Payment gateway name. */
			echo '<span class="woocommerce-input-toggle woocommerce-input-toggle--enabled" aria-label="' . esc_attr( sprintf( __( 'The "%s" rule is currently enabled', 'wc-buy-one-get-one-free' ), $this->object->get_title() ) ) . '">' . esc_attr__( 'Yes', 'wc-buy-one-get-one-free' ) . '</span>';
		} else {
			/* Translators: %s Payment gateway name. */
			echo '<span class="woocommerce-input-toggle woocommerce-input-toggle--disabled" aria-label="' . esc_attr( sprintf( __( 'The "%s" rule is currently disabled', 'wc-buy-one-get-one-free' ), $this->object->get_title() ) ) . '">' . esc_attr__( 'No', 'wc-buy-one-get-one-free' ) . '</span>';
		}
		echo '</a>';
	}

	/**
	 * Render columm: Exclude other rules.
	 */
	protected function render_exclude_rules_column() {
		if ( $this->object->get_exclude_other_rules() ) {
			echo '<span class="status-enabled">Yes</span>';
		} else {
			echo '&mdash;';
		}
	}

}
