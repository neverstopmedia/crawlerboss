<?php $backlink_data = get_field( 'backlink_data' ); ?>

<div class="card">
    <div class="card-header">
        <p class="h6">Details</p>
    </div>
    <div class="card-body">
        <?php if( $backlink_data && is_array($backlink_data) ){
            
            $backlink_data = cleanBacklinkData($backlink_data);

            foreach( $backlink_data as $item ){
                ?>
                <div>
                    <?php foreach( $item['link_from'] as $key => $link_from ){ ?>
                        <div class="d-flex ai-c jc-b">
                            <span><?php echo $link_from ?></span>
                            <i class="fa-solid fa-arrow-right"></i>
                            <span><?php echo $item['link_to'][$key] ?></span>
                        </div>
                    <?php } ?>
                </div>
                <?php
            }

        }
        ?>
    </div>
</div>