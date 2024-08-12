<div class="wrap">
    <h2><?php _e('Jubelio Order Sync'); ?></h2>
    <p><?php _e('Order Sync from WooCommerce to Jubelio'); ?></p>
    <p>
        <button class="ui primary button bulk-sync-order-btn"><?php _e('Bulk Sync'); ?></button>
    </p>
    <table id="order-sync-table" class="ui celled table">
        <thead>
            <tr>
                <th><input type="checkbox" name="select_all" class="select_all"> Woo ID</th>
                <th>Jb ID</th>
                <th>Customer</th>
                <th>Qty</th>
                <th>Total</th>
                <th>Tanggal</th>
                <th>Status Sync</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <th><input type="checkbox" name="select_all" class="select_all"> Woo ID</th>
                <th>Jb ID</th>
                <th>Customer</th>
                <th>Qty</th>
                <th>Total</th>
                <th>Tanggal</th>
                <th>Status Sync</th>
            </tr>
        </tfoot>
    </table>
</div>