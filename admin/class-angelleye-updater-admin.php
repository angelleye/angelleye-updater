<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * AngellEYE Updater Admin Class
 *
 * Admin class for the AngellEYE Updater.
 *
 * @package WordPress
 * @subpackage AngellEYE Updater
 * @category Core
 * @author Angell EYE <service@angelleye.com>
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * private $token
 * private $api
 * private $name
 * private $menu_label
 * private $page_slug
 * private $plugin_path
 * private $screens_path
 * private $classes_path
 *
 * private $installed_products
 * private $pending_products
 *
 * - __construct()
 * - register_settings_screen()
 * - settings_screen()
 * - get_activated_products()
 * - get_product_reference_list()
 * - get_detected_products()
 * - get_pending_products()
 * - activate_products()
 * - deactivate_product()
 * - load_updater_instances()
 */

class AngellEYE_Updater_Admin {

    private $token;
    private $api;
    private $name;
    private $menu_label;
    private $page_slug;
    private $plugin_path;
    private $plugin_url;
    private $screens_path;
    private $classes_path;
    private $assets_url;
    private $installed_products;
    private $pending_products;

    /**
     * Constructor.
     *
     * @access  public
     * @since    1.0.0
     * @return    void
     */
    public function __construct($file) {
        $this->token = 'angelleye-updater'; // Don't ever change this, as it will mess with the data stored of which products are activated, etc.
        // Load in the class to use for the admin screens.
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-angelleye-updater-screen.php';


        // Load the API.
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-angelleye-updater-api.php';

        $this->api = new AngellEYE_Updater_API();

        $this->name = __('The ' . AU_COMPANY_NAME . ' Helper', 'angelleye-updater');
        $this->menu_label = __(AU_COMPANY_NAME.' Helper', 'angelleye-updater');
        $this->page_slug = 'angelleye-helper';
        $this->plugin_path = trailingslashit(plugin_dir_path($file));
        $this->plugin_url = trailingslashit(plugin_dir_url($file));
        $this->screens_path = trailingslashit($this->plugin_path . 'screens');
        $this->classes_path = trailingslashit($this->plugin_path . 'includes');
        $this->assets_url = trailingslashit($this->plugin_url . 'admin');

        $this->installed_products = array();
        $this->pending_products = array();

        // Load the updaters.
        add_action('admin_init', array($this, 'load_updater_instances')); //$this->load_updater_instances();

        $menu_hook = is_multisite() ? 'network_admin_menu' : 'admin_menu';
        add_action($menu_hook, array($this, 'register_settings_screen'));

        // Display an admin notice, if there are Woo products, eligible for licenses, that are not activated.
        add_action('network_admin_notices', array($this, 'maybe_display_activation_notice'));
        add_action('admin_notices', array($this, 'maybe_display_activation_notice'));
        if (is_multisite() && !is_network_admin())
            remove_action('admin_notices', array($this, 'maybe_display_activation_notice'));

        // Process the 'Dismiss' link, if valid.
        add_action('admin_init', array($this, 'maybe_process_dismiss_link'));

        add_action('angelleye_updater_license_screen_before', array($this, 'ensure_keys_are_actually_active'));

        add_action('wp_ajax_angelleye_activate_license_keys', array($this, 'ajax_process_request'));
    }

// End __construct()

