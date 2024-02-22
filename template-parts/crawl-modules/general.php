<?php $domain = get_field( 'domain' ) ?>
<div class="card">
    <div class="card-header">
        <p class="h6">General Information</p>
    </div>
    <div class="card-body">
        <div class="d-flex ai-c jc-b mb-5">
            <b>Network</b>
            <span>
                <?php
                if( $network = getFirstTaxTerm( get_the_ID(), 'site_network' ) ){
                    echo $network->name;
                }else{
                    'No network set';
                }
                ?>
            </span>
        </div>
        <div class="d-flex ai-c jc-b mb-5">
            <b>Site URL</b>
            <a href="<?php echo $domain ?>" target="_blank">
                <?php echo $domain ?>
                <i class="ml-5 fs-12 fa-solid fa-arrow-up-right-from-square"></i>
            </a>
        </div>
        <div class="d-flex ai-c jc-b mb-5">
            <b>Sitemap URL</b>
            <a target="_blank" href="<?php echo get_field('sitemap_url') ?>">
                <?php echo str_replace( $domain, '', get_field('sitemap_url') ) ?>
                <i class="ml-5 fs-12 fa-solid fa-arrow-up-right-from-square"></i>
            </a>
        </div>
    </div>
</div>