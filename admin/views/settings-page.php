<?php
/**
 * Smart Search settings admin view.
 *
 * @package BePlusSmartSearch
 *
 * @var array<string, mixed> $settings Plugin settings.
 * @var array<string, mixed> $sidebar  Sidebar settings.
 * @var string               $tab      Active tab slug.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$option_key            = \BePlusSmartSearch\Settings\SettingsRegistry::OPTION_KEY;
$modes                 = isset( $sidebar['taxonomy_modes'] ) && is_array( $sidebar['taxonomy_modes'] ) ? $sidebar['taxonomy_modes'] : array();
$sub_modes             = isset( $sidebar['taxonomy_sub_modes'] ) && is_array( $sidebar['taxonomy_sub_modes'] ) ? $sidebar['taxonomy_sub_modes'] : array();
$price                 = beplus_smart_search_get_price_settings();
$price_display         = $price['display'] ?? 'range';
$price_segments        = isset( $price['segments'] ) && is_array( $price['segments'] ) ? $price['segments'] : array();
$accent_color          = $sidebar['accent_color'] ?? '#f5c518';
$facet_display_mode    = $sidebar['facet_display_mode'] ?? 'all';
$facets                = isset( $sidebar['facets'] ) && is_array( $sidebar['facets'] ) ? $sidebar['facets'] : array();
$brand_facet           = isset( $facets['brand'] ) && is_array( $facets['brand'] ) ? $facets['brand'] : array();
$custom_taxonomies     = isset( $facets['custom_taxonomies'] ) && is_array( $facets['custom_taxonomies'] ) ? $facets['custom_taxonomies'] : array();
$attribute_definitions = function_exists( 'beplus_smart_search_get_all_attribute_definitions' )
	? beplus_smart_search_get_all_attribute_definitions()
	: array();
$brand_taxonomy        = function_exists( 'beplus_smart_search_get_brand_taxonomy' )
	? beplus_smart_search_get_brand_taxonomy()
	: 'product_brand';
$selectable_taxonomies = function_exists( 'beplus_smart_search_get_selectable_product_taxonomies' )
	? beplus_smart_search_get_selectable_product_taxonomies()
	: array();
$menu_slug             = \BePlusSmartSearch\Admin\SettingsPage::MENU_SLUG;

// Redirect legacy tab slugs.
if ( in_array( $tab, array( 'taxonomies', 'price', 'sidebar' ), true ) ) {
	$tab = 'filters';
}

$tabs = array(
	'general' => __( 'General', 'beplus-smart-search' ),
	'filters' => __( 'Filters', 'beplus-smart-search' ),
);

if ( ! isset( $tabs[ $tab ] ) ) {
	$tab = 'general';
}

$base_url = admin_url( 'admin.php?page=' . $menu_slug );

/**
 * Render selection mode + sub taxonomy controls for a filter group.
 *
 * @param string $option_key   Settings option key.
 * @param string $mode_name    Field name for mode select.
 * @param string $sub_name     Field name for sub checkbox.
 * @param string $current_mode Current mode value.
 * @param bool   $show_sub     Whether sub toggle is checked.
 * @return void
 */
