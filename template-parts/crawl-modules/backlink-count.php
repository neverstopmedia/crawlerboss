<?php
$backlinks      = get_field('backlink_data');
$backlink_count = $backlinks && is_array($backlinks) ? count($backlinks) : 0;
?>

<div class="card">
    <div class="card-header">
        <p class="h6">Backlink Distribution (<?php echo $backlink_count ?>)</p>
    </div>
    <div class="card-body">
    <?php 
    if( $backlink_distribution = getBacklinkDistributionByNetwork( $backlinks ) ){
        foreach( $backlink_distribution as $key => $count ){
            $network = get_term_by( 'slug', $key, 'site_network' );
            ?>
            <div class="d-flex ai-c jc-b mb-5">
                <div>
                    <img class="mw-20 mr-10" src="<?php echo get_field('network_image', 'site_network_' . $network->term_id); ?>" alt="<?php echo $network->name; ?>">
                    <b class="tt-c"><?php echo str_replace( '_', ' ', $key ) ?>: </b>
                </div>
                <span><?php echo $count ?> sites</span>
            </div>
            <?php
        }
    }else{
        echo 'No links found or page not crawled yet.';
    }
    ?>
    </div>
</div>