<?php
/**
 * Rollback Action.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! empty( $_GET['plugin_file'] ) && file_exists( WP_PLUGIN_DIR . '/' . $_GET['plugin_file'] ) ) {

	// This is a plugin rollback.
        $plugin_slug = $_GET['plugin_slug'];
	$title   = $_GET['rollback_name'];
	$nonce   = 'upgrade-plugin_' . $plugin_slug;
	$url     = 'index.php?page=wp-rollback&plugin_file=' . esc_url( $args['plugin_file'] ) . '/action=upgrade-plugin';
	$plugin  = $plugin_slug;
	$version = $args['plugin_version'];
        $plugin_file = WP_PLUGIN_DIR . '/' . $_GET['plugin_file'];
	$upgrader = new Angelleye_Rollback_Plugin_Upgrader( new Plugin_Upgrader_Skin( compact( 'title', 'nonce', 'url', 'plugin', 'version' ) ) );
        
	$result = $upgrader->rollback( $plugin_file );

	if ( ! is_wp_error( $result ) && $result ) {
		do_action( 'wpr_plugin_success', $_GET['plugin_file'], $version );
	} else {
		do_action( 'wpr_plugin_failure', $result );
	}
} else {
	_e( 'This rollback request is missing a proper query string. Please contact support.', 'wp-rollback' );
}

