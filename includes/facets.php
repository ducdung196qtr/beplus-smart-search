<?php

/**
 * Facet helpers for block render.
 *
 * @package BePlusSmartSearch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Count published products assigned to a taxonomy term (matches search query).
 *
 * @param WP_Term $term Term object.
 *
 * @return int
 */
function beplus_smart_search_count_products_for_term( WP_Term $term ): int {
	return BePlusSmartSearch\Search\ProductQueryBuilder::count_for_term( $term );
}

/**
 * Get product categories for filter selects.
 *
 * @return array<int, WP_Term>
 */
function beplus_smart_search_get_product_categories(): array {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
		),
	);

	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Get product tags for filter selects.
 *
 * @return array<int, WP_Term>
 */
function beplus_smart_search_get_product_tags(): array {
	if ( ! taxonomy_exists( 'product_tag' ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_tag',
			'hide_empty' => true,
		),
	);

	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Get WooCommerce attribute taxonomy metadata (no terms loaded).
 *
 * @return array<int, array{slug: string, label: string, taxonomy: string}>
 */
function beplus_smart_search_get_all_attribute_definitions(): array {
	if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
		return array();
	}

	$items = array();

	foreach ( wc_get_attribute_taxonomies() as $attribute ) {
		$slug = $attribute->attribute_name;

		$items[] = array(
			'slug'     => $slug,
			'label'    => $attribute->attribute_label,
			'taxonomy' => wc_attribute_taxonomy_name( $slug ),
		);
	}

	return $items;
}

/**
 * Whether an attribute slug is enabled for storefront search filters.
 *
 * @param string $slug Attribute slug.
 *
 * @return bool
 */
function beplus_smart_search_is_attribute_enabled( string $slug ): bool {
	$sidebar = beplus_smart_search_get_sidebar_settings();
	$enabled = isset( $sidebar['attribute_enabled'] ) && is_array( $sidebar['attribute_enabled'] )
		? $sidebar['attribute_enabled']
		: array();

	if ( ! array_key_exists( $slug, $enabled ) ) {
		return true;
	}

	return ! empty( $enabled[ $slug ] );
}

/**
 * Attribute slugs enabled in plugin settings.
 *
 * @return array<int, string>
 */
function beplus_smart_search_get_enabled_attribute_slugs(): array {
	$slugs = array();

	foreach ( beplus_smart_search_get_all_attribute_definitions() as $attribute ) {
		if ( beplus_smart_search_is_attribute_enabled( $attribute['slug'] ) ) {
			$slugs[] = $attribute['slug'];
		}
	}

	return $slugs;
}

/**
 * Get WooCommerce attribute taxonomies with terms.
 *
 * @param array<int, string> $allowed_slugs Optional attribute slugs to include.
 *
 * @return array<int, array{slug: string, label: string, taxonomy: string, terms: array<int, WP_Term>}>
 */
function beplus_smart_search_get_product_attributes( array $allowed_slugs = array() ): array {
	if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
		return array();
	}

	$taxonomies = wc_get_attribute_taxonomies();
	$items      = array();

	foreach ( $taxonomies as $attribute ) {
		$slug = $attribute->attribute_name;

		if ( ! empty( $allowed_slugs ) && ! in_array( $slug, $allowed_slugs, true ) ) {
			continue;
		}

		$taxonomy = wc_attribute_taxonomy_name( $slug );

		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			),
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			continue;
		}

		$items[] = array(
			'slug'     => $slug,
			'label'    => $attribute->attribute_label,
			'taxonomy' => $taxonomy,
			'terms'    => $terms,
		);
	}

	return $items;
}

/**
 * Build a hierarchical tree from a flat term list.
 *
 * @param array<int, WP_Term> $terms Terms.
 *
 * @return array<int, array{term: WP_Term, children: array<int, array{term: WP_Term, children: array<int, mixed>}>}>
 */
function beplus_smart_search_build_term_tree( array $terms ): array {
	$indexed = array();
	$tree    = array();

	foreach ( $terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}
		$indexed[ $term->term_id ] = array(
			'term'     => $term,
			'children' => array(),
		);
	}

	foreach ( $terms as $term ) {
		if ( ! $term instanceof WP_Term || ! isset( $indexed[ $term->term_id ] ) ) {
			continue;
		}

		$parent_id = (int) $term->parent;
		if ( $parent_id > 0 && isset( $indexed[ $parent_id ] ) ) {
			$indexed[ $parent_id ]['children'][] = &$indexed[ $term->term_id ];
		} else {
			$tree[] = &$indexed[ $term->term_id ];
		}
	}

	return $tree;
}

/**
 * Get terms for a product taxonomy facet.
 *
 * @param string $taxonomy Taxonomy slug.
 *
 * @return array<int, WP_Term>
 */
function beplus_smart_search_get_taxonomy_terms( string $taxonomy ): array {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		),
	);

	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Get brand terms for the configured brand taxonomy.
 *
 * @return array<int, WP_Term>
 */
function beplus_smart_search_get_brand_terms(): array {
	$taxonomy = beplus_smart_search_get_brand_taxonomy();
	if ( ! $taxonomy ) {
		return array();
	}

	return beplus_smart_search_get_taxonomy_terms( $taxonomy );
}

/**
 * Term IDs that should start expanded (current term + ancestors).
 *
 * @param string $taxonomy Taxonomy slug.
 *
 * @return array<int, int>
 */
function beplus_smart_search_get_expanded_term_ids( string $taxonomy ): array {
	if ( ! is_tax( $taxonomy ) ) {
		return array();
	}

	$queried = get_queried_object();
	if ( ! $queried instanceof WP_Term || $queried->taxonomy !== $taxonomy ) {
		return array();
	}

	$ids = array( (int) $queried->term_id );
	$ids = array_merge(
		$ids,
		array_map( 'intval', get_ancestors( $queried->term_id, $taxonomy, 'taxonomy' ) ),
	);

	return array_values( array_unique( $ids ) );
}
