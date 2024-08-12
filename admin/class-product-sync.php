<?php

namespace Woocommerce_Jubelio_Sync\Admin;

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
class Product_Sync {

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
	 * Add menu product sync
	 */
	public function add_menu_product_sync(){
		add_submenu_page( 
			'woocommerce',
			__( 'Jubelio Product Sync', $this->plugin_name ),
			__( 'Jubelio Product Sync', $this->plugin_name ),
			'manage_options',
			'jubelio-product-sync',
			[$this,'display_product_sync'],
			3
		); 

	}

	/**
	 * Display product sync
	 */
	public function display_product_sync(){

		if ( isset( $_GET['item_ids'] ) && !empty( $_GET['item_ids'] ) ) :

			include WOOCOMMERCE_JUBELIO_SYNC_PATH.'/admin/partials/product-sync-process.php';

		else:

			include WOOCOMMERCE_JUBELIO_SYNC_PATH.'/admin/partials/product-sync.php';

		endif;

	}

	public function get_products_by_ajax() {

		check_ajax_referer( 'woo_jb_get_products_sync', 'security' );

		$_request = wp_parse_args( $_REQUEST, [
			'draw' 		=> 1,
			'start' 	=> 0,
			'length' 	=> 10,
			'search' 	=> [
				'value' => '',
			],
			'order'		=> [
				[
					'column' => 0,
					'dir' 	 => 'asc',
				]
			]
		] );

		$recordsTotal = $recordsFiltered = 0;

		$page = intval( $_request['start'] / $_request['length'] );
		$page += 1;

		$args = [
			'page' 		=> $page,
			'pageSize' 	=> $_request['length']
		];

		if ( isset( $_request['order'][0]['column'] ) ) :
			switch ( $_request['order'][0]['column'] ) :
				case 2:
						$sortBy = 'item_group_id';
					break;
				case 3:
						$sortBy = 'item_name';
					break;
				default:
						$sortBy = 'item_group_id';
					break;
			endswitch;
			$args['sortBy'] = $sortBy;
		endif;

		if ( isset( $_request['order'][0]['dir'] ) ) :
			$args['sortDirection'] = $_request['order'][0]['dir'];
		endif;

		if ( isset( $_request['search']['value'] ) ) :
			$args['q'] = rawurlencode(trim($_request['search']['value']));
		endif;

		$token 				= woo_jb_get_jubelio_token();
		$jubelio_categories = woo_jb_get_jubelio_categories_full( $token );
		$jubelio_products 	= woo_jb_get_jubelio_products( $args, $token );
		// do_action( 'inspect', [ 'jubelio_products', $jubelio_products ] );

		$jb_products = get_transient( 'jb_products' );
		if ( !is_array( $jb_products ) ) :
			$jb_products = [];
		endif;

		$data 		= [];
		$jb_ids 	= [];

		$jubelio_products_data = [];
		if ( isset( $jubelio_products['data'] ) ) :
			$jubelio_products_data = $jubelio_products['data'];
			foreach ( $jubelio_products_data as $key => $jubelio_product ) :
				$jb_ids[] = $jubelio_product['item_group_id'];
			endforeach;
		endif;

		$woo_ids = [];
		$woo_products = [];
		if ( $jb_ids ) :
			$args = array(
				'numberposts' 	=> -1,
				'post_type'   	=> 'product',
				'meta_key' 		=> 'jb_id',
				'meta_value' 	=> $jb_ids,
				'meta_compare' 	=> 'IN',
                'post_status'   => 'any'
			);
			$woo_products = get_posts( $args );
			if ( $woo_products ) :
				foreach ( $woo_products as $key => $woo_product ) :
					$woo_ids[$woo_product->jb_id] = $woo_product->ID;
				endforeach;
			endif;
		endif;

		foreach ( $jubelio_products_data as $key => $jubelio_product ) :

			$item_id = $jubelio_product['item_group_id'];
			$jb_products[$item_id] = $jubelio_product;

			$woo_id = '';
			// $status_sync = '<span class="ui red label">Belum</span>';
			$status_sync = 'Belum';
			if ( isset( $woo_ids[$item_id] ) ) :
				$woo_id = $woo_ids[$item_id];
				// $status_sync = '<span class="ui green label">Sudah</span>';
				$status_sync = 'Sudah';
			endif;

			$category_id = $jubelio_product['item_category_id'];
			$category = '';
			if ( isset( $jubelio_categories[$category_id] ) ) :

				$cat_parent_name = $cat_parent_parent_name = '';
				$cat_parent_id = $jubelio_categories[$category_id]['parent_id'];
				if ( $cat_parent_id && isset( $jubelio_categories[$cat_parent_id] ) ) :

					$cat_parent = $jubelio_categories[$cat_parent_id];
					$cat_parent_name = $cat_parent['name'].' > ';
					$cat_parent_parent_id = $cat_parent['parent_id'];
					if ( $cat_parent_parent_id ) :
						$cat_parent_parent = $jubelio_categories[$cat_parent_parent_id];
						$cat_parent_parent_name = $cat_parent_parent['name'].' > ';
					endif;

				endif;
				$category = $cat_parent_parent_name.$cat_parent_name.$jubelio_categories[$category_id]['name'];

			endif;

			$stock = 0;
			$variants = [];
			foreach ( $jubelio_product['variants'] as $variation ) :
				$variant_value = [];
				foreach ( $variation['variation_values'] as $variation_value ) :
					$variant_value[] = $variation_value['label'].': '.$variation_value['value'];
				endforeach;
				$variant_value = implode( ', ', $variant_value );
				$available_qty = intval($variation['available_qty']);
				$variants[] = [
					'variant_value' => $variant_value,
					'sku' 			=> $variation['item_code'],
					'harga' 		=> wc_price($variation['sell_price']),
					'stok' 			=> $available_qty,
				];
				$stock += $available_qty;
			endforeach;

			if ( $woo_id ) :
				$woo_id = '<a href="'.admin_url('post.php?post='.$woo_id.'&action=edit').'">'.$woo_id.'</a>';
			endif;

			$data[] = [
				'woo_id' 		=> $woo_id,
				'jb_id' 		=> $item_id,
				'name' 			=> $jubelio_product['item_name'],
				'thumbnail' 	=> $jubelio_product['thumbnail'],
				'category' 		=> $category,
				'variants' 		=> $variants,
				'stock'			=> $stock,
				'status_sync' 	=> $status_sync,
			];

		endforeach;
		
		set_transient( 'jb_products', $jb_products, ( DAY_IN_SECONDS * 2 ) );
		set_transient( 'jb_categories', $jubelio_categories );
		
		if ( isset( $jubelio_products['total'] ) ) :
			$recordsTotal = $recordsFiltered = $jubelio_products['total'];
		endif;

		$response = [
			'draw' 					=> $_request['draw'],
			'recordsTotal' 			=> $recordsTotal,
			'recordsFiltered' 		=> $recordsFiltered,
			'data' 					=> $data,
			// 'jubelio_products_data' => $jubelio_products_data,
			// 'jubelio_categories' 	=> $jubelio_categories,
			'jb_ids'				=> $jb_ids,
			'woo_ids'				=> $woo_ids,
            'woo_products'			=> $woo_products
		];

		wp_send_json( $response );

	}

