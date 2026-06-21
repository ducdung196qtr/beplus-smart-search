<?php

/**
 * Align WooCommerce catalog per-page with plugin settings when search block is present.
 *
 * @package BePlusSmartSearch
 * @subpackage Frontend
 */

namespace BePlusSmartSearch\Frontend;

use BePlusSmartSearch\Core\AbstractModule;
use WP_Block;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Overrides product archive query limits when Advanced Woo Search block is on the page.
 */
final class ShopQueryIntegration extends AbstractModule {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'loop_shop_per_page', array( $this, 'filter_loop_shop_per_page' ), 20 );
		add_filter( 'query_loop_block_query_vars', array( $this, 'filter_query_loop_block' ), 20, 3 );
		add_action( 'pre_get_posts', array( $this, 'filter_main_product_query' ), 20 );
		add_filter( 'render_block_data', array( $this, 'filter_product_collection_block' ), 20, 1 );
		add_action( 'template_redirect', array( $this, 'redirect_legacy_keyword_param' ), 0 );
	}

	/**
	 * WordPress treats ?s= as site search and may redirect (e.g. to a single product).
	 * Use bpss_s in URLs instead; migrate old links before core handles ?s=.
	 *
	 * @return void
	 */
	public function redirect_legacy_keyword_param(): void {
		if ( is_admin() || ! $this->should_override_per_page() ) {
			return;
		}

		if ( ! $this->is_product_catalog_view() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['s'] ) || ! empty( $_GET['bpss_s'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$keyword = sanitize_text_field( wp_unslash( (string) $_GET['s'] ) );
		if ( '' === $keyword ) {
			return;
		}

		$target = remove_query_arg( 's' );
		$target = add_query_arg( 'bpss_s', $keyword, $target );

		wp_safe_redirect( $target, 302 );
		exit;
	}

	/**
	 * Whether the current front-end request is a WooCommerce catalog view.
	 *
	 * @return bool
	 */
	private function is_product_catalog_view(): bool {
		if ( function_exists( 'is_shop' ) && is_shop() ) {
			return true;
		}

		return function_exists( 'is_product_taxonomy' ) && is_product_taxonomy();
	}

	/**
	 * Override WooCommerce loop size (inherit product collection uses main query).
	 *
	 * @param int|string $per_page Current per page.
	 *
	 * @return int
	 */
	public function filter_loop_shop_per_page( $per_page ): int {
		if ( ! $this->should_override_per_page() ) {
			return (int) $per_page;
		}

		return beplus_smart_search_get_per_page();
	}

	/**
	 * Override product collection block query vars.
	 *
	 * @param array<string, mixed> $query Query vars.
	 * @param WP_Block             $block Block instance.
	 * @param int                  $page  Page number.
	 *
	 * @return array<string, mixed>
	 */
	public function filter_query_loop_block( array $query, WP_Block $block, int $page ): array {
		unset( $page );

		if ( ! $this->should_override_per_page() ) {
			return $query;
		}

		$block_query = $block->context['query'] ?? array();

		if ( empty( $block_query['isProductCollectionBlock'] ) ) {
			return $query;
		}

		if ( ( $block_query['postType'] ?? '' ) !== 'product' ) {
			return $query;
		}

		$query['posts_per_page'] = beplus_smart_search_get_per_page();

		return $query;
	}

	/**
	 * Ensure main product archive query matches plugin per page.
	 *
	 * @param WP_Query $query Query instance.
	 *
	 * @return void
	 */
	public function filter_main_product_query( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! $this->should_override_per_page() ) {
			return;
		}

		if ( ! $query->is_post_type_archive( 'product' ) && ! $query->is_tax( get_object_taxonomies( 'product' ) ) ) {
			return;
		}

		$query->set( 'posts_per_page', beplus_smart_search_get_per_page() );
	}

	/**
	 * Ensure product collection block query uses plugin per-page setting.
	 *
	 * @param array<string, mixed> $parsed_block Parsed block data.
	 *
	 * @return array<string, mixed>
	 */
	public function filter_product_collection_block( array $parsed_block ): array {
		if ( ! $this->should_override_per_page() ) {
			return $parsed_block;
		}

		if ( ( $parsed_block['blockName'] ?? '' ) !== 'woocommerce/product-collection' ) {
			return $parsed_block;
		}

		if ( empty( $parsed_block['attrs']['query']['isProductCollectionBlock'] ) ) {
			return $parsed_block;
		}

		if ( ! isset( $parsed_block['attrs']['query'] ) || ! is_array( $parsed_block['attrs']['query'] ) ) {
			$parsed_block['attrs']['query'] = array();
		}

		$parsed_block['attrs']['query']['perPage'] = beplus_smart_search_get_per_page();

		return $parsed_block;
	}

	/**
	 * Whether the current view includes our search block.
	 *
	 * @return bool
	 */
	private function should_override_per_page(): bool {
		return beplus_smart_search_page_has_search_block();
	}
}
