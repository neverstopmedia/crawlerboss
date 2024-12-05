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

 // Add the custom columns to the car post type:
function site_admin_table_columns($columns) {

    $columns['networks'] = __( 'Networks', 'luxcars' );

    return $columns;
}
add_filter( 'manage_site_posts_columns', 'site_admin_table_columns' );

// Add the data to the custom columns for the car post type:
function site_admin_table_columns_data( $column, $post_id ) {

    switch ( $column ) {

        case 'networks' :
            if($networks = getTaxTerms( $post_id, 'site_network' )){
                foreach( $networks as $network ){
                    echo '<span style="display: block;">'.$network->name.'</span>';
                }
            }else{
                echo '-';
            }
            break;
            foreach($prices as $key => $price){
                if($price == 0)
                continue;

                echo '<span class="d-block luxcars-car-price">'.str_replace("_", '-', $key).' Days: '.$currency.' '.$price.'</span>';
            }
            break;
    }
}
add_action( 'manage_site_posts_custom_column' , 'site_admin_table_columns_data', 10, 2 );