<?php

/**
 * Plugin Name: Beplus Fast Product Filter & Live Search for WooCommerce
 * Plugin URI:  https://github.com/ducdung196qtr/beplus-fast-product-filter-live-search-for-woocommerce
 * Description: Fast, smart AJAX product filter & live search for WooCommerce. Instant results, no page reload — more conversions on your shop.
 * Version:     1.0.0
 * Author:      Beplus
 * Author URI:  https://beplusthemes.com/
 * Text Domain: beplus-fast-product-filter-live-search-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html.
 *
 * @package BePlusFastProductFilterLiveSearch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_VERSION', '1.0.0' );
define( 'BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_PLUGIN_DIR . 'includes/helpers.php';
require_once BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_PLUGIN_DIR . 'includes/facets.php';

if ( file_exists( BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_PLUGIN_DIR . 'vendor/autoload.php';
} else {
	spl_autoload_register(
		function ( string $class_name ) {
			$prefix = 'BePlusFastProductFilterLiveSearch\\';
			if ( strncmp( $class_name, $prefix, strlen( $prefix ) ) !== 0 ) {
				return;
			}
			$file = BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_PLUGIN_DIR
				. 'src/'
				. str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) )
				. '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		},
	);
}

/**
 * Boot plugin.
 *
 * @return BePlusFastProductFilterLiveSearch\Core\Plugin
 */
function beplus_fast_product_filter_live_search_boot() {
	static $plugin = null;
	if ( null === $plugin ) {
		$plugin = new BePlusFastProductFilterLiveSearch\Core\Plugin();
		$plugin->boot();
	}
	return $plugin;
}

add_action( 'plugins_loaded', 'beplus_fast_product_filter_live_search_init' );

/**
 * Init on plugins_loaded.
 *
 * @return void
 */
function beplus_fast_product_filter_live_search_init() {
	if ( ! beplus_fast_product_filter_live_search_is_woocommerce_active() ) {
		add_action( 'admin_notices', 'beplus_fast_product_filter_live_search_woocommerce_missing_notice' );
		return;
	}

	beplus_fast_product_filter_live_search_boot();
}

/**
 * Admin notice when WooCommerce is not active.
 *
 * @return void
 */
function beplus_fast_product_filter_live_search_woocommerce_missing_notice(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__(
			'Beplus Fast Product Filter & Live Search for WooCommerce requires WooCommerce to be installed and active.',
			'beplus-fast-product-filter-live-search-for-woocommerce',
		),
	);
}

register_activation_hook( __FILE__, 'beplus_fast_product_filter_live_search_activate' );
register_deactivation_hook( __FILE__, 'beplus_fast_product_filter_live_search_deactivate' );

/**
 * Activation handler.
 *
 * @return void
 */
function beplus_fast_product_filter_live_search_activate() {
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'Beplus Fast Product Filter & Live Search for WooCommerce requires PHP 7.4 or higher.', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
			'Plugin Activation Error',
			array( 'back_link' => true ),
		);
	}

	if ( ! beplus_fast_product_filter_live_search_is_woocommerce_active() ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__(
				'Beplus Fast Product Filter & Live Search for WooCommerce requires WooCommerce to be installed and active. Please activate WooCommerce first, then try again.',
				'beplus-fast-product-filter-live-search-for-woocommerce',
			),
			esc_html__( 'Plugin Activation Error', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
			array( 'back_link' => true ),
		);
	}

	( new BePlusFastProductFilterLiveSearch\Core\Plugin() )->activate();
}

/**
 * Deactivation handler.
 *
 * @return void
 */
function beplus_fast_product_filter_live_search_deactivate() {
	( new BePlusFastProductFilterLiveSearch\Core\Plugin() )->deactivate();
}
