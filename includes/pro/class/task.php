<?php
/**
 * Task list manager class
 *
 * @author Tareq Hasan
 */
class CPM_Pro_Task {
    private static $_instance;

    public static function getInstance() {
        if ( !self::$_instance ) {
            self::$_instance = new CPM_Pro_Task();
        }
        return self::$_instance;
    }

    function __construct() {
        add_filter( 'cpm_task_complete_response', array( $this, 'attach_my_task_count_on_done_open' ), 10, 5 );
        add_filter( 'cpm_task_open_response', array( $this, 'attach_my_task_count_on_done_open' ), 10, 5 );
        add_action( 'cpm_after_new_task', array( $this, 'mytask_flush_cache' ) );
        add_action( 'cpm_after_update_task', array( $this, 'mytask_flush_cache' ) );
        add_action( 'cpm_delete_task_after', array( $this, 'mytask_flush_cache' ) );
    }

    function task_count( $user_id ,  $range = false ) {
        global $wpdb;

        $query = "SELECT du.meta_value as due_date, n.meta_value as complete_status
            FROM `$wpdb->posts` AS t
            LEFT JOIN $wpdb->posts AS tl ON tl.ID = t.post_parent
            LEFT JOIN $wpdb->posts AS p ON p.ID = tl.post_parent
            LEFT JOIN $wpdb->postmeta AS m ON m.post_id = t.ID
            LEFT JOIN $wpdb->postmeta AS n ON n.post_id = t.ID
            LEFT JOIN $wpdb->postmeta AS du ON du.post_id = t.ID
            WHERE t.post_type = 'cpm_task' AND t.post_status = 'publish' ";

        if ( $range ) {
            $start_date = $range['start_date'];
            $end_date   = $range['end_date'];
            $query .= " AND post_date >= $end_date AND post_date <= $start_date";
        }

        $query .= "AND m.meta_key = '_assigned' AND m.meta_value = $user_id
            AND n.meta_key = '_completed'
            AND du.meta_key = '_due'
            AND p.post_title is not null";
        $task   = $wpdb->get_results( $query );
        $counts = array(
            __( 'Current', 'cpm' )     => 0,
            __( 'Outstanding', 'cpm' ) => 0,
            __( 'Completed', 'cpm' )   => 0
        );

        foreach ( $task as $key => $obj ) {
            if ( ( empty( $obj->due_date ) || date( 'Y-m-d', strtotime( $obj->due_date ) ) >= date( 'Y-m-d', time() ) ) && $obj->complete_status != 1 ) {
                $counts[ __( 'Current', 'cpm' ) ] += 1;
            }
            if ( !empty( $obj->due_date ) && date( 'Y-m-d', strtotime( $obj->due_date ) ) < date( 'Y-m-d', time() ) && $obj->complete_status != 1 ) {
                $counts[ __( 'Outstanding', 'cpm' ) ] += 1;
            }

            if ( $obj->complete_status == 1 ) {
                $counts[ __( 'Completed', 'cpm' ) ] += 1;
            }
        }

        return $counts;
    }

    function my_task_current_tab( $tab ) {
        return apply_filters( 'cpm_my_task_tab', $tab );
    }

