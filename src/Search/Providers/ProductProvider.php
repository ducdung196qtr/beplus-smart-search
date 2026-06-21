<?php

/**
 * WooCommerce product search provider.
 *
 * @package BePlusSmartSearch
 * @subpackage Search
 */

namespace BePlusSmartSearch\Search\Providers;

use BePlusSmartSearch\Search\ProductMatchMeta;
use BePlusSmartSearch\Search\ProductQueryBuilder;
use BePlusSmartSearch\Search\ProductTemplateRenderer;
use BePlusSmartSearch\Search\SearchQuery;
use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Searches WooCommerce products.
 */
class ProductProvider extends AbstractProvider {

	/**
	 * @return string
	 */
	public function get_id(): string {
		return 'product';
	}

	/**
	 * @return bool
	 */
	public function is_enabled(): bool {
		return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_products' );
	}

	/**
	 * Search products.
	 *
	 * @param SearchQuery $query Search query.
	 *
	 * @return array{items: array<int, array<string, mixed>>, total: int, totalPages: int, page: int, perPage: int}
	 */
	public function search( SearchQuery $query ): array {
		if ( ! $this->is_enabled() ) {
			return $this->empty_result( $query );
		}

		$result = ProductQueryBuilder::query( $query );

		$products   = isset( $result->products ) ? $result->products : array();
		$total      = isset( $result->total ) ? (int) $result->total : 0;
		$total_page = isset( $result->max_num_pages ) ? (int) $result->max_num_pages : 0;

		$items = array();
		foreach ( $products as $product ) {
			if ( $product instanceof WC_Product ) {
				$items[] = $this->normalize_product( $product, $query );
			}
		}

		return array(
			'items'         => $items,
			'total'         => $total,
			'totalPages'    => $total_page,
			'page'          => $query->get_page(),
			'perPage'       => $query->get_per_page(),
			'interactivity' => ProductTemplateRenderer::collect_client_payload(),
		);
	}

	/**
	 * Normalize product to REST item.
	 *
	 * @param WC_Product  $product Product object.
	 * @param SearchQuery $query   Active search query.
	 *
	 * @return array<string, mixed>
	 */
	private function normalize_product( WC_Product $product, SearchQuery $query ): array {
		$image_id  = (int) $product->get_image_id();
		$image_url = $image_id > 0
			? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' )
			: wc_placeholder_img_src( 'woocommerce_thumbnail' );
		$html      = ProductTemplateRenderer::render_product( $product->get_id() );

		return array(
			'id'              => $product->get_id(),
			'title'           => $product->get_name(),
			'url'             => get_permalink( $product->get_id() ),
			'image'           => $image_url ? $image_url : '',
			'price_html'      => $product->get_price_html(),
			'stock_status'    => $product->get_stock_status(),
			'on_sale'         => $product->is_on_sale(),
			'type'            => 'product',
			'product_type'    => $product->get_type(),
			'ajax_add_to_cart' => $product->supports( 'ajax_add_to_cart' ),
			'html'            => $html,
			'match_meta'      => ProductMatchMeta::collect( $product, $query ),
		);
	}

	/**
	 * Empty result set.
	 *
	 * @param SearchQuery $query Search query.
	 *
	 * @return array{items: array<int, array<string, mixed>>, total: int, totalPages: int, page: int, perPage: int}
	 */
	private function empty_result( SearchQuery $query ): array {
		return array(
			'items'         => array(),
			'total'         => 0,
			'totalPages'    => 0,
			'page'          => $query->get_page(),
			'perPage'       => $query->get_per_page(),
			'interactivity' => ProductTemplateRenderer::collect_client_payload(),
		);
	}
}
