<?php 
$backlink_data = get_field( 'backlink_data', $args ); 
?>

<?php if( $backlink_data && is_array($backlink_data) ){
    
    $backlink_data = cleanBacklinkData($backlink_data);

    foreach( $backlink_data as $item ){
        ?>
        <div class="mb-10 link-group">
            <div class="d-flex jc-b">
                <div class="f-1">
                    <p class="d-flex ai-c mb-5 fs-14 tt-u fw-sb">
                        <?php echo get_the_title( $item['referer_id'] ); ?>
                    </p>
                    <a target="_blank" href="<?php echo $item['link_from'][0] ?>" class="fs-12 word-break color-link-base d-block"><?php echo $item['link_from'][0] ?></a>
                </div>
                <div class="f-1 ml-20">
                    <?php foreach( $item['link_to'] as $key => $link_to ){ ?>
                    <div class="link-item">
                        <span class="fs-14 d-block"><?php echo $item['content'][$key] ?></span>
                        <a target="_blank" href="<?php echo $link_to ?>" class="fs-12 d-block word-break color-link"><?php echo $link_to ?></a>
                        <div class="mt-5">
                            <span class="tt-c fw-r badge fs-12">
                                <?php echo $item['rel'][$key] ?>
                            </span>
                            <span class="tt-c ml-5 fw-r badge fs-12 status-<?php echo $item['status'][$key] ?>">
                                <?php echo $item['status'][$key] ?>
                            </span>
                        </div>
                        <?php if( $item['redirect'][$key] ){ ?>
                        <a target="_blank" href="<?php echo $item['redirect'][$key] ?>" class="mt-5 ml-15 fs-12 word-break d-block color-link">
                            <i class="fa-solid fa-arrows-turn-right"></i>
                            <?php echo $item['redirect'][$key] ?>
                        </a>
                        <?php } ?>
                    </div>
                    <?php } ?>
                </div>

            </div>
        </div>
        <?php
    }

}
?>
