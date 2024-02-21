<?php
/**
 * The main template file.
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

$networks = get_terms( array(
    'taxonomy'   => 'site_network',
    'hide_empty' => false,
) );
?>

<div class="py-30 py-sm-40">
    <?php if( $networks ){ ?>
    <div class="container">
        <div class="d-flex mb-20 ai-c jc-b">
            <h1 class="mb-0 text-center">Select a <span class="tc-p">Network</span> </h1>
            <button type="button" class="btn" id="regenerate-sitemaps">Regenerate Sitemaps</button>
        </div>
        <div class="crawler--networks row jc-c">
            <?php foreach( $networks as $network ){ ?>
            <div class="f-1 mx-15">
                <a class="network--item d-block text-center border br-4 pb-20" href="<?php echo get_term_link( $network->term_id, 'site_network' ) ?>">
                    <img src="<?php echo get_field('network_image', 'site_network_' . $network->term_id); ?>" alt="<?php echo $network->name; ?>">
                    <p class="tc-d mb-0 fw-sb fs-14"><?php echo $network->name; ?> (<?php echo $network->count ?>)</p>
                </a>
            </div>
            <?php } ?>
        </div>
        <div class="crawler--seperator d-flex ai-c jc-c my-20 text-center tt-u">
            or
        </div>
        <form method="GET" class="d-flex" id="custom-domain-search-form">
            <select name="domain" data-placeholder="Select a domain" id="siteID" class="crawler--select2"></select>
            <button type="submit" disabled class="ml-20 btn btn-sm" id="custom-domain-search-btn">Crawl</button>
        </form>
        <p class="fs-12 mb-0 mt-10 tc-l">This will attempt to find a link on every single site we own</p>

        <div class="mt-40 crawl--results">
            <div class="text-center">
                Select a domain, and press on Crawl to start crawling a website
            </div>
        </div>

    </div>
    <?php } ?>

</div>

<?php get_footer(); ?>