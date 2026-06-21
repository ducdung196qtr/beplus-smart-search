<?php

/**
 * Render product cards using the theme's WooCommerce product-template block.
 *
 * @package BePlusSmartSearch
 * @subpackage Search
 */

namespace BePlusSmartSearch\Search;

use WP_Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mirrors WooCommerce ProductTemplate block rendering for AJAX results.
 */
final class ProductTemplateRenderer {

	/**
	 * Cached product-template parsed block.
	 *
	 * @var array<string, mixed>|null|false
	 */
	private static $template_block = null;

	/**
	 * Cached product-collection context.
	 *
	 * @var array<string, mixed>|null
	 */
	private static $collection_context = null;

	/**
	 * Register hook to capture live template when the shop archive renders.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'render_block_woocommerce/product-collection', array( self::class, 'capture_from_collection_block' ), 5, 2 );
	}

	/**
	 * Store parsed product-template block from a rendered product collection.
	 *
	 * @param string               $content Block HTML.
	 * @param array<string, mixed> $block   Block data.
	 *
	 * @return string
	 */
	public static function capture_from_collection_block( string $content, array $block ): string {
		if ( empty( $block['blockName'] ) || 'woocommerce/product-collection' !== $block['blockName'] ) {
			return $content;
		}

		$template = self::find_block( $block['innerBlocks'] ?? array(), 'woocommerce/product-template' );
		if ( ! $template ) {
			return $content;
		}

		self::$template_block     = $template;
		self::$collection_context = self::build_context_from_collection_attrs( $block['attrs'] ?? array() );

		return $content;
	}

	/**
	 * Render a single product list item using the active product template.
	 *
	 * @param int $product_id Product post ID.
	 *
	 * @return string
	 */
	public static function render_product( int $product_id ): string {
		$setup = self::get_template_setup();
		if ( ! $setup ) {
			return '';
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return '';
		}

		if ( $product->is_type( 'variable' ) && function_exists( 'wc_interactivity_api_load_variations' ) ) {
			wc_interactivity_api_load_variations(
				'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce',
				$product_id,
			);
		}

		global $post;
		$previous_post = $post;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( $product_id );
		if ( ! $post ) {
			return '';
		}

		setup_postdata( $post );

		$block_instance            = $setup['parsed_block'];
		$block_instance['blockName'] = 'core/null';

		$context = array_merge(
			$setup['context'],
			array(
				'postType' => 'product',
				'postId'   => $product_id,
			),
		);

		$block_content = ( new WP_Block( $block_instance, $context ) )->render(
			array(
				'dynamic' => false,
			),
		);

		if ( function_exists( 'wc_interactivity_api_load_product' ) ) {
			wc_interactivity_api_load_product(
				'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce',
				$product_id,
			);
		}

		$li_directives = '';
		if ( function_exists( 'wp_interactivity_data_wp_context' ) ) {
			$product_context_directive = wp_interactivity_data_wp_context(
				array(
					'productId'   => $product_id,
					'variationId' => null,
				),
				'woocommerce/products',
			);

			$li_directives = trim(
				'data-wp-interactive="woocommerce/product-collection" '
				. $product_context_directive
				. ' data-wp-key="product-item-' . $product_id . '"',
			);
		}

		$post_classes = implode( ' ', get_post_class( 'wc-block-product', $product_id ) );
		$html         = '<li class="' . esc_attr( $post_classes ) . '"' . ( $li_directives ? ' ' . $li_directives : '' ) . '>' . $block_content . '</li>';

		wp_reset_postdata();

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = $previous_post;

		/**
		 * Filter rendered product card HTML for AJAX results.
		 *
		 * @param string $html       Product list item HTML.
		 * @param int    $product_id Product ID.
		 */
		return (string) apply_filters( 'beplus_smart_search_product_card_html', $html, $product_id );
	}

	/**
	 * Resolve product-template block and collection context.
	 *
	 * @return array{parsed_block: array<string, mixed>, context: array<string, mixed>}|null
	 */
	private static function get_template_setup(): ?array {
		if ( null !== self::$template_block ) {
			if ( false === self::$template_block ) {
				return null;
			}

			return array(
				'parsed_block' => self::$template_block,
				'context'      => self::$collection_context ?? array(),
			);
		}

		$content = self::resolve_template_content();
		if ( ! $content ) {
			self::$template_block = false;
			return null;
		}

		$blocks     = parse_blocks( $content );
		$collection = self::find_block( $blocks, 'woocommerce/product-collection' );
		$template   = null;

		if ( $collection ) {
			$template = self::find_block( $collection['innerBlocks'] ?? array(), 'woocommerce/product-template' );
			if ( $template ) {
				self::$collection_context = self::build_context_from_collection_attrs( $collection['attrs'] ?? array() );
			}
		}

		if ( ! $template ) {
			$template = self::find_block( $blocks, 'woocommerce/product-template' );
		}

		if ( ! $template ) {
			self::$template_block = false;
			return null;
		}

		self::$template_block = $template;

		return array(
			'parsed_block' => self::$template_block,
			'context'      => self::$collection_context ?? array(),
		);
	}

