<?php
/**
 * Gifts query loop.
 *
 * @since 4.2
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Gifts_Loop Class
 */
class WC_BOGOF_Gifts_Loop {

	/**
	 * Array of WC_BOGOF_Cart_Rules.
	 *
	 * @var array
	 */
	protected $cart_rules;

	/**
	 * Page.
	 *
	 * @var int
	 */
	protected $page;

	/**
	 * Query results
	 *
	 * @var array
	 */
	protected $results;

	/**
	 * Gift loop HTML.
	 *
	 * @var string
	 */
	protected $the_loop;

	/**
	 * Where to filter the product.
	 *
	 * @var string
	 */
	protected $post_where;

	/**
	 * Hash.
	 *
	 * @var string
	 */
	protected $hash;

	/**
	 * Create a new gifts loop.
	 *
	 * @param array $cart_rules Array of WC_BOGOF_Cart_Rules.
	 * @param int   $page Loop page.
	 */
	public function __construct( $cart_rules, $page = 1 ) {

		$this->cart_rules = [];
		$this->hash       = false;

		foreach ( $cart_rules as $cart_rule ) {
			if ( ! is_callable( [ $cart_rule, 'get_free_products_in' ] ) ) {
				continue;
			}
			$this->cart_rules[] = $cart_rule;
		}

		$this->page       = absint( $page );
		$this->results    = (object) [
			'products'      => [],
			'total'         => 0,
			'max_num_pages' => 0,
		];
		$this->the_loop   = '';
		$this->post_where = false;

		if ( count( $this->cart_rules ) > 0 ) {
			$this->query();
		}
	}

	/**
	 * Total getter.
	 */
	public function get_total() {
		return $this->results->total;
	}

	/**
	 * Num_pages getter.
	 */
	public function get_num_pages() {
		return $this->results->max_num_pages;
	}

	/**
	 * Page getter.
	 */
	public function get_page() {
		return $this->page;
	}

	/**
	 * The loop getter.
	 */
	public function get_the_loop() {
		if ( empty( $this->the_loop ) ) {
			$this->loop();
		}
		return $this->the_loop;
	}

	/**
	 * Free quantity getter.
	 */
	public function get_available_free_quantity() {
		if ( $this->get_total() < 1 ) {
			return 0;
		}

		$qty = 0;
		foreach ( $this->cart_rules as $rule ) {
			$qty += $rule->get_shop_free_quantity();
		}
		return $qty;
	}

	/**
	 * Percentage discounts getter.
	 */
	public function get_product_percentage_discounts() {
		$discounts = [];
		foreach ( $this->cart_rules as $rule ) {
			if ( $rule->get_shop_free_quantity() > 0 ) {
				$discounts[] = $rule->get_rule_discount();
			}
		}
		return array_unique( $discounts );
	}

	/**
	 * Returns the hash based on bogo rules.
	 *
	 * @return string hash for cart content
	 */
	public function get_query_hash() {
		if ( false === $this->hash ) {

			if ( '' === $this->get_post_where() ) {
				$this->hash = '';
			}

			$this->hash = md5(
				wp_json_encode(
					[
						'where' => $this->get_post_where(),
						WC_Cache_Helper::get_transient_version( 'bogof_rules' ),
						WC_Cache_Helper::get_transient_version( 'product_query' ),
						wc_bool_to_string( current_user_can( 'manage_woocommerce' ) ),
						WC_BOGOF_Mods::get( 'show_single_variations' ),
						WC_Buy_One_Get_One_Free::VERSION,
					]
				)
			);
		}

		return $this->hash;
	}

	/**
	 * Returns the default allowed query vars.
	 *
	 * @return array
	 */
	protected function get_query_vars() {

		$query_vars = [
			'fields'         => 'ids',
			'post_type'      => 'product',
			'post_status'    => [ 'publish' ],
			'posts_per_page' => -1,
		];

		if ( current_user_can( 'manage_woocommerce' ) ) {
			// Display no-publish products to store managers.
			$query_vars['post_status'] = [ 'publish', 'private', 'draft' ];
		} else {
			// Hide out of stock items.
			$product_visibility_terms = wc_get_product_visibility_term_ids();
			$query_vars['tax_query']  = [
				[
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $product_visibility_terms['outofstock'],
					'operator' => 'NOT IN',
				],
			];
		}

		return $query_vars;
	}

	/**
	 * Returns the cart rules WHERE.
	 */
	protected function get_post_where() {
		if ( false !== $this->post_where ) {
			return $this->post_where;
		}

		$filters = array();

		foreach ( $this->cart_rules as $cart_rule ) {
			$post_id_in = $cart_rule->get_free_products_in();
			if ( $post_id_in ) {
				$filters[] = '(' . $post_id_in . ')';
			}
		}

		if ( ! empty( $filters ) ) {
			$where = ' AND (' . implode( ' OR ', $filters ) . ')';
		} else {
			$where = '';
		}

		$this->post_where = $where;

		return $this->post_where;
	}

	/**
	 * Returns true if the product is an available gift.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	protected function is_a_gift( $product_id ) {
		foreach ( $this->cart_rules as $cart_rule ) {
			if ( $cart_rule->is_shop_avilable_free_product( $product_id ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns true if the product type supports the modal gift.
	 *
	 * @param string $product_type The product type.
	 * @return bool
	 */
	protected function is_a_supported( $product_type ) {
		return current_user_can( 'manage_woocommerce' ) || wc_bogof_product_type_supports_modal_gifts( $product_type );
	}