    function current_user_task( $user_id, $tab ) {
        global $wpdb;

        // $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : false;
        $tab = $this->my_task_current_tab( $tab );

        if ( $tab == 'all' ) {
            $query1 = " ";
            $query2 = " ";

        } else if ( $tab == 'outstanding' ) {

            $query1 = "AND n.meta_key = '_completed' AND n.meta_value = '0'";
            $query2 = "AND due.meta_value != '' AND STR_TO_DATE( due.meta_value, '%Y-%m-%d') < STR_TO_DATE( NOW(), '%Y-%m-%d')";

        } else if ( $tab == 'complete' ) {

            $query1 = "AND n.meta_key = '_completed' AND n.meta_value = '1'";
            $query2 = '';

        } else { // PATCHED: Выводятся все задачи и просроченные и текущие на вкладке Текущие задачи
            $query1 = "AND n.meta_key = '_completed' AND n.meta_value = '0'";
            //$query2 = "AND ( due.meta_value = '' OR STR_TO_DATE( due.meta_value, '%Y-%m-%d') >= STR_TO_DATE( NOW(), '%Y-%m-%d') ) ";
            $query2 = "AND ( due.meta_value = '' OR TRUE ) ";
        }
        // TODO: Проверить, почему выводятся задачи из архивных проектов! 
        $que     = "SELECT t.post_title as task, t.comment_count as comment_count, t.ID as task_id, t.post_type as post_type ,
                    tl.post_title as list, tl.ID as task_list_id,
                    p.post_title as project_title, p.ID as project_id,
                    m.meta_value as assigned_to,
                    n.meta_value as completed,
                    due.meta_value as due_date,
                    strday.meta_value as start_date
                    FROM `$wpdb->posts` AS t
                    LEFT JOIN $wpdb->posts AS tl ON t.post_parent = tl.ID
                    LEFT JOIN $wpdb->posts AS p ON tl.post_parent = p.ID
                    LEFT JOIN $wpdb->postmeta AS m ON m.post_id = t.ID
                    LEFT JOIN $wpdb->postmeta AS n ON n.post_id = t.ID
                    LEFT JOIN $wpdb->postmeta AS due ON due.post_id = t.ID
                    LEFT JOIN $wpdb->postmeta AS strday ON strday.post_id = t.ID
                    WHERE t.post_type = 'cpm_task' AND t.post_status = 'publish'
                    AND m.meta_key = '_assigned' AND m.meta_value = $user_id
                    $query1
                    AND strday.meta_key = '_start'
                    AND due.meta_key = '_due' $query2
                    AND p.post_title is not null
                ORDER BY project_id DESC";

        $tasks   = $wpdb->get_results( $que );
        $project = array();

        foreach ( $tasks as $task ) {
            $projects[$task->project_id]['tasks'][] = $task;
            $projects[$task->project_id]['title']   = $task->project_title;
            $this->set_task_meta( $task );
        }

        $projects = isset( $projects ) ? $projects : '';

        return $projects;
    }

    function set_task_meta( &$task ) {
        $task->completed    = get_post_meta( $task->task_id, '_completed', true );
        $task->completed_by = get_post_meta( $task->task_id, '_completed_by', true );
        $task->completed_on = get_post_meta( $task->task_id, '_completed_on', true );
        $task->assigned_to  = get_post_meta( $task->task_id, '_assigned' );
        $task->due_date     = get_post_meta( $task->task_id, '_due', true );
        $task->start_date   = get_post_meta( $task->task_id, '_start', true );
        $task->task_privacy = get_post_meta( $task->task_id, '_task_privacy', true );
    }


    function get_mytask_content( $user_id, $ctab = 'overview' ) {
        $content = '';

        if ( $ctab == 'overview' ) {
            $content =  $this->user_overview($user_id) ;

        } else if ( $ctab == 'useractivity' ) {
            $content = $this->user_activity($user_id) ;
        } else {
            $task_list =  $this->current_user_task( $user_id, $ctab ) ;
            $content   = $this->taskhtmloutput( $task_list , $user_id, $ctab) ;
        }

    }

    function get_user_all_task( $user_id, $project_id ) {
        global $wpdb;

       $psql = ( $project_id != 0 )  ?  " AND p.ID = $project_id " : " ";

       $que = "SELECT t.post_title as task, t.comment_count as comment_count, t.ID as task_id, tl.post_title as list, tl.ID as task_list_id,
                    p.post_title as project_title, p.ID as project_id, m.meta_value as assigned_to, n.meta_value as completed, due.meta_value as due_date,
                    strday.meta_value as start_date
                FROM `$wpdb->posts` AS t
                LEFT JOIN $wpdb->posts AS tl ON t.post_parent = tl.ID
                LEFT JOIN $wpdb->posts AS p ON tl.post_parent = p.ID
                LEFT JOIN $wpdb->postmeta AS m ON m.post_id = t.ID
                LEFT JOIN $wpdb->postmeta AS n ON n.post_id = t.ID
                LEFT JOIN $wpdb->postmeta AS due ON due.post_id = t.ID
                LEFT JOIN $wpdb->postmeta AS strday ON strday.post_id = t.ID
                WHERE t.post_type = 'cpm_task' AND t.post_status = 'publish'
                    AND m.meta_key = '_assigned' AND m.meta_value = $user_id
                    $psql
                    AND strday.meta_key = '_start'
                    AND due.meta_key = '_due'
                    AND p.post_title is not null
                    GROUP BY task_id
                    ORDER BY project_id DESC";

        $tasks   = $wpdb->get_results( $que );

        return $tasks;
    }


