# Beplus Fast Product Filter & Live Search for WooCommerce — Plugin Structure Documentation

> This document defines the architecture standards, naming conventions, and build checklist for the **Beplus Fast Product Filter & Live Search for WooCommerce** plugin.

---

## 1. Plugin Information

| Item | Value |
|------|-------|
| **Display name** | Beplus Fast Product Filter & Live Search for WooCommerce |
| **Directory slug** | `beplus-fast-product-filter-live-search-for-woocommerce` |
| **Bootstrap file** | `beplus-fast-product-filter-live-search-for-woocommerce.php` |
| **Text domain** | `beplus-fast-product-filter-live-search-for-woocommerce` |
| **PHP namespace** | `BePlusFastProductFilterLiveSearch` |
| **Global function prefix** | `beplus_fast_product_filter_live_search_` |
| **Constants prefix** | `BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_` |
| **Hook prefix (legacy WP style)** | `beplus_fast_product_filter_live_search_` |
| **Hook prefix (new, namespaced)** | `beplus-fast-product-filter-live-search-for-woocommerce/` or `beplus_fast_product_filter_live_search.` |
| **REST namespace** | `beplus-fast-product-filter-live-search-for-woocommerce/v1` |
| **Block category** | `beplus-fast-product-filter-live-search-for-woocommerce` |
| **Block name prefix** | `beplus-fast-product-filter-live-search-for-woocommerce/` |
| **Requires WP** | 6.0+ |
| **Requires PHP** | 7.4+ (8.0+ recommended) |

---

## 2. Architecture Overview

This plugin uses a **container-based architecture** — every module registers hooks inside `register()`, with no side effects when files are `require`d.

```
beplus-fast-product-filter-live-search-for-woocommerce.php          ← Bootstrap: constants, autoload, activation hooks
        │
        ▼
BePlusFastProductFilterLiveSearch\Core\Plugin    ← Entry point: boot(), activate(), deactivate()
        │
        ├── Container              ← DI container (lazy singleton)
        ├── AbstractModule         ← Base class for all modules
        │
        ├── AssetLoader            ← Enqueue JS/CSS
        ├── SettingsRegistry       ← Options + defaults
        ├── BlockRegistry          ← Auto-discover blocks/
        ├── SearchRegistry         ← (domain) register search providers
        ├── REST Controllers       ← API for admin/frontend
        └── Services               ← SearchService, IndexService, ...
```

**Core principles:**

1. **Single entry point** — the `Plugin` class boots the entire plugin.
2. **No side effects on file load** — only declare classes/functions; attach hooks in `register()`.
3. **PSR-4 autoload** for all new code in `src/`.
4. **Prefix everything** — avoid conflicts with WordPress core and other plugins.
5. **Every PHP file** starts with `if ( ! defined( 'ABSPATH' ) ) { exit; }`.

---

## 3. Recommended Directory Structure