    /**
     * If the nonce is valid and the action is "angelleye-helper-dismiss", process the dismissal.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function maybe_process_dismiss_link() {
        if (isset($_GET['action']) && ( 'angelleye-helper-dismiss' == $_GET['action'] ) && isset($_GET['nonce']) && check_admin_referer('angelleye-helper-dismiss', 'nonce')) {
            update_site_option('angelleye_helper_dismiss_activation_notice', true);

            $redirect_url = remove_query_arg('action', remove_query_arg('nonce', $_SERVER['REQUEST_URI']));

            wp_safe_redirect($redirect_url);
            exit;
        }
    }

// End maybe_process_dismiss_link()

    /**
     * Display an admin notice, if there are licenses that are not yet activated.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function maybe_display_activation_notice() {
        if (isset($_GET['page']) && 'angelleye-helper' == $_GET['page'])
            return;
        if (!current_user_can('manage_options'))
            return; // Don't show the message if the user isn't an administrator.
        if (is_multisite() && !is_super_admin())
            return; // Don't show the message if on a multisite and the user isn't a super user.
        if (true == get_site_option('angelleye_helper_dismiss_activation_notice', false))
            return; // Don't show the message if the user dismissed it.

        $products = $this->get_detected_products();
        $has_inactive_products = false;
        if (0 < count($products)) {
            foreach ($products as $k => $v) {
                if (isset($v['product_status']) && 'inactive' == $v['product_status']) {
                    $has_inactive_products = true; // We know we have inactive product licenses, so break out of the loop.
                    break;
                }
            }

            if ($has_inactive_products) {
                $url = add_query_arg('page', 'angelleye-helper', network_admin_url('index.php'));
                $dismiss_url = add_query_arg('action', 'angelleye-helper-dismiss', add_query_arg('nonce', wp_create_nonce('angelleye-helper-dismiss')));
                echo '<div class="updated fade"><p class="alignleft">' . sprintf(__('%sYour %s products are almost ready.%s To get started, %sactivate your product licenses%s.', 'angelleye-updater'), '<strong>', AU_COMPANY_NAME,'</strong>', '<a href="' . esc_url($url) . '">', '</a>') . '</p><p class="alignright"><a href="' . esc_url($dismiss_url) . '">' . __('Dismiss', 'angelleye-updater') . '</a></p><div class="clear"></div></div>' . "\n";
            }
        }
    }

// End maybe_display_activation_notice()

   

    /**
     * Register the admin screen.
     *
     * @access public
     * @since   1.0.0
     * @return   void
     */
    public function register_settings_screen() {
        $hook = add_dashboard_page($this->name, $this->menu_label, 'manage_options', $this->page_slug, array($this, 'settings_screen'));

        add_action('load-' . $hook, array($this, 'process_request'));
        add_action('admin_print_styles-' . $hook, array($this, 'enqueue_styles'));
        add_action('admin_print_scripts-' . $hook, array($this, 'enqueue_scripts'));
    }

// End register_settings_screen()

    /**
     * Load the main management screen.
     *
     * @access public
     * @since   1.0.0
     * @return   void
     */
    public function settings_screen() {
        ?>
        <div id="welcome-panel" class="wrap about-wrap angelleye-updater-wrap">
            <h1><?php _e('Welcome to Angell EYE Updater', 'angelleye-updater'); ?></h1>

            <div class="about-text angelleye-helper-about-text">
                <?php
                _e('Use this tool to activate your premium extensions by '.AU_COMPANY_NAME.' and enable updates through the <a href="' . admin_url() . 'plugins.php">WordPress Plugins page</a>.', 'angelleye-updater');
                ?>
            </div>
        </div><!--/#welcome-panel .welcome-panel-->
        <?php
        AngellEYE_Updater_Screen::get_header();

        $screen = AngellEYE_Updater_Screen::get_current_screen();

        switch ($screen) {
            // Help screen.
            case 'help':
                do_action('angelleye_updater_help_screen_before');
                //$this->load_help_screen_boxes();
                require_once( $this->screens_path . 'screen-help.php' );
                do_action('angelleye_updater_help_screen_after');
                break;

            // Licenses screen.
            case 'license':
            default:
                if ($this->api->ping()) {
                    $this->installed_products = $this->get_detected_products();
                    $this->pending_products = $this->get_pending_products();

                    do_action('angelleye_updater_license_screen_before');
                    require_once( $this->screens_path . 'screen-manage.php' );
                    do_action('angelleye_updater_license_screen_after');
                } else {
                    do_action('angelleye_updater_api_unreachable_screen_before');
                    require_once( $this->screens_path . 'angelleye-api-unreachable.php' );
                    do_action('angelleye_updater_api_unreachable_screen_after');
                }
                break;
        }

        AngellEYE_Updater_Screen::get_footer();
    }

// End settings_screen()

    /**
     * Load the boxes for the "Help" screen.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function load_help_screen_boxes() {
        add_action('angelleye_helper_column_left', array($this, 'display_general_links'));
        add_action('angelleye_helper_column_left', array($this, 'display_sensei_links'));
        add_action('angelleye_helper_column_middle', array($this, 'display_angelleye_links'));
    }

// End load_help_screen_boxes()

    /**
     * Display rendered HTML markup containing general support links.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function display_general_links() {
       
    }

// End display_general_links()

    /**
     * Display rendered HTML markup containing WooCommerce support links.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function display_angelleye_links() {
       
    }


    /**
     * Display rendered HTML markup containing Sensei support links.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function display_sensei_links() {
       
    }

// End display_sensei_links()

    /**
     * Display rendered HTML markup containing a panic button.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function display_panic_button() {
        
    }

// End display_panic_button()

    /**
     * Generate the HTML for a given array of links.
     * @access  private
     * @since   1.0.0
     * @param   array  $links Links with the key as the URL and the value as the title.
     * @return  string Rendered HTML for the links.
     */
    private function _generate_link_list($links = array()) {
        if (0 >= count($links))
            return;
        $html = '';
        foreach ($links as $k => $v) {
            $html .= '<li><a href="' . esc_url(trailingslashit($k) . '?utm_source=helper') . '" title="' . esc_attr($v) . '">' . esc_html($v) . '</a></li>' . "\n";
        }

        return $html;
    }

// End _generate_link_list()

