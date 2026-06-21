import { ToggleControl } from '@wordpress/components';

interface AttributeDefinition {
	slug: string;
	label: string;
}

interface AttributeFiltersProps {
	attributeSlugs: string[];
	setAttributes: ( attrs: { attributeSlugs?: string[] } ) => void;
}

declare global {
	interface Window {
		bpssData?: {
			attributeDefinitions?: AttributeDefinition[];
		};
	}
}

function getAttributeDefinitions(): AttributeDefinition[] {
	return window.bpssData?.attributeDefinitions ?? [];
}

function isAttributeShown(
	slug: string,
	attributeSlugs: string[],
	allSlugs: string[]
): boolean {
	if ( attributeSlugs.length === 0 ) {
		return allSlugs.includes( slug );
	}

	return attributeSlugs.includes( slug );
}

function toggleAttributeSlug(
	slug: string,
	attributeSlugs: string[],
	allSlugs: string[]
): string[] {
	const active =
		attributeSlugs.length === 0 ? [ ...allSlugs ] : [ ...attributeSlugs ];

	if ( active.includes( slug ) ) {
		const next = active.filter( ( item ) => item !== slug );
		return next.length === allSlugs.length ? [] : next;
	}

	const next = [ ...active, slug ];
	return next.length === allSlugs.length ? [] : next;
}

export default function AttributeFilters( {
	attributeSlugs,
	setAttributes,
}: AttributeFiltersProps ) {
	const definitions = getAttributeDefinitions();
	const allSlugs = definitions.map( ( item ) => item.slug );

	return (
		<>
			{ definitions.map( ( attribute ) => (
				<ToggleControl
					key={ attribute.slug }
					label={ attribute.label }
					checked={ isAttributeShown(
						attribute.slug,
						attributeSlugs,
						allSlugs
					) }
					onChange={ () =>
						setAttributes( {
							attributeSlugs: toggleAttributeSlug(
								attribute.slug,
								attributeSlugs,
								allSlugs
							),
						} )
					}
				/>
			) ) }
		</>
	);
}
