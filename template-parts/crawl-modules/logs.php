<?php $args = []; ?>

<p>Logs found: <b class="logs-count"><?php echo Crawler_Logger_Helper::get_count( $args ) ?></b></p>
<?php if( $logs = Crawler_Logger_Helper::get() ){ ?>

<div class="logs-wrap">
    <table class="crawler-logs">
        <thead>
            <tr>
                <th></th>
                <th>Timestamp</th>
                <th>Context</th>
                <th>Text</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach( $logs as $key => $log ){ ?>
            <tr class="crawler-log log-<?php echo $key ?>" data-id="<?php echo $log->id ?>">
                <td class="crawler-log-id">#<?php echo $log->id ?></td>
                <td class="crawler-log-time"><?php echo $log->time ?></td>
                <td class="crawler-log-context"><?php echo $log->context ?></td>
                <td class="crawler-log-text" title="<?php echo $log->text ?>"><?php echo $log->text ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <?php }else{ ?>
        <p class="ss-alert ss-alert-danger">No Logs found</p>
    <?php } ?>
</div>