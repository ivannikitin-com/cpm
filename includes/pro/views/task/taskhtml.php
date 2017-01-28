<?php
$this_user = true;
$disabled = '';
$is_admin      = ( is_admin() ) ? 'yes' : 'no';
$single = false  ;
?>
<ul class='cpm-todolists cpm-my-todolists' >
    <?php
    foreach ($project as $project_id => $project_obj) {
        $ul_class = ( $tab == 'complete' ) ? 'cpm-todo-completed' : 'cpm-uncomplete-mytask';
        ?>
        <li>
            <article class="cpm-user-task cpm-todolist">
                <header class="cpm-list-header">
                    <h3>
                        <a href="<?php echo cpm_url_tasklist_index( $project_id ); ?>">
                            <?php echo $project[$project_id]['title']; ?>
                        </a>
                    </h3>
                </header>
                <ul class="cpm-todos  <?php echo $ul_class ?>">
                    <?php
                    foreach ($project_obj['tasks'] as $task) {
                        $list_id = $task->task_list_id ;
                        $start_date = isset( $task->start_date ) ? $task->start_date : '';
                        $class_name = ( $task->completed == 1 ) ? 'cpm-complete' : 'cpm-uncomplete';
                        $status_class = ( $task->completed == '1' ) ? 'cpm-complete' : 'cpm-uncomplete';
                        $title_link_status = apply_filters( 'cpm_task_title_link', true, $task, $project_id, $list_id, $single  );
                        $private_class = ( $task->task_privacy == 'yes') ? 'cpm-lock' : 'cpm-unlock';
                        //var_dump($task) ;
                        $task->task_privacy = false;
                        ?>
                        <li class="cpm-todo">
                            <?php // echo cpm_task_html( $task, $project_id, $task->task_list_id ); ?>

    <div class="cpm-todo-wrap clearfix">
        <div class="cpm-todo-content" >
            <div>
            <div class="cpm-col-7">
                <span class="cpm-spinner"></span>
                <input class="<?php echo $status_class; ?>" type="checkbox" <?php cpm_data_attr( array('single' => $single, 'list' => $list_id, 'project' => $project_id, 'is_admin' => $is_admin ) ); ?> value="<?php echo $task->task_id; ?>" name="" <?php checked( $task->completed, '1' ); ?> <?php echo $disabled; ?>>

                <?php if ( $single ) { ?>
                    <span class="cpm-todo-text"><?php echo $task->post_title; ?></span>
                    <span class="<?php echo $private_class; ?>"></span>
                <?php } else {
                    if ( $title_link_status ) {
                        ?>
                        <a class="task-title" href="<?php echo cpm_url_single_task( $project_id, $list_id, $task->task_id ); ?>">
                            <span class="cpm-todo-text"><?php echo $task->task; ?></span>
                            <span class="<?php echo $private_class; ?>"></span>
                        </a>
                        <?php
                    } else {?>
                        <span class="cpm-todo-text"><?php echo $task->task; ?></span>
                        <span class="<?php echo $private_class; ?>"></span>
                <?php } } ?>

                <?php
                // if the task is completed, show completed by

                if ( $task->completed == '1' && $task->completed_by ) {
                    $completion_time = cpm_get_date( $task->completed_on, false, 'M d' );
                    ?>
                    <span class="cpm-completed-by">
                        <?php printf( __( 'Completed by %s on %s', 'cpm' ), cpm_url_user( $task->completed_by, true ), $completion_time ) ?>
                    </span>
                <?php } ?>

                <?php
                if ( $task->completed != '1' ) {

                    if ( reset( $task->assigned_to ) != '-1' ) {
                        cpm_assigned_user( $task->assigned_to );
                    }

                    if ( $start_date != '' || $task->due_date != '' ) {
                        $task_status_wrap = ( date( 'Y-m-d', time() ) > date( 'Y-m-d', strtotime( $task->due_date) ) ) ? 'cpm-due-date' : 'cpm-current-date';
                        ?>
                        <span class="<?php echo $task_status_wrap; ?>">
                            <?php
                                if ( ( cpm_get_option( 'task_start_field', 'cpm_general' ) == 'on' ) && $start_date != '' ) {
                                    echo cpm_get_date( $start_date, false, 'M d' );
                                }

                                if ( $start_date != '' & $task->due_date != '' ) {
                                    echo ' - ';
                                }

                                if ( $task->due_date != '') {
                                    echo cpm_get_date( $task->due_date, false, 'M d' );
                                }
                            ?>
                        </span>
                        <?php
                    }
                }
                ?>
            </div>

            <div class="cpm-col-4">
                <?php if ( !$single ) { ?>

                    <span class="cpm-comment-count">
                        <a href="<?php echo cpm_url_single_task( $project_id, $list_id, $task->task_id ); ?>">
                            <?php  echo $task->comment_count  ; ?>
                        </a>
                    </span>

                <?php } ?>


            </div>

            <div class="clearfix"></div>
            </div>

        </div>


    </div>


                        </li>
                        <?php
                    }
                    ?>
                </ul>

            </article>
        </li>
        <?php
    }
    ?>
</ul>