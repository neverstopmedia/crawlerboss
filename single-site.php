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
            <span> <b>Last Crawled: </b> <?php echo $last_checked ? time_elapsed_string($last_checked) : 'Not Crawled yet' ?></span>
        </div>
        <a href="<?php echo get_home_url('/') ?>" class="btn btn-primary">Back home</a>
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
        <div class="md-4">
            <?php get_template_part( 'template-parts/crawl-modules/keyword-distribution' ) ?>
        </div>
        <div class="md-8">
            <div class="card">
                <div class="card-header">
                    <p class="h6">Details</p>
                </div>
                <div class="card-body ajax-content" data-template="template-parts/crawl-modules/details" data-id="<?php echo get_the_ID() ?>">

                </div>
            </div>
        </div>
    </div>
    
</div>

<?php get_footer(); ?>