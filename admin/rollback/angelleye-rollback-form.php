<?php
if (isset($_GET['type']) && !isset($_GET['plugin_file'])) {
    wp_die(__('WP Rollback is missing necessary parameters to continue. Please contact support.', 'angelleye-updater'));
}
$plugin_rollback = $_GET['type'] == 'plugin' ? true : false;
$plugins = get_plugins();
?>
<div class="wrap">
    <div class="angelleye-content-wrap">
        <h1>
            <?php _e('Angell EYE Updater', 'angelleye-updater'); ?>
        </h1>
        <p><?php echo apply_filters('angelleye_rollback_description', sprintf(__('Please select which %1$s version you would like to rollback to from the releases listed below. You currently have version %2$s installed of %3$s.', 'angelleye-updater'), '<span class="type">' . __('plugin', 'angelleye-updater') . '</span>', '<span class="current-version">' . esc_html($args['current_version']) . '</span>', '<span class="rollback-name">' . esc_html($args['rollback_name']) . '</span>')); ?></p>
        <div class="angelleye-changelog"></div>
    </div>
    <?php
    if (isset($args['plugin_file']) && in_array($args['plugin_file'], array_keys($plugins))) {
        
    } else {
        wp_die(__('Oh no! We\'re missing required rollback query strings. Please contact support so we can check this bug out and squash it!', 'angelleye-updater'));
    }
    ?>
    <form name="check_for_rollbacks" class="rollback-form" action="<?php echo admin_url('/index.php'); ?>">
        <div class="angelleye-versions-wrap">
            <?php
            echo $versions_html;
            ?>
        </div>
        <div class="wpr-submit-wrap">
            <input type="submit" value="<?php _e('Rollback', 'angelleye-updater'); ?>" class="button-primary" />
            <input type="button" value="<?php _e('Cancel', 'angelleye-updater'); ?>" class="button" onclick="location.href = '<?php echo wp_get_referer(); ?>';" />
        </div>
        <input type="hidden" name="page" value="angelleye-rollback">
        <input type="hidden" name="plugin_file" value="<?php echo esc_attr($args['plugin_file']); ?>">
        <input type="hidden" name="plugin_slug" value="<?php echo esc_attr($args['plugin_slug']); ?>">
        <input type="hidden" name="rollback_name" value="<?php echo esc_attr($args['rollback_name']); ?>">
        <input type="hidden" name="installed_version" value="<?php echo esc_attr($args['current_version']); ?>">
        <?php wp_nonce_field('angelleye_rollback_nonce'); ?>
    </form>
</div>