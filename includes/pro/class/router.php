<?php

/**
 * Project manager pro - route class
 * @since 1.1
 */
class CPM_Pro_router {

    /**
     * @var The single instance of the class
     * @since 1.1
     */
    protected static $_instance = null;

    /**
     * Main Cpmrp Instance
     *
     * @since 0.1
     * @return CPMRP_Admin_Pageload - Main instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Render my tasks page
     *
     * @since 1.1
     * @return void
     */
    function my_task() {
        require_once CPM_PRO_PATH . '/views/task/my-task.php';
    }

    /**
     * Shows the add-ons page on admin
     *
     * @since 1.1
     * @return void
     */
    function admin_page_addons() {
        include_once CPM_PATH . '/includes/add-ons.php';
    }

    /**
     * Report table html form
     *
     * @param obj $posts
     * @since 1.2
     * @return void
     */
    function generate_report_table( $posts, $data ) {
        include_once CPM_PRO_PATH . '/views/report/table.php';
    }

    /**
     * Report header
     *
     * @since 1.1
     * @return void
     */
    function get_report_header() {
        require_once CPM_PRO_PATH . '/views/report/header.php';
    }

    /**
     * Include the required files
     *
     * @since 1.1
     * @return void
     */
    function includes() {

        if ( cpm_is_pro() ) {
            include_once CPM_PATH . '/includes/pro/loader.php';
        } else {
            include_once CPM_PATH . '/includes/free/loader.php';
        }

        include_once CPM_PATH . '/includes/urls.php';
        include_once CPM_PATH . '/includes/html.php';
        include_once CPM_PATH . '/includes/shortcodes.php';
    }

    /**
     * CPM function
     *
     * @since 1.1
     * @return void
     */
    function cpm_function() {
        include_once CPM_PATH . '/includes/functions.php';
    }

    /**
     * CPM API
     *
     * @since 1.2
     * @return void
     */
    function api() {
        require_once CPM_PATH . '/includes/cpm-api/api.php';
    }

    /**
     * CPM API
     *
     * @since 1.2
     * @return void
     */
    public static function api_content() {
        include_once CPM_PATH . '/includes/cpm-api/class-cpm-json-projects.php';
        include_once CPM_PATH . '/includes/cpm-api/class-cpm-json-lists.php';
        include_once CPM_PATH . '/includes/cpm-api/class-cpm-json-tasks.php';
        include_once CPM_PATH . '/includes/cpm-api/class-cpm-json-messages.php';
        include_once CPM_PATH . '/includes/cpm-api/class-cpm-json-milestones.php';
        include_once CPM_PATH . '/includes/cpm-api/class-cpm-json-comments.php';
    }

    /**
     * Main output
     *
     * @since 1.1
     * @return type
     */
    static public function output() {
        echo '<div class="wrap cpm cpm-front-end">';

        $page   = (isset( $_GET['page'] )) ? $_GET['page'] : '';
        $tab    = (isset( $_GET['tab'] )) ? $_GET['tab'] : '';
        $action = (isset( $_GET['action'] )) ? $_GET['action'] : '';

        $project_id   = (isset( $_GET['pid'] )) ? ( int ) $_GET['pid'] : 0;
        $message_id   = (isset( $_GET['mid'] )) ? ( int ) $_GET['mid'] : 0;
        $tasklist_id  = (isset( $_GET['tl_id'] )) ? ( int ) $_GET['tl_id'] : 0;
        $task_id      = (isset( $_GET['task_id'] )) ? ( int ) $_GET['task_id'] : 0;
        $milestone_id = (isset( $_GET['ml_id'] )) ? ( int ) $_GET['ml_id'] : 0;

        $cpm_path = CPM_PRO_PATH;
        $cpm_path = apply_filters( 'cpm_tab_cpm_path', $cpm_path );

        $default_file = $cpm_path . '/views/project/index.php';

        switch ( $page ) {
            case 'cpm_projects':

                switch ( $tab ) {
                    case 'settings':
                        switch ( $action ) {
                            case 'index':
                                $file = $cpm_path . '/views/project/settings.php';
                                break;
                        }
                        break;
                    case 'project':

                        switch ( $action ) {
                            case 'index':
                                $file = $cpm_path . '/views/project/index.php';
                                break;

                            case 'single':
                                $file = $cpm_path . '/views/project/single.php';
                                break;

                            default:
                                $file = $cpm_path . '/views/project/index.php';
                                break;
                        }

                        break;

                    case 'message':
                        switch ( $action ) {
                            case 'index':
                                $file = $cpm_path . '/views/message/index.php';
                                break;

                            case 'single':
                                $file = $cpm_path . '/views/message/single.php';
                                break;

                            default:
                                $file = $cpm_path . '/views/message/index.php';
                                break;
                        }

                        break;

                    case 'task':
                        switch ( $action ) {
                            case 'index':
                                $file = $cpm_path . '/views/task/index.php';
                                break;

                            case 'single':
                                $file = $cpm_path . '/views/task/single.php';
                                break;

                            case 'task_single':
                                $file = $cpm_path . '/views/task/task-single.php';
                                break;

                            default:
                                $file = $cpm_path . '/views/task/index.php';
                                break;
                        }

                        break;

                    case 'milestone':
                        switch ( $action ) {
                            case 'index':
                                $file = $cpm_path . '/views/milestone/index.php';
                                break;

                            default:
                                $file = $cpm_path . '/views/milestone/index.php';
                                break;
                        }

                        break;

                    case 'files':
                        $file = $cpm_path . '/views/files/index.php';
                        break;
                    
                    case 'settings':
                        $file = $cpm_path . '/views/project/settings.php';
                        break;


                    default:
                        $file = $cpm_path . '/views/project/index.php';
                        break;
                }
                break;

            case 'cpm_calendar':
                $file = $cpm_path . '/views/calendar/index.php';
                break;
            case 'cpm_reports':
                switch ( $action ) {
                    default:
                        $file = $cpm_path . '/views/report/index.php';
                        break;
                }

                break;
            case 'cpm_progress':
                $file = $cpm_path . '/views/progress/progress.php';
                break;

            default:
                break;
        }

        $file = apply_filters( 'cpm_tab_file', $file, $project_id, $page, $tab, $action );

        if ( file_exists( $file ) ) {
            require_once $file;
        } else {
            require_once $default_file;
        }

        echo '</div>';
    }

}
