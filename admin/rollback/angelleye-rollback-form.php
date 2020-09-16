<?php
/**
 * Rollback Menu
 *
 * Provides the rollback screen view with releases.
 */
// Ensure we have our necessary query strings
if (isset($_GET['type']) && !isset($_GET['plugin_file'])) {
    wp_die(__('WP Rollback is missing necessary parameters to continue. Please contact support.', 'wp-rollback'));
}


$plugin_rollback = $_GET['type'] == 'plugin' ? true : false;
$plugins = get_plugins();
?>
<div class="wrap">

    <div class="angelleye-content-wrap">

        <h1>
            <?php _e('Angell EYE Updater', 'wp-rollback'); ?>
        </h1>

        <p><?php echo apply_filters('angelleye_rollback_description', sprintf(__('Please select which %1$s version you would like to rollback to from the releases listed below. You currently have version %2$s installed of %3$s.', 'wp-rollback'), '<span class="type">' . ( $theme_rollback == true ? __('theme', 'wp-rollback') : __('plugin', 'wp-rollback') ) . '</span>', '<span class="current-version">' . esc_html($args['current_version']) . '</span>', '<span class="rollback-name">' . esc_html($args['rollback_name']) . '</span>')); ?></p>

        <div class="angelleye-changelog"></div>
    </div>

    <?php
    // A: Plugin rollbacks in first conditional:
    if (isset($args['plugin_file']) && in_array($args['plugin_file'], array_keys($plugins))) {
        
    } else {
        // Fallback check
        wp_die(__('Oh no! We\'re missing required rollback query strings. Please contact support so we can check this bug out and squash it!', 'wp-rollback'));
    }
    ?>

    <form name="check_for_rollbacks" class="rollback-form" action="<?php echo admin_url('/index.php'); ?>">
       
        

            <div class="angelleye-versions-wrap">

                <?php
                echo $versions_html;
                ?>

            </div>

        


        <div class="wpr-submit-wrap">
            <input type="submit" value="<?php _e( 'Rollback', 'wp-rollback' ); ?>" class="button-primary" />
            <input type="button" value="<?php _e('Cancel', 'wp-rollback'); ?>" class="button" onclick="location.href = '<?php echo wp_get_referer(); ?>';" />
        </div>

        <input type="hidden" name="page" value="angelleye-rollback">

        <input type="hidden" name="plugin_file" value="<?php echo esc_attr($args['plugin_file']); ?>">
        <input type="hidden" name="plugin_slug" value="<?php echo esc_attr($args['plugin_slug']); ?>">

        <input type="hidden" name="rollback_name" value="<?php echo esc_attr($args['rollback_name']); ?>">
        <input type="hidden" name="installed_version" value="<?php echo esc_attr($args['current_version']); ?>">
        <?php wp_nonce_field('angelleye_rollback_nonce'); ?>


    </form>

</div>
