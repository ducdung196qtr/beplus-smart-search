# Spotlight Search — Reference for BePlus Smart Search

> Study guide based on the **Nextora** theme implementation (`themes/nextora-develop`). Use this document when building live search, autocomplete, and search UI in **BePlus Smart Search**.

**Theme source docs:** [`themes/nextora-develop/docs/spotlight-search.md`](../../../themes/nextora-develop/docs/spotlight-search.md)

---

## 1. What Spotlight Search Does

Spotlight search is a **header magnifying-glass control** that opens a **modal dialog** with **live, debounced search** over site content. Key behaviors:

| Behavior | Detail |
|----------|--------|
| Progressive enhancement | Form submits to `home_url('/')` with `?s=` if JavaScript is disabled |
| Live search | Debounced `fetch()` to WordPress REST API while typing |
| Request cancellation | `AbortController` aborts stale requests when the user keeps typing |
| Keyboard navigation | ArrowUp/ArrowDown cycles results; Enter opens active URL |
| Modal integration | Focus input on open; clear query and abort fetch on close |
| Accessibility | Combobox ARIA, live status region, translatable strings |
| Extensibility | PHP filters for REST URL, debounce, markup, and modal args |

The theme uses Core's **`wp/v2/search`** endpoint. The plugin should use its own **`beplus-smart-search/v1/search`** endpoint with provider-based results (posts, products, taxonomies).

---

## 2. Architecture Overview

Spotlight search is split into **four layers**. The plugin should mirror this separation:

```
┌─────────────────────────────────────────────────────────────────┐
│  BLOCK / TRIGGER LAYER                                          │
│  blocks/spotlight-search/render.php                             │
│  blocks/header/render.php (searchMode: spotlight)               │
│  → outputs trigger button + modal shell                         │
└──────────────────────────┬──────────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────────┐
│  PHP FEATURE LAYER                                              │
│  inc/features/spotlight-search/                                 │
│    load.php          — bootstrap requires                       │
│    modal-markup.php  — trigger + dialog shell                   │
│    search-ui.php     — form HTML + wp_localize_script           │
│    register-hooks.php — legacy optional hook (deprecated)       │
└──────────────────────────┬──────────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────────┐
│  CLIENT LAYER                                                     │
│  resources/ts/lib/spotlight-search.ts   — bind forms, fetch, UI   │
│  resources/ts/spotlight-search-portal.ts — reparent modal to body │
│  resources/ts/lib/modal.ts              — focus trap, Escape    │
└──────────────────────────┬──────────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────────┐
│  STYLE LAYER                                                      │
│  resources/css/modules/components/spotlight-search.css            │
└───────────────────────────────────────────────────────────────────┘
```

### Plugin equivalent mapping

| Nextora (theme) | BePlus Smart Search (plugin) |
|-----------------|------------------------------|
| `inc/features/spotlight-search/` | `src/Frontend/SearchUI.php` + `templates/partials/search-form.php` |
| `nextora_get_spotlight_search_inner_html()` | `beplus_smart_search_get_search_form_html()` or template loader |
| `nextora_localize_spotlight_search()` | `AssetLoader::localize_frontend()` → `window.bpssData` |
| `resources/ts/lib/spotlight-search.ts` | `assets/js/autocomplete.ts` → `build/frontend.js` |
| `blocks/spotlight-search/` | `blocks/search-bar/` |
| `wp/v2/search` REST | `beplus-smart-search/v1/search` via `SearchController` |
| `window.nextoraSpotlight` | `window.bpssData` |
| `.nextora-spotlight*` CSS | `.beplus-smart-search__*` CSS |
| Theme modal (`data-nextora-modal`) | Plugin-owned modal **or** optional theme modal integration |

---

## 3. Source File Map (Nextora)

| Piece | Path | Role |
|-------|------|------|
| Feature bootstrap | `inc/features/spotlight-search/load.php` | Requires PHP modules |
| Modal shell | `inc/features/spotlight-search/modal-markup.php` | Trigger + dialog HTML |
| Form + localization | `inc/features/spotlight-search/search-ui.php` | Inner form, `wp_localize_script` |
| Standalone block | `blocks/spotlight-search/` | `block.json`, `edit.tsx`, `render.php` |
| Header integration | `blocks/header/render.php` | `searchMode: spotlight` calls same PHP helpers |
| Client behavior | `resources/ts/lib/spotlight-search.ts` | Debounce, fetch, keyboard, render |
| Modal portal | `resources/ts/spotlight-search-portal.ts` | Move modal root to `document.body` |
| Styles | `resources/css/modules/components/spotlight-search.css` | Loading, results, focus |
| Boot order | `resources/ts/main.ts` | Portal → modals → `initSpotlightSearch()` |