    /**
     * Returns the action value to use.
     * @access private
     * @since 1.0.0
     * @return string|bool Contains the string given in $_POST['action'] or $_GET['action'], or false if none provided
     */
    private function get_post_or_get_action($supported_actions) {
        if (isset($_POST['action']) && in_array($_POST['action'], $supported_actions))
            return $_POST['action'];

        if (isset($_GET['action']) && in_array($_GET['action'], $supported_actions))
            return $_GET['action'];

        return false;
    }

// End get_post_or_get_action()

    /**
     * Enqueue admin styles.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function enqueue_styles() {
        wp_enqueue_style('angelleye-updater-admin', esc_url($this->assets_url . 'css/angelleye-updater-admin.css'), array(), '1.0.0', 'all');
    }

// End enqueue_styles()

    /**
     * Enqueue admin scripts.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();
        wp_enqueue_script('post');
        wp_register_script('angelleye-updater-admin', $this->assets_url . 'js/angelleye-updater-admin.js', array('jquery'));

        // Only load script and localization on helper admin page.
        if ('dashboard_page_angelleye-helper' == $screen->id) {
            wp_enqueue_script('angelleye-updater-admin');
            $localization = array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'activate_license_nonce' => wp_create_nonce('activate-license-keys')
            );
            wp_localize_script('angelleye-updater-admin', 'WTHelper', $localization);
        }
    }

// End enqueue_scripts()

    /**
     * Process the action for the admin screen.
     * @since  1.0.0
     * @return  void
     */
    public function process_request() {
        $notices_hook = is_multisite() ? 'network_admin_notices' : 'admin_notices';
        add_action($notices_hook, array($this, 'admin_notices'));

        $supported_actions = array('activate-products', 'deactivation_request');

        $action = $this->get_post_or_get_action($supported_actions);

        if ($action && in_array($action, $supported_actions) && check_admin_referer('bulk-' . 'licenses')) {
            $response = false;
            $status = 'false';
            $type = $action;

            switch ($type) {
                case 'activate-products':
                    $license_keys = array();
                    if (isset($_POST['license_keys']) && 0 < count($_POST['license_keys'])) {
                        foreach ($_POST['license_keys'] as $k => $v) {
                            if ('' != $v) {
                                $license_keys[$k] = $v;
                            }
                        }
                    }

                    if (0 < count($license_keys)) {
                        $response = $this->activate_products($license_keys);
                    } else {
                        $response = false;
                        $type = 'no-license-keys';
                    }
                    break;

                case 'deactivation_request':
                    if (isset($_GET['filepath']) && ( '' != $_GET['filepath'] )) {
                        $response = $this->deactivate_product($_GET['filepath']);
                    }
                    break;

                default:
                    break;
            }

            if ($response == true) {
                $status = 'true';
            }

            wp_safe_redirect(add_query_arg('type', urlencode($type), add_query_arg('status', urlencode($status), add_query_arg('page', urlencode($this->page_slug), network_admin_url('index.php')))));
            exit;
        }
    }

// End process_request()

