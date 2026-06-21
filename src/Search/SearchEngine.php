<?php

/**
 * Search engine orchestrator.
 *
 * @package BePlusSmartSearch
 * @subpackage Search
 */

namespace BePlusSmartSearch\Search;

use BePlusSmartSearch\Core\Container;
use BePlusSmartSearch\Core\HookManager;
use BePlusSmartSearch\Search\Providers\AbstractProvider;
use BePlusSmartSearch\Search\Providers\ProductProvider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Delegates search to registered providers.
 */
class SearchEngine {

	/**
	 * Service container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container Service container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Search products.
	 *
	 * @param SearchQuery $query Search query.
	 *
	 * @return array{items: array<int, array<string, mixed>>, total: int, totalPages: int, page: int, perPage: int}
	 */
	public function search( SearchQuery $query ): array {
		$query = apply_filters( HookManager::FILTER_SEARCH_QUERY, $query );

		$provider = $this->get_provider( 'product' );

		if ( ! $provider || ! $provider->is_enabled() ) {
			return array(
				'items'      => array(),
				'total'      => 0,
				'totalPages' => 0,
				'page'       => $query->get_page(),
				'perPage'    => $query->get_per_page(),
			);
		}

		$result = $provider->search( $query );

		$result['items'] = apply_filters( HookManager::FILTER_SEARCH_RESULTS, $result['items'], $query );

		do_action( HookManager::ACTION_SEARCH_COMPLETED, $query, $result['items'] );

		return $result;
	}

	/**
	 * Get provider by ID.
	 *
	 * @param string $id Provider ID.
	 *
	 * @return AbstractProvider|null
	 */
	private function get_provider( string $id ): ?AbstractProvider {
		$registry = $this->container->get( SearchRegistry::class );

		if ( ! $registry instanceof SearchRegistry ) {
			return new ProductProvider();
		}

		return $registry->get_provider( $id );
	}
}