---

## 4. PHP Patterns to Adopt

### 4.1 Feature bundle bootstrap

Theme loads a dedicated feature folder from `functions.php`:

```php
// inc/features/spotlight-search/load.php
require_once __DIR__ . '/modal-markup.php';
require_once __DIR__ . '/search-ui.php';
require_once __DIR__ . '/register-hooks.php';
```

**Plugin approach:** Do not use procedural requires. Register a `SearchForm` or `SearchUI` module in `Plugin::boot()` that hooks `wp_enqueue_scripts` and exposes template functions via `src/Functions/templates.php`.

### 4.2 Separate modal shell from search form

Nextora splits concerns:

- **`modal-markup.php`** — trigger button, scrim, dialog surface, close button
- **`search-ui.php`** — reusable inner form (input, results container, hint, empty state)

The inner form is injected into the modal via:

```php
$form_html = nextora_get_spotlight_search_inner_html( $args );
```

**Plugin approach:**

```
templates/
├── search-modal.php       # optional full modal wrapper
├── search-form.php        # inner form only (reusable inline or in modal)
└── partials/
    └── result-item.php    # server-rendered fallback item
```

Expose via `beplus_smart_search_get_template( 'search-form', $args )` with theme override support (`{theme}/beplus-smart-search/search-form.php`).

### 4.3 Args array with defaults + filters

Modal markup uses a defaults array merged through filters:

```php
function nextora_get_header_search_modal_markup_args(): array {
    $defaults = array(
        'modal_id'       => 'nextora-search-modal',
        'title_text'     => __( 'Search', 'nextora' ),
        'open_label'     => __( 'Open search', 'nextora' ),
        'close_label'    => __( 'Close dialog', 'nextora' ),
        'form_aria_label'=> __( 'Search this site', 'nextora' ),
        // ... class strings for styling hooks
    );
    return apply_filters( 'nextora_header_search_modal_markup_args', $defaults );
}
```

**Plugin equivalent:**

```php
function beplus_smart_search_get_form_args(): array {
    $defaults = array(
        'form_id'        => 'bpss-search-form',
        'input_id'       => 'bpss-search-query',
        'placeholder'    => __( 'Search…', 'beplus-smart-search' ),
        'form_aria_label'=> __( 'Search this site', 'beplus-smart-search' ),
        'min_chars_hint' => __( 'Type at least two characters to search.', 'beplus-smart-search' ),
    );
    return apply_filters( 'beplus_smart_search_form_args', $defaults );
}
```

Block attributes merge into args via a dedicated function (`nextora_merge_spotlight_search_block_modal_args()`). Replicate as `SearchForm::merge_block_attributes( array $attributes ): array`.

### 4.4 Script localization (critical)

Theme localizes config onto the main script **after** it is enqueued (priority 25):

```php
function nextora_localize_spotlight_search(): void {
    if ( ! wp_script_is( 'nextora-main', 'enqueued' ) ) {
        return;
    }
    wp_localize_script( 'nextora-main', 'nextoraSpotlight', array(
        'restUrl'        => apply_filters( 'nextora_spotlight_rest_url', rest_url( 'wp/v2/search' ) ),
        'debounceMs'     => (int) apply_filters( 'nextora_spotlight_debounce_ms', 280 ),
        'minQueryLength' => (int) apply_filters( 'nextora_spotlight_min_query_length', 2 ),
        'perPage'        => (int) apply_filters( 'nextora_spotlight_per_page', 12 ),
        'loading'        => __( 'Searching…', 'nextora' ),
        'noResults'      => __( 'No results found.', 'nextora' ),
        'error'          => __( 'Something went wrong. Try again.', 'nextora' ),
        // type labels, keyboardHint ...
    ) );
}
add_action( 'wp_enqueue_scripts', 'nextora_localize_spotlight_search', 25 );
```

**Plugin approach** in `AssetLoader::enqueue_frontend()`:

