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
class Order_Sync {

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
	 * Add menu order sync
	 */
	function add_menu_order_sync(){
		add_submenu_page( 
			'woocommerce',
			__( 'Jubelio Order Sync', $this->plugin_name ),
			__( 'Jubelio Order Sync', $this->plugin_name ),
			'manage_options',
			'jubelio-order-sync',
			[$this,'display_order_sync'],
			4
		); 
	}

	/**
	 * Display order sync
	 */
	function display_order_sync(){

		include WOOCOMMERCE_JUBELIO_SYNC_PATH.'/admin/partials/order-sync.php';

	}

	public function get_orders_by_ajax() {

		check_ajax_referer( 'woo_jb_get_orders_sync', 'security' );

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

		$data = [];

		$args = [
			'paginate' 	=> true,
			'offset' 	=> $_request['start'],
			'limit' 	=> $_request['length'],
			'paginate' 	=> true,
			'orderby' 	=> 'date',
			'order' 	=> 'DESC',
		];

		if ( isset( $_request['search']['value'] ) ) :
			$args['order_id'] = $_request['search']['value'];
		endif;

		$orders = wc_get_orders( $args );

		$orders_data = $orders->orders;

		foreach ( $orders_data as $key => $order ) :

			if ( is_a( $order, 'WC_Order_Refund' ) ) :
				$order = wc_get_order( $order->get_parent_id() );
			endif;

			$customer_name = $order->get_billing_first_name();
			if ( $order->get_billing_last_name() ) :
				$customer_name .= ' '.$order->get_billing_last_name();
			endif;

			$qty 			= $order->get_item_count();
			$total 			= $order->get_total();
			$tgl 			= $order->get_date_created()->format ('d-m-Y H:i');
			$status_sync 	= '';

			$status_sync = '<span class="ui red label">Belum</span>';
			$jb_id = $order->get_meta('jb_id');
			if ( $jb_id ) :
				$status_sync = '<span class="ui green label">Sudah</span>';
			endif;

			$woo_link = '<a href="'.admin_url('post.php?post='.$order->get_id().'&action=edit').'">'.$order->get_id().'</a>';

			$data[] = [
				'woo_id' 		=> $order->get_id(),
				'woo_link' 		=> $woo_link,
				'jb_id' 		=> $jb_id,
				'customer' 		=> $customer_name,
				'qty' 			=> $qty,
				'total' 		=> wc_price($total),
				'date' 			=> $tgl,
				'status_sync' 	=> $status_sync
			];

		endforeach;

		$recordsTotal = $recordsFiltered = $orders->total;

		$response = [
			'draw' 				=> $_request['draw'],
			'recordsTotal' 		=> $recordsTotal,
			'recordsFiltered' 	=> $recordsFiltered,
			'data' 				=> $data,
		];

		wp_send_json( $response );

	}

	/**
	 * Handle a custom 'customvar' query var to get orders with the 'customvar' meta.
	 * @param array $query - Args for WP_Query.
	 * @param array $query_vars - Query vars from WC_Order_Query.
	 * @return array modified $query
	 */
	public function custom_wc_orders_query_var( $query, $query_vars ) {
		if ( ! empty( $query_vars['order_id'] ) ) {
			$query['p'] = esc_attr( $query_vars['order_id'] );
		}
		if ( ! empty( $query_vars['order_ids'] ) ) {
			$query['post__in'] = $query_vars['order_ids'];
		}

		return $query;
	}

	public function bulk_sync_orders_by_ajax() {

		check_ajax_referer( 'woo_jb_bulk_sync_orders', 'security' );

		$_request = wp_parse_args( $_REQUEST, [
			'item_ids' => '',
		] );

		if ( $_request['item_ids'] ) :

			$item_ids = explode(',',$_request['item_ids']);
		
			$args = [
				'limit' 	=> -1,
				'orderby' 	=> 'date',
				'order' 	=> 'ASC',
				'order_ids' => $item_ids
			];

			$query = new \WC_Order_Query( $args );
			$orders = $query->get_orders();

			$token  = woo_jb_get_jubelio_token();

			$data = [];
			foreach ( $orders as $order ) :
				
				$jb_id = woo_jb_create_jubelio_order( $order, $token );
				$order_id = $order->get_id();
				
				$status = 'error';
				if ( $jb_id ) :
					$status = 'success';
				endif;
				
				$message = '['.$status.'] action: create jb order / jb_id '.$jb_id.' / woo_id: '.$order_id;
				woo_jb_save_log_order( $message );

				$data[] = $jb_id;
			endforeach;

			wp_send_json_success($data);

		else:

			wp_send_json_error();

		endif;

	}
	
	public function update_order_status_change_to_jubelio( $order_id, $old_status, $new_status ) {

		if ( $old_status !== $new_status ) :

			$token = woo_jb_get_jubelio_token();

			$order = wc_get_order($order_id);
			$jb_id = $order->get_meta('jb_id');

			$status = 'error';
			$update = woo_jb_update_jubelio_order( $order, $token );
			if ( $update ) :
				$status = 'success';
			endif;

			$additonal_message = '';
			if ( 'processing' === $new_status ) :
				$update_paid = woo_jb_update_jubelio_order_status_paid( $order, $token );
				if ( $update_paid ) :
					$additonal_message = ' / Update Paid';
				endif;
			elseif ( 'completed' === $new_status ) :
				$update_complete = woo_jb_update_jubelio_order_status_complete( $order, $token );
				if ( $update_complete ) :
					$additonal_message = ' / Update Complete';
				endif;
			endif;

			$message = '['.$status.'] action: update jb order / jb_id '.$jb_id.' / woo_id: '.$order_id.' / woo_status: '.$old_status . ' to '.$new_status.$additonal_message;
			woo_jb_save_log_order( $message );

		endif;

	}

}