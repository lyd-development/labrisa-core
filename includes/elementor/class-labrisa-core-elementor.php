<?php

/**
 * Elementor integration bootstrap.
 *
 * Registers the plugin's Elementor widget category, autoloads/registers
 * widgets from includes/elementor/widgets, and registers the shared
 * front-end assets those widgets depend on. Dynamic tags (once added under
 * includes/elementor/dynamic-tags) hook in the same way via register_dynamic_tags().
 *
 * @link       https://lydbaligroup.com
 * @since      1.0.0
 *
 * @package    Labrisa_Core
 * @subpackage Labrisa_Core/includes/elementor
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * All callbacks here are only ever invoked by Elementor's own hooks, so they
 * are safe to register even when Elementor is not installed/active — the
 * hooks simply never fire in that case.
 *
 * @since      1.0.0
 * @package    Labrisa_Core
 * @subpackage Labrisa_Core/includes/elementor
 */
class Labrisa_Core_Elementor {

	/**
	 * Elementor widget category slug used by every widget in this plugin.
	 */
	const CATEGORY = 'labrisa-core';

	/**
	 * Register the "Labrisa Core" widget category so widgets are grouped
	 * together in the Elementor panel instead of falling under "General".
	 *
	 * @since    1.0.0
	 * @param    \Elementor\Elements_Manager    $elements_manager
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			self::CATEGORY,
			array(
				'title' => __( 'Labrisa Core', 'labrisa-core' ),
				'icon'  => 'eicon-plug',
			)
		);
	}

	/**
	 * Register plugin widgets with Elementor.
	 *
	 * @since    1.0.0
	 * @param    \Elementor\Widgets_Manager    $widgets_manager
	 */
	public function register_widgets( $widgets_manager ) {
		require_once LABRISA_CORE_PLUGIN_DIR . 'includes/elementor/widgets/class-widget-past-events.php';
		require_once LABRISA_CORE_PLUGIN_DIR . 'includes/elementor/widgets/class-widget-upcoming-events.php';
		require_once LABRISA_CORE_PLUGIN_DIR . 'includes/elementor/widgets/class-widget-all-events.php';
		require_once LABRISA_CORE_PLUGIN_DIR . 'includes/elementor/widgets/class-widget-regular-events.php';
		require_once LABRISA_CORE_PLUGIN_DIR . 'includes/elementor/widgets/class-widget-featured-events.php';

		$widgets_manager->register( new Labrisa_Core_Elementor_Widget_Past_Events() );
		$widgets_manager->register( new Labrisa_Core_Elementor_Widget_Upcoming_Events() );
		$widgets_manager->register( new Labrisa_Core_Elementor_Widget_All_Events() );
		$widgets_manager->register( new Labrisa_Core_Elementor_Widget_Regular_Events() );
		$widgets_manager->register( new Labrisa_Core_Elementor_Widget_Featured_Events() );
	}

	/**
	 * Register (but do not enqueue) front-end assets shared by widgets.
	 * Widgets declare them via get_style_depends()/get_script_depends() so
	 * Elementor only loads them on pages that actually use the widget.
	 *
	 * @since    1.0.0
	 */
	public function register_assets() {
		wp_register_style(
			'labrisa-core-events',
			LABRISA_CORE_PLUGIN_URL . 'public/css/labrisa-core-events.css',
			array(),
			LABRISA_CORE_VERSION
		);

		wp_register_script(
			'labrisa-core-past-events',
			LABRISA_CORE_PLUGIN_URL . 'public/js/labrisa-core-past-events.js',
			array(),
			LABRISA_CORE_VERSION,
			true
		);

		// 'swiper' is registered by Elementor core itself (includes/frontend.php)
		// from its bundled assets/lib/swiper/v8/ — reuse it instead of
		// shipping a second copy, since Elementor is already a hard
		// requirement for every includes/elementor/* class.
		wp_register_style(
			'labrisa-core-upcoming-events',
			LABRISA_CORE_PLUGIN_URL . 'public/css/labrisa-core-upcoming-events.css',
			array( 'swiper' ),
			LABRISA_CORE_VERSION
		);

		wp_register_script(
			'labrisa-core-upcoming-events',
			LABRISA_CORE_PLUGIN_URL . 'public/js/labrisa-core-upcoming-events.js',
			array( 'swiper' ),
			LABRISA_CORE_VERSION,
			true
		);

		wp_register_style(
			'labrisa-core-all-events',
			LABRISA_CORE_PLUGIN_URL . 'public/css/labrisa-core-all-events.css',
			array( 'swiper' ),
			LABRISA_CORE_VERSION
		);

		wp_register_script(
			'labrisa-core-all-events',
			LABRISA_CORE_PLUGIN_URL . 'public/js/labrisa-core-all-events.js',
			array( 'swiper' ),
			LABRISA_CORE_VERSION,
			true
		);

		wp_register_style(
			'labrisa-core-regular-events',
			LABRISA_CORE_PLUGIN_URL . 'public/css/labrisa-core-regular-events.css',
			array( 'swiper' ),
			LABRISA_CORE_VERSION
		);

		wp_register_script(
			'labrisa-core-regular-events',
			LABRISA_CORE_PLUGIN_URL . 'public/js/labrisa-core-regular-events.js',
			array( 'swiper' ),
			LABRISA_CORE_VERSION,
			true
		);

		wp_register_style(
			'labrisa-core-featured-events',
			LABRISA_CORE_PLUGIN_URL . 'public/css/labrisa-core-featured-events.css',
			array( 'swiper' ),
			LABRISA_CORE_VERSION
		);

		wp_register_script(
			'labrisa-core-featured-events',
			LABRISA_CORE_PLUGIN_URL . 'public/js/labrisa-core-featured-events.js',
			array( 'swiper' ),
			LABRISA_CORE_VERSION,
			true
		);
	}

	/**
	 * Bridge for the Past Events widget's "Load More" AJAX endpoint
	 * (wp_ajax_labrisa_core_load_more_past_events / its _nopriv_ twin, wired
	 * up in Labrisa_Core::define_elementor_hooks()).
	 *
	 * Those wp_ajax_* hooks are registered on this plain class — not
	 * directly on Labrisa_Core_Elementor_Widget_Past_Events — because that
	 * class extends \Elementor\Widget_Base, which may not be loaded yet at
	 * the point define_elementor_hooks() runs (Elementor's own classes are
	 * only guaranteed available once its 'elementor/widgets/register' hook
	 * fires). This method only requires/instantiates that widget class once
	 * the AJAX action actually fires, by which point every plugin —
	 * including Elementor — has finished loading.
	 *
	 * @since    1.0.0
	 */
	public function ajax_load_more_past_events() {
		require_once LABRISA_CORE_PLUGIN_DIR . 'includes/elementor/widgets/class-widget-past-events.php';

		$widget = new Labrisa_Core_Elementor_Widget_Past_Events();
		$widget->ajax_render_more();
	}
}
