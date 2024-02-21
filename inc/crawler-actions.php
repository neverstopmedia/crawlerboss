<?php

/**
 * Updates the sitemap data of a post on save.
 * 
 * @since 1.0.0
 */
function updateSitemapOnSave( $post_id, $post ){

    if( $post != null && ($post->post_type !== 'site' || 'auto-draft' == $post->post_status) )
    return false;

    $url = get_field( 'domain' );

    if( empty($url) )
    return false;

    // @TODO: Let's check if the update was done more than a week ago

    // Let's set the sitemap information
    if( $sitemapURL = setParentSitemap($url, $post_id, 'sitemap_index.xml') ){

        setInnerSitemaps( $sitemapURL, $post_id );
    
    }

}
add_action( 'save_post', 'updateSitemapOnSave', 10, 2 );
