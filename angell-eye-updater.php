<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * Dashboard. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           AngellEYE_Updater
 *
 * @wordpress-plugin
 * Plugin Name:       Angell EYE Updater
 * Plugin URI:        http://example.com/angell-eye-updater-uri/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress dashboard.
 * Version:           1.0.0
 * Author:            angelleye
 * Author URI:        http://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       angell-eye-updater
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-angell-eye-updater-activator.php
 */
function activate_angelleye_updater() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-angell-eye-updater-activator.php';
	AngellEYE_Updater_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-angell-eye-updater-deactivator.php
 */
function deactivate_angelleye_updater() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-angell-eye-updater-deactivator.php';
	AngellEYE_Updater_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_angelleye_updater' );
register_deactivation_hook( __FILE__, 'deactivate_angelleye_updater' );

/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-angell-eye-updater.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_angelleye_updater() {

	global $angeleye_updater;
	$angeleye_updater = new AngellEYE_Updater( __FILE__ );
	$angeleye_updater->version = '1.4.1';
	$angeleye_updater->run();

}
run_angelleye_updater();
