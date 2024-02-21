<?php
/**
 * Register the Group taxonomy
 * 
 * @since 1.0.0
 */
function site_group_tax(){
    $group_labels = array(
        'name'              => sprintf( _x( '%s Groups', 'taxonomy general name', 'nsm' ), 'Site' ),
        'singular_name'     => sprintf( _x( '%s Group', 'taxonomy singular name', 'nsm' ), 'Site' ),
        'search_items'      => sprintf( __( 'Search %s Groups', 'nsm' ), 'Site' ),
        'all_items'         => sprintf( __( 'All %s Groups', 'nsm' ), 'Site' ),
        'parent_item'       => sprintf( __( 'Parent %s Group', 'nsm' ), 'Site' ),
        'parent_item_colon' => sprintf( __( 'Parent %s Group:', 'nsm' ), 'Site' ),
        'edit_item'         => sprintf( __( 'Edit %s Group', 'nsm' ), 'Site' ),
        'update_item'       => sprintf( __( 'Update %s Group', 'nsm' ), 'Site' ),
        'add_new_item'      => sprintf( __( 'Add New %s Group', 'nsm' ), 'Site' ),
        'new_item_name'     => sprintf( __( 'New %s Group Name', 'nsm' ), 'Site' ),
        'menu_name'         => __( 'Groups', 'nsm' ),
    );
    
    $group_args = array(
        'hierarchical' => true,
        'labels'       => $group_labels,
        'show_ui'      => true,
        'query_var'    => 'site_group',
    );
    register_taxonomy( 'site_group', ['site'], $group_args );
}
add_action('init',  'site_group_tax' );

/**
 * Register the Network taxonomy
 * 
 * @since 1.0.0
 */
function site_network_tax(){
    $network_labels = array(
        'name'              => sprintf( _x( '%s Networks', 'taxonomy general name', 'nsm' ), 'Site' ),
        'singular_name'     => sprintf( _x( '%s Network', 'taxonomy singular name', 'nsm' ), 'Site' ),
        'search_items'      => sprintf( __( 'Search %s Networks', 'nsm' ), 'Site' ),
        'all_items'         => sprintf( __( 'All %s Networks', 'nsm' ), 'Site' ),
        'parent_item'       => sprintf( __( 'Parent %s Network', 'nsm' ), 'Site' ),
        'parent_item_colon' => sprintf( __( 'Parent %s Network:', 'nsm' ), 'Site' ),
        'edit_item'         => sprintf( __( 'Edit %s Network', 'nsm' ), 'Site' ),
        'update_item'       => sprintf( __( 'Update %s Network', 'nsm' ), 'Site' ),
        'add_new_item'      => sprintf( __( 'Add New %s Network', 'nsm' ), 'Site' ),
        'new_item_name'     => sprintf( __( 'New %s Network Name', 'nsm' ), 'Site' ),
        'menu_name'         => __( 'Networks', 'nsm' ),
    );
    
    $network_args = array(
        'hierarchical' => true,
        'labels'       => $network_labels,
        'show_ui'      => true,
        'query_var'    => 'site_network',
    );
    register_taxonomy( 'site_network', ['site'], $network_args );
}
add_action('init',  'site_network_tax' );