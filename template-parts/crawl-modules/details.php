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
                <div class="mb-10 link-group">
                    <?php foreach( $item['link_to'] as $key => $link_to ){ ?>
                        <div class="d-flex ai-c jc-b link-item">
                            <div class="f-1">
                                <p class="d-flex ai-c mb-5 fs-14 tt-u fw-sb">
                                    <?php echo get_the_title( $item['referer_id'] ); ?>
                                    <span class="tt-c fw-r ml-5 badge fs-12 <?php echo str_contains( $item['rel'][$key], 'nofollow' ) ? 'danger' : 'success' ?>">
                                        <?php echo $item['rel'][$key] ?>
                                    </span>
                                </p>
                                <a href="<?php echo $item['link_from'][$key] ?>" class="fs-12 d-block shorten"><?php echo $item['link_from'][$key] ?></a>
                            </div>
                            <i class="fa-solid fa-arrow-right"></i>
                            <div class="f-1">
                                <a href="<?php echo $link_to ?>" class="fs-12 d-block shorten"><?php echo $link_to ?></a>
                                <span class="fs-12"><b>Content:</b> <?php echo $item['content'][$key] ?></span>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                <?php
            }

        }
        ?>
    </div>
</div>