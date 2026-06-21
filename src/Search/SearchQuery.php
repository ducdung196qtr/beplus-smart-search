<?php
/**
 * Search query value object.
 *
 * @package BePlusSmartSearch
 * @subpackage Search
 */

namespace BePlusSmartSearch\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Immutable search query DTO.
 */
final class SearchQuery {

	/**
	 * @var string
	 */
	private string $keyword;

	/**
	 * @var array<int, string>
	 */
	private array $product_cats;

	/**
	 * @var array<int, string>
	 */
	private array $product_tags;

	/**
	 * @var array<string, array<int, string>>
	 */
	private array $attributes;

	/**
	 * @var string
	 */
	private string $stock_status;

	/**
	 * @var bool
	 */
	private bool $on_sale;

	/**
	 * @var bool
	 */
	private bool $featured;

	/**
	 * @var float
	 */
	private float $min_rating;

	/**
	 * @var array<string, array<int, string>>
	 */
	private array $taxonomies;

	/**
	 * @var float
	 */
	private float $min_price;

	/**
	 * @var float
	 */
	private float $max_price;

	/**
	 * @var int
	 */
	private int $page;

	/**
	 * @var int
	 */
	private int $per_page;

	/**
	 * @var string
	 */
	private string $orderby;

	/**
	 * @var string
	 */
	private string $order;

	/**
	 * @param array<string, mixed> $args Query arguments.
	 */
	public function __construct( array $args = array() ) {
		$this->keyword      = isset( $args['keyword'] ) ? (string) $args['keyword'] : '';
		$this->product_cats = self::normalize_terms( $args['product_cats'] ?? ( $args['product_cat'] ?? array() ) );
		$this->product_tags = self::normalize_terms( $args['product_tags'] ?? ( $args['product_tag'] ?? array() ) );
		$this->attributes   = self::normalize_attributes( $args['attributes'] ?? array() );
		$this->stock_status = isset( $args['stock_status'] ) ? (string) $args['stock_status'] : '';
		$this->on_sale      = ! empty( $args['on_sale'] );
		$this->featured     = ! empty( $args['featured'] );
		$this->min_rating    = isset( $args['min_rating'] ) ? max( 0, min( 5, (float) $args['min_rating'] ) ) : 0.0;
		$this->taxonomies   = self::normalize_taxonomies( $args['taxonomies'] ?? array() );
		$this->min_price    = isset( $args['min_price'] ) ? max( 0, (float) $args['min_price'] ) : 0.0;
		$this->max_price    = isset( $args['max_price'] ) ? (float) $args['max_price'] : 0.0;
		if ( $this->max_price > 0 ) {
			$this->max_price = max( 1.0, $this->max_price );
		}
		$this->page         = max( 1, isset( $args['page'] ) ? (int) $args['page'] : 1 );
		$default_per_page   = function_exists( 'beplus_smart_search_get_per_page' )
			? beplus_smart_search_get_per_page()
			: 10;
		$requested_per_page = isset( $args['per_page'] ) ? (int) $args['per_page'] : 0;
		$this->per_page     = min(
			50,
			max( 1, $requested_per_page > 0 ? $requested_per_page : $default_per_page )
		);

		$parsed_orderby     = beplus_smart_search_parse_catalog_orderby(
			isset( $args['orderby'] ) ? (string) $args['orderby'] : '',
			isset( $args['order'] ) ? (string) $args['order'] : ''
		);
		$this->orderby      = $parsed_orderby['orderby'];
		$this->order        = $parsed_orderby['order'];
	}

	/**
	 * Build from REST request.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return self
	 */
	public static function from_rest_request( \WP_REST_Request $request ): self {
		$attributes = array();
		$raw_attrs  = $request->get_param( 'attribute' );

		if ( is_array( $raw_attrs ) ) {
			foreach ( $raw_attrs as $slug => $term ) {
				$slug  = sanitize_key( (string) $slug );
				$terms = self::normalize_terms( $term );
				if ( $slug && ! empty( $terms ) ) {
					$attributes[ $slug ] = $terms;
				}
			}
		}

		$per_page = (int) $request->get_param( 'per_page' );
		if ( $per_page <= 0 ) {
			$per_page = beplus_smart_search_get_per_page();
		}

		return new self(
			array(
				'keyword'       => sanitize_text_field( (string) $request->get_param( 's' ) ),
				'product_cats'  => self::normalize_terms( $request->get_param( 'product_cat' ) ),
				'product_tags'  => self::normalize_terms( $request->get_param( 'product_tag' ) ),
				'attributes'    => $attributes,
				'stock_status'  => sanitize_key( (string) $request->get_param( 'stock_status' ) ),
				'on_sale'       => rest_sanitize_boolean( $request->get_param( 'on_sale' ) ),
				'featured'      => rest_sanitize_boolean( $request->get_param( 'featured' ) ),
				'min_rating'    => (float) $request->get_param( 'min_rating' ),
				'taxonomies'    => self::taxonomies_from_request( $request ),
				'min_price'     => (float) $request->get_param( 'min_price' ),
				'max_price'     => (float) $request->get_param( 'max_price' ),
				'page'          => (int) $request->get_param( 'page' ),
				'per_page'      => $per_page,
				'orderby'       => sanitize_text_field( (string) $request->get_param( 'orderby' ) ),
				'order'         => sanitize_key( (string) $request->get_param( 'order' ) ),
			)
		);
	}

