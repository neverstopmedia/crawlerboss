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
$network = get_queried_object();
?>

<div class="container py-40">
    <div class="d-flex ai-c jc-b">
        <div class="d-flex ai-s mb-20">
            <img class="mw-60 mr-10" src="<?php echo get_field('network_image', 'site_network_' . $network->term_id); ?>" alt="<?php echo $network->name; ?>">
            <div class="f-1">
                <h1 class="mb-0"><?php echo $network->name ?> Network</h1>
                <h5><?php echo $network->count ?> sites available</h5>
            </div>
        </div>
        <a href="<?php echo get_home_url('/') ?>" class="btn btn-outline">Back home</a>
    </div>
    <?php if(have_posts()){ ?>
    
    <!-- <div id="siteDistributionChart" class="d-flex ai-c jc-c border mb-30" data-network="<?php echo $network->term_id ?>" style="height: 600px"> 
        <span class="loader"></span> 
    </div> -->
    
    <label>Domain</label>
    <input id="dynamic-search" type="text" class="mb-15" placeholder="Search for a domain">
    <table id="siteTable" class="fs-14">
        <thead>
            <tr>
                <th>Site</th>
                <th>Domain</th>
                <th>Links</th>
                <th>Sitemaps</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            while(have_posts()){ 
                the_post();
                $last_checked = get_field( 'last_checked' );
            ?>
            <tr class="c-pointer" onclick="window.location.href='<?php echo get_the_permalink() ?>'">
                <td>
                    <div class="d-flex ai-c jc-b">
                        <div class="d-flex ai-c">
                            <img class="mr-10" src="https://www.google.com/s2/favicons?domain=<?php echo get_field('domain') ?>" alt="Site">
                            <div class="f-1">
                                <p class="mb-0"><b><?php echo get_the_title(); ?></b></p>
                                <span><?php echo $last_checked ? time_elapsed_string($last_checked) . ' ago' : 'Not Crawled yet' ?></span>
                                <?php if( get_field( 'skip_cron' ) ){ ?>
                                <small class="tc-e d-block">This site can only be manually crawled</small>
                                <?php } ?>
                            </div>
                        </div>

                        <?php if( empty($last_checked) || ( !empty($last_checked) && ( strtotime( $last_checked ) < strtotime('-7 day') ) ) ){ ?>
                        <i title="It has been more than 7 days since the last crawl" class="tc-w tooltip fa-solid fa-triangle-exclamation"></i>
                        <?php }else{ ?>
                        <i title="This site was already crawled in the last 7 days" class="tc-s tooltip fa-solid fa-check"></i>
                        <?php } ?>
                    </div>
                </td>
                <td>
                    <?php echo get_field('domain') ?>
                </td>
                <td class="html-tooltip">
                    <?php 
                    $backlinks = get_field('backlink_data');
                    echo $backlinks && is_array($backlinks) ? count($backlinks) : 'No links detected';

                    if( $backlinks && is_array($backlinks) ){
                    ?>
                    <div class="tooltip-content d-none">
                        <?php 
                        if( $backlink_distribution = getBacklinkDistributionByNetwork( $backlinks ) ){
                            foreach( $backlink_distribution as $key => $count ){
                                ?>
                                <p class="fs-12 mb-5 tt-c"> <b><?php echo str_replace( '_', ' ', $key ) ?>: </b> <?php echo $count ?> sites</p>
                                <?php
                            }
                        } 
                        ?>
                    </div>
                    <?php } ?>
                </td>
                <td class="html-tooltip">
                    <?php 
                    $sitemaps = get_field('sitemaps');
                    echo $sitemaps && is_array($sitemaps) ? count($sitemaps) : 0;
                    echo get_field( 'skip_sitemap' ) ? '<small class="tc-e d-block">Skipped</small>' : null;
                    ?>
                    <div class="tooltip-content d-none">
                        <?php if( $sitemaps && is_array($sitemaps) ){ ?>
                        <ul class="mb-0">
                            <?php foreach( $sitemaps as $sitemap ){ ?>
                            <li>
                                <p class="fs-12 mb-5"><?php echo str_replace( get_field('domain'), '', $sitemap['sitemap']); ?></p>
                                <small>Last modified:<?php echo $sitemap['last_modified'] ?></small>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php }else{ ?>
                        <span>No sitemaps coming</span>
                        <?php } ?>
                    </div>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <?php } ?>
</div>

<?php get_footer(); ?>