<?php

/**
 * Object and transient cache for expensive search data.
 *
 * @package BePlusSmartSearch
 * @subpackage Search
 */

namespace BePlusSmartSearch\Search;

use BePlusSmartSearch\Settings\SettingsRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central cache layer for facet payloads and related storefront data.
 */
final class CacheService {

	/**
	 * wp_cache group.
	 */
	public const CACHE_GROUP = 'beplus_smart_search';

	/**
	 * Transient / object-cache key for unfiltered facet lists.
	 */
	public const KEY_FACETS_ALL = 'bpss_cache_facets_all';

	/**
	 * Option storing facet load benchmark (cold vs cached).
	 */
	public const BENCHMARK_OPTION = 'bpss_cache_benchmark';

	/**
	 * Option storing the last manual or automatic cache flush timestamp.
	 */
	public const LAST_CLEARED_OPTION = 'bpss_cache_last_cleared';

	/**
	 * Register product/taxonomy hooks that invalidate cache when catalog changes.
	 *
	 * @return void
	 */
	public static function register_invalidation_hooks(): void {
		$settings = self::get_cache_settings();

		if ( empty( $settings['cache_clear_on_product_save'] ) ) {
			return;
		}

		$flush = static function (): void {
			self::flush_all( 'auto' );
		};

		add_action( 'save_post_product', $flush, 99 );
		add_action( 'before_delete_post', static function ( int $post_id ) use ( $flush ): void {
			if ( 'product' === get_post_type( $post_id ) ) {
				$flush();
			}
		}, 99 );
		add_action( 'woocommerce_product_set_stock_status', $flush, 99 );
		add_action( 'created_term', static function ( int $term_id, int $tt_id, string $taxonomy ) use ( $flush ): void {
			unset( $term_id, $tt_id );

			if ( self::is_product_taxonomy( $taxonomy ) ) {
				$flush();
			}
		}, 99, 3 );
		add_action( 'edited_term', static function ( int $term_id, int $tt_id, string $taxonomy ) use ( $flush ): void {
			unset( $term_id, $tt_id );

			if ( self::is_product_taxonomy( $taxonomy ) ) {
				$flush();
			}
		}, 99, 3 );
		add_action( 'delete_term', static function ( int $term_id, int $tt_id, string $taxonomy ) use ( $flush ): void {
			unset( $term_id, $tt_id );

			if ( self::is_product_taxonomy( $taxonomy ) ) {
				$flush();
			}
		}, 99, 3 );
	}

	/**
	 * Human-readable list of cached data groups for the settings screen.
	 *
	 * @return array<int, array{title: string, description: string}>
	 */
	public static function get_cached_item_catalog(): array {
		return array(
			array(
				'title'       => __( 'Filter lists (facets)', 'beplus-smart-search' ),
				'description' => __( 'Categories, tags, attributes, brands, and price bounds shown in the sidebar when shoppers have not applied any filter yet.', 'beplus-smart-search' ),
			),
			array(
				'title'       => __( 'Term counts in facet lists', 'beplus-smart-search' ),
				'description' => __( 'How many products belong to each category, tag, or attribute option in the default (unfiltered) view.', 'beplus-smart-search' ),
			),
		);
	}

	/**
	 * Allowed TTL presets in minutes.
	 *
	 * @return array<int, int>
	 */
	public static function get_ttl_presets(): array {
		return array( 15, 30, 60, 120, 360, 720, 1440, 10080 );
	}

	/**
	 * Human label for a TTL preset.
	 *
	 * @param int $minutes TTL in minutes.
	 *
	 * @return string
	 */
	public static function format_ttl_label( int $minutes ): string {
		if ( $minutes < 60 ) {
			return sprintf(
				/* translators: %d: number of minutes */
				_n( '%d minute', '%d minutes', $minutes, 'beplus-smart-search' ),
				$minutes,
			);
		}

		if ( $minutes < 1440 ) {
			$hours = (int) round( $minutes / 60 );

			return sprintf(
				/* translators: %d: number of hours */
				_n( '%d hour', '%d hours', $hours, 'beplus-smart-search' ),
				$hours,
			);
		}

		$days = (int) round( $minutes / 1440 );

		return sprintf(
			/* translators: %d: number of days */
			_n( '%d day', '%d days', $days, 'beplus-smart-search' ),
			$days,
		);
	}

	/**
	 * Whether caching is enabled in settings.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		$settings = self::get_cache_settings();

		return ! empty( $settings['enable_cache'] );
	}

	/**
	 * Cache lifetime in seconds.
	 *
	 * @return int
	 */
	public static function get_ttl_seconds(): int {
		$settings = self::get_cache_settings();
		$minutes  = (int) ( $settings['cache_ttl'] ?? 60 );

		return max( 5, min( 10080, $minutes ) ) * MINUTE_IN_SECONDS;
	}

	/**
	 * Get cached facet payload.
	 *
	 * @return array<string, mixed>|false
	 */
	public static function get_facets_all() {
		if ( ! self::is_enabled() ) {
			return false;
		}

		$cached = wp_cache_get( self::KEY_FACETS_ALL, self::CACHE_GROUP );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$cached = get_transient( self::KEY_FACETS_ALL );
		if ( is_array( $cached ) ) {
			wp_cache_set( self::KEY_FACETS_ALL, $cached, self::CACHE_GROUP, self::get_ttl_seconds() );
			return $cached;
		}

		return false;
	}

