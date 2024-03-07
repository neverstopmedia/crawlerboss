<?php
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @var $url - The URL to test on
 * @var $suffix - any suffix that we might want to add after the link, E.G. /sitemap_index.xml
 * 
 * Tests whether the URL returns status 3xx, 404 or 200
 * 
 * @since 1.0.0
 */
function setParentSitemap( $url, $siteID, $suffix = null ){

    // If we already fetched the sitemap before, no worries.
    if( $existingSitemap = get_field( 'sitemap_url', $siteID ) )
    return $existingSitemap;
    
    $url = $url . $suffix;

    $client = HttpClient::create();
    $response = $client->request(
        'GET',
        $url,
        [
            'max_redirects' => 0
        ]
    );
    // Let's get the status code for the request
    $statusCode = $response->getStatusCode();

    // Let's check the status code of the request and act accordingly
    if( $statusCode == 404 ){

        // @TODO: Add logic to show that this is a broken link
        return false;

    }elseif( $statusCode == 301 || $statusCode == 302 ){

        $redirectURL = $response->getInfo('redirect_url');

        // If the redirect URL contains the word sitemap in the url, then lets continue, otherwise fuck it.
        if( strpos($redirectURL, 'sitemap') == false ){
            // @TODO: Add logic to show that the link that was redirected to, is not a sitemap link
            // This could happen is /sitemap.xml was accessed and the site has 404 redirect to home page site-wide
            return false;
        }

        return setParentSitemap( $redirectURL, $siteID );

    }elseif( $statusCode == 200 ){

        update_field( 'sitemap_url', $url, $siteID );
        return $url;

    }

}

/**
 * @var $sitemapURL - The sitemap URL we are testing
 * 
 * Gets the internal sitemaps from a parent sitemap
 * 
 * @since 1.0.0
 */
