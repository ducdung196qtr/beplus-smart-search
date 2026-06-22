<?php
/**
 * Live Search block render callback.
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

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Block render template variables.

$defaults = array(
	'placeholder'        => __( 'Search products…', 'beplus-smart-search' ),
	'showCategory'       => true,
	'searchScope'        => 'all',
	'limitCategorySlugs' => array(),
	'maxResults'         => 6,
	'debounceMs'         => 280,
	'minChars'           => 2,
	'enableSuggestions'  => true,
	'suggestionLayout' => 'inline',
	'misspellingFix'     => true,
	'exactMatch'         => false,
	'searchLogic'        => 'or',
	'showAddToCart'      => true,
	'showViewAll'        => true,
	'highlightColor'     => '#ffff00',
	'searchFields'       => array( 'title' ),
);

$attrs = wp_parse_args( $attributes, $defaults );

$plugin_settings = beplus_smart_search_get_settings();
$attrs['debounceMs'] = isset( $plugin_settings['debounce_ms'] )
	? (int) $plugin_settings['debounce_ms']
	: (int) $attrs['debounceMs'];
$attrs['minChars'] = isset( $plugin_settings['min_chars'] )
	? (int) $plugin_settings['min_chars']
	: (int) $attrs['minChars'];

if ( ! class_exists( 'WooCommerce' ) ) {
	echo '<p class="beplus-smart-search__notice">' . esc_html__( 'WooCommerce is required for Live Search.', 'beplus-smart-search' ) . '</p>';
	return;
}

$block_id   = 'bpss-ls-' . wp_unique_id();
$input_id   = $block_id . '-input';
$list_id    = $block_id . '-listbox';
$catalog_base_url   = beplus_smart_search_get_catalog_search_base_url();
$needs_post_type    = beplus_smart_search_catalog_search_needs_post_type_arg();
$all_categories = beplus_smart_search_get_product_categories();

$limit_slugs = array_values(
	array_filter(
		array_map( 'sanitize_title', (array) ( $attrs['limitCategorySlugs'] ?? array() ) ),
	),
);
$is_limited  = 'limited' === $attrs['searchScope'] && ! empty( $limit_slugs );

$scope_slugs = $is_limited ? $limit_slugs : array();
$scope_terms = array();

if ( $is_limited ) {
	foreach ( $all_categories as $term ) {
		if ( in_array( $term->slug, $limit_slugs, true ) ) {
			$scope_terms[] = $term;
		}
	}
}

$filter_terms = array();
if ( $attrs['showCategory'] ) {
	$filter_terms = $is_limited ? $scope_terms : $all_categories;
}

$show_category_filter = $attrs['showCategory'] && count( $filter_terms ) > 1;

$sidebar_settings = beplus_smart_search_get_sidebar_settings();
$accent_color     = $sidebar_settings['accent_color'] ?? '#000000';

$search_fields = array_values(
	array_filter(
		array_map(
			'sanitize_key',
			(array) ( $attrs['searchFields'] ?? array( 'title' ) ),
		),
	),
);
if ( empty( $search_fields ) ) {
	$search_fields = array( 'title' );
}

$suggestion_layout = in_array( $attrs['suggestionLayout'] ?? 'inline', array( 'inline', 'tags' ), true )
	? $attrs['suggestionLayout']
	: 'inline';

$wrapper_attrs = get_block_wrapper_attributes(
	array(
		'class'                      => 'beplus-smart-search beplus-smart-search--live-search beplus-smart-search--suggestion-' . $suggestion_layout,
		'style'                      => '--bpss-accent:' . esc_attr( $accent_color ) . ';--bpss-highlight:' . esc_attr( $attrs['highlightColor'] ) . ';',
		'data-bpss-live-search'      => '',
		'data-debounce-ms'           => (string) (int) $attrs['debounceMs'],
		'data-min-chars'             => (string) (int) $attrs['minChars'],
		'data-max-results'           => (string) (int) $attrs['maxResults'],
		'data-enable-suggestions'    => $attrs['enableSuggestions'] ? '1' : '0',
		'data-suggestion-layout'     => esc_attr( $suggestion_layout ),
		'data-misspelling-fix'       => $attrs['misspellingFix'] ? '1' : '0',
		'data-exact-match'           => $attrs['exactMatch'] ? '1' : '0',
		'data-search-logic'          => esc_attr( $attrs['searchLogic'] ),
		'data-show-add-to-cart'      => $attrs['showAddToCart'] ? '1' : '0',
		'data-show-view-all'         => $attrs['showViewAll'] ? '1' : '0',
		'data-shop-url'              => esc_url( $catalog_base_url ),
		'data-catalog-action'        => esc_url( $catalog_base_url ),
		'data-needs-post-type'       => $needs_post_type ? '1' : '0',
		'data-search-scope'          => esc_attr( $is_limited ? 'limited' : 'all' ),
		'data-limit-categories'      => esc_attr( implode( ',', $scope_slugs ) ),
		'data-search-fields'         => esc_attr( implode( ',', $search_fields ) ),
	),
);

?>
<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?>>
	<form
		class="beplus-smart-search__live-form"
		role="search"
		method="get"
		action="<?php echo esc_url( $catalog_base_url ); ?>"
		data-bpss-live-form
		autocomplete="off"
	>
		<?php if ( $needs_post_type ) : ?>
			<input type="hidden" name="post_type" value="product" />
		<?php endif; ?>
		<div class="beplus-smart-search__live-bar">
			<?php if ( $show_category_filter ) : ?>
				<div class="beplus-smart-search__live-category">
					<label class="screen-reader-text" for="<?php echo esc_attr( $block_id ); ?>-cat">
						<?php esc_html_e( 'Category', 'beplus-smart-search' ); ?>
					</label>
					<select
						id="<?php echo esc_attr( $block_id ); ?>-cat"
						name="product_cat"
						class="beplus-smart-search__live-category-select"
						data-bpss-live-category
					>
						<option value="">
							<?php esc_html_e( 'All categories', 'beplus-smart-search' ); ?>
						</option>
						<?php foreach ( $filter_terms as $term ) : ?>
							<option value="<?php echo esc_attr( $term->slug ); ?>">
								<?php echo esc_html( $term->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>

			<div class="beplus-smart-search__live-input-wrap">
				<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>">
					<?php esc_html_e( 'Search products', 'beplus-smart-search' ); ?>
				</label>
				<div class="beplus-smart-search__live-input-stack" data-bpss-live-input-stack>
					<div
						class="beplus-smart-search__live-ghost"
						data-bpss-live-ghost
						aria-hidden="true"
						hidden
					>
						<span class="beplus-smart-search__live-ghost-prefix" data-bpss-live-ghost-prefix></span><span class="beplus-smart-search__live-ghost-suffix" data-bpss-live-ghost-suffix></span>
					</div>
					<input
						type="text"
						inputmode="search"
						enterkeyhint="search"
						id="<?php echo esc_attr( $input_id ); ?>"
						name="bpss_s"
						class="beplus-smart-search__live-input"
						placeholder="<?php echo esc_attr( $attrs['placeholder'] ); ?>"
						autocomplete="off"
						role="combobox"
						aria-autocomplete="inline"
						aria-expanded="false"
						aria-controls="<?php echo esc_attr( $list_id ); ?>"
						data-bpss-live-input
					/>
				</div>
				<button type="submit" class="beplus-smart-search__live-submit" aria-label="<?php esc_attr_e( 'Search', 'beplus-smart-search' ); ?>">
					<span class="beplus-smart-search__live-submit-icon" aria-hidden="true"></span>
				</button>
			</div>
		</div>

		<div
			class="beplus-smart-search__live-dropdown"
			id="<?php echo esc_attr( $list_id ); ?>"
			role="listbox"
			data-bpss-live-dropdown
			hidden
		>
			<div
				class="beplus-smart-search__live-suggestions"
				data-bpss-live-suggestions
				role="listbox"
				aria-label="<?php esc_attr_e( 'Search suggestions', 'beplus-smart-search' ); ?>"
				hidden
			></div>
			<div class="beplus-smart-search__live-products" data-bpss-live-products></div>
			<div class="beplus-smart-search__live-footer" data-bpss-live-footer hidden>
				<a href="<?php echo esc_url( $catalog_base_url ); ?>" class="beplus-smart-search__live-view-all" data-bpss-live-view-all>
					<?php esc_html_e( 'View All Results', 'beplus-smart-search' ); ?>
				</a>
			</div>
		</div>

		<span class="beplus-smart-search__live-status screen-reader-text" role="status" aria-live="polite" data-bpss-live-status></span>
	</form>
</div>
