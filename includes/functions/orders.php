<?php

function woo_jb_create_jubelio_order( $order, $token ) {

    $customer_name = $order->get_formatted_billing_full_name();

    $phone = $order->get_billing_phone();

    $contact_id = $order->get_meta( 'contact_id', true );
    if ( empty( $contact_id ) ) :
        $contact_id = get_user_meta( $order->get_user_id(), 'contact_id', true );
        if ( $contact_id ) :
            update_post_meta( $order->get_id(), 'contact_id', $contact_id );
        endif;
    endif;

    if ( empty( $contact_id ) ) :

        // get 
        if ( empty( $phone ) ) :
            $q = $customer_name;
        else:
            $q = $phone;
        endif;
        $query_args = [
            'q'         => rawurlencode($q),
            'page'      => 1,
            'pageSize'  => 1,
        ];
        $url    = add_query_arg( $query_args, 'https://api2.jubelio.com/contacts/customers/' );
        $headers = [
            'authorization: '.$token            
        ];
        $response_get_contact = woo_jb_request_get( $url, $headers );
        update_post_meta( $order->get_id(), 'jb_search_contact_sync_result', $response_get_contact );

        $response_get_contact = json_decode($response_get_contact,1);
        if ( isset( $response_get_contact['data'][0]['contact_id'] ) ) :
            $contact_id = $response_get_contact['data'][0]['contact_id'];
        endif;

        if ( empty( $contact_id ) ) :
            // create
            $url = 'https://api2.jubelio.com/contacts/';
            $data = [
                'contact_id'        => 0,
                'contact_name'      => $customer_name,
                'contact_type'      => 0,
                'primary_contact'   => $customer_name,
                'email'             => $order->get_billing_email(),
                'phone'             => $order->get_billing_phone(),
                's_address'         => $order->get_shipping_address_1().' '.$order->get_shipping_address_2(),
                's_area'            => $order->get_shipping_state(),
                's_city'            => $order->get_shipping_city(),
                's_province'        => $order->get_shipping_country(),
                's_post_code'       => $order->get_shipping_postcode(),
                'b_address'         => $order->get_billing_address_1().' '.$order->get_billing_address_2(),
                'b_area'            => $order->get_billing_state(),
                'b_city'            => $order->get_billing_city(),
                'b_province'        => $order->get_billing_country(),
                'b_post_code'       => $order->get_billing_postcode(),
            ];
            $headers = [
                'authorization: '.$token,
            ];
            $response_create_contact = woo_jb_request_post( $url, $data, $headers );
            update_post_meta( $order->get_id(), 'jb_create_contact_sync_result', $response_create_contact );
            
            $response_create_contact = json_decode($response_create_contact,1);
            if ( isset( $response_create_contact['status'], $response_create_contact['contact_id'] ) && 
                $response_create_contact['status'] == 'ok' &&
                !empty( $response_create_contact['contact_id'] ) ) :
                $contact_id = $response_create_contact['contact_id'];
            endif;
        endif;

        if ( $contact_id ) :
            update_post_meta( $order->get_id(), 'contact_id', $contact_id );
            update_user_meta( $order->get_user_id(), 'contact_id', $contact_id );
        endif;

    endif;

    $location_id = 0;
    $items = [];
    foreach ( $order->get_items() as $key => $item ) :

        $product        = $item->get_product();
        $item_id        = $product->get_meta('item_id',true);
        $location_id    = $product->get_meta('location_id',true);
        $tax_id         = $product->get_meta('tax_id',true);
        $unit           = $product->get_meta('unit',true);

        $serial_no      = '';
        $shipper        = $order->get_shipping_method();
        
        $disc_amount    = $item->get_subtotal()-$item->get_total();
        if ( $disc_amount > 0 ) :
            //$disc           = $disc_amount/$item->get_quantity();
            $count1         = intval($item->get_total()) / intval($item->get_subtotal());
            $count2         = $count1 * 100;
            $disc           = number_format($count2, 0,"","");
        else:
            $disc = 0;
        endif;

        $items[] = [
            'salesorder_detail_id'      => 0, //req
            'item_id'                   => $item_id, //req
            'serial_no'                 => $serial_no,
            'description'               => $item->get_name(),
            'tax_id'                    => $tax_id, //req
            'price'                     => $item->get_subtotal(), //req
            'unit'                      => $unit, //req
            'qty_in_base'               => $item->get_quantity(), //req
            'disc'                      => $disc, //req
            'disc_amount'               => $disc_amount, //req
            'tax_amount'                => $item->get_subtotal_tax(), //req
            'amount'                    => $item->get_total(), //req
            'location_id'               => $location_id, //req
            'shipper'                   => $shipper,
            'channel_order_detail_id'   => null
        ];            
    endforeach;

    $is_tax_included = false;
    $total_tax = $order->get_total_tax();
    if ( $total_tax ) :
        $is_tax_included = true;
    endif;

    $cancel_reason          = '';
    $cancel_reason_detail   = '';
    
    $channel_status         = $order->get_status();
    if ( 'waiting-delivery' === $channel_status ) :
        $channel_status = 'Ready To Ship';
    elseif ( 'in-shipping' === $channel_status ) :
        $channel_status = 'Shipped';
    endif;
    
    $shipping_cost          = $order->get_shipping_total();
    $insurance_cost         = 0;

    $shipping_full_name     = $order->get_shipping_first_name().' '.$order->get_shipping_last_name();
    $shipping_phone         = $order->get_billing_phone();
    $shipping_address       = $order->get_shipping_address_1().' '.$order->get_shipping_address_2();
    $shipping_area          = '';

    $wc_s_country           = $order->get_shipping_country();
    $wc_s_country_label = $wc_s_country;
    if ( $wc_s_country ) :
        $wc_s_country_label     = WC()->countries->countries[$wc_s_country];
    endif;
    $wc_s_state             = $order->get_shipping_state();
    $wc_s_state_label = $wc_s_state;
    if ( $wc_s_state ) :
        $wc_s_state_label       = WC()->countries->get_states($wc_s_country)[$wc_s_state];
    endif;

    $shipping_city          = $order->get_shipping_city();
    $shipping_province      = $wc_s_state_label;
    $shipping_post_code     = $order->get_shipping_postcode();
    $shipping_country       = $wc_s_country_label;
    
    $add_disc               = $order->get_discount_total();
    $add_fee                = intval($order->get_total_fees());
    $salesmen_id            = null;
    $store_id               = 0;
    $service_fee            = intval($order->get_total_fees());
    $payment_method         = $order->get_payment_method_title();

    $is_paid = false;
    $is_canceled = false;

    if ( in_array( $order->get_status(), ['processing','completed','waiting-delivery','in-shipping','shipped'] ) ) :
        $is_paid = true;
    elseif ( in_array( $order->get_status(), ['cancelled','refunded','failed'] ) ) :
        $is_canceled = true;
    endif;

    $ref_no = 'WOO-'.strval($order->get_id());

    $source = 131072;

    $body = [
        'salesorder_id'         => 0, //req
        'salesorder_no'         => '[auto]', //req
        'contact_id'            => $contact_id, //req
        'customer_name'         => $customer_name, //req
        'transaction_date'      => $order->get_date_created()->format('Y-m-d H:i:s'), //req
        'is_tax_included'       => $is_tax_included,
        'note'                  => $order->get_customer_note(),
        'sub_total'             => $order->get_subtotal(), //req
        'total_disc'            => $order->get_discount_total(), //req
        'total_tax'             => $total_tax, //req
        'grand_total'           => $order->get_total(), //req
        'ref_no'                => $ref_no,
        'location_id'           => $location_id, //req
        'source'                => $source, //req
        'is_canceled'           => $is_canceled,
        'cancel_reason'         => $cancel_reason,
        'cancel_reason_detail'  => $cancel_reason_detail,
        'channel_status'        => $channel_status,
        'shipping_cost'         => $shipping_cost,
        'insurance_cost'        => $insurance_cost,
        'is_paid'               => $is_paid,
        'shipping_full_name'    => $shipping_full_name,
        'shipping_phone'        => $shipping_phone,
        'shipping_address'      => $shipping_address,
        'shipping_area'         => $shipping_area,
        'shipping_city'         => $shipping_city,
        'shipping_province'     => $shipping_province,
        'shipping_post_code'    => $shipping_post_code,
        'shipping_country'      => $shipping_country,
        'add_disc'              => $add_disc, //req
        'add_fee'               => $add_fee, //req
        'salesmen_id'           => $salesmen_id,
        'store_id'              => $store_id,
        'service_fee'           => $service_fee, //req
        'payment_method'        => $payment_method,
        'items'                 => $items, //req
    ];

    $url = 'https://api2.jubelio.com/sales/orders/';

    $data    = json_encode( $body );
    $headers = [
        'Content-Type: application/json',
        'Authorization: '.$token,
    ];
    $responseBody   = woo_jb_request_post( $url, $data, $headers );
    $result         = json_decode( $responseBody, true );

    update_post_meta( $order->get_id(), 'jb_create_order_sync_body', $data );
    update_post_meta( $order->get_id(), 'jb_create_order_sync_result', $responseBody );

    $order_id = 0;

    if ( isset( $result['id'] ) && !empty( $result['id'] ) ) :

        $order_id = $result['id'];
        update_post_meta( $order->get_id(), 'jb_id', $order_id );

        $jb_no = '';
        $jb_order = woo_jb_get_jubelio_order( $order_id, $token );
        if ( isset($jb_order['salesorder_no']) && !empty($jb_order['salesorder_no']) ) :
            $jb_no = $jb_order['salesorder_no'];
        endif;
        update_post_meta( $order->get_id(), 'jb_no', $jb_no );

        update_post_meta( $order->get_id(), 'jb_create_order_sync_status', 'success' );

    else:

        update_post_meta( $order->get_id(), 'jb_create_order_sync_status', 'error' );

    endif;

    return $order_id;

}

