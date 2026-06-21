<?php
/**
 * Advanced Woo Search block render callback.
 *
 * @package BePlusSmartSearch
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Block content.
 * @var WP_Block             $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BEPLUS_SMART_SEARCH_PLUGIN_DIR . 'includes/facets.php';
require_once BEPLUS_SMART_SEARCH_PLUGIN_DIR . 'includes/render-layouts.php';

$defaults = array(
	'placeholder'       => __( 'Search products…', 'beplus-smart-search' ),
	'showKeyword'       => true,
	'showCategory'      => true,
	'showTag'           => true,
	'showAttributes'    => true,
	'showStock'         => true,
	'showOnSale'        => false,
	'showRating'        => false,
	'showFeatured'      => false,
	'showBrand'         => true,
	'showCustomTaxonomies' => false,
	'attributeSlugs'    => array(),
	'layout'            => 'inline',
	'resultsMode'       => 'filter-collection',
	'resultsSelector'   => '.wp-block-woocommerce-product-collection',
	'debounceMs'        => 280,
	'minChars'          => 2,
	'perPage'           => 10,
	'enableLiveSearch'  => true,
	'showResultCount'   => true,
	'showClearButton'   => true,
	'showPrice'       => true,
	'filterOrder'     => array(),
);

$attrs = wp_parse_args( $attributes, $defaults );

$plugin_settings = beplus_smart_search_get_settings();
$attrs['perPage']  = beplus_smart_search_get_per_page();
$attrs['debounceMs'] = isset( $plugin_settings['debounce_ms'] )
	? (int) $plugin_settings['debounce_ms']
	: (int) $attrs['debounceMs'];
$attrs['minChars'] = isset( $plugin_settings['min_chars'] )
	? (int) $plugin_settings['min_chars']
	: (int) $attrs['minChars'];

// Backward compatibility: old "stacked" maps to sidebar.
if ( 'stacked' === $attrs['layout'] ) {
	$attrs['layout'] = 'sidebar';
}

if ( ! class_exists( 'WooCommerce' ) ) {
	echo '<p class="beplus-smart-search__notice">' . esc_html__( 'WooCommerce is required for Advanced Woo Search.', 'beplus-smart-search' ) . '</p>';
	return;
}

$block_id    = 'bpss-aws-' . wp_unique_id();
$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$is_sidebar  = 'sidebar' === $attrs['layout'];
$layout_mod  = $is_sidebar ? 'sidebar' : 'inline';
$form_class  = 'beplus-smart-search__form beplus-smart-search__form--' . $layout_mod;

$sidebar_settings = beplus_smart_search_get_sidebar_settings();
$accent_color     = $sidebar_settings['accent_color'] ?? '#f5c518';
$facet_mode       = beplus_smart_search_get_facet_display_mode();
$filter_taxonomies = beplus_smart_search_get_configured_filter_taxonomies();

$wrapper_attrs = get_block_wrapper_attributes(
	array(
		'class'                         => 'beplus-smart-search beplus-smart-search--advanced-woo beplus-smart-search--' . $layout_mod,
		'style'                         => '--bpss-accent:' . esc_attr( $accent_color ) . ';',
		'data-bpss-advanced-woo-search' => '',
		'data-results-mode'             => esc_attr( $attrs['resultsMode'] ),
		'data-results-selector'         => esc_attr( $attrs['resultsSelector'] ),
		'data-debounce-ms'              => (string) (int) $attrs['debounceMs'],
		'data-min-chars'                => (string) (int) $attrs['minChars'],
		'data-per-page'                 => (string) (int) $attrs['perPage'],
		'data-live-search'              => $attrs['enableLiveSearch'] ? '1' : '0',
		'data-show-result-count'        => $attrs['showResultCount'] ? '1' : '0',
		'data-facet-mode'               => esc_attr( $facet_mode ),
		'data-bpss-filter-taxonomies'   => esc_attr( implode( ',', $filter_taxonomies ) ),
	),
);

$categories      = $attrs['showCategory'] ? beplus_smart_search_get_product_categories() : array();
$tags            = $attrs['showTag'] ? beplus_smart_search_get_product_tags() : array();
$enabled_attribute_slugs = beplus_smart_search_get_block_attribute_slugs( $attrs );
$attributes_list         = ! empty( $enabled_attribute_slugs )
	? beplus_smart_search_get_product_attributes( $enabled_attribute_slugs )
	: array();

?>
<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?>>
	<form
		class="<?php echo esc_attr( $form_class ); ?>"
		role="search"
		method="get"
		action="<?php echo esc_url( $shop_url ); ?>"
		data-bpss-search-form
	>
		<?php
		if ( $is_sidebar ) {
			beplus_smart_search_render_sidebar_form( $attrs, $block_id, $categories, $tags, $attributes_list, $sidebar_settings );
		} else {
			beplus_smart_search_render_inline_form( $attrs, $block_id, $categories, $tags, $attributes_list );
		}
		?>

		<div class="beplus-smart-search__actions">
			<button type="submit" class="beplus-smart-search__submit">
				<?php esc_html_e( 'Search', 'beplus-smart-search' ); ?>
			</button>

			<?php if ( $attrs['showClearButton'] ) : ?>
				<button type="button" class="beplus-smart-search__clear" data-bpss-clear hidden>
					<?php esc_html_e( 'Clear', 'beplus-smart-search' ); ?>
				</button>
			<?php endif; ?>

			<span class="beplus-smart-search__status" role="status" aria-live="polite" data-bpss-status hidden></span>
		</div>
	</form>

	<?php if ( 'own-grid' === $attrs['resultsMode'] ) : ?>
		<div class="beplus-smart-search__results" data-bpss-results hidden></div>
	<?php endif; ?>
</div>
