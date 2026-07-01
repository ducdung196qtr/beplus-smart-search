<?php

/**
 * Global helper functions.
 *
 * @package BePlusFastProductFilterLiveSearch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether WooCommerce is installed and active.
 *
 * @return bool
 */
function beplus_fast_product_filter_live_search_is_woocommerce_active(): bool {
	if ( class_exists( 'WooCommerce' ) ) {
		return true;
	}

	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		return true;
	}

	if ( is_multisite() && function_exists( 'is_plugin_active_for_network' ) ) {
		return is_plugin_active_for_network( 'woocommerce/woocommerce.php' );
	}

	return false;
}

/**
 * Get merged plugin settings.
 *
 * @return array<string, mixed>
 */
function beplus_fast_product_filter_live_search_get_settings(): array {
	static $settings = null;

	if ( null === $settings ) {
		$registry = new BePlusFastProductFilterLiveSearch\Settings\SettingsRegistry(
			new BePlusFastProductFilterLiveSearch\Core\Container(),
		);
		$settings = $registry->get_settings();
	}

	return $settings;
}

/**
 * Get sidebar subsection settings.
 *
 * @return array<string, mixed>
 */
function beplus_fast_product_filter_live_search_get_sidebar_settings(): array {
	$settings = beplus_fast_product_filter_live_search_get_settings();
	$sidebar  = isset( $settings['sidebar'] ) && is_array( $settings['sidebar'] )
		? $settings['sidebar']
		: array();

	return wp_parse_args(
		$sidebar,
		array(
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
 * Get selection mode for a taxonomy group.
 *
 * @param string $taxonomy_key product_cat|product_tag|attribute|attribute:{slug}.
 *
 * @return string radio|checkbox
 */
function beplus_fast_product_filter_live_search_get_taxonomy_mode( string $taxonomy_key ): string {
	$sidebar = beplus_fast_product_filter_live_search_get_sidebar_settings();
	$modes   = isset( $sidebar['taxonomy_modes'] ) && is_array( $sidebar['taxonomy_modes'] )
		? $sidebar['taxonomy_modes']
		: array();

	if ( 0 === strpos( $taxonomy_key, 'attribute:' ) ) {
		$slug = substr( $taxonomy_key, strlen( 'attribute:' ) );
		if ( ! empty( $modes[ 'attribute:' . $slug ] ) ) {
			return 'checkbox' === $modes[ 'attribute:' . $slug ] ? 'checkbox' : 'radio';
		}
		$taxonomy_key = 'attribute';
	}

	if ( 0 === strpos( $taxonomy_key, 'custom:' ) ) {
		$slug = substr( $taxonomy_key, strlen( 'custom:' ) );
		foreach ( beplus_fast_product_filter_live_search_get_custom_taxonomy_facets() as $facet ) {
			if ( $facet['taxonomy'] === $slug ) {
				return 'checkbox' === ( $facet['mode'] ?? 'checkbox' ) ? 'checkbox' : 'radio';
			}
		}
	}

	if ( 'brand' === $taxonomy_key ) {
		$brand = beplus_fast_product_filter_live_search_get_brand_facet_settings();
		return 'checkbox' === ( $brand['mode'] ?? 'checkbox' ) ? 'checkbox' : 'radio';
	}

	$mode = $modes[ $taxonomy_key ] ?? 'radio';

	return 'checkbox' === $mode ? 'checkbox' : 'radio';
}

/**
 * Whether a taxonomy group should render nested sub-terms with toggles.
 *
 * @param string $taxonomy_key product_cat|product_tag|attribute|attribute:{slug}.
 *
 * @return bool
 */
function beplus_fast_product_filter_live_search_show_sub_taxonomy( string $taxonomy_key ): bool {
	$sidebar = beplus_fast_product_filter_live_search_get_sidebar_settings();
	$modes   = isset( $sidebar['taxonomy_sub_modes'] ) && is_array( $sidebar['taxonomy_sub_modes'] )
		? $sidebar['taxonomy_sub_modes']
		: array();

	if ( 0 === strpos( $taxonomy_key, 'attribute:' ) ) {
		$slug = substr( $taxonomy_key, strlen( 'attribute:' ) );
		if ( ! empty( $modes[ 'attribute:' . $slug ] ) ) {
			return true;
		}
		$taxonomy_key = 'attribute';
	}

	if ( 0 === strpos( $taxonomy_key, 'custom:' ) ) {
		$slug = substr( $taxonomy_key, strlen( 'custom:' ) );
		foreach ( beplus_fast_product_filter_live_search_get_custom_taxonomy_facets() as $facet ) {
			if ( $facet['taxonomy'] === $slug ) {
				return ! empty( $facet['show_sub'] );
			}
		}
		return false;
	}

	if ( 'brand' === $taxonomy_key ) {
		$brand = beplus_fast_product_filter_live_search_get_brand_facet_settings();
		return ! empty( $brand['show_sub'] );
	}

	return ! empty( $modes[ $taxonomy_key ] );
}

/**
 * Get extended facet settings from sidebar config.
 *
 * @return array<string, mixed>
 */
function beplus_fast_product_filter_live_search_get_facet_settings(): array {
	$sidebar = beplus_fast_product_filter_live_search_get_sidebar_settings();
	$facets  = isset( $sidebar['facets'] ) && is_array( $sidebar['facets'] ) ? $sidebar['facets'] : array();

	return wp_parse_args(
		$facets,
		array(
			'rating'            => array(
				'mode' => 'radio',
			),
			'brand'             => array(
				'taxonomy' => '',
				'mode'     => 'checkbox',
				'show_sub' => false,
			),
			'custom_taxonomies' => array(),
		),
	);
}

/**
 * Brand facet settings.
 *
 * @return array{taxonomy: string, mode: string, show_sub: bool}
 */
function beplus_fast_product_filter_live_search_get_brand_facet_settings(): array {
	$facets = beplus_fast_product_filter_live_search_get_facet_settings();
	$brand  = isset( $facets['brand'] ) && is_array( $facets['brand'] ) ? $facets['brand'] : array();

	return array(
		'taxonomy' => sanitize_key( (string) ( $brand['taxonomy'] ?? '' ) ),
		'mode'     => 'checkbox' === ( $brand['mode'] ?? 'checkbox' ) ? 'checkbox' : 'radio',
		'show_sub' => ! empty( $brand['show_sub'] ),
	);
}

/**
 * Detect a product brand taxonomy when not configured manually.
 *
 * @return string
 */
function beplus_fast_product_filter_live_search_detect_brand_taxonomy(): string {
	$candidates = array( 'product_brand', 'pwb-brand', 'yith_product_brand', 'brand' );

	foreach ( $candidates as $taxonomy ) {
		if ( taxonomy_exists( $taxonomy ) && is_object_in_taxonomy( 'product', $taxonomy ) ) {
			return $taxonomy;
		}
	}

	return '';
}

/**
 * Resolved brand taxonomy slug for filtering.
 *
 * @return string
 */
function beplus_fast_product_filter_live_search_get_brand_taxonomy(): string {
	if ( taxonomy_exists( 'product_brand' ) && is_object_in_taxonomy( 'product', 'product_brand' ) ) {
		return 'product_brand';
	}

	return beplus_fast_product_filter_live_search_detect_brand_taxonomy();
}

/**
 * Custom taxonomy facet definitions from settings.
 *
 * @return array<int, array{taxonomy: string, label: string, mode: string, show_sub: bool}>
 */
function beplus_fast_product_filter_live_search_get_custom_taxonomy_facets(): array {
	$facets = beplus_fast_product_filter_live_search_get_facet_settings();
	$rows   = isset( $facets['custom_taxonomies'] ) && is_array( $facets['custom_taxonomies'] )
		? $facets['custom_taxonomies']
		: array();

	$items = array();
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$taxonomy = sanitize_key( (string) ( $row['taxonomy'] ?? '' ) );
		if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) || ! is_object_in_taxonomy( 'product', $taxonomy ) ) {
			continue;
		}

		$tax_object = get_taxonomy( $taxonomy );
		$label      = sanitize_text_field( (string) ( $row['label'] ?? '' ) );
		if ( '' === $label && $tax_object instanceof WP_Taxonomy ) {
			$label = $tax_object->labels->name;
		}

		$items[] = array(
			'taxonomy' => $taxonomy,
			'label'    => $label,
			'mode'     => 'checkbox' === ( $row['mode'] ?? 'checkbox' ) ? 'checkbox' : 'radio',
			'show_sub' => ! empty( $row['show_sub'] ),
		);
	}

	return $items;
}

