/**
 * Front-end behaviour for the Labrisa Core "Upcoming Events" widget: a
 * Swiper carousel (manual navigation only, no autoplay) plus the
 * "Explore More" popup. Swiper itself is Elementor core's own bundled
 * copy (registered under the 'swiper' handle), not a copy we ship.
 */
( function () {
	'use strict';

	/**
	 * slidesPerView:'auto' + loop reads slide width/margin straight off the
	 * DOM (set via the widget's "Slide Width"/"Gap" controls in CSS), so no
	 * JS sizing config is needed here beyond wiring up the custom nav arrows.
	 */
	function initCarousel( root ) {
		var container = root.querySelector( '[data-labrisa-swiper]' );

		if ( ! container || ! window.Swiper ) {
			return;
		}

		var prevEl = root.querySelector( '[data-labrisa-swiper-prev]' );
		var nextEl = root.querySelector( '[data-labrisa-swiper-next]' );
		var loop = 'yes' === container.dataset.loop;
		var slideCount = container.querySelectorAll( '.swiper-slide' ).length;

		new window.Swiper( container, { // eslint-disable-line no-new
			slidesPerView: 'auto',
			watchOverflow: true,
			loop: loop,
			/*
			 * With slidesPerView:'auto', Swiper's automatic guess at how many
			 * loop-clone slides to generate on each side is unreliable, so
			 * navigating one direction (typically "next") can run out of
			 * clones and stop looping before the other direction does.
			 * Forcing enough additional clones for a full extra set fixes it.
			 */
			loopAdditionalSlides: loop ? slideCount : 0,
			navigation: prevEl && nextEl ? { prevEl: prevEl, nextEl: nextEl } : false,
			a11y: true,
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
