<?php

if (!defined('ABSPATH')) {
    exit;
}

class Angelleye_Rollback_Plugin_Upgrader extends Plugin_Upgrader {

    public function rollback($plugin, $args = array()) {

        $this->api = new AngellEYE_Updater_API();
        $defaults = array(
            'clear_update_cache' => true,
        );
        $parsed_args = wp_parse_args($args, $defaults);
        $this->init();
        $this->upgrade_strings();
        $plugin_slug = $this->skin->plugin;
        $plugin_version = $this->skin->options['version'];
        $zip_full_path = add_query_arg(array('action' => 'rollback_download', 'file_id' => '999', 'product_id' => $plugin_slug, 'version' => $plugin_version), $this->api->api_url);
        $url = $zip_full_path;
        add_filter('upgrader_pre_install', array($this, 'deactivate_plugin_before_upgrade'), 10, 2);
        add_filter('upgrader_clear_destination', array($this, 'delete_old_plugin'), 10, 4);
        $this->run(array(
            'package' => $url,
            'destination' => WP_PLUGIN_DIR,
            'clear_destination' => true,
            'clear_working' => true,
            'hook_extra' => array(
                'plugin' => $plugin,
                'type' => 'plugin',
                'action' => 'update',
            ),
        ));
        remove_filter('upgrader_pre_install', array($this, 'deactivate_plugin_before_upgrade'));
        remove_filter('upgrader_clear_destination', array($this, 'delete_old_plugin'));
        if (!$this->result || is_wp_error($this->result)) {
            return $this->result;
        }
        wp_clean_plugins_cache($parsed_args['clear_update_cache']);
        return true;
    }

}