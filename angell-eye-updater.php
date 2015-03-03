<?php

/**
 *
 * @wordpress-plugin
 * Plugin Name:       Angell EYE Updater
 * Plugin URI:        http://www.angelleye.com/
 * Description:       Manage activations and updates for premium extensions to Angell EYE plugins.
 * Version:           1.0.0
 * Author:            Angell EYE
 * Author URI:        http://www.angelleye.com/
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       angell-eye-updater
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * define plugin basename
 */
if (!defined('AA_PLUGIN_BASENAME')) {
    define('AA_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

/**
 *  define PIW_PLUGIN_DIR constant for global use
 */
if (!defined('AU_PLUGIN_DIR'))
    define('AU_PLUGIN_DIR', dirname(__FILE__));

/**
 * define PIW_PLUGIN_URL constant for global use
 */
if (!defined('AU_PLUGIN_URL'))
    define('AU_PLUGIN_URL', plugin_dir_url(__FILE__));


/**
 * define plugin basename
 */
if (!defined('AU_PLUGIN_BASENAME')) {
    define('AU_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-angell-eye-updater-activator.php
 */
function activate_angelleye_updater() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-angell-eye-updater-activator.php';
    AngellEYE_Updater_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-angell-eye-updater-deactivator.php
 */
function deactivate_angelleye_updater() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-angell-eye-updater-deactivator.php';
    AngellEYE_Updater_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_angelleye_updater');
register_deactivation_hook(__FILE__, 'deactivate_angelleye_updater');

/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-angell-eye-updater.php';

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
    $angeleye_updater = new AngellEYE_Updater(__FILE__);
    $angeleye_updater->version = '1.0.0';
    $angeleye_updater->run();
}

run_angelleye_updater();