```
beplus-fast-product-filter-live-search-for-woocommerce/
├── beplus-fast-product-filter-live-search-for-woocommerce.php       # Main plugin file (WordPress reads the header here)
├── readme.txt                    # WordPress.org readme (if publishing)
├── composer.json                 # PSR-4 autoload + dev dependencies
├── package.json                  # wp-scripts / frontend build
├── webpack.mix.js                # (optional) Laravel Mix for legacy assets
├── Document Plugin.md            # This document
│
├── src/                          # ★ New PHP code — PSR-4 autoload
│   ├── Core/
│   │   ├── Plugin.php            # Main bootstrap
│   │   ├── Container.php         # Service container
│   │   ├── AbstractModule.php    # Base module
│   │   ├── AssetLoader.php       # Enqueue scripts/styles
│   │   ├── HookManager.php       # Constants for hooks/filters
│   │   ├── Compat.php            # Backward compatibility
│   │   └── HasSettingsTrait.php  # Shared settings trait
│   │
│   ├── Search/                   # Domain: smart search
│   │   ├── SearchRegistry.php
│   │   ├── SearchEngine.php
│   │   ├── SearchQuery.php
│   │   ├── SearchResult.php
│   │   └── Providers/
│   │       ├── AbstractProvider.php
│   │       ├── PostProvider.php
│   │       ├── ProductProvider.php   # WooCommerce
│   │       └── TaxonomyProvider.php
│   │
│   ├── Index/                    # Domain: index / result cache
│   │   ├── IndexService.php
│   │   └── IndexCron.php
│   │
│   ├── Autocomplete/
│   │   └── AutocompleteService.php
│   │
│   ├── Settings/
│   │   └── SettingsRegistry.php
│   │
│   ├── REST/
│   │   ├── SearchController.php
│   │   └── SettingsController.php
│   │
│   ├── Admin/
│   │   ├── AdminMenu.php
│   │   └── Notices.php
│   │
│   ├── Frontend/
│   │   ├── Shortcodes.php
│   │   ├── SearchForm.php
│   │   └── TemplateLoader.php
│   │
│   ├── Blocks/
│   │   └── BlockRegistry.php
│   │
│   ├── Services/
│   │   └── AnalyticsService.php
│   │
│   └── Functions/
│       ├── helpers.php           # Namespaced wrapper functions
│       └── templates.php
│
├── includes/                     # Procedural / legacy (if backward compat is needed)
│   ├── common.php                # Global helper functions
│   ├── hooks.php                 # Centralized add_action/add_filter
│   └── install.php               # DB tables, default options
│
├── admin/                        # Admin UI (PHP views + React source)
│   ├── includes/
│   │   ├── dashboard.php
│   │   └── settings.php
│   ├── js/
│   │   ├── admin.js              # Admin React entry
│   │   └── components/
│   └── css/
│       └── admin.scss
│
├── assets/                       # Source assets (before build)
│   ├── js/
│   └── css/
│
├── build/                        # wp-scripts output (DO NOT edit by hand)
│   ├── admin.js
│   ├── admin.css
│   ├── admin.asset.php
│   └── blocks/
│
├── blocks/                       # Gutenberg blocks
│   ├── search-bar/
│   │   ├── block.json
│   │   ├── block.js
│   │   ├── render.php
│   │   └── style.css
│   ├── search-results/
│   └── index.js                  # Blocks build entry
│
├── templates/                    # Frontend PHP templates
│   ├── search-form.php
│   ├── search-results.php
│   └── partials/
│       └── result-item.php
│
├── languages/                    # .pot, .po, .mo
│   └── beplus-fast-product-filter-live-search-for-woocommerce.pot
│
└── vendor/                       # Composer autoload (dev)
```

> **Note:** This plugin keeps procedural helpers in `includes/` alongside PSR-4 code in `src/`. Prefer `src/` for new classes; use `includes/` for template/render helpers tied to blocks.

---

## 4. Bootstrap File — `beplus-fast-product-filter-live-search-for-woocommerce.php`

```php
<?php
/**
  * Plugin Name: Beplus Fast Product Filter & Live Search for WooCommerce
 * Plugin URI:  https://beplusthemes.com/
 * Description: Smart search with autocomplete, live results, and WooCommerce integration.
 * Version:     1.0.0
 * Author:      Beplus
 * Author URI:  https://beplusthemes.com/
 * Text Domain: beplus-fast-product-filter-live-search-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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

// Composer autoload (dev) or PSR-4 fallback (production).
$autoload = BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
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
		}
	);
}

/**
 * Boot plugin.
 *
 * @return \BePlusFastProductFilterLiveSearch\Core\Plugin
 */
function beplus_fast_product_filter_live_search_boot() {
	static $plugin = null;
	if ( null === $plugin ) {
		$plugin = new \BePlusFastProductFilterLiveSearch\Core\Plugin();
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
	beplus_fast_product_filter_live_search_boot();
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
			array( 'back_link' => true )
		);
	}
	( new \BePlusFastProductFilterLiveSearch\Core\Plugin() )->activate();
}

/**
 * Deactivation handler.
 *
 * @return void
 */
function beplus_fast_product_filter_live_search_deactivate() {
	( new \BePlusFastProductFilterLiveSearch\Core\Plugin() )->deactivate();
}
```

---

## 5. Naming Conventions

### 5.1 Constants

