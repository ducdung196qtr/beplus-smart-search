<?php
/**
 * Plugin Name: BePlus Smart Search
 * Plugin URI:  https://beplusthemes.com/
 * Description: Advanced WooCommerce search block with live filters — no page reload.
 * Version:     1.0.0
 * Author:      Beplus
 * Author URI:  https://beplusthemes.com/
 * Text Domain: beplus-smart-search
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package BePlusSmartSearch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BEPLUS_SMART_SEARCH_VERSION', '1.0.0' );
define( 'BEPLUS_SMART_SEARCH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BEPLUS_SMART_SEARCH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BEPLUS_SMART_SEARCH_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once BEPLUS_SMART_SEARCH_PLUGIN_DIR . 'includes/helpers.php';
require_once BEPLUS_SMART_SEARCH_PLUGIN_DIR . 'includes/facets.php';

$autoload = BEPLUS_SMART_SEARCH_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
} else {
	spl_autoload_register(
		function ( string $class_name ) {
			$prefix = 'BePlusSmartSearch\\';
			if ( strncmp( $class_name, $prefix, strlen( $prefix ) ) !== 0 ) {
				return;
			}
			$file = BEPLUS_SMART_SEARCH_PLUGIN_DIR
				. 'src/'
				. str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) )
				. '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	);
}

/**
 * Boot plugin.
 *
 * @return \BePlusSmartSearch\Core\Plugin
 */
function beplus_smart_search_boot() {
	static $plugin = null;
	if ( null === $plugin ) {
		$plugin = new \BePlusSmartSearch\Core\Plugin();
		$plugin->boot();
	}
	return $plugin;
}

add_action( 'plugins_loaded', 'beplus_smart_search_init' );

/**
 * Init on plugins_loaded.
 *
 * @return void
 */
function beplus_smart_search_init() {
	beplus_smart_search_boot();
}

register_activation_hook( __FILE__, 'beplus_smart_search_activate' );
register_deactivation_hook( __FILE__, 'beplus_smart_search_deactivate' );

/**
 * Activation handler.
 *
 * @return void
 */
function beplus_smart_search_activate() {
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'BePlus Smart Search requires PHP 7.4 or higher.', 'beplus-smart-search' ),
			'Plugin Activation Error',
			array( 'back_link' => true )
		);
	}
	( new \BePlusSmartSearch\Core\Plugin() )->activate();
}

/**
 * Deactivation handler.
 *
 * @return void
 */
function beplus_smart_search_deactivate() {
	( new \BePlusSmartSearch\Core\Plugin() )->deactivate();
}