function setInnerSitemaps( $sitemapURL, $siteID, $cronKey = null ){

    $client = HttpClient::create();
    $startTime = microtime(true);

    try{

        $elapsedTime = microtime(true) - $startTime;

        if($elapsedTime > 29)
        return false;

        $response = $client->request(
            'GET',
            $sitemapURL,
            [
                'max_redirects' => 0
            ]
        );

    } catch (TransportExceptionInterface $e) {
        return false;
    }
    
    $xml = simplexml_load_file($sitemapURL, null, LIBXML_COMPACT);

    $sitemaps   = [];
    $flag       = false;
    $to_skip    = ['local', 'video', 'attachment', 'news', 'category', 'tag', 'author'];

    foreach( $xml as $item ){

        // If we dont need that sitemap, lets just skip
        foreach( $to_skip as $skip ){
            if( strpos( (string)$item->loc, $skip ) !== false ){
                $flag = true;
                break;
            }
        }

        // If one of the keywrods were flagged, lets exit
        if( $flag )
        continue;

        $sitemaps[] = [
            'sitemap'       => (string)$item->loc,
            'last_modified' => (string)$item->lastmod,
        ];

    }

    if( $sitemaps ){
        update_field( 'sitemaps', $sitemaps, $siteID );
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
function checkSite( $siteToCrawl, $siteID ){

    if( !$siteToCrawl || !is_array($siteToCrawl) || empty($siteID) )
    wp_send_json_error( [ 'message' => 'Missing information' ] );

    $siteBreakdown = null;

    // Let's extract the domain and tld from the URL of the site want want to check for so we can start checking it.
    if( $domain = extractDomain(get_field( 'domain', $siteID )) ){

        // First, lets check the site, and see if we have any index links before proceeding to anything else
        // If we checked, and the domain exists, we will skip the sitemaps, otherwise we will start
        // checking the sitemaps of $siteToCrawl['id'] for $domain
        if( $siteBreakdown = checkPage( $siteToCrawl, $domain ) ){
            
            wp_send_json_success( [ 'code' => 'CHUNK_COMPLETE', 'siteBreakdown' => $siteBreakdown ] );

        }else{

            // Let's update the sitemaps again
            setInnerSitemaps( get_field( 'sitemap_url', $siteToCrawl['id'] ) , $siteToCrawl['id'] );

            // If the option is enabled, lets skip the sitemap check. E.G. Sites with big sitemaps
            if( get_field( 'skip_sitemap', $siteToCrawl['id'] ) ){
                wp_send_json_success( [ 'code' => 'CHUNK_COMPLETE', 'siteBreakdown' => false ] );
            }

            // Let's start checking the sitemaps.
            $siteToCrawlSitemaps = get_field( 'sitemaps', $siteToCrawl['id'] );

            $siteBreakdown = checkSitemaps( $siteToCrawl, $siteID, $domain, $siteToCrawlSitemaps );
            wp_send_json_success( [ 'code' => 'CHUNK_COMPLETE', 'siteBreakdown' => $siteBreakdown ] );

        }

    }
    
    wp_send_json_success( [ 'code' => 'CHUNK_COMPLETE', 'siteBreakdown' => false ] );

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
function checkSitemaps( $siteToCrawl, $siteID, $domain, $siteToCrawlSitemaps ){
    
    if( !$siteToCrawlSitemaps )
    return false;

    // Check when was the domain crawled last
    $last_checked = get_field( 'last_checked', $siteID );

    // Let's sort the sitemap to have post-sitemaps at the end
    usort($siteToCrawlSitemaps, 'sitemapSort');

    $results = false;

    foreach( $siteToCrawlSitemaps as $key => $sitemap ){

        // Check if $last_checked is empty. Meaning that we didn't crawl the domain yet
        // If we already crawled the domain, and last_checked is not empty
        // then we can check if last_modified is greater than last_checked, and only crawl if it is
        if( empty($last_checked) || ( !empty($last_checked) && ( strtotime( $sitemap['last_modified'] ) > strtotime($last_checked)  ) ) ){

            $client = HttpClient::create();
            $startTime = microtime(true);

            try{

                $elapsedTime = microtime(true) - $startTime;

                if($elapsedTime > 29)
                return false;

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

            // If we have more than 50 pages in a sitemap, lets split the tasks
            // into different calls so we dont get a 5xx response code
            if( count($sitemap_links) > 50 ){

                // At this point, we will not return a result, and match_sites_chunk will stop, and 
                // we will move onto the next step, which is splitting the sitemap links
                // and then processing them into sets of 50, then returning the result there to continue.
                wp_send_json_success( [ 
                    'code'                      => 'CRAWL_HEARTBEAT', 
                    'sitemap_links'             => $sitemap_links, 
                    'sitemap'                   => $sitemap, 
                    'domain'                    => $domain, 
                    'site_to_crawl_sitemaps'    => $siteToCrawlSitemaps
                ] );

            }else{

                if( $results = crawlIndividualSitemap( $sitemap_links, $sitemap, $domain, $siteToCrawl ) )
                return $results;

                // If we didnt find anything in the sitemap, lets remove it from the array
                unset($siteToCrawlSitemaps[$key]);

            }
      
        }

    }

    return false;

}

/**
 * Crawls an individual sitemap
 * 
 * @since 1.0.0
 */
function crawlIndividualSitemap( $sitemap_links, $sitemap, $domain, $siteToCrawl ){

    $found = false;

    foreach( $sitemap_links as $sitemap_link ){

        $siteToCheck = [
            'id'                => $siteToCrawl['id'], 
            'domain'            => is_array($sitemap_link) ? $sitemap_link['loc'] : (string)$sitemap_link->loc,
            'source'            => $sitemap['sitemap'],
            'source_modified'   => $sitemap['last_modified']
        ];

        // As soon as we find one link on a page, lets break from the loop
        // and return the results, we dont care about the other pages anymore
        if( $results = checkPage( $siteToCheck, $domain ) ){
            $found = $results;
            break;
        }
        
    }

    return $found;

}

/**
 * A function that will check a page from $siteToCrawl['domain'], to see if the $domain exists in it
 * 
 * @var $siteToCrawl - The site we want to check in
 *      $siteToCrawl['domain'] Example: https://example.com
 *      $siteToCrawl['id']
 * 
 * @var $domain - The domain we want to check for
 * 
 * @return Array|Boolean Returns an array if all went well, otherwise returns false
 * array(
 *       'referer_id'        => int,
 *       'link_from'         => string,
 *       'source'            => string,
 *       'source_modified'   => date,
 *       'link_to'           => string,
 *       'rel'               => string,
 *       'content'           => string
 * );
 * 
 * @since 1.0.0
 */
function checkPage( $siteToCrawl, $domain ){

    $client = HttpClient::create();
    $startTime = microtime(true);

    try{

        $elapsedTime = microtime(true) - $startTime;

        if ($elapsedTime > 29)
        return false;

        $response = $client->request(
            'GET',
            $siteToCrawl['domain'],
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
    return false;

    $crawler = new Crawler($content);

    // Let's get rid of all the internal links
    $crawler = $crawler->filter('body a')->reduce(function (Crawler $node, $i)use($domain) {
        return str_contains( $node->attr('href'), $domain );
    });

    if( !$crawler->count() )
    return false;

    // source & source_modified only exist if we are checking a sitemap link
    $anchorData = [
        'referer_id'        => $siteToCrawl['id'],
        'link_from'         => $siteToCrawl['domain'],
        'source'            => isset($siteToCrawl['source']) ? $siteToCrawl['source'] : null,
        'source_modified'   => isset($siteToCrawl['source_modified']) ? $siteToCrawl['source_modified'] : null,
        'link_to'           => implode(', ', $crawler->extract(['href'])),
        'rel'               => $crawler->extract(['rel']) ? implode(', ', $crawler->extract(['rel'])) : 'follow',
        'content'           => $crawler->extract(['_text']) ? implode(', ', $crawler->extract(['_text'])) : ''
    ];

    return $anchorData;

}