<?php

/**
 * Function that is used to simply regenerate all sitemaps for a site
 * 
 * @since 1.0.0
 */
function regenerate_sitemaps(){

    $args = array(
        'post_type'         => 'site',
        'posts_per_page'    => -1,
    );

    $query = new WP_Query( $args );
    $sites = array();

    if ( $query->have_posts() ) {
        $count = 0;
        while ( $query->have_posts() ) {
            $query->the_post();

            if( $main_sitemap = get_field('sitemap_url') ){
                setInnerSitemaps( $main_sitemap, get_the_ID() );
                $count++;
            }

        }

        wp_send_json_success( ['message' => 'Regenerated sitemaps for ' . $count . ' sites'] );
    }

    wp_send_json_error( [ 'message' => 'No sites found' ] );

}
add_action( 'wp_ajax_regenerate_sitemaps', 'regenerate_sitemaps' );

/**
 * Callback to get the list of sites
 * 
 * @since 1.0.0
 */
function get_sites_callback() {
    $term = sanitize_text_field( $_GET['q'] );
    $page = (int) $_GET['page'];

    $args = array(
        'post_type'         => 'site',
        's'                 => $term,
        'posts_per_page'    => 10,
        'paged'             => $page
    );

    $query = new WP_Query( $args );

    $results = array();
    $more = false;
    
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();

            $results[] = array(
                'id'            => get_the_ID(),
                'text'          => get_the_title(),
                'last_checked' => get_field('last_checked')
            );
        }

        $more = $query->max_num_pages > $page;

    }

    wp_send_json( compact( 'results', 'more' ) );

}
add_action( 'wp_ajax_get_sites', 'get_sites_callback' );

/**
 * Starts a single site crawl process.
 * 
 * The purpose for this is to get the list of all sites in DB, and
 * then we will use the domains to cross check them in the sitemaps of
 * the site we are trying to crawl
 * 
 * #Step 2
 * 
 * @since 1.0.0
 */
function crawl_callback(){

    // Check if the site has been crawled the last 7 days
    if( !empty($last_checked) && ( strtotime( $last_checked ) > strtotime('-7 day') ) ){

    }

    $args = array(
        'post_type'         => 'site',
        'posts_per_page'    => -1,
    );

    $query = new WP_Query( $args );
    $sites = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();

            $sites[] = [
                'id'        => get_the_ID(),
                'domain'    => get_field('domain'),
            ];
            
        }

        wp_send_json_success( $sites );
    }

    wp_send_json_error( [ 'message' => 'No sites found' ] );

}
add_action( 'wp_ajax_crawl_callback', 'crawl_callback' );

/**
 * Let's start crawling the a chunk of sites at a time inside a
 * given link in the sitemap
 * 
 * #Step 3
 * 
 * @since 1.0.0
 */
function match_sites_chunk(){
    
    $siteID = isset($_POST['site_id']) ? $_POST['site_id'] : null;
    $chunk  = isset($_POST['chunk']) ? $_POST['chunk'] : null;

    if( !$siteID )
    wp_send_json_error( [ 'message' => 'Invalid Site' ] );

    if( !$chunk )
    wp_send_json_error( [ 'message' => 'No site chunk to check with' ] );

    if( !$chunk_state = checkChunk( $chunk, $siteID ) )
    wp_send_json_error( [ 'message' => 'Something went wrong, please contact developer' ] );
    
    wp_send_json_success( [ 'message' => 'Chunk processed', 'chunk' => $chunk ] );

}
add_action( 'wp_ajax_match_sites_chunk', 'match_sites_chunk' );

/**
 * Saves the site data in the DB after crawl
 * 
 * #Step 4
 * 
 * @since 1.0.0
 */
function finalize_crawl(){

    $siteID = isset($_POST['site_id']) ? $_POST['site_id'] : null;
    $siteData  = isset($_POST['site_data']) ? $_POST['site_data'] : null;
    
    if( !$siteID )
    wp_send_json_error( [ 'message' => 'Invalid Site' ] );

    if( !$siteData )
    wp_send_json_success( [ 'message' => 'Complete, with no sites found' ] );

    // field_65cdd29bba666 = backlink_data
    update_field( 'field_65cdd29bba666', $siteData, $siteID );

    // Update the crawl date of the site
    $dt = new DateTime("now", new DateTimeZone('Asia/Dubai'));
    $dt->setTimestamp(time());
    update_field( 'last_checked', $dt->format('Y-m-d H:i:s'), $siteID );

    wp_send_json_success( [ 'message' => 'Site updated with new crawl data' ] );

}
add_action( 'wp_ajax_finalize_crawl', 'finalize_crawl' );
