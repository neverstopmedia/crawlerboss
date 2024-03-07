<?php

class Crawler_Logger_Actions{

    public function __construct(){
        $this->init();
        $this->log_actions();
    }

    public function log_actions(){

        // Ajax hooks
        add_action( 'wp_ajax_get_logs', [$this, 'get_logs'] );
    }

    private function init(){

        // Let's make sure this only runs once.
        if( get_option('crawler_logs_db_init') )
        return false;

		global $wpdb;

        $table_name      = $wpdb->prefix.'crawler_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time varchar(255) NOT NULL,
        text text NOT NULL,
        context varchar(55) NOT NULL,
        PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        update_option( 'crawler_logs_db_init', true );

    }

    public function get_logs(){

        $args = [
            'context' => isset($_POST['context']) && !empty(isset( $_POST['context'] ) && !empty($_POST['context'])) ? $_POST['context'] : null
        ];

        $logs  = Crawler_Logger_Helper::get( $args, 200 );
        $count = is_array($logs) ? sizeof($logs) : 0;
        $output = '<div style="text-align: center; margin-top: 20px">No logs found matching your search</div>';

        if( $logs ){
            $output = ob_start();
            ?>
            <table class="crawler-logs">
                <thead>
                    <tr>
                        <th></th>
                        <th>User</th>
                        <th>Timestamp</th>
                        <th>Context</th>
                        <th>Text</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach( $logs as $key => $log ){ ?>
                <tr class="crawler-log log-<?php echo $key ?>" data-id="<?php echo $log->id ?>">
                    <td class="crawler-log-id">#<?php echo $log->id ?></td>
                    <td class="crawler-log-time"><?php echo $log->time ?></td>
                    <td class="crawler-log-context"><?php echo $log->context ?></td>
                    <td class="crawler-log-text" title="<?php echo $log->text ?>"><?php echo $log->text ?></td>
                </tr>
                <?php } ?>
                </tbody>
            </table>
            <?php
            $output = ob_get_clean();
        }
        
        wp_send_json( ['html' => $output, 'count' => $count] );

    }
    
}