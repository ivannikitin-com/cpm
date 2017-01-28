<?php
if ( in_array( 'co-worker', $data['filter'] ) && $data['co_worker'] != '-1' ) {
    $uesr_restrict = true;
} else {
    $uesr_restrict = false;
}
$items        = array();
$start_enable = cpm_get_option( 'task_start_field', 'cpm_general' );

$time      = false;
$task_mode = false;
$list_mode = false;
$items     = array();

if ( in_array( 'time', $data['filter'] ) && $data['interval'] != '-1' ) {
    if ( $data['interval'] == 1 ) {
        $interval = 'post_year';
    } else if ( $data['interval'] == 2 ) {
        $interval = 'post_month';
    } else if ( $data['interval'] == 3 ) {
        $interval = 'post_week';
    }

    foreach ( $posts as $key => $obj ) {
        if ( ! isset( $obj->list_id ) && ! $obj->list_id ) {
            continue;
        }
        if ( ! isset( $obj->task_id ) && ! $obj->task_id ) {
            continue;
        }

        $assigned_to = get_post_meta( $obj->task_id, '_assigned' );
        //when search by uesr
        if ( $uesr_restrict ) {
            if ( in_array( $data['co_worker'], $assigned_to ) ) {
                $items[$obj->$interval][$obj->ID][$obj->list_id][$obj->task_id] = $key;
            }
        } else {
            $items[$obj->$interval][$obj->ID][$obj->list_id][$obj->task_id] = $key;
        }
    }
} else {
    foreach ( $posts as $key => $obj ) {
        if ( ! isset( $obj->list_id ) && ! $obj->list_id ) {
            continue;
        }
        if ( ! isset( $obj->task_id ) && ! $obj->task_id ) {
            continue;
        }
        $assigned_to = get_post_meta( $obj->task_id, '_assigned' );
        //when search by uesr
        if ( $uesr_restrict ) {
            if ( in_array( $data['co_worker'], $assigned_to ) ) {
                $items[$obj->ID][$obj->list_id][$obj->task_id] = $key;
            }
        } else {
            $items[$obj->ID][$obj->list_id][$obj->task_id] = $key;
        }
    }
}

if ( in_array( 'time', $data['filter'] ) && $data['interval'] != '-1' ) {
    $time = true;

    if ( $data['interval'] == 1 ) {
        $interval      = 'post_year';
        $interval_view = __( 'Year', 'cpm' );
    } else if ( $data['interval'] == 2 ) {
        $interval      = 'post_month';
        $interval_view = __( 'Month', 'cpm' );
    } else if ( $data['interval'] == 3 ) {
        $interval      = 'post_week';
        $interval_view = __( 'Week', 'cpm' );
    }

    if ( $data['timemode'] == 'list' ) {
        $list_mode = true;
    } else if ( $data['timemode'] == 'task' ) {
        $task_mode = true;
    }

    $from = $data['from'] ? $data['from'] : current_time( 'mysql' );
    $to   = $data['to'] ? $data['to'] : current_time( 'mysql' );
}

if ( ! $items ) {
    echo '<h3>';
    _e( 'No result found!', 'cpm' );
    echo '</h3>';
    return;
}

