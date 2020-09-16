<?php

if (!defined('ABSPATH')) {
    exit;
}

class AngellEYE_Updater_Rollback {

    public $versions;
    public $api;

    public function __construct() {
        add_filter('plugin_action_links', array($this, 'plugin_action_links'), 20, 4);
        add_filter('network_admin_plugin_action_links', array($this, 'plugin_action_links'), 20, 4);
        add_action('admin_menu', array($this, 'admin_menu'), 20);
        add_action('network_admin_menu', array($this, 'admin_menu'), 20);
        $this->api = new AngellEYE_Updater_API();
    }

    public function plugin_action_links($actions, $plugin_file, $plugin_data, $context) {
        $plugin_data = apply_filters('angelleye_plugin_data', $plugin_data);
        if (!isset($plugin_data['AuthorURI']) || strpos($plugin_data['AuthorURI'], 'www.angelleye.com') === false) {
            return $actions;
        }
        if (is_multisite() && (!is_network_admin() && !is_main_site() )) {
            return $actions;
        }
        if (!isset($plugin_data['Version'])) {
            return $actions;
        }
        $rollback_url = 'index.php?page=angelleye-rollback&type=plugin&plugin_file=' . $plugin_file;
        $rollback_url = add_query_arg(
                apply_filters(
                        'angelleye_plugin_query_args', array(
            'current_version' => urlencode($plugin_data['Version']),
            'rollback_name' => urlencode($plugin_data['Name']),
            'plugin_slug' => urlencode($plugin_data['slug']),
            '_wpnonce' => wp_create_nonce('angelleye_rollback_nonce'),
            'product_name' => $plugin_data['TextDomain']
                        )
                ), $rollback_url
        );
        $actions['rollback'] = apply_filters('angelleye_plugin_markup', '<a href="' . esc_url($rollback_url) . '">' . __('Rollback', 'wp-rollback') . '</a>');
        return apply_filters('angelleye_plugin_action_links', $actions);
    }

    public function admin_menu() {

        if (isset($_GET['page']) && $_GET['page'] == 'angelleye-rollback') {
            wp_enqueue_script('updates');
            add_dashboard_page(
                    __('Rollback', 'angelleye-updater'), __('Rollback', 'angelleye-updater'), 'update_plugins', 'angelleye-rollback', array(
                $this,
                'html',
                    )
            );
        }
    }

    public function html() {
        // Permissions check
        if (!current_user_can('update_plugins')) {
            wp_die(__('You do not have sufficient permissions to perform rollbacks for this site.', 'wp-rollback'));
        }

        // Get the necessary class
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        $defaults = apply_filters(
                'wpr_rollback_html_args', array(
            'page' => 'wp-rollback',
            'plugin_file' => '',
            'action' => '',
            'plugin_version' => '',
            'plugin' => '',
                )
        );

        $args = wp_parse_args($_GET, $defaults);

        if (!empty($args['plugin_version'])) {
             check_admin_referer('angelleye_rollback_nonce');
             include AU_PLUGIN_DIR . '/admin/rollback/class-angelleye-rollback-plugin.php';
             include AU_PLUGIN_DIR . '/admin/rollback/angelleye-rollback-action.php';
        } else {
            $versions_html = $this->angelleye_prepare_version_list_html($args);
            check_admin_referer('angelleye_rollback_nonce');
            include AU_PLUGIN_DIR . '/admin/rollback/angelleye-rollback-form.php';
        }
    }

    public function angelleye_prepare_version_list_html($args) {
        $this->angelleye_get_version_tags($args);
        if (empty($this->versions)) {
            $versions_html = '<div class="wpr-error"><p>' . sprintf(__('It appears there are no version to select. This is likely due to the %s author not using tags for their versions and only committing new releases to the repository trunk.', 'wp-rollback'), $type) . '</p></div>';
            return apply_filters('versions_failure_html', $versions_html);
        }
        $versions_html = '<ul class="wpr-version-list">';
        usort($this->versions, 'version_compare');
        $this->versions = array_reverse($this->versions);
        foreach ($this->versions as $version) {
            $versions_html .= '<li class="wpr-version-li">';
            $versions_html .= '<label><input type="radio" value="' . esc_attr($version) . '" name="plugin_version">' . $version;
            if ($version === $this->current_version) {
                $versions_html .= '<span class="current-version">' . __('Installed Version', 'wp-rollback') . '</span>';
            }
            $versions_html .= '</label>';
            $versions_html .= '</li>';
        }
        $versions_html .= '</ul>';
        return $versions_html;
    }

    public function angelleye_get_version_tags($args) {
        $tag_result = $this->api->angelleye_get_plugin_tags($args);
        if( !empty($tag_result->payload) ) {
            foreach ($tag_result->payload as $key => $value) {
                $this->versions[] = $value->version;
            }
        }
        
    }

}