    function get_mytasks( $user_id ) {
        $cache_key = 'cpm_mytask_' . $user_id;
        $project   = wp_cache_get( $cache_key );

        if ( $project === false ) {
            $project = $this->current_user_task( $user_id, false );
            wp_cache_set( $cache_key, $project );
        }

        return $project;
    }

    /**
     * Counts my task
     *
     * @param type $response
     * @param type $task_id
     * @param type $list_id
     * @param type $project_id
     *
     * @return type
     */
    function mytask_count( $user_id ) {
        $response  = array();
        $user_id   = intval( $user_id );
        $cache_key = 'cpm_mytask_count_' . $user_id;
        $task      = wp_cache_get( $cache_key );

        if ( $task === false ) {
            $task = $this->task_count($user_id);
            wp_cache_set( $cache_key, $task );
        }

        $response[ __( 'Current', 'cpm' ) ]     = $task[ __( 'Current', 'cpm' ) ];
        $response[ __( 'Outstanding', 'cpm' ) ] = $task[ __( 'Outstanding', 'cpm' ) ];
        $response[ __( 'Completed', 'cpm' ) ]   = $task[ __( 'Completed', 'cpm' ) ];

        return $response;
    }

    /**
     * Add the task count when a task is complete/uncompleted
     *
     * @param  array  $response
     * @param  integer  $task_id
     * @param  integer  $list_id
     * @param  integer  $project_id
     * @param  integer  $user_id
     *
     * @return array
     */
    public function attach_my_task_count_on_done_open( $response, $task_id, $list_id, $project_id, $user_id ) {
        $counts = $this->mytask_count( $user_id );

        array_merge( $response, $counts );

        return $response;
    }

    function mytask_flush_cache( $task_id ) {
        $user_id = get_current_user_id();
        wp_cache_delete( 'cpm_mytask_' . $task_id . $user_id );
        wp_cache_delete( 'cpm_mytask_count_' . $task_id . $user_id );
    }

    function mytask_date_chart( $user_id, $start_date, $end_date ) {
        global $wpdb;

        // User Total activity
        $where          = $wpdb->prepare( "WHERE user_id = '%d' AND DATE(comment_date) >= '%s' AND DATE(comment_date) <= '%s'", $user_id, $start_date, $end_date );
        $sql            = "SELECT * FROM {$wpdb->comments} $where  ";
        $total_activity = $wpdb->get_results( $sql );

        // User assign Task
        $csql = "SELECT  * FROM {$wpdb->prefix}cpm_tasks as task , {$wpdb->prefix}cpm_project_items  as taskitem
                    WHERE task.item_id = taskitem.id
                    AND taskitem.complete_status = 0
                    AND task.user_id = {$user_id}
                    AND DATE(task.start) >= '{$start_date}'
                    AND DATE(task.start) <= '{$end_date}'
                    ";
        $assign_tasks = $wpdb->get_results( $csql );

        // User Complete Task
        $cosql = "SELECT  * FROM {$wpdb->prefix}cpm_tasks as task , {$wpdb->prefix}cpm_project_items  as taskitem
                    WHERE task.item_id = taskitem.id
                    AND taskitem.complete_status = 1
                    AND task.user_id = {$user_id}
                    AND DATE(taskitem.complete_date) >= '{$start_date}'
                    AND DATE(taskitem.complete_date) <= '{$end_date}'
                    ";

        $complete_tasks          = $wpdb->get_results( $cosql );
        $response['date_list']   = '';
        $response['ctask_list']  = '';
        $response['cotask_list'] = '';
        $date_list               = array( );

        foreach ( $assign_tasks as $task ) {
            $tdate = date( 'M d', strtotime( $task->start ) );
            array_push( $date_list, $tdate );

            if ( !isset( $response['ctask_list'][$tdate] ) ) {
                $response['ctask_list'][$tdate] = 1;
            } else {
                $response['ctask_list'][$tdate] += 1;
            }
        }

        foreach ( $complete_tasks as $ctask ) {
            $ctdate = date( 'M d', strtotime( $ctask->start ) );
            array_push( $date_list, $ctdate );

            if ( !isset( $response['cotask_list'][$ctdate] ) ) {
                $response['cotask_list'][$ctdate] = 1;
            } else {
                $response['cotask_list'][$ctdate] += 1;
            }
        }

        foreach ( $total_activity as $activity ) {
            $adate = date( 'M d', strtotime( $activity->comment_date ) );
            array_push( $date_list, $adate );

            if ( !isset( $response['activity_list'][$adate] ) ) {
                $response['activity_list'][$adate] = 1;
            } else {
                $response['activity_list'][$adate] += 1;
            }
        }

        $date_list = array_unique( $date_list );
        array_multisort( $date_list );
        $response['date_list'] = $date_list;

        return $response;
    }

