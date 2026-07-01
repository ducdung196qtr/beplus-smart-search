<?php

/**
 * Centralized asset registration.
 *
 * @package BePlusFastProductFilterLiveSearch
 * @subpackage Core
 */

namespace BePlusFastProductFilterLiveSearch\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles script and style enqueuing with localized data.
 */
class AssetLoader extends AbstractModule {

	/**
	 * Register enqueuing hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_scripts' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor' ) );
	}

	/**
	 * Register script handles used as block dependencies.
	 *
	 * @return void
	 */
	public function register_scripts(): void {
		wp_register_script(
			'bpss-data',
			false,
			array(),
			$this->version,
			true,
		);
	}

	/**
	 * Enqueue shared frontend data for block view scripts.
	 *
	 * @return void
	 */
	public function enqueue_frontend(): void {
		if ( ! $this->should_localize() ) {
			return;
		}

		wp_register_script(
			'bpss-data',
			false,
			array(),
			$this->version,
			true,
		);

		wp_enqueue_script( 'bpss-data' );
		wp_localize_script( 'bpss-data', 'bpssData', $this->get_localized_data() );
	}

	/**
	 * Enqueue localized data in block editor and frontend.
	 *
	 * @return void
	 */
	public function enqueue_block_assets(): void {
		if ( ! wp_script_is( 'bpss-data', 'registered' ) ) {
			$this->register_scripts();
		}

		if ( ! wp_script_is( 'bpss-data', 'enqueued' ) ) {
			wp_enqueue_script( 'bpss-data' );
			wp_localize_script( 'bpss-data', 'bpssData', $this->get_localized_data() );
		}
	}

	/**
	 * Ensure bpssData is available in the block editor.
	 *
	 * @return void
	 */
	public function enqueue_editor(): void {
		$this->enqueue_block_assets();
	}

	/**
	 * Whether to output localized data.
	 *
	 * @return bool
	 */
	private function should_localize(): bool {
		return ! is_admin() || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() );
	}

	/**
	 * Data passed to view scripts.
	 *
	 * @return array<string, mixed>
	 */
	private function get_localized_data(): array {
		$data = array(
			'restUrl' => esc_url_raw( rest_url( 'beplus-fast-product-filter-live-search-for-woocommerce/v1/' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'shopUrl' => esc_url_raw( beplus_fast_product_filter_live_search_get_catalog_search_base_url() ),
			'facetDisplayMode' => beplus_fast_product_filter_live_search_get_facet_display_mode(),
			'filterSections'   => beplus_fast_product_filter_live_search_get_filter_section_catalog(),
			'attributeDefinitions' => beplus_fast_product_filter_live_search_get_all_attribute_definitions(),
			'productCategories'    => beplus_fast_product_filter_live_search_get_product_category_definitions(),
			'i18n'    => array(
				'searching'    => __( 'Searching…', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'noResults'    => __( 'No products found.', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				/* translators: %d: number of products found */
				'resultsFound' => __( '%d products found', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'error'        => __( 'Search failed. Please try again.', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'cleared'      => __( 'Filters cleared.', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				/* translators: %d: number of results */
				'showingResults' => __( 'Showing %d results', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'showingResult'  => __( 'Showing 1 result', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'clearAllFilters' => __( 'Clear all filters', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'removeFilter'   => __( 'Remove filter', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'activeFilters' => __( 'Active filters', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'search'       => __( 'Search', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'category'     => __( 'Category', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'tag'          => __( 'Tag', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'price'        => __( 'Price', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'stock'        => __( 'Stock', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'onSale'       => __( 'On sale', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'featured'     => __( 'Featured', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'rating'       => __( 'Rating', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'brand'        => __( 'Brand', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'addToCart'    => __( 'Add to cart', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'viewProduct'  => __( 'View product', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'added'        => __( 'Added', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'addedToCart'  => __( 'Added to cart', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				'viewAll'      => __( 'View All Results', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
			),
		);

		if ( class_exists( 'WC_AJAX' ) ) {
			$data['wcAjaxUrl'] = esc_url_raw( \WC_AJAX::get_endpoint( '%%endpoint%%' ) );
		}

		return $data;
	}
}
