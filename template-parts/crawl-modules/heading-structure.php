<?php
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;
$domain = get_field( 'domain' ) ?>
<div class="card">
    <div class="card-header">
        <p class="h6">Heading Structure</p>
    </div>
    <div class="heading-structure card-body limited d-flex flex-w ai-c jc-c">
        <div class="text-center">
            <?php if( $validity = get_field('validity') ){ ?>
            <div class="mb-10 alert alert-<?php echo $validity == 'invalid' ? 'danger' : 'success' ?>">
                The heading structure was <?php echo $validity ?> the last time this site was checked.
            </div>
            <?php } ?>
            <p class="mb-10">Check the Heading Structure of <?php echo get_the_title(); ?></p>
            <button data-id="<?php echo get_the_ID() ?>" class="btn btn-outline d-i-flex ai-c jc-c" id="checkHeadings" type="button">Check</button>
            <?php if( $heading_check_date = get_field('heading_structure_checked') ){ ?>
            <p class="mb-0 mt-10">The heading structure was last <b>checked <?php echo time_elapsed_string($heading_check_date) ?> ago</b></p>
            <?php }else{ ?>
            <p class="mb-0 mt-10">Heading structure was never checked for <?php echo get_the_title(); ?></p>
            <?php } ?>
        </div>
    </div>
</div>