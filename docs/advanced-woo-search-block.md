# Advanced Woo Search Block — Build Specification

> Primary feature spec for **Beplus Fast Product Filter & Live Search for WooCommerce**. Defines the `advanced-woo-search` Gutenberg block: live WooCommerce product filtering **without page reload**, droppable into the shop template (`archive-product`).

**Read first (mandatory):**

| Doc / rule / skill | Use for |
|--------------------|---------|
| [`Document Plugin.md`](../Document%20Plugin.md) | Architecture, naming, folder structure, REST, security |
| [`AGENTS.md`](../AGENTS.md) | Module registry, boot flow, quality gates |
| [`docs/search-ux-patterns.md`](./search-ux-patterns.md) | Live search UX: debounce, abort, combobox ARIA, loading states |
| [`.cursor/rules/bpss-core.mdc`](../.cursor/rules/bpss-core.mdc) | Prefix, build output, no side effects |
| [`.cursor/rules/bpss-blocks.mdc`](../.cursor/rules/bpss-blocks.mdc) | block.json, render.php, viewScript |
| [`.cursor/rules/bpss-rest.mdc`](../.cursor/rules/bpss-rest.mdc) | REST routes, permissions, sanitization |
| [`.cursor/rules/bpss-a11y-blocks.mdc`](../.cursor/rules/bpss-a11y-blocks.mdc) | Form labels, live regions, keyboard |
| [`.cursor/skills/bpss-add-plugin-block`](../.cursor/skills/bpss-add-plugin-block/SKILL.md) | Block scaffold workflow |
| [`.cursor/skills/bpss-add-search-provider`](../.cursor/skills/bpss-add-search-provider/SKILL.md) | `ProductProvider`, `SearchEngine` |

**Target placement (verified on `plugin.local`):**

- Template: `archive-product` (Twenty Twenty-Five + WooCommerce blockified)
- Site Editor URL: `site-editor.php?canvas=edit&p=/wp_template/twentytwentyfive//archive-product`
- Existing blocks below search: `woocommerce/product-collection` → `woocommerce/product-template`

---

## 1. Feature summary

| Requirement | Solution |
|-------------|----------|
| Gutenberg block | `beplus-fast-product-filter-live-search-for-woocommerce/advanced-woo-search` |
| Drop into shop page | Compatible with `archive-product` FSE template |
| No page reload | REST `fetch()` + client-side DOM update |
| Keyword search | `s` query param → `WP_Query` / `wc_get_products` |
| Category filter | `product_cat` taxonomy |
| Tag filter | `product_tag` taxonomy |
| Attribute filter | `pa_*` taxonomies (WooCommerce attributes) |
| Stock filter | `instock` / `outofstock` / `onbackorder` |
| Debounced typing | Same pattern as [`search-ux-patterns.md`](./search-ux-patterns.md) (280ms default) |
| WooCommerce required | Block hidden or admin notice if WC inactive |

---

## 2. User experience

### 2.1 Shop page layout (recommended)

Place the block **above** the existing WooCommerce product grid in Site Editor:

```
archive-product template
├── template-part: header
├── main
│   ├── beplus-fast-product-filter-live-search-for-woocommerce/advanced-woo-search   ← NEW (drag here)
│   ├── woocommerce/breadcrumbs                 (optional, can hide via block attr)
│   ├── core/query-title                        (optional)
│   ├── woocommerce/product-results-count     (sync with filtered count)
│   ├── woocommerce/catalog-sorting             (optional)
│   └── woocommerce/product-collection          ← TARGET (filtered in place)
└── template-part: footer
```

### 2.2 Interaction flow (no reload)

```
User changes filter (keyword / category / tag / attribute / stock)
        │
        ▼
JS debounce (keyword only) ──► GET /wp-json/beplus-fast-product-filter-live-search-for-woocommerce/v1/products?...
        │
        ▼
SearchEngine → ProductProvider → WC query
        │
        ▼
JSON: { items[], total, totalPages, facets? }
        │
        ▼
Client updates DOM:
  Mode A (default): replace <li> inside sibling product-collection product-template
  Mode B: render into block-owned results container
        │
        ▼
Update URL with history.replaceState (optional, shareable filters) — NO navigation
```

