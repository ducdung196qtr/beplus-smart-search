<?php

/**
 * Products REST controller.
 *
 * @package BePlusFastProductFilterLiveSearch
 * @subpackage REST
 */

namespace BePlusFastProductFilterLiveSearch\REST;

use BePlusFastProductFilterLiveSearch\Core\AbstractModule;
use BePlusFastProductFilterLiveSearch\Search\SearchEngine;
use BePlusFastProductFilterLiveSearch\Search\SearchFieldsFilter;
use BePlusFastProductFilterLiveSearch\Search\SearchQuery;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST endpoint for product search.
 */
class ProductsController extends AbstractModule {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	private string $namespace = 'beplus-fast-product-filter-live-search-for-woocommerce/v1';

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes on rest_api_init.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/products',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_collection_params(),
			),
		);
	}

	/**
	 * Handle GET /products.
	 *
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( WP_REST_Request $request ): WP_REST_Response {
		$query  = SearchQuery::from_rest_request( $request );
		$engine = $this->container->get( SearchEngine::class );

		if ( ! $engine instanceof SearchEngine ) {
			return new WP_REST_Response(
				array(
					'items'      => array(),
					'total'      => 0,
					'totalPages' => 0,
					'page'       => $query->get_page(),
					'perPage'    => $query->get_per_page(),
				),
				200,
			);
		}

		$result = $engine->search( $query );

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Collection params schema.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_collection_params(): array {
		$params = array(
			's'             => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'product_cat'   => array(
				'type'              => array( 'string', 'array' ),
				'sanitize_callback' => array( $this, 'sanitize_terms_param' ),
			),
			'product_tag'   => array(
				'type'              => array( 'string', 'array' ),
				'sanitize_callback' => array( $this, 'sanitize_terms_param' ),
			),
			'attribute'     => array(
				'type' => 'object',
			),
			'min_price'     => array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_price_param' ),
			),
			'max_price'     => array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_price_param' ),
			),
			'stock_status'  => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
			),
			'on_sale'       => array(
				'type' => 'boolean',
			),
			'featured'      => array(
				'type' => 'boolean',
			),
			'min_rating'    => array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_rating_param' ),
			),
			'page'          => array(
				'type'    => 'integer',
				'default' => 1,
				'minimum' => 1,
			),
			'per_page'      => array(
				'type'    => 'integer',
				'default' => 10,
				'minimum' => 1,
				'maximum' => 50,
			),
			'orderby'       => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'order'         => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
				'default'           => 'asc',
			),
			'exact_match'   => array(
				'type' => 'boolean',
			),
			'search_logic'  => array(
				'type'              => 'string',
				'default'           => 'or',
				'sanitize_callback' => 'sanitize_key',
			),
			'misspelling_fix' => array(
				'type' => 'boolean',
			),
			'search_fields' => array(
				'type'              => array( 'string', 'array' ),
				'sanitize_callback' => array( $this, 'sanitize_search_fields_param' ),
			),
		);

		if ( function_exists( 'beplus_fast_product_filter_live_search_get_configured_filter_taxonomies' ) ) {
			foreach ( beplus_fast_product_filter_live_search_get_configured_filter_taxonomies() as $taxonomy ) {
				$params[ $taxonomy ] = array(
					'type'              => array( 'string', 'array' ),
					'sanitize_callback' => array( $this, 'sanitize_terms_param' ),
				);
			}
		}

		return $params;
	}

	/**
	 * Sanitize taxonomy REST param.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return array<int, string>|string
	 */
	public function sanitize_terms_param( $value ) {
		if ( is_array( $value ) ) {
			return array_values( array_filter( array_map( 'sanitize_text_field', $value ) ) );
		}

		return sanitize_text_field( (string) $value );
	}

	/**
	 * Sanitize price REST param (WP passes 3 args; floatval accepts only 1).
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return float
	 */
	public function sanitize_price_param( $value ): float {
		return max( 0, (float) $value );
	}

	/**
	 * Sanitize rating REST param.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return float
	 */
	public function sanitize_rating_param( $value ): float {
		return max( 0, min( 5, (float) $value ) );
	}

	/**
	 * Sanitize search_fields REST param.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return array<int, string>|string
	 */
	public function sanitize_search_fields_param( $value ) {
		if ( is_array( $value ) ) {
			return SearchFieldsFilter::normalize_fields( $value );
		}

		return sanitize_text_field( (string) $value );
	}
}
