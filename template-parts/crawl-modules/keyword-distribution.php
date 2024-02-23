<?php $backlink_data = get_field( 'backlink_data' ); ?>

<div class="card">
    <div class="card-header">
        <p class="h6">Keyword Distribution</p>
    </div>
    <div class="card-body">
        <?php 
        if( $backlink_data && is_array($backlink_data) ){ 
            if( $keyword_distribution = getKeywordDistribution($backlink_data) ){
            ?>
            <div>
                <?php 
                foreach( $keyword_distribution as $key => $count ){ 
                    if( $key == 'count' )
                    continue;
                
                    ?>
                    <div class="d-flex ai-c jc-b mb-5">
                        <b class="fs-12 f-1"><?php echo $key ?></b>
                        <span>
                        <?php echo $count ?> <small>( <?php echo number_format( ( $count / $keyword_distribution['count'] ) * 100, 2, '.', '' ) ?>% )</small>
                        </span>
                    </div>
                <?php } ?>
            </div>
            <?php } ?>
        <?php }else{ ?>
            <p class="text-center mb-0">Site hasn't been crawled or has no data</p>
        <?php } ?>
    </div>
</div>