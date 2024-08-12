<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://ridwan-arifandi.com
 * @since             1.0.0
 * @package           Woocommerce_Jubelio_Sync
 *
 * @wordpress-plugin
 * Plugin Name:       Woocommerce Jubelio Sync
 * Plugin URI:        https://ridwan-arifandi.com
 * Description:       Woocommerce Jubelio Sync
 * Version:           1.0.15
 * Author:            Orangerdev Team
 * Author URI:        https://ridwan-arifandi.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woocommerce-jubelio-sync
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WOOCOMMERCE_JUBELIO_SYNC_VERSION', '1.0.15' );

/**
 * Base plugin path & uri
 */
define( 'WOOCOMMERCE_JUBELIO_SYNC_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOOCOMMERCE_JUBELIO_SYNC_URI', plugin_dir_url( __FILE__ ) );

define( 'WOOCOMMERCE_JUBELIO_SYNC_TAX_BRAND', 'brand' );

require_once( 'vendor/autoload.php' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woocommerce-jubelio-sync-activator.php
 */
function activate_woocommerce_jubelio_sync() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-jubelio-sync-activator.php';
	Woocommerce_Jubelio_Sync_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woocommerce-jubelio-sync-deactivator.php
 */
function deactivate_woocommerce_jubelio_sync() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-jubelio-sync-deactivator.php';
	Woocommerce_Jubelio_Sync_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woocommerce_jubelio_sync' );
register_deactivation_hook( __FILE__, 'deactivate_woocommerce_jubelio_sync' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-jubelio-sync.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woocommerce_jubelio_sync() {

	$plugin = new Woocommerce_Jubelio_Sync();
	$plugin->run();

}
run_woocommerce_jubelio_sync();


// add_action( 'parse_request', function(){

// 	if ( isset( $_GET['dev'] ) ) :

		
// 		die('dev');

// 	endif;

// } );