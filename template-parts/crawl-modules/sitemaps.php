<?php 
$sitemaps       = get_field('sitemaps');
$sitemap_count  = $sitemaps && is_array($sitemaps) ? count($sitemaps) : 0;
$domain         = get_field('domain');
?>
    
<div class="card">
    <div class="card-header">
        <p class="h6">Crawlable Sitemaps (<?php echo $sitemap_count ?>)</p>
        <?php echo get_field( 'skip_sitemap' ) ? '<small class="tc-e d-block">Skipped</small>' : null; ?>
    </div>
    <div class="card-body">
        <?php if( $sitemaps && is_array($sitemaps) ){ ?>
        <ul class="mb-0">
            <?php foreach( $sitemaps as $sitemap ){ ?>
            <li>
                <p class="fs-14 mb-5"><?php echo str_replace( $domain, '', $sitemap['sitemap']); ?></p>
                <small>Last modified: <?php echo time_elapsed_string($sitemap['last_modified']) ?></small>
            </li>
            <?php } ?>
        </ul>
        <?php }else{
            echo 'No sitemaps coming.';
        } 
        ?>
    </div>
</div>