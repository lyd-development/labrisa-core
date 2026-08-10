<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://lydbaligroup.com
 * @since      1.0.0
 *
 * @package    Labrisa_Core
 * @subpackage Labrisa_Core/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Labrisa_Core
 * @subpackage Labrisa_Core/includes
 * @author     Web Developer <webmaster@lydbaligroup.com>
 */
class Labrisa_Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Labrisa_Core_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'LABRISA_CORE_VERSION' ) ) {
			$this->version = LABRISA_CORE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'labrisa-core';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_elementor_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Labrisa_Core_Loader. Orchestrates the hooks of the plugin.
	 * - Labrisa_Core_i18n. Defines internationalization functionality.
	 * - Labrisa_Core_Admin. Defines all hooks for the admin area.
	 * - Labrisa_Core_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-labrisa-core-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-labrisa-core-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-labrisa-core-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-labrisa-core-public.php';

		/**
		 * Query helpers for the "events" custom post type, shared by the
		 * Elementor widgets, dynamic tags, blocks, and the CSV admin screen
		 * below.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/post-types/class-labrisa-core-events.php';

		/**
		 * The class responsible for the Events CSV export/import admin screen.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-labrisa-core-events-csv.php';

		/**
		 * The class responsible for registering this plugin's Elementor
		 * widget category, widgets, and their shared assets. Its callbacks
		 * only run when Elementor is active, since they are hooked to
		 * Elementor-specific actions.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/elementor/class-labrisa-core-elementor.php';

		$this->loader = new Labrisa_Core_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Labrisa_Core_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Labrisa_Core_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Labrisa_Core_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'pre_get_posts', $plugin_admin, 'set_events_admin_default_order' );

		$plugin_events_csv = new Labrisa_Core_Admin_Events_CSV();

		$this->loader->add_action( 'admin_menu', $plugin_events_csv, 'register_menu' );
		$this->loader->add_action( 'admin_post_labrisa_core_export_events', $plugin_events_csv, 'handle_export' );
		$this->loader->add_action( 'admin_post_labrisa_core_import_events', $plugin_events_csv, 'handle_import' );
		$this->loader->add_action( 'admin_post_labrisa_core_events_csv_sample', $plugin_events_csv, 'handle_sample' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Labrisa_Core_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to Elementor integration (widget
	 * category, widgets, and their shared assets).
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_elementor_hooks() {

		$plugin_elementor = new Labrisa_Core_Elementor();

		$this->loader->add_action( 'elementor/elements/categories_registered', $plugin_elementor, 'register_category' );
		$this->loader->add_action( 'elementor/widgets/register', $plugin_elementor, 'register_widgets' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_elementor, 'register_assets' );
		$this->loader->add_action( 'wp_ajax_labrisa_core_load_more_past_events', $plugin_elementor, 'ajax_load_more_past_events' );
		$this->loader->add_action( 'wp_ajax_nopriv_labrisa_core_load_more_past_events', $plugin_elementor, 'ajax_load_more_past_events' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Labrisa_Core_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
