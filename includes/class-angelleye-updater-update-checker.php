<?php

defined('ABSPATH') || exit;

/**
 * AngellEYE Updater - Single Updater Class
 *
 * The AngellEYE Updater - updater class.
 *
 * @package WordPress
 * @subpackage AngellEYE Updater
 * @category Core
 * @author      Angell EYE <service@angelleye.com>
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * private $file
 * private $api_url
 * private $product_key
 * private $license_hash
 *
 * - __construct()
 * - update_check()
 * - plugin_information()
 * - request()
 */
class AngellEYE_Updater_Update_Checker {

    protected $api_url;
    public $updater_helper;

    /**
     * Constructor.
     *
     * @access public
     * @since  1.0.0
     * @return void
     */
    public function __construct() {
        $this->api_url = AU_WEBSITE_URL . '?AngellEYE_Activation';
        if (!class_exists('AngellEYE_Updater_Helper_Function')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-angelleye-updater-helper-function.php';
        }
        $this->updater_helper = AngellEYE_Updater_Helper_Function::instance();
        $this->init();
    }

// End __construct()

    /**
     * Initialise the update check process.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function init() {
        // Check For Updates
        add_filter('pre_set_site_transient_update_plugins', array($this, 'update_check'), 20, 1);

        // Check For Plugin Information
        add_filter('plugins_api', array($this, 'plugin_information'), 20, 3);
        add_filter("upgrader_post_install", array($this, "angelleye_post_install"), 20, 3);
    }

// End init()

    /**
     * Check for updates against the remote server.
     *
     * @access public
     * @since  1.0.0
     * @param  object $transient
     * @return object $transient
     */
    public function update_check($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        $plugin_list_data = $this->updater_helper->load_updater_instances();
        if (!empty($plugin_list_data)) {
            foreach ($plugin_list_data as $key => $plugin_data) {
                $plugin_info_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_data['file'], false, false);
                if (!is_array($plugin_info_data) || !isset($plugin_info_data['Version'])) {
                    $response = new stdClass ();
                    $response->slug = $plugin_data['product_id'];
                    $transient->no_update[$plugin_data['file']] = $response;
                    return $transient;
                }
                $current_plugin_version = !empty($plugin_info_data['Version']) ? $plugin_info_data['Version'] : '1.0.0';
                $args = array(
                    'request' => 'pluginupdatecheck',
                    'plugin_name' => $plugin_data['file'],
                    'version' => $current_plugin_version,
                    'product_id' => $plugin_data['product_id'],
                    'file_id' => $plugin_data['file_id'],
                    'license_hash' => $plugin_data['license_hash'],
                    'url' => esc_url(home_url('/'))
                );
                $response = $this->request($args);
                if (false !== $response) {
                    if (isset($response->errors) && isset($response->errors->woo_updater_api_license_deactivated)) {
                        add_action('admin_notices', array($this, 'error_notice_for_deactivated_plugin'));
                    } else {
                        if (isset($response->icons)) {
                            $response->icons = (array) $response->icons;
                        }
                        if (isset($response->banners)) {
                            $response->banners = (array) $response->banners;
                        }
                        
                        if ( version_compare( $plugin_info_data['Version'], $response->new_version, '<' ) ) {
				$transient->response[ $plugin_data['file'] ] = $response;
				unset( $transient->no_update[ $plugin_data['file'] ] );
			} else {
				$transient->no_update[ $plugin_data['file'] ] = $response;
				unset( $transient->response[ $plugin_data['file'] ] );
			}
                    }
                } else {
                    $response = new stdClass ();
                    $response->slug = $plugin_data['product_id'];
                    $transient->no_update[$plugin_data['file']] = $response;
                }
            }
            return $transient;
        } else {
            return $transient;
        }
    }

