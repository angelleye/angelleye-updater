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
        $this->version = '1.0.0';

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
        register_activation_hook($this->file, array($this, 'activation'));

        if (is_admin()) {
            // Load the self-updater.
            //require_once $this->plugin_path . 'includes/class-angell-eye-updater-self-updater.php';
            //$this->updater = new AngellEYE_Updater_Self_Updater( $file );
            // Load the admin.
            // Look for enabled updates across all themes (active or otherwise). If they are available, queue them.
            add_action('init', array($this, 'maybe_queue_theme_updates'), 1);

            // Get queued plugin updates - Run on init so themes are loaded as well as plugins.
            add_action('init', array($this, 'load_queued_updates'), 2);
        }

        $this->add_notice_unlicensed_product();

        add_filter('site_transient_' . 'update_plugins', array($this, 'change_update_information'));
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
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-angell-eye-updater-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-angell-eye-updater-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the Dashboard.
         */
        //require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-angell-eye-updater-admin-display.php';


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

        require_once $this->plugin_path . 'admin/class-angell-eye-updater-admin.php';
        $plugin_admin = new AngellEYE_Updater_Admin($file, $this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
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

    public function maybe_queue_theme_updates() {
        $themes = wp_get_themes();
        if (is_array($themes) && 0 < count($themes)) {
            foreach ($themes as $k => $v) {
                // Search for the text file.
                $file = $this->_maybe_find_theme_info_file($v);
                if (!is_wp_error($file)) {
                    $parsed = $this->_parse_theme_info_file($file);
                    if (!is_wp_error($parsed)) {
                        $this->add_product($parsed[2], $parsed[1], $parsed[0]); // 0: file, 1: file_id, 2: product_id.
                    }
                }
            }
        }
    }

// End maybe_queue_theme_updates()

    /**
     * Maybe find the theme_info.txt file.
     * @access  private
     * @since   1.0.0
     * @param   object $theme WP_Theme instance.
     * @return  object/string WP_Error object if not found, path to the file, if it exists.
     */
    private function _maybe_find_theme_info_file($theme) {
        $response = new WP_Error(404, __('Theme Information File Not Found.', 'angelleye-updater'));
        $txt_files = $theme->get_files('txt', 0);
        if (isset($txt_files['theme_info.txt'])) {
            $response = $txt_files['theme_info.txt'];
        }
        return $response;
    }

// End _maybe_find_theme_info_file()

    /**
     * Parse a given theme_info.txt file.
     * @access  private
     * @since   1.0.0
     * @param   string $file The path to the file to be parsed.
     * @return  object/array WP_Error object if the data is incorrect, array, if it is accurate.
     */
    private function _parse_theme_info_file($file) {
        $response = new WP_Error(500, __('Theme Information File is Inaccurate. Please try again.', 'angelleye-updater'));
        if (is_string($file) && file_exists($file)) {
            $contents = file_get_contents($file);
            $contents = explode("\n", $contents);
            // Sanity check on the parsed array.
            if (( 3 == count($contents) ) && stristr($contents[2], '/style.css')) {
                $response = $contents;
            }
        }
        return $response;
    }

// End _parse_theme_info_file()

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
        echo '<div class="error"><p>' . __('AngellEYE Updater must be network activated when in multisite environment.', 'angelleye-updater') . '</p></div>';
    }

// End admin_notice_require_network_activation()

    /**
     * Add action for queued products to display message for unlicensed products.
     * @access  public
     * @since   1.1.0
     * @return  void
     */
    public function add_notice_unlicensed_product() {
        global $angelleye_queued_updates;
        if (!is_array($angelleye_queued_updates) || count($angelleye_queued_updates) < 0)
            return;

        foreach ($angelleye_queued_updates as $key => $update) {
            add_action('in_plugin_update_message-' . $update->file, array($this, 'need_license_message'), 10, 2);
        }
    }

// End add_notice_unlicensed_product()

    /**
     * Message displayed if license not activated
     * @param  array $plugin_data
     * @param  object $r
     * @return void
     */
    public function need_license_message($plugin_data, $r) {
        if (empty($r->package)) {
            _e(' To enable updates for this AngellEYE product, please activate your license by visiting the Dashboard > AngellEYE Helper screen.', 'angelleye-updater');
        }
    }

// End need_license_message()

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

            $notice_text = __('To enable this update please activate your AngellEYE license by visiting the Dashboard > AngellEYE Helper screen.', 'angelleye-updater');

            foreach ($angelleye_queued_updates as $key => $value) {
                if (isset($transient->response[$value->file]) && isset($transient->response[$value->file]->package) && '' == $transient->response[$value->file]->package && ( FALSE === stristr($transient->response[$value->file]->upgrade_notice, $notice_text) )) {
                    $message = $notice_text . ' ' . $transient->response[$value->file]->upgrade_notice;
                    $transient->response[$value->file]->upgrade_notice = $message;
                }
            }
        }

        return $transient;
    }

// End change_update_information()
}
