<?php

/**
 * Fired during plugin activation
 *
 * @link       https://ridwan-arifandi.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Jubelio_Sync
 * @subpackage Woocommerce_Jubelio_Sync/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Woocommerce_Jubelio_Sync
 * @subpackage Woocommerce_Jubelio_Sync/includes
 * @author     Orangerdev Team <orangerdigiart@gmail.com>
 */
class Woocommerce_Jubelio_Sync_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		if ( ! is_dir( WP_CONTENT_DIR . '/woo_jb' ) ) :
			wp_mkdir_p( WP_CONTENT_DIR . '/woo_jb' );
			chmod( WP_CONTENT_DIR . '/woo_jb', 0755 );
		endif;

	}

}
