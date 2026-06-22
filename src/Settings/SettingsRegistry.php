<?php

/**
 * Plugin settings registry.
 *
 * @package BePlusSmartSearch
 * @subpackage Settings
 */

namespace BePlusSmartSearch\Settings;

use BePlusSmartSearch\Core\AbstractModule;
use BePlusSmartSearch\Search\CacheService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages plugin options and defaults.
 */
class SettingsRegistry extends AbstractModule {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	public const OPTION_KEY = 'beplus_smart_search_settings';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings with WordPress.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'beplus_smart_search',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_defaults(),
			),
		);
	}

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_defaults(): array {
		return array(
			'debounce_ms'  => 280,
			'min_chars'    => 2,
			'per_page'     => 10,
			'enable_cache'                => true,
			'cache_ttl'                   => 60,
			'cache_clear_on_product_save' => true,
			'sidebar'      => array(
				'show_term_counts'      => true,
				'collapsible_sections'  => true,
				'sections_open_default' => true,
				'accent_color'          => '#000000',
				'facet_display_mode'    => 'all',
				'taxonomy_modes'        => array(
					'product_cat' => 'radio',
					'product_tag' => 'checkbox',
					'attribute'   => 'checkbox',
				),
				'taxonomy_sub_modes'    => array(
					'product_cat' => false,
					'product_tag' => false,
					'attribute'   => false,
				),
				'facets'                => array(
					'rating'            => array(
						'mode' => 'radio',
					),
					'brand'             => array(
						'taxonomy' => 'product_brand',
						'mode'     => 'checkbox',
						'show_sub' => false,
					),
					'custom_taxonomies' => array(),
				),
				'attribute_enabled'     => array(),
				'price'                 => array(
					'display'  => 'range',
					'min'      => 0,
					'max'      => 1000,
					'step'     => 1,
					'segments' => array(
						array(
							'min'   => 0,
							'max'   => 50,
							'label' => '',
						),
						array(
							'min'   => 50,
							'max'   => 100,
							'label' => '',
						),
						array(
							'min'   => 100,
							'max'   => 200,
							'label' => '',
						),
						array(
							'min'   => 200,
							'max'   => 0,
							'label' => '',
						),
					),
				),
			),
		);
	}

	/**
	 * Get merged settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_settings(): array {
		$stored = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return $this->merge_recursive( $this->get_defaults(), $stored );
	}

	/**
	 * Deep-merge settings with defaults.
	 *
	 * @param array<string, mixed> $defaults Defaults.
	 * @param array<string, mixed> $stored   Stored values.
	 *
	 * @return array<string, mixed>
	 */
	private function merge_recursive( array $defaults, array $stored ): array {
		$merged = $defaults;

		foreach ( $stored as $key => $value ) {
			if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
				$merged[ $key ] = $this->merge_recursive( $merged[ $key ], $value );
			} else {
				$merged[ $key ] = $value;
			}
		}

		return $merged;
	}

	/**
	 * Sanitize settings input.
	 *
	 * @param mixed $input Raw input.
	 *
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			return $this->get_defaults();
		}

		$sidebar_input = isset( $input['sidebar'] ) && is_array( $input['sidebar'] ) ? $input['sidebar'] : array();
		$modes_input   = isset( $sidebar_input['taxonomy_modes'] ) && is_array( $sidebar_input['taxonomy_modes'] )
			? $sidebar_input['taxonomy_modes']
			: array();
		$price_input   = isset( $sidebar_input['price'] ) && is_array( $sidebar_input['price'] )
			? $sidebar_input['price']
			: array();
		$facets_input  = isset( $sidebar_input['facets'] ) && is_array( $sidebar_input['facets'] )
			? $sidebar_input['facets']
			: array();

		$taxonomy_modes = array(
			'product_cat' => $this->sanitize_mode( $modes_input['product_cat'] ?? 'radio' ),
			'product_tag' => $this->sanitize_mode( $modes_input['product_tag'] ?? 'checkbox' ),
			'attribute'   => $this->sanitize_mode( $modes_input['attribute'] ?? 'checkbox' ),
		);

		$sub_modes_input = isset( $sidebar_input['taxonomy_sub_modes'] ) && is_array( $sidebar_input['taxonomy_sub_modes'] )
			? $sidebar_input['taxonomy_sub_modes']
			: array();

		$taxonomy_sub_modes = array(
			'product_cat' => ! empty( $sub_modes_input['product_cat'] ),
			'product_tag' => ! empty( $sub_modes_input['product_tag'] ),
			'attribute'   => ! empty( $sub_modes_input['attribute'] ),
		);

		if ( function_exists( 'beplus_smart_search_get_all_attribute_definitions' ) ) {
			foreach ( beplus_smart_search_get_all_attribute_definitions() as $attribute ) {
				$slug = sanitize_key( (string) ( $attribute['slug'] ?? '' ) );
				if ( ! $slug ) {
					continue;
				}

				$key = 'attribute:' . $slug;

				$taxonomy_modes[ $key ] = $this->sanitize_mode(
					(string) ( $modes_input[ $key ] ?? $modes_input['attribute'] ?? 'checkbox' ),
				);
				$taxonomy_sub_modes[ $key ] = ! empty( $sub_modes_input[ $key ] );
			}
		}

		$price_min = isset( $price_input['min'] ) ? (float) $price_input['min'] : 0;
		$price_max = isset( $price_input['max'] ) ? (float) $price_input['max'] : 1000;

		if ( $price_max < $price_min ) {
			$price_max = $price_min;
		}

		$price_max = max( 1, $price_max );

		$segments_input = isset( $price_input['segments'] ) && is_array( $price_input['segments'] )
			? $price_input['segments']
			: array();

		$ttl_input = isset( $input['cache_ttl'] ) ? (int) $input['cache_ttl'] : 60;
		$ttl       = in_array( $ttl_input, CacheService::get_ttl_presets(), true ) ? $ttl_input : 60;

		$previous = $this->get_settings();

		$sanitized = array(
			'debounce_ms'                 => max( 0, min( 2000, (int) ( $input['debounce_ms'] ?? 280 ) ) ),
			'min_chars'                   => max( 0, min( 10, (int) ( $input['min_chars'] ?? 2 ) ) ),
			'per_page'                    => max( 1, min( 50, (int) ( $input['per_page'] ?? 10 ) ) ),
			'enable_cache'                => ! empty( $input['enable_cache'] ),
			'cache_ttl'                   => $ttl,
			'cache_clear_on_product_save' => ! empty( $input['cache_clear_on_product_save'] ),
			'sidebar'      => array(
				'show_term_counts'      => ! empty( $sidebar_input['show_term_counts'] ),
				'collapsible_sections'  => ! empty( $sidebar_input['collapsible_sections'] ),
				'sections_open_default' => ! empty( $sidebar_input['sections_open_default'] ),
				'accent_color'          => sanitize_hex_color( $sidebar_input['accent_color'] ?? '#000000' ) ?: '#000000',
				'facet_display_mode'    => $this->sanitize_facet_mode( $sidebar_input['facet_display_mode'] ?? 'all' ),
				'taxonomy_modes'        => $taxonomy_modes,
				'taxonomy_sub_modes'    => $taxonomy_sub_modes,
				'attribute_enabled'     => $this->sanitize_attribute_enabled( $sidebar_input ),
				'facets'                => $this->sanitize_facets( $facets_input ),
				'price'                 => array(
					'display'  => $this->sanitize_price_display( $price_input['display'] ?? 'range' ),
					'min'      => max( 0, $price_min ),
					'max'      => max( 1, $price_max ),
					'step'     => max( 0.01, (float) ( $price_input['step'] ?? 1 ) ),
					'segments' => $this->sanitize_price_segments( $segments_input ),
				),
			),
		);

		$cache_changed = empty( $sanitized['enable_cache'] )
			|| empty( $previous['enable_cache'] ) !== empty( $sanitized['enable_cache'] )
			|| (int) ( $previous['cache_ttl'] ?? 60 ) !== (int) $sanitized['cache_ttl'];

		if ( $cache_changed ) {
			CacheService::flush_all( 'settings' );
		}

		return $sanitized;
	}

	/**
	 * Sanitize extended facet settings.
	 *
	 * @param array<string, mixed> $input Raw facets input.
	 *
	 * @return array<string, mixed>
	 */
	private function sanitize_facets( array $input ): array {
		$brand_input = isset( $input['brand'] ) && is_array( $input['brand'] ) ? $input['brand'] : array();
		$custom_raw  = isset( $input['custom_taxonomies'] ) && is_array( $input['custom_taxonomies'] )
			? $input['custom_taxonomies']
			: array();

		$custom_taxonomies = array();
		foreach ( $custom_raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$taxonomy = sanitize_key( (string) ( $row['taxonomy'] ?? '' ) );
			if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			if ( ! is_object_in_taxonomy( 'product', $taxonomy ) ) {
				continue;
			}

			if ( in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) ) {
				continue;
			}

			if ( 0 === strpos( $taxonomy, 'pa_' ) ) {
				continue;
			}

			$brand_taxonomy = $this->resolve_brand_taxonomy();
			if ( $brand_taxonomy && $taxonomy === $brand_taxonomy ) {
				continue;
			}

			$custom_taxonomies[] = array(
				'taxonomy' => $taxonomy,
				'label'    => sanitize_text_field( (string) ( $row['label'] ?? '' ) ),
				'mode'     => $this->sanitize_mode( (string) ( $row['mode'] ?? 'checkbox' ) ),
				'show_sub' => ! empty( $row['show_sub'] ),
			);
		}

		$brand_taxonomy = $this->resolve_brand_taxonomy();

		return array(
			'rating'            => array(
				'mode' => 'radio',
			),
			'brand'             => array(
				'taxonomy' => $brand_taxonomy,
				'mode'     => $this->sanitize_mode( (string) ( $brand_input['mode'] ?? 'checkbox' ) ),
				'show_sub' => ! empty( $brand_input['show_sub'] ),
			),
			'custom_taxonomies' => $custom_taxonomies,
		);
	}

	/**
	 * Resolve the fixed brand taxonomy slug.
	 *
	 * @return string
	 */
	private function resolve_brand_taxonomy(): string {
		if ( taxonomy_exists( 'product_brand' ) && is_object_in_taxonomy( 'product', 'product_brand' ) ) {
			return 'product_brand';
		}

		if ( function_exists( 'beplus_smart_search_detect_brand_taxonomy' ) ) {
			return beplus_smart_search_detect_brand_taxonomy();
		}

		return 'product_brand';
	}

	/**
	 * Sanitize per-attribute visibility toggles.
	 *
	 * @param array<string, mixed> $sidebar_input Raw sidebar input.
	 *
	 * @return array<string, bool>
	 */
	private function sanitize_attribute_enabled( array $sidebar_input ): array {
		$raw = isset( $sidebar_input['attribute_enabled'] ) && is_array( $sidebar_input['attribute_enabled'] )
			? $sidebar_input['attribute_enabled']
			: array();

		$enabled = array();

		if ( ! function_exists( 'beplus_smart_search_get_all_attribute_definitions' ) ) {
			return $enabled;
		}

		foreach ( beplus_smart_search_get_all_attribute_definitions() as $attribute ) {
			$slug              = sanitize_key( (string) ( $attribute['slug'] ?? '' ) );
			$enabled[ $slug ] = ! empty( $raw[ $slug ] );
		}

		return $enabled;
	}

	/**
	 * Sanitize taxonomy selection mode.
	 *
	 * @param string $mode Raw mode.
	 *
	 * @return string
	 */
	private function sanitize_mode( string $mode ): string {
		return 'checkbox' === $mode ? 'checkbox' : 'radio';
	}

	/**
	 * Sanitize facet display mode.
	 *
	 * @param string $mode Raw mode.
	 *
	 * @return string
	 */
	private function sanitize_facet_mode( string $mode ): string {
		return 'contextual' === $mode ? 'contextual' : 'all';
	}

	/**
	 * Sanitize price display mode.
	 *
	 * @param string $display Raw display mode.
	 *
	 * @return string
	 */
	private function sanitize_price_display( string $display ): string {
		return 'segments' === $display ? 'segments' : 'range';
	}

	/**
	 * Sanitize price segments.
	 *
	 * @param array<int, mixed> $segments Raw segments.
	 *
	 * @return array<int, array{min: float, max: float, label: string}>
	 */
	private function sanitize_price_segments( array $segments ): array {
		$items = array();

		foreach ( $segments as $segment ) {
			if ( ! is_array( $segment ) ) {
				continue;
			}

			$min   = isset( $segment['min'] ) ? (float) $segment['min'] : 0;
			$max   = isset( $segment['max'] ) ? (float) $segment['max'] : 0;
			$label = isset( $segment['label'] ) ? sanitize_text_field( (string) $segment['label'] ) : '';

			if ( $max > 0 && $max < $min ) {
				$max = $min;
			}

			$items[] = array(
				'min'   => max( 0, $min ),
				'max'   => max( 0, $max ),
				'label' => $label,
			);
		}

		if ( empty( $items ) ) {
			return $this->get_defaults()['sidebar']['price']['segments'];
		}

		return $items;
	}
}
