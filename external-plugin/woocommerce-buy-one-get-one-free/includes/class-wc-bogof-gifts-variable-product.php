<?php
/**
 * Variable Gift Product Class.
 *
 * This class extends the WC Variable product class to handle the variable products in the gifts Pop-Up.
 *
 * @since 5.2
 * @package WC_BOGOF
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_BOGOF_Gifts_Variable_Product Class
 */
class WC_BOGOF_Gifts_Variable_Product extends WC_Product_Variable {

	/**
	 * Variation gifts. Array of variations that are gifts.
	 *
	 * @var array
	 */
	protected $gift_children = [];

	/**
	 * Loop hash to handle cache keys.
	 *
	 * @var string
	 */
	protected $loop_hash = '';

	/**
	 * Variation prices.
	 *
	 * @var array
	 */
	protected $variation_prices = false;

	/**
	 * Constructor
	 *
	 * @param int|WC_Product|object $product Product to init.
	 * @param array                 $children Children products.
	 * @param string                $loop_hash Loop hash.
	 */
	public function __construct( $product = 0, $children = [], $loop_hash = '' ) {
		parent::__construct( $product );
		$this->set_children( $children );
		$this->loop_hash = $loop_hash;

	}
	/**
	 * Return a products child ids.
	 *
	 * @param bool|string $visible_only Visible only.
	 * @return array Children ids
	 */
	public function get_children( $visible_only = '' ) {
		if ( null === $this->children ) {
			$this->children = wc_list_pluck( $this->gift_children, 'get_id' );
		}

		return $this->children;
	}

	/**
	 * Sets an array of children for the product.
	 *
	 * @param array $children Children products.
	 */
	public function set_children( $children ) {
		$this->gift_children = array_filter(
			$children,
			function( $variation ) {
				return $variation instanceof WC_Product_Variation;
			}
		);
	}

	/**
	 * Return all product variations.
	 *
	 * @return array
	 */
	public function get_gift_children() {
		return $this->gift_children;
	}

	/**
	 * Get an array of available variations for the current product.
	 *
	 * @param string $return Optional. The format to return the results in. Can be 'array' to return an array of variation data or 'objects' for the product objects. Default 'array'.
	 *
	 * @return array[]|WC_Product_Variation[]
	 */
	public function get_available_variations( $return = 'array' ) {

		foreach ( $this->gift_children as $variation ) {
			$available_variations[] = $this->get_available_variation( $variation );
		}

		return array_filter( $available_variations );
	}

	/**
	 * Returns an array of data for a variation. Used in the add to cart form.
	 *
	 * @param  WC_Product $variation Variation product object or ID.
	 * @return array|bool
	 */
	public function get_available_variation( $variation ) {
		return [
			'attributes'            => $variation->get_variation_attributes(),
			'availability_html'     => wc_get_stock_html( $variation ),
			'backorders_allowed'    => $variation->backorders_allowed(),
			'dimensions'            => $variation->get_dimensions( false ),
			'dimensions_html'       => wc_format_dimensions( $variation->get_dimensions( false ) ),
			'image'                 => $this->get_variation_image( $variation ),
			'image_id'              => $variation->get_image_id(),
			'is_downloadable'       => $variation->is_downloadable(),
			'is_in_stock'           => $variation->is_in_stock(),
			'is_purchasable'        => $variation->is_purchasable(),
			'is_sold_individually'  => $variation->is_sold_individually() ? 'yes' : 'no',
			'is_virtual'            => $variation->is_virtual(),
			'max_qty'               => 0 < $variation->get_max_purchase_quantity() ? $variation->get_max_purchase_quantity() : '',
			'min_qty'               => $variation->get_min_purchase_quantity(),
			'price_html'            => $this->get_variation_price_html( $variation ),
			'sku'                   => $variation->get_sku(),
			'variation_description' => wc_format_content( $variation->get_description() ),
			'variation_id'          => $variation->get_id(),
			'variation_is_active'   => $variation->variation_is_active(),
			'variation_is_visible'  => $variation->variation_is_visible(),
			'weight'                => $variation->get_weight(),
			'weight_html'           => wc_format_weight( $variation->get_weight() ),
			'status_notice'         => wc_bogof_gift_template_product_status_notice( $variation ),
		];
	}

	/**
	 * Returns the variation price HTML.
	 *
	 * @param  WC_Product $variation Variation product object or ID.
	 * @return string
	 */
	protected function get_variation_price_html( $variation ) {
		ob_start();
		wc_bogof_gift_template_loop_price( $variation );
		return ob_get_clean();
	}

	/**
	 * Returns the variation price HTML.
	 *
	 * @param  WC_Product $variation Variation product object or ID.
	 * @return array
	 */
	protected function get_variation_image( $variation ) {
		$variation_img = [];

		if ( ! $variation->get_image_id() ) {
			return $variation_img;
		}

		$size  = apply_filters( 'wc_bogof_gift_template_thumbnail_size', 'woocommerce_thumbnail' );
		$image = wp_get_attachment_image_src( $variation->get_image_id(), $size );

		if ( $image ) {
			list( $src, $width, $height ) = $image;

			$variation_img = [
				'src'    => $src,
				'width'  => $width,
				'height' => $height,
			];
		}

		return $variation_img;
	}

	/**
	 * Should show the "from" in the price?
	 *
	 * @return bool
	 */
	public function show_from_text() {
		return $this->get_variation_sale_price( 'min' ) !== $this->get_variation_sale_price( 'max' ) || $this->get_variation_regular_price( 'min' ) !== $this->get_variation_regular_price( 'max' );
	}

	/**
	 * Return an array of attributes used for variations, as well as their possible values.
	 *
	 * @return array Attributes and their available values
	 */
	public function get_variation_attributes() {
		// Hack the cache key.
		$product_id = $this->get_id();
		$this->id   = "{$this->loop_hash}_$product_id";

		// Get attributes.
		$attributes = parent::get_variation_attributes();

		$this->id = $product_id; // Go back product ID.

		return $attributes;
	}

	/**
	 * Get an array of all sale and regular prices from all variations. This is used for example when displaying the price range at variable product level or seeing if the variable product is on sale.
	 *
	 * @param  bool $for_display If true, prices will be adapted for display based on the `woocommerce_tax_display_shop` setting (including or excluding taxes).
	 * @return array Array of RAW prices, regular prices, and sale prices with keys set to variation ID.
	 */
	public function get_variation_prices( $for_display = false ) {

		if ( false !== $this->variation_prices ) {
			return $this->variation_prices;
		}

		$this->variation_prices = [];

		foreach ( $this->gift_children as $variation ) {
			$this->variation_prices['price'][ $variation->get_id() ]         = $variation->get_price( 'edit' );
			$this->variation_prices['sale_price'][ $variation->get_id() ]    = $variation->get_sale_price( 'edit' );
			$this->variation_prices['regular_price'][ $variation->get_id() ] = $variation->get_regular_price();
		}

		$this->variation_prices = array_map( [ $this, 'sort_variation_prices' ], $this->variation_prices );

		return $this->variation_prices;
	}

	/**
	 * Returns the product's active price.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string price
	 */
	public function get_price( $context = 'view' ) {
		return $this->get_variation_price( 'min' );
	}

	/**
	 * Returns the product's regular price.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string price
	 */
	public function get_regular_price( $context = 'view' ) {
		return $this->get_variation_regular_price( 'min' );
	}
}