/**
 * Taxonomy slugs used by brand + custom facet filters (for REST/URL).
 *
 * @return array<int, string>
 */
function beplus_fast_product_filter_live_search_get_configured_filter_taxonomies(): array {
	$taxonomies = array();

	$brand = beplus_fast_product_filter_live_search_get_brand_taxonomy();
	if ( $brand ) {
		$taxonomies[] = $brand;
	}

	foreach ( beplus_fast_product_filter_live_search_get_custom_taxonomy_facets() as $facet ) {
		$taxonomies[] = $facet['taxonomy'];
	}

	return array_values( array_unique( $taxonomies ) );
}

/**
 * Product taxonomies available for brand/custom facet configuration.
 *
 * @return array<string, string> slug => label
 */
function beplus_fast_product_filter_live_search_get_selectable_product_taxonomies(): array {
	$taxonomies = get_object_taxonomies( 'product', 'objects' );
	$items      = array();

	if ( ! is_array( $taxonomies ) ) {
		return $items;
	}

	foreach ( $taxonomies as $taxonomy => $object ) {
		if ( ! $object instanceof WP_Taxonomy ) {
			continue;
		}

		if ( in_array( $taxonomy, array( 'product_cat', 'product_tag', 'product_type', 'product_visibility', 'product_shipping_class' ), true ) ) {
			continue;
		}

		if ( 0 === strpos( $taxonomy, 'pa_' ) ) {
			continue;
		}

		$items[ $taxonomy ] = $object->labels->name;
	}

	asort( $items );

	return $items;
}

