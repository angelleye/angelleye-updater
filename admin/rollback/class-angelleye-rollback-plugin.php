<?php
/**
 * WP Rollback Plugin Upgrader
 *
 * Class that extends the WP Core Plugin_Upgrader found in core to do rollbacks.
 *
 * @copyright  : http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      : 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Angelleye_Rollback_Plugin_Upgrader
 */
class Angelleye_Rollback_Plugin_Upgrader extends Plugin_Upgrader {

	/**
	 * Plugin rollback.
	 *
	 * @param       $plugin
	 * @param array $args
	 *
	 * @return array|bool|\WP_Error
	 */
	public function rollback( $plugin, $args = array() ) {

                
                $this->api = new AngellEYE_Updater_API();
                
            
		$defaults    = array(
			'clear_update_cache' => true,
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$this->init();
		$this->upgrade_strings();

		// TODO: Add final check to make sure plugin exists
		if ( 0 ) {
			$this->skin->before();
			$this->skin->set_result( false );
			$this->skin->error( 'up_to_date' );
			$this->skin->after();

			return false;
		}

		$plugin_slug = $this->skin->plugin;

		$plugin_version = $this->skin->options['version'];
                $zip_full_path = add_query_arg(array('action' => 'rollback_download', 'file_id' => '999', 'product_id' => $plugin_slug, 'version' => $plugin_version), $this->api->api_url);
		$url = $zip_full_path;
		add_filter( 'upgrader_pre_install', array( $this, 'deactivate_plugin_before_upgrade' ), 10, 2 );
		add_filter( 'upgrader_clear_destination', array( $this, 'delete_old_plugin' ), 10, 4 );

		$this->run( array(
			'package'           => $url,
			'destination'       => WP_PLUGIN_DIR,
			'clear_destination' => true,
			'clear_working'     => true,
			'hook_extra'        => array(
				'plugin' => $plugin,
				'type'   => 'plugin',
				'action' => 'update',
			),
		) );

		// Cleanup our hooks, in case something else does a upgrade on this connection.
		remove_filter( 'upgrader_pre_install', array( $this, 'deactivate_plugin_before_upgrade' ) );
		remove_filter( 'upgrader_clear_destination', array( $this, 'delete_old_plugin' ) );

		if ( ! $this->result || is_wp_error( $this->result ) ) {
			return $this->result;
		}

		// Force refresh of plugin update information.
		wp_clean_plugins_cache( $parsed_args['clear_update_cache'] );

		return true;
	}

}
