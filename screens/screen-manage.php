<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly    ?>

<div id="col-container" class="about-wrap">
    <div class="ag-helper">
        <a class="button button-update" href="<?php echo esc_url( $refresh_url ); ?>"><span class="dashicons dashicons-image-rotate"></span> <?php _e( 'Update', 'woocommerce' ); ?></a>
    </div>
   
    <div class="col-wrap">
        <form id="activate-products" method="post" action="" class="validate">
            <input type="hidden" name="action" value="activate-products" />
            <input type="hidden" name="page" value="<?php echo esc_attr($this->page_slug); ?>" />
            <?php
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-angelleye-updater-licenses-table.php';
            $this->list_table = new AngellEYE_Updater_Licenses_Table();
            $this->list_table->data = $this->angelleye_get_plugin_list();
            $this->list_table->prepare_items();
            $this->list_table->display();
            wp_nonce_field( 'angelleye-activate-license', 'angelleye-helper-nonce' ); 
            submit_button(__('Activate Products', 'angelleye-updater'), 'button-primary');
            ?>
        </form>
    </div><!--/.col-wrap-->
</div><!--/#col-container-->