	public function bulk_sync_products_by_ajax() {

		check_ajax_referer( 'woo_jb_bulk_sync_products', 'security' );

		$_request = wp_parse_args( $_REQUEST, [
			'item_ids' => '',
		] );

		$token = woo_jb_get_jubelio_token();
				
		$item_group_ids = explode(',',$_request['item_ids']);

		$response = [
			'all' 		=> count($item_group_ids),
			'success' 	=> 0,
			'error' 	=> 0,
			'message'	=> '',
			'product_ids' => [],
		];

		$jb_products = get_transient( 'jb_products' );

		foreach ( $item_group_ids as $item_group_id ) :

			$jb_product = [];
			if ( isset( $jb_products[$item_group_id] ) ) :
				$jb_product = $jb_products[$item_group_id];
			endif;

			$woo_product = woo_jb_update_woo_product_by_item_group_id( $item_group_id, $jb_product, $token );

			$response['product_ids'][] = $woo_product;

			if ( !empty( $woo_product ) ) :
				$response['success']++;
			else:
				$response['error']++;
			endif;

		endforeach;

		$response['message'] = 'Bulk Sync Results: Success '.$response['success'].' / Error '.$response['error'].' / All '.$response['all'];

		wp_send_json( $response );

	}

	public function setup_custom_taxonomy() {

		if ( ! taxonomy_exists( WOOCOMMERCE_JUBELIO_SYNC_TAX_BRAND ) ) :

			$labels = array(
				'name'                       => _x( 'Brands', 'Taxonomy General Name', 'woocommerce-jubelio-sync' ),
				'singular_name'              => _x( 'Brand', 'Taxonomy Singular Name', 'woocommerce-jubelio-sync' ),
				'menu_name'                  => __( 'Brand', 'woocommerce-jubelio-sync' ),
				'all_items'                  => __( 'All Brands', 'woocommerce-jubelio-sync' ),
				'parent_item'                => __( 'Parent Brand', 'woocommerce-jubelio-sync' ),
				'parent_item_colon'          => __( 'Parent Brand:', 'woocommerce-jubelio-sync' ),
				'new_item_name'              => __( 'New Brand Name', 'woocommerce-jubelio-sync' ),
				'add_new_item'               => __( 'Add Brand Item', 'woocommerce-jubelio-sync' ),
				'edit_item'                  => __( 'Edit Brand', 'woocommerce-jubelio-sync' ),
				'update_item'                => __( 'Update Brand', 'woocommerce-jubelio-sync' ),
				'view_item'                  => __( 'View Brand', 'woocommerce-jubelio-sync' ),
				'separate_items_with_commas' => __( 'Separate brands with commas', 'woocommerce-jubelio-sync' ),
				'add_or_remove_items'        => __( 'Add or remove brands', 'woocommerce-jubelio-sync' ),
				'choose_from_most_used'      => __( 'Choose from the most used', 'woocommerce-jubelio-sync' ),
				'popular_items'              => __( 'Popular Brands', 'woocommerce-jubelio-sync' ),
				'search_items'               => __( 'Search Brands', 'woocommerce-jubelio-sync' ),
				'not_found'                  => __( 'Not Found', 'woocommerce-jubelio-sync' ),
				'no_terms'                   => __( 'No brands', 'woocommerce-jubelio-sync' ),
				'items_list'                 => __( 'Brands list', 'woocommerce-jubelio-sync' ),
				'items_list_navigation'      => __( 'Brands list navigation', 'woocommerce-jubelio-sync' ),
			);

			$args = array(
				'labels'                     => $labels,
				'hierarchical'               => true,
				'public'                     => true,
				'show_ui'                    => true,
				'show_admin_column'          => true,
				'show_in_nav_menus'          => true,
				'show_tagcloud'              => true,
			);
			
			register_taxonomy( WOOCOMMERCE_JUBELIO_SYNC_TAX_BRAND, array( 'product' ), $args );

		endif;

	}

