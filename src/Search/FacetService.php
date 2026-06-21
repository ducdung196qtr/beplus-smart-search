<?php
/**
 * Contextual facet resolver.
 *
 * @package BePlusSmartSearch
 * @subpackage Search
 */

namespace BePlusSmartSearch\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns available facet terms based on active filters.
 */
final class FacetService {

	/**
	 * Get facets for display mode.
	 *
	 * @param SearchQuery $context Active filter context.
	 * @param string      $mode    all|contextual.
	 * @return array<string, mixed>
	 */
	public function get_facets( SearchQuery $context, string $mode = 'all' ): array {
		if ( 'contextual' !== $mode ) {
			return $this->get_all_facets();
		}

		return array(
			'categories' => $this->get_contextual_categories( $context ),
			'tags'       => $this->get_contextual_tags( $context ),
			'attributes' => $this->get_contextual_attributes( $context ),
			'ratings'    => $this->get_contextual_ratings( $context ),
			'taxonomies' => $this->get_contextual_taxonomies( $context ),
			'mode'       => 'contextual',
		);
	}

	/**
	 * All facets without contextual filtering.
	 *
	 * @return array<string, mixed>
	 */
	public function get_all_facets(): array {
		return array(
			'categories' => $this->format_terms( $this->get_terms( 'product_cat' ) ),
			'tags'       => $this->format_terms( $this->get_terms( 'product_tag' ) ),
			'attributes' => $this->get_all_attributes(),
			'ratings'    => $this->get_all_ratings(),
			'taxonomies' => $this->get_all_taxonomy_facets(),
			'mode'       => 'all',
		);
	}

	/**
	 * @param SearchQuery $context Context.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_contextual_categories( SearchQuery $context ): array {
		$base = $this->context_without( $context, 'category' );
		$terms = $this->get_terms( 'product_cat' );
		$selected = $context->get_product_cats();

		return $this->filter_available_terms(
			$terms,
			$selected,
			function ( \WP_Term $term ) use ( $base ) {
				return $this->query_with_category( $base, array( $term->slug ) );
			}
		);
	}

	/**
	 * @param SearchQuery $context Context.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_contextual_tags( SearchQuery $context ): array {
		$base = $this->context_without( $context, 'tag' );
		$terms = $this->get_terms( 'product_tag' );
		$selected = $context->get_product_tags();

		return $this->filter_available_terms(
			$terms,
			$selected,
			function ( \WP_Term $term ) use ( $base ) {
				return $this->query_with_tag( $base, array( $term->slug ) );
			}
		);
	}

	/**
	 * @param SearchQuery $context Context.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_contextual_attributes( SearchQuery $context ): array {
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return array();
		}

		$items      = array();
		$taxonomies = wc_get_attribute_taxonomies();

		foreach ( $taxonomies as $attribute ) {
			$slug     = $attribute->attribute_name;
			$taxonomy = wc_attribute_taxonomy_name( $slug );

			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$terms    = $this->get_terms( $taxonomy );
			$base     = $this->context_without( $context, 'attribute', $slug );
			$selected = isset( $context->get_attributes()[ $slug ] )
				? $context->get_attributes()[ $slug ]
				: array();

			$term_items = $this->filter_available_terms(
				$terms,
				$selected,
				function ( \WP_Term $term ) use ( $base, $slug ) {
					return $this->query_with_attribute( $base, $slug, array( $term->slug ) );
				}
			);

			if ( empty( $term_items ) ) {
				continue;
			}

			$items[] = array(
				'slug'  => $slug,
				'label' => $attribute->attribute_label,
				'terms' => array_map(
					function ( array $item ) {
						return array(
							'slug'  => $item['slug'],
							'name'  => $item['name'],
							'count' => $item['count'],
						);
					},
					$term_items
				),
			);
		}

		return $items;
	}

	/**
	 * @param SearchQuery $context Context.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_contextual_ratings( SearchQuery $context ): array {
		if ( ! function_exists( 'beplus_smart_search_get_rating_filter_options' ) ) {
			return array();
		}

		$base  = $this->context_without( $context, 'rating' );
		$items = array();

		// Rating uses exclusive radios — always return every option so users can switch.
		foreach ( beplus_smart_search_get_rating_filter_options() as $option ) {
			$items[] = array(
				'slug'  => (string) $option['value'],
				'name'  => (string) $option['label'],
				'count' => $this->query_with_rating( $base, (float) $option['value'] ),
			);
		}

		return $items;
	}

	/**
	 * @param SearchQuery $context Context.
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	private function get_contextual_taxonomies( SearchQuery $context ): array {
		if ( ! function_exists( 'beplus_smart_search_get_configured_filter_taxonomies' ) ) {
			return array();
		}

		$items = array();

		foreach ( beplus_smart_search_get_configured_filter_taxonomies() as $taxonomy ) {
			$terms = $this->get_contextual_taxonomy_terms( $context, $taxonomy );
			if ( ! empty( $terms ) ) {
				$items[ $taxonomy ] = $terms;
			}
		}

		return $items;
	}

	/**
	 * @param SearchQuery $context  Context.
	 * @param string      $taxonomy Taxonomy slug.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_contextual_taxonomy_terms( SearchQuery $context, string $taxonomy ): array {
		$base     = $this->context_without( $context, 'taxonomy', $taxonomy );
		$terms    = $this->get_terms( $taxonomy );
		$selected = isset( $context->get_taxonomies()[ $taxonomy ] )
			? $context->get_taxonomies()[ $taxonomy ]
			: array();

		return $this->filter_available_terms(
			$terms,
			$selected,
			function ( \WP_Term $term ) use ( $base, $taxonomy ) {
				return $this->query_with_taxonomy( $base, $taxonomy, array( $term->slug ) );
			}
		);
	}

	/**
	 * @param array<int, WP_Term>        $terms    All terms.
	 * @param array<int, string>         $selected Selected slugs (always visible).
	 * @param callable                   $counter  Count callback.
	 * @return array<int, array<string, mixed>>
	 */
	private function filter_available_terms( array $terms, array $selected, callable $counter ): array {
		$items = array();

		foreach ( $terms as $term ) {
			$is_selected = in_array( $term->slug, $selected, true );
			$count       = (int) $counter( $term );

			if ( $count > 0 || $is_selected ) {
				$items[] = array(
					'id'    => (int) $term->term_id,
					'slug'  => $term->slug,
					'name'  => $term->name,
					'count' => $count,
				);
			}
		}

		return $items;
	}

