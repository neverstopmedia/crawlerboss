<?php
if( $orphanedSites = getOrphanedSites() ){
    ?>
    <p class="alert alert-warning">There are <?php echo count($orphanedSites) ?> sites without links from our network</p>
    <?php foreach( $orphanedSites as $site ){ ?>
        <div class="mb-10">
            <span class="fw-sb fs-14"><?php echo $site['site'] ?></span>
            <a class="fs-12 word-break color-link d-block" target="_blank" href="<?php echo $site['domain'] ?>">
                <?php echo $site['domain'] ?>
                <i class="ml-5 fs-12 fa-solid fa-arrow-up-right-from-square"></i>
            </a>
        </div>
        <?php
    }

}