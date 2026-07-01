<?php

/**
 * Suggestions REST controller.
 *
 * @package BePlusFastProductFilterLiveSearch
 * @subpackage REST
 */

namespace BePlusFastProductFilterLiveSearch\REST;

use BePlusFastProductFilterLiveSearch\Core\AbstractModule;
use BePlusFastProductFilterLiveSearch\Search\SuggestionService;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST endpoint for search suggestions.
 */
class SuggestionsController extends AbstractModule {

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
			'/suggestions',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_collection_params(),
			),
		);
	}

	/**
	 * Handle GET /suggestions.
	 *
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( WP_REST_Request $request ): WP_REST_Response {
		$keyword = sanitize_text_field( (string) $request->get_param( 's' ) );
		$limit   = max( 1, min( 10, (int) $request->get_param( 'limit' ) ) );

		$service = new SuggestionService();
		$result  = $service->get_suggestions(
			$keyword,
			$limit,
			array(
				'product_cat'     => $request->get_param( 'product_cat' ),
				'search_logic'    => sanitize_key( (string) $request->get_param( 'search_logic' ) ),
				'exact_match'     => (bool) $request->get_param( 'exact_match' ),
				'misspelling_fix' => (bool) $request->get_param( 'misspelling_fix' ),
				'search_fields'   => $request->get_param( 'search_fields' ),
			),
		);

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Collection params schema.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_collection_params(): array {
		return array(
			's' => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'limit' => array(
				'type'    => 'integer',
				'default' => 5,
				'minimum' => 1,
				'maximum' => 10,
			),
			'product_cat' => array(
				'type'              => array( 'string', 'array' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'exact_match' => array(
				'type' => 'boolean',
			),
			'search_logic' => array(
				'type'              => 'string',
				'default'           => 'or',
				'sanitize_callback' => 'sanitize_key',
			),
			'misspelling_fix' => array(
				'type' => 'boolean',
			),
			'search_fields' => array(
				'type'              => array( 'string', 'array' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}
}
