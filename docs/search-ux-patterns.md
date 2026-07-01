# Search UX patterns

> Frontend and editor patterns for live product search in **Beplus Fast Product Filter & Live Search for WooCommerce**. Use when building blocks, `view.source.ts`, or accessibility behavior.

---

## 1. Data contract (`window.bpssData`)

Localized from PHP via `AssetLoader` / block render:

| Key | Purpose |
|-----|---------|
| `restUrl` | Base REST URL (`beplus-fast-product-filter-live-search-for-woocommerce/v1`) |
| `nonce` | `wp_rest` nonce for authenticated requests |
| `debounceMs` | Delay before live fetch (default `280`) |
| `minChars` | Minimum query length before search (default `2`) |
| `perPage` | Products per page |
| `filterSections` | Editor: section id → label map for Sort tab |
| `attributeDefinitions` | Editor: Woo attribute slug/label list |

Editor-only keys are injected when the block editor loads; storefront uses REST + DOM `data-*` attributes on the block wrapper.

---

## 2. DOM markup contract

Root wrapper (from `render.php`):

```html
<div class="beplus-fast-product-filter-live-search-for-woocommerce beplus-fast-product-filter-live-search-for-woocommerce--inline|sidebar"
     data-bpss-advanced-woo-search
     data-results-mode="filter-collection|own-grid"
     data-results-selector=".wp-block-woocommerce-product-collection"
     data-debounce-ms="280"
     data-min-chars="2"
     data-per-page="12"
     data-live-search="1"
     …>
  <form data-bpss-search-form role="search" …>
    <input data-bpss-filter="keyword" … />
    …
  </form>
</div>
```

| Attribute | Role |
|-----------|------|
| `data-bpss-advanced-woo-search` | JS init root |
| `data-bpss-search-form` | Filter collection target |
| `data-bpss-filter` | Filter type: `keyword`, `category`, `tag`, `attribute`, `stock`, `price`, … |
| `data-bpss-multi="1"` | Multi-select taxonomy/attribute |
| `data-attribute-slug` | Woo attribute slug on attribute filters |

CSS state classes:

- `beplus-fast-product-filter-live-search-for-woocommerce--loading` — fetch in progress
- `beplus-fast-product-filter-live-search-for-woocommerce--ready` — initialized
- BEM: `beplus-fast-product-filter-live-search-for-woocommerce__*` for elements

---

## 3. Live search flow (`view.source.ts`)

1. Read config from root `dataset` + `window.bpssData`.
2. Debounce input/change events (`debounceMs`).
3. `collectFilters(form)` → query params for `GET /beplus-fast-product-filter-live-search-for-woocommerce/v1/products`.
4. Update product grid via **filter-collection** mode (replace inner HTML of `resultsSelector`) or **own-grid** mode.
5. Sync URL query args for shareable filter state (optional).
6. Toggle loading class; handle empty/error states.

Facet counts: `GET /beplus-fast-product-filter-live-search-for-woocommerce/v1/facets` with current filter params.

---

## 4. Editor preview

Use **ServerSideRender** with block `beplus-fast-product-filter-live-search-for-woocommerce/advanced-woo-search` inside the block `edit.tsx` inspector preview. Matches PHP `render.php` output without duplicating markup in JavaScript.

---

## 5. Accessibility (combobox / filters)

- Search input: `role="search"`, visible or screen-reader label.
- Live region for result count / status messages.
- Keyboard: Tab through filters; Enter submits; Escape clears or closes panels.
- Focus visible on filter controls; do not trap focus unless using a modal.
- Checkbox/radio groups: associate `<label>` with inputs; use `fieldset`/`legend` for sidebar panels when appropriate.

See [`.cursor/rules/bpss-a11y-blocks.mdc`](../.cursor/rules/bpss-a11y-blocks.mdc) for block-specific rules.

---

## 6. Extension filters

| Filter | Purpose |
|--------|---------|
| `beplus_fast_product_filter_live_search_rest_products_args` | Modify product query before fetch |
| `beplus-fast-product-filter-live-search-for-woocommerce/search.query` | Modify parsed search query object |
| `beplus-fast-product-filter-live-search-for-woocommerce/search.results` | Modify REST response payload |

---

## 7. Source files

| File | Purpose |
|------|---------|
| `blocks/advanced-woo-search/render.php` | Server markup + `data-*` contract |
| `blocks/advanced-woo-search/view.source.ts` | Storefront filter + fetch logic |
| `blocks/advanced-woo-search/edit.tsx` | Inspector + ServerSideRender preview |
| `blocks/advanced-woo-search/types.ts` | Shared TS types + `bpssData` |
| `includes/render-layouts.php` | Sidebar/inline filter HTML |
| `src/REST/ProductsController.php` | Product REST endpoint |
| `src/REST/FacetsController.php` | Facet counts REST |
| `src/Frontend/ShopQueryIntegration.php` | Shop archive per-page alignment |

Primary block spec: [`advanced-woo-search-block.md`](./advanced-woo-search-block.md).

---

*Update this document when the block markup or REST contract changes.*
