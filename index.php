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
            <button type="button" class="btn btn-outline" id="regenerate-sitemaps">Regenerate Sitemaps</button>
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

    <div class="container mt-40">

        <div class="row">
            <div class="md-4">
                <div class="card">
                    <div class="card-header">
                        <p class="h6">Abandoned Sites</p>
                        <span class="d-block mt-5 fs-12">Sites that haven't been updated in the past month</span>
                    </div>
                    <div class="card-body limited">
                        <?php get_template_part( 'template-parts/crawl-modules/abandoned-sitemaps' ); ?>
                    </div>
                </div>
            </div>

            <div class="md-4">
                <div class="card height-auto">
                    <div class="card-header">
                        <p class="h6">Top Linked</p>
                        <span class="d-block mt-5 fs-12">Top 10 sites with the most number of backlinks</span>
                    </div>
                    <div class="card-body limited">
                        <?php get_template_part( 'template-parts/crawl-modules/top-linked' ); ?>
                    </div>
                </div>
                <div class="card height-auto">
                    <div class="card-header">
                        <p class="h6">Wrong Heading Structure</p>
                        <span class="d-block mt-5 fs-12">Sites that have a wrong heading structure in any page</span>
                    </div>
                    <div class="card-body limited-sm">
                        <?php get_template_part( 'template-parts/crawl-modules/invalid-headings' ); ?>
                    </div>
                </div>
            </div>

            <div class="md-4">
                <div class="card">
                    <div class="card-header">
                        <p class="h6">Orphaned Sites</p>
                        <span class="d-block mt-5 fs-12">Sites with no links at all</span>
                    </div>
                    <div class="card-body limited">
                        <?php get_template_part( 'template-parts/crawl-modules/orphaned-sites' ); ?>
                    </div>
                </div>
            </div>

        </div>

        <div class="card can-toggle">
            <div class="card-header d-flex ai-c jc-b">
                <p class="h6">Cron Schedule</p>
                <i class="c-pointer toggle fas fa-chevron-down"></i>
            </div>
            <div class="card-body d-none limited">
                <?php get_template_part( 'template-parts/crawl-modules/cron-jobs' ); ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex ai-c jc-b">
                <p class="h6">Site Logs</p>
            </div>
            <div class="card-body">
                <?php get_template_part( 'template-parts/crawl-modules/logs' ); ?>
            </div>
        </div>

    </div>

</div>

<?php get_footer(); ?>