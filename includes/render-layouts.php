<?php
/**
 * Layout render helpers for Advanced Woo Search block.
 *
 * @package BePlusSmartSearch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Open a collapsible sidebar panel.
 *
 * @param string $title       Panel title.
 * @param string $section_mod BEM modifier.
 * @param array  $sidebar     Sidebar settings.
 * @param string $facet_panel Facet group key for contextual mode.
 * @param string $attr_slug   Attribute slug when facet panel is attribute.
 * @return void
 */
function beplus_smart_search_render_sidebar_panel_open( string $title, string $section_mod, array $sidebar, string $facet_panel = '', string $attr_slug = '' ): void {
	$collapsible = ! empty( $sidebar['collapsible_sections'] );
	$open        = ! isset( $sidebar['sections_open_default'] ) || ! empty( $sidebar['sections_open_default'] );
	$panel_attrs = ' data-bpss-panel';
	if ( $facet_panel ) {
		$panel_attrs .= ' data-bpss-facet-panel="' . esc_attr( $facet_panel ) . '"';
	}
	if ( $attr_slug ) {
		$panel_attrs .= ' data-bpss-attr-slug="' . esc_attr( $attr_slug ) . '"';
	}
	?>
	<div class="beplus-smart-search__panel beplus-smart-search__panel--<?php echo esc_attr( $section_mod ); ?>"<?php echo $panel_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( $collapsible ) : ?>
			<button
				type="button"
				class="beplus-smart-search__panel-toggle"
				data-bpss-panel-toggle
				aria-expanded="<?php echo $open ? 'true' : 'false'; ?>"
			>
				<span class="beplus-smart-search__panel-title"><?php echo esc_html( $title ); ?></span>
				<span class="beplus-smart-search__panel-icon" aria-hidden="true"></span>
			</button>
			<div class="beplus-smart-search__panel-body" data-bpss-panel-body <?php echo $open ? '' : 'hidden'; ?>>
		<?php else : ?>
			<div class="beplus-smart-search__panel-heading">
				<span class="beplus-smart-search__panel-title"><?php echo esc_html( $title ); ?></span>
			</div>
			<div class="beplus-smart-search__panel-body" data-bpss-panel-body>
		<?php endif; ?>
	<?php
}

/**
 * Close sidebar panel wrapper.
 *
 * @return void
 */
function beplus_smart_search_render_sidebar_panel_close(): void {
	?>
			</div>
		</div>
	<?php
}

/**
 * Render a single taxonomy term row (and optional children).
 *
 * @param array{term: WP_Term, children: array<int, array>} $node         Tree node.
 * @param string                                            $filter_type  data-bpss-filter value.
 * @param string                                            $input_name   Input name.
 * @param string                                            $input_type   radio|checkbox.
 * @param string                                            $name_attr    Input name attribute.
 * @param string                                            $block_id     Block ID prefix.
 * @param string                                            $input_id     Input suffix.
 * @param array                                             $sidebar      Sidebar settings.
 * @param string                                            $attr_data    Attribute data HTML.
 * @param string                                            $multi_attr   Multi-select data HTML.
 * @param string                                            $current_slug Current term slug.
 * @param array<int, int>                                   $expand_ids   Expanded term IDs.
 * @param bool                                              $show_sub     Render nested children.
 * @param int                                               $depth        Nesting depth.
 * @return void
 */
function beplus_smart_search_render_sidebar_taxonomy_item(
	array $node,
	string $filter_type,
	string $input_name,
	string $input_type,
	string $name_attr,
	string $block_id,
	string $input_id,
	array $sidebar,
	string $attr_data,
	string $multi_attr,
	string $current_slug,
	array $expand_ids,
	bool $show_sub,
	int $depth = 0
): void {
	$term         = $node['term'];
	$children     = $show_sub ? $node['children'] : array();
	$has_children = ! empty( $children );
	$url          = get_term_link( $term );

	if ( is_wp_error( $url ) ) {
		return;
	}

	$is_current  = $current_slug && $term->slug === $current_slug;
	$is_expanded = $has_children && in_array( (int) $term->term_id, $expand_ids, true );
	$show_counts = ! empty( $sidebar['show_term_counts'] );
	$item_class  = 'beplus-smart-search__list-item';

	if ( $is_current ) {
		$item_class .= ' is-active';
	}
	if ( $has_children ) {
		$item_class .= ' beplus-smart-search__list-item--parent';
	}
	if ( $is_expanded ) {
		$item_class .= ' is-expanded';
	}
	?>
	<li class="<?php echo esc_attr( $item_class ); ?>" data-bpss-term-slug="<?php echo esc_attr( $term->slug ); ?>" data-bpss-term-url="<?php echo esc_url( $url ); ?>">
		<div class="beplus-smart-search__list-row">
			<label class="beplus-smart-search__list-label" for="<?php echo esc_attr( $block_id . '-' . $input_id . '-' . $term->slug ); ?>">
				<input
					type="<?php echo esc_attr( $input_type ); ?>"
					name="<?php echo esc_attr( $name_attr ); ?>"
					id="<?php echo esc_attr( $block_id . '-' . $input_id . '-' . $term->slug ); ?>"
					class="beplus-smart-search__list-input"
					value="<?php echo esc_attr( $term->slug ); ?>"
					<?php echo $is_current ? 'checked' : ''; ?>
					data-bpss-filter="<?php echo esc_attr( $filter_type ); ?>"
					data-bpss-term-url="<?php echo esc_url( $url ); ?>"
					<?php echo $attr_data . $multi_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				/>
				<span class="beplus-smart-search__list-text"><?php echo esc_html( $term->name ); ?></span>
				<?php if ( $show_counts && ! $has_children ) : ?>
					<span class="beplus-smart-search__list-count">(<?php echo esc_html( (string) beplus_smart_search_count_products_for_term( $term ) ); ?>)</span>
				<?php endif; ?>
			</label>
			<?php if ( $has_children ) : ?>
				<div class="beplus-smart-search__list-meta">
					<?php if ( $show_counts ) : ?>
						<span class="beplus-smart-search__list-count">(<?php echo esc_html( (string) beplus_smart_search_count_products_for_term( $term ) ); ?>)</span>
					<?php endif; ?>
					<button
						type="button"
						class="beplus-smart-search__term-toggle"
						data-bpss-term-toggle
						aria-expanded="<?php echo $is_expanded ? 'true' : 'false'; ?>"
						aria-label="<?php echo esc_attr( sprintf( __( 'Toggle %s subcategories', 'beplus-smart-search' ), $term->name ) ); ?>"
					>
						<span class="beplus-smart-search__term-toggle-icon" aria-hidden="true"></span>
					</button>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( $has_children ) : ?>
			<ul class="beplus-smart-search__list beplus-smart-search__list--children" role="list"<?php echo $is_expanded ? '' : ' hidden'; ?>>
				<?php
				foreach ( $children as $child ) {
					beplus_smart_search_render_sidebar_taxonomy_item(
						$child,
						$filter_type,
						$input_name,
						$input_type,
						$name_attr,
						$block_id,
						$input_id,
						$sidebar,
						$attr_data,
						$multi_attr,
						$current_slug,
						$expand_ids,
						$show_sub,
						$depth + 1
					);
				}
				?>
			</ul>
		<?php endif; ?>
	</li>
	<?php
}

