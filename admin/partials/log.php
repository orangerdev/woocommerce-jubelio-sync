<div class="wrap">
    <h2 style="margin-bottom:5px"><?php _e('Jubelio Log'); ?></h2>
    <?php
    $log_files      = woo_jb_get_log_files();
    $log_file_name  = end($log_files);
    if ( isset( $_GET['log_filename'] ) && !empty( $_GET['log_filename'] ) ) :
        $log_file_name = $_GET['log_filename'];
    endif;
    ?>
    <form action="<?php echo admin_url( "admin.php?page=jubelio-log" ); ?>" class="ui form">
        <div class="ui fluid action input">
            <select name="log_filename">
                <option value="">-Pilih File Log-</option>
                <?php
                foreach ( $log_files as $log_file ) :
                ?>
                    <option value="<?php echo $log_file; ?>" <?php selected( $log_file_name, $log_file, true ); ?>><?php echo $log_file; ?></option>
                <?php
                endforeach;
                ?>
            </select>
            <input type="hidden" name="page" value="jubelio-log">
            <button type="submit" class="ui blue button">View</button>
        </div>
    </form>
    <div class="ui segment">
        <?php
        if ( $log_file_name ) :
            woo_jb_read_log_file( $log_file_name );
        else:
            ?>
            <p>Empty</p>
            <?php
        endif;
        ?>
    </div>
</div>