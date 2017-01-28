<?php
class CPM_Frontend_URLs {

    function __construct() {

        add_filter( 'cpm_url_project_details', array($this, 'project_details'), 10, 2 );
        add_filter( 'cpm_url_tasklist_index', array($this, 'tasklist_index'), 10, 2 );
        add_filter( 'cpm_url_single_tasklislt', array($this, 'tasklist_single'), 10, 3 );
        add_filter( 'cpm_url_single_task', array($this, 'task_single'), 10, 4 );

        add_filter( 'cpm_url_message_index', array($this, 'message_index'), 10, 2 );
        add_filter( 'cpm_url_single_message', array($this, 'single_message'), 10, 3 );

        add_filter( 'cpm_url_project_overview', array($this, 'project_overview'), 10, 2 );

        add_filter( 'cpm_url_milestone_index', array($this, 'milestone_index'), 10, 2 );
        add_filter( 'cpm_url_file_index', array($this, 'file_index'), 10, 2 );
        add_filter( 'cpm_url_all', array($this, 'show_all_project') );

        add_filter( 'cpm_url_project_page', array($this, 'show_all_project') );
        add_filter( 'cpm_url_active', array($this, 'show_all_active') );
        add_filter( 'cpm_url_archive', array($this, 'show_all_archive') );
        add_filter( 'cpm_project_duplicate', array($this, 'show_all_active' ) );
        add_filter( 'cpm_url_settings_index', array($this, 'show_all_settings' ), 10, 2 );

        add_filter( 'cpm_url_my_task', array( $this, 'show_url_my_task' ) );
        add_filter( 'cpm_url_outstanding_task', array( $this, 'show_url_outstanding_task' ) );
        add_filter( 'cpm_url_complete_task', array( $this, 'show_url_complete_task' ) );
        add_filter( 'cpm_url_user', array( $this, 'cpm_front_end_url_user' ), 10, 5 );
        add_filter( 'cpmtt_log_redirect', array( $this, 'redirect_log' ), 10, 5 );
        add_filter( 'cpm_project_list_url', array( $this, 'cpm_project_list_url' ) );

    }

    function cpm_project_list_url( $projects_url ) {
        if ( is_admin() ) {
            return $projects_url;
        }
        $page_id = cpm_get_option('project', 'cpm_page');
        return $this->get_permalink( $page_id );
    }

    function redirect_log( $redirect, $project_id, $list_id, $task_id, $del_status ) {
        $url = $this->task_single( $redirect, $project_id, $list_id, $task_id );
        $url = add_query_arg( array( 'delete' => $del_status), $url );

        return $url; 
    }

    function cpm_front_end_url_user( $url, $user, $link, $avatar, $size ) {
        $page_id = cpm_get_option('my_task', 'cpm_page');
        $name = $user->display_name;
        if ( $avatar ) {
            // PATCHED! 3 parameter breaks avatars at Russian Names
			//$name = get_avatar( $user->ID, $size, $user->display_name );
            $name = get_avatar( $user->user_email, $size );
        }
        $link = add_query_arg( array( 'user_id' => $user->ID ), get_permalink( $page_id ) );
        // PATCHED!
		//$url = sprintf( '<a href="%s">%s</a>', $link, $name );
        $url = sprintf( '<a href="%s" title="%s">%s</a>', $link, $user->display_name, $name );
        return $url;
    }

    function show_url_my_task( $url ) {
        $page_id = cpm_get_option('my_task', 'cpm_page');
        $url = add_query_arg( array(
            'page' => 'cpm_task',
        ), $this->get_permalink($page_id));
        return $url;
    }

    function show_url_outstanding_task( $url ) {
        $page_id = cpm_get_option('my_task', 'cpm_page');
        $url = add_query_arg( array(
            'page' => 'cpm_task',
            'tab' => 'outstanding'
        ), $this->get_permalink($page_id));
        return $url;
    }

    function show_url_complete_task( $url ) {
        $page_id = cpm_get_option('my_task', 'cpm_page');
        $url = add_query_arg( array(
            'page' => 'cpm_task',
            'tab' => 'complete'
        ), $this->get_permalink($page_id));
        return $url;
    }

