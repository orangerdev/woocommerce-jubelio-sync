<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://ridwan-arifandi.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Jubelio_Sync
 * @subpackage Woocommerce_Jubelio_Sync/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Woocommerce_Jubelio_Sync
 * @subpackage Woocommerce_Jubelio_Sync/includes
 * @author     Orangerdev Team <orangerdigiart@gmail.com>
 */
class Woocommerce_Jubelio_Sync {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Woocommerce_Jubelio_Sync_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WOOCOMMERCE_JUBELIO_SYNC_VERSION' ) ) {
			$this->version = WOOCOMMERCE_JUBELIO_SYNC_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'woocommerce-jubelio-sync';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Woocommerce_Jubelio_Sync_Loader. Orchestrates the hooks of the plugin.
	 * - Woocommerce_Jubelio_Sync_i18n. Defines internationalization functionality.
	 * - Woocommerce_Jubelio_Sync_Admin. Defines all hooks for the admin area.
	 * - Woocommerce_Jubelio_Sync_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-jubelio-sync-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-jubelio-sync-i18n.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/functions/token.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/functions/products.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/functions/orders.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/functions/request.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/functions/log.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-woocommerce-jubelio-sync-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-product-sync.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-order-sync.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-log.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-woocommerce-jubelio-sync-public.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-product-sync.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-order-sync.php';

		$this->loader = new Woocommerce_Jubelio_Sync_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Woocommerce_Jubelio_Sync_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Woocommerce_Jubelio_Sync_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Woocommerce_Jubelio_Sync_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'after_setup_theme', $plugin_admin, 'load_crb' );
		$this->loader->add_action( 'carbon_fields_register_fields', $plugin_admin, 'setup_plugin_options' );
		$this->loader->add_action( 'carbon_fields_theme_options_container_saved', $plugin_admin, 'custom_data_plugin_options' );

		$product_sync = new Woocommerce_Jubelio_Sync\Admin\Product_Sync( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_menu', $product_sync, 'add_menu_product_sync', 999 );
		$this->loader->add_action( 'wp_ajax_woo_jb_get_products_sync', $product_sync, 'get_products_by_ajax' );
		$this->loader->add_action( 'wp_ajax_woo_jb_bulk_sync_products', $product_sync, 'bulk_sync_products_by_ajax' );
		$this->loader->add_action( 'init', $product_sync, 'setup_custom_taxonomy', 10 );
		$this->loader->add_action( 'wp_ajax_woo_jb_bulk_all_sync_per_product', $product_sync, 'bulk_all_sync_per_product_by_ajax', 10 );
		$this->loader->add_action( 'admin_head', $product_sync, 'run_process_product_sync', 999999 );

		$order_sync = new Woocommerce_Jubelio_Sync\Admin\Order_Sync( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_menu', $order_sync, 'add_menu_order_sync', 999 );
		$this->loader->add_action( 'wp_ajax_woo_jb_get_orders_sync', $order_sync, 'get_orders_by_ajax' );
		$this->loader->add_action( 'wp_ajax_woo_jb_bulk_sync_orders', $order_sync, 'bulk_sync_orders_by_ajax' );
		$this->loader->add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', $order_sync, 'custom_wc_orders_query_var', 10, 2 );
		$this->loader->add_action( 'woocommerce_order_status_changed', $order_sync, 'update_order_status_change_to_jubelio', 10, 3 );

		$log = new Woocommerce_Jubelio_Sync\Admin\Log( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_menu', $log, 'add_menu_log', 999 );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Woocommerce_Jubelio_Sync_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$product = new \Woocommerce_Jubelio_Sync\Publics\Product_Sync( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'template_redirect', $product, 'webhook_new_product' );
		$this->loader->add_action( 'template_redirect', $product, 'webhook_update_stock' );
		$this->loader->add_action( 'template_redirect', $product, 'webhook_update_price' );
		$this->loader->add_action( 'template_redirect', $product, 'webhook_transfer_update_stock' );

		$order = new \Woocommerce_Jubelio_Sync\Publics\Order_Sync( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'woocommerce_checkout_order_processed', $order, 'order_sync_jubelio' );
		$this->loader->add_action( 'template_redirect', $order, 'webhook_update_order' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Woocommerce_Jubelio_Sync_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
