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
	 * Block name to detect.
	 */
	private const BLOCK_NAME = 'beplus-smart-search/advanced-woo-search';

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
	}

	/**
	 * Override WooCommerce loop size (inherit product collection uses main query).
	 *
	 * @param int|string $per_page Current per page.
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
