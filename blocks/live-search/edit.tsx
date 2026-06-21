import {
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	RadioControl,
	TextControl,
	ToggleControl,
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
				<PanelBody title={ __( 'Search', 'beplus-smart-search' ) } initialOpen={ true }>
					<TextControl
						label={ __( 'Placeholder', 'beplus-smart-search' ) }
						value={ attributes.placeholder }
						onChange={ ( value ) => setAttributes( { placeholder: value } ) }
					/>
					<RadioControl
						label={ __( 'Search scope', 'beplus-smart-search' ) }
						selected={ attributes.searchScope }
						options={ [
							{
								label: __( 'All categories', 'beplus-smart-search' ),
								value: 'all',
							},
							{
								label: __( 'Selected categories only', 'beplus-smart-search' ),
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
						label={ __( 'Show category filter on frontend', 'beplus-smart-search' ) }
						checked={ attributes.showCategory }
						onChange={ ( value ) => setAttributes( { showCategory: value } ) }
						help={
							isLimited
								? __(
										'When limited, the dropdown only lists the selected categories.',
										'beplus-smart-search'
								  )
								: __(
										'Let visitors narrow search by category.',
										'beplus-smart-search'
								  )
						}
					/>
					<RangeControl
						label={ __( 'Max results', 'beplus-smart-search' ) }
						value={ attributes.maxResults }
						onChange={ ( value ) =>
							setAttributes( { maxResults: value ?? attributes.maxResults } )
						}
						min={ 1 }
						max={ 12 }
					/>
					<RangeControl
						label={ __( 'Min characters', 'beplus-smart-search' ) }
						value={ attributes.minChars }
						onChange={ ( value ) =>
							setAttributes( { minChars: value ?? attributes.minChars } )
						}
						min={ 1 }
						max={ 5 }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Search in', 'beplus-smart-search' ) } initialOpen={ true }>
					<SearchFields
						searchFields={ attributes.searchFields ?? [ 'title' ] }
						setAttributes={ setAttributes }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Suggestions & matching', 'beplus-smart-search' ) } initialOpen={ true }>
					<ToggleControl
						label={ __( 'Enable suggestions', 'beplus-smart-search' ) }
						checked={ attributes.enableSuggestions }
						onChange={ ( value ) => setAttributes( { enableSuggestions: value } ) }
						help={ __(
							'Show inline autocomplete inside the search input.',
							'beplus-smart-search'
						) }
					/>
					<ToggleControl
						label={ __( 'Misspelling fix', 'beplus-smart-search' ) }
						checked={ attributes.misspellingFix }
						onChange={ ( value ) => setAttributes( { misspellingFix: value } ) }
						help={ __(
							'When no results match, suggest the closest product title word.',
							'beplus-smart-search'
						) }
					/>
					<RadioControl
						label={ __( 'Exact match', 'beplus-smart-search' ) }
						selected={ attributes.exactMatch ? 'yes' : 'no' }
						options={ [
							{ label: __( 'Yes', 'beplus-smart-search' ), value: 'yes' },
							{ label: __( 'No', 'beplus-smart-search' ), value: 'no' },
						] }
						onChange={ ( value ) =>
							setAttributes( { exactMatch: value === 'yes' } )
						}
					/>
					<RadioControl
						label={ __( 'Search logic', 'beplus-smart-search' ) }
						selected={ attributes.searchLogic }
						options={ [
							{ label: __( 'OR', 'beplus-smart-search' ), value: 'or' },
							{ label: __( 'AND', 'beplus-smart-search' ), value: 'and' },
						] }
						onChange={ ( value ) =>
							setAttributes( {
								searchLogic: value as BlockAttributes['searchLogic'],
							} )
						}
						help={ __(
							'OR matches any keyword; AND requires all keywords across the selected search fields.',
							'beplus-smart-search'
						) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Display', 'beplus-smart-search' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Add to cart button', 'beplus-smart-search' ) }
						checked={ attributes.showAddToCart }
						onChange={ ( value ) => setAttributes( { showAddToCart: value } ) }
					/>
					<ToggleControl
						label={ __( 'View all results link', 'beplus-smart-search' ) }
						checked={ attributes.showViewAll }
						onChange={ ( value ) => setAttributes( { showViewAll: value } ) }
					/>
					<TextControl
						label={ __( 'Highlight color', 'beplus-smart-search' ) }
						value={ attributes.highlightColor }
						onChange={ ( value ) => setAttributes( { highlightColor: value } ) }
						help={ __( 'Background color for matched keywords in results.', 'beplus-smart-search' ) }
					/>
				</PanelBody>
			</InspectorControls>

			<ServerSideRender
				block="beplus-smart-search/live-search"
				attributes={ attributes as unknown as Record< string, unknown > }
			/>
		</div>
	);
}
