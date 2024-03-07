<?php

class Crawler_Logger{

    private $id = null;

    private $context;

    private $text;

    private $time;

    public function __construct( $context = null, $text = null ){
        $this->context  = $context;
        $this->text     = $text;
        $this->time     = date('Y-m-d H:i:s');
    }

    public function insert(){

        global $wpdb;

        $table_name = $wpdb->prefix.'crawler_logs';

        $query = "INSERT INTO $table_name ( time, text, context ) VALUES ( %s, %s, %s )";
        $wpdb->query(
            $wpdb->prepare( $query, $this->time, $this->text, $this->context )
        );

        return true;

    }

    public function get( $args, $limit ){

        global $wpdb;
        $table_name = $wpdb->prefix.'crawler_logs';

        $page = isset( $_GET['crawler_page'] ) ? absint( $_GET['crawler_page'] ) : 1; // Get the current page from the URL parameter
        $offset = ( $page - 1 ) * $limit; // Calculate the offset based on the current page and per page

        $where = implode(' AND ', array_map(function ($value, $key) { return !empty($value) ? "`". $key . "`='" . $value . "'" : null; }, $args, array_keys($args)));
        $query = "SELECT * FROM $table_name";
        $query .= $where ? " WHERE $where " : '';
        $query .= " ORDER BY id DESC LIMIT $limit";
        $query .= $page > 1 ? " OFFSET $offset " : '';
        
        return $wpdb->get_results( $query );
        
    }

    public function get_count( $args ){
        global $wpdb;
        $table_name = $wpdb->prefix.'crawler_logs';

        return $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
    }

    public function delete( $log_id ){

        $this->log_id = $log_id;

    }

}