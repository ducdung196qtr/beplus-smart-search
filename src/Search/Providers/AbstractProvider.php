<?php

/**
 * Abstract search provider.
 *
 * @package BePlusSmartSearch
 * @subpackage Search
 */

namespace BePlusSmartSearch\Search\Providers;

use BePlusSmartSearch\Search\SearchQuery;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for search providers.
 */
abstract class AbstractProvider {

	/**
	 * Provider identifier.
	 *
	 * @return string
	 */
	abstract public function get_id(): string;

	/**
	 * Whether this provider is available.
	 *
	 * @return bool
	 */
	abstract public function is_enabled(): bool;

	/**
	 * Execute search.
	 *
	 * @param SearchQuery $query Search query.
	 *
	 * @return array{items: array<int, array<string, mixed>>, total: int, totalPages: int, page: int, perPage: int}
	 */
	abstract public function search( SearchQuery $query ): array;
}
