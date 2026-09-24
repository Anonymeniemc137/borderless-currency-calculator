/**
 * Borderless Currency Calculator frontend behavior.
 *
 * Vanilla JS, no external dependencies. Talks to admin-ajax.php via the
 * localized `cconvConverter` object (ajaxUrl, nonce, i18n strings, flag emoji map).
 *
 * @package Borderless_Currency_Calculator
 */

( function () {
	'use strict';

	if ( typeof cconvConverter === 'undefined' ) {
		return;
	}

	/**
	 * The currently open dropdown, so opening one closes the other.
	 *
	 * @type {Object|null}
	 */
	var activeDropdown = null;

	/**
	 * Wire up a custom currency dropdown (button + listbox) that sits on
	 * top of a visually hidden <select> holding the real value.
	 *
	 * @param {HTMLElement} group     The .cconv-from-group / .cconv-to-group element.
	 * @param {Function}    onSelect  Called with the chosen code.
	 * @return {Object} API: refresh( code ), open(), close().
	 */
	function createDropdown( group, onSelect ) {
		if ( ! group ) {
			return null;
		}

		var toggle  = group.querySelector( '.cconv-dropdown-toggle' );
		var menu    = group.querySelector( '.cconv-dropdown-menu' );
		var select  = group.querySelector( '.cconv-select' );
		var flagEl  = group.querySelector( '.cconv-dropdown-toggle .cconv-dropdown-flag' );
		var codeEl  = group.querySelector( '.cconv-dropdown-code' );
		var options = Array.prototype.slice.call( group.querySelectorAll( '.cconv-dropdown-option' ) );

		var dd = {
			/**
			 * Whether this dropdown's menu is currently open.
			 *
			 * @return {Boolean}
			 */
			isOpen: function () {
				return !! menu && menu.classList.contains( 'cconv-open' );
			},

			/**
			 * Open this dropdown's menu (closing any other open one).
			 *
			 * @return {void}
			 */
			open: function () {
				if ( ! menu || dd.isOpen() ) {
					return;
				}
				if ( activeDropdown && activeDropdown !== dd ) {
					activeDropdown.close();
				}
				activeDropdown = dd;
				menu.classList.add( 'cconv-open' );
				menu.setAttribute( 'aria-hidden', 'false' );
				toggle.setAttribute( 'aria-expanded', 'true' );
			},

			/**
			 * Close this dropdown's menu.
			 *
			 * @return {void}
			 */
			close: function () {
				if ( ! menu ) {
					return;
				}
				if ( activeDropdown === dd ) {
					activeDropdown = null;
				}
				menu.classList.remove( 'cconv-open' );
				menu.setAttribute( 'aria-hidden', 'true' );
				toggle.setAttribute( 'aria-expanded', 'false' );
			},

			/**
			 * Sync the toggle (flag + code), the hidden select and the
			 * listbox highlight with a currency code.
			 *
			 * @param {String} code Lowercase currency code.
			 * @return {void}
			 */
			refresh: function ( code ) {
				var flag = cconvConverter.flags && cconvConverter.flags[ code ] ? cconvConverter.flags[ code ] : '';

				if ( select ) {
					select.value = code;
				}
				if ( codeEl ) {
					codeEl.textContent = code.toUpperCase();
				}
				if ( flagEl ) {
					flagEl.textContent = flag;
				}
				options.forEach( function ( option ) {
					var isSelected = option.getAttribute( 'data-code' ) === code;
					option.classList.toggle( 'cconv-selected', isSelected );
					option.setAttribute( 'aria-selected', isSelected ? 'true' : 'false' );
				} );
			}
		};

		if ( ! toggle || ! menu ) {
			return dd;
		}

		toggle.addEventListener(
			'click',
			function ( event ) {
				event.stopPropagation();
				if ( dd.isOpen() ) {
					dd.close();
				} else {
					dd.open();
				}
			}
		);

		toggle.addEventListener(
			'keydown',
			function ( event ) {
				if ( 'ArrowDown' === event.key || 'Enter' === event.key || ' ' === event.key ) {
					event.preventDefault();
					dd.open();
					if ( options.length ) {
						options[ 0 ].focus();
					}
				}
			}
		);

		options.forEach( function ( option, index ) {
			option.addEventListener(
				'click',
				function () {
					dd.refresh( option.getAttribute( 'data-code' ) );
					dd.close();
					toggle.focus();
					if ( onSelect ) {
						onSelect( option.getAttribute( 'data-code' ) );
					}
				}
			);

			option.addEventListener(
				'keydown',
				function ( event ) {
					var next = null;
					if ( 'ArrowDown' === event.key ) {
						event.preventDefault();
						next = options[ index + 1 ] || options[ 0 ];
						next.focus();
					} else if ( 'ArrowUp' === event.key ) {
						event.preventDefault();
						next = options[ index - 1 ] || options[ options.length - 1 ];
						next.focus();
					} else if ( 'Enter' === event.key || ' ' === event.key ) {
						event.preventDefault();
						dd.refresh( option.getAttribute( 'data-code' ) );
						dd.close();
						toggle.focus();
						if ( onSelect ) {
							onSelect( option.getAttribute( 'data-code' ) );
						}
					} else if ( 'Escape' === event.key ) {
						event.preventDefault();
						dd.close();
						toggle.focus();
					} else if ( 'Tab' === event.key ) {
						dd.close();
					}
				}
			);
		} );

		return dd;
	}

	function initConverter( root ) {
		var form      = root.querySelector( '.cconv-form' );
		var amountEl  = root.querySelector( '.cconv-input' );
		var fromEl    = root.querySelector( '.cconv-select-from' );
		var toEl      = root.querySelector( '.cconv-select-to' );
		var switchBtn = root.querySelector( '.cconv-switch-currencies' );
		var button    = root.querySelector( '.cconv-convert-btn' );
		var resultEl  = root.querySelector( '.cconv-result' );
		var rateEl    = root.querySelector( '.cconv-exchange-rate' );
		var timeEl    = root.querySelector( '.cconv-timestamp' );
		var errorEl   = root.querySelector( '.cconv-error' );

		if ( ! form || ! amountEl || ! fromEl || ! toEl || ! button ) {
			return;
		}

		function showError( message ) {
			if ( errorEl ) {
				errorEl.textContent = message;
				errorEl.classList.add( 'cconv-visible' );
			}
		}

		function clearError() {
			if ( errorEl ) {
				errorEl.textContent = '';
				errorEl.classList.remove( 'cconv-visible' );
			}
		}

		function setLoading( isLoading ) {
			button.disabled = isLoading;
			button.querySelector( 'span' ).textContent = isLoading
				? cconvConverter.i18n.converting
				: button.getAttribute( 'data-label' ) || button.querySelector( 'span' ).textContent;
		}

		function convert( event ) {
			if ( event ) {
				event.preventDefault();
			}

			clearError();

			var amount = amountEl.value.trim().replace( /,/g, '' );

			if ( '' === amount || isNaN( parseFloat( amount ) ) || parseFloat( amount ) < 0 ) {
				showError( cconvConverter.i18n.invalid );
				return;
			}

			var originalLabel = button.querySelector( 'span' ).textContent;
			button.setAttribute( 'data-label', originalLabel );
			setLoading( true );

			var formData = new FormData();
			formData.append( 'action', 'bccalc_convert' );
			formData.append( 'nonce', cconvConverter.nonce );
			formData.append( 'amount', amount );
			formData.append( 'from', fromEl.value );
			formData.append( 'to', toEl.value );

			fetch( cconvConverter.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData,
			} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( json ) {
					setLoading( false );

					if ( ! json || ! json.success ) {
						var message = json && json.data && json.data.message ? json.data.message : cconvConverter.i18n.error;
						showError( message );
						if ( resultEl ) {
							resultEl.textContent = '';
						}
						if ( rateEl ) {
							rateEl.textContent = '';
						}
						return;
					}

					var data = json.data;

					if ( resultEl ) {
						// The API returns amount and converted values as fixed 2-decimal strings.
						resultEl.textContent = data.amount + ' ' + data.from + ' = ' + data.converted + ' ' + data.to;
					}
					if ( rateEl ) {
						// The API returns the exchange rate as a fixed 4-decimal string.
						rateEl.textContent = '1 ' + data.from + ' = ' + data.rate + ' ' + data.to;
					}
					if ( timeEl ) {
						timeEl.textContent = data.timestamp ? 'Date: ' + data.timestamp : '';
					}
				} )
				.catch( function () {
					setLoading( false );
					showError( cconvConverter.i18n.error );
				} );
		}

		var from = createDropdown(
			root.querySelector( '.cconv-from-group' ),
			function () {
				convert();
			}
		);
		var to = createDropdown(
			root.querySelector( '.cconv-to-group' ),
			function () {
				convert();
			}
		);

		form.addEventListener( 'submit', convert );

		if ( switchBtn ) {
			switchBtn.addEventListener( 'click', function () {
				var temp = fromEl.value;
				fromEl.value = toEl.value;
				toEl.value = temp;
				if ( from ) {
					from.refresh( fromEl.value );
				}
				if ( to ) {
					to.refresh( toEl.value );
				}
				convert();
			} );
		}

		// Sync dropdowns with the initial defaults, then run an initial
		// conversion on load so the widget isn't empty.
		if ( from ) {
			from.refresh( fromEl.value );
		}
		if ( to ) {
			to.refresh( toEl.value );
		}
		convert();
	}

	document.addEventListener( 'click', function () {
		if ( activeDropdown ) {
			activeDropdown.close();
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key && activeDropdown ) {
			activeDropdown.close();
		}
	} );

	document.addEventListener( 'DOMContentLoaded', function () {
		var converters = document.querySelectorAll( '.cconv-currency-converter' );
		converters.forEach( initConverter );
	} );
} )();