// End update_check()

    /**
     * Display an error notice
     * @param  strin $message The message
     * @return void
     */
    public function error_notice_for_deactivated_plugin($message) {

        $plugins = get_plugins();

        $plugin_name = isset($plugins[$this->file]) ? $plugins[$this->file]['Name'] : $this->file;

        echo sprintf('<div id="message" class="error"><p>The license for the plugin %s has been deactivated. You can reactivate the license on your <a href="https://www.angelleye.com/my-account/my-licenses" target="_blank">dashboard</a>.</p></div>', $plugin_name);
    }

    /**
     * Check for the plugin's data against the remote server.
     *
     * @access public
     * @since  1.0.0
     * @return object $response
     */
    public function plugin_information($response, $action, $args) {
        $transient = get_site_transient('update_plugins');

        // Check if this plugins API is about this plugin
        if (!isset($args->slug) || ( $args->slug != $this->product_id )) {
            return $response;
        }

        // POST data to send to your API
        $args = array(
            'request' => 'plugininformation',
            'plugin_name' => $this->file,
            'version' => $transient->checked[$this->file],
            'product_id' => $args->slug,
            'file_id' => $this->file_id,
            'license_hash' => $this->license_hash,
            'url' => esc_url(home_url('/'))
        );

        // Send request for detailed information
        $response = $this->request($args);

        if (isset($response->sections) && !empty($response->sections)) {
            $response->sections = (array) $response->sections;
        }

        if (isset($response->compatibility) && !empty($response->compatibility)) {
            $response->compatibility = (array) $response->compatibility;
        }

        if (isset($response->tags) && !empty($response->tags)) {
            $response->tags = (array) $response->tags;
        }

        if (isset($response->contributors) && !empty($response->contributors)) {
            $response->contributors = (array) $response->contributors;
        }

        if (isset($response->compatibility) && count($response->compatibility) > 0) {
            foreach ($response->compatibility as $k => $v) {
                $response->compatibility[$k] = (array) $v;
            }
        }

        if (isset($response->icons)) {
            $response->icons = (array) $response->icons;
        }
        if (isset($response->banners)) {
            $response->banners = (array) $response->banners;
        }

        return $response;
    }

// End plugin_information()

    /**
     * Generic request helper.
     *
     * @access protected
     * @since  1.0.0
     * @param  array $args
     * @return object $response or boolean false
     */
    protected function request($args) {
        // Send request



        if (isset($args['action']) && !empty($args['action'])) {
            $this->api_url .= '&action=' . $args['action'];
        } else {
            if (isset($args['request']) && !empty($args['request'])) {
                $this->api_url .= '&action=' . $args['request'];
            }
        }

        $request = wp_remote_post($this->api_url, array(
            'timeout' => 60,
            'httpversion' => '1.1',
            'user-agent' => 'AngellEYE_Updater',
            'body' => $args,
            'sslverify' => false
        ));

        // Make sure the request was successful
        if (is_wp_error($request) || wp_remote_retrieve_response_code($request) != 200) {
            // Request failed
            return false;
        }
        // Read server response, which should be an object
        if ($request != '') {
            $response = json_decode(wp_remote_retrieve_body($request));
        } else {
            $response = false;
        }

        if (is_object($response) && isset($response->payload)) {
            return $response->payload;
        } else {
            // Unexpected response
            return false;
        }
    }

    public function angelleye_post_install($true, $hook_extra, $result) {
        global $wp_filesystem;
        if (isset($hook_extra['plugin']) && !empty($hook_extra['plugin'])) {
            $all_plugins = get_plugins();
            if (!empty($all_plugins) && isset($all_plugins[$hook_extra['plugin']])) {
                $plugins = $all_plugins[$hook_extra['plugin']];
                if (isset($plugins['Author']) && !empty($plugins['Author']) && trim($plugins['Author']) === 'Angell EYE') {
                    $plugin_path = explode('/', $hook_extra['plugin']);
                    $plugin_folder_path_php_file = $plugin_path[1];
                    if (is_plugin_active($hook_extra['plugin'])) {
                        $this->angelleye_make_auto_active_plugin($hook_extra['plugin'], $result['destination_name'] . '/' . $plugin_folder_path_php_file);
                    }
                    return $true;
                }
            }
        }
        return $true;
    }

    public function angelleye_make_auto_active_plugin($old_plugin_path, $new_plugin_path) {
        $active_plugins = get_option('active_plugins');
        foreach ($active_plugins as $key => $value) {
            if ($old_plugin_path == $value) {
                $active_plugins[$key] = $new_plugin_path;
            }
            update_option('active_plugins', $active_plugins);
        }
    }

}

// End Class
?>