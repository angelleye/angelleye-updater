<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    AngellEYE_Updater
 * @subpackage AngellEYE_Updater/includes
 * @author     Angell EYE <service@angelleye.com>
 */
class AngellEYE_Updater_Activator {

	/**
     * @since    1.0.0
     */
	public static function activate() {
		if (!defined('AU_PLUGIN_BASENAME')) {
			deactivate_plugins(AU_PLUGIN_DIR . '/angell-eye-updater.php');
			wp_die("<strong>Angell EYE Updater</strong> requires <strong>PayPal IPN for WordPress</strong> plugin to work normally. Please activate it or install it.<br /><br />Back to the WordPress <a href='" . get_admin_url(null, 'plugins.php') . "'>Plugins page</a>.");
		} else {

			/**
             * Log activation in Angell EYE database via web service.
             */
			//$log_url = $_SERVER['HTTP_HOST'];
			//$log_plugin_id = 7;
			//$log_activation_status = 1;
			//wp_remote_request('http://www.angelleye.com/web-services/wordpress/update-plugin-status.php?url=' . $log_url . '&plugin_id=' . $log_plugin_id . '&activation_status=' . $log_activation_status);
		}
	}
	
}