```php
wp_localize_script( 'beplus-smart-search-frontend', 'bpssData', array(
    'restUrl'        => rest_url( 'beplus-smart-search/v1/' ),
    'debounceMs'     => (int) apply_filters( 'beplus_smart_search_debounce_ms', 280 ),
    'minQueryLength' => (int) apply_filters( 'beplus_smart_search_min_query_length', 2 ),
    'perPage'        => (int) apply_filters( 'beplus_smart_search_per_page', 10 ),
    'nonce'          => wp_create_nonce( 'wp_rest' ),
    'i18n'           => array(
        'loading'   => __( 'Searching…', 'beplus-smart-search' ),
        'noResults' => __( 'No results found.', 'beplus-smart-search' ),
        'error'     => __( 'Something went wrong. Try again.', 'beplus-smart-search' ),
    ),
) );
```

Register equivalent filters in `HookManager`:

```php
public const FILTER_REST_URL         = 'beplus_smart_search.rest_url';
public const FILTER_DEBOUNCE_MS      = 'beplus_smart_search.debounce_ms';
public const FILTER_MIN_QUERY_LENGTH = 'beplus_smart_search.min_query_length';
public const FILTER_PER_PAGE         = 'beplus_smart_search.per_page';
public const FILTER_FORM_HTML        = 'beplus_smart_search.form_html';
```

### 4.5 Progressive enhancement form markup

The inner form from `nextora_get_spotlight_search_inner_html()` demonstrates the contract:

```html
<form
  class="nextora-spotlight"
  role="search"
  method="get"
  action="{home_url}"
  data-nextora-spotlight
>
  <label class="sr-only" for="{input_id}">{form_aria_label}</label>
  <input type="search" name="s" autocomplete="off" … />
  <div role="listbox" data-spotlight-results hidden></div>
  <span role="status" aria-live="polite" data-spotlight-status hidden></span>
  <p data-spotlight-hint>…min chars hint…</p>
  <p data-spotlight-empty hidden role="status"></p>
  <button type="submit" class="sr-only" tabindex="-1">Submit search</button>
</form>
```

**Plugin mapping** — use `data-bpss-search` as root hook and `beplus-smart-search` BEM classes:

| Nextora attribute | Plugin attribute |
|-------------------|------------------|
| `data-nextora-spotlight` | `data-bpss-search` |
| `data-spotlight-results` | `data-bpss-results` |
| `data-spotlight-status` | `data-bpss-status` |
| `data-spotlight-hint` | `data-bpss-hint` |
| `data-spotlight-empty` | `data-bpss-empty` |
| `nextora-spotlight--loading` | `beplus-smart-search--loading` |

Filter to replace entire HTML: `beplus_smart_search.form_html`.

### 4.6 Block render pattern

Standalone block uses **ServerSideRender** in editor and PHP render on front:

```php
// blocks/spotlight-search/render.php
$args = nextora_merge_spotlight_search_block_modal_args( $attributes );
$html = nextora_get_header_search_modal_markup( $args );
echo '<div ' . get_block_wrapper_attributes() . '>' . $html . '</div>';
```

**Plugin block** (`blocks/search-bar/render.php`):

1. Merge block attributes into form/modal args.
2. Output trigger (if modal mode) or inline form.
3. Use `get_block_wrapper_attributes()`.
4. Respect disable filter: `apply_filters( 'beplus_smart_search_show_search', true )`.

### 4.7 Single instance guard

Theme prevents duplicate modals with a static flag in legacy hook and documents: **do not mount header spotlight + standalone block on the same page**.

**Plugin approach:** Use a static `$rendered` flag in `SearchForm` or track modal IDs; warn in admin if multiple modal blocks share the same `modalId`.

---

## 5. JavaScript Patterns to Adopt

### 5.1 Initialization entry point

```typescript
export function initSpotlightSearch(): void {
    const config = window.nextoraSpotlight;
    if (!config?.restUrl) return;

    document.querySelectorAll('[data-nextora-spotlight]').forEach((form) => {
        bindSpotlightForm(form, config);
    });
}
```

**Plugin equivalent:**

```typescript
export function initSmartSearch(): void {
    const config = window.bpssData;
    if (!config?.restUrl) return;

    document.querySelectorAll<HTMLFormElement>('[data-bpss-search]').forEach((form) => {
        bindSearchForm(form, config);
    });
}
```

Call from front-end entry after DOM ready (plugin: `assets/js/frontend.ts`).

### 5.2 Form binding checklist

For each form, query and bind:

