/**
 * Front-end behaviour for the Labrisa Core "Past Events" widget's "Load
 * More" pagination method — fetches the next page of event cards from
 * Labrisa_Core_Elementor_Widget_Past_Events::ajax_render_more() (via
 * wp_ajax_labrisa_core_load_more_past_events) and appends them to the grid.
 * "Classic Pagination" needs no JS at all — it's plain paginate_links().
 */
( function () {
	'use strict';

	/**
	 * @param {HTMLButtonElement} button
	 */
	function initButton( button ) {
		if ( button.dataset.labrisaBound ) {
			return;
		}

		button.dataset.labrisaBound = '1';

		button.addEventListener( 'click', function () {
			var wrapper = button.closest( '.labrisa-past-events' );
			var grid = wrapper ? wrapper.querySelector( '[data-labrisa-events-grid]' ) : null;

			if ( ! grid ) {
				return;
			}

			var payload;
			try {
				payload = JSON.parse( button.getAttribute( 'data-payload' ) );
			} catch ( error ) {
				return;
			}

			var body = new FormData();
			body.append( 'action', 'labrisa_core_load_more_past_events' );
			body.append( 'nonce', button.dataset.nonce );
			body.append( 'page', button.dataset.page );
			body.append( 'gallery_id', payload.gallery_id || '' );

			Object.keys( payload.query || {} ).forEach( function ( key ) {
				var value = payload.query[ key ];
				if ( Array.isArray( value ) ) {
					value.forEach( function ( item ) {
						body.append( 'query[' + key + '][]', item );
					} );
				} else {
					body.append( 'query[' + key + ']', value );
				}
			} );

			Object.keys( payload.render || {} ).forEach( function ( key ) {
				body.append( 'render[' + key + ']', payload.render[ key ] );
			} );

			button.disabled = true;
			button.classList.add( 'labrisa-events-load-more__btn--loading' );

			fetch( button.dataset.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: body,
			} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( json ) {
					if ( ! json || ! json.success || ! json.data ) {
						return;
					}

					grid.insertAdjacentHTML( 'beforeend', json.data.html );
					button.dataset.page = json.data.nextPage;

					if ( ! json.data.hasMore ) {
						button.parentNode.removeChild( button );
					}
				} )
				.catch( function () {
					// Leave the button as-is so the visitor can retry.
				} )
				.finally( function () {
					button.disabled = false;
					button.classList.remove( 'labrisa-events-load-more__btn--loading' );
				} );
		} );
	}

	function initWidget( scope ) {
		var root = scope && scope.get ? scope.get( 0 ) : scope;

		if ( ! root ) {
			return;
		}

		var button = root.querySelector( '[data-labrisa-load-more]' );

		if ( button ) {
			initButton( button );
		}
	}

	if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
		window.elementorFrontend.hooks.addAction(
			'frontend/element_ready/labrisa-past-events.default',
			initWidget
		);
	} else {
		document.addEventListener( 'DOMContentLoaded', function () {
			document.querySelectorAll( '.labrisa-past-events' ).forEach( initWidget );
		} );
	}
} )();