/**
 * Rating filter options (minimum average rating).
 *
 * @return array<int, array{value: int, label: string}>
 */
function beplus_fast_product_filter_live_search_get_rating_filter_options(): array {
	$options = array();

	for ( $stars = 5; $stars >= 1; $stars-- ) {
		$filled = str_repeat( '★', $stars );
		$empty  = str_repeat( '☆', 5 - $stars );
		/* translators: %d: minimum star rating */
		$label = sprintf( __( '%1$s%2$s & up', 'beplus-fast-product-filter-live-search-for-woocommerce' ), $filled, $empty );

		$options[] = array(
			'value' => $stars,
			'label' => $label,
		);
	}

	return $options;
}

/**
 * Get price filter settings.
 *
 * @return array<string, mixed>
 */
function beplus_fast_product_filter_live_search_get_price_settings(): array {
	$sidebar = beplus_fast_product_filter_live_search_get_sidebar_settings();
	$price   = isset( $sidebar['price'] ) && is_array( $sidebar['price'] ) ? $sidebar['price'] : array();

	return wp_parse_args(
		$price,
		array(
			'display'  => 'range',
			'min'      => 0,
			'max'      => 1000,
			'step'     => 1,
			'segments' => array(),
		),
	);
}

/**
 * Whether price filter uses segment radios.
 *
 * @return bool
 */
function beplus_fast_product_filter_live_search_is_price_segments_mode(): bool {
	$price = beplus_fast_product_filter_live_search_get_price_settings();

	return 'segments' === ( $price['display'] ?? 'range' );
}

/**
 * Format a price segment label for display.
 *
 * @param float  $min   Segment minimum.
 * @param float  $max   Segment maximum (0 = open ended).
 * @param string $label Custom label.
 *
 * @return string
 */
function beplus_fast_product_filter_live_search_format_price_segment_label( float $min, float $max, string $label = '' ): string {
	if ( '' !== $label ) {
		return $label;
	}

	$currency = beplus_fast_product_filter_live_search_get_currency_symbol();
	$min_fmt  = number_format_i18n( $min );

	if ( $max <= 0 ) {
		/* translators: 1: currency symbol, 2: minimum price */
		return sprintf( __( '%1$s%2$s and above', 'beplus-fast-product-filter-live-search-for-woocommerce' ), $currency, $min_fmt );
	}

	$max_fmt = number_format_i18n( $max );

	/* translators: 1: currency symbol, 2: minimum price, 3: currency symbol, 4: maximum price */
	return sprintf( __( '%1$s%2$s — %3$s%4$s', 'beplus-fast-product-filter-live-search-for-woocommerce' ), $currency, $min_fmt, $currency, $max_fmt );
}

/**
 * Whether price filter is enabled (global settings).
 *
 * @deprecated Block attribute showPrice controls visibility.
 *
 * @return bool
 */