### 2.3 Progressive enhancement

- Without JS: form `method="get"` submits to shop URL with standard Woo query args (`s`, `product_cat`, etc.) — **full page load fallback**.
- With JS: `preventDefault()` on submit; REST path only.

---

## 3. Block specification

### 3.1 Identity

| Item | Value |
|------|-------|
| Block name | `beplus-fast-product-filter-live-search-for-woocommerce/advanced-woo-search` |
| Title | Advanced Woo Search |
| Category | `beplus-fast-product-filter-live-search-for-woocommerce` |
| Icon | `filter` or `search` |
| Folder | `blocks/advanced-woo-search/` |

### 3.2 block.json attributes

```json
{
  "keyword": { "type": "string", "default": "" },
  "placeholder": { "type": "string", "default": "Search products…" },
  "showKeyword": { "type": "boolean", "default": true },
  "showCategory": { "type": "boolean", "default": true },
  "showTag": { "type": "boolean", "default": true },
  "showAttributes": { "type": "boolean", "default": true },
  "showStock": { "type": "boolean", "default": true },
  "showOnSale": { "type": "boolean", "default": false },
  "attributeSlugs": { "type": "array", "default": [] },
  "layout": { "type": "string", "enum": ["inline", "stacked"], "default": "inline" },
  "resultsMode": { "type": "string", "enum": ["filter-collection", "own-grid"], "default": "filter-collection" },
  "resultsSelector": { "type": "string", "default": ".wp-block-woocommerce-product-collection" },
  "debounceMs": { "type": "number", "default": 280 },
  "minChars": { "type": "number", "default": 2 },
  "perPage": { "type": "number", "default": 10 },
  "enableLiveSearch": { "type": "boolean", "default": true },
  "showResultCount": { "type": "boolean", "default": true },
  "showClearButton": { "type": "boolean", "default": true }
}
```

### 3.3 Editor (block.js / edit.jsx)

Inspector panels (match [`bpss-blocks.mdc`](../.cursor/rules/bpss-blocks.mdc)):

| Panel | Controls |
|-------|----------|
| **Filters** | Toggle each filter field: keyword, category, tag, attributes, stock, on-sale |
| **Attributes** | Multi-select which `pa_*` attributes to expose (load from REST `/facets`) |
| **Layout** | `inline` / `stacked`; placeholder text |
| **Results** | `filter-collection` vs `own-grid`; CSS selector for collection target |
| **Behavior** | `enableLiveSearch`, `debounceMs`, `minChars`, `perPage` |

Use **ServerSideRender** in the block editor preview (`edit.tsx`) so the inspector matches PHP `render.php` output.

### 3.4 render.php markup contract

Root wrapper from `get_block_wrapper_attributes()` with `data-bpss-advanced-woo-search`.

```html
<div class="beplus-fast-product-filter-live-search beplus-fast-product-filter-live-search-for-woocommerce--advanced-woo" data-bpss-advanced-woo-search>
  <form class="beplus-fast-product-filter-live-search-for-woocommerce__form" role="search" method="get"
        action="{shop_permalink}" data-bpss-search-form>

    <!-- Keyword -->
    <div class="beplus-fast-product-filter-live-search-for-woocommerce__field beplus-fast-product-filter-live-search-for-woocommerce__field--keyword">
      <label class="screen-reader-text" for="{id}-keyword">…</label>
      <input type="search" name="s" id="{id}-keyword" … />
    </div>

    <!-- Category -->
    <div class="beplus-fast-product-filter-live-search-for-woocommerce__field beplus-fast-product-filter-live-search-for-woocommerce__field--category">
      <label for="{id}-cat">Category</label>
      <select name="product_cat" id="{id}-cat" data-bpss-filter="category">…</select>
    </div>

    <!-- Tag -->
    <div class="beplus-fast-product-filter-live-search-for-woocommerce__field beplus-fast-product-filter-live-search-for-woocommerce__field--tag">
      <select name="product_tag" data-bpss-filter="tag">…</select>
    </div>

    <!-- Attributes (one select per pa_*) -->
    <div class="beplus-fast-product-filter-live-search-for-woocommerce__field beplus-fast-product-filter-live-search-for-woocommerce__field--attribute"
         data-attribute="{slug}">
      <select name="filter_{slug}" data-bpss-filter="attribute" data-attribute-slug="{slug}">…</select>
    </div>

    <!-- Stock -->
    <div class="beplus-fast-product-filter-live-search-for-woocommerce__field beplus-fast-product-filter-live-search-for-woocommerce__field--stock">
      <select name="stock_status" data-bpss-filter="stock">
        <option value="">All stock</option>
        <option value="instock">In stock</option>
        <option value="outofstock">Out of stock</option>
        <option value="onbackorder">On backorder</option>
      </select>
    </div>

    <button type="submit" class="beplus-fast-product-filter-live-search-for-woocommerce__submit">Search</button>
    <button type="button" class="beplus-fast-product-filter-live-search-for-woocommerce__clear" data-bpss-clear hidden>Clear</button>

    <span class="beplus-fast-product-filter-live-search-for-woocommerce__status" role="status" aria-live="polite"
          data-bpss-status hidden></span>
  </form>

  <!-- own-grid mode only -->
  <div class="beplus-fast-product-filter-live-search-for-woocommerce__results" data-bpss-results hidden></div>
</div>
```

