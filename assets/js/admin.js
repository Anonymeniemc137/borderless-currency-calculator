/**
 * Admin settings page behavior: sortable currency list + color pickers.
 *
 * @package Currency_Converter
 */

( function ( $ ) {
	'use strict';

	$( function () {
		$( '.bccalc-currency-sortable' ).sortable( {
			handle: '.bccalc-drag-handle',
			axis: 'y',
			placeholder: 'bccalc-currency-item ui-sortable-placeholder',
		} );

		if ( $.fn.wpColorPicker ) {
			$( '.bccalc-color-picker' ).wpColorPicker();
		}

		$( '#bccalc-select-all' ).on( 'click', function ( event ) {
			event.preventDefault();
			$( '.bccalc-currency-sortable input[type="checkbox"]' ).prop( 'checked', true );
		} );

		$( '#bccalc-select-none' ).on( 'click', function ( event ) {
			event.preventDefault();
			$( '.bccalc-currency-sortable input[type="checkbox"]' ).prop( 'checked', false );
		} );

		var $themeEnabled = $( '#bccalc-theme-enabled' );

		function resetAppearanceDefaults() {
			$( '.bccalc-color-picker' ).each( function () {
				$( this ).val( $( this ).attr( 'data-default-color' ) ).trigger( 'change' );
			} );
			$( '#bccalc-border-radius' ).val( $( '#bccalc-border-radius' ).attr( 'data-default' ) );
			$( '#bccalc-font-size' ).val( $( '#bccalc-font-size' ).attr( 'data-default' ) );
			$( '#bccalc-converter-width' ).val( $( '#bccalc-converter-width' ).attr( 'data-default' ) );
		}

		if ( $themeEnabled.length ) {
			if ( $themeEnabled.prop( 'checked' ) ) {
				resetAppearanceDefaults();
			}
			$themeEnabled.on( 'change', function () {
				if ( $( this ).prop( 'checked' ) ) {
					resetAppearanceDefaults();
				}
			} );
		}
	} );
} )( jQuery );
