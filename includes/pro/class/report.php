<?php

/**
 * Report Event Handler
 *
 * @class 		CPM_Report
 * @version		1.2
 */
class CPM_Pro_Report {

    /**
     * @var The single instance of the class
     * @since 1.2
     */
    protected static $_instance = null;

    /**
     * @var $_POST or $_GET data
     * @since 1.2
     */
    protected static $form_data = null;

    /**
     * Main Instance
     *
     * @since 1.2
     * @return Main instance
     */
    public $_timetracker =  false ;

    public static function getInstance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new CPM_Pro_Report();
        }
        return self::$_instance;
    }

    /**
     * Class initial do
     *
     * @since 1.2
     * @return type
     */
    function __construct() {
        add_action( 'init', array( $this, 'report_form_redirect' ) );

        //AJAX call hande
        add_action( 'wp_ajax_cpm_run_report', array( $this, 'run_report' ) );
        add_action( 'wp_ajax_cpm_report_overdue_task', array( $this, 'overdue_task' ) );
        add_action( 'wp_ajax_cpm_report_complete_task', array( $this, 'complete_task' ) );
        add_action( 'wp_ajax_cpm_report_useractivity', array( $this, 'useractivity' ) );
        add_action( 'wp_ajax_cpm_report_taskbyproject', array( $this, 'taskbyproject' ) );
        add_action( 'wp_ajax_cpm_report_unassignedtask', array( $this, 'unassignedtask' ) );
        add_action( 'wp_ajax_cpm_report_taskbymilestone', array( $this, 'taskbymilestone' ) );
        add_action( 'wp_ajax_cpm_report_csv_output', array( $this, 'report_csv' ) );
        add_action( 'wp_ajax_cpm_report_filtermilestone', array( $this, 'filtermilestone' ) );

        if ( class_exists( 'CPM_Time_Tracker' ) ){
            $this->_timetracker = TRUE;
        }
    }

    /**
     * Redirect report form data
     *
     * @since 1.2
     * @return type
     */
    public function filtermilestone() {
        $posted     = $_POST;
        check_ajax_referer( 'cpm_nonce' );
        $project_id = ( isset( $posted['project'] ) AND $posted['project'] != '-1' ) ? intval( $posted['project'] ) : NULL;
        $milestones = cpm()->milestone->get_by_project( $project_id );
        if ( $milestones ) {
            $milestone_array[] = array(
                'text' => __( "Select a milestone", 'cpm' ),
                'val'  => ''
            );
            foreach ( $milestones as $milestone ) {
                $milestone_array[] = array(
                    'text' => $milestone->post_title,
                    'val'  => $milestone->ID
                );
            }
        } else {
            $milestone_array[] = array(
                'text' => __( "No milestone on this Project", "cpm" ),
                'val'  => ''
            );
        }
        echo json_encode( $milestone_array );
        exit();
    }

    public function overdue_task() {
        $posted = $_POST;

        check_ajax_referer( 'cpm_nonce' );

        $project_id = ( isset( $posted['project'] ) AND $posted['project'] != '-1' ) ? intval( $posted['project'] ) : NULL;
        $coworker   = ( isset( $posted['co_worker'] ) AND $posted['co_worker'] != '-1' ) ? intval( $posted['co_worker'] ) : NULL;
        $where      = '1 = 1';

        $response['reporttitle']      = __( 'Overdue Task', 'cpm' );
        $response['selectedproject']  = __( 'All Project', 'cpm' );
        $response['selectedcoworder'] = __( 'All Coworker', 'cpm' );
        $response['extrahead']        = '';

        $response['timetracker'] = $this->_timetracker ;


        if ( $coworker != NULL ) {
            $where .= " AND tasktable.user_id =  '$coworker' ";
            $response['selectedcoworder'] = get_userdata( $coworker )->display_name;
        }

        if ( $project_id != NULL ) {
            $where .= "  AND projectt.ID = '$project_id' ";
            $response['selectedproject'] = get_post( $project_id )->post_title;
        }
        global $wpdb;
        $report_day = date( "Y-m-d 23:59:59" );
        $post       = $wpdb->prefix . 'posts';
        $meta       = $wpdb->prefix . 'postmeta';

        $userr = $wpdb->prefix . 'cpm_user_role';

        $task_table   = $wpdb->prefix . 'cpm_tasks';
        $project_item = $wpdb->prefix . 'cpm_project_items';

        $sql = "
                SELECT
                taskt.ID as task_id, taskt.post_title as task_name,
                listt.ID as list_id, listt.post_title as list_name,
                projectt.ID as project_id, projectt.post_title as project,
                tasktable.start as start_date, tasktable.due as due_date,
                GROUP_CONCAT(tasktable.user_id) as assign_user,
                projectitem.complete_date as complete_date, projectitem.complete_status as complete_status
                FROM $post as taskt
                LEFT JOIN $post as listt ON  listt.ID = taskt.post_parent
                LEFT JOIN $post as projectt ON  projectt.ID = listt.post_parent
                LEFT JOIN $project_item as projectitem ON projectitem.object_id = taskt.ID
                LEFT JOIN $task_table as tasktable ON tasktable.item_id = projectitem.id
                WHERE
                {$where}
                AND taskt.post_type = 'cpm_task'
                AND tasktable.due <= '$report_day'
                AND projectitem.complete_status = 0

                GROUP BY taskt.ID
                ORDER BY projectt.ID ,  listt.ID
               ";

        $task_list = $wpdb->get_results( $sql );

        $response['output']      = $this->render_report_data( $task_list );
        $response['countresult'] = count( $task_list );

        $tran_id = 'rcsv_' . get_current_user_id();
        set_transient( $tran_id, $response, HOUR_IN_SECONDS );

        echo json_encode( $response );

        exit();
    }

    public function complete_task() {
        check_ajax_referer( 'cpm_nonce' );
        $posted     = $_POST;
        $project_id = ( isset( $posted['project'] ) AND $posted['project'] != '-1' ) ? intval( $posted['project'] ) : NULL;
        $coworker   = ( isset( $posted['co_worker'] ) AND $posted['co_worker'] != '-1' ) ? intval( $posted['co_worker'] ) : NULL;
        $where      = '1 = 1';

        $response['reporttitle']      = __( 'Completed Task', 'cpm' );
        $response['selectedproject']  = __( 'All Project', 'cpm' );
        $response['selectedcoworder'] = __( 'All Coworker', 'cpm' );
        $response['extrahead']        = '';
        $response['timetracker'] = $this->_timetracker ;

        if ( $coworker != NULL ) {
            $where .= " AND tasktable.user_id =  '$coworker' ";
            $response['selectedcoworder'] = get_userdata( $coworker )->display_name;
        }

        if ( $project_id != NULL ) {
            $where .= "  AND projectt.ID = '$project_id' ";
            $response['selectedproject'] = get_post( $project_id )->post_title;
        }
        global $wpdb;
        $report_day = date( "Y-m-d 23:59:59" );
        $post       = $wpdb->prefix . 'posts';
        $meta       = $wpdb->prefix . 'postmeta';

        $userr = $wpdb->prefix . 'cpm_user_role';

        $task_table   = $wpdb->prefix . 'cpm_tasks';
        $project_item = $wpdb->prefix . 'cpm_project_items';
        $wpdb->query("SET @@GLOBAL.sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        $sql = "
                SELECT
                taskt.ID as task_id, taskt.post_title as task_name,
                listt.ID as list_id, listt.post_title as list_name,
                projectt.ID as project_id, projectt.post_title as project,
                tasktable.start as start_date, tasktable.due as due_date,
                GROUP_CONCAT(tasktable.user_id) as assign_user,
                projectitem.complete_date as complete_date, projectitem.complete_status as complete_status
                FROM $post as taskt
                LEFT JOIN $post as listt ON  listt.ID = taskt.post_parent
                LEFT JOIN $post as projectt ON  projectt.ID = listt.post_parent
                LEFT JOIN $project_item as projectitem ON projectitem.object_id = taskt.ID
                LEFT JOIN $task_table as tasktable ON tasktable.item_id = projectitem.id
                WHERE
                {$where}
                AND taskt.post_type = 'cpm_task'
                AND projectitem.complete_date <= '$report_day'
                AND projectitem.complete_status = 1
                GROUP BY taskt.ID
                ORDER BY projectt.ID ,  listt.ID
               ";

        $task_list = $wpdb->get_results( $sql );

        // include_once CPM_PRO_PATH . '/views/report/datatable.php';

        $response['output']      = $this->render_report_data( $task_list );
        $response['countresult'] = count( $task_list );

        $tran_id = 'rcsv_' . get_current_user_id();
        set_transient( $tran_id, $response, HOUR_IN_SECONDS );

        echo json_encode( $response );

        exit();
    }

    public function useractivity() {
        check_ajax_referer( 'cpm_nonce' );
        $posted    = $_POST;
        $user_id   = ( isset( $posted['co_worker'] ) AND $posted['co_worker'] != '-1' ) ? intval( $posted['co_worker'] ) : NULL;
        $form_date = ( isset( $posted['start_date'] ) AND $posted['start_date'] != '' ) ? $posted['start_date'] : date( "Y-m-d" );
        $to_date   = ( isset( $posted['end_date'] ) AND $posted['end_date'] != '' ) ? $posted['end_date'] : date( "Y-m-d" );

        $date_format = get_option( 'date_format' );

        $where = '1 = 1';

        $response['reporttitle']      = __( 'User Activity Report', 'cpm' );
        $response['selectedproject']  = __( 'All Project', 'cpm' );
        $response['selectedcoworder'] = '';
        $response['extrahead']        = __( '<b> Rnage </b>', 'cpm' ) . "<br/> ". mysql2date( $date_format, $form_date) ." to  ".mysql2date( $date_format, $to_date);
        $response['timetracker'] = $this->_timetracker ;


        if ( $user_id != NULL ) {
            $response['selectedcoworder'] = get_userdata( $user_id )->display_name;
            $where .= " AND tasktable.user_id =  '$user_id' " ;
        }


        global $wpdb;
        //$report_day = date( "Y-m-d" );
        $post = $wpdb->prefix . 'posts';
        $meta = $wpdb->prefix . 'postmeta';

        $userr = $wpdb->prefix . 'cpm_user_role';

        $task_table   = $wpdb->prefix . 'cpm_tasks';
        $project_item = $wpdb->prefix . 'cpm_project_items';

        $sql = "
                SELECT
                taskt.ID as task_id, taskt.post_title as task_name,
                listt.ID as list_id, listt.post_title as list_name,
                projectt.ID as project_id, projectt.post_title as project,
                tasktable.start as start_date, tasktable.due as due_date,
                GROUP_CONCAT(tasktable.user_id) as assign_user,
                projectitem.complete_date as complete_date, projectitem.complete_status as complete_status
                FROM $post as taskt
                LEFT JOIN $post as listt ON  listt.ID = taskt.post_parent
                LEFT JOIN $post as projectt ON  projectt.ID = listt.post_parent
                LEFT JOIN $project_item as projectitem ON projectitem.object_id = taskt.ID
                LEFT JOIN $task_table as tasktable ON tasktable.item_id = projectitem.id
                WHERE
                {$where}
                AND taskt.post_type = 'cpm_task'
                AND (
                        ( projectitem.complete_date <= '$to_date' AND  projectitem.complete_date >= '$form_date')
                        OR ( tasktable.start <= '$to_date' AND tasktable.start >= '$form_date')
                        OR ( tasktable.due <= '$to_date' AND tasktable.due >= '$form_date')
                        OR ( projectitem.complete_status != '1'  AND  tasktable.start <= '$to_date' )
                    )

                GROUP BY taskt.ID
                ORDER BY projectt.ID ,  listt.ID
               ";

        $task_list = $wpdb->get_results( $sql );

        // include_once CPM_PRO_PATH . '/views/report/datatable.php';

        $response['output']      = $this->render_report_data( $task_list );
        $response['countresult'] = count( $task_list );

        // Set Transeant for access/download report

        $tran_id = 'rcsv_' . get_current_user_id();
        set_transient( $tran_id, $response, HOUR_IN_SECONDS );
        echo json_encode( $response );

        exit();
    }

    public function taskbyproject() {
        check_ajax_referer( 'cpm_nonce' );
        $posted      = $_POST;
        $project_id  = ( isset( $posted['project'] ) AND $posted['project'] != '-1' ) ? intval( $posted['project'] ) : NULL;
        $task_status = ( isset( $posted['task_status'] ) AND $posted['task_status'] != '-1' ) ? intval( $posted['task_status'] ) : '-1';


        $where = '1 = 1';

        $response['reporttitle']      = __( 'All Task by Project', 'cpm' );
        $response['selectedproject']  = __( 'All Project', 'cpm' );
        $response['selectedcoworder'] = false;
        $response['timetracker'] = $this->_timetracker ;
        $status_head = __( "Task Status : All Task", 'cpm' );


        if ( $project_id != NULL ) {
            $where .= "  AND projectt.ID = '$project_id' ";
            $response['selectedproject'] = get_post( $project_id )->post_title;
        }

        if ( $task_status != '-1' ) {
            $where .=" AND projectitem.complete_status =  $task_status ";
            $status_head = ($task_status == 1) ? "All Complete Task" : " All Incomplete Task";
        }

        $response['extrahead'] = "<h3> $status_head </h3>";
        global $wpdb;

        //$report_day = date( "Y-m-d" );

        $post = $wpdb->prefix . 'posts';
        $meta = $wpdb->prefix . 'postmeta';

        $userr = $wpdb->prefix . 'cpm_user_role';

        $task_table   = $wpdb->prefix . 'cpm_tasks';
        $project_item = $wpdb->prefix . 'cpm_project_items';

        $sql = "
                SELECT
                taskt.ID as task_id, taskt.post_title as task_name,
                listt.ID as list_id, listt.post_title as list_name,
                projectt.ID as project_id, projectt.post_title as project,
                tasktable.start as start_date, tasktable.due as due_date,
                GROUP_CONCAT(tasktable.user_id) as assign_user,
                projectitem.complete_date as complete_date, projectitem.complete_status as complete_status
                FROM $post as taskt
                LEFT JOIN $post as listt ON  listt.ID = taskt.post_parent
                LEFT JOIN $post as projectt ON  projectt.ID = listt.post_parent
                LEFT JOIN $project_item as projectitem ON projectitem.object_id = taskt.ID
                LEFT JOIN $task_table as tasktable ON tasktable.item_id = projectitem.id
                WHERE
                $where
                AND taskt.post_type = 'cpm_task'
                GROUP BY taskt.ID
                ORDER BY projectt.ID ,  listt.ID
               ";

        $task_list = $wpdb->get_results( $sql );

        // include_once CPM_PRO_PATH . '/views/report/datatable.php';

        $response['output']      = $this->render_report_data( $task_list );
        $response['countresult'] = count( $task_list );

        $tran_id = 'rcsv_' . get_current_user_id();
        set_transient( $tran_id, $response, HOUR_IN_SECONDS );
        echo json_encode( $response );

        exit();
    }

    public function taskbymilestone() {
        check_ajax_referer( 'cpm_nonce' );
        $posted        = $_POST;
        $project_id    = ( isset( $posted['project'] ) AND $posted['project'] != '-1' ) ? intval( $posted['project'] ) : NULL;
        $milsestone_id = ( isset( $posted['milsestone'] ) AND $posted['milsestone'] != '-1' ) ? intval( $posted['milsestone'] ) : NULL;


        $where = '1 = 1';

        $response['reporttitle']      = __( 'All Task by Milestone', 'cpm' );
        $response['selectedproject']  = __( 'All Project', 'cpm' );
        $response['selectedcoworder'] = __( 'All Coworker', 'cpm' );
        $response['timetracker'] = $this->_timetracker ;
        $status_head = "";


        if ( $project_id != NULL ) {
            // $where .= "  AND projectt.ID = '$project_id' ";
            $response['selectedproject'] = get_post( $project_id )->post_title;
        }
        if ( $milsestone_id != NULL ) {
            $where .= "  AND milestonet.ID = '$milsestone_id' ";
            $smilestone = get_post( $milsestone_id )->post_title;
        }



        $response['extrahead'] = "<b>" . __( 'Milestone', 'cpm' ) . "<br/>" . $smilestone . "</b>";

        global $wpdb;

        $post = $wpdb->prefix . 'posts';
        $meta = $wpdb->prefix . 'postmeta';

        $userr = $wpdb->prefix . 'cpm_user_role';

        $task_table   = $wpdb->prefix . 'cpm_tasks';
        $project_item = $wpdb->prefix . 'cpm_project_items';

        $sql = "
                SELECT
                taskt.ID as task_id, taskt.post_title as task_name,
                listt.ID as list_id, listt.post_title as list_name,
                projectt.ID as project_id, projectt.post_title as project,
                tasktable.start as start_date, tasktable.due as due_date,
                GROUP_CONCAT(tasktable.user_id) as assign_user,
                projectitem.complete_date as complete_date, projectitem.complete_status as complete_status
                FROM $post as taskt
                LEFT JOIN $post as listt ON  listt.ID = taskt.post_parent
                LEFT JOIN $post as projectt ON  projectt.ID = listt.post_parent
                LEFT JOIN $post as milestonet ON  milestonet.ID = $milsestone_id
                LEFT JOIN $meta as milestonemeta ON milestonemeta.meta_value = $milsestone_id
                LEFT JOIN $project_item as projectitem ON projectitem.object_id = taskt.ID
                LEFT JOIN $task_table as tasktable ON tasktable.item_id = projectitem.id
                WHERE
                $where
                AND milestonemeta.post_id = listt.ID
                AND taskt.post_type = 'cpm_task'
                GROUP BY taskt.ID
                ORDER BY projectt.ID ,  listt.ID
               ";

        $task_list = $wpdb->get_results( $sql );

        // include_once CPM_PRO_PATH . '/views/report/datatable.php';

        $response['output']      = $this->render_report_data( $task_list );
        $response['countresult'] = count( $task_list );

        $tran_id = 'rcsv_' . get_current_user_id();
        set_transient( $tran_id, $response, HOUR_IN_SECONDS );
        echo json_encode( $response );

        exit();
    }

    public function unassignedtask() {
        check_ajax_referer( 'cpm_nonce' );
        $posted = $_POST;


        $where = '1 = 1';

        $response['reporttitle']     = __( 'All Unassigned Task', 'cpm' );
        $response['selectedproject'] = __( 'All Project', 'cpm' );
        $response['timetracker'] = $this->_timetracker ;


        global $wpdb;

        //$report_day = date( "Y-m-d" );

        $post = $wpdb->prefix . 'posts';
        $meta = $wpdb->prefix . 'postmeta';

        $userr = $wpdb->prefix . 'cpm_user_role';

        $task_table   = $wpdb->prefix . 'cpm_tasks';
        $project_item = $wpdb->prefix . 'cpm_project_items';
        $sql          = "
                SELECT
                taskt.ID as task_id, taskt.post_title as task_name,
                listt.ID as list_id, listt.post_title as list_name,
                projectt.ID as project_id, projectt.post_title as project,
                tasktable.start as start_date, tasktable.due as due_date,
                GROUP_CONCAT(tasktable.user_id) as assign_user,
                projectitem.complete_date as complete_date, projectitem.complete_status as complete_status
                FROM $post as taskt
                LEFT JOIN $post as listt ON  listt.ID = taskt.post_parent
                LEFT JOIN $post as projectt ON  projectt.ID = listt.post_parent
                LEFT JOIN $project_item as projectitem ON projectitem.object_id = taskt.ID
                LEFT JOIN $task_table as tasktable ON tasktable.item_id = projectitem.id
                WHERE
                tasktable.user_id = -1
                AND projectitem.complete_status != 1
                AND taskt.post_type = 'cpm_task'
                GROUP BY taskt.ID
                ORDER BY projectt.ID ,  listt.ID
               ";

        $task_list = $wpdb->get_results( $sql );

        // include_once CPM_PRO_PATH . '/views/report/datatable.php';

        $response['output']      = $this->render_report_data( $task_list );
        $response['countresult'] = count( $task_list );

        $tran_id = 'rcsv_' . get_current_user_id();
        set_transient( $tran_id, $response, HOUR_IN_SECONDS );
        echo json_encode( $response );

        exit();
    }

    public function render_report_data( $task_list ) {
        $data        = array();
        $date_format = get_option( 'date_format' );

        foreach ( $task_list as $task ) {
            $pid        = $task->project_id;
            $task_array = array();
            $list_array = array();

            $users = explode( ',', $task->assign_user );

            if ( ! empty( $task->assign_user ) AND  $task->assign_user != -1) {
                $asigned_user = cpm_assigned_user( $users, FALSE, FALSE, ', ' );
            } else {
                $asigned_user = __( 'Not assigned', 'cpm' );
            }

            if ( $this->_timetracker ) {
                $cpm_time = new CPM_Time_Tracker();
                $ttime    = $cpm_time->get_time( $task->task_id );
            } else {
                $ttime = false;
            }

            $task_obj = array(
                'task_id'         => $task->task_id,
                'task_name'       => "<a href='" . cpm_url_single_task( $pid, $task->list_id, $task->task_id ) . "'>{$task->task_name}</a>",
                'assignto'        => $asigned_user,
                'task_time'       => $ttime,
                'start_date'      => ($task->start_date != '0000-00-00 00:00:00' ) ? mysql2date( $date_format, $task->start_date ) : __( 'Not set', 'cpm' ),
                'due_date'        => ($task->due_date != '0000-00-00 00:00:00' ) ? mysql2date( $date_format, $task->due_date ) : __( 'No due date', 'cpm' ),
                'complete_date'   => ($task->complete_date != '0000-00-00 00:00:00' ) ? mysql2date( $date_format, $task->complete_date ) : __( 'Incomplete', 'cpm' ),
                'complete_status' => $task->complete_status == '1' ? __( 'Complete', 'cpm' ) : __( 'Incomplete', 'cpm' ),
            );

            $task_array[$task->task_id] = $task_obj;
            $list_array[$task->list_id] = array(
                'list_id'   => $task->list_id,
                'list_name' => "<a href='" . cpm_url_single_tasklist( $pid, $task->list_id ) . "'>{$task->list_name}</a>",
                'task'      => $task_array,
            );


            if ( ! $pid ) {
                continue;
            }
            if ( isset( $data[$pid] ) ) {

                if ( isset( $data[$pid]['list'][$task->list_id] ) ) {
                    $data[$pid]['list'][$task->list_id]['task'][$task->task_id] = $task_obj;
                } else {
                    $data[$pid]['list'][$task->list_id] = array(
                        'list_id'   => $task->list_id,
                        'list_name' => "<a href='" . cpm_url_single_tasklist( $pid, $task->list_id ) . "'>{$task->list_name}</a>",
                        'task'      => $task_array,
                    );
                }
            } else {
                $data[$pid] = array(
                    'project_id'   => $pid,
                    'project_name' => "<a href='" . cpm_url_project_overview( $pid ) . "'>{$task->project}</a>",
                    'list'         => $list_array,
                );
            }
        }

        return $data;
    }

    public function report_csv() {

        $tran_id = 'rcsv_' . get_current_user_id();
        if ( $tran_id ) {
            $posted = $_POST;
            $data   = get_transient( $tran_id );
            $fp     = fopen( 'php://output', 'w' );

            header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
            header( "Content-Transfer-Encoding: UTF-8" );
            header( "Content-type: text/csv" );
            header( "Content-Disposition: attachment; filename=report.csv" );



            $str = array( __( 'Report Title : ', 'cpm' ), $data['reporttitle'] );
            fputcsv( $fp, $str );

            $str = array( __( 'Project for : ', 'cpm' ), $data['selectedproject'] );
            fputcsv( $fp, $str );

            $str = array( __( 'Co Worker : ', 'cpm' ), $data['selectedcoworder'] );
            fputcsv( $fp, $str );

            if ( isset( $data['extrahead'] ) ) {
                $str = array( $data['extrahead'] );
                fputcsv( $fp, $str );
            }

            foreach ( $data['output'] as $fields ) {
                $str = array( __( 'Project : ', 'cpm' ), $fields['project_name'] );
                fputcsv( $fp, $str );
                foreach ( $fields['list'] as $list ) {
                    $str = array( 'List : ', $list['list_name'] );
                    fputcsv( $fp, $str );
                    $str = array( __( 'Task Name', 'cpm' ), __( 'Assign to', 'cpm' ), __( 'Assign date', 'cpm' ), __( 'Due Date', 'cpm' ), __( 'Complete Date', 'cpm' ), __( 'Status', 'cpm' ) );
                    fputcsv( $fp, $str );
                    foreach ( $list['task'] as $task ) {
                        $str = array( strip_tags( $task['task_name'] ), strip_tags( $task['assignto'] ), $task['start_date'], $task['due_date'], $task['complete_date'], $task['complete_status'] );
                        fputcsv( $fp, $str );
                    }
                    fputcsv( $fp, array() );
                }
                fputcsv( $fp, array() );
            }
            fclose( $fp );
        }
        exit();
    }

    // End Nurul
    // Previoud functions
    public static function report_form_redirect() {

        if ( isset( $_POST['cpm-report-generat'] ) ) {
            $url = add_query_arg( $_POST, cpm_report_advancesearch_url() );
            wp_redirect( $url );
        }

        if ( isset( $_POST['cpm_report_csv_generat'] ) ) {
            self::download_send_headers( "Project-manager-report-" . date( "Y-m-d" ) . ".csv" );

            $data = $_POST;
            self::csv_generate( $data );
            exit();
        }
    }

    /**
     * Send header
     *
     * @param str $filename
     * @since 1.2
     * @return type
     */
    public static function download_send_headers( $filename ) {
        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        header( 'Pragma: no-cache' );
        header( "Expires: 0" );
    }

    /**
     * Report CSV file generate
     *
     * @param object $data
     * @since 1.2
     * @return type
     */
    public static function csv_generate( $data ) {

        $output  = fopen( "php://output", "w" );
        $reports = self::report_generate( $data );
        $posts   = $reports->posts;

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

        if ( in_array( 'time', $data['filter'] ) ) {
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
                    $items[$obj->interval][$obj->ID][$obj->list_id][$obj->task_id] = $key;
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

        if ( in_array( 'time', $data['filter'] ) ) {
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
            _e( 'No result found!', 'cpm' );
            return;
        }

        $i = 1;
        if ( in_array( 'time', $data['filter'] ) ) {
            foreach ( $items as $key => $item ) {

                if ( $data['interval'] != '-1' ) {
                    echo cpm_ordinal( $i ) . ' ' . $interval_view . '  ';
                } else {
                    echo $interval_view . '  ';
                }


                foreach ( $item as $project_id => $projects ) {
                    $project = get_post( $project_id );
                    _e( 'Project Title: ', 'cpm' ) . '  ';
                    echo $project->post_title . "\n";

                    foreach ( $projects as $list_id => $lists ) {
                        $list = get_post( $list_id );
                        _e( 'Task List Title: ', 'cpm' ) . '  ';
                        echo $list->post_title . "\n";

                        $task_cell   = __( 'Task', 'cpm' );
                        $assign_cell = __( 'Assign To', 'cpm' );
                        $sdate_cell  = __( 'Start Date', 'cpm' );
                        $edate_cell  = __( 'Due Date', 'cpm' );
                        $status_cell = __( 'Status', 'cpm' );

                        echo "$task_cell, $assign_cell, $sdate_cell, $edate_cell, $status_cell \n";

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
                            echo $task->post_title . ",";

                            foreach ( $task->assigned_to as $user_id ) {
                                $user = get_user_by( 'id', $user_id );
                                echo $user->display_name . '  ';
                            }
                            echo "," . cpm_get_date_without_html( $start_date ) . ",";
                            echo cpm_get_date_without_html( $task->due_date ) . ",";

                            $status = $task->completed ? __( 'Completed', 'cpm' ) : __( 'Incompleted', 'cpm' );
                            echo $status . "\n";
                        }

                        echo "\n";
                    }
                }

                $i ++;
            }
        } else {

            foreach ( $items as $project_id => $projects ) {
                $project = get_post( $project_id );

                _e( 'Project Title: ', 'cpm' ) . '  ';
                echo $project->post_title . "\n";

                foreach ( $projects as $list_id => $lists ) {
                    $list = get_post( $list_id );
                    _e( 'Task List Title: ', 'cpm' ) . '  ';
                    echo $list->post_title . "\n";

                    $task_cell   = __( 'Task', 'cpm' );
                    $assign_cell = __( 'Assign To', 'cpm' );
                    $sdate_cell  = __( 'Start Date', 'cpm' );
                    $edate_cell  = __( 'Due Date', 'cpm' );
                    $status_cell = __( 'Status', 'cpm' );

                    echo "$task_cell, $assign_cell, $sdate_cell, $edate_cell, $status_cell \n";

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
                        echo $task->post_title . ",";

                        foreach ( $task->assigned_to as $user_id ) {
                            $user = get_user_by( 'id', $user_id );
                            echo $user->display_name . '  ';
                        }

                        echo "," . cpm_get_date_without_html( $start_date ) . ",";
                        echo cpm_get_date_without_html( $task->due_date ) . ",";

                        $status = $task->completed ? __( 'Completed', 'cpm' ) : __( 'Incompleted', 'cpm' );
                        echo $status . "\n";
                    }

                    echo "\n";
                }
            }
        }
    }

    /**
     * Report header
     *
     * @version 1.2
     * @return type
     */
    function get_header() {
        cpmpro()->pro_router->get_report_header();
    }

    /**
     * Report generate
     *
     * @param object $data
     * @version 1.2
     * @return object
     */
    public static function report_generate( $data ) {
        self::$form_data = $data;

        $args = array(
            'post_type'      => 'cpm_project',
            'post_status'    => 'publish',
            'posts_per_page' => '-1',
        );

        self::project_select( $args, $data );
        self::project_date_query( $args, $data );
        self::project_status_query( $args, $data );

        add_filter( 'posts_join', array( cpm()->report, 'co_worker_table' ) );
        add_filter( 'posts_where', array( cpm()->report, 'co_worker_where' ) );
        add_filter( 'posts_fields', array( cpm()->report, 'select_field' ), 10, 2 );
        add_filter( 'posts_groupby', array( cpm()->report, 'posts_groupby' ) );

        $args    = apply_filters( 'cpm_report_args', $args );
        $results = new WP_Query( $args );

        remove_filter( 'posts_join', array( cpm()->report, 'co_worker_table' ) );
        remove_filter( 'posts_where', array( cpm()->report, 'co_worker_where' ) );
        remove_filter( 'posts_fields', array( cpm()->report, 'select_field' ), 10, 2 );
        remove_filter( 'posts_groupby', array( cpm()->report, 'posts_groupby' ) );

        return $results;
    }

    /**
     * Remove group by from wp_query
     *
     * @version 1.2
     * @return str
     */
    function posts_groupby( $groupby ) {
        return '';
    }

    /**
     * Render report table
     *
     * @param array $post
     * @param array $data
     *
     * @version 1.2
     * @return str
     */
    public static function render_table( $posts, $data ) {
        ob_start();
        cpmpro()->pro_router->generate_report_table( $posts, $data );

        return ob_get_clean();
    }

    /**
     * Render report table
     *
     * @param init $project_id
     *
     * @version 1.2
     * @return str
     */
    public static function get_tasklist_task( $project_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'cpm_project_items';

        $sql = "SELECT * FROM {$table} WHERE item_type IN( 'task_list', 'task' ) AND project_id = $project_id";
        return $wpdb->get_results( $sql );
    }

    /**
     * Select field
     *
     * @version 1.2
     * @return str
     */
    public static function select_field( $fields, $self ) {
        $data = self::$form_data;

        global $wpdb;
        $post              = $wpdb->prefix . 'posts';
        $start_date_enable = cpm_get_option( 'task_start_field', 'cpm_general' );

        if ( in_array( 'time', $data['filter'] ) && $data['timemode'] == 'task' ) {
            if ( $start_date_enable == 'on' ) {
                $interval = "YEAR(tskmeta.meta_value) AS post_year, MONTH(tskmeta.meta_value) AS post_month,
                WEEK(tskmeta.meta_value) as post_week, tsk.ID as task_id, tsk.post_title as task_title,
                tl.ID as list_id, tl.post_title as list_title ";
            } else {
                $interval = "YEAR($post.post_date) AS post_year, MONTH($post.post_date) AS post_month,
                WEEK($post.post_date) AS post_week,
                tsk.ID as task_id, tsk.post_title as task_title, tl.ID as list_id, tl.post_title as list_title";
            }
        } else {
            $interval = "YEAR($post.post_date) AS post_year, MONTH($post.post_date) AS post_month,
            WEEK($post.post_date) AS post_week, tl.ID as list_id,
            tl.post_title as list_title, tsk.ID as task_id, tsk.post_title as task_title";
        }

        $fields .= ", $interval ";
        return $fields;
    }

    /**
     * Co worker query where condition
     *
     * @param str $where
     *
     * @version 1.2
     * @return str
     */
    public static function co_worker_where( $where ) {
        $data = self::$form_data;

        global $wpdb;
        $post = $wpdb->prefix . 'posts';

        $start_date_enable = cpm_get_option( 'task_start_field', 'cpm_general' );

        if ( in_array( 'co-worker', $data['filter'] ) && $data['co_worker'] != '-1' ) {
            $user_id = $data['co_worker'];
            $table   = $wpdb->prefix . 'cpm_user_role';
            $where .= " AND ur.user_id = $user_id";
        }

        if ( in_array( 'time', $data['filter'] ) && $data['timemode'] == 'list' ) {

            $from = $data['from'];
            $to   = $data['to'];
            $from = $from ? date( 'Y-m-d H:i:m', strtotime( $from ) ) : current_time( 'mysql' );
            $to   = $to ? date( 'Y-m-d H:i:m', strtotime( $to ) ) : current_time( 'mysql' );
            $where .= " AND tl.post_type='cpm_task_list' AND tl.post_date >= '$from' AND tl.post_date <= '$to'";
        } else if ( in_array( 'time', $data['filter'] ) && $data['timemode'] == 'task' ) {

            $from = $data['from'];
            $to   = $data['to'];
            $from = $from ? date( 'Y-m-d H:i:m', strtotime( $from ) ) : current_time( 'mysql' );
            $to   = $to ? date( 'Y-m-d H:i:m', strtotime( $to ) ) : current_time( 'mysql' );

            if ( $start_date_enable == 'on' ) {
                $where .= " AND tsk.post_type='cpm_task' AND tskmeta.meta_key = '_start' AND tskmeta.meta_value >= '$from' AND tskmeta.meta_value <= '$to'";
            } else {
                $where .= " AND tsk.post_type='cpm_task' AND tsk.post_date >= '$from' AND tsk.post_date <= '$to'";
            }
        }

        return $where;
    }

    /**
     * Co worker tabel join
     *
     * @param str $join
     *
     * @version 1.2
     * @return str
     */
    public static function co_worker_table( $join ) {
        $data = self::$form_data;

        global $wpdb;
        $table             = $wpdb->prefix . 'posts';
        $table_post_meta   = $wpdb->prefix . 'postmeta';
        $start_date_enable = cpm_get_option( 'task_start_field', 'cpm_general' );

        if ( $start_date_enable == 'on' ) {
            $start_query = " LEFT JOIN {$table_post_meta} as tskmeta ON tskmeta.post_id = tsk.ID";
        } else {
            $start_query = '';
        }

        if ( in_array( 'co-worker', $data['filter'] ) ) {
            $user_table = $wpdb->prefix . 'cpm_user_role';
            $join .= " LEFT JOIN {$user_table} AS ur ON $table.ID = ur.project_id";
        }

        $join .= " LEFT JOIN {$table} AS tl ON $table.ID = tl.post_parent
        LEFT JOIN {$table} AS tsk ON tl.ID = tsk.post_parent $start_query";

        return $join;
    }

    /**
     * Select all project or specific project
     *
     * @param array $args
     * @param array $data
     *
     * @version 1.2
     * @return type
     */
    public static function project_select( &$args, $data ) {
        if ( ! in_array( 'project', $data['filter'] ) ) {
            return;
        }
        if ( $data['project'] == '-1' ) {
            return;
        }
        $args = array_merge( $args, array( 'p' => $data['project'] ) );
    }

    /**
     * Select project by date
     *
     * @param array $args
     * @param array $data
     *
     * @version 1.2
     * @return type
     */
    public static function project_date_query( &$args, $data ) {

        if ( ! in_array( 'time', $data['filter'] ) ) {
            return;
        }
        if ( ! isset( $data['from'] ) ) {
            return;
        }

        if ( $data['timemode'] != 'project' ) {
            return;
        }

        $date['date_query'] = array();

        $per_date = array(
            'after'     => isset( $data['from'] ) && $data['from'] ? $data['from'] : current_time( 'mysql' ),
            'before'    => isset( $data['to'] ) && $data['to'] ? $data['to'] : current_time( 'mysql' ),
            'inclusive' => true,
        );

        $date['date_query'] = array_merge( $date['date_query'], $per_date );
        $args               = array_merge( $args, $date );
    }

    /**
     * Select project by activity status
     *
     * @param array $args
     * @param array $data
     *
     * @version 1.2
     * @return type
     */
    public static function project_status_query( &$args, $data ) {
        if ( ! in_array( 'status', $data['filter'] ) ) {
            return;
        }

        if ( $data['status'] == '-1' ) {
            return;
        }

        $meta['meta_query'] = array();
        $status[]           = array(
            'key'     => '_project_active',
            'value'   => $data['status'] == 1 ? 'yes' : 'no',
            'compare' => '=',
        );

        $meta['meta_query'] = array_merge( $meta['meta_query'], $status );

        $args = array_merge( $args, $meta );
    }

}