| Constant | Purpose |
|----------|---------|
| `BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_VERSION` | Plugin version string |
| `BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_PLUGIN_DIR` | Absolute path to plugin root |
| `BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_PLUGIN_URL` | Plugin URL |
| `BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_PLUGIN_BASENAME` | Relative path from `wp-content/plugins/` |

- Always **UPPER_SNAKE_CASE** with the plugin prefix.

### 5.2 Global functions (procedural)

**Pattern:** `{prefix}_{module}_{action}`

**Examples:**

| Function | Purpose |
|----------|---------|
| `beplus_fast_product_filter_live_search_boot()` | Boot plugin container |
| `beplus_fast_product_filter_live_search_init()` | Late init hook |
| `beplus_fast_product_filter_live_search_activate()` | Activation handler |
| `beplus_fast_product_filter_live_search_get_settings()` | Read merged settings |
| `beplus_fast_product_filter_live_search_sanitize_array()` | Recursive array sanitize |
| `beplus_fast_product_filter_live_search_render_result_item()` | Render a search result row |

**Rules:**

- Prefix is always `beplus_fast_product_filter_live_search_`.
- Use action verbs: `get_`, `render_`, `register_`, `process_`, `sanitize_`, `is_`, `has_`.
- Include module name when needed: `beplus_fast_product_filter_live_search_index_rebuild()`.
- Every public function must have full **PHPDoc** with `@param` and `@return`.

### 5.3 Namespaced functions (`src/Functions/`)

Optional namespaced wrappers live in `src/Functions/`:

```php
namespace BePlusFastProductFilterLiveSearch\Functions;

function get_settings(): array {
	return function_exists( 'beplus_fast_product_filter_live_search_get_settings' )
		? beplus_fast_product_filter_live_search_get_settings()
		: array();
}
```

- **camelCase** inside namespaces (PSR-1).
- Global functions remain **snake_case** with prefix.

### 5.4 Class naming

| Type | Convention | Example |
|------|------------|---------|
| Core | PascalCase | `Plugin`, `Container` |
| Abstract base | `Abstract` + name | `AbstractModule`, `AbstractProvider` |
| Interface | name + `Interface` | `ProviderInterface` |
| Registry | name + `Registry` | `SearchRegistry`, `BlockRegistry` |
| REST controller | name + `Controller` | `ProductsController` |
| Service | name + `Service` | `FacetService`, `SearchEngine` |
| Trait | `Has` + name + `Trait` | `HasSettingsTrait` |

**Namespace mapping (PSR-4):**

```
BePlusFastProductFilterLiveSearch\Core\Plugin           → src/Core/Plugin.php
BePlusFastProductFilterLiveSearch\Search\SearchEngine   → src/Search/SearchEngine.php
BePlusFastProductFilterLiveSearch\REST\SearchController → src/REST/SearchController.php
```

### 5.5 File naming

| Location | Convention | Example |
|----------|------------|---------|
| `src/` | PascalCase matching class name | `SearchEngine.php` |
| `includes/` legacy | `class-{name}.php` or `{name}.php` | `class-base.php`, `hooks.php` |
| Templates | descriptive kebab-case | `search-results.php` |
| Blocks folder | kebab-case | `search-bar/block.json` |
| SCSS partial | `_component-name.scss` | `_search-bar.scss` |
| JS component | PascalCase.jsx | `SearchBar.jsx` |
| JS module | kebab-case.js | `autocomplete.js` |

### 5.6 Hooks, Filters, and Actions

The plugin uses **two hook naming styles** — prefer modern `HookManager` constants for new code:

**Modern style (recommended) — dot/slash notation:**

```php
// HookManager.php
public const SEARCH_QUERY     = 'beplus-fast-product-filter-live-search-for-woocommerce/search.query';
public const SEARCH_RESULTS   = 'beplus-fast-product-filter-live-search-for-woocommerce/search.results';
public const FILTER_SERVICES  = 'beplus_fast_product_filter_live_search.services';
public const FILTER_PROVIDERS = 'beplus_fast_product_filter_live_search.providers';
public const CRON_REINDEX     = 'beplus_fast_product_filter_live_search_reindex';
```

**Legacy WordPress style (still used for template hooks):**

```php
do_action( 'beplus_fast_product_filter_live_search_before_search_form', $args );
apply_filters( 'beplus_fast_product_filter_live_search_result_item', $html, $post );
```

