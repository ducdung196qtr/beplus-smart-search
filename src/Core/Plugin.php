<?php

/**
 * Main plugin bootstrap.
 *
 * @package BePlusSmartSearch
 * @subpackage Core
 */

namespace BePlusSmartSearch\Core;

use BePlusSmartSearch\Admin\SettingsPage;
use BePlusSmartSearch\Blocks\BlockRegistry;
use BePlusSmartSearch\Frontend\ShopQueryIntegration;
use BePlusSmartSearch\REST\FacetsController;
use BePlusSmartSearch\REST\ProductsController;
use BePlusSmartSearch\REST\SuggestionsController;
use BePlusSmartSearch\Search\CacheService;
use BePlusSmartSearch\Search\ProductTemplateRenderer;
use BePlusSmartSearch\Search\SearchEngine;
use BePlusSmartSearch\Search\SearchRegistry;
use BePlusSmartSearch\Settings\SettingsRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core plugin class.
 */
class Plugin {

	/**
	 * Service container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Registered module class names.
	 *
	 * @var array<int, class-string<AbstractModule>>
	 */
	private array $modules = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->container = new Container();
	}

	/**
	 * Boot the plugin.
	 *
	 * @return void
	 */
	public function boot(): void {
		$this->register_core_services();
		$this->register_services_from_filter();
		$this->boot_registered_modules();

		add_action( 'init', array( $this, 'on_init' ) );
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ) );
	}

	/**
	 * Run on init.
	 *
	 * @return void
	 */
	public function on_init(): void {
		ProductTemplateRenderer::register();
		CacheService::register_invalidation_hooks();
	}

	/**
	 * Activate the plugin.
	 *
	 * @return void
	 */
	public function activate(): void {
		flush_rewrite_rules();
	}

	/**
	 * Deactivate the plugin.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		flush_rewrite_rules();
	}

	/**
	 * Register block category.
	 *
	 * @param array<int, array<string, mixed>> $categories Block categories.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function register_block_category( array $categories ): array {
		$categories[] = array(
			'slug'  => 'beplus-smart-search',
			'title' => __( 'Beplus Smart Search', 'beplus-smart-search' ),
			'icon'  => 'search',
		);

		return $categories;
	}

	/**
	 * Register core container services.
	 *
	 * @return void
	 */
	private function register_core_services(): void {
		$this->container->set(
			SearchEngine::class,
			function ( Container $container ) {
				return new SearchEngine( $container );
			},
		);

		$this->modules = array(
			AssetLoader::class,
			SettingsRegistry::class,
			SettingsPage::class,
			BlockRegistry::class,
			ShopQueryIntegration::class,
			SearchRegistry::class,
			ProductsController::class,
			FacetsController::class,
			SuggestionsController::class,
		);
	}

	/**
	 * Allow extensions to register services.
	 *
	 * @return void
	 */
	private function register_services_from_filter(): void {
		$extra = apply_filters( 'beplus_smart_search.services', array() );

		if ( ! is_array( $extra ) ) {
			return;
		}

		foreach ( $extra as $id => $definition ) {
			if ( is_string( $id ) ) {
				$this->container->set( $id, $definition );
			}
		}

		$extra_modules = apply_filters( 'beplus_smart_search.modules', array() );

		if ( is_array( $extra_modules ) ) {
			foreach ( $extra_modules as $module_class ) {
				if ( is_string( $module_class ) && is_subclass_of( $module_class, AbstractModule::class ) ) {
					$this->modules[] = $module_class;
				}
			}
		}
	}

	/**
	 * Boot all registered modules.
	 *
	 * @return void
	 */
	private function boot_registered_modules(): void {
		foreach ( $this->modules as $module_class ) {
			$module = $this->container->get( $module_class );
			if ( $module instanceof AbstractModule ) {
				$module->register();
			}
		}
	}
}
