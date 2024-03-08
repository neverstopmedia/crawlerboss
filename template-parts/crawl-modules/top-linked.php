<?php
if( $topLinkedSites = getSitesWithMostLinks() ){
    usort( $topLinkedSites, 'sortByLinkCount' );
    ?>
    <?php foreach( array_splice($topLinkedSites, 0, 10) as $site ){ ?>
        <div class="mb-10">
            <a href="<?php echo $site['permalink'] ?>" class="fw-sb fs-14"><?php echo $site['site'] . ' <small>(' . count($site['links']) . ' links)</small>'; ?></a>
            <span class="fs-12 word-break color-link d-block"><?php echo $site['domain'] ?></span>
        </div>
        <?php
    }
}