/**
 * Smart Search admin settings tabs.
 *
 * @package BePlusSmartSearch
 */
// @ts-nocheck
( function ( $ ) {
	'use strict';

	function togglePriceSettings( $wrap ) {
		const mode = $wrap
			.find( 'input[name*="[sidebar][price][display]"]:checked' )
			.val();
		$wrap.find( '[data-bpss-price-settings="range"]' ).prop(
			'hidden',
			mode !== 'range'
		);
		$wrap.find( '[data-bpss-price-settings="segments"]' ).prop(
			'hidden',
			mode !== 'segments'
		);
	}

	function reindexSegmentRows( $wrap ) {
		const optionKey = $wrap
			.find( 'input[name*="[sidebar][price][segments]"]' )
			.first()
			.attr( 'name' )
			?.match( /^[^\[]+/)?.[ 0 ];

		if ( ! optionKey ) {
			return;
		}

		$wrap.find( '#bpss-price-segments tbody .bpss-settings__segment-row' ).each(
			function ( index ) {
				$( this )
					.find( 'input[data-name="label"], input[name*="[label]"]' )
					.attr(
						'name',
						optionKey +
							'[sidebar][price][segments][' +
							index +
							'][label]'
					);
				$( this )
					.find( 'input[data-name="min"], input[name*="[min]"]' )
					.attr(
						'name',
						optionKey +
							'[sidebar][price][segments][' +
							index +
							'][min]'
					);
				$( this )
					.find( 'input[data-name="max"], input[name*="[max]"]' )
					.attr(
						'name',
						optionKey +
							'[sidebar][price][segments][' +
							index +
							'][max]'
					);
			}
		);
	}

	function reindexCustomTaxRows( $wrap ) {
		const optionKey = $wrap
			.find( 'select[name*="[sidebar][facets][custom_taxonomies]"]' )
			.first()
			.attr( 'name' )
			?.match( /^[^\[]+/)?.[ 0 ];

		if ( ! optionKey ) {
			return;
		}

		$wrap
			.find( '#bpss-custom-taxonomies tbody .bpss-settings__custom-tax-row' )
			.each( function ( index ) {
				$( this )
					.find(
						'select[data-name="taxonomy"], select[name*="[taxonomy]"]'
					)
					.attr(
						'name',
						optionKey +
							'[sidebar][facets][custom_taxonomies][' +
							index +
							'][taxonomy]'
					);
				$( this )
					.find( 'input[data-name="label"], input[name*="[label]"]' )
					.attr(
						'name',
						optionKey +
							'[sidebar][facets][custom_taxonomies][' +
							index +
							'][label]'
					);
				$( this )
					.find( 'select[data-name="mode"], select[name*="[mode]"]' )
					.attr(
						'name',
						optionKey +
							'[sidebar][facets][custom_taxonomies][' +
							index +
							'][mode]'
					);
				$( this )
					.find(
						'input[data-name="show_sub"], input[name*="[show_sub]"]'
					)
					.attr(
						'name',
						optionKey +
							'[sidebar][facets][custom_taxonomies][' +
							index +
							'][show_sub]'
					);
			} );
	}

	$( function () {
		const $wrap = $( '.bpss-settings' );
		if ( ! $wrap.length ) {
			return;
		}

		$wrap.on( 'click', '.bpss-settings__tab', function ( event ) {
			event.preventDefault();
			const tab = $( this ).data( 'tab' );
			if ( ! tab ) {
				return;
			}

			$wrap.find( '.bpss-settings__tab' ).removeClass( 'is-active' );
			$( this ).addClass( 'is-active' );
			$wrap.find( '.bpss-settings__panel' ).removeClass( 'is-active' );
			$wrap
				.find( '.bpss-settings__panel[data-tab-panel="' + tab + '"]' )
				.addClass( 'is-active' );
			$wrap.find( 'input[name="bpss_active_tab"]' ).val( tab );
		} );

		$wrap.on(
			'change',
			'input[name*="[sidebar][price][display]"]',
			function () {
				togglePriceSettings( $wrap );
			}
		);

		$wrap.on( 'click', '.bpss-add-segment', function () {
			const template = document.getElementById(
				'bpss-segment-row-template'
			);
			const tbody = $wrap.find( '#bpss-price-segments tbody' )[ 0 ];

			if ( ! template || ! tbody ) {
				return;
			}

			const clone = template.content.cloneNode( true );
			tbody.appendChild( clone );
			reindexSegmentRows( $wrap );
		} );

		$wrap.on( 'click', '.bpss-remove-segment', function () {
			$( this ).closest( '.bpss-settings__segment-row' ).remove();
			reindexSegmentRows( $wrap );
		} );

		$wrap.on( 'click', '.bpss-add-custom-tax', function () {
			const template = document.getElementById(
				'bpss-custom-tax-row-template'
			);
			const tbody = $wrap.find( '#bpss-custom-taxonomies tbody' )[ 0 ];

			if ( ! template || ! tbody ) {
				return;
			}

			const clone = template.content.cloneNode( true );
			tbody.appendChild( clone );
			reindexCustomTaxRows( $wrap );
		} );

		$wrap.on( 'click', '.bpss-remove-custom-tax', function () {
			$( this ).closest( '.bpss-settings__custom-tax-row' ).remove();
			reindexCustomTaxRows( $wrap );
		} );

		togglePriceSettings( $wrap );

		if ( $.fn.wpColorPicker ) {
			$( '.bpss-color-picker' ).wpColorPicker();
		}
	} );
} )( jQuery );