PHP responsibilities in `render.php`:

1. Merge block attributes with defaults.
2. Pre-render `<option>` lists for categories, tags, attributes (cached transients, 1h).
3. Output only enabled filters per block attributes.
4. Escape all output; translatable strings with text domain `beplus-fast-product-filter-live-search-for-woocommerce`.
5. Do **not** run product query in `render.php` for interactive mode.

### 3.5 viewScript (view.js)

File: `blocks/advanced-woo-search/view.js` → built via wp-scripts.

Register in `block.json`:

```json
"viewScript": "file:./view.js",
"style": "file:./style.css"
```

**Init contract** (see [`search-ux-patterns.md`](./search-ux-patterns.md)):

```javascript
// Select [data-bpss-advanced-woo-search] — idempotent via data-bpss-search-inited
// Read window.bpssData (restUrl, nonce, i18n)
// Bind input/change on [data-bpss-filter]
// Debounce keyword; immediate on select change
// AbortController for stale requests
// Toggle beplus-fast-product-filter-live-search-for-woocommerce--loading on form
```

**Results update — `filter-collection` mode (default):**

1. Locate closest `resultsSelector` (default `.wp-block-woocommerce-product-collection`).
2. Find `ul.wc-block-product-template` inside it.
3. Replace `<li class="wc-block-product">` children with rendered cards from REST JSON.
4. Reuse WooCommerce card HTML structure (image, title, price, button) for theme consistency.
5. Update `woocommerce/product-results-count` text if present.
6. Show empty state via `woocommerce/product-collection-no-results` or plugin empty message.

**Results update — `own-grid` mode:**

Render into `[data-bpss-results]` inside the block (simpler MVP fallback).

---

## 4. REST API

### 4.1 Products search endpoint

**Route:** `GET beplus-fast-product-filter-live-search-for-woocommerce/v1/products`

Register in `SearchController` or dedicated `ProductsController` (extends `WP_REST_Controller`).

**Permission:** `__return_true` for read (public catalog); rate-limit if needed.

### 4.2 Query parameters

| Param | Type | Maps to |
|-------|------|---------|
| `s` | string | `WP_Query` `s` / product search |
| `product_cat` | string\|int | `product_cat` term slug or ID |
| `product_tag` | string\|int | `product_tag` term slug or ID |
| `attribute[{slug}]` | string | `tax_query` on `pa_{slug}` |
| `stock_status` | string | `instock`, `outofstock`, `onbackorder` |
| `on_sale` | bool | `_sale_price` meta / `wc_get_product_ids_on_sale()` |
| `min_price` | number | optional phase 2 |
| `max_price` | number | optional phase 2 |
| `orderby` | string | `title`, `price`, `date`, `popularity` |
| `order` | string | `asc`, `desc` |
| `page` | int | pagination |
| `per_page` | int | default from block attr (max 50) |

**Example:**

```
GET /wp-json/beplus-fast-product-filter-live-search-for-woocommerce/v1/products?s=beanie&product_cat=clothing&stock_status=instock&attribute[color]=blue&page=1&per_page=10
```

