<?php
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Add the weekly cron job schedules for crawling sites.
 * This will split each 10 sites into one cron job, and the time between them will be
 * 10 minute intervals
 * 
 * @since 1.0.0
 */
function scheduleCrawls(){

    $splitSites = getSitesInChunks();

    foreach( $splitSites as $key => $list ){

        if( !wp_next_scheduled( 'crawl_cron_'.$key, [$list] ) ) {
            $multiplier = $key + 1;
            $time = date( 'Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) + ( 36000 * $multiplier ) );
            wp_schedule_event( strtotime($time), 'weekly', 'crawl_cron_'.$key, [$list], 'crawl_cron_'.$key );
        }

        add_action( 'crawl_cron_'.$key, 'crawlByCron', 10, 2 );
    }

}
add_action( 'init', 'scheduleCrawls' );

/**
 * Crawls the sites cron
 * 
 * @var Array - The list of sites we want to crawl in this cron
 * @var String - The cron job key
 * @since 1.0.0
 */
function crawlByCron( $list, $cronKey ){

    if( empty($list) ){
        Crawler_Logger_Helper::log( 'cron', '[FAIL] Cron list missing' );
        return false;
    }

    // Let's loop the 10 sites
    foreach( $list as $siteID ){
        $args = array(
            'post_type'         => 'site',
            'posts_per_page'    => -1,
            'post__not_in'      => [ $siteID ]
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
            
            Crawler_Logger_Helper::log( 'cron [' . $siteID . ']', '['.$cronKey.'] Crawl Started for ' . get_the_title($siteID) );

            if( $sites ){

                $newSiteData = [];

                foreach( $sites as $key => $siteToCrawl ){

                    // if we got a site breakdown, lets save it
                    if( $data = checkSiteCron( $siteToCrawl, $siteID, $cronKey ) )
                    $newSiteData[] = $data;
                    
                    // If we are in the last element and newSiteData is not empty
                    if( $newSiteData && ( $key == count($sites) - 1 ) )
                    saveSiteCron( $newSiteData, $siteID, $cronKey );

                }
            }

            wp_reset_postdata();
        }
       
    }
    
}

/**
 * @var Array - $siteToCrawl - The site we want to check in
 *      $siteToCrawl['domain'] Example: https://example.com
 *      $siteToCrawl['id']
 * 
 * @var Int - $siteID - The site ID that we want to check for
 * 
 * Crawls a site
 * 
 * @since 1.0.0
 */
function checkSiteCron( $siteToCrawl, $siteID, $cronKey ){

    if( !$siteToCrawl || !is_array($siteToCrawl) || empty($siteID) ){
        Crawler_Logger_Helper::log( 'cron', '['.$cronKey.'][FAIL] SiteID or SiteToCrawl are missing' );
        return false;
    }

    $siteBreakdown = null;

    // Let's extract the domain and tld from the URL of the site want want to check for so we can start checking it.
    if( $domain = extractDomain(get_field( 'domain', $siteID )) ){

        // First, lets check the site, and see if we have any index links before proceeding to anything else
        // If we checked, and the domain exists, we will skip the sitemaps, otherwise we will start
        // checking the sitemaps of $siteToCrawl['id'] for $domain
        if( $siteBreakdown = checkPage( $siteToCrawl, $domain ) ){
            Crawler_Logger_Helper::log( 'cron [' . $siteID . ']', '['.$cronKey.'][SUCCESS] Link was found for ' . $domain . ' in ' . $siteToCrawl['domain'] );
            return $siteBreakdown;
        }else{

            // Let's update the sitemaps again
            setInnerSitemaps( get_field( 'sitemap_url', $siteToCrawl['id'] ), $siteToCrawl['id'], $cronKey );

            // If the option is enabled, lets skip the sitemap check. E.G. Sites with big sitemaps
            if( get_field( 'skip_sitemap', $siteToCrawl['id'] ) ){
                Crawler_Logger_Helper::log( 'cron [' . $siteID . ']', '['.$cronKey.'][FAIL] No links found, and sitemap is being skipped for ' . $siteToCrawl['domain'] );
                return false;
            }

            // Let's start checking the sitemaps.
            $siteToCrawlSitemaps = get_field( 'sitemaps', $siteToCrawl['id'] );

            return checkSitemapsCron( $siteToCrawl, $siteID, $domain, $siteToCrawlSitemaps, $cronKey );

        }

    }
    
    return false;

}

/**
 * @var $siteToCrawl - The site we are looking in
 *      $siteToCrawl['domain']
 *      $siteToCrawl['id']
 * 
 * @var $siteID - The Id of the site we are looking for.
 * @var $domain - The domain of the site we are looking for. Example: example.com
 * @var $siteToCrawlSitemaps - The sitemaps of the site we are looking in
 * 
 * Checks the sitemaps of a particular site, and then run checkPage() on each link
 * 
 * @since 1.0.0
 */
function checkSitemapsCron( $siteToCrawl, $siteID, $domain, $siteToCrawlSitemaps, $cronKey ){
    
    if( !$siteToCrawlSitemaps ){
        Crawler_Logger_Helper::log( 'cron [' . $siteID . ']', '['.$cronKey.'][FAIL] ' . $siteToCrawl['domain'] . ' does not have any sitemaps' );
        return false;
    }

    // Check when was the domain crawled last
    $last_checked = get_field( 'last_checked', $siteID );

    // Let's sort the sitemap to have post-sitemaps at the end
    usort($siteToCrawlSitemaps, 'sitemapSort');

    $results = false;

    foreach( $siteToCrawlSitemaps as $key => $sitemap ){

        Crawler_Logger_Helper::log( 'cron [' . $siteID . ']', '['.$cronKey.'][WORKING] Crawling ' . $sitemap['sitemap'] );

        // Check if $last_checked is empty. Meaning that we didn't crawl the domain yet
        // If we already crawled the domain, and last_checked is not empty
        // then we can check if last_modified is greater than last_checked, and only crawl if it is
        if( empty($last_checked) || ( !empty($last_checked) && ( strtotime( $sitemap['last_modified'] ) > strtotime($last_checked)  ) ) ){

            $client = HttpClient::create();
            $startTime = microtime(true);

            try{

                $elapsedTime = microtime(true) - $startTime;

                if($elapsedTime > 29){
                    Crawler_Logger_Helper::log( 'cron [' . $siteID . ']', '['.$cronKey.'][FAIL] More than 29 seconds have passed' );
                    return false;
                }

                $response = $client->request(
                    'GET',
                    $sitemap['sitemap'],
                    [
                        'max_redirects' => 0,
                        'timeout'       => 30
                    ]
                );
    
                // Incase the sitemap is now a 404, 3xx or whatever, lets skip it
                if( $response->getStatusCode() != 200 )
                continue;

            } catch (TransportExceptionInterface $e) {
                return false;
            }

            $sitemap_links = simplexml_load_file($sitemap['sitemap'], null, LIBXML_COMPACT);

            if( $results = crawlIndividualSitemap( $sitemap_links, $sitemap, $domain, $siteToCrawl ) ){
                Crawler_Logger_Helper::log( 'cron [' . $siteID . ']', '['.$cronKey.'][SUCCESS] Link was found for ' . $domain . ' in ' . $siteToCrawl['domain'] . ' through sitemap' );
                return $results;
            }

            // If we didnt find anything in the sitemap, lets remove it from the array
            unset($siteToCrawlSitemaps[$key]);

        }

    }

    return false;

}

/**
 * Saves the site data in the DB after crawl
 * 
 * @since 1.0.0
 */
function saveSiteCron( $newSiteData, $siteID, $cronKey ){

    if( !$siteID )
    return false;

    if( !$newSiteData )
    return false;

    // Update the crawl date of the site
    $dt = new DateTime("now", new DateTimeZone('Asia/Dubai'));
    $dt->setTimestamp(time());
    update_field( 'last_checked', $dt->format('Y-m-d H:i:s'), $siteID );

    // Let's see if we have some data already
    if( $oldData = get_field( 'field_65cdd29bba666', $siteID ) ){
        $newSiteDataIds = array_column( $newSiteData, 'referer_id' );

        foreach( $oldData as $key => $oldLink ){
            // IF we have the same site on both old and new data, keep the one from the new site only
            if( in_array( $oldLink['referer_id'], $newSiteDataIds ) ){
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

        if( $oldData && $newSiteData )
        $newSiteData = array_merge( $oldData, $newSiteData );
    }

    // field_65cdd29bba666 = backlink_data
    Crawler_Logger_Helper::log( 'cron [' . $siteID . ']', '['.$cronKey.'][SUCCESS] Data updated for ' . get_the_title($siteID) );
    update_field( 'field_65cdd29bba666', $newSiteData, $siteID );

    return true;

}