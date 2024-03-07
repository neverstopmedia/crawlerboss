<?php $backlink_data = get_field( 'backlink_data' ); ?>
<div class="card can-toggle">
    <div class="card-header d-flex ai-c jc-b">
        <p class="h6">Opportunities</p>
        <i class="c-pointer toggle fas fa-chevron-down"></i>
    </div>
    <div class="card-body limited">

        <?php foreach( getOpportunities( $backlink_data, get_the_ID() ) as $key => $opportunity_network ){ 
            $network = get_term($key, 'site_network');
            ?>
        <div class="d-flex ai-c mb-20">
            <img class="mw-20 mr-10" src="<?php echo get_field('network_image', 'site_network_' . $network->term_id); ?>" alt="<?php echo $network->name; ?>">
            <p class="h6 mb-0"><?php echo $network->name ?></p>
        </div>
        <div class="row">
            <?php foreach( $opportunity_network as $opportunity ){ ?>
            <div class="md-6 mb-15">
                <span class="fw-sb fs-14"><?php echo $opportunity['site'] ?></span>
                <a class="fs-12 word-break color-link d-block" target="_blank" href="<?php echo $opportunity['domain'] ?>"><?php echo $opportunity['domain'] ?></a>
            </div>
            <?php } ?>
        </div>
        <?php } ?>

    </div>
</div>