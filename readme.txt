=== BePlus Smart Search ===
Contributors: beplus
Tags: search, woocommerce, filter, gutenberg
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced WooCommerce search block with live filters — no page reload.

== Description ==

BePlus Smart Search adds an **Advanced Woo Search** Gutenberg block for WooCommerce shop pages. Filter products by keyword, category, tag, attributes, and stock status without reloading the page.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/beplus-smart-search/`
2. Activate through the 'Plugins' menu in WordPress
3. Requires WooCommerce to be active
4. In Site Editor, edit the shop template and insert **Advanced Woo Search** above the product collection block

For development setup (Node, Composer, build), see `README.md` in the plugin folder.

== Frequently Asked Questions ==

= Does this work with block themes? =

Yes. The block is designed for blockified WooCommerce shop templates (e.g. Twenty Twenty-Five).

== Changelog ==

= 1.0.0 =
* Initial release with Advanced Woo Search block
* REST API: products and facets endpoints
* Live filtering without page reload