**Custom action hooks (domain events):**

```php
do_action( HookManager::SEARCH_COMPLETED, $query, $results );
// → 'beplus-fast-product-filter-live-search-for-woocommerce/search.completed'
```

### 5.7 Options and transients

```php
// Options
'beplus_fast_product_filter_live_search_settings'        // main settings
'beplus_fast_product_filter_live_search_v2_settings'     // if a new settings schema version exists
'beplus_fast_product_filter_live_search_first_activation_notice_dismissed'

// Transients
'beplus_fast_product_filter_live_search_index_status'
'beplus_fast_product_filter_live_search_popular_queries'
```

### 5.8 Database tables

```php
// Prefix: {wpdb->prefix}bpfpfls_
$wpdb->prefix . 'bpfpfls_search_log'
$wpdb->prefix . 'bpfpfls_search_index'
```

- Short table prefix `bpfpfls_` (Beplus Fast Product Filter & Live Search for WooCommerce).
- Create/drop in `activate()` / `uninstall.php`.

### 5.9 Script and style handles

```php
'beplus-fast-product-filter-live-search-for-woocommerce-admin'
'beplus-fast-product-filter-live-search-for-woocommerce-frontend'
'beplus-fast-product-filter-live-search-for-woocommerce-autocomplete'
'beplus-fast-product-filter-live-search-for-woocommerce-block-search-bar'
```

### 5.10 CSS class prefix

```html
<div class="beplus-fast-product-filter-live-search-for-woocommerce beplus-fast-product-filter-live-search-for-woocommerce__form">
```

- BEM blocks: `beplus-fast-product-filter-live-search-for-woocommerce__element--modifier`.

---

## 6. Writing Classes — Standard Patterns

### 6.1 Required PHP file header

```php
<?php
/**
 * Search Engine — orchestrates search providers.
 *
 * @package BePlusFastProductFilterLiveSearch
 * @subpackage Search
 */

namespace BePlusFastProductFilterLiveSearch\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
```

### 6.2 AbstractModule — base for all modules

Standard module base:

```php
namespace BePlusFastProductFilterLiveSearch\Core;

abstract class AbstractModule {

	protected Container $container;
	protected string $version;
	protected string $plugin_dir;
	protected string $plugin_url;

	public function __construct( Container $container ) {
		$this->container  = $container;
		$this->version    = BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_VERSION;
		$this->plugin_dir = BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_PLUGIN_DIR;
		$this->plugin_url = BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_PLUGIN_URL;
	}

	/**
	 * Register WordPress hooks. Called ONCE during boot.
	 */
	abstract public function register(): void;
}
```

**Module rules:**

- Constructor receives `Container`.
- All `add_action()` / `add_filter()` calls live inside `register()`.
- Do not call WordPress APIs at file top level (outside `register()`).

### 6.3 Plugin class — boot flow

```php
namespace BePlusFastProductFilterLiveSearch\Core;

class Plugin {

	private Container $container;

	public function __construct() {
		$this->container = new Container();
	}

	public function boot(): void {
		$this->register_core_services();
		$this->register_services_from_filter();
		$this->boot_registered_modules();

		add_action( 'init', array( $this, 'on_init' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ) );

		$this->register_admin_menus();
		$this->schedule_cron_jobs();
	}

	public function on_init(): void {
		$this->init_frontend();
		$this->init_rest_controllers(); // or use rest_api_init
	}

	public function activate(): void {
		// Create tables, default options, flush rewrite rules.
		flush_rewrite_rules();
	}

	public function deactivate(): void {
		// Clear cron, flush rewrite rules.
		flush_rewrite_rules();
	}

	private function boot_registered_modules(): void {
		$modules = array(
			AssetLoader::class,
			SettingsRegistry::class,
			BlockRegistry::class,
			SearchRegistry::class,
		);

		foreach ( $modules as $class ) {
			$instance = $this->container->get( $class );
			if ( $instance instanceof AbstractModule ) {
				$instance->register();
			}
		}
	}
}
```

### 6.4 Container — dependency injection

The `Container` supports:

