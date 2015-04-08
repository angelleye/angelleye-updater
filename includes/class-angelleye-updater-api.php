<?php

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * AngellEYE Updater API Class
 *
 * API class for the AngellEYE Updater.
 *
 * @package WordPress
 * @subpackage AngellEYE Updater
 * @category Core
 * @author AngellEYE
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * private $token
 * private $api_url
 * private $errors
 *
 * - __construct()
 * - activate()
 * - deactivate()
 * - check()
 * - request()
 * - log_request_error()
 * - store_error_log()
 * - get_error_log()
 * - clear_error_log()
 */

class AngellEYE_Updater_API {

    private $token;
    private $api_url;
    private $products_api_url;
    private $errors;

    public function __construct() {
        $this->token = 'angelleye-updater';
        $this->api_url = AU_WEBSITE_URL . '?AngellEYE_Activation';
        $this->products_api_url = AU_WEBSITE_URL . 'wc-api/angelleye-installer-api';
        $this->errors = array();
    }

// End __construct()

    /**
     * Activate a given license key for this installation.
     * @since    1.0.0
     * @param   string $key 		 	The license key to be activated.
     * @param   string $product_id	 	Product ID to be activated.
     * @return boolean      			Whether or not the activation was successful.
     */
    public function activate($key, $product_id, $plugin_file = '') {
        $response = false;

        //Ensure we have a correct product id.
        $product_id = trim($product_id);
//        if (!is_numeric($product_id)) {
//            $plugins = get_plugins();
//            $plugin_name = isset($plugins[$plugin_file]['Name']) ? $plugins[$plugin_file]['Name'] : $plugin_file;
//            $error = '<strong>There seems to be incorrect data for the plugin ' . $plugin_name . '. Please contact <a href="https://www.angelleye.com/support/" target="_blank">' . AU_COMPANY_NAME . ' Support</a> with this message.</strong>';
//            $this->log_request_error($error);
//            return false;
//        }

        $request = $this->request('activation_request', array('license_key' => $key, 'product_id' => $product_id, 'domain_name' => esc_url(home_url('/'))));

        return !isset($request->error);
    }

// End activate()

    /**
     * Deactivate a given license key for this installation.
     * @since    1.0.0
     * @param   string $key  The license key to be deactivated.
     * @return boolean      Whether or not the deactivation was successful.
     */
    public function deactivate($key) {
        $response = false;

        $request = $this->request('deactivation_request', array('license_key' => $key, 'domain_name' => esc_url(home_url('/'))));

        return !isset($request->error);
    }

// End deactivate()

    /**
     * Check if the license key is valid.
     * @since    1.0.0
     * @param   string $key The license key to be validated.
     * @return boolean      Whether or not the license key is valid.
     */
    public function check($key) {
        $response = false;

        $request = $this->request('check', array('license_key' => $key));

        return !isset($request->error);
    }

// End check()

    /**
     * Check if the API is up and reachable.
     * @since    1.0.0
     * @return boolean Whether or not the API is up and reachable.
     */
    public function ping() {
        $response = false;

        $request = $this->request('pingback');

        return isset($request->success);
    }

// End ping()

    /**
     * Check if a product license key is actually active for the current website.
     * @access   public
     * @since    1.0.0
     * @return   boolean Whether or not the given key is actually active for the current website.
     */
    public function product_active_status_check($file, $product_id, $file_id, $license_hash) {
        $response = true;

        $request_type = 'pluginupdatecheck';
        $name_label = 'plugin_name';
        if (strpos($file, 'style.css')) {
            $request_type = 'themeupdatecheck';
            $name_label = 'theme_name';
            $file = str_replace('/style.css', '', $file);
        }

        $args = array(
            $name_label => $file,
            'product_id' => $product_id,
            'version' => '1.0.0',
            'file_id' => $file_id,
            'license_hash' => $license_hash,
            'url' => esc_url(home_url('/'))
        );

        // Send request checking for an update
        $request = $this->request($request_type, $args, 'POST');

        // If request is false, don't alter the transient
        if (false !== $request) {
            if (isset($request->payload->errors->woo_updater_api_license_deactivated)) {
                $response = false;
            } else {
                if (isset($request->error) && !empty($request->error)) {
                    $response = false;
                } else {
                    $response = true;
                }
            }
        }
        return (bool) $response;
    }

// End product_active_status_check()

