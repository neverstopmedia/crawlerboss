<?php
use Symfony\Component\HttpClient\HttpClient;

/**
 * Extract the main domain + TLD
 * 
 * @since 1.0.0
 */
function extractDomain( $url, $return_tld = true ){

    if( empty($url) )
    return false;

    $parsed_url = parse_url($url);

    if (isset($parsed_url['host'])) {
        $host = $parsed_url['host'];
        $parts = explode('.', $host);

        // Get the TLD (based on common assumptions, not always accurate)
        $tld = array_pop($parts);

        // Get the domain (excluding subdomains)
        $domain = array_pop($parts);

        return $return_tld ? $domain . '.' . $tld : $domain;

    }

    return false;
}

/**
 * Sort the post sitemaps to the end
 * 
 * @since 1.0.0
 */
function sitemapSort($a, $b) {
    // Check if either $a or $b contains the word "post"
    $aContainsPost = strpos($a['sitemap'], 'post') !== false;
    $bContainsPost = strpos($b['sitemap'], 'post') !== false;
    
    // If $a contains "post" and $b doesn't, $a should be after $b
    if ($aContainsPost && !$bContainsPost) {
        return 1;
    }
    // If $b contains "post" and $a doesn't, $a should be before $b
    elseif (!$aContainsPost && $bContainsPost) {
        return -1;
    }
    // Otherwise, maintain the original order
    else {
        return 0;
    }
}

/**
 * Get taxonomy terms.
 * 
 * @param int @post_id - The post id to get terms for
 * @param string @tax - The taxonomy in which we want to get terms for
 * @param int @parent - Whether to get top level or not
 * @param array @meta_query - Custom meta query
 *
 * @since 1.0.0
 */
function getTaxTerms( $post_id = null, $tax, $parent = 0, $meta_query = null ){
    if($post_id){
        $terms = get_the_terms( $post_id, $tax );
    }else{
        $args = [ 'taxonomy' => $tax, 'hide_empty' => false, 'parent' => $parent ];

        if( $meta_query )
        $args['meta_query'] = $meta_query;          
        
        $terms = get_terms( $args );
    }

    return $terms && !is_wp_error( $terms ) ? $terms : false;
}

/**
 * Returns the first term of a given tax for a post.
 *
 * @since 1.0.0
 */
function getFirstTaxTerm( $post_id, $tax ){
    $terms = getTaxTerms($post_id, $tax);
    return $terms && isset($terms[0]) ? $terms[0] : false;
}

/**
 * Takes in a set of backlinks, and distributes them into a new array 
 * consisting of thier respective networks
 * 
 * @since 1.0.0
 */
function getBacklinkDistributionByNetwork( $backlinks ){

    if( empty($backlinks) )
    return false;

    $distribution = [];

    foreach( $backlinks as $backlink ){
        $network = getFirstTaxTerm( $backlink['referer_id'], 'site_network' );

        $slug = str_replace( '-', '_', $network->slug );
        
        $distribution[$slug] = isset( $distribution[$slug] ) ? $distribution[$slug] + 1 : 1;

    }

    return $distribution;

}

/**
 * Cleans up the backlink data to make them representable
 * 
 * @var array $data - The backlink data
 * 
 * @since 1.0.0
 */
function cleanBacklinkData( $data ){

    foreach( $data as $key => $item ){

        $data[$key]['link_from']    = explode( ', ', $item['link_from'] );
        $data[$key]['link_to']      = explode( ', ', $item['link_to'] );
        $data[$key]['rel']          = explode( ', ', $item['rel'] );
        $data[$key]['content']      = explode( ', ', $item['content'] );

        foreach( $data[$key]['link_to'] as $_key => $link_to ){

            $client = HttpClient::create();
            $response = $client->request(
                'GET',
                $link_to,
                [
                    'max_redirects' => 0,
                    'timeout'       => -1
                ]
            );

            $statusCode = $response->getStatusCode();
    
            $data[$key]['status'][$_key] = $statusCode;
            $data[$key]['redirect'][$_key] = null;

            if( $statusCode == 301 || $statusCode == 302 ){
                $data[$key]['redirect'][$_key] = $response->getInfo('redirect_url');
            }

        }

        foreach( $data[$key]['rel'] as $_key => $rel ){

            if( empty($rel) )
            $data[$key]['rel'][$_key] = 'follow';

            if( !empty($rel) && !str_contains( $rel, 'nofollow' ) )
            $data[$key]['rel'][$_key] = 'follow';

            if( !empty($rel) && str_contains( $rel, 'nofollow' ) )
            $data[$key]['rel'][$_key] = 'nofollow';

        }

        foreach( $data[$key]['content'] as $_key => $content ){

            if( empty($content) )
            $data[$key]['content'][$_key] = 'Image';

        }
        
    }

    return $data;

}

/**
 * Cleans up the backlink data to make them representable
 * 
 * @var array $data - The backlink data
 * 
 * @since 1.0.0
 */
function getKeywordDistribution( $data ){
    
    $distribution = [];
    $frequencies = [];

    $count = 0;

    foreach( $data as $key => $item ){

        $data[$key]['content'] = explode( ', ', $item['content'] );

        foreach( $data[$key]['content'] as $_key => $content ){

            if( empty($content) )
            $content = 'Image';

            $distribution[] = $content;

            $count++;

        }

    }
    
    if( is_array($distribution) ){

        $frequencies['count'] = $count;
        
        foreach ($distribution as $keyword) {
            if (!isset($frequencies[$keyword])) {
                $frequencies[$keyword] = 0;
            }
        
            $frequencies[$keyword]++;
        }

        asort($frequencies);
        $frequencies = array_reverse($frequencies);

    }
    
    return $frequencies;

}

/**
 * Returns a list of opportunities that can be added
 * 
 * @var array $data - The backlink data
 * @var int $siteID - The site ID in which we want to add to the post_not_in
 * 
 * @since 1.0.0
 */
function getOpportunities( $data, $siteID ){

    $existing_sites = array_column( $data , 'referer_id' );
    array_push( $existing_sites, $siteID );
    
    $args = array(
        'post_type'         => 'site',
        'posts_per_page'    => -1,
        'post__not_in'      => $existing_sites
    );

    $query = new WP_Query( $args );
    $sites = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();

            $network = getFirstTaxTerm( get_the_ID(), 'site_network' );

            $sites[$network->term_id][] = [
                'id'        => get_the_ID(),
                'site'      => get_the_title(),
                'domain'    => get_field('domain')
            ];
            
        }
        wp_reset_postdata();
    }

    return $sites;

}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}