    function user_overview( $user_id ) {
        $count = $this->mytask_count($user_id);

        include CPM_PATH . '/includes/pro/views/task/overview.php';
    }

    function user_activity( $user_id ) {
        include CPM_PATH . '/includes/pro/views/task/activity.php';
    }

    function mytask_line_graph( $user_id, $range ) {

        $range             = explode( ',', $range );
        $end_date          = date( "{$range[1]}-{$range[0]}-31" );
        $start_date        = date( "{$range[1]}-{$range[0]}-1" );
        $mytask_date_chart = $this->mytask_date_chart( $user_id, $start_date, $end_date );

        foreach ( $mytask_date_chart['date_list'] as $cdate ) {
            $act_data       = isset( $mytask_date_chart['activity_list'][$cdate] ) ? $mytask_date_chart['activity_list'][$cdate] : 0;
            $ctask_data     = isset( $mytask_date_chart['ctask_list'][$cdate] ) ? $mytask_date_chart['ctask_list'][$cdate] : 0;
            $cotask_data    = isset( $mytask_date_chart['cotask_list'][$cdate] ) ? $mytask_date_chart['cotask_list'][$cdate] : 0;
            $str_date[]     = '"' . $cdate . '"';
            $str_task[]     = '"' . $ctask_data . '"';
            $str_ctask[]    = '"' . $cotask_data . '"';
            $str_activity[] = '"' . $act_data . '"';
        }

        if ( empty( $str_date ) ) {
            echo '<p class="cpm-error">' . __( 'No Data Found!', 'cpm' ) . '</p>';
        } else {
            $str_date     = implode( $str_date, ',' );
            $str_activity = implode( $str_activity, ',' );
            $str_task     = implode( $str_task, ',' );
            $str_ctask    = implode( $str_ctask, ',' );
            include CPM_PATH . '/includes/pro/views/task/mytask-line-graph.php';
        }
    }

    function taskhtmloutput( $project, $user_id, $tab ) {
        global $current_user;

        if ( empty( $project ) ) {
            printf( '<p>%s</p>', __( 'No Data Found!', 'cpm' ) );
        } else {
            include CPM_PATH . '/includes/pro/views/task/taskhtml.php';
        }
    }

    function mytask_calender($user_id){

        include CPM_PATH . '/includes/pro/views/task/mycalender.php';
    }


    /**
     * get user activity
     *
     * @param type $project_id
     * @param type $args
     *
     * @return type
     */
    function get_user_activity( $user_id, $args = array() ) {

        $defaults = array(
            'order'  => 'DESC',
            'number'   => 20,
            //'search' => '[',
            'offset' => 0
        );

        $args            = wp_parse_args( $args, $defaults );
        $args['user_id'] = $user_id;
        $args['type']    = 'cpm_activity';

        $response =  get_comments( apply_filters( 'cpm_mytask_user_activity_args', $args, $user_id ) );

        return $response ;
    }

    function  get_user_activity_total($user_id){

        $carg = array(
            'order'  => 'DESC',
            'offset' => 0,
            'type' => 'cpm_activity',
            'search' => '[',
            'user_id' => $user_id
        );

        $count =  get_comments( apply_filters( 'cpm_mytask_user_activity_args', $carg, $user_id ) );

       return $count = count($count) ;
    }

}