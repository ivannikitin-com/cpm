<?php
$disabled = '';

if ( isset( $_GET['user_id'] ) && !empty( $_GET['user_id'] ) ) {
    if ( ! cpm_can_manage_projects() ) {
        printf( '<h1>%s</h1>', __( 'You do no have permission to access this page', 'cpm' ) );
        return;
    }

    $user_id         = intval( $_GET['user_id'] );
    $this_user       = false;
    $mytaskuser_user = get_user_by( 'id', $user_id );
    $title           = sprintf( "%s's tasks", $mytaskuser_user->display_name );
} else {
    $this_user       = true;
    $loin_user       = wp_get_current_user();
    $user_id         = $loin_user->ID;
    $mytaskuser_user = get_user_by( 'id', $user_id );
    $title           = __( 'My Tasks', 'cpm' );
}

// Текущий таб
if ( isset( $_GET['tab'] ) ) {
    $ctab = sanitize_text_field( $_GET['tab'] );
} else {
    $ctab = 'current';  // Таб по умолчанию
}

$user_id = apply_filters( 'cpm_my_task_user_id', $user_id );
$avatar  = get_avatar( $user_id, 64, '', $mytaskuser_user->display_name );

// Check user exist
if ( !get_userdata( $user_id ) ) {
    printf( '<h1>%s</h1>', __( 'The user could not be found!', 'cpm' ) );
    return;
}

if ( ! cpm_can_manage_projects() && $mytaskuser_user->ID != $user_id ) {
    printf( '<h1>%s</h1>', __( 'You do no have permission to access this page', 'cpm' ) );
    return;
}

$task    = CPM_Pro_Task::getInstance();
$project = $task->get_mytasks( $user_id );
$count   = $task->mytask_count( $user_id );
$ctab  = apply_filters( 'cpm_my_task_tab', $ctab );
?>
<!-- Start -->
<div class="wrap cpm my-tasks cpm-my-tasks">
    <div class="cpm-top-bar cpm-no-padding cpm-project-header cpm-project-head">
        <div class="cpm-row cpm-no-padding cpm-border-bottom">
            <div class="cpm-project-detail ">
                <?php if ( apply_filters( 'cpm_my_task_title', true ) ) { ?>
                    <h3 class="cpm-my-task"><?php echo $avatar . " " . $title; ?></h3>
                <?php } ?>

                <?php do_action( 'cpm_my_task_after_title', $project, $ctab ); ?>
            </div>
        </div>

        <div class="cpm-row cpm-project-group">
            <ul class="cpm-col-10 cpm-my-task-menu">

                <li  class="<?php if ( $ctab == 'overview' ) echo 'active' ?>">
                    <a href="<?php echo cpm_url_user_overview() ?>" class="cpm-my-taskoverview" data-item="overview" data-user="<?php echo $user_id ?>">
                        <?php _e( 'Task Overview', 'cpm' ); ?><div></div>
                    </a>
                </li>
                <li class="<?php if ( $ctab == 'useractivity' ) echo 'active' ?>">
                    <a href="<?php echo cpm_url_user_activity(); ?>" class="cpm-my-taskactivity" data-item="activity" data-user="<?php echo $user_id ?>">
                        <?php _e( 'Task Activity', 'cpm' ); ?><div></div>
                    </a>
                </li>

                <li class="<?php if ( $ctab == 'current' ) echo 'active' ?>">
                    <a href="<?php echo cpm_url_current_task(); ?>" data-item="current" data-user="<?php echo $user_id ?>" class="cpm-my-currenttask">
                        <?php _e( 'Current Tasks', 'cpm' ); ?> <div ><?php echo $count[ __( 'Current', 'cpm' ) ]; ?></div>
                    </a>
                </li>

                <li class="<?php if ( $ctab == 'outstanding' ) echo 'active' ?>">
                    <a href="<?php echo cpm_url_outstanding_task(); ?>" data-item="outstanding" data-user="<?php echo $user_id ?>" class="cpm-my-outstandigntask">
                        <?php _e( 'Outstanding Tasks', 'cpm' ); ?> <div><?php echo $count[ __( 'Outstanding', 'cpm' ) ]; ?></div>
                    </a>
                </li>
                <li class="<?php if ( $ctab == 'complete' ) echo 'active' ?>">
                    <a href="<?php echo cpm_url_complete_task(); ?>" data-item="complete" data-user="<?php echo $user_id ?>"  class="cpm-my-completetask">
                        <?php _e( 'Completed Tasks', 'cpm' ); ?> <div ><?php echo $count[ __( 'Completed', 'cpm' ) ]; ?></div>
                    </a>
                </li>
            </ul>
            <div class="cpm-col-2 cpm-sm-col-12 cpm-user-select">
                <?php
                if ( isset( $_GET['page'] ) && $_GET['page'] === 'cpm_task' ) {
					// PATCHED: I have added new capability 'cpm_can_view_users_tasks' for department heads
                    /* OLD_CODE: if ( current_user_can( 'cpm_can_view_users_tasks' ) ) { */
                    if ( cpm_can_manage_projects() ) {
						
						// PATCHED: Filter users only by following roles
						$users_with_role = implode(",", get_users ( array ( 
							'fields' => 'id',
							// TODO: This list should be in settings
							'role__in' => array( 'employee', 'lumper', 'head' ) 
						) ) );
						
                        $dropdown_users = wp_dropdown_users( array(
                            'selected'         	=> $user_id,
                            'class'            	=> 'cpm-mytask-switch-user',
                            'echo'             	=> false,
							'include_selected' 	=> true,
							'include' 			=> $users_with_role,
                            'show_option_none' 	=> __( 'Select an User', 'cpm' )
                        ) );

                        $dropdown_users = str_replace( '<select', '<select data-tab="' . $ctab . '"', $dropdown_users );
                        echo $dropdown_users;
                    }
                }
                ?>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>

    <div id="cpm-mytask-page-content">
        <?php $task->get_mytask_content( $user_id, $ctab ); ?>
    </div>
</div>
