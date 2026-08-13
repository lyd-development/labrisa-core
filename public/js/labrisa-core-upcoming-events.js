/**
 * Front-end behaviour for the Labrisa Core "Upcoming Events" widget: a
 * Swiper carousel (navigation, plus optional pause-then-advance autoplay —
 * off by default — that moves exactly one card at a time: "Slide Speed"
 * controls the glide's transition duration, "Autoplay Delay" the pause
 * before the next glide starts) plus the "Explore More" popup, plus a
 * second, separate Swiper (initFeaturedCarousel()) with its own,
 * independent autoplay for the desktop-only "featured" fallback layout
 * shown instead when there are fewer than 3 matching events. Swiper itself
 * is not bundled here — it reuses the 'swiper' script Elementor core
 * already registers from its own assets/lib/swiper/v8/, declared as a
 * dependency in Labrisa_Core_Elementor::register_assets().
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
		var autoplayEnabled = 'yes' === container.dataset.autoplay;
		var autoplaySpeed = parseInt( container.dataset.autoplaySpeed, 10 ) || 600;
		var autoplayDelay = parseInt( container.dataset.autoplayDelay, 10 );
		if ( isNaN( autoplayDelay ) ) {
			autoplayDelay = 3000;
		}
		var pauseOnHover = 'yes' === container.dataset.pauseOnHover;

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
			// Next-card "peek" (Enable Offset control) is handled purely in
			// CSS via --labrisa-marquee-peek, not slidesOffsetBefore — that
			// option pulls a cloned slide into view on the *left* edge too
			// once Infinite Loop is on, which isn't the intended effect here.
			grabCursor: true,
			navigation: ( prevEl && nextEl ) ? { prevEl: prevEl, nextEl: nextEl } : false,
			// Standard Swiper slide-by-slide autoplay (no freeMode): with
			// slidesPerView: 'auto' each slide is exactly one card, so this
			// advances exactly one card per cycle — "speed" is the glide's
			// own transition duration, "delay" is the pause after a glide
			// finishes before the next one starts.
			speed: autoplayEnabled ? autoplaySpeed : 300,
			autoplay: autoplayEnabled
				? {
					delay: autoplayDelay,
					disableOnInteraction: false,
					pauseOnMouseEnter: pauseOnHover,
				}
				: false,
		} );
	}

	/**
	 * Swiper for the desktop-only "featured" fallback layout (see
	 * $show_featured_layout in class-widget-upcoming-events.php's render())
	 * — shown instead of initCarousel()'s card marquee when there are fewer
	 * than 3 matching events. With exactly 1 slide, Swiper still
	 * initializes (harmless) but loop/autoplay are skipped and navigation
	 * was never rendered in PHP for that case, so it just displays
	 * statically. Its autoplay is independent from the card carousel's
	 * (own "Featured Layout Autoplay"/"Featured Layout Autoplay Delay"
	 * controls) — a normal pause-then-advance autoplay, not the card
	 * carousel's continuous glide, since only one event is ever visible
	 * here at a time. "Slide Speed" is the transition duration itself,
	 * "Autoplay Delay" the pause before that transition starts.
	 *
	 * @param {Element} root
	 */
	function initFeaturedCarousel( root ) {
		var container = root.querySelector( '.labrisa-upcoming-events__featured-swiper' );

		if ( ! container || 'undefined' === typeof window.Swiper ) {
			return;
		}

		if ( container.swiper ) {
			container.swiper.destroy( true, true );
		}

		var prevEl = root.querySelector( '[data-labrisa-featured-prev]' );
		var nextEl = root.querySelector( '[data-labrisa-featured-next]' );
		var slideCount = container.querySelectorAll( '.swiper-slide' ).length;
		var loop = 'yes' === container.dataset.loop && slideCount > 1;
		var autoplayEnabled = 'yes' === container.dataset.autoplay && slideCount > 1;
		var autoplaySpeed = parseInt( container.dataset.autoplaySpeed, 10 ) || 600;
		var autoplayDelay = parseInt( container.dataset.autoplayDelay, 10 ) || 5000;
		var pauseOnHover = 'yes' === container.dataset.pauseOnHover;

		new window.Swiper( container, { // eslint-disable-line no-new
			slidesPerView: 1,
			loop: loop,
			speed: autoplayEnabled ? autoplaySpeed : 300,
			grabCursor: true,
			navigation: ( prevEl && nextEl ) ? { prevEl: prevEl, nextEl: nextEl } : false,
			autoplay: autoplayEnabled
				? {
					delay: autoplayDelay,
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
		initFeaturedCarousel( root );

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