| Selector | Purpose |
|----------|---------|
| `input[name="s"]` | Query field |
| `[data-bpss-results]` | Results listbox container |
| `[data-bpss-status]` | Screen reader status (visually hidden) |
| `[data-bpss-hint]` | Min-length hint (hidden during search) |
| `[data-bpss-empty]` | No results / error message |

If `restUrl` is missing, **no-op** — form still works as standard WordPress search.

### 5.3 Debounced fetch with abort

Core pattern from `spotlight-search.ts`:

```typescript
let abort: AbortController | null = null;

const runFetch = async (q: string) => {
    if (q.trim().length < config.minQueryLength) {
        clearResults();
        hintEl?.removeAttribute('hidden');
        return;
    }
    hintEl?.setAttribute('hidden', '');
    abort?.abort();
    abort = new AbortController();

    form.classList.add('beplus-smart-search--loading');
    setStatus(config.i18n.loading);

    const params = new URLSearchParams({ s: q.trim(), per_page: String(config.perPage) });

    try {
        const res = await fetch(`${config.restUrl}search?${params}`, {
            signal: abort.signal,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-WP-Nonce': config.nonce,
            },
        });
        if (!res.ok) throw new Error(String(res.status));
        const data = await res.json();
        renderResults(normalizeResults(data));
    } catch (e) {
        if ((e as Error).name === 'AbortError') return;
        showError(config.i18n.error);
    } finally {
        form.classList.remove('beplus-smart-search--loading');
    }
};

const debouncedFetch = debounce(runFetch, Math.max(80, config.debounceMs));
input.addEventListener('input', () => debouncedFetch(input.value));
```

**Plugin differences:**

- Endpoint: `beplus-smart-search/v1/search?s=…` instead of `wp/v2/search?search=…`
- Response shape: normalize plugin format `{ items: [...] }` in `renderResults()`
- Include `X-WP-Nonce` for authenticated requests if needed

### 5.4 Result rendering

Theme renders from Core search item shape:

```typescript
interface WpSearchItem {
    id: number | string;
    title: string | { rendered?: string };
    url: string;
    type: string;
    subtype: string;
}
```

Each result row includes:

- Title (strip HTML from `title.rendered`)
- Type label (Post / Page / Content)
- Path snippet (truncated URL pathname)
- Decorative icon by subtype

**Plugin normalized item:**

```typescript
interface BpssSearchItem {
    id: number;
    title: string;
    url: string;
    type: string;      // post, product, term
    excerpt?: string;
    thumbnail?: string;
}
```

Render with `<a role="option" aria-selected="false">` inside `<ul role="presentation">` within the listbox container.

### 5.5 Keyboard navigation

When results are visible:

| Key | Action |
|-----|--------|
| ArrowDown | Next option (wrap from -1 to 0) |
| ArrowUp | Previous option (wrap to last) |
| Enter | Navigate to active option URL (prevent form submit) |
| Escape | Close modal (handled by modal layer if present) |

Update `aria-activedescendant` on input when active index changes. Toggle `aria-selected` on options.

### 5.6 Modal lifecycle hooks

When form is inside a modal:

```typescript
const modalRoot = form.closest('[data-bpss-modal]');
modalRoot?.addEventListener('bpss:modalopen', () => {
    requestAnimationFrame(() => { input.focus(); input.select(); });
});
modalRoot?.addEventListener('bpss:modalclose', resetUi);
```

Theme uses `nextora:modalopen` / `nextora:modalclose` from `modal.ts`. Plugin can:

- Ship a lightweight modal in `assets/js/modal.ts`, **or**
- Integrate with Nextora's modal if theme is active (detect `data-nextora-modal` and listen to existing events)

### 5.7 Portal pattern (optional)

Theme moves modal to `document.body` to avoid header `overflow: hidden` clipping:

```typescript
document.querySelectorAll('[data-nextora-spotlight-search-portal]').forEach((el) => {
    if (el.parentElement !== document.body) {
        document.body.appendChild(el);
    }
});
```

Run **before** modal init. Plugin blocks embedded in headers should use the same technique if z-index/stacking issues appear.

---

## 6. CSS Patterns to Adopt

### 6.1 Loading state without extra DOM

Theme toggles icon → spinner via class on form root:

```css
.beplus-smart-search--loading .beplus-smart-search__field-icon-slot--search { display: none; }
.beplus-smart-search--loading .beplus-smart-search__field-icon-slot--loading { display: flex; }
```

Spinner is a CSS `::after` pseudo-element — no extra spinner node in HTML.