	/**
	 * Build block context from product-collection attributes.
	 *
	 * @param array<string, mixed> $attrs Collection block attributes.
	 *
	 * @return array<string, mixed>
	 */
	private static function build_context_from_collection_attrs( array $attrs ): array {
		return array(
			'query'         => $attrs['query'] ?? array(),
			'displayLayout' => $attrs['displayLayout'] ?? array(),
			'queryId'       => $attrs['queryId'] ?? 0,
			'collection'    => $attrs['collection'] ?? '',
		);
	}

	/**
	 * Find block markup that contains a product collection / template.
	 *
	 * @return string
	 */
	private static function resolve_template_content(): string {
		if ( function_exists( 'wc_get_page_id' ) ) {
			$shop_id = wc_get_page_id( 'shop' );
			if ( $shop_id > 0 ) {
				$shop_post = get_post( $shop_id );
				if ( $shop_post instanceof \WP_Post && has_block( 'woocommerce/product-collection', $shop_post ) ) {
					return (string) $shop_post->post_content;
				}
			}
		}

		$template_slugs = self::get_archive_template_slugs();

		foreach ( $template_slugs as $slug ) {
			$content = self::get_template_content_by_slug( $slug );
			if ( $content ) {
				return $content;
			}
		}

		if ( function_exists( 'WC' ) ) {
			$default = WC()->plugin_path() . '/templates/templates/blockified/archive-product.html';
			if ( is_readable( $default ) ) {
				return (string) file_get_contents( $default );
			}
		}

		return '';
	}

	/**
	 * Template slugs to try, most specific first.
	 *
	 * @return array<int, string>
	 */
	private static function get_archive_template_slugs(): array {
		$slugs = array( 'archive-product' );

		if ( is_tax() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				array_unshift(
					$slugs,
					'taxonomy-' . $term->taxonomy . '-' . $term->slug,
					'taxonomy-' . $term->taxonomy,
				);
			}
		}

		return array_values( array_unique( $slugs ) );
	}

	/**
	 * Load template content by slug from DB or theme files.
	 *
	 * @param string $slug Template slug.
	 *
	 * @return string
	 */
	private static function get_template_content_by_slug( string $slug ): string {
		if ( function_exists( 'get_block_templates' ) ) {
			$templates = get_block_templates(
				array(
					'slug__in' => array( $slug ),
				),
				'wp_template',
			);

			if ( ! empty( $templates[0]->content ) ) {
				return (string) $templates[0]->content;
			}
		}

		$theme_paths = array(
			get_stylesheet_directory() . '/templates/' . $slug . '.html',
			get_template_directory() . '/templates/' . $slug . '.html',
		);

		foreach ( $theme_paths as $path ) {
			if ( is_readable( $path ) ) {
				return (string) file_get_contents( $path );
			}
		}

		return '';
	}

	/**
	 * Find a block by name recursively.
	 *
	 * @param array<int, array<string, mixed>> $blocks Block list.
	 * @param string                           $name   Block name.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function find_block( array $blocks, string $name ): ?array {
		foreach ( $blocks as $block ) {
			if ( ( $block['blockName'] ?? '' ) === $name ) {
				return $block;
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$found = self::find_block( $block['innerBlocks'], $name );
				if ( $found ) {
					return $found;
				}
			}
		}

		return null;
	}

	/**
	 * Export serializable interactivity state for AJAX clients.
	 *
	 * @return array{state: array<string, array<string, mixed>>}
	 */
	public static function collect_client_payload(): array {
		$payload = array(
			'state' => array(),
		);

		if ( ! function_exists( 'wp_interactivity_state' ) ) {
			return $payload;
		}

		$products_state = wp_interactivity_state( 'woocommerce/products' );
		if ( ! empty( $products_state['products'] ) && is_array( $products_state['products'] ) ) {
			$payload['state']['woocommerce/products'] = array(
				'products' => $products_state['products'],
			);

			if ( ! empty( $products_state['productVariations'] ) && is_array( $products_state['productVariations'] ) ) {
				$payload['state']['woocommerce/products']['productVariations'] = $products_state['productVariations'];
			}
		}

		return $payload;
	}
}
