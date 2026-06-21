<?php

/**
 * Multi-field product keyword search (title, SKU, content, taxonomies).
 *
 * @package BePlusSmartSearch
 * @subpackage Search
 */

namespace BePlusSmartSearch\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extends product queries with configurable search fields.
 */
final class SearchFieldsFilter {

	/**
	 * Query var flag.
	 */
	public const QUERY_FLAG = 'bpss_search_fields';

	/**
	 * Allowed search field keys.
	 */
	public const ALLOWED_FIELDS = array(
		'title',
		'sku',
		'content',
		'categories',
		'tags',
		'attributes',
	);

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
	 * Whether extended field search should replace default `s` param.
	 *
	 * @param SearchQuery $query Search query.
	 *
	 * @return bool
	 */
	public static function should_apply( SearchQuery $query ): bool {
		return '' !== trim( $query->get_keyword() ) && ! empty( $query->get_search_fields() );
	}

	/**
	 * @param mixed $value Raw field list.
	 *
	 * @return array<int, string>
	 */
	public static function normalize_fields( $value ): array {
		if ( ! is_array( $value ) ) {
			if ( is_string( $value ) && '' !== $value ) {
				$value = array_map( 'trim', explode( ',', $value ) );
			} else {
				return array();
			}
		}

		$normalized = array();
		foreach ( $value as $field ) {
			$field = sanitize_key( (string) $field );
			if ( in_array( $field, self::ALLOWED_FIELDS, true ) ) {
				$normalized[] = $field;
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Clear WP core search SQL — custom WHERE handles matching.
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

		return '';
	}

	/**
	 * @param string    $distinct DISTINCT clause.
	 * @param \WP_Query $wp_query WP_Query instance.
	 *
	 * @return string
	 */
	public function filter_distinct( string $distinct, \WP_Query $wp_query ): string {
		if ( ! $wp_query->get( self::QUERY_FLAG ) ) {
			return $distinct;
		}

		return 'DISTINCT';
	}

	/**
	 * @param string    $join     JOIN clause.
	 * @param \WP_Query $wp_query WP_Query instance.
	 *
	 * @return string
	 */
	public function filter_join( string $join, \WP_Query $wp_query ): string {
		if ( ! $wp_query->get( self::QUERY_FLAG ) ) {
			return $join;
		}

		global $wpdb;

		$fields = $this->query->get_search_fields();

		if ( in_array( 'sku', $fields, true ) ) {
			$join .= " LEFT JOIN {$wpdb->postmeta} AS bpss_sku ON ( {$wpdb->posts}.ID = bpss_sku.post_id AND bpss_sku.meta_key = '_sku' ) ";
		}

		if ( in_array( 'categories', $fields, true ) ) {
			$join .= " LEFT JOIN {$wpdb->term_relationships} AS bpss_cat_tr ON ( {$wpdb->posts}.ID = bpss_cat_tr.object_id ) ";
			$join .= " LEFT JOIN {$wpdb->term_taxonomy} AS bpss_cat_tt ON ( bpss_cat_tr.term_taxonomy_id = bpss_cat_tt.term_taxonomy_id AND bpss_cat_tt.taxonomy = 'product_cat' ) ";
			$join .= " LEFT JOIN {$wpdb->terms} AS bpss_cat_t ON ( bpss_cat_tt.term_id = bpss_cat_t.term_id ) ";
		}

		if ( in_array( 'tags', $fields, true ) ) {
			$join .= " LEFT JOIN {$wpdb->term_relationships} AS bpss_tag_tr ON ( {$wpdb->posts}.ID = bpss_tag_tr.object_id ) ";
			$join .= " LEFT JOIN {$wpdb->term_taxonomy} AS bpss_tag_tt ON ( bpss_tag_tr.term_taxonomy_id = bpss_tag_tt.term_taxonomy_id AND bpss_tag_tt.taxonomy = 'product_tag' ) ";
			$join .= " LEFT JOIN {$wpdb->terms} AS bpss_tag_t ON ( bpss_tag_tt.term_id = bpss_tag_t.term_id ) ";
		}

		if ( in_array( 'attributes', $fields, true ) ) {
			$join .= " LEFT JOIN {$wpdb->term_relationships} AS bpss_attr_tr ON ( {$wpdb->posts}.ID = bpss_attr_tr.object_id ) ";
			$join .= " LEFT JOIN {$wpdb->term_taxonomy} AS bpss_attr_tt ON ( bpss_attr_tr.term_taxonomy_id = bpss_attr_tt.term_taxonomy_id AND bpss_attr_tt.taxonomy LIKE 'pa\\_%' ) ";
			$join .= " LEFT JOIN {$wpdb->terms} AS bpss_attr_t ON ( bpss_attr_tt.term_id = bpss_attr_t.term_id ) ";
		}

		return $join;
	}

	/**
	 * @param string    $where    WHERE clause.
	 * @param \WP_Query $wp_query WP_Query instance.
	 *
	 * @return string
	 */
	public function filter_where( string $where, \WP_Query $wp_query ): string {
		if ( ! $wp_query->get( self::QUERY_FLAG ) ) {
			return $where;
		}

		global $wpdb;

		$keyword = trim( $this->query->get_keyword() );
		if ( '' === $keyword ) {
			return $where;
		}

		$sql = $this->build_match_sql( $keyword );
		if ( '' === $sql ) {
			return $where;
		}

		return $where . ' AND ( ' . $sql . ' ) ';
	}

	/**
	 * Build OR / AND SQL for keyword against enabled fields.
	 *
	 * @param string $keyword Search keyword.
	 *
	 * @return string
	 */
	private function build_match_sql( string $keyword ): string {
		global $wpdb;

		$fields = $this->query->get_search_fields();
		if ( empty( $fields ) ) {
			return '';
		}

		if ( $this->query->is_exact_match() ) {
			return $this->term_group_sql( $keyword, $fields );
		}

		$words = preg_split( '/\s+/u', $keyword, -1, PREG_SPLIT_NO_EMPTY );
		if ( empty( $words ) ) {
			return '';
		}

		if ( 'and' === $this->query->get_search_logic() ) {
			$groups = array();
			foreach ( $words as $word ) {
				$group = $this->term_group_sql( $word, $fields );
				if ( '' !== $group ) {
					$groups[] = '(' . $group . ')';
				}
			}

			return empty( $groups ) ? '' : implode( ' AND ', $groups );
		}

		$groups = array();
		foreach ( $words as $word ) {
			$group = $this->term_group_sql( $word, $fields );
			if ( '' !== $group ) {
				$groups[] = '(' . $group . ')';
			}
		}

		return empty( $groups ) ? '' : implode( ' OR ', $groups );
	}

	/**
	 * OR-group for a single term across enabled fields.
	 *
	 * @param string             $term   Search term.
	 * @param array<int, string> $fields Enabled fields.
	 *
	 * @return string
	 */
	private function term_group_sql( string $term, array $fields ): string {
		global $wpdb;

		$like   = '%' . $wpdb->esc_like( $term ) . '%';
		$parts  = array();

		if ( in_array( 'title', $fields, true ) ) {
			$parts[] = $wpdb->prepare( "{$wpdb->posts}.post_title LIKE %s", $like );
		}

		if ( in_array( 'content', $fields, true ) ) {
			$parts[] = $wpdb->prepare( "{$wpdb->posts}.post_content LIKE %s", $like );
			$parts[] = $wpdb->prepare( "{$wpdb->posts}.post_excerpt LIKE %s", $like );
		}

		if ( in_array( 'sku', $fields, true ) ) {
			$parts[] = $wpdb->prepare( 'bpss_sku.meta_value LIKE %s', $like );
			$parts[] = $wpdb->prepare(
				"EXISTS (
					SELECT 1 FROM {$wpdb->posts} bpss_var
					INNER JOIN {$wpdb->postmeta} bpss_var_sku ON ( bpss_var.ID = bpss_var_sku.post_id AND bpss_var_sku.meta_key = '_sku' )
					WHERE bpss_var.post_parent = {$wpdb->posts}.ID
					AND bpss_var.post_type = 'product_variation'
					AND bpss_var_sku.meta_value LIKE %s
				)",
				$like,
			);
		}

		if ( in_array( 'categories', $fields, true ) ) {
			$parts[] = $wpdb->prepare( 'bpss_cat_t.name LIKE %s', $like );
			$parts[] = $wpdb->prepare( 'bpss_cat_t.slug LIKE %s', $like );
		}

		if ( in_array( 'tags', $fields, true ) ) {
			$parts[] = $wpdb->prepare( 'bpss_tag_t.name LIKE %s', $like );
			$parts[] = $wpdb->prepare( 'bpss_tag_t.slug LIKE %s', $like );
		}

		if ( in_array( 'attributes', $fields, true ) ) {
			$parts[] = $wpdb->prepare( 'bpss_attr_t.name LIKE %s', $like );
			$parts[] = $wpdb->prepare( 'bpss_attr_t.slug LIKE %s', $like );
		}

		return empty( $parts ) ? '' : implode( ' OR ', $parts );
	}

	/**
	 * Register filters for a product query.
	 *
	 * @param SearchQuery $query Search query.
	 *
	 * @return self
	 */
	public static function register( SearchQuery $query ): self {
		$filter = new self( $query );
		add_filter( 'posts_search', array( $filter, 'filter_search' ), 500, 2 );
		add_filter( 'posts_join', array( $filter, 'filter_join' ), 10, 2 );
		add_filter( 'posts_where', array( $filter, 'filter_where' ), 10, 2 );
		add_filter( 'posts_distinct', array( $filter, 'filter_distinct' ), 10, 2 );

		return $filter;
	}

	/**
	 * Remove filters.
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'posts_search', array( $this, 'filter_search' ), 500 );
		remove_filter( 'posts_join', array( $this, 'filter_join' ), 10 );
		remove_filter( 'posts_where', array( $this, 'filter_where' ), 10 );
		remove_filter( 'posts_distinct', array( $this, 'filter_distinct' ), 10 );
	}
}