$i = 1;
if ( in_array( 'time', $data['filter'] ) && $data['interval'] != '-1' ) {

    foreach ( $items as $key => $item ) {
        ?>
        <div class="cpm-reoprt-individul-table-wrap postbox">
            <div class="cpm-interval-title"><strong><?php echo cpm_ordinal( $i ) . ' ' . $interval_view; ?></strong></div>
            <?php
            foreach ( $item as $project_id => $projects ) {

                $project = get_post( $project_id );
                ?>

                <div class="cpm-project-title">
                    <strong><?php _e( 'Project Title: ' ); ?></strong>
                    <a href="<?php echo cpm_url_project_overview( $project_id ); ?>"><?php echo $project->post_title; ?></a>
                </div>

            <?php
            foreach ( $projects as $list_id => $lists ) {
                $list = get_post( $list_id );
                ?>
                    <div class="cpm-list-title">
                        <strong><?php _e( 'Task List Title: ' ); ?></strong>
                        <a href="<?php echo cpm_url_single_tasklist( $project_id, $list_id ); ?>"><?php echo $list->post_title; ?></a>
                    </div>

                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e( 'Task', 'cpm' ); ?></th>
                                <th><?php _e( 'Assigned To', 'cpm' ); ?></th>
                                <th><?php _e( 'Start Date', 'cpm' ); ?></th>
                                <th><?php _e( 'Due Date', 'cpm' ); ?></th>
                                <th><?php _e( 'Status', 'cpm' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                <?php
                foreach ( $lists as $task_id => $tasks ) {
                    $task = cpm()->task->get_task( $task_id );
                    if ( $start_enable == 'on' ) {
                        $start_date = $task->start_date;
                    } else {
                        $start_date = $task->post_date;
                    }
                    //when search by uesr
                    if ( $uesr_restrict ) {
                        if ( ! in_array( $data['co_worker'], $task->assigned_to ) ) {
                            continue;
                        }
                    }
                    ?>

                                <tr>
                                    <td>
                                        <a href="<?php echo cpm_url_single_task( $project_id, $list_id, $task_id ); ?>">
                    <?php echo $task->post_title; ?>
                                        </a>
                                    </td>
                                    <td>
                                            <?php
                                            foreach ( $task->assigned_to as $user_id ) {
                                                $user = get_user_by( 'id', $user_id );
                                                echo cpm_url_user( $user->ID );
                                            }
                                            ?>
                                    </td>
                                    <td>
                                        <?php echo cpm_get_date( $start_date ); ?>
                                    </td>
                                    <td>
                                        <?php echo cpm_get_date( $task->due_date ); ?>
                                    </td>
                                    <td>
                                        <?php echo $task->completed ? __( 'Completed', 'cpm' ) : __( 'Incompleted', 'cpm' ); ?>
                                    </td>
                                </tr>

                                        <?php
                                    }
                                    echo '</tbody></table>';
                                }
                            }
                            ?>
                    </div>
                    <?php
                    $i ++;
                }
            } else {

                foreach ( $items as $project_id => $projects ) {
                    $project = get_post( $project_id );
                    ?>
                <div class="cpm-reoprt-individul-table-wrap postbox">
                    <div class="cpm-project-title-not-interval">
                        <strong><?php _e( 'Project Title: ' ); ?></strong>
                        <a href="<?php echo cpm_url_project_overview( $project_id ); ?>"><?php echo $project->post_title; ?></a>
                    </div>

        <?php
        foreach ( $projects as $list_id => $lists ) {
            $list = get_post( $list_id );
            ?>
                        <div class="cpm-list-title">
                            <strong><?php _e( 'Task List Title: ' ); ?></strong>
                            <a href="<?php echo cpm_url_single_tasklist( $project_id, $list_id ); ?>"><?php echo $list->post_title; ?></a>
                        </div>

                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php _e( 'Task', 'cpm' ); ?></th>
                                    <th><?php _e( 'Assigned To', 'cpm' ); ?></th>
                                    <th><?php _e( 'Start Date', 'cpm' ); ?></th>
                                    <th><?php _e( 'Due Date', 'cpm' ); ?></th>
                                    <th><?php _e( 'Status', 'cpm' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
            <?php
            foreach ( $lists as $task_id => $tasks ) {
                $task = cpm()->task->get_task( $task_id );
                if ( $start_enable == 'on' ) {
                    $start_date = $task->start_date;
                } else {
                    $start_date = $task->post_date;
                }
                //when search by uesr
                if ( $uesr_restrict ) {
                    if ( ! in_array( $data['co_worker'], $task->assigned_to ) ) {
                        continue;
                    }
                }
                ?>

                                    <tr>
                                        <td>
                                            <a href="<?php echo cpm_url_single_task( $project_id, $list_id, $task_id ); ?>">
                                    <?php echo $task->post_title; ?>
                                            </a>
                                        </td>
                                        <td>
                                                <?php
                                                foreach ( $task->assigned_to as $user_id ) {
                                                    $user = get_user_by( 'id', $user_id );
                                                    echo cpm_url_user( $user->ID );
                                                }
                                                ?>
                                        </td>
                                        <td>
                                            <?php echo cpm_get_date( $start_date ); ?>
                                        </td>
                                        <td>
                <?php echo cpm_get_date( $task->due_date ); ?>
                                        </td>
                                        <td>
                <?php echo $task->completed ? __( 'Completed', 'cpm' ) : __( 'Incompleted', 'cpm' ); ?>
                                        </td>
                                    </tr>

                                            <?php
                                        }
                                        echo '</tbody></table>';
                                    }
                                    ?>
                            </div>
                            <?php
                        }
                    }
