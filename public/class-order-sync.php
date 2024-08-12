<?php

namespace Woocommerce_Jubelio_Sync\Publics;

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

    public function order_sync_jubelio( $order_id ) {

        $order = wc_get_order( $order_id );

		$token = woo_jb_get_jubelio_token();

        $jb_id = woo_jb_create_jubelio_order( $order, $token );
		$status = 'error';
		if ( $jb_id ) :
			$status = 'success';
		endif;

		$message = '['.$status.'] action: create jb order / jb_id '.$jb_id.' / woo_id: '.$order_id;
		woo_jb_save_log_order( $message );

    }

	public function webhook_update_order() {

        if ( isset( $_GET['woo_jb_webhook'] ) && $_GET['woo_jb_webhook'] == 'update-order' ) :

            $post_data = file_get_contents('php://input');

			$jb_webhook_key_active = carbon_get_theme_option('jb_webhook_key_active');
            if ( $jb_webhook_key_active ) :
                $jb_webhook_key_hash = carbon_get_theme_option('jb_webhook_key_hash');
                if ( empty( $jb_webhook_key_hash ) ) :
                    $jb_webhook_key_hash = 'sha256';
                endif;
                $jb_webhook_key = carbon_get_theme_option('jb_webhook_key');
                $jb_hash = hash_hmac($jb_webhook_key_hash, $post_data, $jb_webhook_key);
                if ( isset( $_SERVER['HTTP_SIGN'] ) && $jb_hash === $_SERVER['HTTP_SIGN'] ) :
                else:
                    $response_status = 'invalid sign';
                    $response = [
                        "status" => $response_status
                    ];
                    wp_send_json( $response );
                    exit();
                endif;
                // $jb_webhook_key = carbon_get_theme_option('jb_webhook_key');
                // if ( !empty( $jb_webhook_key ) &&
                // 	isset( $_SERVER['HTTP_AUTHORIZATION'] ) && 
                // 	$_SERVER['HTTP_AUTHORIZATION'] === $jb_webhook_key ) :
                // else:
                // 	$response_status = 'failed';
                // 	$response = [
                // 		"status" => $response_status
                // 	];
                // 	wp_send_json( $response );
                // 	exit();
                // endif;
            endif;

            $jb_webhook_ip_active = carbon_get_theme_option('jb_webhook_ip_active');
            if ( $jb_webhook_ip_active ) :
                $jb_webhook_ip = carbon_get_theme_option('jb_webhook_ip');
                $jb_webhook_ip = explode(',',$jb_webhook_ip);
				$user_ip = woo_jb_get_user_ip();
                if ( $jb_webhook_ip && in_array($user_ip,$jb_webhook_ip) ) :
                else:
                    $response_status = 'invalid ip address';
                    $response = [
                        "status" => $response_status,
                        "user_ip" => $user_ip 
                    ];
                    wp_send_json( $response );
                    exit();
                endif;
            endif;

            $post_data = json_decode($post_data, true);

			// do_action( 'inspect', [ 'headers_webhook_update_order', getallheaders() ] );
			// if ( function_exists( 'apache_request_headers' ) ) :
			// 	do_action( 'inspect', [ 'headers2_webhook_update_order', apache_request_headers() ] );
			// endif;
            // do_action( 'inspect', [ 'headers3_webhook_update_order', $_SERVER ] );

            $_request = wp_parse_args( $post_data, [
                'action'            	=> '',
                'salesorder_id'     	=> '',
                'salesorder_no'			=> '',
				'source' 				=> '',
				'store' 				=> '',
				'status'				=> '',
				'contact_id' 			=> '',
				'customer_name' 		=> '',
				'customer_phone' 		=> '',
				'customer_email' 		=> '',
				'payment_date' 			=> '',
				'transaction_date' 		=> '',
				'created_date' 			=> '',
				'invoice_id' 			=> '',
				'invoice_no' 			=> '',
				'is_tax_included' 		=> '',
				'note' 					=> '',
				'sub_total' 			=> '',
				'total_disc' 			=> '',
				'total_tax' 			=> '',
				'grand_total' 			=> '',
				'ref_no' 				=> '',
				'payment_method' 		=> '',
				'location_id' 			=> '',
				'is_canceled' 			=> '',
				'cancel_reason' 		=> '',
				'cancel_reason_detail' 	=> '',
				'cancel_status' 		=> '',
				'shipping_cost' 		=> '',
				'insurance_cost'		=> '',
				'is_paid' 				=> '',
				'shipping_full_name' 	=> '',
				'shipping_phone' 		=> '',
				'shipping_address' 		=> '',
				'shipping_area' 		=> '',
				'shipping_city' 		=> '',
				'shipping_province' 	=> '',
				'shipping_post_code' 	=> '',
				'shipping_country' 		=> '',
				'last_modified' 		=> '',
				'register_session_id' 	=> '',
				'user_name' 			=> '',
				'ordprdseq' 			=> '',
				'store_id' 				=> '',
				'marked_as_complete'	=> '',
				'is_tracked' 			=> '',
				'store_so_number'		=> '',
				'is_deleted_from_picklist' => '',
				'deleted_from_picklist_by' => '',
				'dropshipper' 			=> '',
				'dropshipper_note' 		=> '',
				'dropshipper_address' 	=> '',
				'is_shipped' 			=> '',
				'due_date' 				=> '',
				'received_date' 		=> '',
				'salesmen_id' 			=> '',
				'salesmen_name' 		=> '',
				'escrow_amount' 		=> '',
				'is_acknowledge' 		=> '',
				'acknowledge_status' 	=> '',
				'is_label_printed' 		=> '',
				'is_invoice_printed' 	=> '',
				'total_amount_mp' 		=> '',
				'internal_do_number'	=> '',
				'internal_status' 		=> '',
				'internal_so_number' 	=> '',
				'tracking_number' 		=> '',
				'courier' 				=> '',
				'username' 				=> '',
				'is_po' 				=> '',
				'picked_in' 			=> '',
				'district_cd' 			=> '',
				'sort_code' 			=> '',
				'shipment_type' 		=> '',
				'status_details' 		=> '',
				'service_fee' 			=> '',
				'source_name' 			=> '',
				'store_name' 			=> '',
				'location_name' 		=> '',
				'shipper' 				=> '',
				'tracking_no' 			=> '',
				'add_disc' 				=> '',
				'add_fee' 				=> '',
				'total_weight_in_kg' 	=> '',
				'is_cod' 				=> '',
				'items' 				=> ''
            ] );

			$jb_order = $_request;
			// do_action( 'inspect', [ 'webhook_update_order1', $jb_order ] );

			$response_status = 'failed';
			$status = 'error';

			if ( 'update-salesorder' == $_request['action'] && 
				$_request['ref_no'] && $jb_order['salesorder_id'] ) :

				if ( strpos( $_request['ref_no'], 'WOO' ) !== false ) :

					$order_id_arr = explode('-',$_request['ref_no']);

					if ( isset( $order_id_arr[1] ) && !empty( $order_id_arr[1] ) ) :

						$order = wc_get_order( $order_id_arr[1] );

						if ( $order ) :
							
							$order->update_meta_data( 'jb_webhook_update_order_sync_result', json_encode($jb_order) );

							$token   	  = woo_jb_get_jubelio_token();
							$new_jb_order = woo_jb_get_jubelio_order( $jb_order['salesorder_id'], $token );

							if ( $new_jb_order ) :

								$order->update_meta_data( 'jb_webhook_update_order_sync_result_new_jb_order', json_encode($new_jb_order) );

								$update = woo_jb_update_woo_order( $new_jb_order, $order );
								
								if ( $update ) :
									$response_status = 'ok';
									$status = 'success';
								endif;

							endif;

						endif;

					endif;

					// $message = '['.$status.'] action: webhook '.$_request['action'].' / jb_id: '.$_request['salesorder_id'].' / ref_no '.$_request['ref_no'];
					// woo_jb_save_log_order( $message );		

				endif;

			endif;

			$response = [
                "status" => $response_status
            ];
            wp_send_json( $response );
            exit();

        endif;

    }

}