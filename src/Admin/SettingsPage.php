<?php
/**
 * Admin settings page.
 *
 * @package BePlusSmartSearch
 * @subpackage Admin
 */

namespace BePlusSmartSearch\Admin;

use BePlusSmartSearch\Core\AbstractModule;
use BePlusSmartSearch\Settings\SettingsRegistry;

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
	public const MENU_SLUG = 'beplus-smart-search';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'wp_redirect', array( $this, 'preserve_settings_tab' ), 10, 2 );
	}

	/**
	 * Keep active settings tab after save.
	 *
	 * @param string $location Redirect URL.
	 * @param int    $status   HTTP status.
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
	 * Add top-level settings menu.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		$cap = class_exists( 'WooCommerce' ) ? 'manage_woocommerce' : 'manage_options';

		add_menu_page(
			__( 'Smart Search', 'beplus-smart-search' ),
			__( 'Smart Search', 'beplus-smart-search' ),
			$cap,
			self::MENU_SLUG,
			array( $this, 'render_page' ),
			'dashicons-search',
			56
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings', 'beplus-smart-search' ),
			__( 'Settings', 'beplus-smart-search' ),
			$cap,
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin styles on settings page only.
	 *
	 * @param string $hook_suffix Current admin page hook.
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
			$this->version
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
			true
		);
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( class_exists( 'WooCommerce' ) ? 'manage_woocommerce' : 'manage_options' ) ) {
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
					admin_url( 'admin.php?page=' . self::MENU_SLUG )
				)
			);
			exit;
		}

		require $this->plugin_dir . 'admin/views/settings-page.php';
	}
}
