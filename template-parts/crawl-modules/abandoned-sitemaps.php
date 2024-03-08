<?php
if( $abandonedSites = getAbandonedSites() ){
    ?>
    <p class="alert alert-warning">There are <?php echo count($abandonedSites) ?> sites that haven't been updated in the past month</p>
    <?php foreach( $abandonedSites as $site ){ ?>
        <div class="mb-10">
            <span class="fw-sb fs-14"><?php echo $site['site'] ?> <span class="fs-12 fw-r">(<?php echo time_elapsed_string($site['last_updated']); ?> ago)</span></span>
            <a class="fs-12 word-break color-link d-block" target="_blank" href="<?php echo $site['domain'] ?>">
                <?php echo $site['domain'] ?>
                <i class="ml-5 fs-12 fa-solid fa-arrow-up-right-from-square"></i>
            </a>
        </div>
        <?php
    }

}