<?php $backlink_data = get_field( 'backlink_data' ); ?>
<div class="card">
    <div class="card-header">
        <p class="h6">Opportunities</p>
    </div>
    <div class="card-body limited">

        <?php if( $backlink_data && is_array($backlink_data) ){ 
            $opportunities = getOpportunities( $backlink_data, get_the_ID() );
            ?>
            <p><?php echo 'There are ' . count($opportunities) . ' opportunities for ' . get_the_title(); ?></p>
            <ul>
            <?php foreach( $opportunities as $opportunity ){ ?>
                <li>
                    <span class="fw-sb fs-14"><?php echo $opportunity['site'] ?></span>
                    <a class="fs-12 word-break color-link d-block" target="_blank" href="<?php echo $opportunity['domain'] ?>"><?php echo $opportunity['domain'] ?></a>
                </li>
            <?php } ?>
            </ul>
        <?php }else{ ?>
            <p class="text-center mb-0">Every site is an opportunity, as this site has no links to begin with 😁</p>
        <?php } ?>

    </div>
</div>