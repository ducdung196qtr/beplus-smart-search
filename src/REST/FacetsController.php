<?php

/**
 * Facets REST controller.
 *
 * @package BePlusSmartSearch
 * @subpackage REST
 */

namespace BePlusSmartSearch\REST;

use BePlusSmartSearch\Core\AbstractModule;
use BePlusSmartSearch\Search\FacetService;
use BePlusSmartSearch\Search\SearchQuery;
use BePlusSmartSearch\Settings\SettingsRegistry;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST endpoint for filter facets (categories, tags, attributes).
 */
class FacetsController extends AbstractModule {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	private string $namespace = 'beplus-smart-search/v1';

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	private string $cache_group = 'beplus_smart_search';

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/facets',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_facets' ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_route_args(),
			),
		);
	}

	/**
	 * Route arguments (same filters as products search).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_route_args(): array {
		return array(
			'mode'          => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
			),
			's'             => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'product_cat'   => array(
				'type' => array( 'string', 'array' ),
			),
			'product_tag'   => array(
				'type' => array( 'string', 'array' ),
			),
			'attribute'     => array(
				'type' => 'object',
			),
			'stock_status'  => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
			),
			'on_sale'       => array(
				'type' => 'boolean',
			),
			'min_price'     => array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_price_param' ),
			),
			'max_price'     => array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_price_param' ),
			),
		);
	}

	/**
	 * Handle GET /facets.
	 *
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_facets( WP_REST_Request $request ): WP_REST_Response {
		$registry = new SettingsRegistry( $this->container );
		$settings = $registry->get_settings();
		$sidebar  = isset( $settings['sidebar'] ) && is_array( $settings['sidebar'] ) ? $settings['sidebar'] : array();

		$default_mode = $sidebar['facet_display_mode'] ?? 'all';
		$default_mode = in_array( $default_mode, array( 'all', 'contextual' ), true ) ? $default_mode : 'all';
		$mode         = $request->get_param( 'mode' ) ?: $default_mode;
		$mode         = in_array( $mode, array( 'all', 'contextual' ), true ) ? $mode : 'all';

		// Storefront "Show all options" must not be overridden by client requests.
		if ( 'all' === $default_mode ) {
			$mode = 'all';
		}

		$query   = SearchQuery::from_rest_request( $request );
		$service = new FacetService();

		if ( 'contextual' === $mode && $this->has_active_filters( $query ) ) {
			$data = $service->get_facets( $query, 'contextual' );
			return new WP_REST_Response( $data, 200 );
		}

		if ( 'all' === $mode && ! empty( $settings['enable_cache'] ) && ! $this->has_active_filters( $query ) ) {
			$cached = wp_cache_get( 'bpss_facets_all', $this->cache_group );
			if ( false !== $cached && is_array( $cached ) ) {
				return new WP_REST_Response( $cached, 200 );
			}
		}

		$data = $service->get_facets( $query, 'all' );

		if ( 'all' === $mode && ! $this->has_active_filters( $query ) && ! empty( $settings['enable_cache'] ) ) {
			wp_cache_set( 'bpss_facets_all', $data, $this->cache_group, HOUR_IN_SECONDS );
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Whether request includes active product filters.
	 *
	 * @param SearchQuery $query Query.
	 *
	 * @return bool
	 */
	private function has_active_filters( SearchQuery $query ): bool {
		return $query->get_keyword() !== ''
			|| ! empty( $query->get_product_cats() )
			|| ! empty( $query->get_product_tags() )
			|| ! empty( $query->get_attributes() )
			|| ! empty( $query->get_taxonomies() )
			|| $query->get_stock_status() !== ''
			|| $query->is_on_sale()
			|| $query->is_featured()
			|| $query->get_min_rating() > 0
			|| $query->get_min_price() > 0
			|| $query->get_max_price() > 0;
	}

	/**
	 * Sanitize price REST param.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return float
	 */
	public function sanitize_price_param( $value ): float {
		return max( 0, (float) $value );
	}
}