    /**
     * Make a request to the AngellEYE API.
     *
     * @access private
     * @since 1.0.0
     * @param string $endpoint (must include / prefix)
     * @param array $params
     * @return array $data
     */
    private function request($endpoint = 'check', $params = array(), $method = 'post') {
        $url = $this->api_url;

        $supported_methods = array('check', 'activation', 'deactivation', 'pingback', 'pluginupdatecheck');
        $supported_params = array('license_key', 'file_id', 'product_id', 'domain_name', 'license_hash', 'plugin_name', 'theme_name', 'version');

        $defaults = array(
            'method' => strtoupper($method),
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array('user-agent' => 'AngellEYEUpdater/1.0.0'),
            'cookies' => array(),
            'ssl_verify' => false,
            'user-agent' => 'AngellEYE Updater; http://www.angelleye.com'
        );

        if ('GET' == strtoupper($method)) {
            if (0 < count($params)) {
                foreach ($params as $k => $v) {
                    if (in_array($k, $supported_params)) {
                        $url = add_query_arg($k, $v, $url);
                    }
                }
            }

            if (in_array($endpoint, $supported_methods)) {
                $url = add_query_arg('request', $endpoint, $url);
            }

            // Pass if this is a network install on all requests
            $url = add_query_arg('network', is_multisite() ? 1 : 0, $url);
        } else {
            if (is_multisite()) {
                $params['network'] = 1;
            } else {
                $params['network'] = 0;
            }

            if (in_array($endpoint, $supported_methods)) {
                $params['request'] = $endpoint;
            }


            // Add the 'body' parameter if using a POST method. Not required if using a GET method.
            $defaults['body'] = $params;
        }

        // Set up a filter on our default arguments. If any arguments are removed by the filter, replace them with the default value.
        $args = wp_parse_args((array) apply_filters('angelleye_updater_request_args', $defaults, $endpoint, $params, $method), $defaults);

        if (isset($endpoint) && !empty($endpoint)) {
            $url .= '&action=' . $endpoint;
        }

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            $data = new StdClass;
            $data->error = __(AU_COMPANY_NAME . ' Request Error', 'angelleye-updater');
        } else {
            $data = $response['body'];
            $data = json_decode($data);
        }

        // Store errors in a transient, to be cleared on each request.
        if (isset($data->error) && ( '' != $data->error )) {
            $error = esc_html($data->error);
            $error = '<strong>' . $error . '</strong>';
            if (isset($data->additional_info)) {
                $error .= '<br /><br />' . esc_html($data->additional_info);
            }
            $this->log_request_error($error);
        } elseif (empty($data)) {
            $error = '<strong>' . __('There was an error making your request, please try again.', 'angelleye-updater') . '</strong>';
            $this->log_request_error($error);
        }

        return $data;
    }

// End request()

    /**
     * Log an error from an API request.
     *
     * @access private
     * @since 1.0.0
     * @param string $error
     */
    public function log_request_error($error) {
        $this->errors[] = $error;
    }

// End log_request_error()

    /**
     * Store logged errors in a temporary transient, such that they survive a page load.
     * @since  1.0.0
     * @return  void
     */
    public function store_error_log() {
        set_transient($this->token . '-request-error', $this->errors);
    }

// End store_error_log()

    /**
     * Get the current error log.
     * @since  1.0.0
     * @return  void
     */
    public function get_error_log() {
        return get_transient($this->token . '-request-error');
    }

// End get_error_log()

    /**
     * Clear the current error log.
     * @since  1.0.0
     * @return  void
     */
    public function clear_error_log() {
        return delete_transient($this->token . '-request-error');
    }

// End clear_error_log()
}

// End Class
?>