function woo_jb_get_jubelio_locations( $token ) {

    $data = get_transient( 'jb_locations' );
    if ( !is_array( $data ) ) :
        $data = [];
    endif;
    
    if ( empty( $data ) ) :

        $query_args = [
            'page' => 1,
            'pageSize' => 200,
        ];

        $url = add_query_arg( $query_args, 'https://api.jubelio.com/locations/' );

        $headers = [
            'authorization: '.$token,
        ];

        $response   = woo_jb_request_get( $url, $headers );
        $result     = json_decode( $response, true );
        // do_action( 'inspect', [ 'locations_result', $result ] );

        if ( isset( $result['data'] ) ) :
            foreach ( $result['data'] as $key => $value ) :
                if ( isset( $value['location_id'] ) ) :
                    $k = $value['location_id'];
                    $v = $value['location_name'];
                    $data[$k] = $v;	
                endif;
            endforeach;
            if ( $data ) :
                set_transient( 'jb_locations', $data, DAY_IN_SECONDS );
            endif;
        endif;

    endif;

    return $data;

}

function woo_jb_get_active_locations_ids() {
    
    $locations_ids = get_option('jb_active_locations');
    if ( !is_array( $locations_ids ) ) :
        $locations_ids = [];
    endif;

    return $locations_ids;

}

