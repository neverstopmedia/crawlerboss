<?php

class Crawler_Logger_Helper{

    public static function log( $context, $text ){

        $logger = new Crawler_Logger( $context, $text );
        return $logger->insert();

    }

    public static function get( $args = [], $limit = 100 ){

        $logger = new Crawler_Logger();
        return $logger->get( $args, $limit );

    }

    public static function get_count( $args = [] ){

        $logger = new Crawler_Logger();
        return $logger->get_count( $args );

    }

    public static function delete( $log_id ){

        $logger = new Crawler_Logger();
        return $logger->delete( $log_id );

    }

    public static function export_logs(){
        global $wpdb;

        // Use headers so the data goes to a file and not displayed
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="export.csv"');

        // clean out other output buffers
        ob_end_clean();

        $fp = fopen('php://output', 'w');

        // CSV/Excel header label
        $header_row = array(
            0 => 'Time',
            1 => 'Text',
            2 => 'User',
            3 => 'Context',
        );
        
        //write the header
        fputcsv($fp, $header_row);

        // retrieve any table data desired. Members is an example 
        $table_name   = $wpdb->prefix.'crawler_logs'; 
        $sql_query    = $wpdb->prepare("SELECT * FROM $table_name", 1) ;
        $rows         = $wpdb->get_results($sql_query, ARRAY_A);
        if(!empty($rows)){
            foreach($rows as $record){  
                $output_record = array(
                    $record['time'],
                    $record['text'],
                    $record['context']
                );  
                fputcsv($fp, $output_record);       
            }
        }

        fclose( $fp );
        exit;                // Stop any more exporting to the file
    }

}