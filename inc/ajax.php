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

    $last_checked = get_field( 'last_checked', $_POST['site_id'] );

    // @TODO: Check if the site has been crawled the last 7 days
    if( !empty($last_checked) && ( strtotime( $last_checked ) > strtotime('-7 day') ) ){

        ob_start();
        get_template_part( 'template-parts/site-results', null, $_POST['site_id'] );
        $markup = ob_get_clean();

        wp_send_json_success( [ 'cache' => true, 'markup' => $markup ] );

    }

    $args = array(
        'post_type'         => 'site',
        'posts_per_page'    => -1,
        'post__not_in'      => [ $_POST['site_id'] ]
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

        wp_reset_postdata();
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
function crawl_site(){
    
    $siteID = isset($_POST['site_id']) ? $_POST['site_id'] : null;
    $site_to_crawl   = isset($_POST['site_to_crawl']) ? $_POST['site_to_crawl'] : null;

    if( !$siteID )
    wp_send_json_error( [ 'message' => 'Invalid Site' ] );

    if( !$site_to_crawl )
    wp_send_json_error( [ 'message' => 'No site to check in' ] );

    checkSite( $site_to_crawl, $siteID );

}
add_action( 'wp_ajax_crawl_site', 'crawl_site' );

/**
 * This function will attempt to crawl sitemaps in different calls
 * if a sitemap has more than 50 links.
 * 
 * #Step 3.1
 * 
 * @since 1.0.0
 */
function crawl_sitemap_sets(){

    $links          = $_POST['links'];
    $sitemap        = $_POST['sitemap'];
    $domain         = $_POST['domain'];
    $siteToCrawl    = $_POST['site_to_crawl'];

    if( $results = crawlIndividualSitemap( $links, $sitemap, $domain, $siteToCrawl ) )
    wp_send_json_success( [ 'code' => 'CHUNK_COMPLETE', 'siteBreakdown' => $results ] );

    wp_send_json_error( ['message' => 'Could not find a link in the current set, moving on'] );
}
add_action( 'wp_ajax_crawl_sitemap_sets', 'crawl_sitemap_sets' );

/**
 * After a CRAWL_HEARTBEAT is called, and one sitemap was complete,
 * let's now jump to the other sitemap
 * 
 * #Step 3.2
 * 
 * @since 1.0.0
 */
function jump_to_next_sitemap(){

    $siteBreakdown = null;

    $site_to_crawl              = $_POST['site_to_crawl'];
    $site_id                    = $_POST['site_id'];
    $domain                     = $_POST['domain'];
    $site_to_crawl_sitemaps     = $_POST['site_to_crawl_sitemaps'];

    // If we found a result from checkSitemaps(), lets mark as CHUNK_COMPLETE
    $siteBreakdown = checkSitemaps( $site_to_crawl, $site_id, $domain, $site_to_crawl_sitemaps );
    wp_send_json_success( [ 'code' => 'CHUNK_COMPLETE', 'siteBreakdown' => $siteBreakdown ] );

}
add_action( 'wp_ajax_jump_to_next_sitemap', 'jump_to_next_sitemap' );

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

function get_content_ajax(){

    $template_part = $_POST['template'];
    $site_id = $_POST['site_id'];

    ob_start();
    get_template_part( $template_part, null, $site_id );

    wp_send_json_success( [ 'markup' => ob_get_clean() ] );

}
add_action( 'wp_ajax_get_content_ajax', 'get_content_ajax' );