function woo_jb_update_woo_order( $jb_order, $order ) {

    $update = false;

    $before_status = $order->get_status();

    $jb_status = $jb_order['wms_status'];

    $new_status = '';
    switch ( $jb_status ) :
        case 'PENDING':
            $new_status = 'on-hold';
            break;

        case 'INVOICED':
            $new_status = 'pending';
            break;

        case 'PAID':
            $new_status = 'processing';
            break;

        case 'PROCESSING':
            $new_status = 'processing';
            break;

        case 'SHIPPED':
            $new_status = 'in-shipping';
            break;

        case 'CANCELED':
            $new_status = 'cancelled';
            break;

        case 'RETURNED':
            $new_status = 'refunded';
            break;

        case 'REJECT_RETURN':
            $new_status = 'refunded';
            break;

        case 'COMPLETED':
            $new_status = 'completed';
            break;

        case 'FAILED':
            $new_status = 'failed';
            break;

        case 'Ready To Ship':
            $new_status = 'waiting-delivery';
            break;

        case 'Shipped':
            $new_status = 'in-shipping';
            break;
    endswitch;

    $order->update_meta_data('jb_order_status',$jb_status);

    $allow_update = false;

    if ( !empty( $new_status ) ) :

        if ( $jb_status === 'PAID' && !in_array( $before_status, ['processing','completed','waiting-delivery','in-shipping','shipped'] ) ) :

            $allow_update = true;

        elseif ( $jb_status === 'PENDING' && !in_array( $before_status, ['pending','on-hold'] ) ) :

            $allow_update = true;

        elseif ( $before_status !== $new_status ) :

            $allow_update = true;

        endif;

    endif;

    if ( $allow_update ) :

        $status = 'error';

        $order->set_status( $new_status, __('by webhook','wswp') );
        $order_id = $order->save();
        if ( $order_id ) :
            $update = true;
            $status = 'success';
        endif;

        $after_status = $order->get_status();

        $message = '['.$status.'] action: webhook update woo order status / jb_id: '.$jb_order['salesorder_id'].' / ref_no '.$jb_order['ref_no'].' / jb: '.$jb_status.' = '.$new_status.' / woo: '.$before_status.' to '.$after_status;
        woo_jb_save_log_order( $message );

    endif;

    return $update;

}

