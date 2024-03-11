<?php
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

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
        wp_reset_postdata();
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
    $force_crawl = isset($_POST['force']) ? $_POST['force'] : false;

    // @TODO: Check if the site has been crawled the last 7 days
    if( !empty($last_checked) && ( strtotime( $last_checked ) > strtotime('-7 day') ) && !$force_crawl ){

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
    $newData  = isset($_POST['site_data']) ? $_POST['site_data'] : null;
    
    if( !$siteID )
    wp_send_json_error( [ 'message' => 'Invalid Site' ] );

    if( !$newData )
    wp_send_json_success( [ 'message' => 'Complete, with no sites found' ] );

    // Update the crawl date of the site
    $dt = new DateTime("now", new DateTimeZone('Asia/Dubai'));
    $dt->setTimestamp(time());
    update_field( 'last_checked', $dt->format('Y-m-d H:i:s'), $siteID );

    // Let's see if we have some data already
    if( $oldData = get_field( 'field_65cdd29bba666', $siteID ) ){
        $newDataIds = array_column( $newData, 'referer_id' );

        foreach( $oldData as $key => $oldLink ){
            // IF we have the same site on both old and new data, keep the one from the new site only
            if( in_array( $oldLink['referer_id'], $newDataIds ) ){
                unset( $oldData[$key] );
            }

            $sitemaps = get_field( 'sitemaps', $oldLink['referer_id'] );
            $sitemapsKey = null;

            foreach( $sitemaps as $sitemap_link ){

                // If source_modified of existing link is = last_modified of sitemap link
                // Then lets skip this site, otherwise, we will remove it from oldData
                if( $oldLink['source'] == $sitemap_link['sitemap'] ){

                    if( $oldLink['source_modified'] == $sitemap_link['last_modified'] ){
                        continue;
                    }

                    unset( $oldData[$key] );

                }

            }

        }

        if( $oldData && $newData )
        $newData = array_merge( $oldData, $newData );
    }

    // field_65cdd29bba666 = backlink_data
    update_field( 'field_65cdd29bba666', $newData, $siteID );

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

/**
 * Get the list of sites for a network, only if they have backlinks
 * 
 * @since 1.0.0
 */
function generate_network_graph(){

    $network = $_POST['network'];

    $args = array(
        'post_type'         => 'site',
        'posts_per_page'    => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'site_network',
                'field'    => 'id',
                'terms'    => array( $network )
            )
        ),
        'meta_query' => array(
            array(
              'key' => 'backlink_data',
              'compare' => 'EXISTS',
            ),
        ),
    );

    $query = new WP_Query( $args );
    $sites = [];

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();

            if( !$children = get_field( 'backlink_data' ) )
            continue;

            $links = null;

            foreach( $children as $child ){
                $links[] = [
                    'name' => get_the_title($child['referer_id']),
                    'value' => $child['referer_id']
                ];
            }

            $sites[] = [
                'name' => get_the_title(),
                'children' => $links
            ];

        }
        wp_reset_postdata();
        
        if( $sites );
        wp_send_json_success( ['message' => 'Chart Retrieved', 'sites' => $sites] );

    }

    wp_send_json_error( ['message' => 'No data for this network'] );

}
add_action( 'wp_ajax_generate_network_graph', 'generate_network_graph' );

/**
 * Get the list keyword distribution for a site
 * 
 * @since 1.0.0
 */
function generate_keyword_distribution_graph(){
    
    $site_id = $_POST['site_id'];

    $keyword_distribution = getKeywordDistribution( get_field( 'backlink_data', $site_id ) );

    $data = [];

    foreach( $keyword_distribution as $key => $count ){ 
        if( $key == 'count' )
        continue;
        
        $data[] = [
            'keyword'   => $key,
            'value'     => $count
        ];

    }

    if( $data );
    wp_send_json_success( ['message' => 'Chart Retrieved', 'keywords' => $data] );

    wp_send_json_error( ['message' => 'No data for this network'] );

}
add_action( 'wp_ajax_generate_keyword_distribution_graph', 'generate_keyword_distribution_graph' );

/**
 * Checks the heading structure of a given site
 * 
 * @since 1.0.0
 */
function check_heading_structure(){

    $site_id = $_POST['site_id'];

    if( !$site_id )
    wp_send_json_error( ['message' => 'Invalid site'] );
    
    if( !$sitemaps = get_field('sitemaps', $site_id) )
    wp_send_json_error( ['message' => 'No sitemaps found'] );

    $pages = [];

    foreach( $sitemaps as $sitemap ){

        // Let's not crawl post sitemaps
        if( strpos($sitemap['sitemap'], 'post') !== false )
        continue;

        $sitemap_links = simplexml_load_file($sitemap['sitemap'], null, LIBXML_COMPACT);
        $client = HttpClient::create();

        foreach( $sitemap_links as $key => $link ){

            try{

                $response = $client->request(
                    'GET',
                    $link->loc,
                    [
                        'max_redirects' => 0,
                        'timeout'       => 30
                    ]
                );
    
                if( $response->getStatusCode() != 200 )
                return false;
    
            } catch (TransportExceptionInterface $e) {
                return false;
            }
    
            if( !$content = $response->getContent() )
            continue;
    
            $crawler = new Crawler($content);
            
            if( $headings = $crawler->filterXPath('//h1 | //h2 | //h3 | //h4 | //h5 | //h6')){
                
                $structure = [];
                
                foreach( $headings as $heading ){
                    $structure[] = $heading->nodeName;
                }
                
                $isValid = validateHeadings($structure);

                $pages[] = [
                    'link'      => $link->loc,
                    'headings'  => $structure,
                    'valid'     => $isValid
                ];

            }
            
            if( !$crawler->count() )
            continue;

        }

    }

    if( $pages ){

        // Update the heading structure check date for this site
        $dt = new DateTime("now", new DateTimeZone('Asia/Dubai'));
        $dt->setTimestamp(time());

        update_field( 'heading_structure_checked', $dt->format('Y-m-d H:i:s'), $site_id );

        $validity = array_column( $pages, 'valid' );
        $validity = in_array( false, $validity ) ? 'invalid' : 'valid';
        update_field( 'validity', $validity, $site_id );

    }

    return wp_send_json_success( [ 'message' => 'Heading processing complete', 'structure' => $pages ] );

}
add_action( 'wp_ajax_check_heading_structure', 'check_heading_structure' );