	public function bulk_all_sync_per_product_by_ajax() {

		check_ajax_referer( 'woo_jb_bulk_all_sync_per_product', 'security' );

		$_request = wp_parse_args( $_REQUEST, [
			'current_row' 	=> 0,
			'total_all_row' => 0,
			'product_id'  	=> 0,
		] );
				
		$item_group_id = $_request['product_id'];

		if ( $item_group_id ) :

			$jb_product_tr = [];
			$token = woo_jb_get_jubelio_token();

			$product_id = woo_jb_update_woo_product_by_item_group_id( $item_group_id, $jb_product_tr, $token );
			if ( $product_id ) :
				if ( $_request['current_row'] >= $_request['total_all_row'] ) :
					$status = 'COMPLETE';
				else:
					$status = 'NEXT';
				endif;	
			else:
				$status = 'FAIL';
			endif;

		else:

			$status = 'FAIL';

		endif;

		$response = [
			'status' => $status,
		];

		wp_send_json( $response );

	}

	public function run_process_product_sync() {

		$current_screen = get_current_screen();

		if ( $current_screen->id === 'woocommerce_page_jubelio-product-sync' ) :

			$item_id_arr = [];
			if ( isset( $_GET['item_ids'] ) && !empty( $_GET['item_ids'] ) ) :
				$item_id_arr = explode(',',$_GET['item_ids']);
			endif;

			if ( $item_id_arr ) :
				?>
				<script>
					jQuery(document).ready( function ($) {

						bulk_all_sync_per_product();

					} );
				</script>
				<?php
			endif;

		endif;

	}

}