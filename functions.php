<?php
define( 'CRAWLER_DIR', get_template_directory() );
define( 'CRAWLER_URI', get_template_directory_uri() );
define( 'CRAWLER_VERSION', '1.1.3' );

include 'vendor/autoload.php';

@ini_set( 'max_execution_time', 0 );

include 'inc/scripts.php';

// Site Post type
include 'inc/post-types/site/post-type.php';
include 'inc/post-types/site/taxonomies.php';

include 'inc/helper.php';
include 'inc/ajax.php';
include 'inc/crawler.php';
include 'inc/crawler-actions.php';

require CRAWLER_DIR . '/plugin-update-checker/plugin-update-checker.php';
$update_checker = Puc_v4_Factory::buildUpdateChecker(
    'https://github.com/neverstopmedia/crawlerboss',
    __FILE__,
    get_template()
);
$update_checker->setAuthentication('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');
$update_checker->setBranch('main');

if(is_admin() && strpos($_SERVER['PHP_SELF'], 'themes.php') !== false){
    $update_checker->checkForUpdates();
}