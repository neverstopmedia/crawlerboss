<?php
if( $invalidHeadings = getSitesWithInvalidHeadings() ){
    ?>
    <?php foreach( $invalidHeadings as $site ){ ?>
        <div class="mb-10">
            <a href="<?php echo $site['permalink'] ?>" class="fw-sb fs-14"><?php echo $site['site']; ?></a>
            <span class="fs-12 word-break color-link d-block"><?php echo $site['domain'] ?></span>
        </div>
        <?php
    }
}