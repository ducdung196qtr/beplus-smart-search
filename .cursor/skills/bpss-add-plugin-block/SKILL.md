---
name: bpss-add-plugin-block
description: Adds or extends a BePlus Smart Search Gutenberg block under blocks/ using block.json, TypeScript, esbuild, render.php. Use when creating blocks, editing BlockRegistry, block.json, render.php, index.tsx, or npm build for beplus-smart-search.
disable-model-invocation: true
---

# BePlus Smart Search — add or change a plugin block

## Before you edit

- Read [`AGENTS.md`](../../../AGENTS.md) § **Gutenberg blocks** and [`Document Plugin.md`](../../../Document Plugin.md) § **Gutenberg Blocks**.
- Read [`docs/advanced-woo-search-block.md`](../../../docs/advanced-woo-search-block.md) when building the primary **Advanced Woo Search** block.
- Read [`docs/spotlight-search-reference.md`](../../../docs/spotlight-search-reference.md) for live search UX, debounce, combobox ARIA, and result rendering patterns from Nextora.
- Registration: [`src/Blocks/BlockRegistry.php`](../../../src/Blocks/BlockRegistry.php) — auto-discovers `blocks/*/block.json`.

## Scaffold

1. Create `blocks/{slug}/` with `block.json`, `index.tsx`, `edit.tsx`, `render.php`, `style.css`.
2. Set `block.json`:
   - `name`: `beplus-smart-search/{slug}`
   - `textdomain`: `beplus-smart-search`
   - `category`: `beplus-smart-search`
   - `render`: `file:./render.php`
3. Register block category in `Plugin::register_block_category()` if not already present.

## Implement — search bar block

1. **Attributes:** `placeholder`, `showIcon`, `maxResults`, `enableLiveSearch`, `minChars`.
2. **render.php:** wrapper with `get_block_wrapper_attributes()`; input + results container markup; escape all output.
3. **block.js:** use `index.tsx` + `edit.tsx` with Inspector panels; run `npm run build:blocks`.
4. **viewScript** (if front-end interactivity outside editor): init autocomplete; idempotent with `data-bpss-search-inited`.
5. **A11y:** combobox pattern — see [`.cursor/rules/bpss-a11y-blocks.mdc`](../../rules/bpss-a11y-blocks.mdc).

## Implement — search results block

1. **Attributes:** `postsPerPage`, `showExcerpt`, `showThumbnail`, `layout` (`list`|`grid`).
2. **render.php:** server-render initial state or placeholder; hydrate via REST if live search enabled.
3. Pagination: REST offset/page param or standard `paged` query arg on results page.

## Styling

- Class prefix: `beplus-smart-search__*`
- Loading: `beplus-smart-search--loading` → `beplus-smart-search--ready`
- Scope styles to block wrapper — avoid global resets

## Build and verify

1. `npm run build` from plugin root.
2. Block inserter: block appears under **BePlus Smart Search** category.
3. Front-end: autocomplete works, keyboard nav, empty/error states, no layout jump on init.

## Reference

| Source | Use for |
|--------|---------|
| `plugins/giftflow/blocks/donation-button/block.json` | block.json structure, render callback |
| `plugins/giftflow/src/Blocks/BlockRegistry.php` | auto-discovery |
| `themes/nextora-develop/blocks/spotlight-search/` | combobox UX, modal/live search (adapt for plugin) |

## Checklist

- [ ] `block.json` uses `beplus-smart-search/` name prefix and textdomain.
- [ ] `render.php` escaped; no raw user input in output.
- [ ] REST used for live search — not ad-hoc SQL in render.
- [ ] `npm run build` run; no hand-edits to `build/**`.
- [ ] A11y: labels, combobox ARIA, focus-visible, reduced motion.