function woo_jb_get_jubelio_order( $id, $token ) {

    $data = [];

    if ( $id && $token ) :

        $url 	= 'https://api2.jubelio.com/sales/orders/'.$id;

        $headers = [
            'authorization: '.$token
        ];
    
        $response   = woo_jb_request_get( $url, $headers );
        $result 	= json_decode( $response, true );
        // do_action( 'inspect', [ 'product_result', $result ] );

        if ( $result ) :

            $data = $result;

        endif;

    endif;

    return $data;

}

function woo_jb_update_jubelio_order( $order, $token ) {

    $updated = false;

    $jb_id = get_post_meta( $order->get_id(), 'jb_id', true ); 
    $jb_no = get_post_meta( $order->get_id(), 'jb_no', true ); 

    if ( $jb_id && $jb_no ) :

        $customer_name = $order->get_formatted_billing_full_name();

        $phone = $order->get_billing_phone();

        $contact_id = $order->get_meta( 'contact_id', true );
        if ( empty( $contact_id ) ) :
            $contact_id = get_user_meta( $order->get_user_id(), 'contact_id', true );
            if ( $contact_id ) :
                update_post_meta( $order->get_id(), 'contact_id', $contact_id );
            endif;
        endif;

        if ( empty( $contact_id ) ) :

            // get 
            if ( empty( $phone ) ) :
                $q = $customer_name;
            else:
                $q = $phone;
            endif;
            $query_args = [
                'q'         => rawurlencode($q),
                'page'      => 1,
                'pageSize'  => 1,
            ];
            $url    = add_query_arg( $query_args, 'https://api2.jubelio.com/contacts/customers/' );
            $headers = [
                'authorization: '.$token            
            ];
            $response_get_contact = woo_jb_request_get( $url, $headers );
            $response_get_contact = json_decode($response_get_contact,1);
            update_post_meta( $order->get_id(), 'jb_search_contact_sync_result', $response_get_contact );    
            if ( isset( $response_get_contact['data'][0]['contact_id'] ) ) :
                $contact_id = $response_get_contact['data'][0]['contact_id'];
            endif;

            if ( empty( $contact_id ) ) :
                // create
                $url = 'https://api2.jubelio.com/contacts/';
                $data = [
                    'contact_id'        => 0,
                    'contact_name'      => $customer_name,
                    'contact_type'      => 0,
                    'primary_contact'   => $customer_name,
                    'email'             => $order->get_billing_email(),
                    'phone'             => $order->get_billing_phone(),
                    's_address'         => $order->get_shipping_address_1().' '.$order->get_shipping_address_2(),
                    's_area'            => $order->get_shipping_state(),
                    's_city'            => $order->get_shipping_city(),
                    's_province'        => $order->get_shipping_country(),
                    's_post_code'       => $order->get_shipping_postcode(),
                    'b_address'         => $order->get_billing_address_1().' '.$order->get_billing_address_2(),
                    'b_area'            => $order->get_billing_state(),
                    'b_city'            => $order->get_billing_city(),
                    'b_province'        => $order->get_billing_country(),
                    'b_post_code'       => $order->get_billing_postcode(),
                ];
                $headers = [
                    'authorization: '.$token,
                ];
                $response_create_contact = woo_jb_request_post( $url, $data, $headers );
                update_post_meta( $order->get_id(), 'jb_create_contact_sync_result', $response_create_contact );

                $response_create_contact = json_decode($response_create_contact,1);
                if ( isset( $response_create_contact['status'], $response_create_contact['contact_id'] ) && 
                    $response_create_contact['status'] == 'ok' &&
                    !empty( $response_create_contact['contact_id'] ) ) :
                    $contact_id = $response_create_contact['contact_id'];
                endif;
            endif;

            if ( $contact_id ) :
                update_post_meta( $order->get_id(), 'contact_id', $contact_id );
                update_user_meta( $order->get_user_id(), 'contact_id', $contact_id );
            endif;

        endif;

        $location_id = 0;
        $items = [];
        foreach ( $order->get_items() as $key => $item ) :

            $product        = $item->get_product();
            $item_id        = $product->get_meta('item_id',true);
            $location_id    = $product->get_meta('location_id',true);
            $tax_id         = $product->get_meta('tax_id',true);
            $unit           = $product->get_meta('unit',true);

            $serial_no      = '';
            $shipper        = $order->get_shipping_method();

            $disc_amount    = $item->get_subtotal()-$item->get_total();
            if ( $disc_amount > 0 ) :
                //$disc           = $disc_amount/$item->get_quantity();
                $count1         = intval($item->get_total()) / intval($item->get_subtotal());
                $count2         = $count1 * 100;
                $disc           = number_format($count2, 0,"","");
            else:
                $disc = 0;
            endif;

            $items[] = [
                'salesorder_detail_id'      => 0, //req
                'item_id'                   => $item_id, //req
                'serial_no'                 => $serial_no,
                'description'               => $item->get_name(),
                'tax_id'                    => $tax_id, //req
                'price'                     => $item->get_subtotal(), //req
                'unit'                      => $unit, //req
                'qty_in_base'               => $item->get_quantity(), //req
                'disc'                      => $disc, //req
                'disc_amount'               => $disc_amount, //req
                'tax_amount'                => $item->get_subtotal_tax(), //req
                'amount'                    => $item->get_total(), //req
                'location_id'               => $location_id, //req
                'shipper'                   => $shipper,
                'channel_order_detail_id'   => null
            ];            
        endforeach;

        $is_tax_included = false;
        $total_tax = $order->get_total_tax();
        if ( $total_tax ) :
            $is_tax_included = true;
        endif;

        $cancel_reason          = '';
        $cancel_reason_detail   = '';

        $channel_status         = $order->get_status();
        if ( 'waiting-delivery' === $channel_status ) :
            $channel_status = 'Ready To Ship';
        elseif ( 'in-shipping' === $channel_status ) :
            $channel_status = 'Shipped';
        endif;

        $shipping_cost          = $order->get_shipping_total();
        $insurance_cost         = 0;

        $shipping_full_name     = $order->get_shipping_first_name().' '.$order->get_shipping_last_name();
        $shipping_phone         = $order->get_billing_phone();
        $shipping_address       = $order->get_shipping_address_1().' '.$order->get_shipping_address_2();
        $shipping_area          = '';

        $wc_s_country           = $order->get_shipping_country();
        $wc_s_country_label = $wc_s_country;
        if ( $wc_s_country ) :
            $wc_s_country_label     = WC()->countries->countries[$wc_s_country];
        endif;
        $wc_s_state             = $order->get_shipping_state();
        $wc_s_state_label = $wc_s_state;
        if ( $wc_s_state ) :
            $wc_s_state_label       = WC()->countries->get_states($wc_s_country)[$wc_s_state];
        endif;

        $shipping_city          = $order->get_shipping_city();
        $shipping_province      = $wc_s_state_label;
        $shipping_post_code     = $order->get_shipping_postcode();
        $shipping_country       = $wc_s_country_label;
        
        $add_disc               = $order->get_discount_total();
        $add_fee                = intval($order->get_total_fees());
        $salesmen_id            = null;
        $store_id               = 0;
        $service_fee            = intval($order->get_total_fees());
        $payment_method         = $order->get_payment_method_title();

        $ref_no = 'WOO-'.strval($order->get_id());

        $source = 131072;

        $body = [
            'salesorder_id'         => $jb_id, //req
            'salesorder_no'         => strval($jb_no), //req
            'contact_id'            => $contact_id, //req
            'customer_name'         => $customer_name, //req
            'transaction_date'      => $order->get_date_created()->format('Y-m-d H:i:s'), //req
            'is_tax_included'       => $is_tax_included,
            'note'                  => $order->get_customer_note(),
            'sub_total'             => $order->get_subtotal(), //req
            'total_disc'            => $order->get_discount_total(), //req
            'total_tax'             => $total_tax, //req
            'grand_total'           => $order->get_total(), //req
            'ref_no'                => $ref_no,
            'location_id'           => $location_id, //req
            'source'                => $source, //req
            // 'cancel_reason'         => $cancel_reason,
            // 'cancel_reason_detail'  => $cancel_reason_detail,
            'channel_status'        => $channel_status,
            'shipping_cost'         => $shipping_cost,
            'insurance_cost'        => $insurance_cost,
            'shipping_full_name'    => $shipping_full_name,
            'shipping_phone'        => $shipping_phone,
            'shipping_address'      => $shipping_address,
            'shipping_area'         => $shipping_area,
            'shipping_city'         => $shipping_city,
            'shipping_province'     => $shipping_province,
            'shipping_post_code'    => $shipping_post_code,
            'shipping_country'      => $shipping_country,
            'add_disc'              => $add_disc, //req
            'add_fee'               => $add_fee, //req
            'salesmen_id'           => $salesmen_id,
            'store_id'              => $store_id,
            'service_fee'           => $service_fee, //req
            'payment_method'        => $payment_method,
            'items'                 => $items, //req
        ];

        if ( in_array( $order->get_status(), ['cancelled','refunded','failed'] ) ) :
            $body['is_canceled'] = true;
        endif;

        if ( in_array( $order->get_status(), ['processing','completed','waiting-delivery','in-shipping','shipped'] ) ) :
            $body['is_paid'] = true;
        endif;

        $url = 'https://api2.jubelio.com/sales/orders/';

        $data    = json_encode( $body );
        $headers = [
            'Content-Type: application/json',
            'Authorization: '.$token,
        ];
        $responseBody   = woo_jb_request_post( $url, $data, $headers );
        $result         = json_decode( $responseBody, true );

        update_post_meta( $order->get_id(), 'jb_update_order_sync_body', $data );
        update_post_meta( $order->get_id(), 'jb_update_order_sync_result', $responseBody );

        if ( isset( $result['id'] ) ) :

            $updated = true;
            update_post_meta( $order->get_id(), 'jb_update_order_sync_status', 'success' );

        else:

            update_post_meta( $order->get_id(), 'jb_update_order_sync_status', 'error' );

        endif;
    
    endif;
    
    return $updated;

}

