<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://lydbaligroup.com
 * @since      1.0.0
 *
 * @package    Labrisa_Core
 * @subpackage Labrisa_Core/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Labrisa_Core
 * @subpackage Labrisa_Core/admin
 * @author     Web Developer <webmaster@lydbaligroup.com>
 */
class Labrisa_Core_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Labrisa_Core_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Labrisa_Core_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/labrisa-core-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Labrisa_Core_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Labrisa_Core_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/labrisa-core-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Default the "Events" admin list table (Posts > Events) to newest
	 * published date first.
	 *
	 * The `events` post type is registered by ACF with `hierarchical: true`
	 * (see sources/acf-export-2026-08-05.json), which makes WordPress core
	 * default its admin-list `WP_Query` to `orderby => 'menu_order title'`
	 * — the same default Pages get — instead of the usual `date DESC` that
	 * non-hierarchical post types like Posts get. This restores date
	 * ordering as the default, without touching it once the admin has
	 * explicitly clicked a column header to sort by something else.
	 *
	 * @since    1.0.0
	 * @param    WP_Query $query
	 */
	public function set_events_admin_default_order( $query ) {

		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( Labrisa_Core_Events::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( ! $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'date' );
			$query->set( 'order', 'DESC' );
		}

	}

	/**
	 * Insert an "Event Date" column (event_date ACF field) into the Events
	 * admin list table, right after the Title column.
	 *
	 * @since    1.0.0
	 * @param    array $columns
	 * @return   array
	 */
	public function add_events_date_column( $columns ) {

		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;

			if ( 'title' === $key ) {
				$new_columns['event_date'] = __( 'Event Date', 'labrisa-core' );
			}
		}

		return $new_columns;

	}

	/**
	 * Output the value for the "Event Date" admin list column added by
	 * add_events_date_column().
	 *
	 * @since    1.0.0
	 * @param    string $column
	 * @param    int    $post_id
	 */
	public function render_events_date_column( $column, $post_id ) {

		if ( 'event_date' !== $column ) {
			return;
		}

		$event_date = get_post_meta( $post_id, 'event_date', true );

		if ( empty( $event_date ) ) {
			echo '&#8212;';
			return;
		}

		echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $event_date ) );

	}

	/**
	 * Make the "Event Date" admin list column sortable.
	 *
	 * @since    1.0.0
	 * @param    array $columns
	 * @return   array
	 */
	public function make_events_date_column_sortable( $columns ) {

		$columns['event_date'] = 'event_date';

		return $columns;

	}

	/**
	 * Handle sorting by the "Event Date" column: WP_Query needs an explicit
	 * meta_key + orderby => 'meta_value' pair to sort by an ACF field, since
	 * "event_date" itself isn't a native sortable column/query var.
	 *
	 * @since    1.0.0
	 * @param    WP_Query $query
	 */
	public function sort_events_by_date_column( $query ) {

		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( Labrisa_Core_Events::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( 'event_date' !== $query->get( 'orderby' ) ) {
			return;
		}

		$query->set( 'meta_key', 'event_date' ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$query->set( 'orderby', 'meta_value' );

	}

}
