import {
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TabPanel,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BlockEditProps } from '@wordpress/blocks';
import ServerSideRender from '@wordpress/server-side-render';
import AttributeFilters from './attribute-filters';
import SortTab from './sort-tab';
import type { BlockAttributes } from './types';

const INSPECTOR_TABS = [
	{ name: 'filters', title: __( 'Filters', 'beplus-fast-product-filter-live-search-for-woocommerce' ) },
	{ name: 'sort', title: __( 'Sort', 'beplus-fast-product-filter-live-search-for-woocommerce' ) },
	{ name: 'layout', title: __( 'Layout', 'beplus-fast-product-filter-live-search-for-woocommerce' ) },
];

export default function Edit( {
	attributes,
	setAttributes,
}: BlockEditProps< BlockAttributes > ) {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<TabPanel tabs={ INSPECTOR_TABS }>
					{ ( tab ) => {
						if ( tab.name === 'filters' ) {
							return (
								<PanelBody title={ __( 'Filters', 'beplus-fast-product-filter-live-search-for-woocommerce' ) } initialOpen={ true }>
									<ToggleControl
										label={ __( 'Keyword search', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
										checked={ attributes.showKeyword }
										onChange={ ( value ) => setAttributes( { showKeyword: value } ) }
									/>
									<ToggleControl
										label={ __( 'Category', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
										checked={ attributes.showCategory }
										onChange={ ( value ) => setAttributes( { showCategory: value } ) }
									/>
									<ToggleControl
										label={ __( 'Price filter', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
										checked={ attributes.showPrice }
										onChange={ ( value ) => setAttributes( { showPrice: value } ) }
									/>
									<ToggleControl
										label={ __( 'Brand', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
										checked={ attributes.showBrand }
										onChange={ ( value ) => setAttributes( { showBrand: value } ) }
									/>
									<AttributeFilters
										attributeSlugs={ attributes.attributeSlugs ?? [] }
										setAttributes={ setAttributes }
									/>
									<ToggleControl
										label={ __( 'Tag', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
										checked={ attributes.showTag }
										onChange={ ( value ) => setAttributes( { showTag: value } ) }
									/>
									<ToggleControl
										label={ __( 'Stock', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
										checked={ attributes.showStock }
										onChange={ ( value ) => setAttributes( { showStock: value } ) }
									/>
									<ToggleControl
										label={ __( 'On sale', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
										checked={ attributes.showOnSale }
										onChange={ ( value ) => setAttributes( { showOnSale: value } ) }
									/>
									<ToggleControl
										label={ __( 'Featured products', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
										checked={ attributes.showFeatured }
										onChange={ ( value ) => setAttributes( { showFeatured: value } ) }
									/>
									<ToggleControl
										label={ __( 'Rating', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
										checked={ attributes.showRating }
										onChange={ ( value ) => setAttributes( { showRating: value } ) }
									/>
									<ToggleControl
										label={ __( 'Custom taxonomies', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
										checked={ attributes.showCustomTaxonomies }
										onChange={ ( value ) =>
											setAttributes( { showCustomTaxonomies: value } )
										}
									/>
								</PanelBody>
							);
						}

						if ( tab.name === 'sort' ) {
							return (
								<PanelBody title={ __( 'Sort', 'beplus-fast-product-filter-live-search-for-woocommerce' ) } initialOpen={ true }>
									<SortTab
										attributes={ attributes }
										setAttributes={ setAttributes }
									/>
								</PanelBody>
							);
						}

						if ( tab.name === 'layout' ) {
							return (
								<PanelBody title={ __( 'Layout', 'beplus-fast-product-filter-live-search-for-woocommerce' ) } initialOpen={ true }>
									<TextControl
										label={ __( 'Placeholder', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
										value={ attributes.placeholder }
										onChange={ ( value ) => setAttributes( { placeholder: value } ) }
									/>
									<SelectControl
										label={ __( 'Filter display', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
										value={
											( attributes.layout as string ) === 'stacked'
												? 'sidebar'
												: attributes.layout
										}
										options={ [
											{
												label: __( 'Filter bar', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
												value: 'inline',
											},
											{
												label: __( 'Sidebar panel', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
												value: 'sidebar',
											},
										] }
										help={ __(
											'Filter bar shows controls in a compact row. Sidebar panel shows collapsible filter sections in a column — ideal next to a Product Collection block.',
											'beplus-fast-product-filter-live-search-for-woocommerce'
										) }
										onChange={ ( value ) =>
											setAttributes( { layout: value as BlockAttributes['layout'] } )
										}
									/>
									<ToggleControl
										label={ __( 'Responsive mobile drawer', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
										checked={ attributes.enableResponsive ?? false }
										help={ __(
											'On small screens, hide the filter column and show a floating filter button. Tapping it opens filters in a slide-in panel from the left.',
											'beplus-fast-product-filter-live-search-for-woocommerce'
										) }
										onChange={ ( value ) =>
											setAttributes( { enableResponsive: value } )
										}
									/>
									<ToggleControl
										label={ __( 'Active filters above results', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
										checked={ attributes.showActiveFilters ?? true }
										help={ __(
											'Show applied filters and a clear action above the Product Collection block.',
											'beplus-fast-product-filter-live-search-for-woocommerce'
										) }
										onChange={ ( value ) =>
											setAttributes( { showActiveFilters: value } )
										}
									/>
								</PanelBody>
							);
						}

						return null;
					} }
				</TabPanel>
			</InspectorControls>
			<ServerSideRender
				block="beplus-fast-product-filter-live-search-for-woocommerce/advanced-woo-search"
				attributes={ attributes as unknown as Record< string, unknown > }
			/>
		</div>
	);
}