	/**
	 * Read brand/custom taxonomy filters from REST request.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return array<string, array<int, string>>
	 */
	private static function taxonomies_from_request( \WP_REST_Request $request ): array {
		$taxonomies = array();

		if ( ! function_exists( 'beplus_smart_search_get_configured_filter_taxonomies' ) ) {
			return $taxonomies;
		}

		foreach ( beplus_smart_search_get_configured_filter_taxonomies() as $taxonomy ) {
			$value = $request->get_param( $taxonomy );
			$terms = self::normalize_terms( $value );
			if ( ! empty( $terms ) ) {
				$taxonomies[ $taxonomy ] = $terms;
			}
		}

		return $taxonomies;
	}

	/**
	 * @param mixed $value Term value(s).
	 * @return array<int, string>
	 */
	private static function normalize_terms( $value ): array {
		if ( is_array( $value ) ) {
			$terms = array_map( 'sanitize_text_field', $value );
			return array_values( array_filter( $terms ) );
		}

		if ( is_string( $value ) && '' !== $value ) {
			if ( false !== strpos( $value, ',' ) ) {
				$parts = array_map( 'trim', explode( ',', $value ) );
				return array_values( array_filter( array_map( 'sanitize_text_field', $parts ) ) );
			}
			return array( sanitize_text_field( $value ) );
		}

		return array();
	}

	/**
	 * @param mixed $value Attribute map.
	 * @return array<string, array<int, string>>
	 */
	private static function normalize_attributes( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$normalized = array();
		foreach ( $value as $slug => $terms ) {
			$slug = sanitize_key( (string) $slug );
			$list = self::normalize_terms( $terms );
			if ( $slug && ! empty( $list ) ) {
				$normalized[ $slug ] = $list;
			}
		}

		return $normalized;
	}

	/**
	 * @param mixed $value Taxonomy map.
	 * @return array<string, array<int, string>>
	 */
	private static function normalize_taxonomies( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$normalized = array();
		foreach ( $value as $taxonomy => $terms ) {
			$taxonomy = sanitize_key( (string) $taxonomy );
			$list     = self::normalize_terms( $terms );
			if ( $taxonomy && ! empty( $list ) ) {
				$normalized[ $taxonomy ] = $list;
			}
		}

		return $normalized;
	}

	public function get_keyword(): string {
		return $this->keyword;
	}

	/**
	 * @return array<int, string>
	 */
	public function get_product_cats(): array {
		return $this->product_cats;
	}

	/**
	 * @return array<int, string>
	 */
	public function get_product_tags(): array {
		return $this->product_tags;
	}

	/**
	 * @return array<string, array<int, string>>
	 */
	public function get_attributes(): array {
		return $this->attributes;
	}

	public function get_stock_status(): string {
		return $this->stock_status;
	}

	public function is_on_sale(): bool {
		return $this->on_sale;
	}

	public function is_featured(): bool {
		return $this->featured;
	}

	public function get_min_rating(): float {
		return $this->min_rating;
	}

	/**
	 * @return array<string, array<int, string>>
	 */
	public function get_taxonomies(): array {
		return $this->taxonomies;
	}

	public function get_min_price(): float {
		return $this->min_price;
	}

	public function get_max_price(): float {
		return $this->max_price;
	}

	public function get_page(): int {
		return $this->page;
	}

	public function get_per_page(): int {
		return $this->per_page;
	}

	public function get_orderby(): string {
		return $this->orderby;
	}

	public function get_order(): string {
		return in_array( $this->order, array( 'asc', 'desc' ), true ) ? $this->order : 'asc';
	}
}