### 4.3 Response shape

```json
{
  "items": [
    {
      "id": 26,
      "title": "Beanie",
      "url": "http://plugin.local/product/beanie/",
      "image": "http://plugin.local/.../beanie-300x300.jpg",
      "price_html": "<span class=\"woocommerce-Price-amount\">…</span>",
      "stock_status": "instock",
      "on_sale": false,
      "type": "product"
    }
  ],
  "total": 42,
  "totalPages": 5,
  "page": 1,
  "perPage": 10
}
```

Apply filters before return:

```php
apply_filters( 'beplus-fast-product-filter-live-search-for-woocommerce/search.results', $items, $query );
do_action( 'beplus-fast-product-filter-live-search-for-woocommerce/search.completed', $query, $items );
```

### 4.4 Facets endpoint (for editor + dynamic filters)

**Route:** `GET beplus-fast-product-filter-live-search-for-woocommerce/v1/facets`

Returns available categories, tags, attributes, stock options for populating selects:

```json
{
  "categories": [{ "id": 1, "slug": "clothing", "name": "Clothing", "count": 12 }],
  "tags": [],
  "attributes": [
    { "slug": "color", "label": "Color", "terms": [{ "slug": "blue", "name": "Blue" }] }
  ]
}
```

Cache with `wp_cache_set( 'bpfpfls_facets', …, 'beplus_fast_product_filter_live_search', HOUR_IN_SECONDS )`.

---

## 5. PHP domain layer

### 5.1 SearchQuery DTO

`src/Search/SearchQuery.php` — immutable value object:

```php
final class SearchQuery {
    public function get_keyword(): string;
    public function get_product_cat(): string;
    public function get_product_tag(): string;
    /** @return array<string, string> attribute slug => term slug */
    public function get_attributes(): array;
    public function get_stock_status(): string;
    public function is_on_sale(): bool;
    public function get_page(): int;
    public function get_per_page(): int;
    public function get_orderby(): string;
    public function get_order(): string;
}
```

Build from REST request in controller via `SearchQuery::from_rest_request( WP_REST_Request $request )`.

### 5.2 ProductProvider

`src/Search/Providers/ProductProvider.php` — follow [`bpss-add-search-provider`](../.cursor/skills/bpss-add-search-provider/SKILL.md).

```php
class ProductProvider extends AbstractProvider {
    public function get_id(): string { return 'product'; }

    public function is_enabled(): bool {
        return class_exists( 'WooCommerce' );
    }

    public function search( SearchQuery $query ): array {
        // wc_get_products() or WP_Query post_type=product
        // tax_query for cat, tag, pa_*
        // meta_query for stock / on_sale
    }
}
```

**WC query mapping:**

```php
$args = array(
    'status'  => 'publish',
    'limit'   => $query->get_per_page(),
    'page'    => $query->get_page(),
    'paginate' => true,
    'return'  => 'objects',
);

if ( $query->get_keyword() ) {
    $args['s'] = $query->get_keyword();
}

$tax_query = array();

if ( $query->get_product_cat() ) {
    $tax_query[] = array(
        'taxonomy' => 'product_cat',
        'field'    => 'slug',
        'terms'    => $query->get_product_cat(),
    );
}

// product_tag, pa_* attributes similarly

if ( $query->get_stock_status() ) {
    $args['stock_status'] = $query->get_stock_status();
}

if ( $query->is_on_sale() ) {
    $args['on_sale'] = true;
}
```

Normalize each `WC_Product` to REST item shape (title, url, image src, `get_price_html()`, stock).

### 5.3 SearchEngine

`src/Search/SearchEngine.php`:

1. Accept `SearchQuery`.
2. Apply `beplus-fast-product-filter-live-search-for-woocommerce/search.query` filter.
3. Delegate to `ProductProvider` when WooCommerce active.
4. Return paginated result set.

---

## 6. Integration with WooCommerce shop template

### 6.1 Why `filter-collection` mode

The shop template already uses `woocommerce/product-collection` with Interactivity API (`data-wp-interactive="woocommerce/product-collection"`). Replacing the full block is fragile.

**Recommended approach:** Update only the **product list items** (`ul.wc-block-product-template > li`) while leaving the collection wrapper intact.

