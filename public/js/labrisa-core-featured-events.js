/**
 * Front-end behaviour for the Labrisa Core "Featured Events" widget: a
 * single-slide-at-a-time Swiper carousel (one event fully visible per
 * slide) with optional autoplay. Swiper itself is not bundled here — it
 * reuses the 'swiper' script Elementor core already registers from its own
 * assets/lib/swiper/v8/, declared as a dependency in
 * Labrisa_Core_Elementor::register_assets().
 */
( function () {
	'use strict';

	/**
	 * @param {Element} root
	 */
	function initCarousel( root ) {
		var container = root.querySelector( '.labrisa-featured-events__swiper' );

		if ( ! container || 'undefined' === typeof window.Swiper ) {
			return;
		}

		// Elementor fires frontend/element_ready again on every re-render
		// (e.g. any settings change in the editor), which would otherwise
		// stack a second Swiper instance on the same DOM node. Destroy any
		// previous instance first — Swiper attaches itself as `container.swiper`.
		if ( container.swiper ) {
			container.swiper.destroy( true, true );
		}

		var prevEl = root.querySelector( '[data-labrisa-featured-prev]' );
		var nextEl = root.querySelector( '[data-labrisa-featured-next]' );
		var slideCount = container.querySelectorAll( '.swiper-slide' ).length;
		var loop = 'yes' === container.dataset.loop && slideCount > 1;
		var autoplayEnabled = 'yes' === container.dataset.autoplay && slideCount > 1;
		var autoplaySpeed = parseInt( container.dataset.autoplaySpeed, 10 ) || 5000;
		var pauseOnHover = 'yes' === container.dataset.pauseOnHover;

		new window.Swiper( container, { // eslint-disable-line no-new
			slidesPerView: 1,
			loop: loop,
			grabCursor: true,
			navigation: ( prevEl && nextEl ) ? { prevEl: prevEl, nextEl: nextEl } : false,
			autoplay: autoplayEnabled
				? {
					delay: autoplaySpeed,
					disableOnInteraction: false,
					pauseOnMouseEnter: pauseOnHover,
				}
				: false,
		} );
	}

	function initWidget( scope ) {
		var root = scope && scope.get ? scope.get( 0 ) : scope;

		if ( ! root ) {
			return;
		}

		initCarousel( root );
	}

	if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
		window.elementorFrontend.hooks.addAction(
			'frontend/element_ready/labrisa-featured-events.default',
			initWidget
		);
	} else {
		document.addEventListener( 'DOMContentLoaded', function () {
			document.querySelectorAll( '.labrisa-featured-events' ).forEach( initWidget );
		} );
	}
} )();
