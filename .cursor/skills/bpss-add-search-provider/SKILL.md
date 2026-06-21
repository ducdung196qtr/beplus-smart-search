---
name: bpss-add-search-provider
description: Adds a new search provider to BePlus Smart Search by extending AbstractProvider and registering via SearchRegistry and beplus_smart_search.providers filter. Use when adding post type search, WooCommerce products, taxonomies, or custom data sources.
disable-model-invocation: true
---

# BePlus Smart Search — add a search provider

## Before you edit

- Read [`Document Plugin.md`](../../../Document Plugin.md) § **Abstract Provider** and **SearchRegistry**.
- Reference GiftFlow gateway pattern: `giftflow/src/Gateways/AbstractGateway.php`, `GatewayRegistry.php`.

## Provider contract

Create `src/Search/Providers/{Name}Provider.php`:

```php
namespace BePlusSmartSearch\Search\Providers;

abstract class AbstractProvider {
	abstract public function get_id(): string;
	abstract public function search( SearchQuery $query ): array;
	public function is_enabled(): bool { return true; }
}
```

Each provider returns normalized items:

```php
array(
  'id'      => (int),
  'title'   => (string),
  'url'     => (string),
  'type'    => (string),  // post, product, term, custom
  'excerpt' => (string),  // optional
  'score'   => (float),   // optional, for ranking
)
```

## Implement a provider

1. Extend `AbstractProvider`.
2. Implement `get_id()` — unique slug, e.g. `post`, `product`, `taxonomy`.
3. Implement `search( SearchQuery $query )`:
   - Respect `$query->get_term()`, `$query->get_limit()`, `$query->get_post_types()`.
   - Use `WP_Query`, `wc_get_products()`, or `$wpdb->prepare()` — never unescaped user input in SQL.
   - Return array of normalized results.
4. Override `is_enabled()` if provider depends on WooCommerce or another plugin.

## Register the provider

**Core registration** in `SearchRegistry::register()`:

```php
$this->providers['post'] = PostProvider::class;
```

**Third-party extension** via filter:

```php
add_filter( 'beplus_smart_search.providers', function ( $providers ) {
	$providers['my_custom'] = \MyPlugin\CustomProvider::class;
	return $providers;
} );
```

Also register in container if the provider needs DI:

```php
add_filter( 'beplus_smart_search.services', function ( $services ) {
	$services[ CustomProvider::class ] = fn( $c ) => new CustomProvider( $c );
	return $services;
} );
```

## SearchEngine orchestration

`SearchEngine` should:

1. Load enabled providers from `SearchRegistry`.
2. Run searches (parallel or sequential).
3. Merge and sort by score/relevance.
4. Apply filters: `beplus-smart-search/search.query`, `beplus-smart-search/search.results`.
5. Fire action: `beplus-smart-search/search.completed`.

## Settings integration

Add provider toggle in `SettingsRegistry` defaults:

```php
'general' => array(
	'providers' => array( 'post', 'product', 'taxonomy' ),
),
```

Admin UI reads/writes via `SettingsController` REST.

## Built-in providers (planned)

| Provider | Class | Data source |
|----------|-------|-------------|
| Posts & pages | `PostProvider` | `WP_Query` |
| Products | `ProductProvider` | WooCommerce (if active) |
| Taxonomies | `TaxonomyProvider` | `get_terms()` |

## Checklist

- [ ] Provider extends `AbstractProvider`; unique `get_id()`.
- [ ] Input sanitized; SQL uses `$wpdb->prepare()`.
- [ ] Results normalized to shared shape.
- [ ] Registered in `SearchRegistry` or `beplus_smart_search.providers` filter.
- [ ] Settings toggle added if user-configurable.
- [ ] REST `/search` returns merged provider results.
