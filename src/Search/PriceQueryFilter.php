<?php
/**
 * Applies WooCommerce-compatible price filtering to product queries.
 *
 * @package BePlusSmartSearch
 * @subpackage Search
 */

namespace BePlusSmartSearch\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds posts_clauses for min/max price using wc_product_meta_lookup.
 */
final class PriceQueryFilter {

	/**
	 * Query var flag so clauses only apply to our product queries.
	 */
	public const QUERY_FLAG = 'bpss_price_filter';

	/**
	 * Run wc_get_products with price filter clauses applied.
	 *
	 * @param array<string, mixed> $args wc_get_products args.
	 * @return object|array<int, mixed>
	 */
	public static function run( array $args ) {
		$filter = new self();
		add_filter( 'posts_clauses', array( $filter, 'add_clauses' ), 10, 2 );

		try {
			return wc_get_products( $args );
		} finally {
			remove_filter( 'posts_clauses', array( $filter, 'add_clauses' ), 10 );
		}
	}

	/**
	 * Append price filter SQL to the product query.
	 *
	 * @param array<string, mixed> $args     Query clauses.
	 * @param \WP_Query            $wp_query WP_Query instance.
	 * @return array<string, mixed>
	 */
	public function add_clauses( array $args, \WP_Query $wp_query ): array {
		if ( ! $wp_query->get( self::QUERY_FLAG ) ) {
			return $args;
		}

		$min_price = (float) $wp_query->get( 'min_price' );
		$max_price = (float) $wp_query->get( 'max_price' );

		if ( $min_price <= 0 && $max_price <= 0 ) {
			return $args;
		}

		global $wpdb;

		$adjust_for_taxes = $this->should_adjust_price_filters_for_displayed_taxes();
		$args['join']       = $this->append_product_sorting_table_join( $args['join'] );

		if ( $min_price > 0 ) {
			if ( $adjust_for_taxes ) {
				$args['where'] .= $this->get_price_filter_query_for_displayed_taxes( $min_price, 'max_price', '>=' );
			} else {
				$args['where'] .= $wpdb->prepare( ' AND wc_product_meta_lookup.max_price >= %f ', $min_price );
			}
		}

		if ( $max_price > 0 ) {
			if ( $adjust_for_taxes ) {
				$args['where'] .= $this->get_price_filter_query_for_displayed_taxes( $max_price, 'min_price', '<=' );
			} else {
				$args['where'] .= $wpdb->prepare( ' AND wc_product_meta_lookup.min_price <= %f ', $max_price );
			}
		}

		return $args;
	}

	/**
	 * Join wc_product_meta_lookup to posts if not already joined.
	 *
	 * @param string $sql SQL join.
	 * @return string
	 */
	private function append_product_sorting_table_join( string $sql ): string {
		global $wpdb;

		if ( ! strstr( $sql, 'wc_product_meta_lookup' ) ) {
			$sql .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON $wpdb->posts.ID = wc_product_meta_lookup.product_id ";
		}

		return $sql;
	}

	/**
	 * Whether price filters need tax adjustment to match displayed prices.
	 *
	 * @return bool
	 */
	private function should_adjust_price_filters_for_displayed_taxes(): bool {
		if ( ! wc_tax_enabled() ) {
			return false;
		}

		$display  = get_option( 'woocommerce_tax_display_shop' );
		$database = wc_prices_include_tax() ? 'incl' : 'excl';

		return $display !== $database;
	}

	/**
	 * Build price filter SQL when shop display tax differs from stored prices.
	 *
	 * @param float  $price_filter Filter amount.
	 * @param string $column       Lookup column (min_price or max_price).
	 * @param string $operator     >= or <=.
	 * @return string
	 */
	private function get_price_filter_query_for_displayed_taxes( float $price_filter, string $column = 'min_price', string $operator = '>=' ): string {
		global $wpdb;

		if ( ! in_array( $operator, array( '>=', '<=' ), true ) ) {
			return '';
		}

		$product_tax_classes = $wpdb->get_col( "SELECT DISTINCT tax_class FROM {$wpdb->wc_product_meta_lookup};" );

		if ( empty( $product_tax_classes ) ) {
			return '';
		}

		$or_queries = array();

		foreach ( $product_tax_classes as $tax_class ) {
			$adjusted_price_filter = $this->adjust_price_filter_for_tax_class( $price_filter, $tax_class );
			$or_queries[]          = $wpdb->prepare(
				'( wc_product_meta_lookup.tax_class = %s AND wc_product_meta_lookup.`' . esc_sql( $column ) . '` ' . esc_sql( $operator ) . ' %f )',
				$tax_class,
				$adjusted_price_filter
			);
		}

		return $wpdb->prepare(
			' AND (
				wc_product_meta_lookup.tax_status = "taxable" AND ( 0=1 OR ' . implode( ' OR ', $or_queries ) . ')
				OR ( wc_product_meta_lookup.tax_status != "taxable" AND wc_product_meta_lookup.`' . esc_sql( $column ) . '` ' . esc_sql( $operator ) . ' %f )
			) ',
			$price_filter
		);
	}

	/**
	 * Adjust filter amount for a product tax class.
	 *
	 * @param float  $price_filter Filter amount.
	 * @param string $tax_class    Tax class slug.
	 * @return float
	 */
	private function adjust_price_filter_for_tax_class( float $price_filter, string $tax_class ): float {
		$tax_display    = get_option( 'woocommerce_tax_display_shop' );
		$tax_rates      = \WC_Tax::get_rates( $tax_class );
		$base_tax_rates = \WC_Tax::get_base_tax_rates( $tax_class );

		if ( 'incl' === $tax_display ) {
			$taxes = apply_filters( 'woocommerce_adjust_non_base_location_prices', true )
				? \WC_Tax::calc_tax( $price_filter, $base_tax_rates, true )
				: \WC_Tax::calc_tax( $price_filter, $tax_rates, true );

			return $price_filter - array_sum( $taxes );
		}

		$taxes = \WC_Tax::calc_tax( $price_filter, $tax_rates, false );

		return $price_filter + array_sum( $taxes );
	}
}
