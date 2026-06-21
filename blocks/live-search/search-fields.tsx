import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

interface SearchFieldsProps {
	searchFields: string[];
	setAttributes: ( attrs: { searchFields?: string[] } ) => void;
}

const FIELD_OPTIONS = [
	{ key: 'title', label: __( 'Title', 'beplus-smart-search' ) },
	{ key: 'sku', label: __( 'SKU', 'beplus-smart-search' ) },
	{ key: 'content', label: __( 'Content', 'beplus-smart-search' ) },
	{ key: 'categories', label: __( 'Categories', 'beplus-smart-search' ) },
	{ key: 'tags', label: __( 'Tags', 'beplus-smart-search' ) },
	{ key: 'attributes', label: __( 'Attributes', 'beplus-smart-search' ) },
] as const;

function toggleField( key: string, searchFields: string[] ): string[] {
	const next = searchFields.includes( key )
		? searchFields.filter( ( field ) => field !== key )
		: [ ...searchFields, key ];

	return next.length > 0 ? next : [ 'title' ];
}

export default function SearchFields( {
	searchFields,
	setAttributes,
}: SearchFieldsProps ) {
	return (
		<>
			<p className="components-base-control__help">
				{ __(
					'Choose which product data to search. Matching fields (except Content) appear under each result.',
					'beplus-smart-search'
				) }
			</p>
			{ FIELD_OPTIONS.map( ( field ) => (
				<ToggleControl
					key={ field.key }
					label={ field.label }
					checked={ searchFields.includes( field.key ) }
					onChange={ () =>
						setAttributes( {
							searchFields: toggleField( field.key, searchFields ),
						} )
					}
				/>
			) ) }
		</>
	);
}
