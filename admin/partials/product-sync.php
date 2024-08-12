<div class="wrap">
    <h2><?php _e('Jubelio Product Sync'); ?></h2>
    <p><?php _e('Product Sync from Jubelio to WooCommerce'); ?></p>
    <div class="ui positive message">
        <div class="header">
            Info
        </div>
        <p>Jika data pada table dibawah kosong, itu berarti proses ambil data dari jubelio gagal, solusinya silahkan refresh halaman kembali</p>
    </div>
    <p>
        <button class="ui primary button bulk-sync-btn"><?php _e('Bulk Selected Sync'); ?></button>
        <!-- <a href="<?php echo admin_url( 'admin.php?page=jubelio-product-all-sync' ); ?>" class="ui green button bulk-all-sync-btn"><?php _e('Bulk All Sync'); ?></a> -->
    </p>
    <p>
        <label for="tampilkan_status_sync"><?php _e('Tampilkan Status Sync :'); ?></label> 
        <select name="tampilkan_status_sync" id="tampilkan_status_sync">
            <option value="<?php _e('Semua'); ?>"><?php _e('Semua'); ?></option>
            <option value="<?php _e('Sudah'); ?>"><?php _e('Sudah'); ?></option>
            <option value="<?php _e('Belum'); ?>"><?php _e('Belum'); ?></option>
        </select>
    </p>
    <table id="product-sync-table" class="ui small table">
        <thead>
            <tr>
                <th></th>
                <th><input type="checkbox" name="select_all" class="select_all"> Woo ID</th>
                <th>Jb ID</th>
                <th>Name</th>
                <th>Stok</th>                
                <th>Image</th>
                <th>Kategori</th>
                <th>Status Sync</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <th></th>
                <th><input type="checkbox" name="select_all" class="select_all"> Woo ID</th>
                <th>Jb ID</th>
                <th>Name</th>
                <th>Stok</th>                
                <th>Image</th>
                <th>Kategori</th>
                <th>Status Sync</th>
            </tr>
        </tfoot>
    </table>
</div>