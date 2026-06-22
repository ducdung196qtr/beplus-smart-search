export interface AttributeDefinition {
	slug: string;
	label: string;
}

export interface BpssEditorData {
	filterSections?: Record< string, string >;
	attributeDefinitions?: AttributeDefinition[];
	productCategories?: Array< { slug: string; name: string } >;
}

export interface BlockAttributes {
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
	enableResponsive: boolean;
	showActiveFilters: boolean;
}

declare global {
	interface Window {
		bpssData?: BpssEditorData;
	}
}

export {};
