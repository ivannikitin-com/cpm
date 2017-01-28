<?php
$color  = array( '#0090D9', '#F29B27', '#85BE33');
?>

<div class="cpm-mytask-overview-page">
    <div class="cpm-col-3 cpm-sm-col-12 cpm-mytask-chart-overview">
        <h3 class="cpm-box-title"><?php _e( 'At a glance', 'CPM' )?></h3>

        <canvas id="cart-at-glance"></canvas>

        <ul>
            <?php
                $cj = 0;
                foreach ($count as $key => $val) {
                ?>
            <li>
                <span class="color-plate" style="background-color: <?php echo $color[$cj] ; ?>"></span> <?php echo $key ; ?>
                <span class="cpm-right task-count">
                    <?php printf( _n( '%s Task', '%s Tasks', $val, 'cpm' ), number_format_i18n( $val ) ) ?>
                </span>
                <span class="clearfix"></span>
            </li>
            <?php
                $cj++;
                }
            ?>
        </ul>
    </div>
    <div class="cpm-mytask-chart-statistics cpm-col-9 cpm-sm-col-12 ">
        <h3 class="cpm-box-title"><?php _e( 'Activity', 'cpm' ); ?></h3>

        <div class="">
            <div class="cpm-right">
                <span class="color-plate" style="background: #0f9abb"></span> <?php _e( 'Activity', 'cpm' ); ?>
                <span class="color-plate" style="background: #590340"></span> <?php _e( 'Assigned', 'cpm' ); ?>
                <span class="color-plate" style="background: #397D02"></span> <?php _e( 'Completed', 'cpm' ); ?>

                <select id="mytask-change-range">
                    <?php
                        $end   = strtotime(date("Y-m-d"));
                        $month = strtotime("-1 year", $end);

                        while ( $month <= $end ) {
                            $m =  date('F Y', $month);
                            $v = date('m,Y', $month) ;
                            $selected = ($month==$end  ? 'selected="selected"' : '' );
                            echo '<option  value="'.$v.'" data-user="'.$user_id.'" '.$selected.'> '.$m.' </option>' ;
                            $month = strtotime("+1 month", $month);
                        }
                    ?>
                </select>
            </div>
            <div class="clearfix"></div>
        </div>
         <div id="mytask-line-graph">
             <?php
             $task = CPM_Pro_Task::getInstance();
             $task->mytask_line_graph($user_id, $v) ;
             ?>

         </div>

    </div>
    <div class="clearfix"></div>
</div>
<div class="cpm-mycalender">
    <div class="cpm-col-12 ">
            <h3 class="cpm-box-title"><?php _e( 'My Calender', 'cpm' ); ?></h3>
            <div class="cpm-calender-content">
               <?php $task->mytask_calender($user_id) ; ?>
            </div>
    </div>

</div>

<script>
jQuery(function($) {
    var lh = $(".cpm-mytask-chart-overview").height() ;
     $(".cpm-mytask-chart-statics").css("height", lh);

    var pieData = [
        <?php
       $ci = 0 ;
        foreach ($count as $key => $val) { ?>
            {
                value : <?php echo $val ?>,
                color : '<?php echo $color[$ci]?>',
                label : '<?php echo $key; ?>',
                labelColor : 'white',
                labelFontSize : '16',

            },
        <?php
            $ci++ ;
            }
        ?>
        ];
        var pid = $("#cart-at-glance").get(0);
        var cag = pid.getContext("2d");
        new Chart(cag).Pie(pieData, {
            segmentShowStroke: true,
            animateRotate: true,
            animateScale: true,
            percentageInnerCutout: 55,
            labelAlign : 'left',
            mationSteps: 100,
            tooltipTemplate: "<%= label %> : <%= value %>"  ,
            multiTooltipTemplate: "<%= label %> - <%= value %>"
	});
});
</script>