import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

interface Attributes {
	showKeyword: boolean;
	showCategory: boolean;
	showTag: boolean;
	showStock: boolean;
	showOnSale: boolean;
	showRating: boolean;
	showFeatured: boolean;
	showBrand: boolean;
	showCustomTaxonomies: boolean;
	showPrice: boolean;
	filterOrder: string[];
	attributeSlugs: string[];
}

interface SortTabProps {
	attributes: Attributes;
	setAttributes: ( attrs: Partial< Attributes > ) => void;
}

declare global {
	interface Window {
		bpssData?: {
			filterSections?: Record< string, string >;
			attributeDefinitions?: Array< { slug: string; label: string } >;
		};
	}
}

function getAllAttributeSlugs(): string[] {
	return ( window.bpssData?.attributeDefinitions ?? [] ).map(
		( item ) => item.slug
	);
}

function isAttributeSectionEnabled(
	sectionId: string,
	attributes: Attributes
): boolean {
	const slug = sectionId.slice( 'attribute:'.length );
	const selected = attributes.attributeSlugs ?? [];
	const allSlugs = getAllAttributeSlugs();

	if ( selected.length === 0 ) {
		return allSlugs.includes( slug );
	}

	return selected.includes( slug );
}

function getFilterCatalog(): Record< string, string > {
	return window.bpssData?.filterSections ?? {};
}

function resolveFilterOrder(
	saved: string[],
	catalog: Record< string, string >
): string[] {
	const defaults = Object.keys( catalog );
	const base = saved.length > 0 ? saved : defaults;
	const merged: string[] = [];

	base.forEach( ( id ) => {
		if ( catalog[ id ] && ! merged.includes( id ) ) {
			merged.push( id );
		}
	} );

	defaults.forEach( ( id ) => {
		if ( ! merged.includes( id ) ) {
			merged.push( id );
		}
	} );

	return merged;
}

function isSectionEnabled( sectionId: string, attributes: Attributes ): boolean {
	if ( sectionId === 'keyword' ) {
		return attributes.showKeyword;
	}
	if ( sectionId === 'category' ) {
		return attributes.showCategory;
	}
	if ( sectionId === 'price' ) {
		return attributes.showPrice;
	}
	if ( sectionId === 'tag' ) {
		return attributes.showTag;
	}
	if ( sectionId.startsWith( 'attribute:' ) ) {
		return isAttributeSectionEnabled( sectionId, attributes );
	}
	if ( sectionId === 'stock' ) {
		return attributes.showStock;
	}
	if ( sectionId === 'on_sale' ) {
		return attributes.showOnSale;
	}
	if ( sectionId === 'rating' ) {
		return attributes.showRating;
	}
	if ( sectionId === 'featured' ) {
		return attributes.showFeatured;
	}
	if ( sectionId === 'brand' ) {
		return attributes.showBrand;
	}
	if ( sectionId.startsWith( 'custom:' ) ) {
		return attributes.showCustomTaxonomies;
	}

	return false;
}

function moveItem(
	order: string[],
	index: number,
	direction: -1 | 1
): string[] {
	const target = index + direction;
	if ( target < 0 || target >= order.length ) {
		return order;
	}

	const next = [ ...order ];
	const temp = next[ index ];
	next[ index ] = next[ target ];
	next[ target ] = temp;

	return next;
}

export default function SortTab( { attributes, setAttributes }: SortTabProps ) {
	const catalog = getFilterCatalog();
	const fullOrder = resolveFilterOrder( attributes.filterOrder ?? [], catalog );
	const visibleOrder = fullOrder.filter( ( sectionId ) =>
		isSectionEnabled( sectionId, attributes )
	);

	const persistOrder = ( reorderedVisible: string[] ): void => {
		const hidden = fullOrder.filter(
			( sectionId ) => ! isSectionEnabled( sectionId, attributes )
		);
		setAttributes( { filterOrder: [ ...reorderedVisible, ...hidden ] } );
	};

	if ( visibleOrder.length === 0 ) {
		return (
			<p className="beplus-smart-search-sort-tab__empty">
				{ __( 'Enable filters in the Filters tab to sort them.', 'beplus-smart-search' ) }
			</p>
		);
	}

	return (
		<div className="beplus-smart-search-sort-tab">
			<p className="beplus-smart-search-sort-tab__help">
				{ __(
					'Drag order of filter sections. Each attribute appears as its own panel.',
					'beplus-smart-search'
				) }
			</p>
			<ul className="beplus-smart-search-sort-tab__list">
				{ visibleOrder.map( ( sectionId, index ) => (
					<li
						key={ sectionId }
						className="beplus-smart-search-sort-tab__item"
					>
						<span className="beplus-smart-search-sort-tab__label">
							{ catalog[ sectionId ] ?? sectionId }
						</span>
						<span className="beplus-smart-search-sort-tab__actions">
							<Button
								icon="arrow-up-alt2"
								label={ __( 'Move up', 'beplus-smart-search' ) }
								onClick={ () =>
									persistOrder( moveItem( visibleOrder, index, -1 ) )
								}
								disabled={ index === 0 }
								size="small"
							/>
							<Button
								icon="arrow-down-alt2"
								label={ __( 'Move down', 'beplus-smart-search' ) }
								onClick={ () =>
									persistOrder( moveItem( visibleOrder, index, 1 ) )
								}
								disabled={ index === visibleOrder.length - 1 }
								size="small"
							/>
						</span>
					</li>
				) ) }
			</ul>
		</div>
	);
}