- `set( $id, $factory )` — register a factory
- `get( $id )` — lazy-resolve singleton
- `register( array $services )` — bulk register
- Auto-instantiate if not registered: `new $id( $this )`

**Third-party extension filter:**

```php
$services = apply_filters( HookManager::FILTER_SERVICES, array() );
$this->container->register( $services );
```

### 6.5 Abstract Provider (domain pattern)

```php
namespace BePlusFastProductFilterLiveSearch\Search\Providers;

abstract class AbstractProvider {

	abstract public function get_id(): string;
	abstract public function search( SearchQuery $query ): array;

	public function is_enabled(): bool {
		return true;
	}
}
```

### 6.6 REST Controller

```php
namespace BePlusFastProductFilterLiveSearch\REST;

class SearchController extends \WP_REST_Controller {

	public function __construct() {
		$this->namespace = 'beplus-fast-product-filter-live-search-for-woocommerce/v1';
		$this->rest_base = 'search';
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => '__return_true', // or a custom check
					'args'                => $this->get_search_args(),
				),
			)
		);
	}
}
```

Register in `Plugin::register_core_services()`:

```php
add_action( 'rest_api_init', function () {
	( new SearchController() )->register_routes();
	( new SettingsController(
		$this->container->get( SettingsRegistry::class )
	) )->register_routes();
} );
```

### 6.7 SettingsRegistry

```php
namespace BePlusFastProductFilterLiveSearch\Settings;

class SettingsRegistry extends AbstractModule {

	private const OPTION_KEY = 'beplus_fast_product_filter_live_search_settings';

	private const DEFAULTS = array(
		'general' => array(
			'min_chars'       => 2,
			'max_results'     => 10,
			'enable_live'     => true,
		),
		'display' => array(
			'show_thumbnail'  => true,
			'show_excerpt'    => true,
		),
	);

	public function register(): void {
		add_action( 'admin_init', array( $this, 'maybe_migrate_settings' ) );
	}

	public function get_all(): array { /* merge defaults + stored */ }
	public function get_group( string $group ): array { /* ... */ }
	public function update( array $settings ): bool { /* ... */ }
}
```

---

## 7. Gutenberg Blocks

Block structure:

```
blocks/search-bar/
├── block.json      # metadata, attributes, render callback
├── block.js        # editor script (source)
├── render.php      # server-side render
└── style.css       # frontend + editor styles
```

**Sample block.json:**

```json
{
	"$schema": "https://schemas.wp.org/trunk/block.json",
	"apiVersion": 3,
	"name": "beplus-fast-product-filter-live-search-for-woocommerce/search-bar",
	"title": "Search Bar",
	"category": "beplus-fast-product-filter-live-search-for-woocommerce",
	"icon": "search",
	"description": "Smart search bar with live autocomplete.",
	"attributes": {
		"placeholder": { "type": "string", "default": "Search..." },
		"showIcon": { "type": "boolean", "default": true }
	},
	"render": "file:./render.php",
	"editorScript": "beplus-fast-product-filter-live-search-for-woocommerce-block-search-bar",
	"style": "file:./style.css"
}
```

**BlockRegistry** auto-scans `blocks/*/block.json` and calls `register_block_type_from_metadata()`.

Extension filter:

```php
apply_filters( 'beplus_fast_product_filter_live_search.blocks', array() );
```

---

## 8. Assets (JS/CSS)

**AssetLoader** pattern:

- Admin: `build/admin.js` + `build/admin.asset.php` (wp-scripts)
- Frontend: `build/frontend.js`
- Blocks: `enqueue_block_assets` hook
- Legacy fallback: `assets/js/*.bundle.js`

**Localized data:**

```php
wp_localize_script(
	'beplus-fast-product-filter-live-search-for-woocommerce-frontend',
	'bpssData',
	array(
		'restUrl' => rest_url( 'beplus-fast-product-filter-live-search-for-woocommerce/v1/' ),
		'nonce'   => wp_create_nonce( 'wp_rest' ),
	)
);
```

**Build commands (package.json):**

```json
{
	"scripts": {
		"build": "wp-scripts build",
		"start": "wp-scripts start",
		"lint:js": "wp-scripts lint-js",
		"lint:css": "wp-scripts lint-style"
	}
}
```

---