function bpss_render_filter_mode_controls( string $option_key, string $mode_name, string $sub_name, string $current_mode, bool $show_sub ): void {
	?>
	<div class="bpss-filter-row__controls">
		<label class="bpss-filter-row__field">
			<span class="bpss-filter-row__label"><?php esc_html_e( 'Selection', 'beplus-smart-search' ); ?></span>
			<select name="<?php echo esc_attr( $option_key . $mode_name ); ?>">
				<option value="radio" <?php selected( $current_mode, 'radio' ); ?>><?php esc_html_e( 'Single (radio)', 'beplus-smart-search' ); ?></option>
				<option value="checkbox" <?php selected( $current_mode, 'checkbox' ); ?>><?php esc_html_e( 'Multiple (checkbox)', 'beplus-smart-search' ); ?></option>
			</select>
		</label>
		<label class="bpss-filter-row__checkbox">
			<input type="checkbox" name="<?php echo esc_attr( $option_key . $sub_name ); ?>" value="1" <?php checked( $show_sub ); ?> />
			<?php esc_html_e( 'Show sub-items with expand/collapse', 'beplus-smart-search' ); ?>
		</label>
	</div>
	<?php
}
?>
<div class="wrap bpss-settings">
	<h1><?php esc_html_e( 'Smart Search Settings', 'beplus-smart-search' ); ?></h1>
	<p class="description bpss-settings__intro">
		<?php esc_html_e( 'Configure search behavior and how each product filter works on the storefront.', 'beplus-smart-search' ); ?>
	</p>

	<nav class="bpss-settings__tabs" aria-label="<?php esc_attr_e( 'Settings sections', 'beplus-smart-search' ); ?>">
		<?php foreach ( $tabs as $slug => $label ) : ?>
			<a
				href="<?php echo esc_url( add_query_arg( 'tab', $slug, $base_url ) ); ?>"
				class="bpss-settings__tab<?php echo $tab === $slug ? ' is-active' : ''; ?>"
				data-tab="<?php echo esc_attr( $slug ); ?>"
			>
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<form method="post" action="options.php" class="bpss-settings__form">
		<?php settings_fields( 'beplus_smart_search' ); ?>
		<input type="hidden" name="bpss_active_tab" value="<?php echo esc_attr( $tab ); ?>" />

		<div class="bpss-settings__panel<?php echo 'general' === $tab ? ' is-active' : ''; ?>" data-tab-panel="general">
			<div class="bpss-settings__card">
				<h2 class="bpss-settings__card-title"><?php esc_html_e( 'Search behavior', 'beplus-smart-search' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="bpss-debounce"><?php esc_html_e( 'Debounce (ms)', 'beplus-smart-search' ); ?></label></th>
						<td><input type="number" id="bpss-debounce" name="<?php echo esc_attr( $option_key ); ?>[debounce_ms]" value="<?php echo esc_attr( (string) ( $settings['debounce_ms'] ?? 280 ) ); ?>" min="0" max="2000" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="bpss-min-chars"><?php esc_html_e( 'Min characters', 'beplus-smart-search' ); ?></label></th>
						<td><input type="number" id="bpss-min-chars" name="<?php echo esc_attr( $option_key ); ?>[min_chars]" value="<?php echo esc_attr( (string) ( $settings['min_chars'] ?? 2 ) ); ?>" min="0" max="10" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="bpss-per-page"><?php esc_html_e( 'Results per page', 'beplus-smart-search' ); ?></label></th>
						<td><input type="number" id="bpss-per-page" name="<?php echo esc_attr( $option_key ); ?>[per_page]" value="<?php echo esc_attr( (string) ( $settings['per_page'] ?? 10 ) ); ?>" min="1" max="50" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Cache facets', 'beplus-smart-search' ); ?></th>
						<td>
							<label class="bpss-filter-row__checkbox">
								<input type="checkbox" name="<?php echo esc_attr( $option_key ); ?>[enable_cache]" value="1" <?php checked( ! empty( $settings['enable_cache'] ) ); ?> />
								<?php esc_html_e( 'Cache facet lists when no filters are active', 'beplus-smart-search' ); ?>
							</label>
						</td>
					</tr>
				</table>
			</div>

			<div class="bpss-settings__card">
				<h2 class="bpss-settings__card-title"><?php esc_html_e( 'Filter options display', 'beplus-smart-search' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Control how filter choices appear when customers combine multiple filters.', 'beplus-smart-search' ); ?></p>
				<fieldset class="bpss-settings__choice-list">
					<label class="bpss-settings__choice">
						<input type="radio" name="<?php echo esc_attr( $option_key ); ?>[sidebar][facet_display_mode]" value="all" <?php checked( $facet_display_mode, 'all' ); ?> />
						<span class="bpss-settings__choice-text">
							<strong><?php esc_html_e( 'Show all options', 'beplus-smart-search' ); ?></strong>
							<span><?php esc_html_e( 'Always display every category, tag, and attribute term.', 'beplus-smart-search' ); ?></span>
						</span>
					</label>
					<label class="bpss-settings__choice">
						<input type="radio" name="<?php echo esc_attr( $option_key ); ?>[sidebar][facet_display_mode]" value="contextual" <?php checked( $facet_display_mode, 'contextual' ); ?> />
						<span class="bpss-settings__choice-text">
							<strong><?php esc_html_e( 'Contextual', 'beplus-smart-search' ); ?></strong>
							<span><?php esc_html_e( 'Hide options that would return zero products with the current selection.', 'beplus-smart-search' ); ?></span>
						</span>
					</label>
				</fieldset>
			</div>
		</div>

		<div class="bpss-settings__panel<?php echo 'filters' === $tab ? ' is-active' : ''; ?>" data-tab-panel="filters">
			<p class="bpss-settings__hint">
				<?php esc_html_e( 'Configure how each filter behaves here. Turn individual filters on or off per block in the editor (Advanced Woo Search → Filters panel).', 'beplus-smart-search' ); ?>
			</p>

			<div class="bpss-settings__card">
				<h2 class="bpss-settings__card-title"><?php esc_html_e( 'Sidebar layout', 'beplus-smart-search' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Display options', 'beplus-smart-search' ); ?></th>
						<td>
							<label class="bpss-filter-row__checkbox"><input type="checkbox" name="<?php echo esc_attr( $option_key ); ?>[sidebar][show_term_counts]" value="1" <?php checked( ! empty( $sidebar['show_term_counts'] ) ); ?> /> <?php esc_html_e( 'Show term counts', 'beplus-smart-search' ); ?></label>
							<label class="bpss-filter-row__checkbox"><input type="checkbox" name="<?php echo esc_attr( $option_key ); ?>[sidebar][collapsible_sections]" value="1" <?php checked( ! empty( $sidebar['collapsible_sections'] ) ); ?> /> <?php esc_html_e( 'Collapsible sections', 'beplus-smart-search' ); ?></label>
							<label class="bpss-filter-row__checkbox"><input type="checkbox" name="<?php echo esc_attr( $option_key ); ?>[sidebar][sections_open_default]" value="1" <?php checked( ! empty( $sidebar['sections_open_default'] ) ); ?> /> <?php esc_html_e( 'Sections open by default', 'beplus-smart-search' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bpss-accent"><?php esc_html_e( 'Accent color', 'beplus-smart-search' ); ?></label></th>
						<td>
							<input type="text" id="bpss-accent" class="bpss-color-picker" name="<?php echo esc_attr( $option_key ); ?>[sidebar][accent_color]" value="<?php echo esc_attr( $accent_color ); ?>" data-default-color="#f5c518" />
							<p class="description"><?php esc_html_e( 'Used for price slider and highlights in the sidebar.', 'beplus-smart-search' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="bpss-settings__card">
				<div class="bpss-settings__card-header">
					<h2 class="bpss-settings__card-title"><?php esc_html_e( 'Default filters', 'beplus-smart-search' ); ?></h2>
					<span class="bpss-settings__badge"><?php esc_html_e( 'Categories · Tags · Attributes · Brand', 'beplus-smart-search' ); ?></span>
				</div>
				<p class="description"><?php esc_html_e( 'Core WooCommerce filters shown on most shops. Configure selection mode and sub-item display for each.', 'beplus-smart-search' ); ?></p>

				<div class="bpss-filter-grid">
					<div class="bpss-filter-row">
						<div class="bpss-filter-row__head">
							<h3><?php esc_html_e( 'Categories', 'beplus-smart-search' ); ?></h3>
							<span class="bpss-filter-row__tag"><?php esc_html_e( 'product_cat', 'beplus-smart-search' ); ?></span>
						</div>
						<?php
						bpss_render_filter_mode_controls(
							$option_key,
							'[sidebar][taxonomy_modes][product_cat]',
							'[sidebar][taxonomy_sub_modes][product_cat]',
							(string) ( $modes['product_cat'] ?? 'radio' ),
							! empty( $sub_modes['product_cat'] )
						);
						?>
					</div>

					<div class="bpss-filter-row">
						<div class="bpss-filter-row__head">
							<h3><?php esc_html_e( 'Tags', 'beplus-smart-search' ); ?></h3>
							<span class="bpss-filter-row__tag"><?php esc_html_e( 'product_tag', 'beplus-smart-search' ); ?></span>
						</div>
						<?php
						bpss_render_filter_mode_controls(
							$option_key,
							'[sidebar][taxonomy_modes][product_tag]',
							'[sidebar][taxonomy_sub_modes][product_tag]',
							(string) ( $modes['product_tag'] ?? 'checkbox' ),
							! empty( $sub_modes['product_tag'] )
						);
						?>
					</div>

					<div class="bpss-filter-row bpss-filter-row--brand">
						<div class="bpss-filter-row__head">
							<h3><?php esc_html_e( 'Brand', 'beplus-smart-search' ); ?></h3>
							<span class="bpss-filter-row__tag"><?php echo esc_html( $brand_taxonomy ); ?></span>
						</div>
						<p class="bpss-filter-row__note">
							<?php
							if ( taxonomy_exists( $brand_taxonomy ) ) {
								esc_html_e( 'Uses the WooCommerce product brand taxonomy automatically.', 'beplus-smart-search' );
							} else {
								esc_html_e( 'Brand taxonomy not found yet — install/enable WooCommerce Brands or create product_brand.', 'beplus-smart-search' );
							}
							?>
						</p>
						<?php
						bpss_render_filter_mode_controls(
							$option_key,
							'[sidebar][facets][brand][mode]',
							'[sidebar][facets][brand][show_sub]',
							(string) ( $brand_facet['mode'] ?? 'checkbox' ),
							! empty( $brand_facet['show_sub'] )
						);
						?>
					</div>
				</div>
			</div>

			<div class="bpss-settings__card">
				<h2 class="bpss-settings__card-title"><?php esc_html_e( 'Product attributes', 'beplus-smart-search' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Choose how customers select attribute values (single or multiple). Enable each attribute per block in the block editor.', 'beplus-smart-search' ); ?></p>

				<?php if ( empty( $attribute_definitions ) ) : ?>
					<p class="bpss-settings__empty"><?php esc_html_e( 'No product attributes found. Create attributes under Products → Attributes.', 'beplus-smart-search' ); ?></p>
				<?php else : ?>
					<div class="bpss-settings__table-wrap">
						<table class="widefat bpss-settings__data-table bpss-settings__attr-table">
							<thead>
								<tr>
									<th class="col-name"><?php esc_html_e( 'Attribute', 'beplus-smart-search' ); ?></th>
									<th class="col-slug"><?php esc_html_e( 'Slug', 'beplus-smart-search' ); ?></th>
									<th class="col-mode"><?php esc_html_e( 'Selection', 'beplus-smart-search' ); ?></th>
									<th class="col-sub"><?php esc_html_e( 'Sub-items', 'beplus-smart-search' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $attribute_definitions as $attribute ) : ?>
									<?php
									$attr_slug     = (string) ( $attribute['slug'] ?? '' );
									$attr_label    = (string) ( $attribute['label'] ?? $attr_slug );
									$mode_key      = 'attribute:' . $attr_slug;
									$current_mode  = (string) ( $modes[ $mode_key ] ?? $modes['attribute'] ?? 'checkbox' );
									$show_sub_attr = ! empty( $sub_modes[ $mode_key ] );
									?>
									<tr>
										<td class="col-name"><strong><?php echo esc_html( $attr_label ); ?></strong></td>
										<td class="col-slug"><code><?php echo esc_html( $attr_slug ); ?></code></td>
										<td class="col-mode">
											<select name="<?php echo esc_attr( $option_key ); ?>[sidebar][taxonomy_modes][<?php echo esc_attr( $mode_key ); ?>]">
												<option value="radio" <?php selected( $current_mode, 'radio' ); ?>><?php esc_html_e( 'Single', 'beplus-smart-search' ); ?></option>
												<option value="checkbox" <?php selected( $current_mode, 'checkbox' ); ?>><?php esc_html_e( 'Multiple', 'beplus-smart-search' ); ?></option>
											</select>
										</td>
										<td class="col-sub">
											<label class="bpss-filter-row__checkbox bpss-filter-row__checkbox--center">
												<input
													type="checkbox"
													name="<?php echo esc_attr( $option_key ); ?>[sidebar][taxonomy_sub_modes][<?php echo esc_attr( $mode_key ); ?>]"
													value="1"
													<?php checked( $show_sub_attr ); ?>
												/>
												<span class="screen-reader-text"><?php esc_html_e( 'Show sub-items', 'beplus-smart-search' ); ?></span>
											</label>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>

			<div class="bpss-settings__card">
				<h2 class="bpss-settings__card-title"><?php esc_html_e( 'Price filter', 'beplus-smart-search' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Choose how the price filter appears in the sidebar. Enable or disable per block in the editor.', 'beplus-smart-search' ); ?></p>

				<fieldset class="bpss-settings__choice-list bpss-settings__price-display">
					<label class="bpss-settings__choice">
						<input type="radio" name="<?php echo esc_attr( $option_key ); ?>[sidebar][price][display]" value="range" <?php checked( $price_display, 'range' ); ?> data-bpss-price-display="range" />
						<span class="bpss-settings__choice-text">
							<strong><?php esc_html_e( 'Price range', 'beplus-smart-search' ); ?></strong>
							<span><?php esc_html_e( 'Dual slider with min/max inputs.', 'beplus-smart-search' ); ?></span>
						</span>
					</label>
					<label class="bpss-settings__choice">
						<input type="radio" name="<?php echo esc_attr( $option_key ); ?>[sidebar][price][display]" value="segments" <?php checked( $price_display, 'segments' ); ?> data-bpss-price-display="segments" />
						<span class="bpss-settings__choice-text">
							<strong><?php esc_html_e( 'Price segments', 'beplus-smart-search' ); ?></strong>
							<span><?php esc_html_e( 'Predefined ranges (e.g. $0–$50, $50–$100).', 'beplus-smart-search' ); ?></span>
						</span>
					</label>
				</fieldset>

				<div class="bpss-settings__price-range" data-bpss-price-settings="range" <?php echo 'range' === $price_display ? '' : 'hidden'; ?>>
					<div class="bpss-filter-row__controls bpss-filter-row__controls--inline">
						<label class="bpss-filter-row__field">
							<span class="bpss-filter-row__label"><?php esc_html_e( 'Min price', 'beplus-smart-search' ); ?></span>
							<input type="number" id="bpss-price-min" name="<?php echo esc_attr( $option_key ); ?>[sidebar][price][min]" value="<?php echo esc_attr( (string) ( $price['min'] ?? 0 ) ); ?>" min="0" step="0.01" class="small-text" />
						</label>
						<label class="bpss-filter-row__field">
							<span class="bpss-filter-row__label"><?php esc_html_e( 'Max price', 'beplus-smart-search' ); ?></span>
							<input type="number" id="bpss-price-max" name="<?php echo esc_attr( $option_key ); ?>[sidebar][price][max]" value="<?php echo esc_attr( (string) ( $price['max'] ?? 1000 ) ); ?>" min="1" step="0.01" class="small-text" />
						</label>
						<label class="bpss-filter-row__field">
							<span class="bpss-filter-row__label"><?php esc_html_e( 'Step', 'beplus-smart-search' ); ?></span>
							<input type="number" id="bpss-price-step" name="<?php echo esc_attr( $option_key ); ?>[sidebar][price][step]" value="<?php echo esc_attr( (string) ( $price['step'] ?? 1 ) ); ?>" min="0.01" step="0.01" class="small-text" />
						</label>
					</div>
				</div>

				<div class="bpss-settings__price-segments" data-bpss-price-settings="segments" <?php echo 'segments' === $price_display ? '' : 'hidden'; ?>>
					<h3 class="bpss-settings__subtitle"><?php esc_html_e( 'Price segments', 'beplus-smart-search' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Add one row per range. Leave Max empty or 0 for open-ended (e.g. $200+).', 'beplus-smart-search' ); ?></p>
					<table class="widefat bpss-settings__data-table" id="bpss-price-segments">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Label (optional)', 'beplus-smart-search' ); ?></th>
								<th><?php esc_html_e( 'Min', 'beplus-smart-search' ); ?></th>
								<th><?php esc_html_e( 'Max', 'beplus-smart-search' ); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $price_segments as $index => $segment ) : ?>
								<?php
								if ( ! is_array( $segment ) ) {
									continue;
								}
								$seg_min   = $segment['min'] ?? 0;
								$seg_max   = $segment['max'] ?? 0;
								$seg_label = $segment['label'] ?? '';
								?>
								<tr class="bpss-settings__segment-row">
									<td><input type="text" class="regular-text" name="<?php echo esc_attr( $option_key ); ?>[sidebar][price][segments][<?php echo esc_attr( (string) $index ); ?>][label]" value="<?php echo esc_attr( (string) $seg_label ); ?>" placeholder="<?php esc_attr_e( 'Auto label', 'beplus-smart-search' ); ?>" /></td>
									<td><input type="number" class="small-text" name="<?php echo esc_attr( $option_key ); ?>[sidebar][price][segments][<?php echo esc_attr( (string) $index ); ?>][min]" value="<?php echo esc_attr( (string) $seg_min ); ?>" min="0" step="0.01" /></td>
									<td><input type="number" class="small-text" name="<?php echo esc_attr( $option_key ); ?>[sidebar][price][segments][<?php echo esc_attr( (string) $index ); ?>][max]" value="<?php echo esc_attr( $seg_max > 0 ? (string) $seg_max : '' ); ?>" min="0" step="0.01" placeholder="∞" /></td>
									<td><button type="button" class="button-link-delete bpss-remove-segment"><?php esc_html_e( 'Remove', 'beplus-smart-search' ); ?></button></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<p><button type="button" class="button bpss-add-segment"><?php esc_html_e( 'Add segment', 'beplus-smart-search' ); ?></button></p>
					<template id="bpss-segment-row-template">
						<tr class="bpss-settings__segment-row">
							<td><input type="text" class="regular-text" data-name="label" placeholder="<?php esc_attr_e( 'Auto label', 'beplus-smart-search' ); ?>" /></td>
							<td><input type="number" class="small-text" data-name="min" min="0" step="0.01" value="0" /></td>
							<td><input type="number" class="small-text" data-name="max" min="0" step="0.01" placeholder="∞" /></td>
							<td><button type="button" class="button-link-delete bpss-remove-segment"><?php esc_html_e( 'Remove', 'beplus-smart-search' ); ?></button></td>
						</tr>
					</template>
				</div>
			</div>

			<div class="bpss-settings__card">
				<h2 class="bpss-settings__card-title"><?php esc_html_e( 'Custom taxonomies', 'beplus-smart-search' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Add extra product taxonomies beyond the defaults above.', 'beplus-smart-search' ); ?></p>
				<div class="bpss-settings__table-wrap">
					<table class="widefat bpss-settings__data-table bpss-settings__custom-tax-table" id="bpss-custom-taxonomies">
					<thead>
						<tr>
							<th class="col-taxonomy"><?php esc_html_e( 'Taxonomy', 'beplus-smart-search' ); ?></th>
							<th class="col-label"><?php esc_html_e( 'Label', 'beplus-smart-search' ); ?></th>
							<th class="col-mode"><?php esc_html_e( 'Selection', 'beplus-smart-search' ); ?></th>
							<th class="col-sub"><?php esc_html_e( 'Sub-items', 'beplus-smart-search' ); ?></th>
							<th class="col-actions"></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $custom_taxonomies as $index => $custom_row ) : ?>
							<?php
							if ( ! is_array( $custom_row ) ) {
								continue;
							}
							$row_taxonomy = $custom_row['taxonomy'] ?? '';
							$row_label    = $custom_row['label'] ?? '';
							$row_mode     = $custom_row['mode'] ?? 'checkbox';
							$row_sub      = ! empty( $custom_row['show_sub'] );
							?>
							<tr class="bpss-settings__custom-tax-row">
								<td>
									<select name="<?php echo esc_attr( $option_key ); ?>[sidebar][facets][custom_taxonomies][<?php echo esc_attr( (string) $index ); ?>][taxonomy]">
										<option value=""><?php esc_html_e( 'Select taxonomy', 'beplus-smart-search' ); ?></option>
										<?php foreach ( $selectable_taxonomies as $taxonomy => $label ) : ?>
											<option value="<?php echo esc_attr( $taxonomy ); ?>" <?php selected( $row_taxonomy, $taxonomy ); ?>><?php echo esc_html( $label . ' (' . $taxonomy . ')' ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td><input type="text" class="regular-text" name="<?php echo esc_attr( $option_key ); ?>[sidebar][facets][custom_taxonomies][<?php echo esc_attr( (string) $index ); ?>][label]" value="<?php echo esc_attr( (string) $row_label ); ?>" placeholder="<?php esc_attr_e( 'Auto label', 'beplus-smart-search' ); ?>" /></td>
								<td>
									<select name="<?php echo esc_attr( $option_key ); ?>[sidebar][facets][custom_taxonomies][<?php echo esc_attr( (string) $index ); ?>][mode]">
										<option value="radio" <?php selected( $row_mode, 'radio' ); ?>><?php esc_html_e( 'Single', 'beplus-smart-search' ); ?></option>
										<option value="checkbox" <?php selected( $row_mode, 'checkbox' ); ?>><?php esc_html_e( 'Multiple', 'beplus-smart-search' ); ?></option>
									</select>
								</td>
								<td>
									<label class="bpss-filter-row__checkbox">
										<input type="checkbox" name="<?php echo esc_attr( $option_key ); ?>[sidebar][facets][custom_taxonomies][<?php echo esc_attr( (string) $index ); ?>][show_sub]" value="1" <?php checked( $row_sub ); ?> />
										<?php esc_html_e( 'Show sub', 'beplus-smart-search' ); ?>
									</label>
								</td>
								<td><button type="button" class="button-link-delete bpss-remove-custom-tax"><?php esc_html_e( 'Remove', 'beplus-smart-search' ); ?></button></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				</div>
				<p><button type="button" class="button bpss-add-custom-tax"><?php esc_html_e( 'Add custom taxonomy', 'beplus-smart-search' ); ?></button></p>
				<template id="bpss-custom-tax-row-template">
					<tr class="bpss-settings__custom-tax-row">
						<td>
							<select data-name="taxonomy">
								<option value=""><?php esc_html_e( 'Select taxonomy', 'beplus-smart-search' ); ?></option>
								<?php foreach ( $selectable_taxonomies as $taxonomy => $label ) : ?>
									<option value="<?php echo esc_attr( $taxonomy ); ?>"><?php echo esc_html( $label . ' (' . $taxonomy . ')' ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<td><input type="text" class="regular-text" data-name="label" placeholder="<?php esc_attr_e( 'Auto label', 'beplus-smart-search' ); ?>" /></td>
						<td>
							<select data-name="mode">
								<option value="radio"><?php esc_html_e( 'Single', 'beplus-smart-search' ); ?></option>
								<option value="checkbox" selected><?php esc_html_e( 'Multiple', 'beplus-smart-search' ); ?></option>
							</select>
						</td>
						<td><label class="bpss-filter-row__checkbox"><input type="checkbox" data-name="show_sub" value="1" /> <?php esc_html_e( 'Show sub', 'beplus-smart-search' ); ?></label></td>
						<td><button type="button" class="button-link-delete bpss-remove-custom-tax"><?php esc_html_e( 'Remove', 'beplus-smart-search' ); ?></button></td>
					</tr>
				</template>
			</div>

			<div class="bpss-settings__card">
				<h2 class="bpss-settings__card-title"><?php esc_html_e( 'Additional filters', 'beplus-smart-search' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Optional filters — enable each one in the block editor when needed.', 'beplus-smart-search' ); ?></p>
				<div class="bpss-filter-grid bpss-filter-grid--compact">
					<div class="bpss-filter-row bpss-filter-row--info">
						<h3><?php esc_html_e( 'Rating', 'beplus-smart-search' ); ?></h3>
						<p><?php esc_html_e( 'Minimum average rating (5★ to 1★ & up).', 'beplus-smart-search' ); ?></p>
					</div>
					<div class="bpss-filter-row bpss-filter-row--info">
						<h3><?php esc_html_e( 'On sale', 'beplus-smart-search' ); ?></h3>
						<p><?php esc_html_e( 'Checkbox to show sale products only.', 'beplus-smart-search' ); ?></p>
					</div>
					<div class="bpss-filter-row bpss-filter-row--info">
						<h3><?php esc_html_e( 'Featured products', 'beplus-smart-search' ); ?></h3>
						<p><?php esc_html_e( 'Checkbox to show featured products only.', 'beplus-smart-search' ); ?></p>
					</div>
					<div class="bpss-filter-row bpss-filter-row--info">
						<h3><?php esc_html_e( 'Stock status', 'beplus-smart-search' ); ?></h3>
						<p><?php esc_html_e( 'In stock / out of stock / backorder.', 'beplus-smart-search' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<?php submit_button(); ?>
	</form>
</div>