	/**
	 * Store facet payload in object cache and transients.
	 *
	 * @param array<string, mixed> $data Facet response.
	 *
	 * @return void
	 */
	public static function set_facets_all( array $data ): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		$ttl = self::get_ttl_seconds();
		wp_cache_set( self::KEY_FACETS_ALL, $data, self::CACHE_GROUP, $ttl );
		set_transient( self::KEY_FACETS_ALL, $data, $ttl );
	}

	/**
	 * Delete all plugin caches.
	 *
	 * @param string $source manual|auto|settings.
	 *
	 * @return void
	 */
	public static function flush_all( string $source = 'manual' ): void {
		unset( $source );

		wp_cache_delete( self::KEY_FACETS_ALL, self::CACHE_GROUP );
		delete_transient( self::KEY_FACETS_ALL );

		update_option( self::LAST_CLEARED_OPTION, time(), false );
	}

	/**
	 * Last cache flush timestamp.
	 *
	 * @return int
	 */
	public static function get_last_cleared_timestamp(): int {
		return (int) get_option( self::LAST_CLEARED_OPTION, 0 );
	}

	/**
	 * Stored facet benchmark stats.
	 *
	 * @return array{cold_ms: float, warm_ms: float, saved_ms: float, saved_percent: int, measured_at: int}|null
	 */
	public static function get_benchmark_stats(): ?array {
		$stats = get_option( self::BENCHMARK_OPTION, null );

		if ( ! is_array( $stats ) ) {
			return null;
		}

		$cold = isset( $stats['cold_ms'] ) ? (float) $stats['cold_ms'] : 0.0;
		$warm = isset( $stats['warm_ms'] ) ? (float) $stats['warm_ms'] : 0.0;

		if ( $cold <= 0 ) {
			return null;
		}

		return array(
			'cold_ms'        => $cold,
			'warm_ms'        => $warm,
			'saved_ms'       => max( 0.0, isset( $stats['saved_ms'] ) ? (float) $stats['saved_ms'] : $cold - $warm ),
			'saved_percent'  => (int) ( $stats['saved_percent'] ?? 0 ),
			'measured_at'    => (int) ( $stats['measured_at'] ?? 0 ),
		);
	}

	/**
	 * Measure unfiltered facet load time with and without cache.
	 *
	 * @return array{cold_ms: float, warm_ms: float, saved_ms: float, saved_percent: int, measured_at: int}
	 */
	public static function run_facets_benchmark(): array {
		self::flush_all( 'benchmark' );

		$service = new FacetService();
		$context = new SearchQuery();

		$cold_start = microtime( true );
		$data       = $service->get_facets( $context, 'all' );
		$cold_ms    = ( microtime( true ) - $cold_start ) * 1000;

		wp_cache_set( self::KEY_FACETS_ALL, $data, self::CACHE_GROUP, self::get_ttl_seconds() );
		set_transient( self::KEY_FACETS_ALL, $data, self::get_ttl_seconds() );

		$warm_start = microtime( true );
		$cached     = wp_cache_get( self::KEY_FACETS_ALL, self::CACHE_GROUP );
		if ( false === $cached || ! is_array( $cached ) ) {
			$cached = get_transient( self::KEY_FACETS_ALL );
		}
		$warm_ms = ( microtime( true ) - $warm_start ) * 1000;

		unset( $cached );

		$saved_ms      = max( 0.0, $cold_ms - $warm_ms );
		$saved_percent = $cold_ms > 0 ? (int) round( ( $saved_ms / $cold_ms ) * 100 ) : 0;

		$stats = array(
			'cold_ms'       => round( $cold_ms, 1 ),
			'warm_ms'       => round( $warm_ms, 1 ),
			'saved_ms'      => round( $saved_ms, 1 ),
			'saved_percent' => $saved_percent,
			'measured_at'   => time(),
		);

		update_option( self::BENCHMARK_OPTION, $stats, false );

		return $stats;
	}

	/**
	 * Format milliseconds for display.
	 *
	 * @param float $ms Milliseconds.
	 *
	 * @return string
	 */
	public static function format_duration_ms( float $ms ): string {
		if ( $ms < 1000 ) {
			return sprintf(
				/* translators: %s: milliseconds with one decimal */
				__( '%s ms', 'beplus-smart-search' ),
				number_format_i18n( $ms, 1 ),
			);
		}

		return sprintf(
			/* translators: %s: seconds with two decimals */
			__( '%s s', 'beplus-smart-search' ),
			number_format_i18n( $ms / 1000, 2 ),
		);
	}

	/**
	 * @return array{enable_cache: bool, cache_ttl: int, cache_clear_on_product_save: bool}
	 */
	private static function get_cache_settings(): array {
		$registry = new SettingsRegistry( new \BePlusSmartSearch\Core\Container() );
		$settings = $registry->get_settings();

		return array(
			'enable_cache'                => ! empty( $settings['enable_cache'] ),
			'cache_ttl'                   => (int) ( $settings['cache_ttl'] ?? 60 ),
			'cache_clear_on_product_save' => ! isset( $settings['cache_clear_on_product_save'] ) || ! empty( $settings['cache_clear_on_product_save'] ),
		);
	}

	/**
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return bool
	 */
	private static function is_product_taxonomy( string $taxonomy ): bool {
		if ( ! taxonomy_exists( $taxonomy ) || ! is_object_in_taxonomy( 'product', $taxonomy ) ) {
			return false;
		}

		return true;
	}
}
