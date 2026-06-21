<?php

/**
 * Match metadata for product search results display.
 *
 * @package BePlusSmartSearch
 * @subpackage Search
 */

namespace BePlusSmartSearch\Search;

use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determines which enabled search fields matched and should be shown in results.
 */
final class ProductMatchMeta {

	/**
	 * Collect displayable match rows for a product.
	 *
	 * @param WC_Product  $product Product.
	 * @param SearchQuery $query   Active search query.
	 *
	 * @return array<int, array{type: string, label: string, value: string}>
	 */
	public static function collect( WC_Product $product, SearchQuery $query ): array {
		$keyword = trim( $query->get_keyword() );
		if ( '' === $keyword ) {
			return array();
		}

		$fields  = $query->get_search_fields();
		$matches = array();

		if ( in_array( 'sku', $fields, true ) ) {
			$sku = (string) $product->get_sku();
			if ( '' !== $sku && self::matches( $sku, $keyword, $query ) ) {
				$matches[] = array(
					'type'  => 'sku',
					'label' => __( 'SKU', 'beplus-smart-search' ),
					'value' => $sku,
				);
			}
		}

		if ( in_array( 'categories', $fields, true ) ) {
			$matches = array_merge( $matches, self::taxonomy_matches( $product, 'product_cat', $keyword, $query ) );
		}

		if ( in_array( 'tags', $fields, true ) ) {
			$matches = array_merge( $matches, self::taxonomy_matches( $product, 'product_tag', $keyword, $query ) );
		}

		if ( in_array( 'attributes', $fields, true ) ) {
			$matches = array_merge( $matches, self::attribute_matches( $product, $keyword, $query ) );
		}

		return self::dedupe( $matches );
	}

	/**
	 * @param WC_Product  $product  Product.
	 * @param string      $taxonomy Taxonomy name.
	 * @param string      $keyword  Keyword.
	 * @param SearchQuery $query    Search query.
	 *
	 * @return array<int, array{type: string, label: string, value: string}>
	 */
	private static function taxonomy_matches( WC_Product $product, string $taxonomy, string $keyword, SearchQuery $query ): array {
		$terms = wp_get_post_terms( $product->get_id(), $taxonomy );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$type  = 'product_cat' === $taxonomy ? 'category' : 'tag';
		$label = 'product_cat' === $taxonomy
			? __( 'Category', 'beplus-smart-search' )
			: __( 'Tag', 'beplus-smart-search' );

		$matches = array();
		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}
			if ( self::matches( $term->name, $keyword, $query ) || self::matches( $term->slug, $keyword, $query ) ) {
				$matches[] = array(
					'type'  => $type,
					'label' => $label,
					'value' => $term->name,
				);
			}
		}

		return $matches;
	}

	/**
	 * @param WC_Product  $product Product.
	 * @param string      $keyword Keyword.
	 * @param SearchQuery $query   Search query.
	 *
	 * @return array<int, array{type: string, label: string, value: string}>
	 */
	private static function attribute_matches( WC_Product $product, string $keyword, SearchQuery $query ): array {
		$matches    = array();
		$attributes = wc_get_attribute_taxonomies();
		$taxonomies = array();

		if ( is_array( $attributes ) ) {
			foreach ( $attributes as $attribute ) {
				if ( isset( $attribute->attribute_name ) ) {
					$taxonomies[] = wc_attribute_taxonomy_name( $attribute->attribute_name );
				}
			}
		}

		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_post_terms( $product->get_id(), $taxonomy );
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				if ( ! $term instanceof \WP_Term ) {
					continue;
				}
				if ( ! self::matches( $term->name, $keyword, $query ) && ! self::matches( $term->slug, $keyword, $query ) ) {
					continue;
				}

				$attr_label = wc_attribute_label( $taxonomy );
				$matches[]  = array(
					'type'  => 'attribute',
					'label' => $attr_label ? $attr_label : __( 'Attribute', 'beplus-smart-search' ),
					'value' => $term->name,
				);
			}
		}

		return $matches;
	}

	/**
	 * @param string      $haystack Value to test.
	 * @param string      $keyword  Keyword.
	 * @param SearchQuery $query    Search query.
	 *
	 * @return bool
	 */
	private static function matches( string $haystack, string $keyword, SearchQuery $query ): bool {
		$haystack = trim( $haystack );
		$keyword  = trim( $keyword );

		if ( '' === $haystack || '' === $keyword ) {
			return false;
		}

		if ( $query->is_exact_match() ) {
			return false !== stripos( $haystack, $keyword );
		}

		if ( 'and' === $query->get_search_logic() ) {
			$words = preg_split( '/\s+/u', $keyword, -1, PREG_SPLIT_NO_EMPTY );
			foreach ( $words as $word ) {
				if ( false === stripos( $haystack, $word ) ) {
					return false;
				}
			}
			return true;
		}

		$words = preg_split( '/\s+/u', $keyword, -1, PREG_SPLIT_NO_EMPTY );
		foreach ( $words as $word ) {
			if ( false !== stripos( $haystack, $word ) ) {
				return true;
			}
		}

		return false !== stripos( $haystack, $keyword );
	}

	/**
	 * @param array<int, array{type: string, label: string, value: string}> $matches Match rows.
	 *
	 * @return array<int, array{type: string, label: string, value: string}>
	 */
	private static function dedupe( array $matches ): array {
		$seen   = array();
		$result = array();

		foreach ( $matches as $match ) {
			$key = $match['type'] . ':' . $match['value'];
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$result[]     = $match;
		}

		return $result;
	}
}