## 9. Templates

```
templates/
├── search-form.php
├── search-results.php
└── partials/
    └── result-item.php
```

**Load template:**

```php
function beplus_fast_product_filter_live_search_get_template( $template_name, $args = array() ) {
	$paths = apply_filters(
		'beplus_fast_product_filter_live_search_template_paths',
		array(
			get_stylesheet_directory() . '/beplus-fast-product-filter-live-search-for-woocommerce/',
			BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_PLUGIN_DIR . 'templates/',
		)
	);
	// locate + load_template()
}
```

Theme override: copy a template to `{theme}/beplus-fast-product-filter-live-search-for-woocommerce/search-form.php`.

---

## 10. composer.json

```json
{
	"name": "beplus/beplus-fast-product-filter-live-search-for-woocommerce",
	"description": "Beplus Fast Product Filter & Live Search for WooCommerce",
	"type": "wordpress-plugin",
	"license": "GPL-2.0-or-later",
	"autoload": {
		"psr-4": {
			"BePlusFastProductFilterLiveSearch\\": "src/"
		}
	},
	"require": {
		"php": ">=7.4"
	},
	"require-dev": {
		"phpcompatibility/phpcompatibility-wp": "*",
		"wp-coding-standards/wpcs": "*"
	}
}
```

---

## 11. Security and WordPress Coding Standards

Every file must follow:

| Rule | Implementation |
|------|----------------|
| Direct access | `if ( ! defined( 'ABSPATH' ) ) { exit; }` |
| Output | `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()` |
| Input | `sanitize_text_field()`, `absint()`, `wp_unslash()` |
| Nonce | `wp_verify_nonce()` for forms/AJAX |
| Capability | `current_user_can( 'manage_options' )` for admin |
| REST | explicit `permission_callback`; do not use `__return_true` for write endpoints |
| SQL | `$wpdb->prepare()` |
| i18n | `__( 'Text', 'beplus-fast-product-filter-live-search-for-woocommerce' )`, `_e()`, `esc_html__()` |

---

## 12. Internationalization (i18n)

- Text domain: `beplus-fast-product-filter-live-search-for-woocommerce`
- Domain Path: `/languages`
- Load in `Plugin::load_textdomain()`:

```php
load_plugin_textdomain(
	'beplus-fast-product-filter-live-search-for-woocommerce',
	false,
	dirname( BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_PLUGIN_BASENAME ) . '/languages'
);
```

- Generate POT: `wp i18n make-pot . languages/beplus-fast-product-filter-live-search-for-woocommerce.pot`

---

## 13. Cron Jobs

```php
// Register
if ( ! wp_next_scheduled( HookManager::CRON_REINDEX ) ) {
	wp_schedule_event( time(), 'daily', HookManager::CRON_REINDEX );
}

// Handler
add_action( HookManager::CRON_REINDEX, array( $index_service, 'rebuild' ) );

// Deactivate: wp_clear_scheduled_hook( HookManager::CRON_REINDEX );
```

---

## 14. New Plugin Build Checklist

### Phase 1 — Scaffold

- [ ] Create `beplus-fast-product-filter-live-search-for-woocommerce/` directory
- [ ] Write `beplus-fast-product-filter-live-search-for-woocommerce.php` with plugin header
- [ ] Define `BEPLUS_FAST_PRODUCT_FILTER_LIVE_SEARCH_*` constants
- [ ] Set up `composer.json` + PSR-4 autoload
- [ ] Create `src/Core/Plugin.php`, `Container.php`, `AbstractModule.php`
- [ ] Create `readme.txt`

### Phase 2 — Core modules

- [ ] `AssetLoader` — enqueue admin + frontend
- [ ] `SettingsRegistry` — options + defaults
- [ ] `HookManager` — document all hooks
- [ ] `includes/common.php` — global helpers
- [ ] `includes/hooks.php` — wire custom actions

### Phase 3 — Domain (Smart Search)

- [ ] `SearchRegistry` + `AbstractProvider`
- [ ] `SearchEngine` — orchestrate providers
- [ ] `PostProvider`, `ProductProvider` (WooCommerce)
- [ ] `AutocompleteService`
- [ ] `IndexService` + cron reindex
- [ ] REST: `SearchController`, `SettingsController`