### 6.2 Selector strategy

```javascript
function findCollectionRoot(formEl, selector) {
  // 1. Next sibling matching selector
  // 2. Closest main → querySelector(selector)
  // 3. document.querySelector(selector) — last resort
}
```

Block attribute `resultsSelector` overridable in editor for custom templates.

### 6.3 Sync with WooCommerce blocks

| Woo block | Sync behavior |
|-----------|---------------|
| `product-results-count` | Update count text: "Showing X of Y results" |
| `catalog-sorting` | On sort change, include `orderby`/`order` in REST call |
| `product-collection` | Replace list items only |
| `query-pagination` | Hide or wire to REST `page` param (phase 2) |

### 6.4 URL state (optional, no reload)

```javascript
const params = new URLSearchParams(window.location.search);
params.set('s', keyword);
params.set('product_cat', category);
history.replaceState(null, '', `${location.pathname}?${params}`);
```

Enables shareable filtered shop URLs without `location.reload()`.

---

## 7. File checklist

### 7.1 New files

```
blocks/advanced-woo-search/
├── block.json
├── block.js              # editor
├── edit.jsx              # InspectorControls (optional split)
├── render.php
├── view.js               # front-end interactivity
└── style.css

src/Search/
├── SearchQuery.php
├── SearchEngine.php
├── SearchRegistry.php
└── Providers/
    └── ProductProvider.php

src/REST/
├── ProductsController.php
└── FacetsController.php

templates/partials/
├── product-card.php      # server fallback card (optional)
└── advanced-woo-search-form.php
```

### 7.2 Files to update

| File | Change |
|------|--------|
| `src/Core/Plugin.php` | Register `ProductsController`, boot `SearchRegistry` |
| `src/Core/HookManager.php` | Add hook constants for product search |
| `src/Core/AssetLoader.php` | Localize `bpssData` with shop URL, i18n strings |
| `src/Blocks/BlockRegistry.php` | Auto-discovers new block folder |
| `src/Settings/SettingsRegistry.php` | Default per-page, debounce, enabled attributes |
| `package.json` | wp-scripts entry for `blocks/advanced-woo-search/view.js` |

---

## 8. Build phases

### Phase 1 — Scaffold (MVP)

- [ ] Plugin bootstrap (`beplus-fast-product-filter-live-search-for-woocommerce.php`, `Plugin`, `Container`, `AbstractModule`)
- [ ] `BlockRegistry` + `blocks/advanced-woo-search/` with static render
- [ ] `ProductProvider` + `GET /products?s=&product_cat=`
- [ ] `view.js`: keyword + category, `filter-collection` mode, debounce, abort
- [ ] `npm run build`; activate plugin; drop block in Site Editor above product-collection
- [ ] Verify: typing filters products **without reload**

### Phase 2 — Full filters

- [ ] Tag + attribute + stock + on-sale filters
- [ ] `GET /facets` for dynamic option lists
- [ ] Clear button; result count sync
- [ ] `own-grid` mode for non-shop pages

### Phase 3 — Editor & polish

- [ ] Full InspectorControls panels
- [ ] ServerSideRender preview
- [ ] `history.replaceState` URL sync
- [ ] Pagination via REST `page`
- [ ] Catalog sorting integration

### Phase 4 — Performance & extensibility

- [ ] Transient cache for facets
- [ ] Optional index table (future, like advanced-woo-search)
- [ ] Filters: `beplus_fast_product_filter_live_search.providers`, `beplus-fast-product-filter-live-search-for-woocommerce/search.query`
- [ ] Admin settings page for global defaults

---

## 9. Testing on `plugin.local`

### 9.1 Site Editor setup

1. Open: `http://plugin.local/wp-admin/site-editor.php?canvas=edit&p=%2Fwp_template%2Ftwentytwentyfive%2F%2Farchive-product`
2. Insert **Advanced Woo Search** block above `woocommerce/product-collection`
3. Set **Results mode** → `Filter existing product collection`
4. Save template

### 9.2 Front-end tests

| Test | Expected |
|------|----------|
| Type "beanie" | Only matching products shown; no reload |
| Select category | Grid filters immediately |
| Select attribute | Grid filters by `pa_*` |
| Stock = In stock | Out-of-stock hidden |
| Clear filters | Full catalog restored |
| Disable JS | GET form submits; page reloads with query args |
| Keyboard | Tab through filters; Enter submits |
| Screen reader | Status region announces result count |

