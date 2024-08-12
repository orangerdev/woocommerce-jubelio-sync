<?php

function woo_jb_save_log_order( $message ) {

    $log  = "[".date("Y-m-d H:i:s")."] ".$message.PHP_EOL;
    file_put_contents( WP_CONTENT_DIR.'/woo_jb/woo_jb_order_'.date("Y-m-d").'.log', $log, FILE_APPEND);

}

function woo_jb_save_log_product( $message ) {

    $log  = "[".date("Y-m-d H:i:s")."] ".$message.PHP_EOL;
    file_put_contents( WP_CONTENT_DIR.'/woo_jb/woo_jb_product_'.date("Y-m-d").'.log', $log, FILE_APPEND);

}

function woo_jb_read_log_file( $file_name ) {

    $file = WP_CONTENT_DIR.'/woo_jb/'.$file_name;

    $read_file = fopen( $file,'r') or die ('File opening failed');
    while( !feof( $read_file ) ) :
        $line = fgets($read_file);
        echo $line. "<br>";
    endwhile;
      
    fclose($read_file);

}

function woo_jb_get_log_files() {

    $files = glob( WP_CONTENT_DIR.'/woo_jb/*' );

    $file_names = [];
    foreach ( $files as $key => $value ) :
        $path_arr = explode('/woo_jb/',$value);
        $file_names[] = $path_arr[1];
    endforeach;

    return $file_names;

}