<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly    ?>

<div id="col-container" class="about-wrap">
    <?php
    //echo '<div class="updated">' . wpautop(sprintf(__('See below for a list of the AngellEYE products in use on %s. You can %s, as well as our %s on how this works. %s', 'angelleye-updater'), get_bloginfo('name'), '<a href="https://www.angelleye.com/my-account/my-licenses">view your licenses here</a>', '<a href="http://docs.angelleye.com/document/angelleye-helper/?utm_source=helper">documentation</a>', '&nbsp;&nbsp;<a href="' . esc_url(admin_url('update-core.php')) . '" class="button">' . __('Check for Updates', 'angelleye-updater') . '</a>')) . '</div>' . "\n";
    ?>
    <div class="col-wrap">
        <form id="activate-products" method="post" action="" class="validate">
            <input type="hidden" name="action" value="activate-products" />
            <input type="hidden" name="page" value="<?php echo esc_attr($this->page_slug); ?>" />
            <?php
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-angelleye-updater-licenses-table.php';
            $this->list_table = new AngellEYE_Updater_Licenses_Table();
            $this->list_table->data = $this->get_detected_products();
            $this->list_table->prepare_items();
            $this->list_table->display();
            submit_button(__('Activate Products', 'angelleye-updater'), 'button-primary');
            ?>
        </form>
    </div><!--/.col-wrap-->
</div><!--/#col-container-->