### 9.3 REST tests

```bash
curl "http://plugin.local/wp-json/beplus-fast-product-filter-live-search-for-woocommerce/v1/products?s=beanie&per_page=5"
curl "http://plugin.local/wp-json/beplus-fast-product-filter-live-search-for-woocommerce/v1/facets"
```

---

## 10. Security & coding standards

From [`Document Plugin.md`](../Document%20Plugin.md) and [`.cursor/rules/bpss-php.mdc`](../.cursor/rules/bpss-php.mdc):

- Sanitize all REST params: `sanitize_text_field()`, `absint()`, validate enums for `stock_status`, `orderby`.
- Taxonomy terms: verify term exists with `term_exists()` before query.
- Attribute slugs: whitelist `^pa_[a-z0-9_-]+$`.
- Cap `per_page` at 50 server-side.
- Escape all `render.php` output.
- `$wpdb->prepare()` if raw SQL used (prefer `wc_get_products`).
- Nonce not required for public read; use for admin facets refresh if needed.

---

## 11. Accessibility

From [`.cursor/rules/bpss-a11y-blocks.mdc`](../.cursor/rules/bpss-a11y-blocks.mdc):

- Every `<select>` has `<label>` or `aria-label`.
- Keyword field: `role="search"` on form; combobox pattern if autocomplete dropdown added later.
- `[data-bpss-status]` with `aria-live="polite"` — announce "12 products found".
- Loading: `beplus-fast-product-filter-live-search-for-woocommerce--loading` class; respect `prefers-reduced-motion`.
- Clear button: `aria-label` translatable.
- Focus management: after filter, focus stays on control (no trap).

---

## 11. Comparison with `advanced-woo-search` plugin

The workspace includes `plugins/advanced-woo-search` (AWS). **Do not fork it.** Use as reference only:

| AWS pattern | Beplus Fast Product Filter & Live Search for WooCommerce approach |
|-------------|------------------------------|
| Custom DB index table | Phase 4 optional; MVP uses `wc_get_products` |
| AJAX admin-ajax | REST `beplus-fast-product-filter-live-search-for-woocommerce/v1/products` |
| Shortcode/widget | Gutenberg block only (phase 1) |
| Filter query string | Same tax/meta concepts; our REST schema |
| Pro filter plugins integration | Out of scope MVP; hooks for extension |

---

## 12. Agent workflow (when implementing)

1. Read this doc end-to-end.
2. Follow [`bpss-add-plugin-block`](../.cursor/skills/bpss-add-plugin-block/SKILL.md) for block scaffold.
3. Follow [`bpss-add-search-provider`](../.cursor/skills/bpss-add-search-provider/SKILL.md) for `ProductProvider`.
4. Apply [`.cursor/rules/bpss-rest.mdc`](../.cursor/rules/bpss-rest.mdc) for controllers.
5. Apply [`search-ux-patterns.md`](./search-ux-patterns.md) for `view.js` debounce/abort/loading.
6. Run `npm run build` after JS/CSS changes.
7. Test on shop template per §9.

---

## 13. Related documents index

| Document | Path |
|----------|------|
| Plugin structure | [`Document Plugin.md`](../Document%20Plugin.md) |
| Agent briefing | [`AGENTS.md`](../AGENTS.md) |
| Search UX patterns | [`docs/search-ux-patterns.md`](./search-ux-patterns.md) |
| MCP / Site Editor | [`docs/mcp-setup.md`](./mcp-setup.md) |
| Block rule | [`.cursor/rules/bpss-blocks.mdc`](../.cursor/rules/bpss-blocks.mdc) |
| Add block skill | [`.cursor/skills/bpss-add-plugin-block/SKILL.md`](../.cursor/skills/bpss-add-plugin-block/SKILL.md) |
| Add provider skill | [`.cursor/skills/bpss-add-search-provider/SKILL.md`](../.cursor/skills/bpss-add-search-provider/SKILL.md) |

---

*Spec version: 1.0 — Advanced Woo Search block for archive-product shop template, no page reload.*