function beplus_fast_product_filter_live_search_is_price_filter_enabled(): bool {
	return true;
}

/**
 * Get facet display mode (all|contextual).
 *
 * @return string
 */
function beplus_fast_product_filter_live_search_get_facet_display_mode(): string {
	$sidebar = beplus_fast_product_filter_live_search_get_sidebar_settings();
	$mode    = $sidebar['facet_display_mode'] ?? 'all';

	return 'contextual' === $mode ? 'contextual' : 'all';
}

/**
 * Get WooCommerce currency symbol for price inputs.
 *
 * @return string
 */
function beplus_fast_product_filter_live_search_get_currency_symbol(): string {
	if ( function_exists( 'get_woocommerce_currency_symbol' ) ) {
		return get_woocommerce_currency_symbol();
	}

	return '$';
}

/**
 * Results per page from plugin settings.
 *
 * @return int
 */
function beplus_fast_product_filter_live_search_get_per_page(): int {
	$settings = beplus_fast_product_filter_live_search_get_settings();
	$per_page = isset( $settings['per_page'] ) ? (int) $settings['per_page'] : 10;

	return max( 1, min( 50, $per_page ) );
}

/**
 * Whether the site uses WordPress plain permalinks (?p=123).
 *
 * @return bool
 */
function beplus_fast_product_filter_live_search_uses_plain_permalinks(): bool {
	return '' === (string) get_option( 'permalink_structure', '' );
}

/**
 * Base URL for catalog search form submissions.
 *
 * Pretty permalinks: WooCommerce shop page URL (/shop/).
 * Plain permalinks: site home — not ?page_id=shop, because WooCommerce canonical
 * redirect (?page_id=7 → ?post_type=product) drops custom query args such as bpss_s.
 *
 * @return string
 */
function beplus_fast_product_filter_live_search_get_catalog_search_base_url(): string {
	if ( beplus_fast_product_filter_live_search_uses_plain_permalinks() ) {
		return home_url( '/' );
	}

	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$shop_url = wc_get_page_permalink( 'shop' );
		if ( is_string( $shop_url ) && '' !== $shop_url ) {
			return $shop_url;
		}
	}

	return home_url( '/' );
}

/**
 * Whether catalog search URLs need an explicit post_type=product query arg.
 *
 * Required on plain permalinks so the product archive loads with filter params intact.
 *
 * @return bool
 */
function beplus_fast_product_filter_live_search_catalog_search_needs_post_type_arg(): bool {
	return beplus_fast_product_filter_live_search_uses_plain_permalinks();
}

/**
 * Build a catalog search URL (GET) with bpss_s and optional filters.
 *
 * @param array<string, mixed> $params Supported keys: keyword, product_cat (string|string[]).
 *
 * @return string
 */
function beplus_fast_product_filter_live_search_build_catalog_search_url( array $params = array() ): string {
	$base = beplus_fast_product_filter_live_search_get_catalog_search_base_url();
	$args = array();

	if ( beplus_fast_product_filter_live_search_catalog_search_needs_post_type_arg() ) {
		$args['post_type'] = 'product';
	}

	$keyword = isset( $params['keyword'] ) ? sanitize_text_field( (string) $params['keyword'] ) : '';
	if ( '' !== $keyword ) {
		$args['bpss_s'] = $keyword;
	}

	if ( ! empty( $params['product_cat'] ) ) {
		$cats = is_array( $params['product_cat'] ) ? $params['product_cat'] : array( $params['product_cat'] );
		$cats = array_values(
			array_filter(
				array_map( 'sanitize_title', $cats ),
			),
		);
		if ( ! empty( $cats ) ) {
			$args['product_cat'] = implode( ',', $cats );
		}
	}

	if ( empty( $args ) ) {
		return $base;
	}

	return add_query_arg( $args, $base );
}

/**
 * Whether the current front-end page contains the Advanced Woo Search block.
 *
 * @return bool
 */
function beplus_fast_product_filter_live_search_page_has_search_block(): bool {
	static $has_block = null;

	if ( null !== $has_block ) {
		return $has_block;
	}

	$has_block = false;
	$block     = 'beplus-fast-product-filter-live-search-for-woocommerce/advanced-woo-search';

	if ( is_singular() ) {
		global $post;
		$has_block = ( $post instanceof WP_Post ) && has_block( $block, $post );
		return $has_block;
	}

	foreach ( beplus_fast_product_filter_live_search_get_block_content_sources() as $content ) {
		if ( $content && has_block( $block, $content ) ) {
			$has_block = true;
			return $has_block;
		}
	}

	return $has_block;
}

