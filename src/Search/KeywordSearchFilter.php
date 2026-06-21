<?php

/**
 * Custom keyword search clauses (exact match, AND/OR logic).
 *
 * @package BePlusSmartSearch
 * @subpackage Search
 */

namespace BePlusSmartSearch\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Modifies WP search SQL for advanced keyword matching.
 */
final class KeywordSearchFilter {

	/**
	 * Query var flag for product queries using custom keyword logic.
	 */
	public const QUERY_FLAG = 'bpss_keyword_search';

	/**
	 * @var SearchQuery
	 */
	private SearchQuery $query;

	/**
	 * @param SearchQuery $query Search query.
	 */
	public function __construct( SearchQuery $query ) {
		$this->query = $query;
	}

	/**
	 * Whether custom keyword filtering should run.
	 *
	 * @param SearchQuery $query Search query.
	 *
	 * @return bool
	 */
	public static function should_apply( SearchQuery $query ): bool {
		$keyword = trim( $query->get_keyword() );

		if ( '' === $keyword ) {
			return false;
		}

		return $query->is_exact_match() || 'and' === $query->get_search_logic();
	}

	/**
	 * Replace default search with custom AND/exact clauses.
	 *
	 * @param string    $search   Search SQL.
	 * @param \WP_Query $wp_query WP_Query instance.
	 *
	 * @return string
	 */
	public function filter_search( string $search, \WP_Query $wp_query ): string {
		if ( ! $wp_query->get( self::QUERY_FLAG ) ) {
			return $search;
		}

		global $wpdb;

		$keyword = trim( $this->query->get_keyword() );
		if ( '' === $keyword ) {
			return $search;
		}

		if ( $this->query->is_exact_match() ) {
			$like = '%' . $wpdb->esc_like( $keyword ) . '%';
			return $wpdb->prepare(
				" AND ( {$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_content LIKE %s ) ",
				$like,
				$like,
			);
		}

		if ( 'and' === $this->query->get_search_logic() ) {
			$words = preg_split( '/\s+/u', $keyword, -1, PREG_SPLIT_NO_EMPTY );
			if ( empty( $words ) ) {
				return $search;
			}

			$clauses = array();
			foreach ( $words as $word ) {
				$like      = '%' . $wpdb->esc_like( $word ) . '%';
				$clauses[] = $wpdb->prepare(
					"( {$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_content LIKE %s OR {$wpdb->posts}.post_excerpt LIKE %s )",
					$like,
					$like,
					$like,
				);
			}

			return ' AND ( ' . implode( ' AND ', $clauses ) . ' ) ';
		}

		return $search;
	}
}