### 6.2 Visually hidden status region

```css
.beplus-smart-search__status {
    position: absolute;
    overflow: hidden;
    width: 1px;
    height: 1px;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
}
```

Announce "Searching…" to screen readers without visual clutter.

### 6.3 Focus styles

Use `:focus-visible` on result links — underline, not bare `outline: none`:

```css
.beplus-smart-search__link:focus-visible {
    outline: none;
    text-decoration: underline;
    text-underline-offset: 0.2em;
}
```

### 6.4 Reduced motion

Wrap item entrance animations:

```css
@media (prefers-reduced-motion: reduce) {
    .beplus-smart-search__item {
        animation: none;
        opacity: 1;
    }
}
```

In JS, skip stagger animations when `prefers-reduced-motion: reduce`.

### 6.5 Scoped styling

Theme uses `color-mix()` with `--wp--preset--color--*` fallbacks. Plugin styles should:

- Scope under `.beplus-smart-search`
- Use CSS custom properties plugin controls via settings (`--bpss-primary`, etc.)
- Avoid overriding theme global `input` or `button` rules

---

## 7. Accessibility Contract

Non-negotiable patterns from Nextora spotlight search:

| Requirement | Implementation |
|-------------|----------------|
| Dialog | `role="dialog"`, `aria-modal="true"`, `aria-label` on surface |
| Dialog description | `aria-describedby` → hint element id |
| Search form | `role="search"` on form |
| Field label | Visually hidden `<label for="…">` or `aria-label` |
| Combobox | Input: `aria-controls`, `aria-expanded`, `aria-autocomplete="list"` |
| Active option | `aria-activedescendant` on input when navigating |
| Results | Container `role="listbox"`; links `role="option"` + `aria-selected` |
| Status | `[data-bpss-status]` with `aria-live="polite"`, `aria-atomic="true"` |
| i18n | All `aria-label` strings via `esc_attr__()` — no hard-coded English |
| Keyboard | Arrow keys + Enter; Escape closes modal |
| Single modal | One spotlight/modal instance per page view |

See also [`.cursor/rules/bpss-a11y-blocks.mdc`](../.cursor/rules/bpss-a11y-blocks.mdc).

---

## 8. Extensibility — Filters and Actions

### Theme filters (reference)

| Filter | Purpose |
|--------|---------|
| `nextora_spotlight_rest_url` | Override REST endpoint |
| `nextora_spotlight_debounce_ms` | Debounce delay |
| `nextora_spotlight_min_query_length` | Min chars before search |
| `nextora_spotlight_per_page` | Result count |
| `nextora_spotlight_search_inner_html` | Replace form HTML |
| `nextora_header_search_modal_markup_args` | Modal/trigger args |
| `nextora_header_search_modal_output` | Final modal HTML |
| `nextora_spotlight_search_block_modal_args` | Block attrs → modal args |
| `nextora_show_header_search_modal` | Disable search UI |

### Plugin equivalents (planned)

| Filter / action | Purpose |
|-----------------|---------|
| `beplus_smart_search.rest_url` | Override search REST base |
| `beplus_smart_search.debounce_ms` | Debounce delay |
| `beplus_smart_search.min_query_length` | Min chars |
| `beplus_smart_search.per_page` | Result page size |
| `beplus_smart_search.form_html` | Replace form markup |
| `beplus_smart_search.modal_args` | Modal/trigger args |
| `beplus_smart_search.show_search` | Disable search UI |
| `beplus-smart-search/search.query` | Modify query before search |
| `beplus-smart-search/search.results` | Modify results array |
| `beplus-smart-search/search.completed` | Action after search |

---

## 9. Block Editor Pattern

Nextora block (`nextora/spotlight-search`) uses:

- **ServerSideRender** in editor (disabled wrapper) — WYSIWYG preview from PHP
- **InspectorControls** panels: Settings, Icon color, Labels
- **Attributes:** `modalId`, `titleText`, `showSubtitle`, `openLabel`, `closeLabel`, `formAriaLabel`, `iconColor`
- Empty attribute string = use PHP default (not stored default override)

**Plugin block** (`beplus-smart-search/search-bar`) should add:

| Attribute | Type | Purpose |
|-----------|------|---------|
| `displayMode` | string | `inline` \| `modal` |
| `placeholder` | string | Input placeholder |
| `showIcon` | boolean | Show search icon |
| `maxResults` | number | `per_page` for REST |
| `enableLiveSearch` | boolean | Toggle live vs submit-only |
| `minChars` | number | Override min query length |
| `modalId` | string | Unique modal id (modal mode) |
| `openLabel` | string | Trigger button aria-label |

