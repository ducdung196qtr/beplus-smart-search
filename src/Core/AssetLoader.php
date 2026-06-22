<?php

/**
 * Centralized asset registration.
 *
 * @package BePlusSmartSearch
 * @subpackage Core
 */

namespace BePlusSmartSearch\Core;

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
			'restUrl' => esc_url_raw( rest_url( 'beplus-smart-search/v1/' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'shopUrl' => esc_url_raw( beplus_smart_search_get_catalog_search_base_url() ),
			'facetDisplayMode' => beplus_smart_search_get_facet_display_mode(),
			'filterSections'   => beplus_smart_search_get_filter_section_catalog(),
			'attributeDefinitions' => beplus_smart_search_get_all_attribute_definitions(),
			'productCategories'    => beplus_smart_search_get_product_category_definitions(),
			'i18n'    => array(
				'searching'    => __( 'Searching…', 'beplus-smart-search' ),
				'noResults'    => __( 'No products found.', 'beplus-smart-search' ),
				/* translators: %d: number of products found */
				'resultsFound' => __( '%d products found', 'beplus-smart-search' ),
				'error'        => __( 'Search failed. Please try again.', 'beplus-smart-search' ),
				'cleared'      => __( 'Filters cleared.', 'beplus-smart-search' ),
				'showingResults' => __( 'Showing %d results', 'beplus-smart-search' ),
				'showingResult'  => __( 'Showing 1 result', 'beplus-smart-search' ),
				'clearAllFilters' => __( 'Clear all filters', 'beplus-smart-search' ),
				'removeFilter'   => __( 'Remove filter', 'beplus-smart-search' ),
				'activeFilters' => __( 'Active filters', 'beplus-smart-search' ),
				'search'       => __( 'Search', 'beplus-smart-search' ),
				'category'     => __( 'Category', 'beplus-smart-search' ),
				'tag'          => __( 'Tag', 'beplus-smart-search' ),
				'price'        => __( 'Price', 'beplus-smart-search' ),
				'stock'        => __( 'Stock', 'beplus-smart-search' ),
				'onSale'       => __( 'On sale', 'beplus-smart-search' ),
				'featured'     => __( 'Featured', 'beplus-smart-search' ),
				'rating'       => __( 'Rating', 'beplus-smart-search' ),
				'brand'        => __( 'Brand', 'beplus-smart-search' ),
				'addToCart'    => __( 'Add to cart', 'beplus-smart-search' ),
				'viewProduct'  => __( 'View product', 'beplus-smart-search' ),
				'added'        => __( 'Added', 'beplus-smart-search' ),
				'addedToCart'  => __( 'Added to cart', 'beplus-smart-search' ),
				'viewAll'      => __( 'View All Results', 'beplus-smart-search' ),
			),
		);

		if ( class_exists( 'WC_AJAX' ) ) {
			$data['wcAjaxUrl'] = esc_url_raw( \WC_AJAX::get_endpoint( '%%endpoint%%' ) );
		}

		return $data;
	}
}
