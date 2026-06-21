<?php

/**
 * Product query builder shared by search and facets.
 *
 * @package BePlusSmartSearch
 * @subpackage Search
 */

namespace BePlusSmartSearch\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds wc_get_products arguments from SearchQuery.
 */
final class ProductQueryBuilder {

	/**
	 * Build wc_get_products args.
	 *
	 * @param SearchQuery          $query     Search query.
	 * @param array<string, mixed> $overrides Optional overrides (limit, paginate, return).
	 *
	 * @return array<string, mixed>
	 */
	public static function build_args( SearchQuery $query, array $overrides = array() ): array {
		$args = array(
			'status'   => 'publish',
			'limit'    => $query->get_per_page(),
			'page'     => $query->get_page(),
			'paginate' => true,
			'return'   => 'objects',
			'orderby'  => self::map_orderby( $query->get_orderby() ),
			'order'    => strtoupper( $query->get_order() ),
		);

		if ( $query->get_keyword() ) {
			$args['s'] = $query->get_keyword();
		}

		$tax_query = array();

		$cats = $query->get_product_cats();
		if ( ! empty( $cats ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $cats,
				'operator' => 'IN',
			);
		}

		$tags = $query->get_product_tags();
		if ( ! empty( $tags ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_tag',
				'field'    => 'slug',
				'terms'    => $tags,
				'operator' => 'IN',
			);
		}

		foreach ( $query->get_attributes() as $attr_slug => $term_slugs ) {
			$taxonomy = wc_attribute_taxonomy_name( $attr_slug );
			if ( ! taxonomy_exists( $taxonomy ) || empty( $term_slugs ) ) {
				continue;
			}
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $term_slugs,
				'operator' => 'IN',
			);
		}