	/**
	 * Remove one facet group from context.
	 *
	 * @param SearchQuery $context   Context.
	 * @param string      $group     category|tag|attribute.
	 * @param string      $attr_slug Attribute slug when group is attribute.
	 * @return SearchQuery
	 */
	private function context_without( SearchQuery $context, string $group, string $attr_slug = '' ): SearchQuery {
		$args = array(
			'keyword'      => $context->get_keyword(),
			'product_cats' => $context->get_product_cats(),
			'product_tags' => $context->get_product_tags(),
			'attributes'   => $context->get_attributes(),
			'taxonomies'   => $context->get_taxonomies(),
			'stock_status' => $context->get_stock_status(),
			'on_sale'      => $context->is_on_sale(),
			'featured'     => $context->is_featured(),
			'min_rating'   => $context->get_min_rating(),
			'min_price'    => $context->get_min_price(),
			'max_price'    => $context->get_max_price(),
		);

		if ( 'category' === $group ) {
			$args['product_cats'] = array();
		} elseif ( 'tag' === $group ) {
			$args['product_tags'] = array();
		} elseif ( 'attribute' === $group && $attr_slug ) {
			$attributes = $args['attributes'];
			unset( $attributes[ $attr_slug ] );
			$args['attributes'] = $attributes;
		} elseif ( 'rating' === $group ) {
			$args['min_rating'] = 0;
		} elseif ( 'taxonomy' === $group && $attr_slug ) {
			$taxonomies = $args['taxonomies'];
			unset( $taxonomies[ $attr_slug ] );
			$args['taxonomies'] = $taxonomies;
		}

		return new SearchQuery( $args );
	}

	/**
	 * @param SearchQuery        $base Base query.
	 * @param array<int, string> $slugs Category slugs.
	 * @return int
	 */
	private function query_with_category( SearchQuery $base, array $slugs ): int {
		return ProductQueryBuilder::count(
			new SearchQuery(
				array(
					'keyword'      => $base->get_keyword(),
					'product_cats' => $slugs,
					'product_tags' => $base->get_product_tags(),
					'attributes'   => $base->get_attributes(),
					'taxonomies'   => $base->get_taxonomies(),
					'stock_status' => $base->get_stock_status(),
					'on_sale'      => $base->is_on_sale(),
					'featured'     => $base->is_featured(),
					'min_rating'   => $base->get_min_rating(),
					'min_price'    => $base->get_min_price(),
					'max_price'    => $base->get_max_price(),
				)
			)
		);
	}

	/**
	 * @param SearchQuery        $base Base query.
	 * @param array<int, string> $slugs Tag slugs.
	 * @return int
	 */
	private function query_with_tag( SearchQuery $base, array $slugs ): int {
		return ProductQueryBuilder::count(
			new SearchQuery(
				array(
					'keyword'      => $base->get_keyword(),
					'product_cats' => $base->get_product_cats(),
					'product_tags' => $slugs,
					'attributes'   => $base->get_attributes(),
					'taxonomies'   => $base->get_taxonomies(),
					'stock_status' => $base->get_stock_status(),
					'on_sale'      => $base->is_on_sale(),
					'featured'     => $base->is_featured(),
					'min_rating'   => $base->get_min_rating(),
					'min_price'    => $base->get_min_price(),
					'max_price'    => $base->get_max_price(),
				)
			)
		);
	}

