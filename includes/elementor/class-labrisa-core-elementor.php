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

		$widgets_manager->register( new Labrisa_Core_Elementor_Widget_Past_Events() );
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
	}
}
