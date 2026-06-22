export interface BlockAttributes {
	[key: string]: string | number | boolean | string[] | undefined;
	placeholder: string;
	showCategory: boolean;
	searchScope: 'all' | 'limited';
	limitCategorySlugs: string[];
	searchFields: string[];
	maxResults: number;
	debounceMs: number;
	minChars: number;
	enableSuggestions: boolean;
	suggestionLayout: 'inline' | 'tags';
	misspellingFix: boolean;
	exactMatch: boolean;
	searchLogic: 'or' | 'and';
	showAddToCart: boolean;
	showViewAll: boolean;
	highlightColor: string;
}

export interface CategoryDefinition {
	slug: string;
	name: string;
}
