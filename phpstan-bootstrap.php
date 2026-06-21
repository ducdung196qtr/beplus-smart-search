<?php

/**
 * Constants for PHPStan analysis.
 *
 * @package BePlusSmartSearch
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'BEPLUS_SMART_SEARCH_VERSION' ) ) {
	define( 'BEPLUS_SMART_SEARCH_VERSION', '1.0.0' );
}

if ( ! defined( 'BEPLUS_SMART_SEARCH_PLUGIN_DIR' ) ) {
	define( 'BEPLUS_SMART_SEARCH_PLUGIN_DIR', __DIR__ . '/' );
}

if ( ! defined( 'BEPLUS_SMART_SEARCH_PLUGIN_URL' ) ) {
	define( 'BEPLUS_SMART_SEARCH_PLUGIN_URL', 'https://example.test/wp-content/plugins/beplus-smart-search/' );
}

if ( ! defined( 'BEPLUS_SMART_SEARCH_PLUGIN_BASENAME' ) ) {
	define( 'BEPLUS_SMART_SEARCH_PLUGIN_BASENAME', 'beplus-smart-search/beplus-smart-search.php' );
}