function woo_jb_update_jubelio_order_status_cancel( $order, $token ) {

    $updated = false;

    if ( 'cancelled' === $order->get_status() ) :

        $order_id = 0;

        $jb_id = get_post_meta( $order->get_id(), 'jb_id', true ); 
        $jb_no = get_post_meta( $order->get_id(), 'jb_no', true ); 
    
        if ( $jb_id && $jb_no ) :
    
            $customer_name = $order->get_formatted_billing_full_name();
    
            $phone = $order->get_billing_phone();
    
            $contact_id = $order->get_meta( 'contact_id', true );
            if ( empty( $contact_id ) ) :
                $contact_id = get_user_meta( $order->get_user_id(), 'contact_id', true );
                if ( $contact_id ) :
                    update_post_meta( $order->get_id(), 'contact_id', $contact_id );
                endif;
            endif;
    
            if ( empty( $contact_id ) ) :
    
                // get 
                if ( empty( $phone ) ) :
                    $q = $customer_name;
                else:
                    $q = $phone;
                endif;
                $query_args = [
                    'q'         => rawurlencode($q),
                    'page'      => 1,
                    'pageSize'  => 1,
                ];
                $url    = add_query_arg( $query_args, 'https://api2.jubelio.com/contacts/customers/' );
                $headers = [
                    'authorization: '.$token            
                ];
                $response_get_contact = woo_jb_request_get( $url, $headers );
                $response_get_contact = json_decode($response_get_contact,1);
                update_post_meta( $order->get_id(), 'jb_search_contact_url', $url );
                update_post_meta( $order->get_id(), 'jb_search_contact_result', $response_get_contact );    
                if ( isset( $response_get_contact['data'][0]['contact_id'] ) ) :
                    $contact_id = $response_get_contact['data'][0]['contact_id'];
                endif;
    
                if ( empty( $contact_id ) ) :
                    // create
                    $url = 'https://api2.jubelio.com/contacts/';
                    $data = [
                        'contact_id'        => 0,
                        'contact_name'      => $customer_name,
                        'contact_type'      => 0,
                        'primary_contact'   => $customer_name,
                        'email'             => $order->get_billing_email(),
                        'phone'             => $order->get_billing_phone(),
                        's_address'         => $order->get_shipping_address_1().' '.$order->get_shipping_address_2(),
                        's_area'            => $order->get_shipping_state(),
                        's_city'            => $order->get_shipping_city(),
                        's_province'        => $order->get_shipping_country(),
                        's_post_code'       => $order->get_shipping_postcode(),
                        'b_address'         => $order->get_billing_address_1().' '.$order->get_billing_address_2(),
                        'b_area'            => $order->get_billing_state(),
                        'b_city'            => $order->get_billing_city(),
                        'b_province'        => $order->get_billing_country(),
                        'b_post_code'       => $order->get_billing_postcode(),
                    ];
                    $headers = [
                        'authorization: '.$token,
                    ];
                    $response_create_contact = woo_jb_request_post( $url, $data, $headers );
                    $response_create_contact = json_decode($response_create_contact,1);
                    if ( isset( $response_create_contact['status'], $response_create_contact['contact_id'] ) && 
                        $response_create_contact['status'] == 'ok' &&
                        !empty( $response_create_contact['contact_id'] ) ) :
                        $contact_id = $response_create_contact['contact_id'];
                    endif;
                endif;
    
                if ( $contact_id ) :
                    update_post_meta( $order->get_id(), 'contact_id', $contact_id );
                    update_user_meta( $order->get_user_id(), 'contact_id', $contact_id );
                endif;
    
            endif;
    
            $location_id = 0;
            $items = [];
            foreach ( $order->get_items() as $key => $item ) :
    
                $product        = $item->get_product();
                $item_id        = $product->get_meta('item_id',true);
                $location_id    = $product->get_meta('location_id',true);
                $tax_id         = $product->get_meta('tax_id',true);
                $unit           = $product->get_meta('unit',true);
    
                $serial_no      = '';
                $shipper        = $order->get_shipping_method();
                $disc_amount    = $item->get_subtotal()-$item->get_total();
                //$disc           = $disc_amount/$item->get_quantity();
                $count1         = intval($item->get_total()) / intval($item->get_subtotal());
                $count2         = $count1 * 100;
                $disc           = number_format($count2, 0,"","");
    
                $items[] = [
                    'salesorder_detail_id'      => 0, //req
                    'item_id'                   => $item_id, //req
                    'serial_no'                 => $serial_no,
                    'description'               => $item->get_name(),
                    'tax_id'                    => $tax_id, //req
                    'price'                     => $item->get_subtotal(), //req
                    'unit'                      => $unit, //req
                    'qty_in_base'               => $item->get_quantity(), //req
                    'disc'                      => $disc, //req
                    'disc_amount'               => $disc_amount, //req
                    'tax_amount'                => $item->get_subtotal_tax(), //req
                    'amount'                    => $item->get_total(), //req
                    'location_id'               => $location_id, //req
                    'shipper'                   => $shipper,
                    'channel_order_detail_id'   => null
                ];            
            endforeach;
    
            $is_tax_included = false;
            $total_tax = $order->get_total_tax();
            if ( $total_tax ) :
                $is_tax_included = true;
            endif;
    
            $cancel_reason          = '';
            $cancel_reason_detail   = '';
            $channel_status         = '';
            $shipping_cost          = $order->get_shipping_total();
            $insurance_cost         = 0;
    
            $shipping_full_name     = $order->get_shipping_first_name().' '.$order->get_shipping_last_name();
            $shipping_phone         = $order->get_billing_phone();
            $shipping_address       = $order->get_shipping_address_1().' '.$order->get_shipping_address_2();
            $shipping_area          = '';
    
            $wc_s_country           = $order->get_shipping_country();
            $wc_s_country_label = $wc_s_country;
            if ( $wc_s_country ) :
                $wc_s_country_label     = WC()->countries->countries[$wc_s_country];
            endif;
            $wc_s_state             = $order->get_shipping_state();
            $wc_s_state_label = $wc_s_state;
            if ( $wc_s_state ) :
                $wc_s_state_label       = WC()->countries->get_states($wc_s_country)[$wc_s_state];
            endif;
    
            $shipping_city          = $order->get_shipping_city();
            $shipping_province      = $wc_s_state_label;
            $shipping_post_code     = $order->get_shipping_postcode();
            $shipping_country       = $wc_s_country_label;
            
            $add_disc               = $order->get_discount_total();
            $add_fee                = intval($order->get_total_fees());
            $salesmen_id            = null;
            $store_id               = 0;
            $service_fee            = 0;
            $payment_method         = $order->get_payment_method_title();

            $ref_no = 'WOO-'.strval($order->get_id());
    
            $body = [
                'salesorder_id'         => $jb_id, //req
                'salesorder_no'         => strval($jb_no), //req
                'contact_id'            => $contact_id, //req
                'customer_name'         => $customer_name, //req
                'transaction_date'      => $order->get_date_created()->format('Y-m-d H:i:s'), //req
                'is_tax_included'       => $is_tax_included,
                'note'                  => $order->get_customer_note(),
                'sub_total'             => $order->get_subtotal(), //req
                'total_disc'            => $order->get_discount_total(), //req
                'total_tax'             => $total_tax, //req
                'grand_total'           => $order->get_total(), //req
                'ref_no'                => $ref_no,
                'location_id'           => $location_id, //req
                'source'                => 1, //req
                // 'cancel_reason'         => $cancel_reason,
                // 'cancel_reason_detail'  => $cancel_reason_detail,
                'channel_status'        => $channel_status,
                'shipping_cost'         => $shipping_cost,
                'insurance_cost'        => $insurance_cost,
                'shipping_full_name'    => $shipping_full_name,
                'shipping_phone'        => $shipping_phone,
                'shipping_address'      => $shipping_address,
                'shipping_area'         => $shipping_area,
                'shipping_city'         => $shipping_city,
                'shipping_province'     => $shipping_province,
                'shipping_post_code'    => $shipping_post_code,
                'shipping_country'      => $shipping_country,
                'add_disc'              => $add_disc, //req
                'add_fee'               => $add_fee, //req
                'salesmen_id'           => $salesmen_id,
                'store_id'              => $store_id,
                'service_fee'           => $service_fee, //req
                'payment_method'        => $payment_method,
                'items'                 => $items, //req
            ];
    
            if ( 'cancelled' === $order->get_status() ) :
                $body['is_canceled'] = true;
            endif;
    
            if ( 'processing' === $order->get_status() ) :
                $body['is_paid'] = true;
            endif;
    
            $url = 'https://api2.jubelio.com/sales/orders/';
    
            $data    = json_encode( $body );
            $headers = [
                'Content-Type: application/json',
                'Authorization: '.$token,
            ];
            $responseBody   = woo_jb_request_post( $url, $data, $headers );
            $result         = json_decode( $responseBody, true );
    
            update_post_meta( $order->get_id(), 'jb_update_status_cancel_sync_result', $responseBody );
    
            if ( isset( $result['id'] ) ) :
    
                $updated = true;
                update_post_meta( $order->get_id(), 'jb_update_status_cancel_sync_status', 'success' );
    
            else:
    
                update_post_meta( $order->get_id(), 'jb_update_status_cancel_sync_status', 'error' );
    
            endif;
        
        endif;
    
    endif;

    return $updated;

}

