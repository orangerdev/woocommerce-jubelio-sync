<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://ridwan-arifandi.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Jubelio_Sync
 * @subpackage Woocommerce_Jubelio_Sync/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woocommerce_Jubelio_Sync
 * @subpackage Woocommerce_Jubelio_Sync/admin
 * @author     Orangerdev Team <orangerdigiart@gmail.com>
 */
class Woocommerce_Jubelio_Sync_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommerce_Jubelio_Sync_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommerce_Jubelio_Sync_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name.'-dataTables.semanticui', 'https://cdn.datatables.net/1.13.1/css/dataTables.semanticui.min.css', array(), '1.13.1', 'all' );
		wp_enqueue_style( $this->plugin_name.'-semantic', 'https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.8/semantic.min.css', array(), '2.8.8', 'all' );
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woocommerce-jubelio-sync-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommerce_Jubelio_Sync_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommerce_Jubelio_Sync_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name.'-jquery.dataTables', 'https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js', array( 'jquery' ), '1.13.1', false );
		wp_enqueue_script( $this->plugin_name.'-semantic', 'https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.8/semantic.min.js', array( 'jquery' ), '2.8.8', false );
		wp_enqueue_script( $this->plugin_name.'-dataTables.semanticui', 'https://cdn.datatables.net/1.13.1/js/dataTables.semanticui.min.js', array( 'jquery' ), '1.13.1', false );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woocommerce-jubelio-sync-admin.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'woo_jb_vars', [
			'woo_jb_get_products_sync' => [
				'ajax_nonce' => wp_create_nonce( 'woo_jb_get_products_sync' ), 
			],
			'woo_jb_get_orders_sync' => [
				'ajax_nonce' => wp_create_nonce( 'woo_jb_get_orders_sync' ), 
			],
			'woo_jb_bulk_sync_products' => [
				'ajax_nonce' => wp_create_nonce( 'woo_jb_bulk_sync_products' ), 
			],
			'woo_jb_sync_products2' => [
				'ajax_nonce' => wp_create_nonce( 'woo_jb_sync_products2' ), 
			],
			'woo_jb_bulk_sync_orders' => [
				'ajax_nonce' => wp_create_nonce( 'woo_jb_bulk_sync_orders' ), 
			],
			'woo_jb_bulk_sync_get_products' => [
				'ajax_nonce' => wp_create_nonce( 'woo_jb_bulk_sync_get_products' ), 
			],
			'woo_jb_bulk_sync_all_products' => [
				'ajax_nonce' => wp_create_nonce( 'woo_jb_bulk_sync_all_products' ), 
			],
			'product_sync_process_url' => admin_url( 'admin.php?page=jubelio-product-sync' )
		] );
	}

	public function setup_plugin_options() {

		$jb_locations_opt = [];
		if ( isset( $_GET['page'] ) && 
			'jubelio-options' == $_GET['page'] ) :
			$token = woo_jb_get_jubelio_token();
			$jb_locations_opt = woo_jb_get_jubelio_locations($token);	
		endif;

		$jb_default_product_status=get_post_statuses();

		Container::make( 'theme_options', __( 'Jubelio Options' ) )
			->set_page_file( 'jubelio-options' )
			->set_page_parent( 'woocommerce' )
			->add_fields( array(
				Field::make( 'text', 'jb_u', __( 'Username' ) ),
				Field::make( 'text', 'jb_p', __( 'Password' ) )
					->set_attribute( 'type', 'password' ),
				Field::make( 'multiselect', 'jb_active_locations', __( 'Active Locations' ) )
					->set_options( $jb_locations_opt ),
				Field::make( 'select', 'jb_default_product_status', __( 'Default Product Status' ) )
					->set_options( $jb_default_product_status )
					->set_default_value('pending'),
				Field::make( 'checkbox', 'jb_webhook_key_active', __( 'Webhook Active Validate Secret Key' ) )
					->set_option_value( 'yes' ),
				Field::make( 'text', 'jb_webhook_key', __( 'Webhook Secret Key' ) ),
				Field::make( 'text', 'jb_webhook_key_hash', __( 'Webhook Secret Key Hashing Algorithm' ) )
					->set_default_value('sha256'),
				Field::make( 'checkbox', 'jb_webhook_ip_active', __( 'Webhook Active Validate IP Adress' ) )
					->set_option_value( 'yes' ),
				Field::make( 'text', 'jb_webhook_ip', __( 'Webhook IP Address' ) )
					->set_help_text('Separate each IP with a comma ,')
					->set_default_value('52.163.124.104'),
				Field::make( 'text', 'jb_webhook_new_product', __( 'Webhook Handle - New Product' ) )
					->set_default_value( site_url('/?woo_jb_webhook=new-product') )
					->set_attribute( 'readOnly', 1 ),
				Field::make( 'text', 'jb_webhook_update_product_stock', __( 'Webhook Handle - Update Product Stock' ) )
					->set_default_value( site_url('/?woo_jb_webhook=update-stock') )
					->set_attribute( 'readOnly', 1 ),
				Field::make( 'text', 'jb_webhook_update_product_price', __( 'Webhook Handle - Update Product Price' ) )
					->set_default_value( site_url('/?woo_jb_webhook=update-price') )
					->set_attribute( 'readOnly', 1 ),
				Field::make( 'text', 'jb_webhook_transfer_update_product_stock', __( 'Webhook Handle - Transfer Update Product Stock' ) )
					->set_default_value( site_url('/?woo_jb_webhook=transfer-update-stock') )
					->set_attribute( 'readOnly', 1 ),
				Field::make( 'text', 'jb_webhook_update_order', __( 'Webhook Handle - Update Order' ) )
					->set_default_value( site_url('/?woo_jb_webhook=update-order') )
					->set_attribute( 'readOnly', 1 ),
			) );
	}
	
	public function load_crb() {
		\Carbon_Fields\Carbon_Fields::boot();
	}

	public function custom_data_plugin_options() {

		if ( isset( $_POST['carbon_fields_compact_input']['_jb_active_locations'] ) ) :

			$locations = $_POST['carbon_fields_compact_input']['_jb_active_locations'];
			$locations_arr = explode('|',$locations);
			update_option('jb_active_locations',$locations_arr);

		endif;

	}

}
