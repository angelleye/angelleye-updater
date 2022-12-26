<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    AngellEYE_Updater
 * @subpackage AngellEYE_Updater/includes
 * @author     Angell EYE <service@angelleye.com>
 */
class AngellEYE_Updater {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      AngellEYE_Updater_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;
    public $angelleye_plugin_more_info_page;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    public $updater;
    public $admin;
    private $token = 'angelleye-updater';
    private $plugin_url;
    private $plugin_path;
    public $version;
    private $file;
    private $products;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the Dashboard and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct($file) {

        $this->plugin_name = 'angelleye-updater';
        if (defined('AU_PLUGIN_VERSION')) {
            $this->version = AU_PLUGIN_VERSION;
        } else {
            $this->version = '2.0.17';
        }
        $this->angelleye_plugin_more_info_page = array(
            'paypal-ipn-for-wordpress-forwarder' => array(
                'web_page' => 'https://www.angelleye.com/product/wordpress-paypal-ipn-forwarder/'
            ),
            'offers-for-woocommerce-wc-vendors' => array(
                'web_page' => 'https://www.angelleye.com/product/offers-for-woocommerce-wc-vendors/'
            ),
            'paypal-for-woocommerce-multi-account-management' => array(
                'web_page' => 'https://www.angelleye.com/product/paypal-woocommerce-multi-account-management/'
            ),
            'woo-paypal-plus' => array(
                'web_page' => 'https://www.angelleye.com/product/woocommerce-paypal-plus-plugin/'
            ),
            'paypal-ipn-for-wordpress-mailchimp' => array(
                'web_page' => 'https://www.angelleye.com/product/paypal-ipn-for-wordpress-paypal-mailchimp-plugin/'
            ),
            'offers-for-woocommerce' => array(
                'web_page' => 'https://www.angelleye.com/product/offers-for-woocommerce/'
            ),
            'paypal-ipn' => array(
                'web_page' => 'https://www.angelleye.com/product/paypal-ipn-wordpress/'
            ),
            'angelleye_paypal_divi' => array(
                'web_page' => 'https://www.angelleye.com/product/divi-paypal-module-plugin/'
            ),
            'paypal-for-woocommerce' => array(
                'web_page' => 'https://www.angelleye.com/product/woocommerce-paypal-plugin/'
            ),
            'woo-paypal-here' => array(
                'web_page' => 'https://www.angelleye.com/product/paypal-here-woocommerce-pos/'
            ),
            'angelleye-paypal-invoicing' => array(
                'web_page' => 'https://www.angelleye.com/product/wordpress-paypal-invoice-plugin/'
            ),
            'paypal-wp-button-manager' => array(
                'web_page' => 'https://www.angelleye.com/product/wordpress-paypal-button-manager/'
            ),
            'angelleye-updater' => array(
                'web_page' => 'https://www.angelleye.com/how-to-get-updates-angelleye-wordpress-plugins/'
            ),
            'angelleye-gravity-forms-braintree' => array(
                'web_page' => 'https://www.angelleye.com/product/gravity-forms-braintree-payments/'
            ),
            'angelleye-paypal-webhooks' => array(
                'web_page' => 'https://www.angelleye.com/product/paypal-webhooks-for-wordpress/'
            ),
            'angelleye-paypal-shipment-tracking-woocommerce' => array(
                'web_page' => 'https://www.angelleye.com/product/paypal-shipment-tracking-numbers-woocommerce/'
            ),
            'angelleye-paypal-woocommerce-credit-card-split' => array(
                'web_page' => 'https://www.angelleye.com/product/paypal-woocommerce-credit-card-split/'
            ),
            'offers-for-woocommerce-dokan' => array(
                'web_page' => 'https://www.angelleye.com/product/offers-for-woocommerce-dokan/'
            ),
        );

        $this->load_dependencies();
        $this->set_locale();


        // If multisite, plugin must be network activated. First make sure the is_plugin_active_for_network function exists
        if (is_multisite() && !is_network_admin()) {
            remove_action('admin_notices', 'angelleye_updater_notice'); // remove admin notices for plugins outside of network admin
            if (!function_exists('is_plugin_active_for_network'))
                require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
            if (!is_plugin_active_for_network(plugin_basename($file)))
                add_action('admin_notices', array($this, 'admin_notice_require_network_activation'));
            return;
        }

        $this->file = $file;
        $this->plugin_url = trailingslashit(plugins_url('', $plugin = $file));
        $this->plugin_path = trailingslashit(dirname($file));

        $this->products = array();

        $this->define_admin_hooks($file);

        // Run this on activation.
        //register_activation_hook($this->file, array($this, 'activation'));

        if (is_admin()) {
            add_action('init', array($this, 'load_queued_updates'), 2);
        }

        $this->angelleye_add_notice_unlicensed_product();

        add_filter('site_transient_' . 'update_plugins', array($this, 'change_update_information'), 0);
        add_action('http_api_curl', array($this, 'http_api_curl_angelleye_updater_add_curl_parameter'), 10, 3);
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - AngellEYE_Updater_Loader. Orchestrates the hooks of the plugin.
     * - AngellEYE_Updater_i18n. Defines internationalization functionality.
     * - AngellEYE_Updater_Admin. Defines all hooks for the dashboard.
     * - AngellEYE_Updater_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/angelleye-updater-function.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-angelleye-updater-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-angelleye-updater-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the Dashboard.
         */
        //require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-angelleye-updater-admin-display.php';
        
        $this->loader = new AngellEYE_Updater_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the AngellEYE_Updater_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new AngellEYE_Updater_i18n();
        $plugin_i18n->set_domain($this->get_plugin_name());

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the dashboard functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks($file) {

        require_once $this->plugin_path . 'admin/class-angelleye-updater-admin.php';
        $plugin_admin = new AngellEYE_Updater_Admin($file, $this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_init', $plugin_admin, 'angell_eye_updater_base_plug_active');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    AngellEYE_Updater_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * load_queued_updates function.
     *
     * @access public
     * @since 1.0.0
     * @return void
     */
    public function load_queued_updates() {
        global $angelleye_queued_updates;

        if (!empty($angelleye_queued_updates) && is_array($angelleye_queued_updates))
            foreach ($angelleye_queued_updates as $plugin)
                if (is_object($plugin) && !empty($plugin->file) && !empty($plugin->file_id) && !empty($plugin->product_id))
                    $this->add_product($plugin->file, $plugin->file_id, $plugin->product_id);
    }

// End load_queued_updates()

    /**
     * Add a product to await a license key for activation.
     *
     * Add a product into the array, to be processed with the other products.
     *
     * @since  1.0.0
     * @param string $file The base file of the product to be activated.
     * @param string $file_id The unique file ID of the product to be activated.
     * @return  void
     */
    public function add_product($file, $file_id, $product_id) {
        if ($file != '' && !isset($this->products[$file])) {
            $this->products[$file] = array('file_id' => $file_id, 'product_id' => $product_id);
        }
    }

// End add_product()

    /**
     * Remove a product from the available array of products.
     *
     * @since     1.0.0
     * @param     string $key The key to be removed.
     * @return    boolean
     */
    public function remove_product($file) {
        $response = false;
        if ($file != '' && in_array($file, array_keys($this->products))) {
            unset($this->products[$file]);
            $response = true;
        }
        return $response;
    }

// End remove_product()

    /**
     * Return an array of the available product keys.
     * @since  1.0.0
     * @return array Product keys.
     */
    public function get_products() {
        return (array) $this->products;
    }

// End get_products()

    /**
     * Display require network activation error.
     * @since  1.0.0
     * @return  void
     */
    public function admin_notice_require_network_activation() {
        echo '<div class="error"><p>' . __(AU_COMPANY_NAME.' Updater must be network activated when in multisite environment.', 'angelleye-updater') . '</p></div>';
    }

// End admin_notice_require_network_activation()

    /**
     * Add action for queued products to display message for unlicensed products.
     * @access  public
     * @since   1.1.0
     * @return  void
     */
    public function angelleye_add_notice_unlicensed_product() {
        global $angelleye_queued_updates;
       
        if (!is_array($angelleye_queued_updates) || count($angelleye_queued_updates) < 0)
            return;

        foreach ($angelleye_queued_updates as $key => $update) {
            add_action('in_plugin_update_message-' . $update->file, array($this, 'angelleye_need_license_message'), 10, 2);
        }
    }

// End angelleye_add_notice_unlicensed_product()

    /**
     * Message displayed if license not activated
     * @param  array $plugin_data
     * @param  object $r
     * @return void
     */
    public function angelleye_need_license_message($plugin_data, $r) {
        if (empty($r->package)) {
            $more_info = sprintf( '<a href="%s" target="_blank">%s</a>', isset($this->angelleye_plugin_more_info_page[$plugin_data['TextDomain']]['web_page']) ? $this->angelleye_plugin_more_info_page[$plugin_data['TextDomain']]['web_page'] : '' , __( 'More Info', 'angelleye-updater' ) );
            echo wp_kses_post( '<div class="angelleye-updater-plugin-upgrade-notice">' . __( 'To enable this update please activate your '.AU_COMPANY_NAME.' license by visiting the Dashboard > '.AU_COMPANY_NAME.' Helper screen. ', 'angelleye-updater' ) . $more_info . '</div>' );
        }
    }

// End angelleye_need_license_message()

    /**
     * Change the update information for unlicense AngellEYE products
     * @param  object $transient The update-plugins transient
     * @return object
     */
    public function change_update_information($transient) {
        //If we are on the update core page, change the update message for unlicensed products
        global $pagenow;
        if (( 'update-core.php' == $pagenow ) && $transient && isset($transient->response) && !isset($_GET['action'])) {

            global $angelleye_queued_updates;

            if (empty($angelleye_queued_updates))
                return $transient;

            $notice_text = __('To enable this update please activate your '.AU_COMPANY_NAME.' license by visiting the Dashboard > AngellEYE Helper screen.', 'angelleye-updater');

            foreach ($angelleye_queued_updates as $key => $value) {
                if (isset($transient->response[$value->file]) && isset($transient->response[$value->file]->package) && '' == $transient->response[$value->file]->package && ( FALSE === stristr($transient->response[$value->file]->upgrade_notice, $notice_text) )) {
                    $message = $notice_text . ' ' . $transient->response[$value->file]->upgrade_notice;
                    $transient->response[$value->file]->upgrade_notice = $message;
                }
            }
        }

        return $transient;
    }
    
    public function http_api_curl_angelleye_updater_add_curl_parameter($handle, $r, $url) {
        if ( strstr( $url, 'angelleye.com' ) ) {
            curl_setopt($handle, CURLOPT_SSLVERSION, 6);
        }
    }
}