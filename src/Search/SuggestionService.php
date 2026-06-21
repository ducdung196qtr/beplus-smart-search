<?php

/**
 * Search suggestion service.
 *
 * @package BePlusSmartSearch
 * @subpackage Search
 */

namespace BePlusSmartSearch\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates keyword suggestions and optional spelling corrections.
 */
final class SuggestionService {

	/**
	 * Build suggestions for a partial keyword.
	 *
	 * @param string               $keyword Partial search term.
	 * @param int                  $limit   Max suggestions.
	 * @param array<string, mixed> $args    Optional filters (product_cat, etc.).
	 *
	 * @return array{suggestions: array<int, string>, corrected: string|null}
	 */
	public function get_suggestions( string $keyword, int $limit = 5, array $args = array() ): array {
		$keyword = trim( $keyword );
		$limit   = max( 1, min( 10, $limit ) );

		if ( '' === $keyword ) {
			return array(
				'suggestions' => array(),
				'corrected'   => null,
			);
		}

		$query_args = array(
			'keyword'       => $keyword,
			'product_cats'  => $this->normalize_cat_param( $args['product_cat'] ?? array() ),
			'per_page'      => 20,
			'page'          => 1,
			'search_logic'  => isset( $args['search_logic'] ) ? (string) $args['search_logic'] : 'or',
			'exact_match'   => ! empty( $args['exact_match'] ),
			'search_fields' => SearchFieldsFilter::normalize_fields( $args['search_fields'] ?? array() ),
		);

		$query  = new SearchQuery( $query_args );
		$result = ProductQueryBuilder::query(
			$query,
			array(
				'return' => 'ids',
			),
		);

		$product_ids = isset( $result->products ) ? $result->products : array();
		$suggestions = array();
		$needle      = mb_strtolower( $keyword );

		foreach ( $product_ids as $product_id ) {
			$title = get_the_title( (int) $product_id );
			if ( ! $title ) {
				continue;
			}

			$title_lower = mb_strtolower( $title );
			if ( false !== mb_strpos( $title_lower, $needle ) ) {
				$suggestions[] = $title;
			}

			$words = preg_split( '/\s+/u', $title, -1, PREG_SPLIT_NO_EMPTY );
			foreach ( $words as $word ) {
				$word_lower = mb_strtolower( $word );
				if ( 0 === mb_strpos( $word_lower, $needle ) && mb_strlen( $word_lower ) > mb_strlen( $needle ) ) {
					$suggestions[] = $word;
				}
			}

			if ( false !== mb_strpos( $title_lower, $needle ) ) {
				$suggestions[] = sprintf(
					/* translators: %s: partial keyword */
					__( 'with %s', 'beplus-smart-search' ),
					$keyword,
				);
			}
		}

		$suggestions = array_values(
			array_unique(
				array_filter(
					array_map( 'trim', $suggestions ),
					static function ( $item ) use ( $needle ) {
						return '' !== $item && mb_strtolower( $item ) !== $needle;
					},
				),
			),
		);

		$suggestions = array_slice( $suggestions, 0, $limit );

		$corrected = null;
		if ( ! empty( $args['misspelling_fix'] ) && empty( $product_ids ) ) {
			$corrected = $this->correct_spelling( $keyword );
		}

		return array(
			'suggestions' => $suggestions,
			'corrected'   => $corrected,
		);
	}

	/**
	 * Find closest product title word using Levenshtein distance.
	 *
	 * @param string $keyword Misspelled term.
	 *
	 * @return string|null
	 */
	public function correct_spelling( string $keyword ): ?string {
		$keyword = trim( $keyword );
		if ( '' === $keyword || ! function_exists( 'wc_get_products' ) ) {
			return null;
		}

		$dictionary = $this->get_title_word_dictionary();
		if ( empty( $dictionary ) ) {
			return null;
		}

		$best_word  = null;
		$best_score = PHP_INT_MAX;
		$needle_len = mb_strlen( $keyword );

		foreach ( $dictionary as $word ) {
			$word_len = mb_strlen( $word );
			if ( abs( $word_len - $needle_len ) > 3 ) {
				continue;
			}

			$distance = levenshtein( mb_strtolower( $keyword ), mb_strtolower( $word ) );
			if ( $distance < $best_score && $distance <= 2 ) {
				$best_score = $distance;
				$best_word  = $word;
			}
		}

		return $best_word;
	}

	/**
	 * Cached list of unique words from published product titles.
	 *
	 * @return array<int, string>
	 */
	private function get_title_word_dictionary(): array {
		static $words = null;

		if ( null !== $words ) {
			return $words;
		}

		$words   = array();
		$results = wc_get_products(
			array(
				'status' => 'publish',
				'limit'  => 200,
				'return' => 'ids',
			),
		);

		foreach ( $results as $product_id ) {
			$title = get_the_title( (int) $product_id );
			if ( ! $title ) {
				continue;
			}
			$parts = preg_split( '/\s+/u', $title, -1, PREG_SPLIT_NO_EMPTY );
			foreach ( $parts as $part ) {
				$clean = preg_replace( '/[^\p{L}\p{N}]/u', '', $part );
				if ( $clean && mb_strlen( $clean ) >= 3 ) {
					$words[] = $clean;
				}
			}
		}

		$words = array_values( array_unique( $words ) );

		return $words;
	}

	/**
	 * @param mixed $value Category slug(s).
	 *
	 * @return array<int, string>
	 */
	private function normalize_cat_param( $value ): array {
		if ( is_array( $value ) ) {
			return array_values( array_filter( array_map( 'sanitize_text_field', $value ) ) );
		}

		if ( is_string( $value ) && '' !== $value ) {
			return array( sanitize_text_field( $value ) );
		}

		return array();
	}
}
