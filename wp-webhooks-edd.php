<?php
/**
 * Plugin Name: WP Webhooks - Easy Digital Downloads
 * Plugin URI: https://ironikus.com/downloads/wp-webhooks-edd/
 * Description: Extend Easy Digital Downloads with webhooks
 * Version: 1.0.0
 * Author: Ironikus
 * Author URI: https://ironikus.com/
 * License: GPL3
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) exit;

// Plugin name.
define( 'WPWH_EDD_NAME',           'WP Webhooks - Easy Digital Downloads' );

// Plugin version.
define( 'WPWH_EDD_VERSION',        '1.0.0' );

// Plugin Root File.
define( 'WPWH_EDD_PLUGIN_FILE',    __FILE__ );

// Plugin base.
define( 'WPWH_EDD_PLUGIN_BASE',    plugin_basename( WPWH_EDD_PLUGIN_FILE ) );

// Plugin Folder Path.
define( 'WPWH_EDD_PLUGIN_DIR',     plugin_dir_path( WPWH_EDD_PLUGIN_FILE ) );

// Plugin Folder URL.
define( 'WPWH_EDD_PLUGIN_URL',     plugin_dir_url( WPWH_EDD_PLUGIN_FILE ) );

if ( function_exists( 'EDD' ) && defined( 'EDD_VERSION' ) && version_compare( EDD_VERSION, '2.1.0', '>=' ) ) {
	
	function wp_webhooks_edd(){

		//Extends the plugin with custom webhook actions & triggers
		require_once WPWH_EDD_PLUGIN_DIR . 'includes/classes/class-wp-webhooks-edd-actions.php';
		require_once WPWH_EDD_PLUGIN_DIR . 'includes/classes/class-wp-webhooks-edd-triggers.php';
	
		//Extends the plugin with custom webhook actions & triggers for EDD Recurring
		require_once WPWH_EDD_PLUGIN_DIR . 'includes/classes/class-wp-webhooks-edd-actions-subscriptions.php';
		require_once WPWH_EDD_PLUGIN_DIR . 'includes/classes/class-wp-webhooks-edd-subscriptions.php';
	
		//Extends the plugin with custom webhook actions & triggers for EDD Software Licensing
		require_once WPWH_EDD_PLUGIN_DIR . 'includes/classes/class-wp-webhooks-edd-actions-software-licensing.php';
		require_once WPWH_EDD_PLUGIN_DIR . 'includes/classes/class-wp-webhooks-edd-software-licensing.php';
	
	}
	
	// Make sure we load the extension after main plugin is loaded
	if( defined( 'WPWHPRO_SETUP' ) || defined( 'WPWH_SETUP' ) ){
		wp_webhooks_edd();
	} else {
		add_action( 'wpwhpro_plugin_loaded', 'wp_webhooks_edd' );
	}
	
} else {
	
	add_action( 'admin_notices', 'wpwh_edd_custom_notice', 100 );
	function wpwh_edd_custom_notice(){

		ob_start();
		?>
		<div class="notice notice-warning">
			<p><?php echo 'To use <strong>' . WPWH_EDD_NAME . '</strong> properly, please activate <strong>Easy Digital Downloads</strong> with a version greater than <strong>2.1.0</strong>.'; ?></p>
		</div>
		<?php
		echo ob_get_clean();

	}
	
}


