<?php

/**
 * Function to Register "site" custom post type.
 * 
 * @since 1.0.0
 */
function crawler_register_cpt_site(){
    $labels = array(
        'name' => esc_html__('Sites', 'crawler'),
        'singular_name' => esc_html__('Site', 'crawler'),
        'menu_name' => esc_html__('Sites', 'crawler'),
        'name_admin_bar' => esc_html__('Site', 'crawler'),
        'add_new' => esc_html__('Add New', 'crawler'),
        'add_new_item' => esc_html__('Add New Site', 'crawler'),
        'new_item' => esc_html__('New Site', 'crawler'),
        'edit_item' => esc_html__('Edit Site', 'crawler'),
        'view_item' => esc_html__('View Site', 'crawler'),
        'all_items' => esc_html__('All Sites', 'crawler'),
        'search_items' => esc_html__('Search Sites', 'crawler'),
        'parent_item_colon' => esc_html__('Parent Site:', 'crawler'),
        'not_found' => esc_html__('No sites found.', 'crawler'),
        'not_found_in_trash' => esc_html__('No sites found in Trash.', 'crawler'),
        'featured_image' => esc_html__('Site Image', 'crawler'),
        'set_featured_image' => esc_html__('Set Site Image', 'crawler'),
        'remove_featured_image' => esc_html__('Remove Site Image', 'crawler'),
        'use_featured_image' => esc_html__('Use Site Image', 'crawler'),
    );

    $cpt_site_args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'sites' ),
        'capability_type' => 'post',
        'has_archive' => false,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title', 'thumbnail', 'editor'),
        'menu_icon' => 'dashicons-editor-ul'
    );

    register_post_type('site', $cpt_site_args);
}
add_action('init', 'crawler_register_cpt_site' );
