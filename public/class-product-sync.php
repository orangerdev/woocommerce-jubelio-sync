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

    public function webhook_new_product() {

        if ( isset( $_GET['woo_jb_webhook'] ) && $_GET['woo_jb_webhook'] == 'new-product' ) :

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

            // do_action( 'inspect', [ 'headers_webhook_new_product', getallheaders() ] );
            // if ( function_exists( 'apache_request_headers' ) ) :
            //     do_action( 'inspect', [ 'headers2_webhook_new_product', apache_request_headers() ] );
			// endif;
            // do_action( 'inspect', [ 'headers3_webhook_new_product', $_SERVER ] );

            $_request = wp_parse_args( $post_data, [
                'action'            => '',
                'item_group_id'     => '',
                'item_group_name'   => '',
            ] );

            $response_status = "failed";
            $status = 'error';
            $product_id = 0;

            if ( !empty( $_request['item_group_id'] ) ) :

                $item_group_id = $_request['item_group_id'];
                $jb_product_tr = [];
                $token = woo_jb_get_jubelio_token();

                if ( 'update-product' === $_request['action'] ) :
                    $product_id = woo_jb_update_woo_product_by_item_group_id( $item_group_id, $jb_product_tr, $token );
                    if ( $product_id ) :
                        $response_status = "ok";
                        $status = 'success';
                    endif;
                else:
                    $product_id = woo_jb_insert_woo_product_by_item_group_id( $item_group_id, $jb_product_tr, $token );
                    if ( $product_id ) :
                        $response_status = "ok";
                        $status = 'success';
                    endif;
                endif;

            endif;

            $message = '['.$status.'] action: webhook '.$_request['action'].' / jb_id: '.$_request['item_group_id'].' / woo_id '.$product_id;
            woo_jb_save_log_product( $message );

            $response = [
                "status" => $response_status
            ];
            wp_send_json( $response );
            exit();

        endif;

    }

    public function webhook_update_stock() {

        if ( isset( $_GET['woo_jb_webhook'] ) && $_GET['woo_jb_webhook'] == 'update-stock' ) :

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

            // do_action( 'inspect', [ 'headers_webhook_update_stock', getallheaders() ] );
            // if ( function_exists( 'apache_request_headers' ) ) :
            //      do_action( 'inspect', [ 'headers2_webhook_update_stock', apache_request_headers() ] );
			// endif;
            // do_action( 'inspect', [ 'headers3_webhook_update_stock', $_SERVER ] );

            $_request = wp_parse_args( $post_data, [
                'action'            => '',
                'item_group_id'     => '',
                'item_group_name'   => '',
            ] );

            $response_status = "failed";
            $status = 'error';

            if ( 'update-qty' === $_request['action'] &&
                !empty( $_request['item_group_id'] ) ) :

                $woo_product_id = woo_jb_get_product_id_from_jb_id( $_request['item_group_id'] );

                if ( $woo_product_id ) :

                    $token   = woo_jb_get_jubelio_token();
                    $product = woo_jb_get_jubelio_product( $_request['item_group_id'], $token );

                    $update_data = woo_jb_update_woo_product_stocks( $product, $token );
                    if ( $update_data ) :
                        $response_status = "ok";
                        $status = 'success';
                    endif;

                    $message = '['.$status.'] action: webhook '.$_request['action'].' / jb_id: '.$_request['item_group_id'];
                    woo_jb_save_log_product( $message );        

                endif;

            endif;

            $response = [
                "status" => $response_status
            ];
            wp_send_json( $response );
            exit();

        endif;

    }

    public function webhook_update_price() {

        if ( isset( $_GET['woo_jb_webhook'] ) && $_GET['woo_jb_webhook'] == 'update-price' ) :

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

            // do_action( 'inspect', [ 'headers_webhook_update_price', getallheaders() ] );
            // if ( function_exists( 'apache_request_headers' ) ) :
            //     do_action( 'inspect', [ 'headers2_webhook_update_price', apache_request_headers() ] );
			// endif;
            // do_action( 'inspect', [ 'headers3_webhook_update_price', $_SERVER ] );

            $_request = wp_parse_args( $post_data, [
                'action'            => '',
                'item_group_id'     => '',
                'item_group_name'   => '',
            ] );

            $response_status = "failed";
            $status = 'error';

            if ( 'update-price' === $_request['action'] &&
                !empty( $_request['item_group_id'] ) ) :

                $woo_product_id = woo_jb_get_product_id_from_jb_id( $_request['item_group_id'] );

                if ( $woo_product_id ) :

                    $token   = woo_jb_get_jubelio_token();
                    $product = woo_jb_get_jubelio_product( $_request['item_group_id'], $token );

                    $update_data = woo_jb_update_woo_product_prices( $product, $token );
                    if ( $update_data ) :
                        $response_status = "ok";
                        $status = 'success';
                    endif;

                    $message = '['.$status.'] action: webhook '.$_request['action'].' / jb_id: '.$_request['item_group_id'];
                    woo_jb_save_log_product( $message );        

                endif;

            endif;
            
            $response = [
                "status" => $response_status
            ];
            wp_send_json( $response );
            exit();

        endif;

    }

    public function webhook_transfer_update_stock() {

        if ( isset( $_GET['woo_jb_webhook'] ) && $_GET['woo_jb_webhook'] == 'transfer-update-stock' ) :

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

            // do_action( 'inspect', [ 'headers_webhook_transfer_update_stock', getallheaders() ] );
            // if ( function_exists( 'apache_request_headers' ) ) :
            //     do_action( 'inspect', [ 'headers2_webhook_transfer_update_stock', apache_request_headers() ] );
			// endif;
            // do_action( 'inspect', [ 'headers3_webhook_transfer_update_stock', $_SERVER ] );

            $_request = wp_parse_args( $post_data, [
                'action'            => '',
                'item_transfer_id'  => '',
                'item_transfer_no'  => '',
                'status'            => '',
                'created_by'        => '',
            ] );

            $response_status = "failed";
            $status = 'error';

            if ( 'new-stocktransfer' === $_request['action'] &&
                !empty( $_request['item_transfer_id'] ) ) :

                $token   = woo_jb_get_jubelio_token();

                $stock_transfer = woo_jb_get_jubelio_stock_transfer( $_request['item_transfer_id'], $token );

                $update_data = woo_jb_transfer_update_woo_product_stocks( $stock_transfer, $token );
                if ( $update_data ) :
                    $response_status = "ok";
                    $status = 'success';
                endif;

            endif;

            // $message = '['.$status.'] action: webhook '.$_request['action'].' / transfer_id: '.$_request['item_transfer_id'];
            // woo_jb_save_log_product( $message );

            $response = [
                "status" => $response_status
            ];
            wp_send_json( $response );
            exit();

        endif;

    }

}