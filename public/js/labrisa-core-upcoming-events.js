/**
 * Front-end behaviour for the Labrisa Core "Upcoming Events" widget: a
 * Swiper carousel (navigation only, no autoplay) plus the "Explore More"
 * popup. Swiper itself is not bundled here — it reuses the 'swiper' script
 * Elementor core already registers from its own assets/lib/swiper/v8/,
 * declared as a dependency in Labrisa_Core_Elementor::register_assets().
 */
( function () {
	'use strict';

	/**
	 * @param {Element} root
	 */
	function initCarousel( root ) {
		var container = root.querySelector( '.labrisa-marquee__swiper' );

		if ( ! container || 'undefined' === typeof window.Swiper ) {
			return;
		}

		// Elementor fires frontend/element_ready again on every re-render
		// (e.g. any settings change in the editor), which would otherwise
		// stack a second Swiper instance on the same DOM node and break
		// navigation/loop. Destroy any previous instance on this element
		// first — Swiper attaches itself as `container.swiper`.
		if ( container.swiper ) {
			container.swiper.destroy( true, true );
		}

		var prevEl = root.querySelector( '[data-labrisa-prev]' );
		var nextEl = root.querySelector( '[data-labrisa-next]' );
		var gap = parseFloat( getComputedStyle( root.querySelector( '.labrisa-marquee' ) ).getPropertyValue( '--labrisa-marquee-gap' ) );
		var loop = 'yes' === container.dataset.loop;
		var slideCount = container.querySelectorAll( '.swiper-slide' ).length;

		new window.Swiper( container, { // eslint-disable-line no-new
			slidesPerView: 'auto',
			spaceBetween: isNaN( gap ) ? 24 : gap,
			loop: loop,
			// With slidesPerView: 'auto' and only a handful of real slides,
			// Swiper's default loop-clone count under-estimates how many
			// duplicates it needs, which makes slideNext() get stuck after
			// the first wrap while slidePrev() keeps working (the two use
			// different shift-correction math). Forcing enough cloned
			// slides fixes it — see swiper's loop + slidesPerView:'auto' + few slides.
			loopedSlides: loop ? Math.max( slideCount * 2, 8 ) : undefined,
			grabCursor: true,
			navigation: ( prevEl && nextEl ) ? { prevEl: prevEl, nextEl: nextEl } : false,
		} );
	}

	function closeModal( modal ) {
		modal.hidden = true;
		document.body.classList.remove( 'labrisa-event-modal-open' );
	}

	function openModal( modal, data ) {
		modal.querySelector( '.labrisa-event-modal__title' ).textContent = data.title || '';

		var metaParts = [ data.date, data.place ].filter( Boolean );
		modal.querySelector( '.labrisa-event-modal__meta' ).textContent = metaParts.join( ' • ' );

		modal.querySelector( '.labrisa-event-modal__terms' ).innerHTML = data.terms || '';

		var img = modal.querySelector( '.labrisa-event-modal__media img' );
		if ( data.image ) {
			img.src = data.image;
			img.alt = data.title || '';
			img.hidden = false;
		} else {
			img.hidden = true;
			img.removeAttribute( 'src' );
		}

		var cta = modal.querySelector( '.labrisa-event-modal__cta' );
		if ( data.ticketUrl ) {
			cta.href = data.ticketUrl;
			cta.hidden = false;
		} else {
			cta.hidden = true;
		}

		modal.hidden = false;
		document.body.classList.add( 'labrisa-event-modal-open' );
	}

	function initModal( root ) {
		var modal = root.querySelector( '[data-labrisa-event-modal]' );

		if ( ! modal || modal.dataset.labrisaBound ) {
			return modal;
		}

		modal.dataset.labrisaBound = '1';

		// Move the modal to <body> so position:fixed isn't trapped by an
		// Elementor motion-effect ancestor with a CSS transform.
		document.body.appendChild( modal );

		modal.addEventListener( 'click', function ( event ) {
			if ( event.target.closest( '[data-labrisa-event-modal-close]' ) ) {
				closeModal( modal );
			}
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && ! modal.hidden ) {
				closeModal( modal );
			}
		} );

		return modal;
	}

	function initWidget( scope ) {
		var root = scope && scope.get ? scope.get( 0 ) : scope;

		if ( ! root ) {
			return;
		}

		initCarousel( root );

		var modal = initModal( root );

		root.addEventListener( 'click', function ( event ) {
			var trigger = event.target.closest( '[data-labrisa-explore]' );

			if ( ! trigger ) {
				return;
			}

			event.preventDefault();

			var payload = {};
			try {
				payload = JSON.parse( trigger.getAttribute( 'data-labrisa-explore' ) );
			} catch ( error ) {
				payload = {};
			}

			if ( modal ) {
				openModal( modal, payload );
			}
		} );
	}

	if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
		window.elementorFrontend.hooks.addAction(
			'frontend/element_ready/labrisa-upcoming-events.default',
			initWidget
		);
	} else {
		document.addEventListener( 'DOMContentLoaded', function () {
			document.querySelectorAll( '.labrisa-upcoming-events' ).forEach( initWidget );
		} );
	}
} )();