		foreach ( $query->get_taxonomies() as $taxonomy => $term_slugs ) {
			if ( ! taxonomy_exists( $taxonomy ) || empty( $term_slugs ) ) {
				continue;
			}
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $term_slugs,
				'operator' => 'IN',
			);
		}

		self::append_min_rating_tax_query( $tax_query, $query->get_min_rating() );

		if ( ! empty( $tax_query ) ) {
			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
			}
			$args['tax_query'] = $tax_query;
		}

		if ( $query->get_stock_status() ) {
			$args['stock_status'] = $query->get_stock_status();
		}

		if ( $query->is_on_sale() ) {
			$args['on_sale'] = true;
		}

		if ( $query->is_featured() ) {
			$args['featured'] = true;
		}

		if ( $query->get_min_price() > 0 ) {
			$args['min_price'] = (string) $query->get_min_price();
		}

		if ( $query->get_max_price() > 0 ) {
			$args['max_price'] = (string) $query->get_max_price();
		}

		if ( $query->get_min_price() > 0 || $query->get_max_price() > 0 ) {
			$args[ PriceQueryFilter::QUERY_FLAG ] = true;
		}

		return array_merge( $args, $overrides );
	}

	/**
	 * Append WooCommerce product_visibility rated-* terms for minimum rating.
	 *
	 * wc_get_products ignores meta_query; visibility terms match WC layered nav.
	 *
	 * @param array<int|string, mixed> $tax_query  Tax query clauses (by reference).
	 * @param float                    $min_rating Minimum average rating (1-5).
	 *
	 * @return void
	 */
	private static function append_min_rating_tax_query( array &$tax_query, float $min_rating ): void {
		if ( $min_rating <= 0 || ! function_exists( 'wc_get_product_visibility_term_ids' ) ) {
			return;
		}

		$min            = max( 1, min( 5, (int) $min_rating ) );
		$visibility_ids = wc_get_product_visibility_term_ids();
		$rated_terms    = array();

		for ( $i = 5; $i >= $min; $i-- ) {
			$key = 'rated-' . $i;
			if ( ! empty( $visibility_ids[ $key ] ) ) {
				$rated_terms[] = (int) $visibility_ids[ $key ];
			}
		}

		if ( empty( $rated_terms ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => array( 0 ),
				'operator' => 'IN',
			);
			return;
		}

		$tax_query[] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_taxonomy_id',
			'terms'    => $rated_terms,
			'operator' => 'IN',
		);
	}

	/**
	 * Execute wc_get_products with optional price filter clauses.
	 *
	 * @param SearchQuery          $query     Search query.
	 * @param array<string, mixed> $overrides Optional overrides.
	 *
	 * @return object|array<int, mixed>
	 */
	public static function query( SearchQuery $query, array $overrides = array() ) {
		$args = self::build_args( $query, $overrides );
		self::apply_wc_catalog_ordering( $query, $args );

		$price_filter = null;
		if ( ! empty( $args[ PriceQueryFilter::QUERY_FLAG ] ) ) {
			$price_filter = new PriceQueryFilter();
			add_filter( 'posts_clauses', array( $price_filter, 'add_clauses' ), 10, 2 );
		}

		try {
			return wc_get_products( $args );
		} finally {
			if ( $price_filter ) {
				remove_filter( 'posts_clauses', array( $price_filter, 'add_clauses' ), 10 );
			}
			self::clear_wc_catalog_ordering_filters();
		}
	}

	/**
	 * Apply WooCommerce catalog ordering (price, popularity, rating, etc.).
	 *
	 * @param SearchQuery          $query Search query.
	 * @param array<string, mixed> $args  wc_get_products args (by reference).
	 *
	 * @return void
	 */
	private static function apply_wc_catalog_ordering( SearchQuery $query, array &$args ): void {
		if ( ! function_exists( 'WC' ) || ! WC()->query ) {
			return;
		}

		$ordering = WC()->query->get_catalog_ordering_args(
			$query->get_orderby(),
			strtoupper( $query->get_order() ),
		);

		$args['orderby'] = $ordering['orderby'];
		$args['order']   = $ordering['order'];

		if ( ! empty( $ordering['meta_key'] ) ) {
			$args['meta_key'] = $ordering['meta_key'];
		}
	}

	/**
	 * Remove WC_Query posts_clauses callbacks registered by get_catalog_ordering_args().
	 *
	 * @return void
	 */
	private static function clear_wc_catalog_ordering_filters(): void {
		if ( ! function_exists( 'WC' ) || ! WC()->query ) {
			return;
		}

		$wc_query  = WC()->query;
		$callbacks = array(
			'order_by_price_asc_post_clauses',
			'order_by_price_desc_post_clauses',
			'order_by_popularity_post_clauses',
			'order_by_rating_post_clauses',
		);

		foreach ( $callbacks as $callback ) {
			remove_filter( 'posts_clauses', array( $wc_query, $callback ) );
		}
	}

	/**
	 * Count products matching query.
	 *
	 * @param SearchQuery $query Search query.
	 *
	 * @return int
	 */
	public static function count( SearchQuery $query ): int {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return 0;
		}

		$result = self::query(
			$query,
			array(
				'limit'    => 1,
				'page'     => 1,
				'paginate' => true,
				'return'   => 'ids',
			),
		);

		return isset( $result->total ) ? (int) $result->total : 0;
	}

	/**
	 * Count published products for a taxonomy term (matches search results).
	 *
	 * @param \WP_Term $term Term object.
	 *
	 * @return int
	 */
	public static function count_for_term( \WP_Term $term ): int {
		static $cache = array();

		$cache_key = $term->taxonomy . ':' . $term->slug;
		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		if ( ! function_exists( 'wc_get_products' ) ) {
			$cache[ $cache_key ] = (int) $term->count;
			return $cache[ $cache_key ];
		}

		$args = array();

		if ( 'product_cat' === $term->taxonomy ) {
			$args['product_cats'] = array( $term->slug );
		} elseif ( 'product_tag' === $term->taxonomy ) {
			$args['product_tags'] = array( $term->slug );
		} elseif ( 0 === strpos( $term->taxonomy, 'pa_' ) ) {
			$args['attributes'] = array(
				substr( $term->taxonomy, 3 ) => array( $term->slug ),
			);
		} elseif ( is_object_in_taxonomy( 'product', $term->taxonomy ) ) {
			$args['taxonomies'] = array(
				$term->taxonomy => array( $term->slug ),
			);
		} else {
			$cache[ $cache_key ] = (int) $term->count;
			return $cache[ $cache_key ];
		}

		$count               = self::count( new SearchQuery( $args ) );
		$cache[ $cache_key ] = $count;

		return $count;
	}

	/**
	 * @param string $orderby Order key.
	 *
	 * @return string
	 */
	private static function map_orderby( string $orderby ): string {
		$map = array(
			'menu_order' => 'menu_order',
			'title'      => 'title',
			'price'      => 'price',
			'date'       => 'date',
			'popularity' => 'popularity',
			'rating'     => 'rating',
			'relevance'  => 'relevance',
		);

		return isset( $map[ $orderby ] ) ? $map[ $orderby ] : 'menu_order';
	}
}
