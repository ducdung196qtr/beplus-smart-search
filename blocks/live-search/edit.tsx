import {
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	BaseControl,
	PanelBody,
	RangeControl,
	RadioControl,
	TextControl,
	ToggleControl,
	ColorPalette,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import type { BlockEditProps } from '@wordpress/blocks';
import ServerSideRender from '@wordpress/server-side-render';
import CategoryScope from './category-scope';
import SearchFields from './search-fields';
import type { BlockAttributes } from './types';

export default function Edit( {
	attributes,
	setAttributes,
}: BlockEditProps< BlockAttributes > ) {
	const blockProps = useBlockProps();
	const isLimited = attributes.searchScope === 'limited';

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Search', 'beplus-fast-product-filter-live-search-for-woocommerce' ) } initialOpen={ true }>
					<TextControl
						label={ __( 'Placeholder', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
						value={ attributes.placeholder }
						onChange={ ( value ) => setAttributes( { placeholder: value } ) }
					/>
					<RadioControl
						label={ __( 'Search scope', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
						selected={ attributes.searchScope }
						options={ [
							{
								label: __( 'All categories', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
								value: 'all',
							},
							{
								label: __( 'Selected categories only', 'beplus-fast-product-filter-live-search-for-woocommerce' ),
								value: 'limited',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( {
								searchScope: value as BlockAttributes['searchScope'],
							} )
						}
					/>
					{ isLimited && (
						<CategoryScope
							limitCategorySlugs={ attributes.limitCategorySlugs ?? [] }
							setAttributes={ setAttributes }
						/>
					) }
					<ToggleControl
						label={ __( 'Show category filter on frontend', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
						checked={ attributes.showCategory }
						onChange={ ( value ) => setAttributes( { showCategory: value } ) }
						help={
							isLimited
								? __(
										'When limited, the dropdown only lists the selected categories.',
										'beplus-fast-product-filter-live-search-for-woocommerce'
								  )
								: __(
										'Let visitors narrow search by category.',
										'beplus-fast-product-filter-live-search-for-woocommerce'
								  )
						}
					/>
					<RangeControl
						label={ __( 'Max results', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
						value={ attributes.maxResults }
						onChange={ ( value ) =>
							setAttributes( { maxResults: value ?? attributes.maxResults } )
						}
						min={ 1 }
						max={ 12 }
					/>
					<RangeControl
						label={ __( 'Min characters', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
						value={ attributes.minChars }
						onChange={ ( value ) =>
							setAttributes( { minChars: value ?? attributes.minChars } )
						}
						min={ 1 }
						max={ 5 }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Search in', 'beplus-fast-product-filter-live-search-for-woocommerce' ) } initialOpen={ true }>
					<SearchFields
						searchFields={ attributes.searchFields ?? [ 'title' ] }
						setAttributes={ setAttributes }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Suggestions & matching', 'beplus-fast-product-filter-live-search-for-woocommerce' ) } initialOpen={ true }>
					<ToggleControl
						label={ __( 'Enable suggestions', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
						checked={ attributes.enableSuggestions }
						onChange={ ( value ) => setAttributes( { enableSuggestions: value } ) }
					/>
					{ attributes.enableSuggestions && (
						<RadioControl
							label={ __( 'Suggestions layout', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
							selected={ attributes.suggestionLayout ?? 'inline' }
							options={ [
								{
									label: __(
										'Inline (autocomplete in search box)',
										'beplus-fast-product-filter-live-search-for-woocommerce',
									),
									value: 'inline',
								},
								{
									label: __(
										'Tags (below search, above results)',
										'beplus-fast-product-filter-live-search-for-woocommerce',
									),
									value: 'tags',
								},
							] }
							onChange={ ( value ) =>
								setAttributes( {
									suggestionLayout: value as BlockAttributes['suggestionLayout'],
								} )
							}
						/>
					) }
					<ToggleControl
						label={ __( 'Misspelling fix', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
						checked={ attributes.misspellingFix }
						onChange={ ( value ) => setAttributes( { misspellingFix: value } ) }
						help={ __(
							'When no results match, suggest the closest product title word.',
							'beplus-fast-product-filter-live-search-for-woocommerce'
						) }
					/>
					<RadioControl
						label={ __( 'Exact match', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
						selected={ attributes.exactMatch ? 'yes' : 'no' }
						options={ [
							{ label: __( 'Yes', 'beplus-fast-product-filter-live-search-for-woocommerce' ), value: 'yes' },
							{ label: __( 'No', 'beplus-fast-product-filter-live-search-for-woocommerce' ), value: 'no' },
						] }
						onChange={ ( value ) =>
							setAttributes( { exactMatch: value === 'yes' } )
						}
					/>
					<RadioControl
						label={ __( 'Search logic', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
						selected={ attributes.searchLogic }
						options={ [
							{ label: __( 'OR', 'beplus-fast-product-filter-live-search-for-woocommerce' ), value: 'or' },
							{ label: __( 'AND', 'beplus-fast-product-filter-live-search-for-woocommerce' ), value: 'and' },
						] }
						onChange={ ( value ) =>
							setAttributes( {
								searchLogic: value as BlockAttributes['searchLogic'],
							} )
						}
						help={ __(
							'OR matches any keyword; AND requires all keywords across the selected search fields.',
							'beplus-fast-product-filter-live-search-for-woocommerce'
						) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Display', 'beplus-fast-product-filter-live-search-for-woocommerce' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Add to cart button', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
						checked={ attributes.showAddToCart }
						onChange={ ( value ) => setAttributes( { showAddToCart: value } ) }
					/>
					<ToggleControl
						label={ __( 'View all results link', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
						checked={ attributes.showViewAll }
						onChange={ ( value ) => setAttributes( { showViewAll: value } ) }
					/>
					<BaseControl
						id="highlight-color"
						label={ __( 'Highlight color', 'beplus-fast-product-filter-live-search-for-woocommerce' ) }
					>
						<ColorPalette
							value={ attributes.highlightColor }
							onChange={ ( value ) => setAttributes( { highlightColor: value ?? '#ffff00' } ) }
							clearable={ false }
						/>
					</BaseControl>
				</PanelBody>
			</InspectorControls>

			<ServerSideRender
				block="beplus-fast-product-filter-live-search-for-woocommerce/live-search"
				attributes={ attributes as unknown as Record< string, unknown > }
			/>
		</div>
	);
}