function woo_jb_update_jubelio_order_status_paid( $order, $token ) {

    $updated = false;

    if ( 'processing' === $order->get_status() ) :

        $jb_id = intval($order->get_meta('jb_id'));

        if ( $jb_id ) :

            $body = [ 
                'ids' => [$jb_id] 
            ];

            $url = 'https://api2.jubelio.com/sales/orders/set-as-paid';

            $data    = json_encode( $body );
            $headers = [
                'Content-Type: application/json',
                'Authorization: '.$token,
            ];
            $response = woo_jb_request_post( $url, $data, $headers );
            $result   = json_decode( $response, true );

            update_post_meta( $order->get_id(), 'jb_update_status_paid_sync_body', $data );
            update_post_meta( $order->get_id(), 'jb_update_status_paid_sync_result', $response );

            if ( isset( $result['status'] ) && $result['status'] === 'ok' ) :

                $updated = true;
                update_post_meta( $order->get_id(), 'jb_update_status_paid_sync_status', 'success' );

            else:

                update_post_meta( $order->get_id(), 'jb_update_status_paid_sync_status', 'error' );

            endif;

        else:

            update_post_meta( $order->get_id(), 'jb_update_status_paid_sync_status', 'error' );

        endif;

    endif;

    return $updated;

}

function woo_jb_update_jubelio_order_status_complete( $order, $token ) {

    $updated = false;

    if ( 'completed' === $order->get_status() ) :

        $jb_id = intval($order->get_meta('jb_id'));

        if ( $jb_id ) :

            $body = [ 
                "ids" => [$jb_id] 
            ];

            $url = 'https://api2.jubelio.com/sales/orders/mark-as-complete';

            $data    = json_encode( $body );
            $headers = [
                'Content-Type: application/json',
                'Authorization: '.$token,
            ];
            $response = woo_jb_request_post( $url, $data, $headers );
            $result   = json_decode( $response, true );

            update_post_meta( $order->get_id(), 'jb_update_status_complete_sync_body', $data );
            update_post_meta( $order->get_id(), 'jb_update_status_complete_sync_result', $response );

            if ( isset( $result['status'] ) && $result['status'] === 'ok' ) :

                $updated = true;
                update_post_meta( $order->get_id(), 'jb_update_status_complete_sync_status', 'success' );

            else:

                update_post_meta( $order->get_id(), 'jb_update_status_complete_sync_status', 'error' );

            endif;

        else:

            update_post_meta( $order->get_id(), 'jb_update_status_complete_sync_status', 'error' );

        endif;

    endif;

    return $updated;

}