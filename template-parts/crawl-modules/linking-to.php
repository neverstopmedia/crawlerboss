<div class="card can-toggle">
    <div class="card-header d-flex ai-c jc-b">
        <p class="h6">Linking To</p>
        <i class="c-pointer toggle fas fa-chevron-down"></i>
    </div>
    <div class="card-body limited">
        <?php if( $linksTo = linksTo(get_the_ID()) ){  ?>
            <div>
                <?php foreach( $linksTo as $link ){ ?>
                <div class="mt-10">
                    <a style="word-break: break-word" href="<?php echo $link['link_from'] ?>" target="_blank" class="d-flex ai-c mb-5 fs-14"><?php echo $link['link_from'] ?></a>
                    <p class="fs-12 d-block word-break color-link mb-0"><?php echo $link['link_to'] ?></p>
                </div>
                <?php } ?>
            </div>
        <?php }else{ ?>
            <p class="text-center mb-0">Site is not giving any links</p>
        <?php } ?>
    </div>
</div>