/**
 * Render taxonomy list for sidebar.
 *
 * @param string              $filter_type   data-bpss-filter value.
 * @param string              $input_name    Input name.
 * @param string              $mode_key      Settings taxonomy mode key.
 * @param array<int, WP_Term> $terms         Terms.
 * @param string              $block_id      Block ID prefix.
 * @param string              $input_id      Input suffix.
 * @param array               $sidebar       Sidebar settings.
 * @param string              $attr_slug     Attribute slug.
 * @return void
 */
function beplus_smart_search_render_sidebar_taxonomy_list(
	string $filter_type,
	string $input_name,
	string $mode_key,
	array $terms,
	string $block_id,
	string $input_id,
	array $sidebar,
	string $attr_slug = ''
): void {
	if ( empty( $terms ) ) {
		return;
	}

	$current_term_slug = '';
	$expand_ids        = array();
	$taxonomy          = 'product_cat';

	if ( 'brand' === $filter_type || 'custom_tax' === $filter_type ) {
		$taxonomy = $attr_slug;
		if ( $taxonomy && is_tax( $taxonomy ) ) {
			$queried = get_queried_object();
			if ( $queried instanceof \WP_Term ) {
				$current_term_slug = (string) $queried->slug;
			}
		}
		if ( $taxonomy ) {
			$expand_ids = beplus_smart_search_get_expanded_term_ids( $taxonomy );
		}
	} elseif ( 'category' === $filter_type ) {
		$taxonomy = 'product_cat';
		if ( is_tax( 'product_cat' ) ) {
			$queried = get_queried_object();
			if ( $queried instanceof \WP_Term ) {
				$current_term_slug = (string) $queried->slug;
			}
		}
		$expand_ids = beplus_smart_search_get_expanded_term_ids( 'product_cat' );
	} elseif ( 'tag' === $filter_type ) {
		$taxonomy = 'product_tag';
		if ( is_tax( 'product_tag' ) ) {
			$queried = get_queried_object();
			if ( $queried instanceof \WP_Term ) {
				$current_term_slug = (string) $queried->slug;
			}
		}
		$expand_ids = beplus_smart_search_get_expanded_term_ids( 'product_tag' );
	} elseif ( 'attribute' === $filter_type && $attr_slug ) {
		$taxonomy = wc_attribute_taxonomy_name( $attr_slug );
		if ( is_tax( $taxonomy ) ) {
			$queried = get_queried_object();
			if ( $queried instanceof \WP_Term ) {
				$current_term_slug = (string) $queried->slug;
			}
		}
		$expand_ids = beplus_smart_search_get_expanded_term_ids( $taxonomy );
	}

	$mode         = beplus_smart_search_get_taxonomy_mode( $mode_key );
	$show_sub     = beplus_smart_search_show_sub_taxonomy( $mode_key );
	$input_type   = 'checkbox' === $mode ? 'checkbox' : 'radio';
	$show_counts  = ! empty( $sidebar['show_term_counts'] );
	$multi_attr   = 'checkbox' === $mode ? ' data-bpss-multi="1"' : '';
	$name_attr    = 'checkbox' === $mode ? $input_name . '[]' : $input_name;
	$attr_data    = $attr_slug ? ' data-taxonomy-slug="' . esc_attr( $attr_slug ) . '"' : '';
	if ( 'attribute' === $filter_type && $attr_slug ) {
		$attr_data .= ' data-attribute-slug="' . esc_attr( $attr_slug ) . '"';
	}
	$facet_group  = 'attribute' === $filter_type ? 'attribute' : $filter_type;
	$list_class   = 'beplus-smart-search__list beplus-smart-search__list--' . esc_attr( $input_type );
	if ( $show_sub ) {
		$list_class .= ' beplus-smart-search__list--tree';
	}
	$list_attrs   = ' data-bpss-facet-group="' . esc_attr( $facet_group ) . '"';
	if ( $attr_slug ) {
		$list_attrs .= ' data-bpss-attr-slug="' . esc_attr( $attr_slug ) . '"';
	}

	$tree = $show_sub ? beplus_smart_search_build_term_tree( $terms ) : array();
	?>
	<ul class="<?php echo esc_attr( $list_class ); ?>" role="list"<?php echo $list_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( 'radio' === $input_type ) : ?>
			<li class="beplus-smart-search__list-item">
				<div class="beplus-smart-search__list-row">
					<label class="beplus-smart-search__list-label" for="<?php echo esc_attr( $block_id . '-' . $input_id . '-all' ); ?>">
						<input
							type="radio"
							name="<?php echo esc_attr( $input_name ); ?>"
							id="<?php echo esc_attr( $block_id . '-' . $input_id . '-all' ); ?>"
							class="beplus-smart-search__list-input"
							value=""
							<?php echo $current_term_slug ? '' : 'checked'; ?>
							data-bpss-filter="<?php echo esc_attr( $filter_type ); ?>"
							data-bpss-term-url="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>"
							<?php echo $attr_data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						/>
						<span class="beplus-smart-search__list-text"><?php esc_html_e( 'All', 'beplus-smart-search' ); ?></span>
					</label>
				</div>
			</li>
		<?php endif; ?>
		<?php
		if ( $show_sub && ! empty( $tree ) ) {
			foreach ( $tree as $node ) {
				beplus_smart_search_render_sidebar_taxonomy_item(
					$node,
					$filter_type,
					$input_name,
					$input_type,
					$name_attr,
					$block_id,
					$input_id,
					$sidebar,
					$attr_data,
					$multi_attr,
					$current_term_slug,
					$expand_ids,
					true
				);
			}
		} else {
			foreach ( $terms as $term ) {
				beplus_smart_search_render_sidebar_taxonomy_item(
					array(
						'term'     => $term,
						'children' => array(),
					),
					$filter_type,
					$input_name,
					$input_type,
					$name_attr,
					$block_id,
					$input_id,
					$sidebar,
					$attr_data,
					$multi_attr,
					$current_term_slug,
					$expand_ids,
					false
				);
			}
		}
		?>
	</ul>
	<?php
}

