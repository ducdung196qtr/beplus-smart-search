import { FormTokenField } from '@wordpress/components';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import '../advanced-woo-search/types';

interface CategoryDefinition {
	slug: string;
	name: string;
}

interface CategoryScopeProps {
	limitCategorySlugs: string[];
	setAttributes: ( attrs: { limitCategorySlugs?: string[] } ) => void;
}

function getCategories(): CategoryDefinition[] {
	return window.bpssData?.productCategories ?? [];
}

function categoryToken( category: CategoryDefinition ): string {
	return `${ category.name } (${ category.slug })`;
}

export default function CategoryScope( {
	limitCategorySlugs,
	setAttributes,
}: CategoryScopeProps ) {
	const categories = getCategories();

	const tokenToSlug = useMemo( () => {
		const map = new Map< string, string >();
		categories.forEach( ( category ) => {
			map.set( categoryToken( category ), category.slug );
		} );
		return map;
	}, [ categories ] );

	const slugToToken = useMemo( () => {
		const map = new Map< string, string >();
		categories.forEach( ( category ) => {
			map.set( category.slug, categoryToken( category ) );
		} );
		return map;
	}, [ categories ] );

	if ( categories.length === 0 ) {
		return (
			<p className="components-base-control__help">
				{ __( 'No product categories found.', 'beplus-smart-search' ) }
			</p>
		);
	}

	const selectedTokens = limitCategorySlugs
		.map( ( slug ) => slugToToken.get( slug ) )
		.filter( ( token ): token is string => Boolean( token ) );

	const allTokens = categories.map( ( category ) => categoryToken( category ) );

	return (
		<>
			<FormTokenField
				label={ __( 'Categories', 'beplus-smart-search' ) }
				value={ selectedTokens }
				suggestions={ allTokens }
				onChange={ ( tokens ) => {
					const slugs = tokens
						.map( ( token ) =>
							tokenToSlug.get(
								typeof token === 'string' ? token : String( token ),
							),
						)
						.filter( ( slug ): slug is string => Boolean( slug ) );
					setAttributes( { limitCategorySlugs: slugs } );
				} }
				__experimentalExpandOnFocus
				__experimentalShowHowTo={ false }
			/>
			<p className="components-base-control__help">
				{ __(
					'Type to search categories. Select one or more to limit search scope.',
					'beplus-smart-search'
				) }
			</p>
		</>
	);
}