    /**
     * Process Ajax license activation requests
     * @since 1.0.0
     * @return void
     */
    public function ajax_process_request() {
        if (isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'activate-license-keys') && isset($_POST['license_data']) && !empty($_POST['license_data'])) {
            $license_keys = array();
            foreach ($_POST['license_data'] as $license_data) {
                if ('' != $license_data['key']) {
                    $license_keys[$license_data['name']] = $license_data['key'];
                }
            }
            if (0 < count($license_keys)) {
                $response = $this->activate_products($license_keys);
            }
            if ($response == true) {
                $request_errors = $this->api->get_error_log();
                if (0 >= count($request_errors)) {
                    $return = '<div class="updated true fade">' . "\n";
                    $return .= wpautop(__('Products activated successfully.', 'angelleye-updater'));
                    $return .= '</div>' . "\n";
                    $return_json = array('success' => 'true', 'message' => $return, 'url' => add_query_arg(array('page' => 'angelleye-helper', 'status' => 'true', 'type' => 'activate-products'), admin_url('index.php')));
                } else {
                    $return = '<div class="error fade">' . "\n";
                    $return .= wpautop(__('There was an error and not all products were activated.', 'angelleye-updater'));
                    $return .= '</div>' . "\n";

                    $message = '';
                    foreach ($request_errors as $k => $v) {
                        $message .= wpautop($v);
                    }

                    $return .= '<div class="error fade">' . "\n";
                    $return .= make_clickable($message);
                    $return .= '</div>' . "\n";

                    $return_json = array('success' => 'false', 'message' => $return);

                    // Clear the error log.
                    $this->api->clear_error_log();
                }
            } else {
                $return = '<div class="error fade">' . "\n";
                $return .= wpautop(__('No license keys were specified for activation.', 'angelleye-updater'));
                $return .= '</div>' . "\n";
                $return_json = array('success' => 'false', 'message' => $return);
            }
            echo json_encode($return_json);
        }
        die();
    }

    /**
     * Display admin notices.
     * @since  1.0.0
     * @return  void
     */
    public function admin_notices() {
        $message = '';
        $response = '';

        if (isset($_GET['status']) && in_array($_GET['status'], array('true', 'false')) && isset($_GET['type'])) {
            $classes = array('true' => 'updated', 'false' => 'error');

            $request_errors = $this->api->get_error_log();

            switch ($_GET['type']) {
                case 'no-license-keys':
                    $message = __('No license keys were specified for activation.', 'angelleye-updater');
                    break;

                case 'deactivation_request':
                    if ('true' == $_GET['status'] && ( 0 >= count($request_errors) )) {
                        $message = __('Product deactivated successfully.', 'angelleye-updater');
                    } else {
                        $message = __('There was an error while deactivating the product.', 'angelleye-updater');
                    }
                    break;

                default:

                    if ('true' == $_GET['status'] && ( 0 >= count($request_errors) )) {
                        $message = __('Products activated successfully.', 'angelleye-updater');
                    } else {
                        $message = __('There was an error and not all products were activated.', 'angelleye-updater');
                    }
                    break;
            }

            $response = '<div class="' . esc_attr($classes[$_GET['status']]) . ' fade">' . "\n";
            $response .= wpautop($message);
            $response .= '</div>' . "\n";

            // Cater for API request error logs.
            if (is_array($request_errors) && ( 0 < count($request_errors) )) {
                $message = '';

                foreach ($request_errors as $k => $v) {
                    $message .= wpautop($v);
                }

                $response .= '<div class="error fade">' . "\n";
                $response .= make_clickable($message);
                $response .= '</div>' . "\n";

                // Clear the error log.
                $this->api->clear_error_log();
            }

            if ('' != $response) {
                echo $response;
            }
        }
    }

// End admin_notices()

    /**
     * Detect which products have been activated.
     *
     * @access public
     * @since   1.0.0
     * @return   void
     */
    protected function get_activated_products() {
        $response = array();

        $response = get_option($this->token . '-activated', array());

        if (!is_array($response))
            $response = array();

        return $response;
    }

// End get_activated_products()

    /**
     * Get a list of products from Angell EYE.
     *
     * @access public
     * @since   1.0.0
     * @return   void
     */
    protected function get_product_reference_list() {
        global $angeleye_updater;
        $response = array();
        $response = $angeleye_updater->get_products();
        return $response;
    }

// End get_product_reference_list()

    /**
     * Get a list of Angell EYE products found on this installation.
     *
     * @access public
     * @since   1.0.0
     * @return   void
     */
    protected function get_detected_products() {
        $response = array();
        $products = get_plugins();

        if (is_array($products) && ( 0 < count($products) )) {
            $reference_list = $this->get_product_reference_list();
            $activated_products = $this->get_activated_products();
            if (is_array($reference_list) && ( 0 < count($reference_list) )) {
                foreach ($products as $k => $v) {
                    if (in_array($k, array_keys($reference_list))) {
                        $status = 'inactive';
                        if (in_array($k, array_keys($activated_products))) {
                            $status = 'active';
                        }
                        if( isset($activated_products[$k][2]) && !empty($activated_products[$k][2]) ) {
                            $license_key_display = $activated_products[$k][2];
                        } else {
                            $license_key_display = '';
                        }
                        $response[$k] = array('product_name' => $v['Name'], 'product_version' => $v['Version'], 'file_id' => $reference_list[$k]['file_id'], 'product_id' => $reference_list[$k]['product_id'], 'product_status' => $status, 'product_file_path' => $k, 'license_key' => $license_key_display);
                    }
                }
            }
        }

        return $response;
    }

