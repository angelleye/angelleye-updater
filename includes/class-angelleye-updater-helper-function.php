<?php

defined('ABSPATH') || exit;

class AngellEYE_Updater_Helper_Function {

    private $token;
    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->token = 'angelleye-updater';
    }

    public function load_updater_instances() {
        $plugin_list = array();
        $products = $this->get_detected_products();
        $all_plugins = get_plugins();
        if (!empty($all_plugins)) {
            foreach ($all_plugins as $key => $plugins) {
                if (isset($plugins['Author']) && !empty($plugins['Author']) && trim($plugins['Author']) === 'Angell EYE') {
                    $products[$key] = array('file_id' => '999', 'product_id' => $plugins['TextDomain']);
                }
            }
        }
        $activated_products = $this->get_activated_products();
        if (0 < count($products)) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-angelleye-updater-update-checker.php';
            foreach ($products as $k => $v) {
                if (isset($v['product_id']) && isset($v['file_id'])) {
                    $license_hash = isset($activated_products[$k][2]) ? $activated_products[$k][2] : '';
                    if (!empty($license_hash)) {
                        $v['file_id'] = '101';
                    }
                    if (strpos($k, 'style.css')) {
                        
                    } else {
                        //new AngellEYE_Updater_Update_Checker($k, $v['product_id'], $v['file_id'], $license_hash);
                        $plugin_list[$k] = array(
                            'file' => $k,
                            'product_id' => $v['product_id'],
                            'file_id' => $v['file_id'],
                            'license_hash' => $license_hash
                        );
                    }
                }
            }
            return $plugin_list;
        }
    }

    public function get_detected_products() {
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
                        if (isset($activated_products[$k][2]) && !empty($activated_products[$k][2])) {
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

    public function get_activated_products() {
        $response = array();
        $response = get_option($this->token . '-activated', array());
        $exclude_key = 'woo-paypal-ratenzahlung/woo-paypal-ratenzahlung.php';
        if (isset($response) && isset($response[$exclude_key])) {
            unset($response[$exclude_key]);
        }
        if (!is_array($response)) {
            $response = array();
        }
        return $response;
    }

    public function get_product_reference_list() {
        global $angeleye_updater;
        $response = array();
        $response = $angeleye_updater->get_products();

        return $response;
    }

}
