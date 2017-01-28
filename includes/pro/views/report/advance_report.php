
 <h1 class="cpm-no-print">
        <?php _e( 'Project Report', 'cpm' ); ?>
        <a href="<?php echo cpm_report_page_url(); ?>" class="page-title-action" >Back to Report Dashboard</a>
    </h1>
<div id="cpm-advance-report">

    <?php
    $projects    = cpm()->project->get_projects();
    $co_workers  = cpm_get_co_worker();
    unset( $projects['total_projects'] );
    $init_action = isset( $_GET['filter'] ) ? reset( $_GET['filter'] ) : '';
    //var_dump( $_GET );
    ?>

    <div class="postbox">



        <div class=""   >

            <?php
            if ( ! isset( $_GET['filter'] ) ) {
                ?>
                <h3 class="cpm-form-title"><span><?php _e( 'Advance Search Project', 'cpm' ); ?></span></h3>
                <div class="cpm-postbox-wrap">
                    <form action="" method="post" id="cpm-report-form">
                        <div class="cpm-report-content">
                            <div class="cpm-action-parent">
                                <div class="cpm-report-action-wrap">
                                    <?php cpm_report_action_from( '-1' ); ?>
                                    <span class="cpm-clear"></span>
                                </div>
                                <div class="cpm-report-more button">+</div>
                            </div>
                        </div>

                        <div class="cpm-report-button-wrap">
                            <?php cpm_report_action_button(); ?>
                        </div>
                    </form>
                </div>
                <?php
            } else {
                ?>
                <h2 class="cpm-form-title"><span><?php _e( 'Advance Report Form', 'cpm' ); ?></span></h2>
                <div class="cpm-postbox-wrap">

                    <form action="" method="post" id="cpm-report-form">

                        <div class="cpm-report-content">

                            <?php
                            $i = 1;

                            foreach ( $_GET['filter'] as $action_key => $action_value ) {
                                $delete_icon = ( $i != 1 ) ? '<span class="cpm-report-cross button">-</span>' : '';
                                $add_more    = '<span class="cpm-report-more button">+</span>';
                                if ( $action_value == 'project' ) {
                                    ?>
                                    <div class="cpm-action-parent">
                                        <div class="cpm-report-action-wrap">
                                            <?php cpm_report_action_from( 'project' ); ?>
                                            <?php echo $add_more; ?>
                                            <?php echo $delete_icon; ?>
                                            <div class="cpm-report-projects-wrap">
                                                <?php cpm_report_project_form( $projects, $_GET['project'] ); ?>
                                            </div>
                                            <span class="cpm-clear"></span>
                                        </div>
                                    </div>
                                    <?php
                                } else if ( $action_value == 'co-worker' ) {
                                    ?>
                                    <div class="cpm-action-parent">
                                        <div class="cpm-report-action-wrap">
                                            <?php cpm_report_action_from( 'co-worker' ); ?>
                                            <?php echo $add_more; ?>
                                            <?php echo $delete_icon; ?>
                                            <div class="cpm-report-co-worker-wrap">
                                                <?php cpm_report_co_worker_form( $co_workers, $_GET['co_worker'] ); ?>
                                            </div>
                                            <span class="cpm-clear"></span>
                                        </div>
                                    </div>
                                    <?php
                                } else if ( $action_value == 'status' ) {
                                    ?>
                                    <div class="cpm-action-parent">
                                        <div class="cpm-report-action-wrap">
                                            <?php cpm_report_action_from( 'status' ); ?>
                                            <?php echo $add_more; ?>
                                            <?php echo $delete_icon; ?>
                                            <div class="cpm-report-status-wrap">
                                                <?php cpm_report_status_form( $_GET['status'] ); ?>
                                            </div>
                                            <span class="cpm-clear"></span>
                                        </div>
                                    </div>
                                    <?php
                                } else if ( $action_value == 'time' ) {
                                    ?>
                                    <div class="cpm-action-parent">
                                        <div class="cpm-report-action-wrap">
                                            <?php cpm_report_action_from( 'time' ); ?>
                                            <?php echo $add_more; ?>
                                            <?php echo $delete_icon; ?>
                                            <div class="cpm-report-time-wrap">
                                                <?php cpm_report_time_form( $_GET['interval'], $_GET['from'], $_GET['to'], $_GET['timemode'], true ); ?>
                                            </div>
                                            <span class="cpm-clear"></span>
                                        </div>

                                    </div>
                                    <?php
                                } else {
                                    ?>
                                    <div class="cpm-action-parent">
                                        <div class="cpm-report-action-wrap">
                                            <?php cpm_report_action_from( '-1' ); ?>
                                            <?php echo $add_more; ?>
                                            <?php echo $delete_icon; ?>
                                            <span class="cpm-clear"></span>
                                        </div>

                                    </div>
                                    <?php
                                }
                                $i ++;
                            }
                            ?>

                        </div>
                        <div class="cpm-report-button-wrap">
                            <?php cpm_report_action_button(); ?>
                        </div>

                    </form>
                </div>
                <?php
            }
            ?>
        </div>
    </div>

    <div>
    <div id="cpm-report-action-wrap" style="display: none;">
        <div class="cpm-action-parent">
            <div class="cpm-report-action-wrap" style="display: none;">
                <?php cpm_report_action_from( '-1' ); ?>
                <span class="cpm-report-more button">+</span>
                <span class="cpm-report-cross button">-</span>
                <span class="cpm-clear"></span>
            </div>
        </div>
    </div>

    <div id="cpm-report-projects-wrap" style="display: none;">
        <div class="cpm-report-projects-wrap" style="display: none;">
            <?php cpm_report_project_form( $projects ); ?>
        </div>
    </div>

    <div id="cpm-report-co-worker-wrap" style="display: none;">
        <div class="cpm-report-co-worker-wrap" style="display: none;">
            <?php cpm_report_co_worker_form( $co_workers ); ?>
        </div>
    </div>

    <div id="cpm-report-status-wrap" style="display: none;">
        <div class="cpm-report-status-wrap" style="display: none;">
            <?php cpm_report_status_form(); ?>
        </div>
    </div>

    <div id="cpm-report-time-wrap" style="display: none;">
        <div class="cpm-report-time-wrap" style="display: none;">
            <?php cpm_report_time_form(); ?>
        </div>
    </div>

    </div>

    <div id="cpm-report-table-wrap">
        <?php
        if ( isset( $_GET['filter'] ) ) {
            $data = $_GET;
            if ( reset( $data['filter'] ) == '-1' ) {
                echo '<h3>';
                _e( 'Please select an filter!', 'cpm' );
                echo '</h3>';
                //close cpm-report-table-wrap before return
                echo '</div>';
                return;
            }
            $results = cpm()->report->report_generate( $data );

            if ( ! $results->posts ) {
                echo '<h3>';
                _e( 'No result found!', 'cpm' );
                echo '</h3>';
                //close cpm-report-table-wrap before return
                echo '</div>';
                return;
            }
            echo cpm()->report->render_table( $results->posts, $data );
        }
        ?>

    </div>



</div>

