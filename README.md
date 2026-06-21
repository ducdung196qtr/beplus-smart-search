# BePlus Smart Search

WordPress plugin: **Advanced Woo Search** Gutenberg block for WooCommerce — live filters without page reload.

Built with **PHP 7.4+**, **TypeScript**, **esbuild**, **PHPStan**, and **PHP CS Fixer** (same toolchain pattern as the [Nextora theme](../../themes/nextora-develop/)).

**For AI / Cursor agents:** [`AGENTS.md`](./AGENTS.md) — architecture, naming, hooks, and file conventions.

---

## Requirements

| Tool | Version |
|------|---------|
| WordPress | 6.0+ |
| WooCommerce | Active (for product search) |
| PHP | 7.4+ (8.0+ recommended) |
| Node.js | 20+ (see `.nvmrc`) |
| Composer | Not required globally — use `npm run composer:install` |

---

## Quick start

From `wp-content/plugins/beplus-smart-search/`:

```bash
npm install
npm run composer:install   # PHP dev tools → vendor/
npm run build
```

Activate **BePlus Smart Search** under **Plugins**.

During development:

```bash
npm run watch
```

---

## Composer on Windows / Local WP

You do **not** need a global `composer` command. This repo ships `scripts/composer.mjs`, which downloads `tools/composer.phar` and runs it with PHP.

### Option A — Local site shell (recommended)

1. Open **Local** → select your site → **Open site shell**
2. `cd` to this plugin folder
3. Run:

```bash
npm run composer:install
```

Local’s shell already has `php` on PATH.

### Option B — Set `PHP_BIN` in `.env`

If you use regular PowerShell/CMD:

1. Copy `.env.example` → `.env`
2. Set the full path to `php.exe`, for example:

```env
PHP_BIN=C:\Users\you\AppData\Local\Programs\Local\lightning-services\php-8.2.27+1\bin\win64\php.exe
```

Find `php.exe` under `%LOCALAPPDATA%\Programs\Local\lightning-services\`.

3. Run:

```bash
npm run composer:install
```

### Option C — Install PHP globally

```bash
winget install PHP.PHP.8.2
```

Reopen the terminal, then `npm run composer:install`.

---

## Build pipeline

| Source | Output | Command |
|--------|--------|---------|
| `blocks/*/index.tsx` | `blocks/*/index.js` + `index.asset.php` | `npm run build:blocks` |
| `blocks/*/view.{ts,js}` | `blocks/*/view.js` | `npm run build:blocks` |
| `admin/js/settings.ts` | `admin/js/settings.js` + `settings.asset.php` | `npm run build:admin` |

**Edit source files only** — do not hand-edit compiled `.js` or `*.asset.php`.

```bash
npm run build          # production build (admin + blocks)
npm run watch          # watch mode
npm run typecheck      # TypeScript
npm run lint:php:all   # PHPStan + CS Fixer (dry-run)
npm run lint:php:fix   # auto-fix PHP style
npm run ci             # full gate (TS + PHP + build)
npm run build:package  # distributable ZIP
```

### New block scaffold

```bash
npm run gen -- --name my-block --title "My Block"
npm run build:blocks
```

---

## Project layout

```
beplus-smart-search/
├── beplus-smart-search.php   # Bootstrap
├── src/                      # PSR-4 PHP (BePlusSmartSearch\)
├── blocks/                   # Gutenberg blocks (block.json + TS source)
├── admin/                    # Settings page (PHP views + TS/JS)
├── includes/                 # Procedural helpers
├── scripts/                  # esbuild + composer helper
├── tools/                    # composer.phar (auto-downloaded, gitignored)
└── vendor/                   # Composer dev deps (PHPStan, CS Fixer)
```

Blocks register via `BlockRegistry` — auto-discovers `blocks/*/block.json`.

---

## Git hooks

After `npm install`, Husky runs on pre-commit:

1. `lint-staged` — format staged PHP, typecheck staged TS
2. `npm run lint:php:all`

Skip once: `git commit --no-verify`  
Dry-run: `npm run precommit`

---

## Release ZIP

```bash
npm run build
npm run build:package
```

Creates `beplus-smart-search-v{version}.zip` in the plugin root (excludes `node_modules`, dev config, TS sources).

---

## Docs

| File | Purpose |
|------|---------|
| [`AGENTS.md`](./AGENTS.md) | Agent / contributor briefing |
| [`Document Plugin.md`](./Document Plugin.md) | Architecture & naming standards |
| [`docs/advanced-woo-search-block.md`](./docs/advanced-woo-search-block.md) | Primary block spec |
| [`docs/spotlight-search-reference.md`](./docs/spotlight-search-reference.md) | UX reference from Nextora |

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| `git is not recognized` | Run `npm run setup:git-path`, reopen terminal, or use `npm run git -- status` |
| `composer is not recognized` | Use `npm run composer:install`, not `composer install` |
| `Could not find PHP` | Run `npm run find-php`, set `PHP_BIN` in `.env`, or use Local site shell |
| Block missing in editor | Run `npm run build:blocks`, reload wp-admin |
| Styles/scripts stale | Run `npm run build` or `npm run watch` |
