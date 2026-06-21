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
			true
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
			true
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
		return array(
			'restUrl' => esc_url_raw( rest_url( 'beplus-smart-search/v1/' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'shopUrl' => function_exists( 'wc_get_page_permalink' )
				? esc_url_raw( wc_get_page_permalink( 'shop' ) )
				: home_url( '/' ),
			'facetDisplayMode' => beplus_smart_search_get_facet_display_mode(),
			'filterSections'   => beplus_smart_search_get_filter_section_catalog(),
			'attributeDefinitions' => beplus_smart_search_get_all_attribute_definitions(),
			'i18n'    => array(
				'searching'    => __( 'Searching…', 'beplus-smart-search' ),
				'noResults'    => __( 'No products found.', 'beplus-smart-search' ),
				'resultsFound' => __( '%d products found', 'beplus-smart-search' ),
				'error'        => __( 'Search failed. Please try again.', 'beplus-smart-search' ),
				'cleared'      => __( 'Filters cleared.', 'beplus-smart-search' ),
			),
		);
	}
}
