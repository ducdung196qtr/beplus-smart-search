/**
 * Live Search — frontend view script.
 *
 * @package BePlusSmartSearch
 */

( function () {
	'use strict';

	const SELECTOR = '[data-bpss-live-search]';

	interface BpssData {
		restUrl: string;
		nonce: string;
		shopUrl: string;
		wcAjaxUrl?: string;
		i18n: Record< string, string >;
	}

	interface BlockConfig {
		debounceMs: number;
		minChars: number;
		maxResults: number;
		enableSuggestions: boolean;
		misspellingFix: boolean;
		exactMatch: boolean;
		searchLogic: string;
		showAddToCart: boolean;
		showViewAll: boolean;
		shopUrl: string;
		searchScope: string;
		limitCategories: string[];
		searchFields: string[];
	}

	interface MatchMeta {
		type: string;
		label: string;
		value: string;
	}

	interface ProductItem {
		id: number;
		title: string;
		url: string;
		image: string;
		price_html: string;
		on_sale: boolean;
		product_type?: string;
		ajax_add_to_cart?: boolean;
		match_meta?: MatchMeta[];
	}

	interface CartAjaxResponse {
		error?: boolean;
		product_url?: string;
		fragments?: Record< string, string >;
		cart_hash?: string;
	}

	interface SuggestionsResponse {
		suggestions: string[];
		corrected: string | null;
	}

	interface ProductsResponse {
		items: ProductItem[];
		total: number;
	}

	function getBpssData(): BpssData {
		return ( window as Window & { bpssData?: BpssData } ).bpssData || {
			restUrl: '/wp-json/beplus-smart-search/v1/',
			nonce: '',
			shopUrl: '/',
			i18n: {},
		};
	}

	function parseConfig( root: HTMLElement ): BlockConfig {
		const limitRaw = root.dataset.limitCategories || '';
		const fieldsRaw = root.dataset.searchFields || 'title';

		return {
			debounceMs: parseInt( root.dataset.debounceMs || '280', 10 ),
			minChars: parseInt( root.dataset.minChars || '2', 10 ),
			maxResults: parseInt( root.dataset.maxResults || '6', 10 ),
			enableSuggestions: root.dataset.enableSuggestions !== '0',
			misspellingFix: root.dataset.misspellingFix !== '0',
			exactMatch: root.dataset.exactMatch === '1',
			searchLogic: root.dataset.searchLogic || 'or',
			showAddToCart: root.dataset.showAddToCart !== '0',
			showViewAll: root.dataset.showViewAll !== '0',
			shopUrl: root.dataset.shopUrl || getBpssData().shopUrl,
			searchScope: root.dataset.searchScope || 'all',
			limitCategories: limitRaw
				.split( ',' )
				.map( ( item ) => item.trim() )
				.filter( Boolean ),
			searchFields: fieldsRaw
				.split( ',' )
				.map( ( item ) => item.trim() )
				.filter( Boolean ),
		};
	}

	function escapeHtml( text: string ): string {
		const div = document.createElement( 'div' );
		div.textContent = text;
		return div.innerHTML;
	}

	function highlightKeywords( text: string, keyword: string ): string {
		const trimmed = keyword.trim();
		if ( ! trimmed ) {
			return escapeHtml( text );
		}

		const words = trimmed.split( /\s+/ ).filter( Boolean );
		let result = escapeHtml( text );

		words.forEach( ( word ) => {
			const pattern = new RegExp(
				`(${word.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' )})`,
				'gi',
			);
			result = result.replace(
				pattern,
				'<mark class="beplus-smart-search__highlight">$1</mark>',
			);
		} );

		return result;
	}

	function getWcAjaxUrl( endpoint: string ): string {
		const data = getBpssData();
		if ( data.wcAjaxUrl ) {
			return data.wcAjaxUrl.replace( '%%endpoint%%', endpoint );
		}

		return `${ window.location.origin }/?wc-ajax=${ endpoint }`;
	}

	function applyCartFragments( fragments?: Record< string, string > ): void {
		if ( ! fragments ) {
			return;
		}

		Object.entries( fragments ).forEach( ( [ selector, html ] ) => {
			const target = document.querySelector( selector );
			if ( ! target || ! target.parentNode ) {
				return;
			}

			const template = document.createElement( 'div' );
			template.innerHTML = html;
			const replacement = template.firstElementChild;
			if ( replacement ) {
				target.parentNode.replaceChild( replacement, target );
			}
		} );
	}

	function triggerAddedToCart(
		fragments: Record< string, string > | undefined,
		cartHash: string | undefined,
	): void {
		document.body.dispatchEvent(
			new CustomEvent( 'added_to_cart', {
				detail: { fragments, cart_hash: cartHash },
			} ),
		);

		const jquery = ( window as Window & {
			jQuery?: ( target: unknown ) => { trigger: ( name: string, args?: unknown[] ) => void };
		} ).jQuery;
		if ( jquery ) {
			// Omit the button so WooCommerce does not inject a "View cart" link beside it.
			jquery( document.body ).trigger( 'added_to_cart', [ fragments, cartHash ] );
		}
	}

	function markButtonAdded( button: HTMLButtonElement ): void {
		const i18n = getBpssData().i18n;

		if ( ! button.dataset.bpssLabel ) {
			button.dataset.bpssLabel = button.textContent || '';
		}

		button.textContent = i18n.added || 'Added';
		button.classList.add( 'is-added' );
		button.setAttribute( 'aria-label', i18n.addedToCart || 'Added to cart' );

		window.setTimeout( () => {
			button.textContent =
				button.dataset.bpssLabel || i18n.addToCart || 'Add to cart';
			button.classList.remove( 'is-added' );
			button.removeAttribute( 'aria-label' );
		}, 2200 );
	}

	function debounce< T extends ( ...args: never[] ) => void >(
		fn: T,
		ms: number,
	): { schedule: T; cancel: () => void } {
		let timer = 0;
		const schedule = ( ( ...args: Parameters< T > ) => {
			window.clearTimeout( timer );
			timer = window.setTimeout( () => fn( ...args ), ms );
		} ) as T;

		return {
			schedule,
			cancel: () => {
				window.clearTimeout( timer );
			},
		};
	}

	function getCompletionSuffix( keyword: string, suggestion: string ): string {
		if ( ! keyword || ! suggestion ) {
			return '';
		}

		const kwLower = keyword.toLowerCase();
		const sugLower = suggestion.toLowerCase();
		const idx = sugLower.indexOf( kwLower );

		if ( idx !== -1 ) {
			return suggestion.slice( idx + keyword.length );
		}

		const words = suggestion.split( /\s+/ );
		for ( const word of words ) {
			if ( word.toLowerCase().startsWith( kwLower ) ) {
				const wordStart = suggestion.indexOf( word );
				return suggestion.slice( wordStart + keyword.length );
			}
		}

		return '';
	}

	function initBlock( root: HTMLElement ): void {
		if ( root.dataset.bpssLiveInited === '1' ) {
			return;
		}
		root.dataset.bpssLiveInited = '1';

		const form = root.querySelector< HTMLFormElement >( '[data-bpss-live-form]' );
		const input = root.querySelector< HTMLInputElement >( '[data-bpss-live-input]' );
		const dropdown = root.querySelector< HTMLElement >( '[data-bpss-live-dropdown]' );
		const productsEl = root.querySelector< HTMLElement >( '[data-bpss-live-products]' );
		const footerEl = root.querySelector< HTMLElement >( '[data-bpss-live-footer]' );
		const viewAllLink = root.querySelector< HTMLAnchorElement >( '[data-bpss-live-view-all]' );
		const statusEl = root.querySelector< HTMLElement >( '[data-bpss-live-status]' );
		const categorySelect = root.querySelector< HTMLSelectElement >( '[data-bpss-live-category]' );
		const ghostEl = root.querySelector< HTMLElement >( '[data-bpss-live-ghost]' );
		const ghostPrefix = root.querySelector< HTMLElement >( '[data-bpss-live-ghost-prefix]' );
		const ghostSuffix = root.querySelector< HTMLElement >( '[data-bpss-live-ghost-suffix]' );
		const inputStack = root.querySelector< HTMLElement >( '[data-bpss-live-input-stack]' );

		if (
			! form ||
			! input ||
			! dropdown ||
			! productsEl ||
			! statusEl ||
			! ghostEl ||
			! ghostPrefix ||
			! ghostSuffix
		) {
			return;
		}

		const formEl = form;
		const inputEl = input;
		const dropdownEl = dropdown;
		const productsBox = productsEl;
		const statusBox = statusEl;

		const ghostLayer = ghostEl;
		const ghostPrefixEl = ghostPrefix;
		const ghostSuffixEl = ghostSuffix;

		const config = parseConfig( root );
		let abortController: AbortController | null = null;
		let suggestAbort: AbortController | null = null;
		let activeIndex = -1;
		let optionElements: HTMLElement[] = [];
		let suggestions: string[] = [];
		let suggestionIndex = 0;

		function setStatus( message: string ): void {
			statusBox.textContent = message;
		}

		function openDropdown(): void {
			dropdownEl.hidden = false;
			inputEl.setAttribute( 'aria-expanded', 'true' );
		}

		function closeDropdown(): void {
			dropdownEl.hidden = true;
			inputEl.setAttribute( 'aria-expanded', 'false' );
			activeIndex = -1;
			optionElements.forEach( ( el ) => el.setAttribute( 'aria-selected', 'false' ) );
		}

		function getActiveCategories(): string[] {
			const selected = categorySelect?.value || '';
			if ( selected ) {
				return [ selected ];
			}
			if ( config.searchScope === 'limited' && config.limitCategories.length ) {
				return config.limitCategories;
			}
			return [];
		}

		function appendCategories( params: URLSearchParams, categories: string[] ): void {
			if ( ! categories.length ) {
				return;
			}
			params.set( 'product_cat', categories.join( ',' ) );
		}

		function buildSearchParams( keyword: string ): URLSearchParams {
			const params = new URLSearchParams();
			params.set( 's', keyword );
			params.set( 'per_page', String( config.maxResults ) );
			params.set( 'page', '1' );

			appendCategories( params, getActiveCategories() );

			if ( config.exactMatch ) {
				params.set( 'exact_match', '1' );
			}

			if ( config.searchLogic === 'and' ) {
				params.set( 'search_logic', 'and' );
			}

			if ( config.misspellingFix ) {
				params.set( 'misspelling_fix', '1' );
			}

			if ( config.searchFields.length ) {
				params.set( 'search_fields', config.searchFields.join( ',' ) );
			}

			return params;
		}

		function renderMatchMeta( meta: MatchMeta[], keyword: string ): string {
			if ( ! meta.length ) {
				return '';
			}

			return meta
				.map(
					( row ) =>
						`<span class="beplus-smart-search__live-product-meta beplus-smart-search__live-product-meta--${escapeHtml( row.type )}">
							<span class="beplus-smart-search__live-product-meta-label">${escapeHtml( row.label )}:</span>
							<span class="beplus-smart-search__live-product-meta-value">${highlightKeywords( row.value, keyword )}</span>
						</span>`,
				)
				.join( '' );
		}

		function buildViewAllUrl( keyword: string ): string {
			const url = new URL( config.shopUrl, window.location.origin );
			if ( keyword ) {
				url.searchParams.set( 's', keyword );
			}
			const cats = getActiveCategories();
			if ( cats.length ) {
				url.searchParams.set( 'product_cat', cats.join( ',' ) );
			}
			return url.toString();
		}

		function clearGhost(): void {
			ghostPrefixEl.textContent = '';
			ghostSuffixEl.textContent = '';
			ghostLayer.hidden = true;
			suggestions = [];
			suggestionIndex = 0;
		}

		function cancelPendingRequests(): void {
			searchDebouncer.cancel();
			suggestDebouncer.cancel();
			if ( abortController ) {
				abortController.abort();
				abortController = null;
			}
			if ( suggestAbort ) {
				suggestAbort.abort();
				suggestAbort = null;
			}
		}

		function resetSearchUi(): void {
			cancelPendingRequests();
			clearGhost();
			closeDropdown();
			productsBox.innerHTML = '';
			if ( footerEl ) {
				footerEl.hidden = true;
			}
			setStatus( '' );
		}

		function updateGhost( keyword: string ): void {
			if ( ! config.enableSuggestions || ! suggestions.length ) {
				clearGhost();
				return;
			}

			const suggestion = suggestions[ suggestionIndex ] || '';
			const suffix = getCompletionSuffix( keyword, suggestion );

			if ( ! suffix ) {
				clearGhost();
				return;
			}

			ghostPrefixEl.textContent = keyword;
			ghostSuffixEl.textContent = suffix;
			ghostLayer.hidden = false;

			if ( inputStack ) {
				inputStack.scrollLeft = inputEl.scrollLeft;
			}
		}

		function acceptSuggestion(): boolean {
			if ( ! suggestions.length ) {
				return false;
			}

			const suggestion = suggestions[ suggestionIndex ];
			if ( ! suggestion ) {
				return false;
			}

			inputEl.value = suggestion;
			cancelPendingRequests();
			clearGhost();
			void runSearch( suggestion );
			return true;
		}

		function cycleSuggestion( direction: 1 | -1 ): void {
			if ( ! suggestions.length ) {
				return;
			}

			suggestionIndex =
				( suggestionIndex + direction + suggestions.length ) %
				suggestions.length;
			updateGhost( inputEl.value );
		}

		async function fetchJson< T >( url: string, signal: AbortSignal ): Promise< T > {
			const data = getBpssData();
			const response = await fetch( url, {
				signal,
				headers: {
					'X-WP-Nonce': data.nonce,
				},
			} );

			if ( ! response.ok ) {
				throw new Error( 'Request failed' );
			}

			return response.json() as Promise< T >;
		}

		async function fetchSuggestions( keyword: string ): Promise< void > {
			if ( ! config.enableSuggestions || keyword.length < config.minChars ) {
				clearGhost();
				return;
			}

			if ( suggestAbort ) {
				suggestAbort.abort();
			}
			suggestAbort = new AbortController();

			try {
				const params = buildSearchParams( keyword );
				params.set( 'limit', '8' );
				const data = getBpssData();
				const result = await fetchJson< SuggestionsResponse >(
					`${data.restUrl}suggestions?${params.toString()}`,
					suggestAbort.signal,
				);

				if ( inputEl.value !== keyword ) {
					return;
				}

				suggestions = result.suggestions || [];
				suggestionIndex = 0;
				updateGhost( keyword );

				if ( result.corrected && config.misspellingFix ) {
					setStatus(
						`Did you mean "${result.corrected}"? Press Tab to accept.`,
					);
				}
			} catch ( err ) {
				if ( err instanceof DOMException && err.name === 'AbortError' ) {
					return;
				}
				clearGhost();
			}
		}

		function renderActionButton( item: ProductItem ): string {
			if ( ! config.showAddToCart ) {
				return '';
			}

			const i18n = getBpssData().i18n;

			if ( item.ajax_add_to_cart ) {
				return `<button type="button"
					class="beplus-smart-search__live-cart beplus-smart-search__live-cart--add"
					data-bpss-add-to-cart
					data-product_id="${item.id}"
					data-product_url="${escapeHtml( item.url )}"
					data-quantity="1"
				>${escapeHtml( i18n.addToCart || 'Add to cart' )}</button>`;
			}

			return `<a href="${escapeHtml( item.url )}" class="beplus-smart-search__live-cart beplus-smart-search__live-cart--view">${escapeHtml(
				i18n.viewProduct || 'View product',
			)}</a>`;
		}

		async function ajaxAddToCart(
			productId: number,
			button: HTMLButtonElement,
			productUrl: string,
		): Promise< void > {
			const i18n = getBpssData().i18n;
			button.disabled = true;
			button.classList.add( 'is-loading' );

			try {
				const body = new URLSearchParams();
				body.set( 'product_id', String( productId ) );
				body.set( 'quantity', '1' );

				const response = await fetch( getWcAjaxUrl( 'add_to_cart' ), {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
					},
					body: body.toString(),
					credentials: 'same-origin',
				} );

				if ( ! response.ok ) {
					throw new Error( 'Add to cart failed' );
				}

				const result = ( await response.json() ) as CartAjaxResponse;

				if ( result.error && result.product_url ) {
					window.location.href = result.product_url;
					return;
				}

				if ( result.error ) {
					window.location.href = productUrl;
					return;
				}

				applyCartFragments( result.fragments );
				triggerAddedToCart( result.fragments, result.cart_hash );
				markButtonAdded( button );
				setStatus( i18n.addedToCart || 'Added to cart' );
			} catch {
				setStatus( i18n.error || 'Search failed. Please try again.' );
			} finally {
				button.disabled = false;
				button.classList.remove( 'is-loading' );
			}
		}

		function renderProducts( items: ProductItem[], keyword: string ): void {
			if ( items.length === 0 ) {
				productsBox.innerHTML = `<p class="beplus-smart-search__live-empty">${escapeHtml(
					getBpssData().i18n.noResults || 'No products found.',
				)}</p>`;
				return;
			}

			productsBox.innerHTML = items
				.map( ( item, index ) => {
					const actionBtn = renderActionButton( item );
					const metaHtml = renderMatchMeta( item.match_meta || [], keyword );

					return `<div
						class="beplus-smart-search__live-product"
						role="option"
						data-bpss-option="product"
						data-index="${index}"
						aria-selected="false"
					>
						<a href="${escapeHtml( item.url )}" class="beplus-smart-search__live-product-link">
							<span class="beplus-smart-search__live-product-image">
								<img src="${escapeHtml( item.image )}" alt="" loading="lazy" width="60" height="60" />
							</span>
							<span class="beplus-smart-search__live-product-body">
								<span class="beplus-smart-search__live-product-title">${highlightKeywords( item.title, keyword )}</span>
								<span class="beplus-smart-search__live-product-price">${item.price_html}</span>
								${metaHtml ? `<span class="beplus-smart-search__live-product-meta-wrap">${metaHtml}</span>` : ''}
							</span>
						</a>
						${actionBtn ? `<span class="beplus-smart-search__live-product-action">${actionBtn}</span>` : ''}
					</div>`;
				} )
				.join( '' );
		}

		function collectOptions(): HTMLElement[] {
			return Array.from(
				dropdownEl.querySelectorAll< HTMLElement >( '[data-bpss-option="product"]' ),
			);
		}

		function setActiveOption( index: number ): void {
			optionElements = collectOptions();
			optionElements.forEach( ( el, i ) => {
				el.setAttribute( 'aria-selected', i === index ? 'true' : 'false' );
				if ( i === index ) {
					el.classList.add( 'is-active' );
					el.scrollIntoView( { block: 'nearest' } );
				} else {
					el.classList.remove( 'is-active' );
				}
			} );
			activeIndex = index;
		}

		async function runSearch( keyword: string ): Promise< void > {
			if ( abortController ) {
				abortController.abort();
			}
			abortController = new AbortController();
			const signal = abortController.signal;

			const params = buildSearchParams( keyword );
			const data = getBpssData();

			root.classList.add( 'beplus-smart-search--loading' );
			setStatus( data.i18n.searching || 'Searching…' );

			try {
				let searchKeyword = keyword;
				let products = await fetchJson< ProductsResponse >(
					`${data.restUrl}products?${params.toString()}`,
					signal,
				);

				if (
					config.misspellingFix &&
					products.items.length === 0 &&
					config.enableSuggestions
				) {
					const suggestParams = buildSearchParams( keyword );
					suggestParams.set( 'limit', '1' );
					const suggestResult = await fetchJson< SuggestionsResponse >(
						`${data.restUrl}suggestions?${suggestParams.toString()}`,
						signal,
					);

					if ( suggestResult.corrected ) {
						searchKeyword = suggestResult.corrected;
						const retryParams = buildSearchParams( searchKeyword );
						products = await fetchJson< ProductsResponse >(
							`${data.restUrl}products?${retryParams.toString()}`,
							signal,
						);
						setStatus( `Showing results for "${suggestResult.corrected}"` );
					}
				}

				renderProducts( products.items, searchKeyword );
				setStatus(
					products.total > 0
						? `${products.total} products found`
						: data.i18n.noResults || 'No products found.',
				);

				if ( footerEl && viewAllLink ) {
					if ( config.showViewAll && keyword.length >= config.minChars ) {
						footerEl.hidden = false;
						viewAllLink.href = buildViewAllUrl( searchKeyword );
					} else {
						footerEl.hidden = true;
					}
				}

				if ( inputEl.value.trim() !== keyword ) {
					return;
				}

				openDropdown();
				activeIndex = -1;
			} catch ( err ) {
				if ( err instanceof DOMException && err.name === 'AbortError' ) {
					return;
				}
				setStatus( data.i18n.error || 'Search failed. Please try again.' );
			} finally {
				root.classList.remove( 'beplus-smart-search--loading' );
			}
		}

		const searchDebouncer = debounce( ( keyword: string ) => {
			void runSearch( keyword );
		}, config.debounceMs );

		const suggestDebouncer = debounce( ( keyword: string ) => {
			void fetchSuggestions( keyword );
		}, Math.max( 120, config.debounceMs - 80 ) );

		inputEl.addEventListener( 'input', () => {
			const keyword = inputEl.value;

			if ( keyword.trim().length < config.minChars ) {
				resetSearchUi();
				return;
			}

			suggestDebouncer.schedule( keyword );
			searchDebouncer.schedule( keyword.trim() );
		} );

		inputEl.addEventListener( 'scroll', () => {
			if ( inputStack ) {
				inputStack.scrollLeft = inputEl.scrollLeft;
			}
		} );

		inputEl.addEventListener( 'focus', () => {
			const keyword = inputEl.value.trim();
			if ( keyword.length >= config.minChars && productsBox.innerHTML ) {
				openDropdown();
			}
			if ( keyword.length >= config.minChars && config.enableSuggestions ) {
				void fetchSuggestions( keyword );
			}
		} );

		inputEl.addEventListener( 'keydown', ( event ) => {
			if ( config.enableSuggestions && suggestions.length ) {
				if ( event.key === 'ArrowDown' ) {
					event.preventDefault();
					cycleSuggestion( 1 );
					return;
				}
				if ( event.key === 'ArrowUp' ) {
					event.preventDefault();
					cycleSuggestion( -1 );
					return;
				}
				if ( event.key === 'Tab' || event.key === 'ArrowRight' ) {
					if ( ghostSuffixEl.textContent ) {
						event.preventDefault();
						acceptSuggestion();
						return;
					}
				}
			}

			optionElements = collectOptions();

			if ( event.key === 'ArrowDown' && ! dropdownEl.hidden ) {
				event.preventDefault();
				setActiveOption(
					activeIndex < optionElements.length - 1 ? activeIndex + 1 : 0,
				);
			} else if ( event.key === 'ArrowUp' && ! dropdownEl.hidden ) {
				event.preventDefault();
				setActiveOption(
					activeIndex > 0 ? activeIndex - 1 : optionElements.length - 1,
				);
			} else if ( event.key === 'Enter' && activeIndex >= 0 ) {
				event.preventDefault();
				const active = optionElements[ activeIndex ];
				const link = active?.querySelector< HTMLAnchorElement >(
					'.beplus-smart-search__live-product-link',
				);
				if ( link ) {
					window.location.href = link.href;
				}
			} else if ( event.key === 'Escape' ) {
				resetSearchUi();
			}
		} );

		productsBox.addEventListener( 'click', ( event ) => {
			const button = ( event.target as HTMLElement ).closest< HTMLButtonElement >(
				'[data-bpss-add-to-cart]',
			);
			if ( ! button ) {
				return;
			}

			event.preventDefault();
			event.stopPropagation();

			const productId = parseInt( button.dataset.product_id || '0', 10 );
			const productUrl = button.dataset.product_url || '';
			if ( productId > 0 ) {
				void ajaxAddToCart( productId, button, productUrl );
			}
		} );

		if ( categorySelect ) {
			categorySelect.addEventListener( 'change', () => {
				const keyword = inputEl.value.trim();
				if ( keyword.length >= config.minChars ) {
					void fetchSuggestions( keyword );
					void runSearch( keyword );
				}
			} );
		}

		document.addEventListener( 'click', ( event ) => {
			if ( ! root.contains( event.target as Node ) ) {
				closeDropdown();
			}
		} );

		formEl.addEventListener( 'submit', ( event ) => {
			const keyword = inputEl.value.trim();
			if ( keyword.length < config.minChars ) {
				event.preventDefault();
			}
		} );
	}

	function init(): void {
		document.querySelectorAll< HTMLElement >( SELECTOR ).forEach( initBlock );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
