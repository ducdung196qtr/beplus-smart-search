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
import ServerSideRender from '@wordpress/server-side-render';
import AttributeFilters from './attribute-filters';
import SortTab from './sort-tab';

interface Attributes {
	[key: string]: string | number | boolean | string[] | undefined;
	placeholder: string;
	showKeyword: boolean;
	showCategory: boolean;
	showTag: boolean;
	showStock: boolean;
	showOnSale: boolean;
	showRating: boolean;
	showFeatured: boolean;
	showBrand: boolean;
	showCustomTaxonomies: boolean;
	attributeSlugs: string[];
	filterOrder: string[];
	layout: 'inline' | 'sidebar';
	debounceMs: number;
	minChars: number;
	perPage: number;
	enableLiveSearch: boolean;
	showResultCount: boolean;
	showClearButton: boolean;
	showPrice: boolean;
}

interface EditProps {
	attributes: Attributes;
	setAttributes: ( attrs: Partial< Attributes > ) => void;
}

const INSPECTOR_TABS = [
	{ name: 'filters', title: __( 'Filters', 'beplus-smart-search' ) },
	{ name: 'sort', title: __( 'Sort', 'beplus-smart-search' ) },
	{ name: 'layout', title: __( 'Layout', 'beplus-smart-search' ) },
];

export default function Edit( { attributes, setAttributes }: EditProps ) {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<TabPanel tabs={ INSPECTOR_TABS }>
					{ ( tab ) => {
						if ( tab.name === 'filters' ) {
							return (
								<PanelBody title={ __( 'Filters', 'beplus-smart-search' ) } initialOpen={ true }>
									<ToggleControl
										label={ __( 'Keyword search', 'beplus-smart-search' ) }
										checked={ attributes.showKeyword }
										onChange={ ( value ) => setAttributes( { showKeyword: value } ) }
									/>
									<ToggleControl
										label={ __( 'Category', 'beplus-smart-search' ) }
										checked={ attributes.showCategory }
										onChange={ ( value ) => setAttributes( { showCategory: value } ) }
									/>
									<ToggleControl
										label={ __( 'Price filter', 'beplus-smart-search' ) }
										checked={ attributes.showPrice }
										onChange={ ( value ) => setAttributes( { showPrice: value } ) }
									/>
									<ToggleControl
										label={ __( 'Brand', 'beplus-smart-search' ) }
										checked={ attributes.showBrand }
										onChange={ ( value ) => setAttributes( { showBrand: value } ) }
									/>
									<AttributeFilters
										attributeSlugs={ attributes.attributeSlugs ?? [] }
										setAttributes={ setAttributes }
									/>
									<ToggleControl
										label={ __( 'Tag', 'beplus-smart-search' ) }
										checked={ attributes.showTag }
										onChange={ ( value ) => setAttributes( { showTag: value } ) }
									/>
									<ToggleControl
										label={ __( 'Stock', 'beplus-smart-search' ) }
										checked={ attributes.showStock }
										onChange={ ( value ) => setAttributes( { showStock: value } ) }
									/>
									<ToggleControl
										label={ __( 'On sale', 'beplus-smart-search' ) }
										checked={ attributes.showOnSale }
										onChange={ ( value ) => setAttributes( { showOnSale: value } ) }
									/>
									<ToggleControl
										label={ __( 'Featured products', 'beplus-smart-search' ) }
										checked={ attributes.showFeatured }
										onChange={ ( value ) => setAttributes( { showFeatured: value } ) }
									/>
									<ToggleControl
										label={ __( 'Rating', 'beplus-smart-search' ) }
										checked={ attributes.showRating }
										onChange={ ( value ) => setAttributes( { showRating: value } ) }
									/>
									<ToggleControl
										label={ __( 'Custom taxonomies', 'beplus-smart-search' ) }
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
								<PanelBody title={ __( 'Sort', 'beplus-smart-search' ) } initialOpen={ true }>
									<SortTab
										attributes={ attributes }
										setAttributes={ setAttributes }
									/>
								</PanelBody>
							);
						}

						if ( tab.name === 'layout' ) {
							return (
								<PanelBody title={ __( 'Layout', 'beplus-smart-search' ) } initialOpen={ true }>
									<TextControl
										label={ __( 'Placeholder', 'beplus-smart-search' ) }
										value={ attributes.placeholder }
										onChange={ ( value ) => setAttributes( { placeholder: value } ) }
									/>
									<SelectControl
										label={ __( 'Filter display', 'beplus-smart-search' ) }
										value={
											( attributes.layout as string ) === 'stacked'
												? 'sidebar'
												: attributes.layout
										}
										options={ [
											{
												label: __( 'Filter bar', 'beplus-smart-search' ),
												value: 'inline',
											},
											{
												label: __( 'Sidebar panel', 'beplus-smart-search' ),
												value: 'sidebar',
											},
										] }
										help={ __(
											'Filter bar shows controls in a compact row. Sidebar panel shows collapsible filter sections in a column — ideal next to a Product Collection block.',
											'beplus-smart-search'
										) }
										onChange={ ( value ) =>
											setAttributes( { layout: value as Attributes['layout'] } )
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
				block="beplus-smart-search/advanced-woo-search"
				attributes={ attributes as unknown as Record< string, unknown > }
			/>
		</div>
	);
}
