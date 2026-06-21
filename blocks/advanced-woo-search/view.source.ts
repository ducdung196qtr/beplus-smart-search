/**
 * Advanced Woo Search — frontend view script.
 *
 * @package BePlusSmartSearch
 */

( function () {
	'use strict';

	const SELECTOR = '[data-bpss-advanced-woo-search]';

	interface BpssData {
		restUrl: string;
		nonce: string;
		i18n: Record< string, string >;
	}

	interface BlockConfig {
		resultsMode: string;
		resultsSelector: string;
		debounceMs: number;
		minChars: number;
		perPage: number;
		liveSearch: boolean;
		showResultCount: boolean;
		facetMode: string;
	}

	interface FilterParams {
		page: number;
		per_page: number;
		attribute: Record< string, string | string[] >;
		s?: string;
		product_cat?: string | string[];
		product_tag?: string | string[];
		stock_status?: string;
		on_sale?: boolean;
		featured?: boolean;
		min_rating?: number;
		taxonomy?: Record< string, string | string[] >;
		min_price?: number;
		max_price?: number;
		orderby?: string;
	}

	const URL_PAGE_KEY = 'bpss_page';
	const URL_ORDERBY_KEY = 'orderby';

	const URL_FILTER_KEYS = [
		's',
		'product_cat',
		'product_tag',
		'min_price',
		'max_price',
		'stock_status',
		'on_sale',
		'featured',
		'min_rating',
		URL_ORDERBY_KEY,
	] as const;

	function getFilterTaxonomies( root?: HTMLElement | null ): string[] {
		const source =
			root ||
			( document.querySelector( SELECTOR ) as HTMLElement | null );
		const raw = source?.dataset.bpssFilterTaxonomies || '';

		return raw
			.split( ',' )
			.map( ( item ) => item.trim() )
			.filter( Boolean );
	}

	function splitListParam( value: string ): string[] {
		return value
			.split( ',' )
			.map( ( item ) => item.trim() )
			.filter( Boolean );
	}

	function parseUrlPage(): number {
		const params = new URLSearchParams( window.location.search );
		const queryPage = parseInt( params.get( URL_PAGE_KEY ) || '0', 10 );

		if ( queryPage > 0 ) {
			return queryPage;
		}

		const pathMatch = window.location.pathname.match( /\/page\/(\d+)\/?$/ );
		if ( pathMatch ) {
			return Math.max( 1, parseInt( pathMatch[ 1 ], 10 ) );
		}

		return 1;
	}

	function parseUrlFilters(): Partial< FilterParams > {
		const params = new URLSearchParams( window.location.search );
		const filters: Partial< FilterParams > = {
			attribute: {},
		};

		if ( params.get( 's' ) ) {
			filters.s = params.get( 's' ) || '';
		}

		if ( params.get( 'product_cat' ) ) {
			const cats = splitListParam( params.get( 'product_cat' ) || '' );
			filters.product_cat = cats.length > 1 ? cats : cats[ 0 ] || '';
		}

		if ( params.get( 'product_tag' ) ) {
			const tags = splitListParam( params.get( 'product_tag' ) || '' );
			filters.product_tag = tags.length > 1 ? tags : tags[ 0 ] || '';
		}

		if ( params.get( 'min_price' ) ) {
			filters.min_price = parseFloat( params.get( 'min_price' ) || '0' );
		}

		if ( params.get( 'max_price' ) ) {
			filters.max_price = parseFloat( params.get( 'max_price' ) || '0' );
		}

		if ( params.get( 'stock_status' ) ) {
			filters.stock_status = params.get( 'stock_status' ) || '';
		}

		if ( params.get( 'on_sale' ) === '1' ) {
			filters.on_sale = true;
		}

		if ( params.get( 'featured' ) === '1' ) {
			filters.featured = true;
		}

		if ( params.get( 'min_rating' ) ) {
			const rating = parseInt( params.get( 'min_rating' ) || '0', 10 );
			if ( rating > 0 ) {
				filters.min_rating = rating;
			}
		}

		if ( params.get( URL_ORDERBY_KEY ) ) {
			filters.orderby = params.get( URL_ORDERBY_KEY ) || '';
		}

		params.forEach( ( value, key ) => {
			if ( key.startsWith( 'filter_' ) && value ) {
				const slug = key.slice( 7 );
				const terms = splitListParam( value );
				if ( ! slug || ! terms.length ) {
					return;
				}

				filters.attribute = filters.attribute || {};
				filters.attribute[ slug ] = terms.length > 1 ? terms : terms[ 0 ];
				return;
			}

			if ( ! value || key === URL_PAGE_KEY ) {
				return;
			}

			if ( getFilterTaxonomies().includes( key ) ) {
				const terms = splitListParam( value );
				if ( ! terms.length ) {
					return;
				}
				filters.taxonomy = filters.taxonomy || {};
				filters.taxonomy[ key ] = terms.length > 1 ? terms : terms[ 0 ];
			}
		} );

		return filters;
	}

	function hasUrlSearchState(): boolean {
		const params = new URLSearchParams( window.location.search );

		for ( const key of URL_FILTER_KEYS ) {
			if ( params.get( key ) ) {
				return true;
			}
		}

		let hasAttribute = false;
		params.forEach( ( _value, key ) => {
			if ( key.startsWith( 'filter_' ) ) {
				hasAttribute = true;
			}
			if ( getFilterTaxonomies().includes( key ) ) {
				hasAttribute = true;
			}
		} );

		return hasAttribute || parseUrlPage() > 1;
	}

	function clearUrlFilterParams( url: URL ): void {
		URL_FILTER_KEYS.forEach( ( key ) => {
			url.searchParams.delete( key );
		} );
		url.searchParams.delete( URL_PAGE_KEY );

		const keysToDelete: string[] = [];
		url.searchParams.forEach( ( _value, key ) => {
			if ( key.startsWith( 'filter_' ) ) {
				keysToDelete.push( key );
			}
			if ( getFilterTaxonomies().includes( key ) ) {
				keysToDelete.push( key );
			}
		} );
		keysToDelete.forEach( ( key ) => url.searchParams.delete( key ) );

		url.pathname = url.pathname.replace( /\/page\/\d+\/?$/, '/' );
	}

	function syncUrlFromFilters( filters: FilterParams ): void {
		const url = new URL( window.location.href );
		clearUrlFilterParams( url );

		if ( filters.s ) {
			url.searchParams.set( 's', filters.s );
		}

		if ( filters.product_cat ) {
			const cats = Array.isArray( filters.product_cat )
				? filters.product_cat
				: [ filters.product_cat ];
			if ( cats.length ) {
				url.searchParams.set( 'product_cat', cats.join( ',' ) );
			}
		}

		if ( filters.product_tag ) {
			const tags = Array.isArray( filters.product_tag )
				? filters.product_tag
				: [ filters.product_tag ];
			if ( tags.length ) {
				url.searchParams.set( 'product_tag', tags.join( ',' ) );
			}
		}

		if ( filters.min_price !== undefined && filters.min_price > 0 ) {
			url.searchParams.set( 'min_price', String( filters.min_price ) );
		}

		if ( filters.max_price !== undefined && filters.max_price > 0 ) {
			url.searchParams.set( 'max_price', String( filters.max_price ) );
		}

		if ( filters.stock_status ) {
			url.searchParams.set( 'stock_status', filters.stock_status );
		}

		if ( filters.on_sale ) {
			url.searchParams.set( 'on_sale', '1' );
		}

		if ( filters.featured ) {
			url.searchParams.set( 'featured', '1' );
		}

		if ( filters.min_rating !== undefined && filters.min_rating > 0 ) {
			url.searchParams.set( 'min_rating', String( filters.min_rating ) );
		}

		Object.keys( filters.attribute ).forEach( ( slug ) => {
			const val = filters.attribute[ slug ];
			const str = Array.isArray( val ) ? val.join( ',' ) : String( val || '' );
			if ( str ) {
				url.searchParams.set( 'filter_' + slug, str );
			}
		} );

		if ( filters.taxonomy ) {
			Object.keys( filters.taxonomy ).forEach( ( taxonomy ) => {
				const val = filters.taxonomy?.[ taxonomy ];
				const str = Array.isArray( val ) ? val.join( ',' ) : String( val || '' );
				if ( str ) {
					url.searchParams.set( taxonomy, str );
				}
			} );
		}

		if ( filters.orderby ) {
			url.searchParams.set( URL_ORDERBY_KEY, filters.orderby );
		}

		if ( filters.page > 1 ) {
			url.searchParams.set( URL_PAGE_KEY, String( filters.page ) );
		}

		window.history.replaceState( { bpss: true }, '', url.toString() );
	}

	function getCatalogOrderingScope( root: HTMLElement ): ParentNode {
		const resultsSelector = root.dataset.resultsSelector;
		if ( resultsSelector ) {
			const results = document.querySelector( resultsSelector );
			const scope =
				results?.closest( 'main' ) ||
				results?.parentElement ||
				document;
			if ( scope.querySelector( '.woocommerce-ordering select.orderby' ) ) {
				return scope;
			}
		}

		const main = root.closest( 'main' );
		if ( main?.querySelector( '.woocommerce-ordering select.orderby' ) ) {
			return main;
		}

		return document;
	}

	function getOrderingSelect(
		root: HTMLElement
	): HTMLSelectElement | null {
		return getCatalogOrderingScope( root ).querySelector(
			'.woocommerce-ordering select.orderby'
		) as HTMLSelectElement | null;
	}

	function getDefaultCatalogOrderby( root: HTMLElement ): string {
		const select = getOrderingSelect( root );
		if ( ! select ) {
			return 'menu_order';
		}

		if ( root.dataset.bpssDefaultOrderby ) {
			return root.dataset.bpssDefaultOrderby;
		}

		const first = select.querySelector( 'option' ) as HTMLOptionElement | null;
		const defaultValue = first?.value || 'menu_order';
		root.dataset.bpssDefaultOrderby = defaultValue;
		return defaultValue;
	}

	function applyOrderbyToSelect(
		root: HTMLElement,
		orderby?: string
	): void {
		const select = getOrderingSelect( root );
		if ( ! select ) {
			return;
		}

		const value = orderby || getDefaultCatalogOrderby( root );
		if ( [ ...select.options ].some( ( option ) => option.value === value ) ) {
			select.value = value;
		}
	}

	function readOrderbyFromSelect( root: HTMLElement ): string | undefined {
		const select = getOrderingSelect( root );
		if ( ! select?.value ) {
			return undefined;
		}

		const defaultOrderby = getDefaultCatalogOrderby( root );
		if ( select.value === defaultOrderby ) {
			return undefined;
		}

		return select.value;
	}

	function setInputValue(
		input: HTMLInputElement | HTMLSelectElement,
		value: string,
		checked?: boolean
	): void {
		if ( input.type === 'checkbox' ) {
			input.checked = checked !== undefined ? checked : input.value === value;
			return;
		}

		if ( input.type === 'radio' ) {
			input.checked = input.value === value;
			return;
		}

		input.value = value;
	}

	function applyUrlStateToForm(
		form: HTMLFormElement,
		state: Partial< FilterParams >,
		root?: HTMLElement
	): void {
		if ( state.s ) {
			const keyword = form.querySelector(
				'[data-bpss-filter="keyword"]'
			) as HTMLInputElement | null;
			if ( keyword ) {
				keyword.value = state.s;
			}
		}

		if ( state.product_cat ) {
			const cats = Array.isArray( state.product_cat )
				? state.product_cat
				: [ state.product_cat ];
			form.querySelectorAll( '[data-bpss-filter="category"]' ).forEach(
				( el ) => {
					const input = el as HTMLInputElement;
					if ( input.type === 'checkbox' ) {
						input.checked = cats.includes( input.value );
					} else if ( input.type === 'radio' ) {
						input.checked = cats.includes( input.value );
					} else {
						input.value = cats[ 0 ] || '';
					}
				}
			);
		}

		if ( state.product_tag ) {
			const tags = Array.isArray( state.product_tag )
				? state.product_tag
				: [ state.product_tag ];
			form.querySelectorAll( '[data-bpss-filter="tag"]' ).forEach( ( el ) => {
				const input = el as HTMLInputElement;
				if ( input.type === 'checkbox' ) {
					input.checked = tags.includes( input.value );
				} else if ( input.type === 'radio' ) {
					input.checked = tags.includes( input.value );
				} else {
					input.value = tags[ 0 ] || '';
				}
			} );
		}

		if ( state.stock_status ) {
			const stock = form.querySelector(
				'[data-bpss-filter="stock"]'
			) as HTMLInputElement | HTMLSelectElement | null;
			if ( stock ) {
				setInputValue( stock, state.stock_status );
			}
		}

		if ( state.on_sale ) {
			const onSale = form.querySelector(
				'[data-bpss-filter="on_sale"]'
			) as HTMLInputElement | null;
			if ( onSale ) {
				onSale.checked = true;
			}
		}

		if ( state.featured ) {
			const featured = form.querySelector(
				'[data-bpss-filter="featured"]'
			) as HTMLInputElement | null;
			if ( featured ) {
				featured.checked = true;
			}
		}

		if ( state.min_rating ) {
			form.querySelectorAll( '[data-bpss-filter="rating"]' ).forEach(
				( el ) => {
					const input = el as HTMLInputElement | HTMLSelectElement;
					if ( input.type === 'radio' ) {
						input.checked =
							input.value === String( state.min_rating );
					} else {
						input.value = String( state.min_rating );
					}
				}
			);
		}

		if ( state.taxonomy ) {
			Object.keys( state.taxonomy ).forEach( ( taxonomy ) => {
				const selected = state.taxonomy?.[ taxonomy ];
				const terms = Array.isArray( selected )
					? selected
					: selected
					? [ selected ]
					: [];
				form.querySelectorAll(
					'[data-bpss-filter="brand"], [data-bpss-filter="custom_tax"]'
				).forEach( ( el ) => {
					const input = el as HTMLInputElement | HTMLSelectElement;
					if ( input.dataset.taxonomySlug !== taxonomy ) {
						return;
					}
					if ( input.type === 'checkbox' ) {
						input.checked = terms.includes( input.value );
					} else if ( input.type === 'radio' ) {
						input.checked = terms.includes( input.value );
					} else {
						input.value = terms[ 0 ] || '';
					}
				} );
			} );
		}

		if (
			state.min_price !== undefined ||
			state.max_price !== undefined
		) {
			form.querySelectorAll( '[data-bpss-price]' ).forEach( ( wrap ) => {
				const priceWrap = wrap as HTMLElement;
				if ( priceWrap.dataset.priceDisplay === 'segments' ) {
					const min = state.min_price || 0;
					const max = state.max_price || 0;
					form.querySelectorAll(
						'[data-bpss-filter="price_segment"]'
					).forEach( ( el ) => {
						const input = el as HTMLInputElement;
						const segMin = parseFloat( input.dataset.priceMin || '0' );
						const segMax = parseFloat( input.dataset.priceMax || '0' );
						input.checked =
							Math.abs( segMin - min ) < 0.001 &&
							( max <= 0 || Math.abs( segMax - max ) < 0.001 );
					} );
					return;
				}

				const minInput = priceWrap.querySelector(
					'[data-bpss-price-input="min"]'
				) as HTMLInputElement | null;
				const maxInput = priceWrap.querySelector(
					'[data-bpss-price-input="max"]'
				) as HTMLInputElement | null;

				if ( minInput && state.min_price !== undefined ) {
					minInput.value = String( state.min_price );
				}
				if ( maxInput && state.max_price !== undefined ) {
					maxInput.value = String( state.max_price );
				}
				syncPriceInputs( priceWrap );
			} );
		}

		if ( state.attribute ) {
			Object.keys( state.attribute ).forEach( ( slug ) => {
				const val = state.attribute?.[ slug ];
				const terms = Array.isArray( val ) ? val : [ val ];
				form.querySelectorAll(
					'[data-bpss-filter="attribute"][data-attribute-slug="' +
						slug +
						'"]'
				).forEach( ( el ) => {
					const input = el as HTMLInputElement;
					if ( input.type === 'checkbox' ) {
						input.checked = terms.includes( input.value );
					} else if ( input.type === 'radio' ) {
						input.checked = terms.includes( input.value );
					} else {
						input.value = terms[ 0 ] || '';
					}
				} );
			} );
		}

		if ( root ) {
			applyOrderbyToSelect( root, state.orderby );
		}
	}

	function getData(): BpssData {
		const win = window as Window & { bpssData?: BpssData };
		return (
			win.bpssData || {
				restUrl: '/wp-json/beplus-smart-search/v1/',
				nonce: '',
				i18n: {
					searching: 'Searching…',
					noResults: 'No products found.',
					resultsFound: '%d products found',
					error: 'Search failed. Please try again.',
					cleared: 'Filters cleared.',
				},
			}
		);
	}

	function debounce( fn: (...args: unknown[]) => void, delayMs: number ) {
		let timer: ReturnType< typeof setTimeout >;
		return function ( this: unknown, ...args: unknown[] ) {
			clearTimeout( timer );
			timer = setTimeout( () => fn.apply( this, args ), delayMs );
		};
	}

	function readConfig( root: HTMLElement ): BlockConfig {
		return {
			resultsMode: root.dataset.resultsMode || 'filter-collection',
			resultsSelector:
				root.dataset.resultsSelector ||
				'.wp-block-woocommerce-product-collection',
			debounceMs: parseInt( root.dataset.debounceMs || '280', 10 ),
			minChars: parseInt( root.dataset.minChars || '2', 10 ),
			perPage: parseInt( root.dataset.perPage || '10', 10 ),
			liveSearch: root.dataset.liveSearch !== '0',
			showResultCount: root.dataset.showResultCount !== '0',
			facetMode: root.dataset.facetMode || 'all',
		};
	}

	function readPriceRange( priceWrap: HTMLElement ) {
		const boundMin = parseFloat( priceWrap.dataset.priceMin || '0' );
		const boundMax = parseFloat( priceWrap.dataset.priceMax || '0' );
		const minInput = priceWrap.querySelector(
			'[data-bpss-price-input="min"]'
		) as HTMLInputElement | null;
		const maxInput = priceWrap.querySelector(
			'[data-bpss-price-input="max"]'
		) as HTMLInputElement | null;

		let minVal = parseFloat( minInput?.value || String( boundMin ) );
		let maxVal = parseFloat( maxInput?.value || String( boundMax ) );

		if ( Number.isNaN( minVal ) ) {
			minVal = boundMin;
		}
		if ( Number.isNaN( maxVal ) ) {
			maxVal = boundMax;
		}
		const maxFloor = Math.max( 1, boundMin );
		if ( maxVal < maxFloor ) {
			maxVal = maxFloor;
		}
		if ( minVal > maxVal ) {
			const temp = minVal;
			minVal = maxVal;
			maxVal = temp;
		}

		return {
			minVal,
			maxVal,
			boundMin,
			boundMax,
			active: minVal > boundMin || maxVal < boundMax,
		};
	}

	function applyPriceToParams(
		priceWrap: HTMLElement,
		params: FilterParams
	): void {
		const { minVal, maxVal, boundMin, boundMax, active } =
			readPriceRange( priceWrap );

		if ( ! active ) {
			return;
		}

		if ( minVal > boundMin ) {
			params.min_price = minVal;
		}
		if ( maxVal < boundMax && maxVal >= 1 ) {
			params.max_price = maxVal;
		}
	}

	function collectFilters(
		form: HTMLFormElement,
		page = 1
	): FilterParams {
		const root = form.closest( SELECTOR ) as HTMLElement | null;
		const params: FilterParams = {
			page,
			per_page: parseInt( root?.dataset.perPage || '10', 10 ),
			attribute: {},
		};

		form.querySelectorAll( '[data-bpss-price]' ).forEach( ( wrap ) => {
			applyPriceToParams( wrap as HTMLElement, params );
		} );

		form.querySelectorAll( '[data-bpss-filter]' ).forEach( ( el ) => {
			const input = el as HTMLInputElement | HTMLSelectElement;
			const type = input.dataset.bpssFilter || '';

			if ( type === 'min_price' || type === 'max_price' ) {
				return;
			}

			if ( input.type === 'radio' && ! input.checked ) {
				return;
			}

			if ( input.type === 'checkbox' && type !== 'on_sale' && type !== 'featured' ) {
				if ( ! input.checked ) {
					return;
				}
			}

			let value =
				input.type === 'checkbox' &&
				( type === 'on_sale' || type === 'featured' )
					? input.checked
						? '1'
						: ''
					: input.value;

			if (
				! value &&
				type !== 'price_segment' &&
				type !== 'keyword'
			) {
				return;
			}

			if ( type === 'keyword' ) {
				if ( value ) {
					params.s = value;
				}
			} else if ( type === 'category' ) {
				if ( ! params.product_cat ) {
					params.product_cat = [];
				}
				if ( input.dataset.bpssMulti === '1' ) {
					( params.product_cat as string[] ).push( value );
				} else {
					params.product_cat = value;
				}
			} else if ( type === 'tag' ) {
				if ( ! params.product_tag ) {
					params.product_tag = [];
				}
				if ( input.dataset.bpssMulti === '1' ) {
					( params.product_tag as string[] ).push( value );
				} else {
					params.product_tag = value;
				}
			} else if ( type === 'stock' ) {
				params.stock_status = value;
			} else if ( type === 'on_sale' ) {
				params.on_sale = true;
			} else if ( type === 'featured' ) {
				params.featured = true;
			} else if ( type === 'rating' ) {
				const rating = parseInt( value, 10 );
				if ( rating > 0 ) {
					params.min_rating = rating;
				}
			} else if ( type === 'price_segment' ) {
				if ( ! input.checked || ! value ) {
					return;
				}
				const min = parseFloat( input.dataset.priceMin || '0' );
				const max = parseFloat( input.dataset.priceMax || '0' );
				if ( ! Number.isNaN( min ) && min > 0 ) {
					params.min_price = min;
				}
				if ( ! Number.isNaN( max ) && max > 0 ) {
					params.max_price = max;
				}
			} else if ( type === 'attribute' ) {
				const slug = input.dataset.attributeSlug;
				if ( ! slug ) {
					return;
				}
				if ( ! params.attribute[ slug ] ) {
					params.attribute[ slug ] =
						input.dataset.bpssMulti === '1' ? [] : '';
				}
				if ( input.dataset.bpssMulti === '1' ) {
					( params.attribute[ slug ] as string[] ).push( value );
				} else {
					params.attribute[ slug ] = value;
				}
			} else if ( type === 'brand' || type === 'custom_tax' ) {
				const taxSlug = input.dataset.taxonomySlug || '';
				if ( ! taxSlug ) {
					return;
				}
				if ( ! params.taxonomy ) {
					params.taxonomy = {};
				}
				if ( ! params.taxonomy[ taxSlug ] ) {
					params.taxonomy[ taxSlug ] =
						input.dataset.bpssMulti === '1' ? [] : '';
				}
				if ( input.dataset.bpssMulti === '1' ) {
					( params.taxonomy[ taxSlug ] as string[] ).push( value );
				} else {
					params.taxonomy[ taxSlug ] = value;
				}
			}
		} );

		if ( root ) {
			const orderby = readOrderbyFromSelect( root );
			if ( orderby ) {
				params.orderby = orderby;
			}
		}

		return params;
	}

	function hasActiveFilters( filters: FilterParams ): boolean {
		if ( filters.s ) {
			return true;
		}
		if (
			filters.product_cat &&
			( Array.isArray( filters.product_cat )
				? filters.product_cat.length
				: filters.product_cat )
		) {
			return true;
		}
		if (
			filters.product_tag &&
			( Array.isArray( filters.product_tag )
				? filters.product_tag.length
				: filters.product_tag )
		) {
			return true;
		}
		if ( filters.stock_status ) {
			return true;
		}
		if ( filters.on_sale ) {
			return true;
		}
		if ( filters.featured ) {
			return true;
		}
		if ( filters.min_rating !== undefined && filters.min_rating > 0 ) {
			return true;
		}
		if ( filters.min_price !== undefined || filters.max_price !== undefined ) {
			const hasMin =
				filters.min_price !== undefined && filters.min_price > 0;
			const hasMax =
				filters.max_price !== undefined && filters.max_price > 0;
			if ( hasMin || hasMax ) {
				return true;
			}
		}
		if ( filters.attribute ) {
			return Object.keys( filters.attribute ).some( ( slug ) => {
				const val = filters.attribute[ slug ];
				return Array.isArray( val ) ? val.length > 0 : !! val;
			} );
		}
		if ( filters.taxonomy ) {
			return Object.keys( filters.taxonomy ).some( ( taxonomy ) => {
				const val = filters.taxonomy?.[ taxonomy ];
				return Array.isArray( val ) ? val.length > 0 : !! val;
			} );
		}
		return false;
	}

	function appendParam(
		searchParams: URLSearchParams,
		key: string,
		value: unknown
	): void {
		if ( Array.isArray( value ) ) {
			value.forEach( ( item ) => {
				searchParams.append( key + '[]', String( item ) );
			} );
			return;
		}

		if ( value !== undefined && value !== null && value !== '' ) {
			searchParams.set( key, String( value ) );
		}
	}

	function buildRestUrl(
		filters: FilterParams,
		endpoint: string,
		extra?: Record< string, string >
	): string {
		const data = getData();
		const url = new URL( data.restUrl + endpoint, window.location.origin );

		if ( extra ) {
			Object.keys( extra ).forEach( ( key ) => {
				url.searchParams.set( key, String( extra[ key ] ) );
			} );
		}

		Object.keys( filters ).forEach( ( key ) => {
			if ( key === 'attribute' ) {
				Object.keys( filters.attribute ).forEach( ( slug ) => {
					const val = filters.attribute[ slug ];
					if ( Array.isArray( val ) ) {
						val.forEach( ( term ) => {
							url.searchParams.append(
								'attribute[' + slug + '][]',
								term
							);
						} );
					} else {
						url.searchParams.append(
							'attribute[' + slug + ']',
							val as string
						);
					}
				} );
				return;
			}

			if ( key === 'taxonomy' ) {
				Object.keys( filters.taxonomy || {} ).forEach( ( taxonomy ) => {
					const val = filters.taxonomy?.[ taxonomy ];
					appendParam( url.searchParams, taxonomy, val );
				} );
				return;
			}

			appendParam(
				url.searchParams,
				key,
				filters[ key as keyof FilterParams ]
			);
		} );

		return url.toString();
	}

	function buildUrl( filters: FilterParams ): string {
		return buildRestUrl( filters, 'products' );
	}

	function buildFacetsUrl(
		filters: FilterParams,
		mode: string = 'all'
	): string {
		return buildRestUrl( filters, 'facets', { mode } );
	}

	function resetFacetVisibility( root: HTMLElement ): void {
		root.querySelectorAll( '[data-bpss-term-slug]' ).forEach( ( el ) => {
			const item = el as HTMLElement;
			item.hidden = false;
			item.classList.remove( 'beplus-smart-search__list-item--hidden' );
			if ( item.tagName === 'OPTION' ) {
				( item as HTMLOptionElement ).disabled = false;
			}
		} );

		root.querySelectorAll( '[data-bpss-facet-panel]' ).forEach( ( panel ) => {
			( panel as HTMLElement ).hidden = false;
		} );
	}

	function panelHasActiveSelection( panel: HTMLElement ): boolean {
		let active = false;

		panel.querySelectorAll( '[data-bpss-filter]' ).forEach( ( input ) => {
			const el = input as HTMLInputElement | HTMLSelectElement;

			if (
				( el.type === 'checkbox' || el.type === 'radio' ) &&
				el.checked &&
				el.value
			) {
				active = true;
			}

			if ( el.tagName === 'SELECT' && el.value ) {
				active = true;
			}
		} );

		return active;
	}

	function applyFacets(
		root: HTMLElement,
		facets: {
			categories?: Array< { slug: string; count: number } >;
			tags?: Array< { slug: string; count: number } >;
			attributes?: Array< {
				slug: string;
				terms: Array< { slug: string; count: number } >;
			} >;
			ratings?: Array< { slug: string; count: number } >;
			taxonomies?: Record<
				string,
				Array< { slug: string; count: number } >
			>;
		},
		facetMode: string = 'contextual'
	): void {
		const available: Record< string, Record< string, number > > = {
			category: {},
			tag: {},
			rating: {},
		};
		const attrMap: Record< string, Record< string, number > > = {};
		const taxonomyMap: Record< string, Record< string, number > > = {};

		( facets.categories || [] ).forEach( ( term ) => {
			available.category[ term.slug ] = term.count;
		} );
		( facets.tags || [] ).forEach( ( term ) => {
			available.tag[ term.slug ] = term.count;
		} );
		( facets.ratings || [] ).forEach( ( term ) => {
			available.rating[ term.slug ] = term.count;
		} );
		( facets.attributes || [] ).forEach( ( attr ) => {
			attrMap[ attr.slug ] = {};
			( attr.terms || [] ).forEach( ( term ) => {
				attrMap[ attr.slug ][ term.slug ] = term.count;
			} );
		} );
		Object.entries( facets.taxonomies || {} ).forEach(
			( [ taxonomy, terms ] ) => {
				taxonomyMap[ taxonomy ] = {};
				( terms || [] ).forEach( ( term ) => {
					taxonomyMap[ taxonomy ][ term.slug ] = term.count;
				} );
			}
		);

		root.querySelectorAll( '[data-bpss-term-slug]' ).forEach( ( el ) => {
			const item = el as HTMLElement;
			const slug = item.dataset.bpssTermSlug || '';
			const groupEl = item.closest(
				'[data-bpss-facet-group]'
			) as HTMLElement | null;
			const panelEl = item.closest(
				'[data-bpss-facet-panel]'
			) as HTMLElement | null;
			const attrEl = item.closest(
				'[data-bpss-attr-slug]'
			) as HTMLElement | null;
			const group =
				item.dataset.bpssFacetGroup ||
				groupEl?.dataset.bpssFacetGroup ||
				'';
			const attrSlug =
				item.dataset.bpssAttrSlug ||
				attrEl?.dataset.bpssAttrSlug ||
				'';
			const taxonomySlug =
				item.dataset.taxonomySlug ||
				panelEl?.dataset.bpssTaxonomy ||
				panelEl?.dataset.bpssAttrSlug ||
				attrEl?.dataset.bpssAttrSlug ||
				'';
			const input =
				( item.querySelector(
					'[data-bpss-filter]'
				) as HTMLInputElement | null ) ||
				( item.tagName === 'OPTION' ? ( item as HTMLInputElement ) : null );

			let isSelected = false;
			if ( input ) {
				if ( input.type === 'checkbox' || input.type === 'radio' ) {
					isSelected = input.checked && !! input.value;
				} else if ( input.tagName === 'OPTION' ) {
					isSelected =
						( input as HTMLOptionElement ).selected &&
						!! input.value;
				}
			}

			let count = 0;
			if ( group === 'category' ) {
				count = available.category[ slug ] || 0;
			} else if ( group === 'tag' ) {
				count = available.tag[ slug ] || 0;
			} else if ( group === 'rating' ) {
				count = available.rating[ slug ] || 0;
			} else if ( group === 'attribute' && attrSlug && attrMap[ attrSlug ] ) {
				count = attrMap[ attrSlug ][ slug ] || 0;
			} else if (
				( group === 'brand' || group === 'custom_tax' ) &&
				taxonomySlug &&
				taxonomyMap[ taxonomySlug ]
			) {
				count = taxonomyMap[ taxonomySlug ][ slug ] || 0;
			}

			// Rating is a single-select radio group — keep every option visible.
			const visible =
				facetMode === 'all' || group === 'rating'
					? true
					: count > 0 || isSelected;

			if ( item.tagName === 'OPTION' ) {
				( item as HTMLOptionElement ).hidden = ! visible;
				( item as HTMLOptionElement ).disabled = ! visible;
			} else {
				item.hidden = ! visible;
				item.classList.toggle(
					'beplus-smart-search__list-item--hidden',
					! visible
				);
			}

			const countEl = item.querySelector(
				'.beplus-smart-search__list-count'
			);
			if ( countEl && count > 0 ) {
				countEl.textContent = '(' + count + ')';
			}
		} );

		root.querySelectorAll( '[data-bpss-facet-panel]' ).forEach( ( panel ) => {
			const el = panel as HTMLElement;
			const group = el.dataset.bpssFacetPanel || '';
			const attrSlug = el.dataset.bpssAttrSlug || '';
			const taxonomy = el.dataset.bpssTaxonomy || attrSlug || '';
			const hasActiveSelection = panelHasActiveSelection( el );

			if (
				facetMode === 'contextual' &&
				[ 'price', 'stock', 'featured', 'on_sale' ].includes( group )
			) {
				el.hidden = ! hasActiveSelection;
				return;
			}

			let hasVisible = hasActiveSelection || facetMode === 'all';

			if ( group === 'category' ) {
				hasVisible =
					hasVisible || Object.keys( available.category ).length > 0;
			} else if ( group === 'tag' ) {
				hasVisible =
					hasVisible || Object.keys( available.tag ).length > 0;
			} else if ( group === 'attribute' && attrSlug ) {
				hasVisible =
					hasVisible ||
					!! (
						attrMap[ attrSlug ] &&
						Object.keys( attrMap[ attrSlug ] ).length > 0
					);
			} else if ( group === 'rating' ) {
				hasVisible =
					hasVisible || Object.keys( available.rating ).length > 0;
			} else if (
				( group === 'brand' || group === 'custom_tax' ) &&
				taxonomy
			) {
				hasVisible =
					hasVisible ||
					!! (
						taxonomyMap[ taxonomy ] &&
						Object.keys( taxonomyMap[ taxonomy ] ).length > 0
					);
			} else {
				hasVisible = true;
			}

			el.hidden = ! hasVisible;
		} );
	}

	function renderProductCard( item: Record< string, string | number > ): string {
		if ( item.html ) {
			return String( item.html );
		}

		const title = String( item.title || '' );
		const url = String( item.url || '#' );
		const image = String( item.image || '' );
		const priceHtml = String( item.price_html || '' );

		return (
			'<li class="wc-block-product wp-block-post post-' +
			item.id +
			' product type-product">' +
			'<a href="' +
			url +
			'" class="wc-block-components-product-image">' +
			( image
				? '<img src="' +
				  image +
				  '" alt="' +
				  title +
				  '" loading="lazy" />'
				: '' ) +
			'</a>' +
			'<h2 class="wp-block-post-title">' +
			'<a href="' +
			url +
			'">' +
			title +
			'</a>' +
			'</h2>' +
			'<div class="wp-block-woocommerce-product-price">' +
			priceHtml +
			'</div>' +
			'<div class="wp-block-button wc-block-components-product-button">' +
			'<a href="' +
			url +
			'" class="wp-block-button__link wp-element-button">' +
			'View product' +
			'</a>' +
			'</div>' +
			'</li>'
		);
	}

	const WC_PRODUCTS_STORE_LOCK =
		'I acknowledge that using a private store means my plugin will inevitably break in the next store release.';
	const WP_INTERACTIVITY_PRIVATE_LOCK =
		'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WordPress.';

	const importModule = new Function(
		'specifier',
		'return import(specifier);'
	) as ( specifier: string ) => Promise< unknown >;

	function deepMergeState(
		target: Record< string, unknown >,
		source: Record< string, unknown >
	): void {
		Object.keys( source ).forEach( ( key ) => {
			const sourceValue = source[ key ];
			const targetValue = target[ key ];

			if (
				sourceValue &&
				typeof sourceValue === 'object' &&
				! Array.isArray( sourceValue ) &&
				targetValue &&
				typeof targetValue === 'object' &&
				! Array.isArray( targetValue )
			) {
				deepMergeState(
					targetValue as Record< string, unknown >,
					sourceValue as Record< string, unknown >
				);
				return;
			}

			target[ key ] = sourceValue;
		} );
	}

	async function mergeInteractivityState(
		interactivity?: Record< string, unknown >
	): Promise< void > {
		const state = interactivity?.state;
		if ( ! state || typeof state !== 'object' ) {
			return;
		}

		try {
			const interactivityModule = ( await importModule(
				'@wordpress/interactivity'
			) ) as {
				store: (
					namespace: string,
					initial: Record< string, unknown >,
					options?: { lock?: string }
				) => { state: Record< string, unknown > };
			};

			Object.entries( state as Record< string, Record< string, unknown > > ).forEach(
				( [ namespace, namespaceState ] ) => {
					if ( ! namespaceState || typeof namespaceState !== 'object' ) {
						return;
					}

					const options =
						namespace === 'woocommerce/products'
							? { lock: WC_PRODUCTS_STORE_LOCK }
							: undefined;
					const storeInstance = interactivityModule.store(
						namespace,
						{},
						options
					);
					deepMergeState( storeInstance.state, namespaceState );
				}
			);
		} catch {
			// Interactivity modules may be unavailable on non-block templates.
		}
	}

	async function hydrateProductIslands(
		list: HTMLElement
	): Promise< boolean > {
		const islands = list.querySelectorAll(
			':scope > li[data-wp-interactive]'
		);
		if ( ! islands.length ) {
			return false;
		}

		try {
			const interactivityModule = ( await importModule(
				'@wordpress/interactivity'
			) ) as {
				privateApis: ( lock: string ) => {
					initialVdomPromise: Promise< unknown >;
					toVdom: ( node: Element ) => unknown;
					getRegionRootFragment: ( node: Element ) => Node;
					render: ( vdom: unknown, fragment: Node ) => void;
				};
			};

			const apis = interactivityModule.privateApis(
				WP_INTERACTIVITY_PRIVATE_LOCK
			);
			await apis.initialVdomPromise;

			for ( const node of islands ) {
				const fragment = apis.getRegionRootFragment( node );
				const vdom = apis.toVdom( node );
				apis.render( vdom, fragment );
				await new Promise( ( resolve ) => {
					setTimeout( resolve, 0 );
				} );
			}

			return true;
		} catch {
			return false;
		}
	}

	async function refreshAjaxProductResults(
		list: HTMLElement,
		interactivity?: Record< string, unknown >
	): Promise< void > {
		await mergeInteractivityState( interactivity );
		await hydrateProductIslands( list );
	}

	function findProductList(
		root: HTMLElement,
		selector: string
	): HTMLElement | null {
		let collection: Element | null = root.parentElement
			? root.parentElement.querySelector( selector )
			: null;

		if ( ! collection ) {
			const main = root.closest( 'main' );
			if ( main ) {
				collection = main.querySelector( selector );
			}
		}

		if ( ! collection ) {
			collection = document.querySelector( selector );
		}

		if ( ! collection ) {
			return null;
		}

		return collection.querySelector(
			'ul.wc-block-product-template, ul.products, ul.wp-block-post-template'
		) as HTMLElement | null;
	}

	function updateResultCount( root: HTMLElement, total: number ): void {
		const main = root.closest( 'main' ) || document;
		const countEl = main.querySelector(
			'.wp-block-woocommerce-product-results-count, .woocommerce-result-count'
		);

		if ( ! countEl ) {
			return;
		}

		const text = total === 1 ? '1 result' : total + ' results';
		countEl.textContent = 'Showing ' + text;
	}

	function updateResults(
		root: HTMLElement,
		config: BlockConfig,
		data: {
			items?: Record< string, unknown >[];
			total?: number;
			totalPages?: number;
			page?: number;
			interactivity?: Record< string, unknown >;
		}
	): void {
		const items = ( data.items || [] ) as Record< string, string | number >[];
		const html = items.map( renderProductCard ).join( '' );

		if ( config.resultsMode === 'own-grid' ) {
			const ownGrid = root.querySelector(
				'[data-bpss-results]'
			) as HTMLElement | null;
			if ( ownGrid ) {
				ownGrid.hidden = false;
				ownGrid.innerHTML =
					'<ul class="beplus-smart-search__grid">' + html + '</ul>';
			}
			return;
		}

		const list = findProductList( root, config.resultsSelector );

		if ( ! list ) {
			return;
		}

		if ( items.length === 0 ) {
			list.innerHTML =
				'<li class="beplus-smart-search__empty">' +
				getData().i18n.noResults +
				'</li>';
		} else {
			list.innerHTML = html;
			void refreshAjaxProductResults( list, data.interactivity );
		}

		if ( config.showResultCount ) {
			updateResultCount( root, data.total || items.length );
		}
	}

	function findPagination(
		root: HTMLElement,
		config: BlockConfig
	): HTMLElement | null {
		const main = root.closest( 'main' ) || document;
		let collection: Element | null = null;

		if ( root.parentElement ) {
			collection = root.parentElement.querySelector(
				config.resultsSelector
			);
		}

		if ( ! collection && main ) {
			collection = main.querySelector( config.resultsSelector );
		}

		if ( ! collection ) {
			collection = document.querySelector( config.resultsSelector );
		}

		if ( collection ) {
			const nav = collection.querySelector(
				'.wp-block-query-pagination, nav[aria-label="Pagination"]'
			);
			if ( nav ) {
				return nav as HTMLElement;
			}
		}

		return main.querySelector(
			'.wp-block-query-pagination, nav[aria-label="Pagination"]'
		) as HTMLElement | null;
	}

	function setPaginationVisible( nav: HTMLElement, visible: boolean ): void {
		if ( visible ) {
			nav.hidden = false;
			nav.style.removeProperty( 'display' );
			nav.removeAttribute( 'data-bpss-pagination-hidden' );
		} else {
			nav.hidden = true;
			nav.style.display = 'none';
			nav.setAttribute( 'data-bpss-pagination-hidden', '1' );
		}
	}

	function updatePagination(
		root: HTMLElement,
		config: BlockConfig,
		page: number,
		totalPages: number
	): void {
		const nav = findPagination( root, config );
		if ( ! nav ) {
			return;
		}

		if ( totalPages <= 1 ) {
			setPaginationVisible( nav, false );
			return;
		}

		setPaginationVisible( nav, true );
		nav.setAttribute( 'data-bpss-pagination', '1' );

		let html = '';

		if ( page > 1 ) {
			html +=
				'<a href="#" class="wp-block-query-pagination-previous" data-bpss-page="' +
				( page - 1 ) +
				'">Previous Page</a>';
		}

		html += '<div class="wp-block-query-pagination-numbers">';
		for ( let i = 1; i <= totalPages; i++ ) {
			if ( i === page ) {
				html +=
					'<span aria-label="Page ' +
					i +
					'" aria-current="page" class="page-numbers current">' +
					i +
					'</span>';
			} else {
				html +=
					'<a href="#" aria-label="Page ' +
					i +
					'" class="page-numbers" data-bpss-page="' +
					i +
					'">' +
					i +
					'</a>';
			}
		}
		html += '</div>';

		if ( page < totalPages ) {
			html +=
				'<a href="#" class="wp-block-query-pagination-next" data-bpss-page="' +
				( page + 1 ) +
				'">Next Page</a>';
		}

		nav.innerHTML = html;
	}

	function scrollResultsIntoView( root: HTMLElement ): void {
		const main = root.closest( 'main' ) || root;
		const target =
			main.querySelector(
				'.wp-block-woocommerce-product-results-count, .woocommerce-result-count'
			) || findProductList( root, readConfig( root ).resultsSelector );

		if ( target ) {
			target.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		}
	}

	function parseResultTotal( root: HTMLElement ): number {
		const main = root.closest( 'main' ) || document;
		const countEl = main.querySelector(
			'.wp-block-woocommerce-product-results-count, .woocommerce-result-count'
		);

		if ( ! countEl ) {
			return 0;
		}

		const text = countEl.textContent || '';
		const match = text.match( /of\s+([\d,.]+)/i );

		if ( ! match ) {
			return 0;
		}

		return parseInt( match[ 1 ].replace( /[,.]/g, '' ), 10 ) || 0;
	}

	function syncInitialPagination(
		root: HTMLElement,
		config: BlockConfig,
		page: number
	): void {
		root.dataset.bpssAjaxActive = '1';

		const perPage = parseInt( root.dataset.perPage || '10', 10 );
		const total = parseResultTotal( root );

		if ( total <= 0 ) {
			return;
		}

		const totalPages = Math.max( 1, Math.ceil( total / perPage ) );
		updatePagination( root, config, page, totalPages );
	}

	function setStatus(
		form: HTMLFormElement,
		message: string,
		visible: boolean
	): void {
		const status = form.querySelector(
			'[data-bpss-status]'
		) as HTMLElement | null;
		if ( ! status ) {
			return;
		}
		status.textContent = message;
		status.hidden = ! visible;
	}

	function setLoading( form: HTMLFormElement, loading: boolean ): void {
		form.classList.toggle( 'beplus-smart-search--loading', loading );
	}

	function inputHasFilterValue( el: HTMLInputElement | HTMLSelectElement ): boolean {
		const type = el.dataset.bpssFilter || '';

		if ( type === 'price_segment' ) {
			return el.type === 'radio' && el.checked && !! el.value;
		}

		if ( type === 'min_price' || type === 'max_price' ) {
			const priceWrap = el.closest(
				'[data-bpss-price]'
			) as HTMLElement | null;
			if ( priceWrap ) {
				return readPriceRange( priceWrap ).active;
			}
			return false;
		}

		if ( el.type === 'checkbox' ) {
			return el.checked;
		}

		if ( el.type === 'radio' ) {
			return el.checked && !! el.value;
		}

		return !! el.value;
	}

	function toggleClearButton( form: HTMLFormElement ): void {
		const clearBtn = form.querySelector(
			'[data-bpss-clear]'
		) as HTMLButtonElement | null;
		if ( ! clearBtn ) {
			return;
		}

		let hasValue = false;

		form.querySelectorAll( '[data-bpss-filter]' ).forEach( ( el ) => {
			if ( inputHasFilterValue( el as HTMLInputElement ) ) {
				hasValue = true;
			}
		} );

		clearBtn.hidden = ! hasValue;
	}

	function syncPriceInputs( priceWrap: HTMLElement ): void {
		const minRange = priceWrap.querySelector(
			'[data-bpss-range="min"]'
		) as HTMLInputElement | null;
		const maxRange = priceWrap.querySelector(
			'[data-bpss-range="max"]'
		) as HTMLInputElement | null;
		const minInput = priceWrap.querySelector(
			'[data-bpss-price-input="min"]'
		) as HTMLInputElement | null;
		const maxInput = priceWrap.querySelector(
			'[data-bpss-price-input="max"]'
		) as HTMLInputElement | null;

		if ( ! minInput || ! maxInput ) {
			return;
		}

		let minVal = parseFloat( minInput.value );
		let maxVal = parseFloat( maxInput.value );
		const boundMin = parseFloat( priceWrap.dataset.priceMin || '0' );
		const boundMax = parseFloat( priceWrap.dataset.priceMax || '0' );

		if ( Number.isNaN( minVal ) ) {
			minVal = boundMin;
		}
		if ( Number.isNaN( maxVal ) ) {
			maxVal = boundMax;
		}

		if ( minVal < boundMin ) {
			minVal = boundMin;
		}
		if ( maxVal > boundMax ) {
			maxVal = boundMax;
		}
		const maxFloor = Math.max( 1, boundMin );
		if ( maxVal < maxFloor ) {
			maxVal = maxFloor;
		}
		if ( minVal > maxVal ) {
			minVal = maxVal;
		}

		minInput.value = String( minVal );
		maxInput.value = String( maxVal );

		if ( minRange ) {
			minRange.value = String( minVal );
		}
		if ( maxRange ) {
			maxRange.value = String( maxVal );
		}

		updatePriceTrack( priceWrap );
	}

	function updatePriceTrack( priceWrap: HTMLElement ): void {
		const track = priceWrap.querySelector(
			'[data-bpss-price-track]'
		) as HTMLElement | null;
		const minInput = priceWrap.querySelector(
			'[data-bpss-price-input="min"]'
		) as HTMLInputElement | null;
		const maxInput = priceWrap.querySelector(
			'[data-bpss-price-input="max"]'
		) as HTMLInputElement | null;

		if ( ! track || ! minInput || ! maxInput ) {
			return;
		}

		const boundMin = parseFloat( priceWrap.dataset.priceMin || '0' );
		const boundMax = parseFloat( priceWrap.dataset.priceMax || '0' );
		const range = boundMax - boundMin || 1;
		let minVal = parseFloat( minInput.value );
		let maxVal = parseFloat( maxInput.value );

		if ( minVal > maxVal ) {
			const temp = minVal;
			minVal = maxVal;
			maxVal = temp;
		}

		const left = ( ( minVal - boundMin ) / range ) * 100;
		const right = ( ( boundMax - maxVal ) / range ) * 100;
		track.style.setProperty( '--bpss-range-left', left + '%' );
		track.style.setProperty( '--bpss-range-right', right + '%' );
	}

	function initPanelToggles( root: HTMLElement ): void {
		root.querySelectorAll( '[data-bpss-panel-toggle]' ).forEach( ( btn ) => {
			btn.addEventListener( 'click', () => {
				const panel = btn.closest( '[data-bpss-panel]' );
				const body = panel?.querySelector(
					'[data-bpss-panel-body]'
				) as HTMLElement | null;
				if ( ! body ) {
					return;
				}
				const expanded =
					btn.getAttribute( 'aria-expanded' ) === 'true';
				btn.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
				body.hidden = expanded;
			} );
		} );
	}

	function toggleTermChildren( btn: HTMLElement ): void {
		const item = btn.closest(
			'.beplus-smart-search__list-item--parent'
		) as HTMLElement | null;
		const children = item?.querySelector(
			':scope > .beplus-smart-search__list--children'
		) as HTMLElement | null;
		if ( ! item || ! children ) {
			return;
		}

		const expanded = btn.getAttribute( 'aria-expanded' ) === 'true';
		btn.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
		children.hidden = expanded;
		item.classList.toggle( 'is-expanded', ! expanded );
	}

	function initTermToggles( root: HTMLElement ): void {
		root.addEventListener( 'click', ( event ) => {
			const target = event.target as HTMLElement | null;
			const btn = target?.closest(
				'[data-bpss-term-toggle]'
			) as HTMLElement | null;
			if ( ! btn || ! root.contains( btn ) ) {
				return;
			}

			event.preventDefault();
			event.stopPropagation();
			toggleTermChildren( btn );
		} );
	}

	function initPriceRange(
		form: HTMLFormElement,
		onChange: () => void
	): void {
		form.querySelectorAll( '[data-bpss-price]' ).forEach( ( wrap ) => {
			const priceWrap = wrap as HTMLElement;
			if ( priceWrap.dataset.priceDisplay === 'segments' ) {
				return;
			}

			const minRange = priceWrap.querySelector(
				'[data-bpss-range="min"]'
			) as HTMLInputElement | null;
			const maxRange = priceWrap.querySelector(
				'[data-bpss-range="max"]'
			) as HTMLInputElement | null;
			const minInput = priceWrap.querySelector(
				'[data-bpss-price-input="min"]'
			) as HTMLInputElement | null;
			const maxInput = priceWrap.querySelector(
				'[data-bpss-price-input="max"]'
			) as HTMLInputElement | null;

			function syncFromRange( source: 'min' | 'max' ): void {
				if ( minRange && minInput ) {
					minInput.value = minRange.value;
				}
				if ( maxRange && maxInput ) {
					maxInput.value = maxRange.value;
				}

				let minVal = parseFloat( minInput?.value || '0' );
				let maxVal = parseFloat( maxInput?.value || '0' );
				const maxFloor = Math.max(
					1,
					parseFloat( priceWrap.dataset.priceMin || '0' )
				);

				if ( maxVal < maxFloor ) {
					maxVal = maxFloor;
				}

				if ( minVal > maxVal ) {
					if ( source === 'min' ) {
						maxVal = minVal;
						if ( maxRange ) {
							maxRange.value = String( maxVal );
						}
						if ( maxInput ) {
							maxInput.value = String( maxVal );
						}
					} else {
						minVal = maxVal;
						if ( minRange ) {
							minRange.value = String( minVal );
						}
						if ( minInput ) {
							minInput.value = String( minVal );
						}
					}
				}

				updatePriceTrack( priceWrap );
				onChange();
			}

			function syncFromInput(): void {
				if ( minRange && minInput ) {
					minRange.value = minInput.value;
				}
				if ( maxRange && maxInput ) {
					maxRange.value = maxInput.value;
				}
				syncPriceInputs( priceWrap );
				onChange();
			}

			if ( minRange ) {
				minRange.addEventListener( 'input', () => syncFromRange( 'min' ) );
			}
			if ( maxRange ) {
				maxRange.addEventListener( 'input', () => syncFromRange( 'max' ) );
			}
			if ( minInput ) {
				minInput.addEventListener( 'input', syncFromInput );
				minInput.addEventListener( 'change', syncFromInput );
			}
			if ( maxInput ) {
				maxInput.addEventListener( 'input', syncFromInput );
				maxInput.addEventListener( 'change', syncFromInput );
			}

			syncPriceInputs( priceWrap );
		} );
	}

	function initCatalogOrdering(
		root: HTMLElement,
		runSearch: ( resetPage?: boolean ) => void
	): void {
		if ( root.dataset.bpssOrderingInited ) {
			return;
		}

		const orderingForm = document.querySelector(
			'.woocommerce-ordering'
		) as HTMLFormElement | null;
		const select = getOrderingSelect( root );

		if ( ! orderingForm || ! select ) {
			return;
		}

		root.dataset.bpssOrderingInited = '1';

		type JQueryLike = {
			( target: Element ): { off: ( event: string, selector?: string ) => void };
			( fn: () => void ): void;
		};

		const jqWindow = window as Window & { jQuery?: JQueryLike };
		let orderingSearchQueued = false;

		const queueOrderSearch = (): void => {
			if ( orderingSearchQueued ) {
				return;
			}
			orderingSearchQueued = true;
			runSearch( true );
			window.setTimeout( () => {
				orderingSearchQueued = false;
			}, 150 );
		};

		const neutralizeWcHandlers = (): void => {
			const jq = jqWindow.jQuery;
			if ( ! jq ) {
				return;
			}
			jq( orderingForm ).off( 'change', 'select.orderby' );
			jq( select ).off( 'change' );
		};

		orderingForm.setAttribute( 'novalidate', 'novalidate' );
		orderingForm.action = window.location.pathname;

		orderingForm.addEventListener(
			'submit',
			( event ) => {
				event.preventDefault();
				event.stopImmediatePropagation();
				queueOrderSearch();
			},
			true
		);

		select.addEventListener(
			'change',
			( event ) => {
				event.preventDefault();
				event.stopImmediatePropagation();
				queueOrderSearch();
			},
			true
		);

		const formWithSubmit = orderingForm as HTMLFormElement & {
			submit: () => void;
		};
		formWithSubmit.submit = (): void => {
			queueOrderSearch();
		};

		neutralizeWcHandlers();

		if ( jqWindow.jQuery ) {
			jqWindow.jQuery( neutralizeWcHandlers );
			jqWindow.jQuery( () => {
				window.setTimeout( neutralizeWcHandlers, 0 );
			} );
		}

		window.addEventListener( 'load', neutralizeWcHandlers, { once: true } );
	}

	function initBlock( root: HTMLElement ): void {
		if ( root.dataset.bpssSearchInited ) {
			return;
		}
		root.dataset.bpssSearchInited = '1';

		const isProductCatArchive = document.body.classList.contains( 'tax-product_cat' );

		const form = root.querySelector(
			'[data-bpss-search-form]'
		) as HTMLFormElement | null;
		if ( ! form ) {
			return;
		}

		// On product category archives, category selection should always navigate to the term URL
		// (even if liveSearch is enabled). We do this at click-level to avoid any race with AJAX handlers.
		if ( isProductCatArchive ) {
			root.addEventListener(
				'click',
				( event ) => {
					const target = event.target as HTMLElement | null;
					if ( ! target ) {
						return;
					}

					const panel = target.closest(
						'[data-bpss-facet-panel="category"]'
					) as HTMLElement | null;
					if ( ! panel ) {
						return;
					}

					const input = target.closest(
						'input[data-bpss-filter="category"]'
					) as HTMLInputElement | null;
					if ( ! input ) {
						return;
					}

					const url =
						input.dataset.bpssTermUrl ||
						( input.closest( 'li' ) as HTMLElement | null )?.dataset
							.bpssTermUrl;
					if ( ! url ) {
						return;
					}

					event.preventDefault();
					event.stopPropagation();
					event.stopImmediatePropagation();
					window.location.href = url;
				},
				true
			);
		}

		const config = readConfig( root );
		let currentPage = parseUrlPage();
		let ajaxActive = hasUrlSearchState();
		let abortController: AbortController | null = null;
		let facetsAbortController: AbortController | null = null;
		const data = getData();

		if ( hasUrlSearchState() ) {
			applyUrlStateToForm( form, parseUrlFilters(), root );
			toggleClearButton( form );
		}

		const refreshFacets = (): void => {
			const filters = collectFilters( form, currentPage );

			if ( config.facetMode !== 'contextual' ) {
				resetFacetVisibility( root );
				return;
			}

			if ( ! hasActiveFilters( filters ) ) {
				resetFacetVisibility( root );
				return;
			}

			if ( facetsAbortController ) {
				facetsAbortController.abort();
			}
			facetsAbortController = new AbortController();

			fetch( buildFacetsUrl( filters, 'contextual' ), {
				method: 'GET',
				headers: { 'X-WP-Nonce': data.nonce },
				signal: facetsAbortController.signal,
			} )
				.then( ( response ) => {
					if ( ! response.ok ) {
						throw new Error( 'HTTP ' + response.status );
					}
					return response.json();
				} )
				.then( ( facets ) =>
					applyFacets( root, facets, config.facetMode )
				)
				.catch( ( err: Error ) => {
					if ( err?.name === 'AbortError' ) {
						return;
					}
				} );
		};

		const runSearch = ( resetPage = false ): void => {
			if ( resetPage ) {
				currentPage = 1;
			}

			const filters = collectFilters( form, currentPage );
			const keyword = filters.s || '';

			if ( keyword && keyword.length < config.minChars ) {
				return;
			}

			ajaxActive = true;
			root.dataset.bpssAjaxActive = '1';

			if ( abortController ) {
				abortController.abort();
			}
			abortController = new AbortController();

			setLoading( form, true );
			setStatus( form, data.i18n.searching, true );

			fetch( buildUrl( filters ), {
				method: 'GET',
				headers: { 'X-WP-Nonce': data.nonce },
				signal: abortController.signal,
			} )
				.then( ( response ) => {
					if ( ! response.ok ) {
						throw new Error( 'HTTP ' + response.status );
					}
					return response.json();
				} )
				.then( ( result ) => {
					currentPage = result.page || currentPage;
					updateResults( root, config, result );
					updatePagination(
						root,
						config,
						currentPage,
						result.totalPages || 0
					);
					syncUrlFromFilters( filters );
					refreshFacets();
					const total = result.total || 0;
					const msg = total
						? ( data.i18n.resultsFound || '%d products found' ).replace(
								'%d',
								String( total )
						  )
						: data.i18n.noResults;
					setStatus( form, msg, true );
					toggleClearButton( form );
				} )
				.catch( ( err: Error ) => {
					if ( err?.name === 'AbortError' ) {
						return;
					}
					setStatus( form, data.i18n.error, true );
				} )
				.finally( () => setLoading( form, false ) );
		};

		const goToPage = ( page: number ): void => {
			currentPage = Math.max( 1, page );
			runSearch( false );
			scrollResultsIntoView( root );
		};

		const debouncedSearch = debounce( () => runSearch( true ), config.debounceMs );
		const debouncedPrice = debounce( () => runSearch( true ), 400 );

		const main = root.closest( 'main' ) || document;
		main.addEventListener(
			'click',
			( event ) => {
				const target = event.target as HTMLElement;
				const pagination = findPagination( root, config );
				const link = target.closest(
					'[data-bpss-page], .wp-block-query-pagination a'
				) as HTMLAnchorElement | null;

				if ( ! link || ! pagination?.contains( link ) ) {
					return;
				}

				event.preventDefault();
				event.stopPropagation();
				event.stopImmediatePropagation();

				const page = parseInt(
					link.dataset.bpssPage ||
						link.getAttribute( 'href' )?.match( /page\/(\d+)/ )?.[ 1 ] ||
						'1',
					10
				);

				if ( page > 0 && page !== currentPage ) {
					goToPage( page );
				}
			},
			true
		);

		window.addEventListener( 'popstate', () => {
			currentPage = parseUrlPage();
			applyUrlStateToForm( form, parseUrlFilters(), root );
			toggleClearButton( form );
			form.querySelectorAll( '[data-bpss-price]' ).forEach( ( wrap ) => {
				syncPriceInputs( wrap as HTMLElement );
			} );

			if ( hasUrlSearchState() ) {
				runSearch( false );
			}
		} );

		initPanelToggles( root );
		initTermToggles( root );

		initPriceRange( form, () => {
			toggleClearButton( form );
			if ( config.liveSearch ) {
				debouncedPrice();
			}
		} );

		form.addEventListener( 'submit', ( event ) => {
			event.preventDefault();
			runSearch( true );
		} );

		form.querySelectorAll( '[data-bpss-filter]' ).forEach( ( el ) => {
			const input = el as HTMLInputElement;
			const type = input.dataset.bpssFilter || '';
			const handler = (): void => {
				// On product category archives, category selection should navigate to the term URL (no AJAX).
				if ( isProductCatArchive && type === 'category' ) {
					if ( input.type === 'radio' && ! input.checked ) {
						return;
					}
					if ( input.type === 'checkbox' && ! input.checked ) {
						return;
					}
					const url =
						input.dataset.bpssTermUrl ||
						( input.closest( 'li' ) as HTMLElement | null )?.dataset
							.bpssTermUrl;
					if ( url ) {
						window.location.href = url;
						return;
					}
				}

				toggleClearButton( form );
				if ( ! config.liveSearch ) {
					return;
				}
				if ( type === 'keyword' ) {
					debouncedSearch();
				} else {
					runSearch( true );
				}
			};

			if ( type === 'keyword' ) {
				input.addEventListener( 'input', handler );
			} else {
				input.addEventListener( 'change', handler );
			}
		} );

		const clearBtn = form.querySelector(
			'[data-bpss-clear]'
		) as HTMLButtonElement | null;
		if ( clearBtn ) {
			clearBtn.addEventListener( 'click', () => {
				currentPage = 1;
				form.querySelectorAll( '[data-bpss-filter]' ).forEach( ( el ) => {
					const input = el as HTMLInputElement;
					if ( input.type === 'checkbox' ) {
						input.checked = false;
					} else if ( input.type === 'radio' ) {
						return;
					} else if ( input.dataset.bpssPriceInput ) {
						const priceWrap = input.closest(
							'[data-bpss-price]'
						) as HTMLElement | null;
						if ( priceWrap ) {
							if ( input.dataset.bpssPriceInput === 'min' ) {
								input.value = priceWrap.dataset.priceMin || '0';
							} else {
								input.value = priceWrap.dataset.priceMax || '0';
							}
						}
					} else {
						input.value = '';
					}
				} );

				const radioNames: Record< string, boolean > = {};
				form.querySelectorAll(
					'[data-bpss-filter][type="radio"]'
				).forEach( ( el ) => {
					const input = el as HTMLInputElement;
					radioNames[ input.name ] = true;
				} );
				Object.keys( radioNames ).forEach( ( name ) => {
					const fallback = form.querySelector(
						'[name="' + name + '"][value=""]'
					) as HTMLInputElement | null;
					if ( fallback ) {
						fallback.checked = true;
					}
				} );

				form.querySelectorAll( '[data-bpss-price]' ).forEach( ( wrap ) => {
					syncPriceInputs( wrap as HTMLElement );
				} );

				applyOrderbyToSelect( root );

				ajaxActive = false;
				root.dataset.bpssAjaxActive = '0';
				const clearedUrl = new URL( window.location.href );
				clearUrlFilterParams( clearedUrl );
				window.history.replaceState( {}, '', clearedUrl.toString() );

				toggleClearButton( form );
				setStatus( form, data.i18n.cleared, true );
				resetFacetVisibility( root );
				runSearch( true );
			} );
		}

		initCatalogOrdering( root, runSearch );

		if ( hasUrlSearchState() ) {
			root.dataset.bpssAjaxActive = '1';
			runSearch( false );
		} else {
			syncInitialPagination( root, config, currentPage );
		}
	}

	function init(): void {
		document.querySelectorAll( SELECTOR ).forEach( ( root ) => {
			initBlock( root as HTMLElement );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
