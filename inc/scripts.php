<?php
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Enqueue scripts and styles.
 * 
 * @since 1.0.0
 */
function crawler_scripts() {

  wp_enqueue_style( 'select2', CRAWLER_URI . '/assets/css/lib/select2.min.css', array(), '4.1.4');
  wp_enqueue_style( 'tooltipster', CRAWLER_URI . '/assets/css/lib/tooltipster.bundle.css', array(), '1.0.0');
  wp_enqueue_style( 'fontawesome', CRAWLER_URI . '/assets/fonts/fontawesome/css/all.min.css', array(), '6.0.0');

    /**
   * Contains critical CSS (Every element that loads above the fold)
   * Anything else thats not above the fold will be loaded on demand
   * and added to its own stylesheet
   */
  wp_enqueue_style( 'main', CRAWLER_URI . '/assets/css/main.css', array(), CRAWLER_VERSION);
  wp_enqueue_style( 'crawler', CRAWLER_URI . '/assets/css/crawler.css', array(), CRAWLER_VERSION);
  wp_enqueue_style( 'google-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap', false );
  
  wp_enqueue_script( 'select2', CRAWLER_URI . '/assets/js/lib/select2.min.js', array('jquery'), '4.1.4', false );
  wp_enqueue_script( 'tooltipster', CRAWLER_URI . '/assets/js/lib/tooltipster.bundle.js', array('jquery'), '1.0.0', false );

  wp_enqueue_script( 'amcharts-index', CRAWLER_URI . '/assets/js/lib/amcharts/index.js', array('jquery'), '5.0.0', false );
  wp_enqueue_script( 'amcharts-hierarchy', CRAWLER_URI . '/assets/js/lib/amcharts/hierarchy.js', array('jquery'), '5.0.0', false );
  wp_enqueue_script( 'amcharts-animated', CRAWLER_URI . '/assets/js/lib/amcharts/animated.js', array('jquery'), '5.0.0', false );
  wp_enqueue_script( 'amcharts-xy', CRAWLER_URI . '/assets/js/lib/amcharts/xy.js', array('jquery'), '5.0.0', false );
  wp_enqueue_script( 'amcharts-radar', CRAWLER_URI . '/assets/js/lib/amcharts/radar.js', array('jquery'), '5.0.0', false );
  wp_enqueue_script( 'amcharts-percent', CRAWLER_URI . '/assets/js/lib/amcharts/percent.js', array('jquery'), '5.0.0', false );
  
  wp_enqueue_script( 'main', CRAWLER_URI . '/assets/js/main.js', array('jquery'), CRAWLER_VERSION, ['in_footer' => true] );
  wp_enqueue_script( 'crawler', CRAWLER_URI . '/assets/js/crawler.js', array('jquery'), CRAWLER_VERSION, [ 'in_footer' => true ] );
  
  if( is_singular('site') )
  wp_enqueue_script( 'site', CRAWLER_URI . '/assets/js/site.js', array('jquery'), CRAWLER_VERSION, [ 'in_footer' => true ] );

  if( is_tax('site_network') )
  wp_enqueue_script( 'network', CRAWLER_URI . '/assets/js/network.js', array('jquery'), CRAWLER_VERSION, ['in_footer' => true] );

  wp_localize_script( 'main', 'crawler_ajax_obj', [
    'ajaxurl' => admin_url('admin-ajax.php'),
  ]);
  
  wp_dequeue_style('wp-block-library');
  wp_dequeue_style('wp-block-library-theme');

}
add_action( 'wp_enqueue_scripts', 'crawler_scripts' );

/**
 * Preload stylesheets that are not set to asset lazy
 *
 * @since 2.0.0
 */
function crawler_preload_styles( $html, $handle ){
  if( !is_admin() ){
    $html = str_replace("rel='stylesheet'", "rel='stylesheet preload' as='style' ", $html);
  }
  return $html;
}
add_filter( 'style_loader_tag',  'crawler_preload_styles', 10, 2 );

add_filter( 'jetpack_sharing_counts', '__return_false', 99 );
add_filter( 'jetpack_implode_frontend_css', '__return_false', 99 );