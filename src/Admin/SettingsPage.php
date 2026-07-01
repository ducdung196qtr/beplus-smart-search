<?php

/**
 * Admin settings page.
 *
 * @package BePlusFastProductFilterLiveSearch
 * @subpackage Admin
 */

namespace BePlusFastProductFilterLiveSearch\Admin;

use BePlusFastProductFilterLiveSearch\Core\AbstractModule;
use BePlusFastProductFilterLiveSearch\Search\CacheService;
use BePlusFastProductFilterLiveSearch\Settings\SettingsRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers admin menu and renders search settings.
 */
class SettingsPage extends AbstractModule {

	/**
	 * Menu slug.
	 *
	 * @var string
	 */
	public const MENU_SLUG = 'beplus-fast-product-filter-live-search-for-woocommerce';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'wp_redirect', array( $this, 'preserve_settings_tab' ), 10, 2 );
		add_action( 'wp_ajax_bpss_clear_cache', array( $this, 'ajax_clear_cache' ) );
		add_action( 'wp_ajax_bpss_benchmark_cache', array( $this, 'ajax_benchmark_cache' ) );
	}

	/**
	 * Keep active settings tab after save.
	 *
	 * @param string $location Redirect URL.
	 * @param int    $status   HTTP status.
	 *
	 * @return string
	 */
	public function preserve_settings_tab( string $location, int $status ): string {
		unset( $status );

		if ( false === strpos( $location, 'page=' . self::MENU_SLUG ) ) {
			return $location;
		}

		if ( empty( $_POST['bpss_active_tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $location;
		}

		$tab = sanitize_key( wp_unslash( $_POST['bpss_active_tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$allowed = array( 'general', 'filters' );

		if ( ! in_array( $tab, $allowed, true ) ) {
			// Legacy tab slugs merged into Filters.
			if ( in_array( $tab, array( 'sidebar', 'taxonomies', 'price' ), true ) ) {
				$tab = 'filters';
			} else {
				return $location;
			}
		}

		return add_query_arg( 'tab', $tab, $location );
	}

	/**
	 * Add settings under WooCommerce admin menu.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		if ( ! beplus_fast_product_filter_live_search_is_woocommerce_active() ) {
			return;
		}

		add_submenu_page(
			'woocommerce',
			__( 'Advanced Search', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
			__( 'Advanced Search', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
			'manage_woocommerce',
			self::MENU_SLUG,
			array( $this, 'render_page' ),
		);
	}

	/**
	 * Enqueue admin styles on settings page only.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( false === strpos( $hook_suffix, self::MENU_SLUG ) ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_style(
			'bpss-admin-settings',
			$this->plugin_url . 'admin/css/settings.css',
			array(),
			$this->version,
		);

		$asset_file = $this->plugin_dir . 'admin/js/settings.asset.php';
		$asset      = file_exists( $asset_file ) ? require $asset_file : array(
			'dependencies' => array( 'jquery', 'wp-color-picker' ),
			'version'      => $this->version,
		);

		wp_enqueue_script(
			'bpss-admin-settings',
			$this->plugin_url . 'admin/js/settings.js',
			$asset['dependencies'],
			$asset['version'],
			true,
		);

		$last_cleared = CacheService::get_last_cleared_timestamp();
		$benchmark    = CacheService::get_benchmark_stats();

		wp_localize_script(
			'bpss-admin-settings',
			'bpssAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'bpss_clear_cache' ),
				'i18n'    => array(
					'clearing'       => __( 'Clearing cache…', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
					'cleared'        => __( 'Cache cleared successfully.', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
					'clearError'     => __( 'Could not clear cache. Please try again.', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
					'lastCleared'    => __( 'Last cleared:', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
					'neverCleared'   => __( 'Cache has not been cleared manually yet.', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
					'on'             => __( 'On', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
					'off'            => __( 'Off', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
					'measuring'      => __( 'Measuring…', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
					'measureError'   => __( 'Could not measure performance. Please try again.', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
					'measureNow'     => __( 'Measure now', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
					'noBenchmark'    => __( 'No measurement yet. Run a quick test to compare facet load time with and without cache.', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
					'coldLabel'      => __( 'Without cache', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
					'warmLabel'      => __( 'With cache', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
					'savedLabel'     => __( 'Estimated saving', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
					'measuredAt'     => __( 'Measured:', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
				),
				'lastCleared' => $last_cleared,
				'benchmark'   => $benchmark,
			),
		);
	}

	/**
	 * AJAX: measure facet load performance.
	 *
	 * @return void
	 */
	public function ajax_benchmark_cache(): void {
		check_ajax_referer( 'bpss_clear_cache', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied.', 'beplus-fast-product-filter-live-search-for-woocommerce' ) ),
				403,
			);
		}

		$stats = CacheService::run_facets_benchmark();

		wp_send_json_success(
			array(
				'benchmark' => $stats,
				'labels'    => array(
					'cold'   => CacheService::format_duration_ms( (float) $stats['cold_ms'] ),
					'warm'   => CacheService::format_duration_ms( (float) $stats['warm_ms'] ),
					'saved'  => CacheService::format_duration_ms( (float) $stats['saved_ms'] ),
					'percent'=> (int) $stats['saved_percent'],
				),
			),
		);
	}

	/**
	 * AJAX: flush plugin caches.
	 *
	 * @return void
	 */
	public function ajax_clear_cache(): void {
		check_ajax_referer( 'bpss_clear_cache', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied.', 'beplus-fast-product-filter-live-search-for-woocommerce' ) ),
				403,
			);
		}

		CacheService::flush_all( 'manual' );

		wp_send_json_success(
			array(
				'clearedAt' => CacheService::get_last_cleared_timestamp(),
				'message'   => __( 'Cache cleared successfully.', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
			),
		);
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$registry = new SettingsRegistry( $this->container );
		$settings = $registry->get_settings();
		$sidebar  = isset( $settings['sidebar'] ) && is_array( $settings['sidebar'] ) ? $settings['sidebar'] : array();

		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( in_array( $tab, array( 'taxonomies', 'price', 'sidebar' ), true ) ) {
			wp_safe_redirect(
				add_query_arg(
					'tab',
					'filters',
					admin_url( 'admin.php?page=' . self::MENU_SLUG ),
				),
			);
			exit;
		}

		require $this->plugin_dir . 'admin/views/settings-page.php';
	}
}