    function show_all_settings( $url, $project_id ) {
        $page_id = cpm_get_option('project', 'cpm_page');
        $url = add_query_arg( array(
            'page' => 'cpm_projects',
            'tab' => 'settings',
            'action' => 'index',
            'project_id'   => $project_id
        ), $this->get_permalink($page_id));
        return $url;
    }

    function show_all_active($url) {
        $page_id = cpm_get_option('project', 'cpm_page');
        $url = add_query_arg( array(
            'status' => 'active',
            'page' => 'cpm_projects'
        ), $this->get_permalink($page_id) );
        return $url;
    }
    function show_all_archive($url) {
        $page_id = cpm_get_option('project', 'cpm_page');
        $url = add_query_arg( array(
            'status' => 'archive',
            'page' => 'cpm_projects'
        ), $this->get_permalink($page_id) );
        return $url;
    }
    function show_all_project($url) {
        $page_id = cpm_get_option('project', 'cpm_page');
        $url = add_query_arg( array(
            'status' => 'all',
            'page' => 'cpm_projects'
        ), $this->get_permalink($page_id) );
        return $url;
    }

    function get_permalink( $page_id = null ) {

        if ( isset( $_REQUEST['cpmf_url'] ) ) {
            $url = $_REQUEST['cpmf_url'];
        } else {
            $url = get_permalink( $page_id );
        }

        if( $page_id != null ) {
            $url = get_permalink( $page_id );
        }

        return $url;
    }

    function tasklist_index( $url, $project_id ) {
        $page_id = cpm_get_option('project', 'cpm_page');
        $url = add_query_arg( array(
            'project_id' => $project_id,
            'tab' => 'task',
            'action' => 'index'
        ), $this->get_permalink($page_id) );

        return $url;
    }

    function tasklist_single( $url, $project_id, $list_id ) {

        $page_id = cpm_get_option('project', 'cpm_page');
        $url = add_query_arg( array(
            'project_id' => $project_id,
            'tab' => 'task',
            'action' => 'single',
            'list_id' => $list_id
        ), $this->get_permalink( $page_id ) );

        return $url;
    }

    function task_single( $url, $project_id, $list_id, $task_id ) {
        $page_id = cpm_get_option('project', 'cpm_page');

        $url = add_query_arg( array(
            'project_id' => $project_id,
            'tab' => 'task',
            'action' => 'todo',
            'list_id' => $list_id,
            'task_id' => $task_id
        ), $this->get_permalink($page_id) );
        return $url;
    }

    function project_overview( $url, $project_id ) {
        $page_id = cpm_get_option('project', 'cpm_page');
        $url = add_query_arg( array(
            'project_id' => $project_id,
            'tab' => 'project',
            'action' => 'index'
        ), $this->get_permalink($page_id) );
        return $url;
    }

      function project_details( $url, $project_id ) {
         $page_id = cpm_get_option('project', 'cpm_page');
        $url = add_query_arg( array(
            'project_id' => $project_id,
            'tab' => 'project',
            'action' => 'activity'
        ), $this->get_permalink( $page_id ) );
        return $url;
    }


    function message_index( $url, $project_id ) {
        $page_id = cpm_get_option('project', 'cpm_page');
        $url = add_query_arg( array(
            'project_id' => $project_id,
            'tab' => 'message',
            'action' => 'index'
        ), $this->get_permalink($page_id) );
        return $url;
    }

    function single_message( $url, $project_id, $message_id ) {
        $page_id = cpm_get_option('project', 'cpm_page');
        $url = add_query_arg( array(
            'project_id' => $project_id,
            'tab' => 'message',
            'action' => 'single',
            'message_id' => $message_id
        ), $this->get_permalink($page_id) );
        return $url;
    }

    function milestone_index( $url, $project_id ) {
        $page_id = cpm_get_option('project', 'cpm_page');
        $url = add_query_arg( array(
            'project_id' => $project_id,
            'tab' => 'milestone',
            'action' => 'index'
        ), $this->get_permalink( $page_id ) );

        return $url;
    }

    function file_index( $url, $project_id ) {
        $page_id = cpm_get_option('project', 'cpm_page');
        $url = add_query_arg( array(
            'project_id' => $project_id,
            'tab' => 'files',
            'action' => 'index'
        ), $this->get_permalink($page_id) );

        return $url;
    }

}