	/**
	 * @param SearchQuery        $base Base query.
	 * @param string             $slug Attribute slug.
	 * @param array<int, string> $term_slugs Term slugs.
	 * @return int
	 */
	private function query_with_attribute( SearchQuery $base, string $slug, array $term_slugs ): int {
		$attributes         = $base->get_attributes();
		$attributes[ $slug ] = $term_slugs;

		return ProductQueryBuilder::count(
			new SearchQuery(
				array(
					'keyword'      => $base->get_keyword(),
					'product_cats' => $base->get_product_cats(),
					'product_tags' => $base->get_product_tags(),
					'attributes'   => $attributes,
					'taxonomies'   => $base->get_taxonomies(),
					'stock_status' => $base->get_stock_status(),
					'on_sale'      => $base->is_on_sale(),
					'featured'     => $base->is_featured(),
					'min_rating'   => $base->get_min_rating(),
					'min_price'    => $base->get_min_price(),
					'max_price'    => $base->get_max_price(),
				)
			)
		);
	}

	/**
	 * @param SearchQuery $base Base query.
	 * @param float       $min_rating Minimum rating.
	 * @return int
	 */
	private function query_with_rating( SearchQuery $base, float $min_rating ): int {
		return ProductQueryBuilder::count(
			new SearchQuery(
				array(
					'keyword'      => $base->get_keyword(),
					'product_cats' => $base->get_product_cats(),
					'product_tags' => $base->get_product_tags(),
					'attributes'   => $base->get_attributes(),
					'taxonomies'   => $base->get_taxonomies(),
					'stock_status' => $base->get_stock_status(),
					'on_sale'      => $base->is_on_sale(),
					'featured'     => $base->is_featured(),
					'min_rating'   => $min_rating,
					'min_price'    => $base->get_min_price(),
					'max_price'    => $base->get_max_price(),
				)
			)
		);
	}

	/**
	 * @param SearchQuery        $base Base query.
	 * @param string             $taxonomy Taxonomy slug.
	 * @param array<int, string> $slugs Term slugs.
	 * @return int
	 */
	private function query_with_taxonomy( SearchQuery $base, string $taxonomy, array $slugs ): int {
		$taxonomies             = $base->get_taxonomies();
		$taxonomies[ $taxonomy ] = $slugs;

		return ProductQueryBuilder::count(
			new SearchQuery(
				array(
					'keyword'      => $base->get_keyword(),
					'product_cats' => $base->get_product_cats(),
					'product_tags' => $base->get_product_tags(),
					'attributes'   => $base->get_attributes(),
					'taxonomies'   => $taxonomies,
					'stock_status' => $base->get_stock_status(),
					'on_sale'      => $base->is_on_sale(),
					'featured'     => $base->is_featured(),
					'min_rating'   => $base->get_min_rating(),
					'min_price'    => $base->get_min_price(),
					'max_price'    => $base->get_max_price(),
				)
			)
		);
	}

	/**
	 * @param string $taxonomy Taxonomy name.
	 * @return array<int, \WP_Term>
	 */
	private function get_terms( string $taxonomy ): array {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			)
		);

		return is_wp_error( $terms ) ? array() : $terms;
	}

	/**
	 * @param array<int, \WP_Term> $terms Terms.
	 * @return array<int, array<string, mixed>>
	 */
	private function format_terms( array $terms ): array {
		$items = array();
		foreach ( $terms as $term ) {
			$items[] = array(
				'id'    => (int) $term->term_id,
				'slug'  => $term->slug,
				'name'  => $term->name,
				'count' => ProductQueryBuilder::count_for_term( $term ),
			);
		}
		return $items;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function get_all_attributes(): array {
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return array();
		}

		$items      = array();
		$taxonomies = wc_get_attribute_taxonomies();

		foreach ( $taxonomies as $attribute ) {
			$taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$terms = $this->get_terms( $taxonomy );
			if ( empty( $terms ) ) {
				continue;
			}

			$term_items = array();
			foreach ( $terms as $term ) {
				$term_items[] = array(
					'slug'  => $term->slug,
					'name'  => $term->name,
					'count' => ProductQueryBuilder::count_for_term( $term ),
				);
			}

			$items[] = array(
				'slug'  => $attribute->attribute_name,
				'label' => $attribute->attribute_label,
				'terms' => $term_items,
			);
		}

		return $items;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function get_all_ratings(): array {
		if ( ! function_exists( 'beplus_smart_search_get_rating_filter_options' ) ) {
			return array();
		}

		$items = array();

		foreach ( beplus_smart_search_get_rating_filter_options() as $option ) {
			$items[] = array(
				'slug'  => (string) $option['value'],
				'name'  => (string) $option['label'],
				'count' => $this->query_with_rating( new SearchQuery(), (float) $option['value'] ),
			);
		}

		return $items;
	}

	/**
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	private function get_all_taxonomy_facets(): array {
		if ( ! function_exists( 'beplus_smart_search_get_configured_filter_taxonomies' ) ) {
			return array();
		}

		$items = array();

		foreach ( beplus_smart_search_get_configured_filter_taxonomies() as $taxonomy ) {
			$terms = $this->format_terms( $this->get_terms( $taxonomy ) );
			if ( ! empty( $terms ) ) {
				$items[ $taxonomy ] = $terms;
			}
		}

		return $items;
	}
}
