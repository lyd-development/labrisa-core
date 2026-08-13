<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://lydbaligroup.com
 * @since             1.0.0
 * @package           Labrisa_Core
 *
 * @wordpress-plugin
 * Plugin Name:       Labrisa Core
 * Plugin URI:        https://lydbaligroup.com
 * Description:       Plugin for Labrisa Core Functions
 * Version:           1.0.46
 * Author:            Web Developer
 * Author URI:        https://lydbaligroup.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       labrisa-core
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'LABRISA_CORE_VERSION', '1.0.46' );

/**
 * Absolute filesystem path and URL to the plugin root, used throughout the
 * plugin instead of repeated plugin_dir_path()/plugin_dir_url() calls.
 */
define( 'LABRISA_CORE_PLUGIN_FILE', __FILE__ );
define( 'LABRISA_CORE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LABRISA_CORE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-labrisa-core-activator.php
 */
function activate_labrisa_core() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-labrisa-core-activator.php';
	Labrisa_Core_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-labrisa-core-deactivator.php
 */
function deactivate_labrisa_core() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-labrisa-core-deactivator.php';
	Labrisa_Core_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_labrisa_core' );
register_deactivation_hook( __FILE__, 'deactivate_labrisa_core' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-labrisa-core.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_labrisa_core() {

	$plugin = new Labrisa_Core();
	$plugin->run();

}
run_labrisa_core();