	/**
	 * Returns the variation IDs of a variable product.
	 *
	 * @param int $post_id Product Id.
	 * @return array Array of vartation IDs
	 */
	protected function get_variation_ids( $post_id ) {
		$product_ids               = [];
		$query_vars                = $this->get_query_vars();
		$query_vars['post_type']   = 'product_variation';
		$query_vars['post_parent'] = $post_id;

		$variations = get_posts( $query_vars );

		if ( $variations && is_array( $variations ) ) {
			foreach ( $variations as $variation_id ) {

				if ( $this->is_a_gift( $variation_id ) ) {
					$product_ids[] = $variation_id;
				}
			}
		}

		return $product_ids;
	}

	/**
	 * Returns the IDs of the gifts.
	 *
	 * @return array Array of product IDs
	 */
	protected function get_products() {

		$products = [];

		if ( '' === $this->get_post_where() ) {
			return $products;
		}

		$debug     = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$cache_key = strtolower( __CLASS__ ) . $this->get_query_hash();
		$data      = get_transient( $cache_key );

		if ( $data && is_array( $data ) && ! $debug ) {
			return $data;
		}

		add_filter( 'posts_where', array( $this, 'posts_where' ), 10, 2 );

		$query = new WP_Query(
			$this->get_query_vars()
		);

		remove_filter( 'posts_where', array( $this, 'posts_where' ), 10, 2 );

		if ( ! empty( $query->posts ) && is_array( $query->posts ) ) {

			$show_single_variations = wc_string_to_bool( WC_BOGOF_Mods::get( 'show_single_variations' ) );

			foreach ( $query->posts as $post_id ) {

				$product_type = WC_Product_Factory::get_product_type( $post_id );

				if ( in_array( $product_type, wc_bogof_variable_types(), true ) ) {

					$variation_ids = $this->get_variation_ids( $post_id );

					if ( ! empty( $variation_ids ) ) {
						$products = array_merge( $products, ( $show_single_variations ? $variation_ids : [ $post_id ] ) );
					}
				} elseif ( $this->is_a_supported( $product_type ) && $this->is_a_gift( $post_id ) ) {

					$products[] = $post_id;
				}
			}
		}

		set_transient( $cache_key, $products, 30 * DAY_IN_SECONDS );

		return $products;
	}

	/**
	 * Add the choose your gift product filter.
	 *
	 * @param string   $where The WHERE clause of the query.
	 * @param WP_Query $q The WP_Query instance (passed by reference).
	 */
	public function posts_where( $where, $q ) {
		$where .= $this->get_post_where();
		return $where;
	}

	/**
	 * Gifts query.
	 */
	protected function query() {
		$product_ids = $this->get_products();
		$total       = count( $product_ids );

		// Paginate.
		$length = WC_BOGOF_Mods::get( 'loop_columns' ) * WC_BOGOF_Mods::get( 'loop_rows' );
		$offset = ( $this->page - 1 ) * $length;

		$this->results = (object) [
			'products'      => array_slice( $product_ids, $offset, $length ),
			'total'         => $total,
			'max_num_pages' => $total / $length,
		];
	}

	/**
	 * Set the product price.
	 *
	 * @param WC_Product $product Product instance.
	 */
	protected function set_product_price( $product ) {
		if ( $product instanceof WC_BOGOF_Gifts_Variable_Product ) {

			foreach ( $product->get_gift_children() as $variation ) {
				$this->set_product_price( $variation );
			}
		} else {

			foreach ( $this->cart_rules as $cart_rule ) {
				// Create a Item discount to calculate the price.
				if ( $cart_rule->is_shop_avilable_free_product( $product ) ) {
					$discount = new WC_BOGOF_Cart_Item_Discount(
						array(
							'data'     => $product,
							'quantity' => 1,
						),
						array(
							$cart_rule->get_id() => 1,
						)
					);
					$price    = $discount->get_sale_price();

					$product->set_price( $price );
					$product->set_sale_price( $price );
					// Get the price of the first rule.
					break;
				}
			}
		}
	}

	/**
	 * Returns the product instance.
	 *
	 * @param int $post_id Product ID.
	 */
	protected function get_product( $post_id ) {
		$product_type = WC_Product_Factory::get_product_type( $post_id );
		$product      = false;

		if ( in_array( $product_type, wc_bogof_variable_types(), true ) ) {

			$variation_ids = $this->get_variation_ids( $post_id );

			if ( ! empty( $variation_ids ) ) {

				foreach ( $variation_ids as $variation_id ) {
					$variation = wc_get_product( $variation_id );
					if ( $variation instanceof WC_Product_Variation ) {
						$variations[] = $variation;
					}
				}

				if ( ! empty( $variations ) ) {
					$product = new WC_BOGOF_Gifts_Variable_Product( $post_id, $variations, $this->get_query_hash() );
				}
			}
		} else {
			$product = wc_get_product( $post_id );
		}

		return $product;
	}

	/**
	 * Gift loop.
	 */
	protected function loop() {

		$results = $this->results;

		if ( $results->total > 0 ) {

			// Prime caches to reduce future queries.
			if ( is_callable( '_prime_post_caches' ) ) {
				_prime_post_caches( $results->products );
			}

			$loop_index = 0;

			ob_start();

			foreach ( $results->products as $post_id ) {

				$loop_index++;
				$product = $this->get_product( $post_id );

				if ( ! $product ) {
					continue;
				}

				$this->set_product_price( $product );

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
		}

		$this->the_loop = ob_get_clean();
	}

}
