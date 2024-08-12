<?php

function woo_jb_request_post( $url, $data = [], $headers = [] ) {

    $ch = curl_init( $url );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );

    if ( $headers ) :
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
    endif;
    
    $response = curl_exec( $ch );
    
    curl_close( $ch );

    return $response;

}

function woo_jb_request_get( $url, $headers = [] ) {
    
    $ch = curl_init( $url );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

    if ( $headers ) :
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
    endif;
    
    $response = curl_exec( $ch );
    
    curl_close( $ch );

    return $response;

}

function woo_jb_get_user_ip() {

    // Get real visitor IP behind CloudFlare network
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) :
        $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    endif;

    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];

    if(filter_var($client, FILTER_VALIDATE_IP)):
        $ip = $client;
    elseif(filter_var($forward, FILTER_VALIDATE_IP)):
        $ip = $forward;
    else:
        $ip = $remote;
    endif;

    return $ip;

}