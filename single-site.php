<?php
/**
 * The main single template for sites.
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package crawler
 */
get_header();

$last_checked = get_field( 'last_checked' );
?>

<div class="container py-40">
    
    <div class="d-flex ai-c jc-b mb-40">
        <div>
            <h1 class="mb-5"><?php the_title(); ?></h1>
            <span> <b>Last Crawled: </b> <?php echo $last_checked ? time_elapsed_string($last_checked) . ' ago' : 'Not Crawled yet' ?></span>
        </div>
        <div class="d-flex">
            <a href="<?php echo get_home_url('/') ?>" class="btn btn-outline">Back home</a>
            <button type="button" data-id="<?php echo get_the_ID(); ?>" class="ml-20 btn btn-primary" id="single-domain-crawl">Force Crawl</button>
        </div>
    </div>

    <div class="crawl--results">
            
    </div>

    <div class="row">
        <div class="md-4">
            <?php get_template_part( 'template-parts/crawl-modules/general' ) ?>
        </div>
        <div class="md-4">
            <?php get_template_part( 'template-parts/crawl-modules/backlink-count' ) ?>
        </div>
        <div class="md-4">
            <?php get_template_part( 'template-parts/crawl-modules/sitemaps' ) ?>
        </div>
        <div class="md-7">
            <?php get_template_part( 'template-parts/crawl-modules/keyword-distribution' ) ?>
        </div>
        <div class="md-5">
            <?php get_template_part( 'template-parts/crawl-modules/heading-structure' ) ?>
        </div>
        <div class="md-8">
            <?php get_template_part( 'template-parts/crawl-modules/opportunities' ) ?>
        </div>
        <div class="md-4">
            <?php get_template_part( 'template-parts/crawl-modules/linking-to' ) ?>
        </div>
        <div class="md-12">
            <div class="card">
                <div class="card-header">
                    <p class="h6">Backlinks</p>
                </div>
                <div class="card-body ajax-content" data-template="template-parts/crawl-modules/details" data-id="<?php echo get_the_ID() ?>">

                </div>
            </div>
        </div>
    </div>
    
</div>

<?php get_footer(); ?>