/**
 * Collect block editor content that may contain the search block on this request.
 *
 * @return array<int, string>
 */
function beplus_fast_product_filter_live_search_get_block_content_sources(): array {
	$sources = array();

	if ( function_exists( 'wc_get_page_id' ) ) {
		$shop_id = wc_get_page_id( 'shop' );
		if ( $shop_id > 0 ) {
			$content = (string) get_post_field( 'post_content', $shop_id );
			if ( $content ) {
				$sources[] = $content;
			}
		}
	}

	if ( function_exists( 'get_block_templates' ) ) {
		$template_slugs = beplus_fast_product_filter_live_search_get_relevant_template_slugs();

		if ( ! empty( $template_slugs ) ) {
			$templates = get_block_templates(
				array(
					'slug__in' => $template_slugs,
				),
				'wp_template',
			);

			foreach ( $templates as $template ) {
				if ( ! empty( $template->content ) ) {
					$sources[] = $template->content;
				}
			}
		}
	}

	/**
	 * Allow themes/plugins to register additional template sources.
	 *
	 * @param array<int, string> $sources Raw block content strings.
	 */
	return apply_filters( 'beplus_fast_product_filter_live_search_block_content_sources', $sources );
}

/**
 * Template slugs that may render on the current product archive route.
 *
 * @return array<int, string>
 */
function beplus_fast_product_filter_live_search_get_relevant_template_slugs(): array {
	$slugs = array();

	if ( function_exists( 'is_shop' ) && is_shop() ) {
		$slugs[] = 'archive-product';
	}

	if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
		$slugs[] = 'archive-product';

		if ( is_tax( 'product_cat' ) ) {
			$slugs[] = 'taxonomy-product_cat';
		}

		if ( is_tax( 'product_tag' ) ) {
			$slugs[] = 'taxonomy-product_tag';
		}

		$queried = get_queried_object();
		if ( $queried instanceof WP_Term ) {
			$slugs[] = 'taxonomy-' . $queried->taxonomy;
		}
	}

	return array_values( array_unique( $slugs ) );
}

/**
 * Default WooCommerce catalog orderby value.
 *
 * @return string
 */
function beplus_fast_product_filter_live_search_get_default_catalog_orderby(): string {
	$default = apply_filters(
		'woocommerce_default_catalog_orderby', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		get_option( 'woocommerce_default_catalog_orderby', 'menu_order' ),
	);

	return is_string( $default ) && '' !== $default ? $default : 'menu_order';
}

/**
 * Parse WooCommerce catalog orderby (e.g. price-desc) for product queries.
 *
 * @param string $raw   Raw orderby value from URL or REST.
 * @param string $order Optional explicit order (asc|desc).
 *
 * @return array{orderby: string, order: string, wc_value: string}
 */
function beplus_fast_product_filter_live_search_parse_catalog_orderby( string $raw = '', string $order = '' ): array {
	$raw = sanitize_text_field( $raw );

	if ( '' === $raw ) {
		$raw = beplus_fast_product_filter_live_search_get_default_catalog_orderby();
	}

	$parts        = explode( '-', $raw, 2 );
	$orderby      = $parts[0];
	$parsed_order = isset( $parts[1] ) ? $parts[1] : $order;

	if ( '' === $parsed_order ) {
		$parsed_order = in_array( $orderby, array( 'date', 'relevance' ), true ) ? 'desc' : 'asc';
	}

	$allowed = array(
		'menu_order',
		'popularity',
		'rating',
		'date',
		'price',
		'title',
		'relevance',
		'rand',
		'id',
	);

	if ( ! in_array( $orderby, $allowed, true ) ) {
		$raw          = beplus_fast_product_filter_live_search_get_default_catalog_orderby();
		$parts        = explode( '-', $raw, 2 );
		$orderby      = $parts[0];
		$parsed_order = isset( $parts[1] ) ? $parts[1] : 'asc';
	}

	$parsed_order = in_array( strtolower( $parsed_order ), array( 'asc', 'desc' ), true )
		? strtolower( $parsed_order )
		: 'asc';

	if ( 'price' === $orderby && 'desc' === $parsed_order ) {
		$wc_value = 'price-desc';
	} elseif ( 'price' === $orderby ) {
		$wc_value = 'price';
	} else {
		$wc_value = $orderby;
	}

	return array(
		'orderby'  => $orderby,
		'order'    => $parsed_order,
		'wc_value' => $wc_value,
	);
}