### Phase 4 — UI

- [ ] Admin dashboard (React + REST)
- [ ] Blocks `search-bar`, `search-results`
- [ ] Shortcode `[beplus_fast_product_filter_live_search]`
- [ ] Frontend templates
- [ ] `package.json` + wp-scripts build

### Phase 5 — Polish

- [ ] Activation: DB tables, default settings, flush rewrites
- [ ] Deactivation: clear cron
- [ ] `uninstall.php`: remove options/tables (if user opts in)
- [ ] PHPCS / WPCS lint
- [ ] i18n POT file
- [ ] Admin notices (first activation)
- [ ] Extensibility filters documented

---

## 15. Core class map

| Class | Path | Role |
|-------|------|------|
| `BePlusFastProductFilterLiveSearch\Core\Plugin` | `src/Core/Plugin.php` | Boot, activate, deactivate |
| `SearchRegistry` | `src/Search/SearchRegistry.php` | Register search providers |
| `AbstractProvider` | `src/Search/Providers/AbstractProvider.php` | Provider base |
| `FacetService` | `src/Search/FacetService.php` | Facet counts |
| `SettingsRegistry` | `src/Settings/SettingsRegistry.php` | Options + defaults |
| `BlockRegistry` | `src/Blocks/BlockRegistry.php` | Auto-discover blocks |
| `ProductsController` | `src/REST/ProductsController.php` | `GET /products` |
| `FacetsController` | `src/REST/FacetsController.php` | `GET /facets` |
| REST namespace | `beplus-fast-product-filter-live-search-for-woocommerce/v1` | Public API |
| Services filter | `beplus_fast_product_filter_live_search.services` | Container extensions |
| Search completed action | `beplus-fast-product-filter-live-search-for-woocommerce/search.completed` | After search runs |
| Primary block | `blocks/advanced-woo-search/` | Advanced Woo Search |

---

## 16. Third-Party Extension Example

```php
add_filter( 'beplus_fast_product_filter_live_search.services', function ( $services ) {
	$services[ \MyPlugin\CustomSearchProvider::class ] = function ( $c ) {
		return new \MyPlugin\CustomSearchProvider( $c );
	};
	return $services;
} );

add_filter( 'beplus_fast_product_filter_live_search.providers', function ( $providers ) {
	$providers['my_custom'] = \MyPlugin\CustomSearchProvider::class;
	return $providers;
} );
```

---

## 17. Advanced Woo Search Block (primary feature)

Before building the main plugin feature, read:

**[`docs/advanced-woo-search-block.md`](./docs/advanced-woo-search-block.md)**

That document specifies the `beplus-fast-product-filter-live-search-for-woocommerce/advanced-woo-search` block: WooCommerce filters (keyword, category, tag, attribute, stock), REST `/products`, no page reload on the shop `archive-product` template, and integration with `woocommerce/product-collection`.

---

## 18. Search UX patterns

Before building live autocomplete or front-end filter behavior, read:

**[`docs/search-ux-patterns.md`](./docs/search-ux-patterns.md)**

That document covers `window.bpssData`, DOM `data-*` contracts, debounced REST fetch, combobox accessibility, and the Advanced Woo Search block flow.

---

## 19. Internal reference files

When implementing, read these plugin files directly:

| File | Purpose |
|------|---------|
| `beplus-fast-product-filter-live-search-for-woocommerce.php` | Bootstrap pattern |
| `src/Core/Plugin.php` | Boot flow, activate/deactivate |
| `src/Core/Container.php` | DI container |
| `src/Core/AbstractModule.php` | Module base |
| `src/Core/AssetLoader.php` | Asset enqueue |
| `src/Settings/SettingsRegistry.php` | Settings pattern |
| `src/Blocks/BlockRegistry.php` | Block auto-discovery |
| `src/REST/ProductsController.php` | Product REST API |
| `src/REST/FacetsController.php` | Facet REST API |
| `blocks/advanced-woo-search/block.json` | Primary block metadata |
| `blocks/advanced-woo-search/view.source.ts` | Storefront filter JS |

---

*This document is the initial blueprint. Update it as the plugin grows with new modules.*
