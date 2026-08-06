/**
 * Front-end behaviour for the Labrisa Core "All Events" widget: a Swiper
 * carousel with autoplay (a continuously auto-advancing "marquee") plus the
 * "Explore More" popup. Swiper itself is not bundled here — it reuses the
 * 'swiper' script Elementor core already registers from its own
 * assets/lib/swiper/v8/, declared as a dependency in
 * Labrisa_Core_Elementor::register_assets().
 *
 * Structurally identical to labrisa-core-upcoming-events.js except for the
 * autoplay block below and the widget/wrapper names it targets.
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
		// navigation/loop/autoplay. Destroy any previous instance on this
		// element first — Swiper attaches itself as `container.swiper`.
		if ( container.swiper ) {
			container.swiper.destroy( true, true );
		}

		var prevEl = root.querySelector( '[data-labrisa-prev]' );
		var nextEl = root.querySelector( '[data-labrisa-next]' );
		var gap = parseFloat( getComputedStyle( root.querySelector( '.labrisa-marquee' ) ).getPropertyValue( '--labrisa-marquee-gap' ) );
		var loop = 'yes' === container.dataset.loop;
		var slideCount = container.querySelectorAll( '.swiper-slide' ).length;
		var autoplayEnabled = 'yes' === container.dataset.autoplay;
		var autoplaySpeed = parseInt( container.dataset.autoplaySpeed, 10 ) || 3000;
		var pauseOnHover = 'yes' === container.dataset.pauseOnHover;

		new window.Swiper( container, { // eslint-disable-line no-new
			slidesPerView: 'auto',
			spaceBetween: isNaN( gap ) ? 24 : gap,
			loop: loop,
			// With slidesPerView: 'auto' and only a handful of real slides,
			// Swiper's default loop-clone count under-estimates how many
			// duplicates it needs, which makes slideNext() (and autoplay)
			// get stuck after the first wrap. Forcing enough cloned slides
			// fixes it — see swiper's loop + slidesPerView:'auto' + few slides.
			loopedSlides: loop ? Math.max( slideCount * 2, 8 ) : undefined,
			// Next-card "peek" (Enable Offset control) is handled purely in
			// CSS via --labrisa-marquee-peek, not slidesOffsetBefore — that
			// option pulls a cloned slide into view on the *left* edge too
			// once Infinite Loop is on, which isn't the intended effect here.
			grabCursor: true,
			navigation: ( prevEl && nextEl ) ? { prevEl: prevEl, nextEl: nextEl } : false,
			// A true continuous "marquee" (no per-card pause) rather than
			// Swiper's default step-then-wait autoplay: delay is set to ~0 so
			// the next glide starts the instant the current one ends, speed
			// controls how long that continuous glide across one card takes
			// (lower = faster), and freeMode keeps the motion from snapping
			// to slide boundaries between glides.
			speed: autoplayEnabled ? autoplaySpeed : 300,
			freeMode: autoplayEnabled ? { enabled: true, momentum: false } : false,
			autoplay: autoplayEnabled
				? {
					delay: 1,
					disableOnInteraction: false,
					pauseOnMouseEnter: pauseOnHover,
				}
				: false,
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
			'frontend/element_ready/labrisa-all-events.default',
			initWidget
		);
	} else {
		document.addEventListener( 'DOMContentLoaded', function () {
			document.querySelectorAll( '.labrisa-all-events' ).forEach( initWidget );
		} );
	}
} )();
