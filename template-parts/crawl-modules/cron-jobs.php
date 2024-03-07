<?php

if( $cron_jobs = _get_cron_array() ){
    foreach( $cron_jobs as $interval => $schedule ){
        
        if( !is_array($schedule) )
        continue;

        foreach( $schedule as $name => $job ){

            if( !is_array($job) )
            continue;

            if( strpos( $name, 'crawl_cron_' ) !== false ){

                foreach( $job as $key => $event ){

                    ?>
                    <p class="mb-5 fw-sb">
                        <?php echo $name ?> <small class="fw-r">
                        <?php echo $event['schedule'] ?> - Next run in <?php echo time_elapsed_string(date( 'd-m-Y H:i:s', $interval )) ?></small>
                    </p> 
                    <div class="row mb-30">
                    <?php foreach( $event['args'] as $site ){ ?>
                        <div class="md-3">
                        <?php echo get_the_title($site); ?>
                        </div>
                    <?php } ?>
                    </div>
                    <?php
                    
                }
            }
            
        }
    }
}