/**
 * Render price filter panel.
 *
 * @param string               $block_id Block ID.
 * @param array<string, mixed> $price    Price settings.
 * @return void
 */
function beplus_smart_search_render_sidebar_price_section( string $block_id, array $price ): void {
	$min           = isset( $price['min'] ) ? (float) $price['min'] : 0;
	$max           = isset( $price['max'] ) ? max( 1, (float) $price['max'] ) : 1000;
	$step          = isset( $price['step'] ) ? (float) $price['step'] : 1;
	$max_input_min = max( 1, $min );
	$currency      = beplus_smart_search_get_currency_symbol();
	?>
	<div
		class="beplus-smart-search__price"
		data-bpss-price
		data-price-display="range"
		data-price-min="<?php echo esc_attr( (string) $min ); ?>"
		data-price-max="<?php echo esc_attr( (string) $max ); ?>"
		data-price-step="<?php echo esc_attr( (string) $step ); ?>"
	>
		<div class="beplus-smart-search__price-slider">
			<div class="beplus-smart-search__price-track" data-bpss-price-track></div>
			<input
				type="range"
				class="beplus-smart-search__range beplus-smart-search__range--min"
				min="<?php echo esc_attr( (string) $min ); ?>"
				max="<?php echo esc_attr( (string) $max ); ?>"
				step="<?php echo esc_attr( (string) $step ); ?>"
				value="<?php echo esc_attr( (string) $min ); ?>"
				aria-label="<?php esc_attr_e( 'Minimum price', 'beplus-smart-search' ); ?>"
				data-bpss-range="min"
			/>
			<input
				type="range"
				class="beplus-smart-search__range beplus-smart-search__range--max"
				min="<?php echo esc_attr( (string) $max_input_min ); ?>"
				max="<?php echo esc_attr( (string) $max ); ?>"
				step="<?php echo esc_attr( (string) $step ); ?>"
				value="<?php echo esc_attr( (string) $max ); ?>"
				aria-label="<?php esc_attr_e( 'Maximum price', 'beplus-smart-search' ); ?>"
				data-bpss-range="max"
			/>
		</div>
		<div class="beplus-smart-search__price-inputs">
			<div class="beplus-smart-search__price-field">
				<label class="beplus-smart-search__price-label" for="<?php echo esc_attr( $block_id ); ?>-price-min">
					<?php esc_html_e( 'Min price', 'beplus-smart-search' ); ?>
				</label>
				<div class="beplus-smart-search__price-input-wrap">
					<input
						type="number"
						id="<?php echo esc_attr( $block_id ); ?>-price-min"
						class="beplus-smart-search__price-input"
						min="<?php echo esc_attr( (string) $min ); ?>"
						max="<?php echo esc_attr( (string) $max ); ?>"
						step="<?php echo esc_attr( (string) $step ); ?>"
						value="<?php echo esc_attr( (string) $min ); ?>"
						data-bpss-filter="min_price"
						data-bpss-price-input="min"
					/>
					<span class="beplus-smart-search__price-currency"><?php echo esc_html( $currency ); ?></span>
				</div>
			</div>
			<div class="beplus-smart-search__price-field">
				<label class="beplus-smart-search__price-label" for="<?php echo esc_attr( $block_id ); ?>-price-max">
					<?php esc_html_e( 'Max price', 'beplus-smart-search' ); ?>
				</label>
				<div class="beplus-smart-search__price-input-wrap">
					<input
						type="number"
						id="<?php echo esc_attr( $block_id ); ?>-price-max"
						class="beplus-smart-search__price-input"
						min="<?php echo esc_attr( (string) $max_input_min ); ?>"
						max="<?php echo esc_attr( (string) $max ); ?>"
						step="<?php echo esc_attr( (string) $step ); ?>"
						value="<?php echo esc_attr( (string) $max ); ?>"
						data-bpss-filter="max_price"
						data-bpss-price-input="max"
					/>
					<span class="beplus-smart-search__price-currency"><?php echo esc_html( $currency ); ?></span>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Render price segment radio list.
 *
 * @param string               $block_id Block ID.
 * @param array<string, mixed> $price    Price settings.
 * @return void
 */
function beplus_smart_search_render_sidebar_price_segments( string $block_id, array $price ): void {
	$segments = isset( $price['segments'] ) && is_array( $price['segments'] ) ? $price['segments'] : array();

	if ( empty( $segments ) ) {
		return;
	}
	?>
	<div class="beplus-smart-search__price beplus-smart-search__price--segments" data-bpss-price data-price-display="segments">
		<ul class="beplus-smart-search__list beplus-smart-search__list--radio" role="list">
			<li class="beplus-smart-search__list-item">
				<label class="beplus-smart-search__list-label" for="<?php echo esc_attr( $block_id ); ?>-price-all">
					<input
						type="radio"
						name="<?php echo esc_attr( $block_id ); ?>-price-segment"
						id="<?php echo esc_attr( $block_id ); ?>-price-all"
						class="beplus-smart-search__list-input"
						value=""
						checked
						data-bpss-filter="price_segment"
					/>
					<span class="beplus-smart-search__list-text"><?php esc_html_e( 'All prices', 'beplus-smart-search' ); ?></span>
				</label>
			</li>
			<?php
			foreach ( $segments as $index => $segment ) {
				if ( ! is_array( $segment ) ) {
					continue;
				}

				$min   = isset( $segment['min'] ) ? (float) $segment['min'] : 0;
				$max   = isset( $segment['max'] ) ? (float) $segment['max'] : 0;
				$label = beplus_smart_search_format_price_segment_label(
					$min,
					$max,
					isset( $segment['label'] ) ? (string) $segment['label'] : ''
				);
				$slug  = $min . '-' . ( $max > 0 ? (string) $max : 'up' );
				?>
				<li class="beplus-smart-search__list-item">
					<label class="beplus-smart-search__list-label" for="<?php echo esc_attr( $block_id . '-price-seg-' . $index ); ?>">
						<input
							type="radio"
							name="<?php echo esc_attr( $block_id ); ?>-price-segment"
							id="<?php echo esc_attr( $block_id . '-price-seg-' . $index ); ?>"
							class="beplus-smart-search__list-input"
							value="<?php echo esc_attr( $slug ); ?>"
							data-bpss-filter="price_segment"
							data-price-min="<?php echo esc_attr( (string) $min ); ?>"
							data-price-max="<?php echo esc_attr( $max > 0 ? (string) $max : '' ); ?>"
						/>
						<span class="beplus-smart-search__list-text"><?php echo esc_html( $label ); ?></span>
					</label>
				</li>
				<?php
			}
			?>
		</ul>
	</div>
	<?php
}

/**
 * Render rating filter panel.
 *
 * @param string $block_id Block ID prefix.
 * @return void
 */
function beplus_smart_search_render_sidebar_rating_section( string $block_id ): void {
	$options = beplus_smart_search_get_rating_filter_options();
	?>
	<ul class="beplus-smart-search__list beplus-smart-search__list--radio beplus-smart-search__list--rating" role="list" data-bpss-facet-group="rating">
		<li class="beplus-smart-search__list-item">
			<label class="beplus-smart-search__list-label" for="<?php echo esc_attr( $block_id ); ?>-rating-all">
				<input type="radio" name="<?php echo esc_attr( $block_id ); ?>-rating" id="<?php echo esc_attr( $block_id ); ?>-rating-all" class="beplus-smart-search__list-input" value="" checked data-bpss-filter="rating" />
				<span class="beplus-smart-search__list-text"><?php esc_html_e( 'All ratings', 'beplus-smart-search' ); ?></span>
			</label>
		</li>
		<?php foreach ( $options as $option ) : ?>
			<li class="beplus-smart-search__list-item" data-bpss-term-slug="<?php echo esc_attr( (string) $option['value'] ); ?>">
				<label class="beplus-smart-search__list-label" for="<?php echo esc_attr( $block_id . '-rating-' . $option['value'] ); ?>">
					<input type="radio" name="<?php echo esc_attr( $block_id ); ?>-rating" id="<?php echo esc_attr( $block_id . '-rating-' . $option['value'] ); ?>" class="beplus-smart-search__list-input" value="<?php echo esc_attr( (string) $option['value'] ); ?>" data-bpss-filter="rating" />
					<span class="beplus-smart-search__list-text"><?php echo esc_html( $option['label'] ); ?></span>
				</label>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Render featured products checkbox panel.
 *
 * @param string $block_id Block ID prefix.
 * @return void
 */
function beplus_smart_search_render_sidebar_featured_section( string $block_id ): void {
	?>
	<ul class="beplus-smart-search__list beplus-smart-search__list--checkbox" role="list" data-bpss-facet-group="featured">
		<li class="beplus-smart-search__list-item">
			<label class="beplus-smart-search__list-label" for="<?php echo esc_attr( $block_id ); ?>-featured">
				<input type="checkbox" name="featured" id="<?php echo esc_attr( $block_id ); ?>-featured" class="beplus-smart-search__list-input" value="1" data-bpss-filter="featured" />
				<span class="beplus-smart-search__list-text"><?php esc_html_e( 'Featured products only', 'beplus-smart-search' ); ?></span>
			</label>
		</li>
	</ul>
	<?php
}

/**
 * Render one sidebar filter section by sort key.
 *
 * @param string               $section_id          Section key.
 * @param array<string, mixed> $attrs               Block attributes.
 * @param string               $block_id            Block ID prefix.
 * @param array<int, WP_Term>  $categories          Product categories.
 * @param array<int, WP_Term>  $tags                Product tags.
 * @param array<string, array> $attributes_by_slug  Attributes keyed by slug.
 * @param array<string, mixed> $sidebar             Sidebar settings.
 * @param array<string, mixed> $price_settings      Price settings.
 * @return void
 */
function beplus_smart_search_render_sidebar_filter_section(
	string $section_id,
	array $attrs,
	string $block_id,
	array $categories,
	array $tags,
	array $attributes_by_slug,
	array $sidebar,
	array $price_settings
): void {
	switch ( $section_id ) {
		case 'keyword':
			?>
			<div class="beplus-smart-search__search-wrap">
				<label class="screen-reader-text" for="<?php echo esc_attr( $block_id ); ?>-keyword">
					<?php esc_html_e( 'Search products', 'beplus-smart-search' ); ?>
				</label>
				<input
					type="search"
					name="s"
					id="<?php echo esc_attr( $block_id ); ?>-keyword"
					class="beplus-smart-search__input beplus-smart-search__input--search"
					placeholder="<?php echo esc_attr( $attrs['placeholder'] ); ?>"
					value=""
					autocomplete="off"
					data-bpss-filter="keyword"
				/>
				<span class="beplus-smart-search__search-icon" aria-hidden="true"></span>
			</div>
			<?php
			break;

		case 'category':
			beplus_smart_search_render_sidebar_panel_open( __( 'Product Categories', 'beplus-smart-search' ), 'category', $sidebar, 'category' );
			beplus_smart_search_render_sidebar_taxonomy_list( 'category', 'product_cat', 'product_cat', $categories, $block_id, 'cat', $sidebar );
			beplus_smart_search_render_sidebar_panel_close();
			break;

		case 'price':
			beplus_smart_search_render_sidebar_panel_open( __( 'Price', 'beplus-smart-search' ), 'price', $sidebar );
			if ( beplus_smart_search_is_price_segments_mode() ) {
				beplus_smart_search_render_sidebar_price_segments( $block_id, $price_settings );
			} else {
				beplus_smart_search_render_sidebar_price_section( $block_id, $price_settings );
			}
			beplus_smart_search_render_sidebar_panel_close();
			break;

		case 'tag':
			beplus_smart_search_render_sidebar_panel_open( __( 'Product Tags', 'beplus-smart-search' ), 'tag', $sidebar, 'tag' );
			beplus_smart_search_render_sidebar_taxonomy_list( 'tag', 'product_tag', 'product_tag', $tags, $block_id, 'tag', $sidebar );
			beplus_smart_search_render_sidebar_panel_close();
			break;

		case 'stock':
			$stock_options = array(
				'instock'     => __( 'In stock', 'beplus-smart-search' ),
				'outofstock'  => __( 'Out of stock', 'beplus-smart-search' ),
				'onbackorder' => __( 'On backorder', 'beplus-smart-search' ),
			);
			beplus_smart_search_render_sidebar_panel_open( __( 'Stock status', 'beplus-smart-search' ), 'stock', $sidebar );
			?>
			<ul class="beplus-smart-search__list beplus-smart-search__list--radio" role="list">
				<li class="beplus-smart-search__list-item">
					<label class="beplus-smart-search__list-label" for="<?php echo esc_attr( $block_id ); ?>-stock-all">
						<input type="radio" name="stock_status" id="<?php echo esc_attr( $block_id ); ?>-stock-all" class="beplus-smart-search__list-input" value="" checked data-bpss-filter="stock" />
						<span class="beplus-smart-search__list-text"><?php esc_html_e( 'All stock', 'beplus-smart-search' ); ?></span>
					</label>
				</li>
				<?php foreach ( $stock_options as $value => $label ) : ?>
					<li class="beplus-smart-search__list-item">
						<label class="beplus-smart-search__list-label" for="<?php echo esc_attr( $block_id . '-stock-' . $value ); ?>">
							<input type="radio" name="stock_status" id="<?php echo esc_attr( $block_id . '-stock-' . $value ); ?>" class="beplus-smart-search__list-input" value="<?php echo esc_attr( $value ); ?>" data-bpss-filter="stock" />
							<span class="beplus-smart-search__list-text"><?php echo esc_html( $label ); ?></span>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
			beplus_smart_search_render_sidebar_panel_close();
			break;

		case 'on_sale':
			beplus_smart_search_render_sidebar_panel_open( __( 'On sale', 'beplus-smart-search' ), 'on-sale', $sidebar, 'on_sale' );
			?>
			<ul class="beplus-smart-search__list beplus-smart-search__list--checkbox" role="list" data-bpss-facet-group="on_sale">
				<li class="beplus-smart-search__list-item">
					<label class="beplus-smart-search__list-label" for="<?php echo esc_attr( $block_id ); ?>-on-sale">
						<input type="checkbox" name="on_sale" id="<?php echo esc_attr( $block_id ); ?>-on-sale" class="beplus-smart-search__list-input" value="1" data-bpss-filter="on_sale" />
						<span class="beplus-smart-search__list-text"><?php esc_html_e( 'On sale only', 'beplus-smart-search' ); ?></span>
					</label>
				</li>
			</ul>
			<?php
			beplus_smart_search_render_sidebar_panel_close();
			break;

		case 'rating':
			beplus_smart_search_render_sidebar_panel_open( __( 'Rating', 'beplus-smart-search' ), 'rating', $sidebar, 'rating' );
			beplus_smart_search_render_sidebar_rating_section( $block_id );
			beplus_smart_search_render_sidebar_panel_close();
			break;

		case 'featured':
			beplus_smart_search_render_sidebar_panel_open( __( 'Featured products', 'beplus-smart-search' ), 'featured', $sidebar, 'featured' );
			beplus_smart_search_render_sidebar_featured_section( $block_id );
			beplus_smart_search_render_sidebar_panel_close();
			break;

		case 'brand':
			$brand_taxonomy = beplus_smart_search_get_brand_taxonomy();
			$brand_terms    = $brand_taxonomy ? beplus_smart_search_get_brand_terms() : array();
			if ( ! $brand_taxonomy || empty( $brand_terms ) ) {
				break;
			}
			$brand_object = get_taxonomy( $brand_taxonomy );
			$brand_label  = $brand_object instanceof \WP_Taxonomy ? $brand_object->labels->name : __( 'Brand', 'beplus-smart-search' );
			beplus_smart_search_render_sidebar_panel_open( $brand_label, 'brand', $sidebar, 'brand', $brand_taxonomy );
			beplus_smart_search_render_sidebar_taxonomy_list(
				'brand',
				$brand_taxonomy,
				'brand',
				$brand_terms,
				$block_id,
				'brand',
				$sidebar,
				$brand_taxonomy
			);
			beplus_smart_search_render_sidebar_panel_close();
			break;

		default:
			if ( 0 === strpos( $section_id, 'attribute:' ) ) {
				$slug      = substr( $section_id, strlen( 'attribute:' ) );
				$attribute = $attributes_by_slug[ $slug ] ?? null;
				if ( ! $attribute ) {
					break;
				}
				beplus_smart_search_render_sidebar_panel_open( $attribute['label'], 'attribute-' . $attribute['slug'], $sidebar, 'attribute', $attribute['slug'] );
				beplus_smart_search_render_sidebar_taxonomy_list(
					'attribute',
					'filter_' . $attribute['slug'],
					'attribute:' . $attribute['slug'],
					$attribute['terms'],
					$block_id,
					'attr-' . $attribute['slug'],
					$sidebar,
					$attribute['slug']
				);
				beplus_smart_search_render_sidebar_panel_close();
				break;
			}

			if ( 0 === strpos( $section_id, 'custom:' ) ) {
				$taxonomy = substr( $section_id, strlen( 'custom:' ) );
				$label    = beplus_smart_search_get_filter_section_catalog()[ $section_id ] ?? $taxonomy;
				$terms    = beplus_smart_search_get_taxonomy_terms( $taxonomy );
				if ( empty( $terms ) ) {
					break;
				}
				beplus_smart_search_render_sidebar_panel_open(
					$label,
					'custom-' . $taxonomy,
					$sidebar,
					'custom_tax',
					$taxonomy
				);
				beplus_smart_search_render_sidebar_taxonomy_list(
					'custom_tax',
					$taxonomy,
					'custom:' . $taxonomy,
					$terms,
					$block_id,
					'tax-' . $taxonomy,
					$sidebar,
					$taxonomy
				);
				beplus_smart_search_render_sidebar_panel_close();
			}
			break;
	}
}

/**
 * Render one inline filter section by sort key.
 *
 * @param string               $section_id         Section key.
 * @param array<string, mixed> $attrs              Block attributes.
 * @param string               $block_id           Block ID prefix.
 * @param array<int, WP_Term>  $categories         Product categories.
 * @param array<int, WP_Term>  $tags               Product tags.
 * @param array<string, array> $attributes_by_slug Attributes keyed by slug.
 * @return void
 */
function beplus_smart_search_render_inline_filter_section(
	string $section_id,
	array $attrs,
	string $block_id,
	array $categories,
	array $tags,
	array $attributes_by_slug
): void {
	switch ( $section_id ) {
		case 'keyword':
			?>
			<div class="beplus-smart-search__field beplus-smart-search__field--keyword">
				<label class="screen-reader-text" for="<?php echo esc_attr( $block_id ); ?>-keyword">
					<?php esc_html_e( 'Search products', 'beplus-smart-search' ); ?>
				</label>
				<input
					type="search"
					name="s"
					id="<?php echo esc_attr( $block_id ); ?>-keyword"
					class="beplus-smart-search__input"
					placeholder="<?php echo esc_attr( $attrs['placeholder'] ); ?>"
					value=""
					autocomplete="off"
					data-bpss-filter="keyword"
				/>
			</div>
			<?php
			break;

		case 'category':
			?>
			<div class="beplus-smart-search__field beplus-smart-search__field--category" data-bpss-facet-panel="category">
				<label class="screen-reader-text" for="<?php echo esc_attr( $block_id ); ?>-cat">
					<?php esc_html_e( 'Category', 'beplus-smart-search' ); ?>
				</label>
				<select name="product_cat" id="<?php echo esc_attr( $block_id ); ?>-cat" class="beplus-smart-search__select" data-bpss-filter="category" data-bpss-facet-group="category">
					<option value=""><?php esc_html_e( 'All categories', 'beplus-smart-search' ); ?></option>
					<?php foreach ( $categories as $term ) : ?>
						<option value="<?php echo esc_attr( $term->slug ); ?>" data-bpss-term-slug="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php
			break;

		case 'tag':
			?>
			<div class="beplus-smart-search__field beplus-smart-search__field--tag" data-bpss-facet-panel="tag">
				<label class="screen-reader-text" for="<?php echo esc_attr( $block_id ); ?>-tag">
					<?php esc_html_e( 'Tag', 'beplus-smart-search' ); ?>
				</label>
				<select name="product_tag" id="<?php echo esc_attr( $block_id ); ?>-tag" class="beplus-smart-search__select" data-bpss-filter="tag" data-bpss-facet-group="tag">
					<option value=""><?php esc_html_e( 'All tags', 'beplus-smart-search' ); ?></option>
					<?php foreach ( $tags as $term ) : ?>
						<option value="<?php echo esc_attr( $term->slug ); ?>" data-bpss-term-slug="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php
			break;

		case 'stock':
			?>
			<div class="beplus-smart-search__field beplus-smart-search__field--stock">
				<label class="screen-reader-text" for="<?php echo esc_attr( $block_id ); ?>-stock">
					<?php esc_html_e( 'Stock status', 'beplus-smart-search' ); ?>
				</label>
				<select name="stock_status" id="<?php echo esc_attr( $block_id ); ?>-stock" class="beplus-smart-search__select" data-bpss-filter="stock">
					<option value=""><?php esc_html_e( 'All stock', 'beplus-smart-search' ); ?></option>
					<option value="instock"><?php esc_html_e( 'In stock', 'beplus-smart-search' ); ?></option>
					<option value="outofstock"><?php esc_html_e( 'Out of stock', 'beplus-smart-search' ); ?></option>
					<option value="onbackorder"><?php esc_html_e( 'On backorder', 'beplus-smart-search' ); ?></option>
				</select>
			</div>
			<?php
			break;

		case 'on_sale':
			?>
			<div class="beplus-smart-search__field beplus-smart-search__field--on-sale">
				<label for="<?php echo esc_attr( $block_id ); ?>-on-sale">
					<input type="checkbox" name="on_sale" id="<?php echo esc_attr( $block_id ); ?>-on-sale" value="1" data-bpss-filter="on_sale" />
					<?php esc_html_e( 'On sale only', 'beplus-smart-search' ); ?>
				</label>
			</div>
			<?php
			break;

		case 'rating':
			?>
			<div class="beplus-smart-search__field beplus-smart-search__field--rating">
				<label class="screen-reader-text" for="<?php echo esc_attr( $block_id ); ?>-rating">
					<?php esc_html_e( 'Minimum rating', 'beplus-smart-search' ); ?>
				</label>
				<select name="min_rating" id="<?php echo esc_attr( $block_id ); ?>-rating" class="beplus-smart-search__select" data-bpss-filter="rating" data-bpss-facet-group="rating">
					<option value=""><?php esc_html_e( 'All ratings', 'beplus-smart-search' ); ?></option>
					<?php foreach ( beplus_smart_search_get_rating_filter_options() as $option ) : ?>
						<option value="<?php echo esc_attr( (string) $option['value'] ); ?>" data-bpss-term-slug="<?php echo esc_attr( (string) $option['value'] ); ?>"><?php echo esc_html( $option['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php
			break;

		case 'featured':
			?>
			<div class="beplus-smart-search__field beplus-smart-search__field--featured">
				<label for="<?php echo esc_attr( $block_id ); ?>-featured">
					<input type="checkbox" name="featured" id="<?php echo esc_attr( $block_id ); ?>-featured" value="1" data-bpss-filter="featured" />
					<?php esc_html_e( 'Featured only', 'beplus-smart-search' ); ?>
				</label>
			</div>
			<?php
			break;

		case 'brand':
			$brand_taxonomy = beplus_smart_search_get_brand_taxonomy();
			$brand_terms    = $brand_taxonomy ? beplus_smart_search_get_brand_terms() : array();
			if ( ! $brand_taxonomy || empty( $brand_terms ) ) {
				break;
			}
			$brand_object = get_taxonomy( $brand_taxonomy );
			$brand_label  = $brand_object instanceof \WP_Taxonomy ? $brand_object->labels->name : __( 'Brand', 'beplus-smart-search' );
			?>
			<div class="beplus-smart-search__field beplus-smart-search__field--brand" data-bpss-facet-panel="brand" data-bpss-taxonomy="<?php echo esc_attr( $brand_taxonomy ); ?>">
				<label class="screen-reader-text" for="<?php echo esc_attr( $block_id ); ?>-brand">
					<?php echo esc_html( $brand_label ); ?>
				</label>
				<select name="<?php echo esc_attr( $brand_taxonomy ); ?>" id="<?php echo esc_attr( $block_id ); ?>-brand" class="beplus-smart-search__select" data-bpss-filter="brand" data-taxonomy-slug="<?php echo esc_attr( $brand_taxonomy ); ?>" data-bpss-facet-group="brand">
					<option value=""><?php echo esc_html( sprintf( __( 'All %s', 'beplus-smart-search' ), $brand_label ) ); ?></option>
					<?php foreach ( $brand_terms as $term ) : ?>
						<option value="<?php echo esc_attr( $term->slug ); ?>" data-bpss-term-slug="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php
			break;

		default:
			if ( 0 === strpos( $section_id, 'attribute:' ) ) {
				$slug      = substr( $section_id, strlen( 'attribute:' ) );
				$attribute = $attributes_by_slug[ $slug ] ?? null;
				if ( ! $attribute ) {
					break;
				}
				?>
				<div class="beplus-smart-search__field beplus-smart-search__field--attribute" data-attribute="<?php echo esc_attr( $attribute['slug'] ); ?>" data-bpss-facet-panel="attribute" data-bpss-attr-slug="<?php echo esc_attr( $attribute['slug'] ); ?>">
					<label class="screen-reader-text" for="<?php echo esc_attr( $block_id ); ?>-attr-<?php echo esc_attr( $attribute['slug'] ); ?>">
						<?php echo esc_html( $attribute['label'] ); ?>
					</label>
					<select
						name="filter_<?php echo esc_attr( $attribute['slug'] ); ?>"
						id="<?php echo esc_attr( $block_id ); ?>-attr-<?php echo esc_attr( $attribute['slug'] ); ?>"
						class="beplus-smart-search__select"
						data-bpss-filter="attribute"
						data-attribute-slug="<?php echo esc_attr( $attribute['slug'] ); ?>"
						data-bpss-facet-group="attribute"
						data-bpss-attr-slug="<?php echo esc_attr( $attribute['slug'] ); ?>"
					>
						<option value=""><?php echo esc_html( sprintf( __( 'All %s', 'beplus-smart-search' ), $attribute['label'] ) ); ?></option>
						<?php foreach ( $attribute['terms'] as $term ) : ?>
							<option value="<?php echo esc_attr( $term->slug ); ?>" data-bpss-term-slug="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php
				break;
			}

			if ( 0 === strpos( $section_id, 'custom:' ) ) {
				$taxonomy = substr( $section_id, strlen( 'custom:' ) );
				$label    = beplus_smart_search_get_filter_section_catalog()[ $section_id ] ?? $taxonomy;
				$terms    = beplus_smart_search_get_taxonomy_terms( $taxonomy );
				if ( empty( $terms ) ) {
					break;
				}
				?>
				<div class="beplus-smart-search__field beplus-smart-search__field--custom-tax" data-bpss-facet-panel="custom-tax" data-bpss-taxonomy="<?php echo esc_attr( $taxonomy ); ?>">
					<label class="screen-reader-text" for="<?php echo esc_attr( $block_id . '-tax-' . $taxonomy ); ?>">
						<?php echo esc_html( $label ); ?>
					</label>
					<select name="<?php echo esc_attr( $taxonomy ); ?>" id="<?php echo esc_attr( $block_id . '-tax-' . $taxonomy ); ?>" class="beplus-smart-search__select" data-bpss-filter="custom_tax" data-taxonomy-slug="<?php echo esc_attr( $taxonomy ); ?>" data-bpss-facet-group="custom_tax">
						<option value=""><?php echo esc_html( sprintf( __( 'All %s', 'beplus-smart-search' ), $label ) ); ?></option>
						<?php foreach ( $terms as $term ) : ?>
							<option value="<?php echo esc_attr( $term->slug ); ?>" data-bpss-term-slug="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php
			}
			break;
	}
}

/**
 * Render inline (horizontal) filter form.
 *
 * @param array<string, mixed> $attrs           Block attributes.
 * @param string               $block_id        Block ID prefix.
 * @param array<int, WP_Term>  $categories      Product categories.
 * @param array<int, WP_Term>  $tags            Product tags.
 * @param array<int, array>    $attributes_list Attribute definitions.
 * @return void
 */
function beplus_smart_search_render_inline_form( array $attrs, string $block_id, array $categories, array $tags, array $attributes_list ): void {
	$attributes_by_slug = array();

	foreach ( $attributes_list as $attribute ) {
		$attributes_by_slug[ $attribute['slug'] ] = $attribute;
	}

	foreach ( beplus_smart_search_resolve_filter_order( $attrs ) as $section_id ) {
		if ( ! beplus_smart_search_should_render_filter_section( $section_id, $attrs, $categories, $tags, $attributes_by_slug ) ) {
			continue;
		}

		beplus_smart_search_render_inline_filter_section(
			$section_id,
			$attrs,
			$block_id,
			$categories,
			$tags,
			$attributes_by_slug
		);
	}
}

/**
 * Render sidebar filter form matching storefront design.
 *
 * @param array<string, mixed> $attrs           Block attributes.
 * @param string               $block_id        Block ID prefix.
 * @param array<int, WP_Term>  $categories      Product categories.
 * @param array<int, WP_Term>  $tags            Product tags.
 * @param array<int, array>    $attributes_list Attribute definitions.
 * @param array<string, mixed> $sidebar         Sidebar settings.
 * @return void
 */
function beplus_smart_search_render_sidebar_form( array $attrs, string $block_id, array $categories, array $tags, array $attributes_list, array $sidebar ): void {
	$price_settings     = beplus_smart_search_get_price_settings();
	$attributes_by_slug = array();

	foreach ( $attributes_list as $attribute ) {
		$attributes_by_slug[ $attribute['slug'] ] = $attribute;
	}

	foreach ( beplus_smart_search_resolve_filter_order( $attrs ) as $section_id ) {
		if ( ! beplus_smart_search_should_render_filter_section( $section_id, $attrs, $categories, $tags, $attributes_by_slug ) ) {
			continue;
		}

		beplus_smart_search_render_sidebar_filter_section(
			$section_id,
			$attrs,
			$block_id,
			$categories,
			$tags,
			$attributes_by_slug,
			$sidebar,
			$price_settings
		);
	}
}