// End get_detected_products()

    /**
     * Get an array of products that haven't yet been activated.
     *
     * @access public
     * @since   1.0.0
     * @return  array Products awaiting activation.
     */
    protected function get_pending_products() {
        $response = array();

        $products = $this->installed_products;

        if (is_array($products) && ( 0 < count($products) )) {
            $activated_products = $this->get_activated_products();

            if (is_array($activated_products) && ( 0 <= count($activated_products) )) {
                foreach ($products as $k => $v) {
                    if (isset($v['product_key']) && !in_array($v['product_key'], $activated_products)) {
                        $response[$k] = array('product_name' => $v['product_name']);
                    }
                }
            }
        }

        return $response;
    }

// End get_pending_products()

    /**
     * Activate a given array of products.
     *
     * @since    1.0.0
     * @param    array   $products  Array of products ( filepath => key )
     * @return boolean
     */
    protected function activate_products($products) {
        $response = false;
        if (!is_array($products) || ( 0 >= count($products) )) {
            return false;
        } // Get out if we have incorrect data.

        $key = $this->token . '-activated';
        $has_update = false;
        $already_active = $this->get_activated_products();
        $product_keys = $this->get_product_reference_list();

        foreach ($products as $k => $v) {
            if (!in_array($v, $product_keys)) {

                // Perform API "activation" request.
                $activate = $this->api->activate($products[$k], $product_keys[$k]['product_id'], $k);

                if (true == $activate) {
                    // key: base file, 0: product id, 1: file_id, 2: hashed license.
                    $already_active[$k] = array($product_keys[$k]['product_id'], $product_keys[$k]['file_id'], $products[$k]);
                    $has_update = true;
                }
            }
        }

        // Store the error log.
        $this->api->store_error_log();

        if ($has_update) {
            $response = update_option($key, $already_active);
        } else {
            $response = true; // We got through successfully, and the supplied keys are already active.
        }

        return $response;
    }

// End activate_products()

    /**
     * Deactivate a given product key.
     * @since    1.0.0
     * @param    string $filename File name of the to deactivate plugin licence
     * @param    bool $local_only Deactivate the product locally without pinging angelleye.com.
     * @return   boolean          Whether or not the deactivation was successful.
     */
    protected function deactivate_product($filename, $local_only = false) {
        $response = false;
        $already_active = $this->get_activated_products();

        if (0 < count($already_active)) {
            $deactivated = true;

            if (isset($already_active[$filename][0])) {
                $key = $already_active[$filename][2];

                if (false == $local_only) {
                    $deactivated = $this->api->deactivate($key);
                }
            }

            if ($deactivated) {
                unset($already_active[$filename]);
                $response = update_option($this->token . '-activated', $already_active);
            } else {
                $this->api->store_error_log();
            }
        }

        return $response;
    }

// End deactivate_product()

    /**
     * Load an instance of the updater class for each activated Angell EYE Product.
     * @access public
     * @since  1.0.0
     * @return void
     */
    public function load_updater_instances() {
        $products = $this->get_detected_products();
        $activated_products = $this->get_activated_products();
        if (0 < count($products)) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-angelleye-updater-update-checker.php';
            foreach ($products as $k => $v) {
                if (isset($v['product_id']) && isset($v['file_id'])) {
                    $license_hash = isset($activated_products[$k][2]) ? $activated_products[$k][2] : '';
                    if (strpos($k, 'style.css')) {
                    } else {
                        new AngellEYE_Updater_Update_Checker($k, $v['product_id'], $v['file_id'], $license_hash);
                    }
                }
            }
        }
    }

// End load_updater_instances()

    /**
     * Run checks against the API to ensure the product keys are actually active on angelleye.com. If not, deactivate them locally as well.
     * @access public
     * @since  1.0.0
     * @return void
     */
    public function ensure_keys_are_actually_active() {
        $products = (array) $this->get_activated_products();

        if (0 < count($products)) {
            foreach ($products as $k => $v) {
                $status = $this->api->product_active_status_check($k, $v[0], $v[1], $v[2]);
                if (false == $status) {
                    $this->deactivate_product($k, true);
                }
            }
        }
    }

    /**
     *  angell_eye_activation_base_plug_active function used for check base plugin active or not
     *  @since    1.0.0
     */
    public function angell_eye_updater_base_plug_active() {

        if (!defined('PIW_PLUGIN_BASENAME')) {
            if (!in_array(@$_GET['action'], array('activate-plugin', 'upgrade-plugin', 'activate', 'do-plugin-upgrade')) && is_plugin_active(AU_PLUGIN_BASENAME)) {
                deactivate_plugins(AU_PLUGIN_BASENAME);
            }
        }
    }

// End ensure_keys_are_actually_active()
}

// End Class
?>