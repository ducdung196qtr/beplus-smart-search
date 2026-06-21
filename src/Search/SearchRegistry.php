<?php

/**
 * Search provider registry.
 *
 * @package BePlusSmartSearch
 * @subpackage Search
 */

namespace BePlusSmartSearch\Search;

use BePlusSmartSearch\Core\AbstractModule;
use BePlusSmartSearch\Core\HookManager;
use BePlusSmartSearch\Search\Providers\AbstractProvider;
use BePlusSmartSearch\Search\Providers\ProductProvider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and resolves search providers.
 */
class SearchRegistry extends AbstractModule {

	/**
	 * Registered providers.
	 *
	 * @var array<string, AbstractProvider>
	 */
	private array $providers = array();

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->register_default_providers();
	}

	/**
	 * Register built-in providers.
	 *
	 * @return void
	 */
	private function register_default_providers(): void {
		$this->add_provider( new ProductProvider() );

		$extra = apply_filters( HookManager::FILTER_PROVIDERS, array() );

		if ( is_array( $extra ) ) {
			foreach ( $extra as $provider ) {
				if ( $provider instanceof AbstractProvider ) {
					$this->add_provider( $provider );
				}
			}
		}
	}

	/**
	 * Add a provider.
	 *
	 * @param AbstractProvider $provider Provider instance.
	 *
	 * @return void
	 */
	public function add_provider( AbstractProvider $provider ): void {
		$this->providers[ $provider->get_id() ] = $provider;
	}

	/**
	 * Get provider by ID.
	 *
	 * @param string $id Provider ID.
	 *
	 * @return AbstractProvider|null
	 */
	public function get_provider( string $id ): ?AbstractProvider {
		return isset( $this->providers[ $id ] ) ? $this->providers[ $id ] : null;
	}

	/**
	 * Get all enabled providers.
	 *
	 * @return array<string, AbstractProvider>
	 */
	public function get_enabled_providers(): array {
		return array_filter(
			$this->providers,
			function ( AbstractProvider $provider ) {
				return $provider->is_enabled();
			},
		);
	}
}
