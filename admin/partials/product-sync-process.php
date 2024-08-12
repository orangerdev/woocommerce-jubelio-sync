<div class="wrap">
    <h2><?php _e('Jubelio Product Sync Process'); ?></h2>
    <p><?php _e('Product Sync Process from Jubelio to WooCommerce'); ?></p>
    <!-- <p><button class="ui primary button bulk-all-sync-now-btn"><?php _e('Bulk All Sync Now'); ?></button></p> -->
    <!-- <form method="post" id="bulk-all-sync-get-products-form">
        <input type="hidden" name="start" class="start" value="0">
        <input type="hidden" name="length" class="length" value="200">
        <input type="hidden" name="action" value="woo_jb_bulk_all_sync_get_products">
        <input type="hidden" name="security" value="<?php echo wp_create_nonce( 'woo_jb_bulk_all_sync_get_products' ); ?>">
    </form> -->
    <?php
    $item_id_arr = [];
    if ( isset( $_GET['item_ids'] ) && !empty( $_GET['item_ids'] ) ) :
        $item_id_arr = explode(',',$_GET['item_ids']);
    endif;

    if ( $item_id_arr ) :
    ?>
        <p class="bulk-all-sync-result-message">Memproses sinkronasi produk...</p>
        <table class="bulk-all-sync-result ui table">
            <!-- <tr><td>Silahkan klik tombol "Bulk All Sync Now" untuk mulai proses sinkronasi semua produk</td></tr> -->
            <?php
            $product_id = 0;
            $total_product = 0;            
            $jb_products = get_transient( 'jb_products' );
            $no = 1;
            foreach ( $item_id_arr as $item_group_id ) :

                $jb_product = [];
                if ( isset( $jb_products[$item_group_id] ) ) :
                    $jb_product = $jb_products[$item_group_id];
                endif;

                if ( $jb_product ) :

                    if ( $product_id == 0 ) :
                        $product_id = $jb_product['item_group_id'];
                    endif;

                    ?>
                    <tr class="sync-product-wr">
                        <td class="sync-product sync-product-<?php echo $jb_product['item_group_id']; ?>" data-product_id="<?php echo $jb_product['item_group_id']; ?>">
                            <?php echo $no; ?>. <span class="sync-product-status">
                                        <span class="ui orange label">Waiting</span>
                                    </span> Generate Product <?php echo $jb_product['item_name']; ?>
                        </td>
                    </tr>
                    <?php

                    $total_product++;
                    $no++;

                endif;

            endforeach;
            ?>
        </table>
        <form method="post" id="bulk-all-sync-per-product-form">
            <input type="hidden" name="current_row" class="current_row" value="1">
            <input type="hidden" name="total_all_row" class="total_all_row" value="<?php echo $total_product; ?>">
            <input type="hidden" name="product_id" class="product_id" value="<?php echo $product_id; ?>">
            <input type="hidden" name="action" value="woo_jb_bulk_all_sync_per_product">
            <input type="hidden" name="security" value="<?php echo wp_create_nonce( 'woo_jb_bulk_all_sync_per_product' ); ?>">
        </form>
    <?php
    else:
    ?>
        <p>Belum ada produk dipilih, silahkan pilih produk terlebih dulu di Table Jubelio Product Sync</p>
        <p><a class="ui primary button" href="<?php echo admin_url( 'admin.php?page=jubelio-product-sync' ); ?>">Kembali ke Table Jubelio Product Sync</a></p>
    <?php
    endif;
    ?>

</div>