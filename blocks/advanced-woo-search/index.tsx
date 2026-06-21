import { BlockConfiguration, registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';

interface Attributes {
	[key: string]: string | number | boolean | string[] | undefined;
	placeholder: string;
	showKeyword: boolean;
	showCategory: boolean;
	showTag: boolean;
	showAttributes: boolean;
	showStock: boolean;
	showOnSale: boolean;
	showRating: boolean;
	showFeatured: boolean;
	showBrand: boolean;
	showCustomTaxonomies: boolean;
	attributeSlugs: string[];
	layout: 'inline' | 'sidebar';
	resultsMode: 'filter-collection' | 'own-grid';
	resultsSelector: string;
	debounceMs: number;
	minChars: number;
	perPage: number;
	enableLiveSearch: boolean;
	showResultCount: boolean;
	showClearButton: boolean;
	showPrice: boolean;
	filterOrder: string[];
}

registerBlockType(
	metadata as unknown as BlockConfiguration< Attributes >,
	{
		edit: Edit,
		save: () => null,
	},
);
