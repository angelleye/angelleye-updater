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
        delete_transient('license_key_status_check');
        delete_site_transient( 'update_plugins' );
        delete_site_option('angelleye_helper_dismiss_activation_notice');
    }

}
