<?php

/**
 * Description of ajax
 *
 * @author tareq
 */
class CPM_Pro_Ajax extends CPM_Ajax {
	private static $_instance;

	public static function getInstance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
    	add_action( 'wp_ajax_cpm_get_events', array( $this, 'get_events' ) );
    	add_action( 'wp_ajax_cpm_get_user_events', array( $this, 'get_user_events' ) );
        add_action( 'wp_ajax_cpm_project_duplicate', array( $this, 'project_duplicate' ) );
        add_action( 'wp_ajax_get_mytask_content', array( $this, 'get_mytask_content' ) );
        add_action( 'wp_ajax_user_line_graph', array( $this, 'get_user_line_graph' ) );
        add_action( 'wp_ajax_get_user_activity', array( $this, 'get_user_activity' ) );
    }

    function get_events() {

        $events = CPM_Pro_Calendar::getInstance()->get_events();

        if ( $events ) {
            echo json_encode( $events );
        } else {
            echo json_encode( array(
                'success' => false
            ) );
        }
        exit;
    }

    function get_user_events() {
        $user_id = sanitize_text_field( $_POST['user_id'] ) ;
        $events = CPM_Pro_Calendar::getInstance()->get_user_events($user_id);


        if ( $events ) {
            echo json_encode( $events );
        } else {
            echo json_encode( array(
                'success' => false
            ) );
        }
        exit;
    }

    function project_duplicate() {

        if ( ! wp_verify_nonce( $_POST['_nonce'], 'cpm_nonce' ) ) {
            wp_send_json_error( __( 'Are you cheating?', 'cpm' ) );
        }

        if ( isset( $_POST['project_id'] ) ) {
            $project_id = $_POST['project_id'];
        } else {
            wp_send_json_error( __( 'Project ID required', 'cpm' ) );
        }

        CPM_Pro_Duplicate::getInstance()->create_duplicate( $project_id );

        wp_send_json_success( array(
            'url' => $_POST['url']
        ) );
    }

    function  my_task_acivity($uid){
         $task    = CPM_Pro_Task::getInstance();
         $content = $task->user_activity($uid);
         die();

    }


    function  get_mytask_content(){

        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'cpm_nonce' ) ) {
            wp_send_json_error( __( 'Are you cheating?', 'cpm' ) );
        }

        $tab = $_POST['tab_act'] ;
        $user = $_POST['user'] ;
        $task    = CPM_Pro_Task::getInstance();

         $content = '';
        if( $tab == 'overview' ) {
            $content =  $task->user_overview($user) ;
        } else if( $tab == 'activity' ) {
            $this->my_task_acivity($user) ;
        }else {
            $project = $task->my_task_current_tab( $tab );
            $task_list  =  $task->current_user_task( $user, $tab ) ;
            $content = $task->taskhtmloutput( $task_list , $user) ;
        }

        die();

    }

    function  get_user_line_graph(){
         if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'cpm_nonce' ) ) {
            wp_send_json_error( __( 'Are you cheating?', 'cpm' ) );
        }

        $range = $_POST['range'] ;
        $user = $_POST['user'] ;
        $task    = CPM_Pro_Task::getInstance();

        $content = $task->mytask_line_graph($user, $range) ;

        die();
    }


    function get_user_activity(){
        $user_id = isset( $_GET['user_id'] ) ? intval( $_GET['user_id'] ) : 0;
        $offset = isset( $_GET['offset'] ) ? intval( $_GET['offset'] ) : 0;
        $activities = CPM_Pro_Task::getInstance()->get_user_activity( $user_id, array('offset' => $offset) );

        if ( $activities ) {
            echo json_encode( array(
                'success' => true,
                'content' => cpm_user_activity_html( $activities, $user_id ),
                'count' => count( $activities )
            ) );
        } else {
            echo json_encode( array(
                'success' => false
            ) );
        }
        exit;


    }

}


