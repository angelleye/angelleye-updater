<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    AngellEYE_Updater
 * @subpackage AngellEYE_Updater/includes
 * @author     Angell EYE <service@angelleye.com>
 */
class AngellEYE_Updater_Deactivator {

    /**
     * @since    1.0.0
     */
    public static function deactivate() {

        /**
         * Log deactivation in Angell EYE database via web service.
         */
        $log_url = $_SERVER['HTTP_HOST'];
        $log_plugin_id = 12;
        $log_activation_status = 0;
        wp_remote_request('https://www.angelleye.com/web-services/wordpress/update-plugin-status.php?url=' . $log_url . '&plugin_id=' . $log_plugin_id . '&activation_status=' . $log_activation_status);


    }

}
