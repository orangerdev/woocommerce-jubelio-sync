<?php

function woo_jb_get_jubelio_product( $id, $token ) {

    $data = [];

    if ( $id && $token ) :

        $url 	= 'https://api2.jubelio.com/inventory/items/group/'.$id;

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

function woo_jb_get_jubelio_catalog( $id, $token ) {

    $data = [];

    if ( $id && $token ) :

        $url 	= 'https://api2.jubelio.com/inventory/catalog/'.$id;

        $headers = [
            'authorization: '.$token
        ];
    
        $response   = woo_jb_request_get( $url, $headers );
        $result 	= json_decode( $response, true );
        // do_action( 'inspect', [ 'product_result', $result ] );

        if ( isset( $result['status'] ) && 'ok' === $result['status'] ) :

            $data = $result;

        endif;

    endif;

    return $data;

}

function woo_jb_get_jubelio_product_item( $id, $token ) {

    $data = [];

    if ( $id && $token ) :

        $url 	= 'https://api2.jubelio.com/inventory/items/'.$id;

        $args 	= [
            'headers' => [
                'authorization' => $token,
            ]
        ];

        $response 		= wp_remote_get( $url, $args );
        $responseBody 	= wp_remote_retrieve_body( $response );
        $result 		= json_decode( $responseBody, true );

        // do_action( 'inspect', [ 'products_result', $result ] );

        if ( is_array( $result ) && ! is_wp_error( $result ) ) :

            $data = $result;

        endif;

    endif;

    return $data;

}

function woo_jb_get_jubelio_categories( $token ) {

    $url 	= 'https://api2.jubelio.com/inventory/categories/item-categories/';

    $headers = [
        'authorization: '.$token
    ];

    $response   = woo_jb_request_get( $url, $headers );
    $result 	= json_decode( $response, true );
    // do_action( 'inspect', [ 'categories_result', $result ] );

    $data = [];
    if ( is_array( $result ) && ! is_wp_error( $result ) ) :
        foreach ( $result as $key => $value ) :
            if ( isset( $value['category_id'] ) ) :
                $k = $value['category_id'];
                $v = $value['category_name'];
                $data[$k] = $v;	
            endif;
        endforeach;
    endif;

    return $data;

}

function woo_jb_get_jubelio_categories_full( $token ) {

    $data = get_transient( 'jb_categories_full' );
    
    if ( empty( $data ) ) :

        $url = 'https://api2.jubelio.com/inventory/categories/item-categories/';

        $headers = [
            'authorization: '.$token
        ];

        $response   = woo_jb_request_get( $url, $headers );
        $result 	= json_decode( $response, true );
        // do_action( 'inspect', [ 'categories_result', $result ] );

        $data = [];
        if ( is_array( $result ) && ! is_wp_error( $result ) ) :
            foreach ( $result as $key => $value ) :
                if ( isset( $value['category_id'] ) ) :
                    $k = $value['category_id'];
                    $data[$k] = [
                        'id'        => $value['category_id'],
                        'parent_id' => $value['parent_id'],
                        'name'      => $value['category_name'],
                    ];
                endif;
            endforeach;
        endif;
        set_transient( 'jb_categories_full', $data, HOUR_IN_SECONDS );

    endif;

    return $data;

}

function woo_jb_get_jubelio_products( $_query_args, $token ) {

    $query_args = wp_parse_args( $_query_args, [
        'page' 			=> 1,
        'pageSize' 		=> 200,
        'q' 			=> ''
    ] );

    $url 	= add_query_arg( $query_args, 'https://api2.jubelio.com/inventory/items/masters' );

    $headers = [
        'authorization: '.$token
    ];

    $response   = woo_jb_request_get( $url, $headers );
    $result 	= json_decode( $response, true );
    // do_action( 'inspect', [ 'products_result', $result ] );

    $data = [];

    if ( is_array( $result ) && ! is_wp_error( $result ) ) :

        if ( isset( $result['data'] ) ) :

            $data = [
                'data'  => $result['data'],
                'total' => $result['totalCount'],
            ];

        endif;

    endif;

    return $data;

}

function woo_jb_upload_image_from_url( $url, $title = null ) {

    require_once( ABSPATH . "/wp-load.php");
    require_once( ABSPATH . "/wp-admin/includes/image.php");
    require_once( ABSPATH . "/wp-admin/includes/file.php");
    require_once( ABSPATH . "/wp-admin/includes/media.php");
    
    // Download url to a temp file
    $tmp = download_url( $url );
    if ( is_wp_error( $tmp ) ) return false;
    
    // Get the filename and extension ("photo.png" => "photo", "png")
    $filename = pathinfo($url, PATHINFO_FILENAME);
    $extension = pathinfo($url, PATHINFO_EXTENSION);
    
    // An extension is required or else WordPress will reject the upload
    if ( ! $extension ) {
        // Look up mime type, example: "/photo.png" -> "image/png"
        $mime = mime_content_type( $tmp );
        $mime = is_string($mime) ? sanitize_mime_type( $mime ) : false;
        
        // Only allow certain mime types because mime types do not always end in a valid extension (see the .doc example below)
        $mime_extensions = array(
            // mime_type         => extension (no period)
            'text/plain'         => 'txt',
            'text/csv'           => 'csv',
            'application/msword' => 'doc',
            'image/jpg'          => 'jpg',
            'image/jpeg'         => 'jpeg',
            'image/gif'          => 'gif',
            'image/png'          => 'png',
            'video/mp4'          => 'mp4',
        );
        
        if ( isset( $mime_extensions[$mime] ) ) {
            // Use the mapped extension
            $extension = $mime_extensions[$mime];
        }else{
            // Could not identify extension
            @unlink($tmp);
            return false;
        }
    }
    
    // Upload by "sideloading": "the same way as an uploaded file is handled by media_handle_upload"
    $args = array(
        'name' => "$filename.$extension",
        'tmp_name' => $tmp,
    );
    
    // Do the upload
    $attachment_id = media_handle_sideload( $args, 0, $title);
    
    // Cleanup temp file
    if ( file_exists( $tmp ) ) :
        @unlink($tmp);
    endif;
    
    // Error uploading
    if ( is_wp_error($attachment_id) ) return false;
    
    // Success, return attachment ID (int)
    return (int) $attachment_id;
    
}

function woo_jb_insert_woo_product_by_item_group_id( $item_group_id, $jb_product_tr, $token ) {

    $product_id = 0;

    $jb_product = woo_jb_get_jubelio_catalog( $item_group_id, $token );
    // do_action( 'inspect', [ 'jb_product', $jb_product ] );

    if ( isset( $jb_product['item_group_id'] ) && !empty( $jb_product['item_group_id'] ) ) :

        /*
        start create new product
        */
        $product = new \WC_Product_Variable();

        // set name
        $item_name = $jb_product['item_group_name'];
        $product->set_name( $item_name ); // product title

        // set description
        $item_description = '';
        // if ( $jb_product['package_content'] ) :
        //     $item_description = $jb_product['package_content'];
        // endif;
        if ( $jb_product['description'] ) :
            $item_description = $jb_product['description'];
        endif;
        $product->set_description( $item_description ); // product description

        // set short description 
        // $item_short_description = '';
        // if ( $jb_product['description'] ) :
        //     $item_short_description = $jb_product['description'];
        // endif;
        // $product->set_short_description( $item_short_description ); // product short description

        // set note
        $item_note = '';
        if ( isset( $jb_product['notes'] ) ) :
           $item_note = $jb_product['notes'];
        endif;
        $product->set_purchase_note( $item_note ); // product note

        // set jb id
        $product->update_meta_data( 'jb_id', $jb_product['item_group_id'] );

        $default_status=get_option( '_jb_default_product_status' );
        if($default_status):
            $product->set_status($default_status);
        endif;

        // get new stocks data
        $variants = $jb_product['product_skus'];
        $variants_data = [];
        foreach ( $variants as $variant ) :
            $variant_id = $variant['item_id'];
            $variants_data[$variant_id] = $variant;
        endforeach;

        $total_base_variants = count($variants);
        $query_args = [
            'page' 			=> 1,
            'pageSize' 		=> $total_base_variants,
            'sortBy'        => 'item_group_id',
            'sortDirection' => 'ASC',
            'q' 	        => rawurlencode($item_name)
        ];

        // ada bug saat get item stocks, ada item selain item_name yang muncul
        $items_stocks = woo_jb_get_items_stocks( $query_args, $token );

        // do_action( 'inspect', [ 'is_query_args', $query_args ] );
        // do_action( 'inspect', [ 'is_items_stocks', $items_stocks ] );

        $total_locations = 0;
        $jb_active_locations_ids = woo_jb_get_active_locations_ids();
        $jb_available_active_locations = [];
        if ( isset( $items_stocks['locations'] ) ) :
            foreach ( $items_stocks['locations'] as $items_stock ) :
                if ( in_array( $items_stock['location_id'], $jb_active_locations_ids ) ) :
                    $k = $items_stock['location_id'];
                    $jb_available_active_locations[$k] = $items_stock['location_name'];
                endif;
                $total_locations++;
            endforeach;    
        endif;

        // start set attributes
        $attributes = [];
        foreach ( $jb_product['variations'] as $jb_variation ) :

            $attribute = new \WC_Product_Attribute();
            $attribute->set_name( $jb_variation['label'] );
            $attribute->set_options( $jb_variation['values'] );
            $attribute->set_position( 0 );
            $attribute->set_visible( true );
            $attribute->set_variation( true );
            $attributes[] = $attribute;

        endforeach;

        $term_loc_slugs_by_name = [];

        if ( $jb_available_active_locations ) :

            $attribute_locations = woo_jb_get_attribute_locations( $jb_available_active_locations );
            if ( $attribute_locations['tax_slug'] && $attribute_locations['terms'] ) :
                $term_loc_slugs_by_name   = $attribute_locations['slugs_by_name'];
                $attr_loc_tax_slug      = $attribute_locations['tax_slug'];
                $attr_loc_tax_name      = $attribute_locations['tax_name'];
                $attr_loc_terms_options = $attribute_locations['terms'];
                $is_taxonomy = true;
            else:
                $attr_loc_tax_slug      = wc_sanitize_taxonomy_name( "Lokasi" );
                $attr_loc_tax_name      = $attr_loc_tax_slug;
                $attr_loc_terms_options = $jb_available_active_locations;
                $is_taxonomy = false;
            endif;

            $attribute = new \WC_Product_Attribute();
            if ( $is_taxonomy ) :
                $attribute->set_id(1);
            endif;
            $attribute->set_name( $attr_loc_tax_slug );
            $attribute->set_options( $attr_loc_terms_options );
            $attribute->set_position( 0 );
            $attribute->set_visible( true );
            $attribute->set_variation( true );
            $attributes[] = $attribute;

        endif;

        if ( $attributes ) :
            $product->set_attributes( $attributes );
        endif;
        // end set attributes

        $product_id = $product->save();

        if ( $product_id ) :

            // set category
            $item_category_id = $jb_product['item_category_id'];
            $category_ids = woo_jb_create_categories($item_category_id);
            if ( $category_ids ) :
                // $product->set_category_ids( $category_ids );
                $category_ids = array_map( 'intval', $category_ids );
                wp_set_object_terms( $product_id, $category_ids, 'product_cat' );
                wp_update_term_count_now( $category_ids, 'product_cat' );
            endif;

            // set brand
            $brand_name = $jb_product['selected_brand_name'];
            $brand_id = intval(woo_jb_create_brand($brand_name));
            if ( $brand_id ) :
                wp_set_object_terms( $product_id, $brand_id, WOOCOMMERCE_JUBELIO_SYNC_TAX_BRAND );
            endif;

            $product_sell_tax_id    = $jb_product['sell_tax_id'];
            if ( empty( $product_sell_tax_id ) ) :
                $product_sell_tax_id = 1;
            endif;
            $product_sell_unit = 'pcs';
            if ( isset( $jb_product['sell_unit'] ) ) :
                $product_sell_unit = $jb_product['sell_unit'];
            endif;

            $product_package_weight = number_format( $jb_product['package_weight'] , 0, '', '' );
            $product_package_height = number_format( $jb_product['package_height'] , 0, '', '' );
            $product_package_width  = number_format( $jb_product['package_width'] , 0, '', '' );
            $product_package_length = number_format( $jb_product['package_length'] , 0, '', '' );

            $product->set_weight( $product_package_weight );
            $product->set_length( $product_package_length );
            $product->set_width( $product_package_width );
            $product->set_height( $product_package_height );
            
            $product_sales_acct_id  = $jb_product['sales_acct_id'];
            $product_tax_rate       = '';
            $product_image_ids      = [];
            /*
            start create variants
            */
            $total_save_variants = 0;

            // do_action( 'inspect', [ 'variants_data', $variants_data ] );
            // do_action( 'inspect', [ 'items_stocks_data', $items_stocks['data'] ] );
            // do_action( 'inspect', [ 'jb_available_active_locations', $jb_available_active_locations ] );

            if ( isset( $items_stocks['data'] ) ) :                
                
                $variant_count = 0;
                foreach ( $items_stocks['data'] as $item_stock_data ) :

                    $stock_item_id = $item_stock_data['item_id'];

                    if ( isset( $variants_data[$stock_item_id] ) ) :

                        $variant_data = $variants_data[$stock_item_id];

                        $stock_item_code             = $item_stock_data['item_code'];
                        $stock_item_locations        = $item_stock_data['location_stocks'];

                        $stock_item_price            = $variant_data['sell_price'];
                        $stock_item_variation_values = $variant_data['variation_values'];
                        $stock_item_images = [];
                        if ( isset( $variant_data['images'] ) ) :
                            $stock_item_images = $variant_data['images'];
                        endif;
                        $stock_item_image_ids        = [];

                        foreach ( $stock_item_images as $stock_item_image ) :
                            if ( $stock_item_image['url'] ):

                                $jb_file_name = $stock_item_image['file_name'];
                                $image_id_exist = woo_jb_get_post_id_by_meta_key_and_value( 'jb_file_name', $jb_file_name );
                                if ( $image_id_exist ) :
                                    $stock_item_image_ids[] = $image_id_exist;
                                else:
                                    $image_id = woo_jb_upload_image_from_url( $stock_item_image['url'] );
                                    if ( $image_id ) :
                                        update_post_meta( $image_id, 'jb_file_name', $jb_file_name );
                                        $stock_item_image_ids[] = $image_id;
                                    endif;
                                endif;

                            endif;
                        endforeach;

                        if ( 0 == $variant_count || empty( $product_image_ids ) ) :
                            $product_image_ids = $stock_item_image_ids;
                        endif;

                        foreach ( $stock_item_locations as $stock_item_location ) :

                            $stock_item_location_id = $stock_item_location['location_id'];

                            if ( isset( $jb_available_active_locations[$stock_item_location_id] ) ) :

                                $stock_item_location_name   = $jb_available_active_locations[$stock_item_location_id];
                                // do_action( 'inspect', [ 'stock_item_location_name1', $stock_item_location_name ] );
                                if ( isset( $term_loc_slugs_by_name[$stock_item_location_name] ) ) :
                                    $stock_item_location_name = $term_loc_slugs_by_name[$stock_item_location_name];
                                endif;
                                // do_action( 'inspect', [ 'stock_item_location_name2', $stock_item_location_name ] );

                                $stock_item_available       = $stock_item_location['available'];
                                $stock_item_sku             = $stock_item_code.'__'.$stock_item_location_id;

                                /* 
                                start create variant
                                */
                                $variation = new \WC_Product_Variation();
                                $variation->set_parent_id( $product_id );

                                // set attribute
                                foreach ( $stock_item_variation_values as $variation_value ) :
                                    $variation->set_attributes( [
                                        wc_sanitize_taxonomy_name( $variation_value['label'] ) => $variation_value['value'],
                                        $attr_loc_tax_slug => $stock_item_location_name
                                    ] );
                                endforeach;

                                // set primary data
                                $variation->set_sku( $stock_item_sku );
                                $variation->set_weight( $product_package_weight );

                                $variation->set_length( $product_package_length );
                                $variation->set_width( $product_package_width );
                                $variation->set_height( $product_package_height );

                                $variation->set_manage_stock( true );
                                $variation->set_stock_quantity( $stock_item_available );
                                $variation->set_regular_price( $stock_item_price );                            
                                
                                // set thumbnail
                                if ( $stock_item_image_ids ) :
                                    $variation->set_image_id( $stock_item_image_ids[0] );
                                    unset($stock_item_image_ids[0]);
                                    if ( $stock_item_image_ids ) :
                                        $variation->set_gallery_image_ids( $stock_item_image_ids );
                                    endif;
                                endif;
                            
                                // set meta data
                                $variation->update_meta_data( 'item_id',        $stock_item_id );
                                $variation->update_meta_data( 'item_code',      $stock_item_code );
                                $variation->update_meta_data( 'location_id',    $stock_item_location_id );
                                $variation->update_meta_data( 'tax_id',         $product_sell_tax_id );
                                $variation->update_meta_data( 'unit',           $product_sell_unit );
                                $variation->update_meta_data( 'sales_id',       $product_sales_acct_id );
                                $variation->update_meta_data( 'tax_rate',       $product_tax_rate );
            
                                $variation->save();
                                /* 
                                end create variant
                                */
                        
                                $total_save_variants++;

                            endif;

                        endforeach;

                        $variant_count++;

                    endif;

                endforeach;

            endif;
            /*
            end create variants
            */ 
            
            // set thumbnail
            $primary_product_image_ids = [];
            if ( isset( $jb_product['images'] ) && !empty( $jb_product['images'] ) ) :
                foreach ( $jb_product['images'] as $pm_key => $pm_value ) :
                    // $group_image_id = $pm_value['group_image_id'];
                    $jb_file_name = $pm_value['file_name'];
                    $pm_id_exist = woo_jb_get_post_id_by_meta_key_and_value( 'jb_file_name', $jb_file_name );
                    if ( $pm_id_exist ) :
                        $primary_product_image_ids[] = $pm_id_exist;
                    else:
                        $pm_id = woo_jb_upload_image_from_url( $pm_value['url'] );
                        if ( $pm_id ) :
                            update_post_meta( $pm_id, 'jb_file_name', $jb_file_name );
                            $primary_product_image_ids[] = $pm_id;
                        endif;
                    endif;
                endforeach;
            endif;
            if ( $primary_product_image_ids ) :
                $product_image_ids = $primary_product_image_ids;
            endif;
            if ( $product_image_ids ) :
                $product->set_image_id( $product_image_ids[0] );
                unset($product_image_ids[0]);
                if ( $product_image_ids ) :
                    $product->set_gallery_image_ids( $product_image_ids );
                endif;
            endif;

            $total_active_locations = count($jb_available_active_locations);
            $total_all_variants     = $total_base_variants * $total_active_locations;

            $product->update_meta_data( 'jb_total_locations',       $total_locations );
            $product->update_meta_data( 'jb_total_active_locations',$total_active_locations );
            $product->update_meta_data( 'jb_total_base_variants',   $total_base_variants );
            $product->update_meta_data( 'jb_total_all_variants',    $total_all_variants );
            $product->update_meta_data( 'jb_total_save_variants',   $total_save_variants );

            if ( $total_save_variants == $total_all_variants ) :
                $status_sync = 'success';
            elseif ( $total_save_variants == 0 ):
                $status_sync = 'variants_are_not_saved';
            else:
                $status_sync = 'variants_are_partially_stored';
            endif;

            $product->update_meta_data( 'jb_create_product_sync_status', $status_sync );

            $product_id2 = $product->save();

            if ( $product_id2 ) :

                $message = '['.$status_sync.'] action: create woo product / jb_id: '.$item_group_id.' / woo_id: '.$product_id2.' / variants_saved: '.$total_save_variants.' from '.$total_all_variants;

            else:

                $message = '[error] action: create woo product / jb_id: '.$item_group_id.' / woo_id: '.$product_id.' / variants_saved: '.$total_save_variants.' from '.$total_all_variants.' / message: not all data is saved';

            endif;

        else:

            $message = '[error] action: create woo product / jb_id: '.$item_group_id.' / message: failed create woo product';

        endif;
        /*
        end create new product
        */
    else:
        
        $message = '[error] action: create woo product / jb_id: '.$item_group_id.' / message: failed get jb product from jubelio';

    endif;

    if ( $message ) :
        woo_jb_save_log_product( $message );
    endif;

    return $product_id;

}

function woo_jb_update_woo_product_by_item_group_id( $item_group_id, $jb_product_tr, $token ) {

    $product_id = 0;

    $jb_product = woo_jb_get_jubelio_catalog( $item_group_id, $token );

    if ( isset( $jb_product['item_group_id'] ) && !empty( $jb_product['item_group_id'] ) ) :

        $wc_product_id = woo_jb_get_product_id_from_jb_id( $jb_product['item_group_id'] );

        if ( $wc_product_id ) :

            $product = wc_get_product( $wc_product_id );

            if ( $product ) :

                /*
                start create new product
                */
                // $product = new \WC_Product_Variable();

                // set name
                $item_name = $jb_product['item_group_name'];
                $product->set_name( $item_name ); // product title

                // set description
                $item_description = '';
                // if ( $jb_product['package_content'] ) :
                //     $item_description = $jb_product['package_content'];
                // endif;
                if ( $jb_product['description'] ) :
                    $item_description = $jb_product['description'];
                endif;
                $product->set_description( $item_description ); // product description

                // set short description 
                // $item_short_description = '';
                // if ( $jb_product['description'] ) :
                //     $item_short_description = $jb_product['description'];
                // endif;
                // $product->set_short_description( $item_short_description ); // product short description

                // set note
                $item_note = '';
                if ( isset( $jb_product['notes'] ) ) :
                   $item_note = $jb_product['notes'];
                endif;
                $product->set_purchase_note( $item_note ); // product note

                // set jb id
                $product->update_meta_data( 'jb_id', $jb_product['item_group_id'] );

                // get new stocks data
                $variants = $jb_product['product_skus'];
                $variants_data = [];
                foreach ( $variants as $variant ) :
                    $variant_id = $variant['item_id'];
                    $variants_data[$variant_id] = $variant;
                endforeach;

                $total_base_variants = count($variants);
                $query_args = [
                    'page' 			=> 1,
                    'pageSize' 		=> $total_base_variants,
                    'sortBy'        => 'item_group_id',
                    'sortDirection' => 'ASC',
                    'q' 	        => rawurlencode($item_name)
                ];

                $items_stocks = woo_jb_get_items_stocks( $query_args, $token );

                $total_locations = 0;
                $jb_active_locations_ids = woo_jb_get_active_locations_ids();
                $jb_available_active_locations = [];
                if ( isset( $items_stocks['locations'] ) ) :
                    foreach ( $items_stocks['locations'] as $items_stock ) :
                        if ( in_array( $items_stock['location_id'], $jb_active_locations_ids ) ) :
                            $k = $items_stock['location_id'];
                            $jb_available_active_locations[$k] = $items_stock['location_name'];
                        endif;
                        $total_locations++;
                    endforeach;    
                endif;

                // start set attributes
                $attributes = [];
                foreach ( $jb_product['variations'] as $jb_variation ) :
                    $attribute = new \WC_Product_Attribute();
                    $attribute->set_name( $jb_variation['label'] );
                    $attribute->set_options( $jb_variation['values'] );
                    $attribute->set_position( 0 );
                    $attribute->set_visible( true );
                    $attribute->set_variation( true );
                    $attributes[] = $attribute;
                endforeach;

                $term_loc_slugs_by_name = [];
                if ( $jb_available_active_locations ) :

                    $attribute_locations = woo_jb_get_attribute_locations( $jb_available_active_locations );
                    if ( $attribute_locations['tax_slug'] && $attribute_locations['terms'] ) :
                        $term_loc_slugs_by_name = $attribute_locations['slugs_by_name'];
                        $attr_loc_tax_slug      = $attribute_locations['tax_slug'];
                        $attr_loc_tax_name      = $attribute_locations['tax_name'];
                        $attr_loc_terms_options = $attribute_locations['terms'];
                        $is_taxonomy = true;
                    else:
                        $attr_loc_tax_slug      = wc_sanitize_taxonomy_name( "Lokasi" );
                        $attr_loc_tax_name      = $attr_loc_tax_slug;
                        $attr_loc_terms_options = $jb_available_active_locations;
                        $is_taxonomy = false;
                    endif;
        
                    $attribute = new \WC_Product_Attribute();
                    if ( $is_taxonomy ) :
                        $attribute->set_id(1);
                    endif;
                    $attribute->set_name( $attr_loc_tax_slug );
                    $attribute->set_options( $attr_loc_terms_options );
                    $attribute->set_position( 0 );
                    $attribute->set_visible( true );
                    $attribute->set_variation( true );
                    $attributes[] = $attribute;

                endif;

                if ( $attributes ) :
                    $product->set_attributes( $attributes );
                endif;
                // end set attributes
                
                $product_id = $product->save();

                if ( $product_id ) :

                    // set category
                    $item_category_id = $jb_product['item_category_id'];
                    $category_ids = woo_jb_create_categories($item_category_id);
                    if ( $category_ids ) :
                        // $product->set_category_ids( $category_ids );
                        $category_ids = array_map( 'intval', $category_ids );
                        wp_set_object_terms( $product_id, $category_ids, 'product_cat' );
                        wp_update_term_count_now( $category_ids, 'product_cat' );
                    endif;

                    // set brand
                    $brand_name = $jb_product['selected_brand_name'];
                    $brand_id = intval(woo_jb_create_brand($brand_name));
                    if ( $brand_id ) :
                        wp_set_object_terms( $product_id, $brand_id, WOOCOMMERCE_JUBELIO_SYNC_TAX_BRAND );
                    endif;

                    $product_sell_tax_id    = $jb_product['sell_tax_id'];
                    if ( empty( $product_sell_tax_id ) ) :
                        $product_sell_tax_id = 1;
                    endif;
                    $product_sell_unit = 'pcs';
                    if ( isset( $jb_product['sell_unit'] ) ) :
                        $product_sell_unit = $jb_product['sell_unit'];
                    endif;

                    $product_package_weight = number_format( $jb_product['package_weight'] , 0, '', '' );
                    $product_package_height = number_format( $jb_product['package_height'] , 0, '', '' );
                    $product_package_width  = number_format( $jb_product['package_width'] , 0, '', '' );
                    $product_package_length = number_format( $jb_product['package_length'] , 0, '', '' );

                    $product->set_weight( $product_package_weight );
                    $product->set_length( $product_package_length );
                    $product->set_width( $product_package_width );
                    $product->set_height( $product_package_height );

                    $product_sales_acct_id  = $jb_product['sales_acct_id'];
                    $product_tax_rate       = '';
                    $product_image_ids      = [];

                    /*
                    get variants
                    */
                    $variation_items = [];

                    if ( isset( $items_stocks['data'] ) ) :
                        
                        $variant_count = 0;
                        foreach ( $items_stocks['data'] as $item_stock_data ) :

                            $stock_item_id = $item_stock_data['item_id'];

                            if ( isset( $variants_data[$stock_item_id] ) ) :

                                $variant_data = $variants_data[$stock_item_id];

                                $stock_item_code             = $item_stock_data['item_code'];
                                $stock_item_locations        = $item_stock_data['location_stocks'];

                                $stock_item_price            = $variant_data['sell_price'];
                                $stock_item_variation_values = $variant_data['variation_values'];
                                $stock_item_images = [];
                                if ( isset( $variant_data['images'] ) ) :
                                    $stock_item_images           = $variant_data['images'];
                                endif;
                                $stock_item_image_ids        = [];

                                foreach ( $stock_item_images as $stock_item_image ) :
                                    if ( $stock_item_image['url'] ):

                                        $jb_file_name = $stock_item_image['file_name'];
                                        $image_id_exist = woo_jb_get_post_id_by_meta_key_and_value( 'jb_file_name', $jb_file_name );
                                        if ( $image_id_exist ) :
                                            $stock_item_image_ids[] = $image_id_exist;
                                        else:
                                            $image_id = woo_jb_upload_image_from_url( $stock_item_image['url'] );
                                            if ( $image_id ) :
                                                update_post_meta( $image_id, 'jb_file_name', $jb_file_name );
                                                $stock_item_image_ids[] = $image_id;
                                            endif;
                                        endif;

                                    endif;
                                endforeach;
        
                                if ( 0 == $variant_count || empty( $product_image_ids ) ) :
                                    $product_image_ids = $stock_item_image_ids;
                                endif;

                                foreach ( $stock_item_locations as $stock_item_location ) :

                                    $stock_item_location_id = $stock_item_location['location_id'];

                                    if ( isset( $jb_available_active_locations[$stock_item_location_id] ) ) :

                                        $stock_item_location_name   = $jb_available_active_locations[$stock_item_location_id];
                                        $stock_item_available       = $stock_item_location['available'];
                                        $stock_item_sku             = $stock_item_code.'__'.$stock_item_location_id;

                                        $variation_items[$stock_item_sku] = [
                                            'variation_values'  => $stock_item_variation_values,
                                            'location_name'     => $stock_item_location_name,
                                            'sku'               => $stock_item_sku,
                                            'package_weight'    => $product_package_weight,
                                            'available'         => $stock_item_available,
                                            'price'             => $stock_item_price,
                                            'images'            => $stock_item_image_ids,
                                            'item_id'           => $stock_item_id,
                                            'item_code'         => $stock_item_code,
                                            'location_id'       => $stock_item_location_id,
                                            'sell_tax_id'       => $product_sell_tax_id,
                                            'sell_unit'         => $product_sell_unit,
                                            'sales_acct_id'     => $product_sales_acct_id,
                                            'tax_rate'          => $product_tax_rate,
                                        ];

                                    endif;

                                endforeach;
                                $variant_count++;

                            endif;

                        endforeach;

                    endif;
                    /*
                    get variants
                    */
                    $total_save_variants = 0;

                    $wc_sku_arr = [];
                    $wc_var_ids_del = [];

                    // update variations
                    foreach ( $product->get_children() as $children ) :

                        $variation = wc_get_product( $children );
                        $sku = $variation->get_sku();
                        $wc_sku_arr[] = $sku;
    
                        if ( isset( $variation_items[$sku] ) ) :

                            $variation_item = $variation_items[$sku];

                            $stock_item_location_name = $variation_item['location_name'];
                            if ( isset( $term_loc_slugs_by_name[$stock_item_location_name] ) ) :
                                $stock_item_location_name = $term_loc_slugs_by_name[$stock_item_location_name];
                            endif;
    
                            /* 
                            start update variant
                            */
                            // $variation = new \WC_Product_Variation();
                            // $variation->set_parent_id( $product_id );

                            // set attribute
                            foreach ( $variation_item['variation_values'] as $variation_value ) :
                                $variation->set_attributes( [
                                    wc_sanitize_taxonomy_name( $variation_value['label'] ) => $variation_value['value'],
                                    $attr_loc_tax_slug => $stock_item_location_name
                                ] );
                            endforeach;

                            // set primary data
                            $variation->set_sku( $variation_item['sku'] );
                            $variation->set_weight( $variation_item['package_weight'] );

                            $variation->set_length( $product_package_length );
                            $variation->set_width( $product_package_width );
                            $variation->set_height( $product_package_height );

                            $variation->set_manage_stock( true );
                            $variation->set_stock_quantity( $variation_item['available'] );
                            $variation->set_regular_price( $variation_item['price'] );                            
                            
                            // set thumbnail
                            if ( $variation_item['images'] ) :
                                $variation->set_image_id( $variation_item['images'][0] );
                                unset($variation_item['images'][0]);
                                if ( $variation_item['images'] ) :
                                    $variation->set_gallery_image_ids( $variation_item['images'] );
                                endif;
                            endif;
                        
                            // set meta data
                            $variation->update_meta_data( 'item_id',        $variation_item['item_id'] );
                            $variation->update_meta_data( 'item_code',      $variation_item['item_code'] );
                            $variation->update_meta_data( 'location_id',    $variation_item['location_id'] );
                            $variation->update_meta_data( 'tax_id',         $variation_item['sell_tax_id'] );
                            $variation->update_meta_data( 'unit',           $variation_item['sell_unit'] );
                            $variation->update_meta_data( 'sales_id',       $variation_item['sales_acct_id'] );
                            $variation->update_meta_data( 'tax_rate',       $variation_item['tax_rate'] );
        
                            $variation->save();
                            /* 
                            end update variant
                            */
                    
                            $total_save_variants++;

                        else:
    
                            // delete not found variation
                            $wc_var_ids_del[] = $children;
                            wp_trash_post($children);

                        endif;
    
                    endforeach;
                    // update variations

                    /*
                    start insert new / delete not found variation
                    */
                    $wc_var_ids_new = [];
                    foreach ( $variation_items as $vi_key => $variation_item ) :
                        $sku = $variation_item['sku'];
                        // insert new variation
                        if ( !in_array( $sku, $wc_sku_arr ) ) :

                            /* 
                            start create variant
                            */
                            $variation = new \WC_Product_Variation();
                            $variation->set_parent_id( $product_id );

                            $stock_item_location_name = $variation_item['location_name'];
                            if ( isset( $term_loc_slugs_by_name[$stock_item_location_name] ) ) :
                                $stock_item_location_name = $term_loc_slugs_by_name[$stock_item_location_name];
                            endif;

                            // set attribute
                            foreach ( $variation_item['variation_values'] as $variation_value ) :
                                $variation->set_attributes( [
                                    wc_sanitize_taxonomy_name( $variation_value['label'] ) => $variation_value['value'],
                                    $attr_loc_tax_slug => $stock_item_location_name
                                ] );
                            endforeach;

                            // set primary data
                            $variation->set_sku( $variation_item['sku'] );
                            $variation->set_weight( $variation_item['package_weight'] );

                            $variation->set_length( $product_package_length );
                            $variation->set_width( $product_package_width );
                            $variation->set_height( $product_package_height );

                            $variation->set_manage_stock( true );
                            $variation->set_stock_quantity( $variation_item['available'] );
                            $variation->set_regular_price( $variation_item['price'] );                            
                            
                            // set thumbnail
                            if ( $variation_item['images'] ) :
                                $variation->set_image_id( $variation_item['images'][0] );
                                unset($variation_item['images'][0]);
                                if ( $variation_item['images'] ) :
                                    $variation->set_gallery_image_ids( $variation_item['images'] );
                                endif;
                            endif;
                        
                            // set meta data
                            $variation->update_meta_data( 'item_id',        $variation_item['item_id'] );
                            $variation->update_meta_data( 'item_code',      $variation_item['item_code'] );
                            $variation->update_meta_data( 'location_id',    $variation_item['location_id'] );
                            $variation->update_meta_data( 'tax_id',         $variation_item['sell_tax_id'] );
                            $variation->update_meta_data( 'unit',           $variation_item['sell_unit'] );
                            $variation->update_meta_data( 'sales_id',       $variation_item['sales_acct_id'] );
                            $variation->update_meta_data( 'tax_rate',       $variation_item['tax_rate'] );
        
                            $var_id = $variation->save();
                            /* 
                            end create variant
                            */
                    
                            $wc_var_ids_new[] = $var_id;
                            $total_save_variants++;

                        endif;
                    endforeach;
                    /*
                    end insert new / delete not found variation
                    */

                    // set thumbnail
                    $primary_product_image_ids = [];
                    if ( isset( $jb_product['images'] ) && !empty( $jb_product['images'] ) ) :
                        foreach ( $jb_product['images'] as $pm_key => $pm_value ) :
                            // $group_image_id = $pm_value['group_image_id'];
                            $jb_file_name = $pm_value['file_name'];
                            $pm_id_exist = woo_jb_get_post_id_by_meta_key_and_value( 'jb_file_name', $jb_file_name );
                            if ( $pm_id_exist ) :
                                $primary_product_image_ids[] = $pm_id_exist;
                            else:
                                $pm_id = woo_jb_upload_image_from_url( $pm_value['url'] );
                                if ( $pm_id ) :
                                    update_post_meta( $pm_id, 'jb_file_name', $jb_file_name );
                                    $primary_product_image_ids[] = $pm_id;
                                endif;
                            endif;
                        endforeach;
                    endif;
                    if ( $primary_product_image_ids ) :
                        $product_image_ids = $primary_product_image_ids;
                    endif;
                    if ( $product_image_ids ) :
                        $product->set_image_id( $product_image_ids[0] );
                        unset($product_image_ids[0]);
                        if ( $product_image_ids ) :
                            $product->set_gallery_image_ids( $product_image_ids );
                        endif;
                    endif; 

                    $total_active_locations = count($jb_available_active_locations);
                    $total_all_variants     = $total_base_variants * $total_active_locations;

                    $total_del_variants = count( $wc_var_ids_del );
                    $total_new_variants = count( $wc_var_ids_new );

                    $product->update_meta_data( 'jb_total_locations',       $total_locations );
                    $product->update_meta_data( 'jb_total_active_locations',$total_active_locations );
                    $product->update_meta_data( 'jb_total_base_variants',   $total_base_variants );
                    $product->update_meta_data( 'jb_total_all_variants',    $total_all_variants );
                    $product->update_meta_data( 'jb_total_save_variants',   $total_save_variants );
                    $product->update_meta_data( 'jb_total_del_variants',    $total_del_variants );
                    $product->update_meta_data( 'jb_total_new_variants',   $total_new_variants );

                    if ( $total_save_variants == $total_all_variants ) :
                        $status_sync = 'success';
                    elseif ( $total_save_variants == 0 ):
                        $status_sync = 'variants_are_not_saved';
                    else:
                        $status_sync = 'variants_are_partially_stored';
                    endif;

                    $product->update_meta_data( 'jb_update_product_sync_status', $status_sync );

                    $product_id2 = $product->save();

                    if ( $product_id2 ) :

                        $message = '['.$status_sync.'] action: update woo product / jb_id: '.$item_group_id.' / woo_id: '.$product_id2.' / variants_saved: '.$total_save_variants.' from '.$total_all_variants;

                    else:

                        $message = '[error] action: update woo product / jb_id: '.$item_group_id.' / woo_id: '.$product_id.' / variants_saved: '.$total_save_variants.' from '.$total_all_variants.' / message: not all data is saved';

                    endif;

                else:

                    $message = '[error] action: update woo product / jb_id: '.$item_group_id.' / message: failed update woo product';

                endif;
                /*
                end create new product
                */

            else:

                $message = '[error] action: update woo product / jb_id: '.$item_group_id.' / message: woo product not found, will create woo product instead';
                woo_jb_save_log_product( $message );
                $message = '';

                $product_id = woo_jb_insert_woo_product_by_item_group_id( $item_group_id, $jb_product_tr, $token );

            endif;

        else:

            $message = '[error] action: update woo product / jb_id: '.$item_group_id.' / message: woo product not found, will create woo product instead';
            woo_jb_save_log_product( $message );
            $message = '';

            $product_id = woo_jb_insert_woo_product_by_item_group_id( $item_group_id, $jb_product_tr, $token );

        endif;

    else:
                
        $message = '[error] action: update woo product / jb_id: '.$item_group_id.' / message: failed get jb product from jubelio';

    endif;

    if ( $message ) :
        woo_jb_save_log_product( $message );
    endif;

    return $product_id;

}

function woo_jb_create_categories( $item_category_id ) {

    $category_ids = [];

    if ( $item_category_id ) :

        $jb_categories 	= get_transient( 'jb_categories' );

        if ( isset( $jb_categories[$item_category_id] ) ) :

            $cat_name = $jb_categories[$item_category_id]['name'];

            // cat 1
            $wp_cat_id = 0;
            $wp_cat = term_exists( $cat_name, 'product_cat' );
            if ( $wp_cat === 0 || $wp_cat === null ) :
                $wp_cat = wp_insert_term(
                    $cat_name, 
                    'product_cat'
                );
                if ( isset( $wp_cat['term_id'] ) ) :
                    $wp_cat_id = $wp_cat['term_id'];
                    $category_ids[] = $wp_cat_id;
                endif;
            else:
                if ( isset( $wp_cat['term_id'] ) ) :
                    $wp_cat_id = $wp_cat['term_id'];
                    $category_ids[] = $wp_cat_id;
                else:
                    $wp_cat_id = $wp_cat;
                    $category_ids[] = $wp_cat_id;
                endif;
            endif;

            $cat_parent_name = $cat_parent_parent_name = '';
            $cat_parent_id = $jb_categories[$item_category_id]['parent_id'];
            if ( $cat_parent_id && isset( $jb_categories[$cat_parent_id] ) ) :

                $cat_parent = $jb_categories[$cat_parent_id];
                $cat_parent_name = $cat_parent['name'];

                // cat 2
                $wp_cat_parent_id = 0;
                $wp_cat_parent = term_exists( $cat_parent_name, 'product_cat' );
                if ( $wp_cat_parent === 0 || $wp_cat_parent === null ) :
                    $wp_cat_parent = wp_insert_term(
                        $cat_parent_name, 
                        'product_cat'
                    );
                    if ( isset( $wp_cat_parent['term_id'] ) ) :
                        $wp_cat_parent_id = $wp_cat_parent['term_id'];
                        $category_ids[] = $wp_cat_parent_id;

                        if ( $wp_cat_id ) :
                            $wp_cat_parent_update = wp_update_term( 
                                $wp_cat_id, 
                                'product_cat', 
                                [
                                    'parent' => $wp_cat_parent_id,   
                                ]
                            );
                        endif;

                    endif;
                else:
                    if ( isset( $wp_cat_parent['term_id'] ) ) :
                        $wp_cat_parent_id = $wp_cat_parent['term_id'];
                        $category_ids[] = $wp_cat_parent_id;
                    else:
                        $wp_cat_parent_id = $wp_cat_parent;
                        $category_ids[] = $wp_cat_parent_id;
                    endif;
                endif;

                $cat_parent_parent_id = $cat_parent['parent_id'];
                if ( $cat_parent_parent_id ) :
                    $cat_parent_parent = $jb_categories[$cat_parent_parent_id];
                    $cat_parent_parent_name = $cat_parent_parent['name'];

                    // cat 3
                    $wp_cat_parent_parent_id = 0;
                    $wp_cat_parent_parent = term_exists( $cat_parent_parent_name, 'product_cat' );
                    if ( $wp_cat_parent_parent === 0 || $wp_cat_parent_parent === null ) :
                        $wp_cat_parent_parent = wp_insert_term(
                            $cat_parent_parent_name, 
                            'product_cat'
                        ); 
                        if ( ( $wp_cat_parent_parent['term_id'] ) ) :
                            $wp_cat_parent_parent_id = $wp_cat_parent_parent['term_id'];
                            $category_ids[] = $wp_cat_parent_parent_id;

                            if ( $wp_cat_parent_id ) :
                                $wp_cat_parent_update = wp_update_term( 
                                    $wp_cat_parent_id, 
                                    'product_cat', 
                                    [
                                        'parent' => $wp_cat_parent_parent_id,   
                                    ]
                                );
                            endif;

                        endif;               
                    else:
                        if ( isset( $wp_cat_parent_parent['term_id'] ) ) :
                            $wp_cat_parent_parent_id = $wp_cat_parent_parent['term_id'];
                            $category_ids[] = $wp_cat_parent_parent_id;
                        else:
                            $wp_cat_parent_parent_id = $wp_cat_parent_parent;
                            $category_ids[] = $wp_cat_parent_parent_id;
                        endif;
                    endif;

                endif;

            endif;

        endif;

    endif;

    return $category_ids;

}

function woo_jb_create_brand( $cat_name ) {

    $wp_cat_id = 0;
    if ( $cat_name ) :
        $wp_cat = term_exists( $cat_name, WOOCOMMERCE_JUBELIO_SYNC_TAX_BRAND );
        // do_action( 'inspect', [ 'brand_wp_cat_exist', $wp_cat ] );
        if ( $wp_cat === 0 || $wp_cat === null ) :
            $wp_cat = wp_insert_term(
                $cat_name, 
                WOOCOMMERCE_JUBELIO_SYNC_TAX_BRAND
            );
            // do_action( 'inspect', [ 'brand_wp_cat_insert', $wp_cat ] );
            if ( !is_wp_error( $wp_cat ) ) :
                if ( isset( $wp_cat['term_id'] ) ) :
                    $wp_cat_id = $wp_cat['term_id'];
                endif;
            else:
                $error = $wp_cat->get_error_message();
                // do_action( 'inspect', [ 'product_brand_error', $error ] );
            endif;
        else:
            if ( isset( $wp_cat['term_id'] ) ) :
                $wp_cat_id = $wp_cat['term_id'];
            else:
                $wp_cat_id = $wp_cat;
            endif;
        endif;
    endif;
    // do_action( 'inspect', [ 'brand_wp_cat_id', $wp_cat_id ] );

    return $wp_cat_id;

}

function woo_jb_get_post_id_by_meta_key_and_value( $key, $value ) {

    global $wpdb;

    $tbl = $wpdb->prefix.'postmeta';

    $sql = "SELECT post_id FROM $tbl WHERE meta_key = '%s' AND meta_value = '%s' LIMIT 1";

    $prepare_guery = $wpdb->prepare( $sql, $key, $value );

    $get_value = $wpdb->get_var( $prepare_guery );

    return $get_value;

}

function woo_jb_get_product_id_from_jb_id( $jb_id ) {

    $post_id = woo_jb_get_post_id_by_meta_key_and_value( 'jb_id', $jb_id );

    return $post_id;

}

function woo_jb_get_items_by_location( $_query_args ) {

    $query_args = wp_parse_args( $_query_args, [
        'page' 			=> 1,
        'pageSize' 		=> 200,
        'location_id' 	=> 0
    ] );

    $location_id = $query_args['location_id'];

    $data = [];

    if ( $location_id ) :

        unset($query_args['location_id']);

        $url    = add_query_arg( $query_args, 'https://api2.jubelio.com/inventory/items/to-sell/'.$location_id );

        $token  = woo_jb_get_jubelio_token();
        $headers = [
            'authorization: '.$token
        ];

        $response   = woo_jb_request_get( $url, $headers );
        $result     = json_decode( $response, true );

        if ( isset( $result['data'] ) ) :
            $data = $result['data'];
            // do_action( 'inspect', [ 'loc_items_result', $data ] );
        endif;

    endif;

    return $data;

}

function woo_jb_get_items_stocks( $_query_args, $token ) {

    $query_args = wp_parse_args( $_query_args, [
        'page' 			=> 1,
        'pageSize' 		=> 200,
        'sortBy'        => 'item_group_id',
        'sortDirection' => 'ASC',
        'q' 	        => ''
    ] );

    $data = [];

    $url    = add_query_arg( $query_args, 'https://api2.jubelio.com/inventory/' );
    // $url = 'https://api2.jubelio.com/inventory/?page=1&pageSize=200&sortBy=item_group_id&sortDirection=ASC&q=ZENITH%20IN%20DARK%20GREEN%20WHITE';

    $headers = [
        'authorization: '.$token
    ];

    $response = woo_jb_request_get( $url, $headers );
    $result = json_decode( $response, true );
    // do_action( 'inspect', [ 'items_stocks_response', $response ] );
    if ( isset( $result['data'] ) ) :
        $data['locations'] = $result['locations'];
        $data['data'] = $result['data'];
        $data['total'] = $result['totalCount'];
    endif;

    return $data;

}

function woo_jb_get_items_prices( $_query_args, $token ) {

    $query_args = wp_parse_args( $_query_args, [
        'page' 			=> 1,
        'pageSize' 		=> 200,
        'sortBy'        => 'item_group_id',
        'sortDirection' => 'ASC',
        'q' 	        => ''
    ] );

    $data = [];

    $url    = add_query_arg( $query_args, 'https://api2.jubelio.com/inventory/' );

    $headers = [
        'authorization: '.$token
    ];

    $response = woo_jb_request_get( $url, $headers );
    $result = json_decode( $response, true );

    if ( isset( $result['data'] ) ) :
        $data['locations'] = $result['locations'];
        $data['data'] = $result['data'];
        $data['total'] = $result['totalCount'];
        // do_action( 'inspect', [ 'items_stocks', $data ] );
    endif;

    return $data;

}

function woo_jb_update_woo_product_prices( $jb_product, $token ) {

    $update = false;

    if ( isset( $jb_product['item_group_id'] ) && !empty( $jb_product['item_group_id'] ) ) :

        $woo_product_id = woo_jb_get_product_id_from_jb_id( $jb_product['item_group_id'] );

        if ( $woo_product_id ) :

            $product = wc_get_product( $woo_product_id );

            if ( $product ) :

                // get new prices data
                $variants = $jb_product['product_skus'];

                $variations = [];
                foreach ( $variants as $variant ) :
                    $sku    = $variant['item_code'];
                    $price  = $variant['sell_price'];
                    $variations[$sku] = $price;
                endforeach;
                // get new prices data

                $total_base_variants     = count($variations);
                $jb_active_locations_ids = woo_jb_get_active_locations_ids();
                $total_active_locations  = count($jb_active_locations_ids);
                $total_all_variants      = $total_base_variants * $total_active_locations;

                $total_save_variants = 0;
                // update woo price
                foreach ( $product->get_children() as $children ) :
                    
                    $variation  = wc_get_product( $children );
                    $sku        = $variation->get_sku();
                    $sku_arr    = explode('__',$sku);
                    $base_sku   = $sku_arr[0];

                    if ( isset( $variations[$base_sku] ) ) :

                        $variation->set_regular_price( $variations[$base_sku] );
                        $variation->save();

                        $total_save_variants++;

                    endif;

                endforeach;
                // update woo price
                $update = true;

                $message = '[success] action: update woo product prices / jb_id: '.$jb_product['item_group_id'].' / woo_id: '.$woo_product_id.' / variants_saved: '.$total_save_variants.' from '.$total_all_variants;

            else:

                $message = '[error] action: update woo product prices / jb_id: '.$jb_product['item_group_id'].' / woo_id: '.$woo_product_id.' / message: product not found';

            endif;

        else:

            $message = '[error] action: update woo product prices / jb_id: '.$jb_product['item_group_id'].' / message: product not found';

        endif;

    else:

        $message = '[error] action: update woo product prices / message: failed get product from jubelio';

    endif;

    woo_jb_save_log_product( $message );

    return $update;

}

function woo_jb_update_woo_product_stocks( $jb_product, $token ) {

    $update = false;

    if ( isset( $jb_product['item_group_id'] ) ) :

        $woo_product_id = woo_jb_get_product_id_from_jb_id( $jb_product['item_group_id'] );

        if ( $woo_product_id ) :

            $product = wc_get_product( $woo_product_id );

            if ( $product ) :

                // get new stocks data
                $variants = $jb_product['product_skus'];

                $total_base_variants = count($variants);
                $query_args = [
                    'page' 			=> 1,
                    'pageSize' 		=> $total_base_variants,
                    'sortBy'        => 'item_group_id',
                    'sortDirection' => 'ASC',
                    'q' 	        => rawurlencode($jb_product['item_group_name'])
                ];

                $items_stocks   = woo_jb_get_items_stocks( $query_args, $token );

                $product_items  = [];

                /*
                get news item stock
                update when item stock exist in wc variants
                add when item stock not exist in wc variants
                delete wc variants when not exist in item stock
                */

                if ( isset( $items_stocks['data'] ) ) :
                    $items_data = $items_stocks['data'];
                    foreach ( $items_data as $item_data ) :

                        $item_code       = $item_data['item_code'];
                        $location_stocks = $item_data['location_stocks'];

                        foreach ( $location_stocks as $location_stock ) :

                            $ls_location_id         = $location_stock['location_id'];
                            $ls_available           = $location_stock['available'];
                            $sku                    = $item_code.'__'.$ls_location_id;
                            $product_items[$sku]    = $ls_available;

                        endforeach;

                    endforeach;
                endif;
                // get new stocks data

                $jb_active_locations_ids = woo_jb_get_active_locations_ids();
                $total_active_locations  = count($jb_active_locations_ids);
                $total_all_variants      = $total_base_variants * $total_active_locations;

                $total_save_variants = 0;
                // update stock
                if ( $product_items ) :
                    foreach ( $product->get_children() as $children ) :
                        
                        $variation = wc_get_product( $children );
                        $sku = $variation->get_sku();

                        if ( isset( $product_items[$sku] ) ) :

                            $variation->set_manage_stock( true );
                            $variation->set_stock_quantity( $product_items[$sku] );
                            $variation->save();

                            $total_save_variants++;

                        endif;

                    endforeach;
                endif;
                // update stock
                $update = true;

                $message = '[success] action: update woo product stocks / jb_id: '.$jb_product['item_group_id'].' / woo_id: '.$woo_product_id.' / variants_saved: '.$total_save_variants.' from '.$total_all_variants;

            else:

                $message = '[error] action: update woo product stocks / jb_id: '.$jb_product['item_group_id'].' / woo_id: '.$woo_product_id.' / message: product not found';

            endif;

        else:

            $message = '[error] action: update woo product stocks / jb_id: '.$jb_product['item_group_id'].' / message: product not found';

        endif;

    else:

        $message = '[error] action: update woo product stocks / message: failed get product from jubelio';

    endif;

    woo_jb_save_log_product( $message );

    return $update;

}

function woo_jb_transfer_update_woo_product_stocks( $stock_transfer, $token ) {

    if ( isset( $stock_transfer['item_transfer_id'] ) ) :

        $source_location_id         = $stock_transfer['source_location_id'];
        $destination_location_id    = $stock_transfer['destination_location_id'];

        $product_items = [];
        $items = $stock_transfer['items'];
        foreach ( $items as $item ) :
            $item_id = $item['item_id'];
            $product_item = woo_jb_get_jubelio_product_item( $item_id, $token );
            if ( isset( $product_item['item_group_id'] ) ) :
                $product_item_id = $product_item['item_group_id'];
                $product_items[$product_item_id] = $product_item;
            endif;
        endforeach;

        $update = false;
        foreach ( $product_items as $product_item_id => $product_item ) :
            $update = woo_jb_update_woo_product_stocks( $product_item, $token );
        endforeach;

        return $update;

    endif;

}

function woo_jb_get_jubelio_stock_transfer( $id, $token ) {

    $data = [];

    if ( $id && $token ) :

        $url 	= 'https://api2.jubelio.com/inventory/transfers/'.$id;

        $args 	= [
            'headers' => [
                'authorization' => $token,
            ]
        ];

        $response 		= wp_remote_get( $url, $args );
        $responseBody 	= wp_remote_retrieve_body( $response );
        $result 		= json_decode( $responseBody, true );

        // do_action( 'inspect', [ 'products_result', $result ] );

        if ( is_array( $result ) && ! is_wp_error( $result ) ) :

            $data = $result;

        endif;

    endif;

    return $data;

}

function woo_jb_get_attribute_locations_ids() {

    $attr_name = get_option('_shipper_location_term');
    if ( empty( $attr_name ) ) :
        $attr_name = "Lokasi";
    endif;

    $attr_pa_name = 'pa_'.wc_sanitize_taxonomy_name($attr_name);

    $attr_pa_terms = get_terms( [
        'taxonomy'   => $attr_pa_name,
        'hide_empty' => false,
    ] );
    
    $attr_terms_arr = [];
    foreach ( $attr_pa_terms as $attr_pa_term ) :
        $attr_terms_arr[$attr_pa_term->name] = $attr_pa_term->term_id;
    endforeach;

    return $attr_terms_arr;

}

function woo_jb_get_attribute_locations( $jb_available_active_locations ) {

    $attr_loc_name = get_option('_shipper_location_term');
    if ( empty( $attr_loc_name ) ) :
        $attr_loc_name = "Lokasi";
    endif;

    $attribute_terms = woo_jb_get_attribute_terms( $attr_loc_name, $jb_available_active_locations );

    return $attribute_terms;

}

function woo_jb_get_attribute_terms( $attr_name, $attr_terms ) {

    $data = [
        'tax_slug'    => '',
        'tax_name'    => '',
        'terms'       => [],
        'slugs_by_name' => [],
    ];

    $attr_name = wc_sanitize_taxonomy_name($attr_name);

    $attr_pa_name = 'pa_'.$attr_name;

    if ( taxonomy_exists( $attr_pa_name ) ) :

        $attr_pa_terms = get_terms( [
            'taxonomy'   => $attr_pa_name,
            'hide_empty' => false,
        ] );

        $attr_terms_slugs = [];
        $attr_terms_arr = [];
        foreach ( $attr_pa_terms as $attr_pa_term ) :
            $attr_terms_arr[$attr_pa_term->name] = $attr_pa_term->term_id;
            $attr_terms_slugs[$attr_pa_term->name] = $attr_pa_term->slug;
        endforeach;

        $attr_terms_options = [];
        foreach ( $attr_terms as $attr_term ) :
            if ( isset( $attr_terms_arr[$attr_term] ) ) :
                $attr_terms_options[] = $attr_terms_arr[$attr_term];
            else:
                $term = wp_insert_term( $attr_term, $attr_pa_name );
                if ( isset( $term['term_id'] ) ) :
                    $attr_terms_options[] = $term['term_id'];
                    $term_object = get_term( $term['term_id'] );
                    if ( $term_object ) :
                        $attr_terms_slugs[$attr_term] = $term_object->slug;
                    endif;
                endif;
            endif;
        endforeach;

        $data = [
            'tax_slug'      => $attr_pa_name,
            'tax_name'      => $attr_name,
            'terms'         => $attr_terms_options,
            'slugs_by_name' => $attr_terms_slugs,
        ];

    endif;

    return $data;

}