/**
 * Catalog of sortable filter section IDs => labels (attributes as separate entries).
 *
 * @param array<string, mixed> $attrs Optional block attributes to filter attribute sections.
 *
 * @return array<string, string>
 */
function beplus_fast_product_filter_live_search_get_filter_section_catalog( array $attrs = array() ): array {
	$sections = array(
		'keyword'  => __( 'Keyword search', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
		'category' => __( 'Product Categories', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
		'price'    => __( 'Price', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
	);

	$brand_tax = beplus_fast_product_filter_live_search_get_brand_taxonomy();
	if ( $brand_tax ) {
		$brand_object = get_taxonomy( $brand_tax );
		$sections['brand'] = $brand_object instanceof WP_Taxonomy
			? $brand_object->labels->name
			: __( 'Brand', 'beplus-fast-product-filter-live-search-for-woocommerce' );
	}

	foreach ( beplus_fast_product_filter_live_search_get_all_attribute_definitions() as $attribute ) {
		if ( ! empty( $attrs ) && ! beplus_fast_product_filter_live_search_is_block_attribute_enabled( $attribute['slug'], $attrs ) ) {
			continue;
		}
		$sections[ 'attribute:' . $attribute['slug'] ] = $attribute['label'];
	}

	$sections['tag']      = __( 'Product Tags', 'beplus-fast-product-filter-live-search-for-woocommerce' );
	$sections['stock']    = __( 'Stock status', 'beplus-fast-product-filter-live-search-for-woocommerce' );
	$sections['on_sale']  = __( 'On sale', 'beplus-fast-product-filter-live-search-for-woocommerce' );
	$sections['featured'] = __( 'Featured products', 'beplus-fast-product-filter-live-search-for-woocommerce' );
	$sections['rating']   = __( 'Rating', 'beplus-fast-product-filter-live-search-for-woocommerce' );

	foreach ( beplus_fast_product_filter_live_search_get_custom_taxonomy_facets() as $facet ) {
		$sections[ 'custom:' . $facet['taxonomy'] ] = $facet['label'];
	}

	return $sections;
}

/**
 * Whether an attribute is enabled on a block instance.
 *
 * @param string               $slug  Attribute slug.
 * @param array<string, mixed> $attrs Block attributes.
 *
 * @return bool
 */
function beplus_fast_product_filter_live_search_is_block_attribute_enabled( string $slug, array $attrs ): bool {
	$selected = isset( $attrs['attributeSlugs'] ) && is_array( $attrs['attributeSlugs'] )
		? array_values( array_map( 'strval', $attrs['attributeSlugs'] ) )
		: array();

	// Legacy blocks: master Attributes toggle off with no explicit slug list.
	if ( empty( $selected ) && isset( $attrs['showAttributes'] ) && empty( $attrs['showAttributes'] ) ) {
		return false;
	}

	if ( empty( $selected ) ) {
		return true;
	}

	return in_array( $slug, $selected, true );
}

/**
 * Attribute slugs to render for a block instance.
 *
 * @param array<string, mixed> $attrs Block attributes.
 *
 * @return array<int, string>
 */
function beplus_fast_product_filter_live_search_get_block_attribute_slugs( array $attrs ): array {
	$selected = isset( $attrs['attributeSlugs'] ) && is_array( $attrs['attributeSlugs'] )
		? array_values( array_map( 'strval', $attrs['attributeSlugs'] ) )
		: array();

	// Legacy blocks: master Attributes toggle off with no explicit slug list.
	if ( empty( $selected ) && isset( $attrs['showAttributes'] ) && empty( $attrs['showAttributes'] ) ) {
		return array();
	}

	$all = array();
	foreach ( beplus_fast_product_filter_live_search_get_all_attribute_definitions() as $attribute ) {
		$all[] = $attribute['slug'];
	}

	if ( empty( $selected ) ) {
		return $all;
	}

	return array_values( array_intersect( $all, $selected ) );
}

/**
 * Default filter section order when block has no saved order.
 *
 * @return array<int, string>
 */
function beplus_fast_product_filter_live_search_get_default_filter_order(): array {
	$order = array(
		'keyword',
		'category',
		'price',
		'brand',
	);

	foreach ( beplus_fast_product_filter_live_search_get_all_attribute_definitions() as $attribute ) {
		$order[] = 'attribute:' . $attribute['slug'];
	}

	$order[] = 'tag';
	$order[] = 'stock';
	$order[] = 'on_sale';
	$order[] = 'featured';
	$order[] = 'rating';

	foreach ( beplus_fast_product_filter_live_search_get_custom_taxonomy_facets() as $facet ) {
		$order[] = 'custom:' . $facet['taxonomy'];
	}

	return $order;
}

/**
 * Merge saved block order with catalog (append new sections, drop unknown).
 *
 * @param array<string, mixed> $attrs Block attributes.
 *
 * @return array<int, string>
 */
function beplus_fast_product_filter_live_search_resolve_filter_order( array $attrs ): array {
	$catalog = beplus_fast_product_filter_live_search_get_filter_section_catalog( $attrs );
	$saved   = isset( $attrs['filterOrder'] ) && is_array( $attrs['filterOrder'] )
		? array_values( array_map( 'strval', $attrs['filterOrder'] ) )
		: array();
	$base    = ! empty( $saved ) ? $saved : beplus_fast_product_filter_live_search_get_default_filter_order();
	$merged  = array();

	foreach ( $base as $section_id ) {
		if ( isset( $catalog[ $section_id ] ) && ! in_array( $section_id, $merged, true ) ) {
			$merged[] = $section_id;
		}
	}

	foreach ( array_keys( $catalog ) as $section_id ) {
		if ( ! in_array( $section_id, $merged, true ) ) {
			$merged[] = $section_id;
		}
	}

	return $merged;
}

/**
 * Whether a filter section should render for current block attrs and data.
 *
 * @param string                              $section_id         Section key.
 * @param array<string, mixed>                $attrs              Block attributes.
 * @param array<int, WP_Term>                 $categories         Categories.
 * @param array<int, WP_Term>                 $tags               Tags.
 * @param array<string, array<string, mixed>> $attributes_by_slug Attribute map keyed by slug.
 *
 * @return bool
 */
function beplus_fast_product_filter_live_search_should_render_filter_section(
	string $section_id,
	array $attrs,
	array $categories,
	array $tags,
	array $attributes_by_slug,
): bool {
	if ( 'keyword' === $section_id ) {
		return ! empty( $attrs['showKeyword'] );
	}

	if ( 'category' === $section_id ) {
		return ! empty( $attrs['showCategory'] ) && ! empty( $categories );
	}

	if ( 'price' === $section_id ) {
		return ! empty( $attrs['showPrice'] );
	}

	if ( 'tag' === $section_id ) {
		return ! empty( $attrs['showTag'] ) && ! empty( $tags );
	}

	if ( 0 === strpos( $section_id, 'attribute:' ) ) {
		$slug = substr( $section_id, strlen( 'attribute:' ) );

		if ( ! beplus_fast_product_filter_live_search_is_block_attribute_enabled( $slug, $attrs ) ) {
			return false;
		}

		return isset( $attributes_by_slug[ $slug ] ) && ! empty( $attributes_by_slug[ $slug ]['terms'] );
	}

	if ( 'stock' === $section_id ) {
		return ! empty( $attrs['showStock'] );
	}

	if ( 'on_sale' === $section_id ) {
		return ! empty( $attrs['showOnSale'] );
	}

	if ( 'rating' === $section_id ) {
		return ! empty( $attrs['showRating'] );
	}

	if ( 'featured' === $section_id ) {
		return ! empty( $attrs['showFeatured'] );
	}

	if ( 'brand' === $section_id ) {
		if ( empty( $attrs['showBrand'] ) ) {
			return false;
		}
		$brand_tax = beplus_fast_product_filter_live_search_get_brand_taxonomy();

		return $brand_tax && ! empty( beplus_fast_product_filter_live_search_get_brand_terms() );
	}

	if ( 0 === strpos( $section_id, 'custom:' ) ) {
		if ( empty( $attrs['showCustomTaxonomies'] ) ) {
			return false;
		}
		$taxonomy = substr( $section_id, strlen( 'custom:' ) );

		return ! empty( beplus_fast_product_filter_live_search_get_taxonomy_terms( $taxonomy ) );
	}

	return false;
}
