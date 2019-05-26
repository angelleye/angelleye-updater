<?php

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class AngellEYE_Updater_Licenses_Table extends WP_List_Table {

    public $per_page = 100;
    public $data;
    
    public $angelleye_plugin_more_info_page;

    /**
     * Constructor.
     * @since  1.0.0
     */
    public function __construct($args = array()) {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'license', //singular name of the listed records
            'plural' => 'licenses', //plural name of the listed records
            'ajax' => false        //does this table support ajax?
        ));
        $status = 'all';

        $page = $this->get_pagenum();

        $this->data = array();

        $this->angelleye_plugin_more_info_page = array(
            'paypal-ipn-for-wordpress-forwarder' => array(
                'web_page' => 'https://www.angelleye.com/product/wordpress-paypal-ipn-forwarder/'
            ),
            'offers-for-woocommerce-wc-vendors' => array(
                'web_page' => 'https://www.angelleye.com/product/offers-for-woocommerce-wc-vendors/'
            ),
            'woo-paypal-ratenzahlung' => array(
                'web_page' => 'https://www.angelleye.com/product/paypal-ratenzahlung-for-woocommerce/'
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
            'angelleye-paypal-for-divi' => array(
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
            'paypal-security' => array(
                'web_page' => 'https://www.angelleye.com/product/wordpress-paypal-security/'
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
        );

        // Make sure this file is loaded, so we have access to plugins_api(), etc.
        require_once( ABSPATH . '/wp-admin/includes/plugin-install.php' );

        parent::__construct($args);
    }

// End __construct()
    // End __construct()

    /**
     * Text to display if no items are present.
     * @since  1.0.0
     * @return  void
     */
    public function no_items() {
        echo wpautop(__('No ' . AU_COMPANY_NAME . ' products found.', 'angelleye-updater'));
    }

    // End no_items(0)

    /**
     * The content of each column.
     * @param  array $item         The current item in the list.
     * @param  string $column_name The key of the current column.
     * @since  1.0.0
     * @return string              Output for the current column.
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'product':
            case 'product_status':
            case 'product_version':
                return $item[$column_name];
                break;
        }
    }

    // End column_default()

    /**
     * Retrieve an array of sortable columns.
     * @since  1.0.0
     * @return array
     */
    public function get_sortable_columns() {
        return array();
    }

    // End get_sortable_columns()

    /**
     * Retrieve an array of columns for the list table.
     * @since  1.0.0
     * @return array Key => Value pairs.
     */
    public function get_columns() {
        $columns = array(
            'product_name' => __('Product', 'angelleye-updater'),
            'product_version' => __('Version', 'angelleye-updater'),
            'license_key' => __('License Key', 'angelleye-updater'),
            'product_status' => __('Action', 'angelleye-updater'),
            'plugin_status' => __('Status', 'angelleye-updater'),
        );
        return $columns;
    }

    // End get_columns()

    /**
     * Content for the "product_name" column.
     * @param  array  $item The current item.
     * @since  1.0.0
     * @return string       The content of this column.
     */
    public function column_product_name($item) {
        return wpautop('<strong>' . $item['product_name'] . '</strong>');
    }

    // End column_product_name()

    /**
     * Content for the "license_key" column.
     * @param  array  $item The current item.
     * @since  1.0.0
     * @return string       The content of this column.
     */
    public function column_license_key($item) {
        if (isset($item['license_key']) && !empty($item['license_key'])) {
            return wpautop($item['license_key']);
        } else {
            if ($item['is_paid'] == true) {
                $response = '';
                $response .= '<input name="license_keys[' . esc_attr($item['product_file_path']) . ']" id="license_keys-' . esc_attr($item['product_file_path']) . '" type="text" value="" size="37" aria-required="true" placeholder="' . esc_attr(__('Enter license key here', 'angelleye-updater')) . '" />' . "\n";
                return $response;
            } else {
                return AU_FREE_LICENSE_KEY_TEXT;
            }
        }
    }

    // End column_license_key()

    /**
     * Content for the "product_version" column.
     * @param  array  $item The current item.
     * @since  1.0.0
     * @return string       The content of this column.
     */
    public function column_product_version($item) {
        return wpautop($item['product_version']);
    }

    // End column_product_version()

    /**
     * Content for the "status" column.
     * @param  array  $item The current item.
     * @since  1.0.0
     * @return string       The content of this column.
     */
    public function column_product_status($item) {
        $response = '';
        if ('active' == $item['product_status']) {
            $deactivate_url = wp_nonce_url(add_query_arg('action', 'deactivation_request', add_query_arg('filepath', $item['product_file_path'], add_query_arg('page', 'angelleye-helper', network_admin_url('index.php')))), 'bulk-licenses');
            $response = '<a href="' . esc_url($deactivate_url) . '">' . __('Deactivate', 'angelleye-updater') . '</a>' . "\n";
            return $response;
        } else {
            return AU_EMPTY_ACTION_TEXT;
        }
    }

    public function column_plugin_status($item) {
        if ($item['plugin_status'] == 'Not Installed') {
            $more_info = sprintf('<a class="" href="%s" target="_blank">%s</a>', isset($this->angelleye_plugin_more_info_page[$item['product_id']]['web_page']) ? $this->angelleye_plugin_more_info_page[$item['product_id']]['web_page'] : '' , __('More Info', 'angelleye-updater'));
            return '<span class="red-font">' . $item['plugin_status'] . '</span>' . str_repeat('&nbsp;', 2) . $more_info;
        } else {
            return '<span class="green-font">' . $item['plugin_status'] . '</span>';
        }
    }

    // End column_status()

    /**
     * Retrieve an array of possible bulk actions.
     * @since  1.0.0
     * @return array
     */
    public function get_bulk_actions() {
        $actions = array();
        return $actions;
    }

    // End get_bulk_actions()

    /**
     * Prepare an array of items to be listed.
     * @since  1.0.0
     * @return array Prepared items.
     */
    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $total_items = count($this->data);

        // only ncessary because we have sample data
        $this->found_data = $this->data;

        $this->set_pagination_args(array(
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page' => $total_items                   //WE have to determine how many items to show on a page
        ));
        $this->items = $this->data;
    }

    // End prepare_items()
}

// End Class
?>