Merge attributes in PHP render via `SearchForm::merge_block_attributes()`.

---

## 10. Boot Order (Theme vs Plugin)

### Nextora theme (`main.ts`)

```
mountSpotlightSearchPortalToBody()
  → initModals()
  → initSpotlightSearch()
```

Portal **before** modals; search init **after** modals.

### Plugin recommended order

```
initBpssModal()              // if plugin ships modal
  → mountSearchPortalToBody() // if needed
  → initSmartSearch()         // bind all [data-bpss-search] forms
```

Enqueue script in footer (`true` in `wp_enqueue_script`). Localize on same handle at priority after enqueue.

---

## 11. REST API Differences

| Aspect | Nextora (theme) | BePlus Smart Search (plugin) |
|--------|-----------------|------------------------------|
| Endpoint | `wp/v2/search` | `beplus-smart-search/v1/search` |
| Query param | `search=` | `s=` (or align with WP convention) |
| Auth | Public read | Public read; nonce optional |
| Data sources | Core search index only | Provider-based (posts, products, terms) |
| Response | Core `WP_REST_Search_Controller` shape | Normalized `{ items: BpssSearchItem[] }` |
| Extensibility | WP filters on search handlers | `SearchEngine` + provider filter |

Implement `SearchController` following GiftFlow's `SettingsController` pattern. Delegate query execution to `SearchEngine`.

---

## 12. Implementation Checklist for Plugin

### Phase A — PHP foundation

- [ ] `SearchForm` class with `get_inner_html( $args )` and `get_form_args()`
- [ ] `AssetLoader` localizes `bpssData` with REST URL, debounce, i18n strings
- [ ] `HookManager` constants for all search filters
- [ ] Template loader with theme override path
- [ ] Filter `beplus_smart_search.form_html` on output

### Phase B — REST

- [ ] `SearchController` — `GET /search?s=&per_page=`
- [ ] `SearchEngine` orchestrates providers
- [ ] `PostProvider` as first provider
- [ ] Filters on query and results

### Phase C — JavaScript

- [ ] `bindSearchForm()` — debounce, abort, render, keyboard
- [ ] Normalize plugin REST response in `renderResults()`
- [ ] Modal open/close hooks (if modal mode)
- [ ] `prefers-reduced-motion` guard

### Phase D — Block

- [ ] `blocks/search-bar/` with attributes matching §9
- [ ] `edit.tsx` with ServerSideRender + InspectorControls
- [ ] `render.php` outputs form or trigger+modal
- [ ] `style.css` scoped BEM

### Phase E — Polish

- [ ] Single-instance guard for modal mode
- [ ] No-results and error states
- [ ] PHPCS on PHP; build pipeline for JS
- [ ] Manual a11y test: combobox, keyboard, screen reader status

---

## 13. Key Code References (Nextora)

| File | Study for |
|------|-----------|
| `inc/features/spotlight-search/search-ui.php` | Form HTML, localization, block arg merge |
| `inc/features/spotlight-search/modal-markup.php` | Modal shell, trigger, filters |
| `resources/ts/lib/spotlight-search.ts` | Full client implementation |
| `resources/ts/spotlight-search-portal.ts` | Portal to body |
| `blocks/spotlight-search/render.php` | Block render delegation |
| `blocks/spotlight-search/edit.tsx` | Editor inspector pattern |
| `resources/css/modules/components/spotlight-search.css` | Loading, results, motion |

---

## 14. What the Plugin Should Do Differently

1. **Own REST endpoint** — do not depend on `wp/v2/search`; support WooCommerce and custom providers.
2. **No theme.json dependency** — use plugin settings for colors/spacing or inherit from theme via CSS variables.
3. **Optional modal** — support inline search bar (shortcode, widget, block) without requiring theme modal system.
4. **Container architecture** — wire through `AbstractModule` / `AssetLoader`, not procedural `load.php`.
5. **Namespaced hooks** — prefer `beplus-smart-search/search.*` for domain events.
6. **Theme compatibility** — optionally detect Nextora and reuse `data-nextora-modal` events if present.

---

*Reference version: based on Nextora theme spotlight search as of theme path `themes/nextora-develop`. Update when either project changes significantly.*
