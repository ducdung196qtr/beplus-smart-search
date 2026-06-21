# BePlus Smart Search — agent briefing

Use this file when changing code under `wp-content/plugins/beplus-smart-search/`. **Architecture and naming standards** live in [`Document Plugin.md`](./Document Plugin.md). Reference implementation: [`../giftflow/`](../giftflow/) (Beplus container-based plugin pattern).

## Cursor rules and skills

- **Cursor rules:** [`.cursor/rules/*.mdc`](./.cursor/rules/) — always-on and file-pattern rules for core, PHP, REST, blocks, front-end, and accessibility.
- **Cursor skills:** [`.cursor/skills/`](./.cursor/skills/) — workflows for adding plugin blocks and search providers.

Long-form context stays in this file and in `Document Plugin.md`; avoid duplicating large sections into rules.

## What this plugin is

- **WordPress plugin:** Smart search with autocomplete, live results, and optional WooCommerce integration.
- **Architecture:** Container-based boot via `BePlusSmartSearch\Core\Plugin`; modules extend `AbstractModule` and register hooks in `register()`.
- **Stack:** PHP 7.4+ (8.0+ recommended), PSR-4 autoload under `src/`, **esbuild + TypeScript** for admin/blocks, procedural helpers in `includes/` when needed.
- **Target:** WordPress 6.0+.

## Naming and constants

| Item | Value |
|------|-------|
| Bootstrap file | `beplus-smart-search.php` |
| Text domain | `beplus-smart-search` |
| PHP namespace | `BePlusSmartSearch\` → `src/` |
| Global functions | `beplus_smart_search_*` |
| Constants | `BEPLUS_SMART_SEARCH_*` |
| REST namespace | `beplus-smart-search/v1` |
| Block prefix | `beplus-smart-search/` |
| CSS prefix | `beplus-smart-search` (BEM) |
| DB table prefix | `{wpdb->prefix}bpss_` |

## Files you usually touch

| Area | Edit (source) | Do not edit as source |
|------|----------------|------------------------|
| Bootstrap / activation | `beplus-smart-search.php` | — |
| Core / domain PHP | `src/**/*.php` | — |
| Global helpers | `includes/common.php`, `includes/hooks.php` | — |
| Admin settings JS | `admin/js/settings.ts` | `admin/js/settings.js`, `admin/js/settings.asset.php` |
| Gutenberg blocks | `blocks/<name>/index.tsx`, `edit.tsx`, `view.ts` | `blocks/<name>/index.js`, `index.asset.php`, `view.js` |
| PHP templates | `templates/**` | — |
| Settings / options | `src/Settings/SettingsRegistry.php` | — |
| REST API | `src/REST/*Controller.php` | — |

After changing JS/TS or block sources, run **`npm run build`** (or **`npm run watch`**) from the plugin root.

PHP dev tools: **`npm run composer:install`** (no global Composer required — see [`README.md`](./README.md)).

## PHP load map

```
beplus-smart-search.php
  ├── Constants (BEPLUS_SMART_SEARCH_*)
  ├── Composer / PSR-4 fallback autoload → src/
  ├── beplus_smart_search_boot() → Plugin::boot()
  └── activation / deactivation hooks → Plugin::activate() / deactivate()
```

**Boot order inside `Plugin::boot()`:**

1. `register_core_services()` — container bindings, REST routes
2. `register_services_from_filter()` — `beplus_smart_search.services`
3. `boot_registered_modules()` — call `register()` on each `AbstractModule`
4. `init` — post types, frontend, block category, textdomain

## Module registry (planned)

| Module | Path | Role |
|--------|------|------|
| `AssetLoader` | `src/Core/AssetLoader.php` | Enqueue admin + frontend + block assets |
| `SettingsRegistry` | `src/Settings/SettingsRegistry.php` | Options, defaults, migration |
| `BlockRegistry` | `src/Blocks/BlockRegistry.php` | Auto-discover `blocks/*/block.json` |
| `SearchRegistry` | `src/Search/SearchRegistry.php` | Register search providers |
| `SearchController` | `src/REST/SearchController.php` | Public search REST |
| `SettingsController` | `src/REST/SettingsController.php` | Admin settings REST |

## Gutenberg blocks (`blocks/`)

- **Registration:** `BlockRegistry` scans `blocks/*/block.json` and calls `register_block_type_from_metadata()`.
- **Category:** `beplus-smart-search` (registered in `Plugin::register_block_category()`).
- **Build:** esbuild → `blocks/*/index.js`, `admin/js/settings.js` (see [`README.md`](./README.md)).
- **Planned blocks:** `advanced-woo-search` (primary), `search-bar`, `search-results`.
- **Extension filter:** `beplus_smart_search.blocks`.

## REST API

- **Namespace:** `beplus-smart-search/v1`
- **Search:** `GET /search?s=…` — public read; sanitize query; rate-limit if needed.
- **Settings:** `GET|POST /settings` — `manage_options` capability.
- Localize REST URL + nonce via `wp_localize_script` (`bpssData` object).

## Extensibility hooks

Document all hooks in `src/Core/HookManager.php`:

| Hook | Type | Purpose |
|------|------|---------|
| `beplus_smart_search.services` | filter | Register container services |
| `beplus_smart_search.providers` | filter | Register search providers |
| `beplus_smart_search.blocks` | filter | Register third-party blocks |
| `beplus-smart-search/search.query` | filter | Modify search query |
| `beplus-smart-search/search.results` | filter | Modify result set |
| `beplus-smart-search/search.completed` | action | Fires after search |

## Quality checks (from plugin root)

When tooling is set up:

- `npm install && npm run composer:install` — JS + PHP dev dependencies
- `npm run build` — compile assets
- `npm run lint:php:all` — PHPStan + CS Fixer
- Manual: activate plugin, test search bar block, REST endpoint, admin settings save

## Security baseline

- Every PHP file: `if ( ! defined( 'ABSPATH' ) ) { exit; }`
- Escape output; sanitize input; `$wpdb->prepare()` for SQL
- REST: explicit `permission_callback` per route
- Nonce verification for admin forms and AJAX

## Related projects

- **GiftFlow** (`plugins/giftflow/`) — reference for `Plugin`, `Container`, `AbstractModule`, `BlockRegistry`, `SettingsRegistry`, REST controllers.
- **Nextora theme** (`themes/nextora-develop/`) — reference for block editor UX and spotlight-search interaction patterns (adapt for plugin blocks).

## Feature reference docs

| Doc | Purpose |
|-----|---------|
| [`docs/advanced-woo-search-block.md`](./docs/advanced-woo-search-block.md) | **Primary feature** — Advanced Woo Search block spec (filters, REST, no reload, shop template) |
| [`docs/spotlight-search-reference.md`](./docs/spotlight-search-reference.md) | Deep study of Nextora spotlight search — PHP/JS/CSS patterns to adopt in this plugin |
| [`docs/mcp-setup.md`](./docs/mcp-setup.md) | Connect Cursor MCP to `plugin.local` Site Editor + WordPress Abilities API |
| [`Document Plugin.md`](./Document Plugin.md) | Plugin architecture, naming, directory structure |
