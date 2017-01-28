<?php

/**
 * The Pro Class
 */
class CPM_Pro_Loader {

    /**
     * @var The single instance of the class
     * @since 0.1
     */
    protected static $_instance = null;

    /**
     * Main CPM Instance
     *
     * @since 1.1
     * @static
     * @see cpm()
     * @return CPMRP - Main instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @var CPM_Router $router
     */
    public $pro_router;

    function __construct() {
        $this->define_constants();
        spl_autoload_register( array( $this, 'autoload' ) );

        add_action( 'cpm_admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'cpm_admin_scripts', array( $this, 'pro_admin_scripts' ) );

        add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
        add_action( 'cpm_new_project_client_field', array( $this, 'new_project_client_field' ), 10, 2 );
        add_action( 'cpm_update_project_client_field', array( $this, 'update_project_client_field' ), 10, 2 );
        add_action( 'cpm_milestone_form', array( $this, 'milestone_form' ), 10, 2 );
        add_action( 'cpm_message_privicy_field', array( $this, 'message_privicy_field' ), 10, 2 );

        add_action( 'cpm_tasklist_form', array( $this, 'tasklist_form' ), 10, 2 );
        add_action( 'cpm_task_new_form', array( $this, 'task_new_form' ), 10, 3 );
        add_action( 'cpm_instantiate', array( $this, 'instantiate' ) );

        add_action( 'cpm_tab_file', array( $this, 'include_file' ), 10, 5 );

        add_action( 'cpm_install', array( $this, 'install' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        add_filter( 'cpm_settings_field_general', array( $this, 'settings' ) );

        add_filter( 'cpm_project_total_files', array( $this, 'count_project_file' ), 10, 2 );

        add_action( 'cpm_filter_project', array( $this, 'filter_project' ) );
        add_action( 'cpm_inside_project_filter', array( $this, 'inside_project_filter' ) );

        register_activation_hook( CPM_PATH . '/cpm.php', array( $this, 'createpages' ) );
    }

    function inside_project_filter( $project ) {
        ?>
        <div class="cpm-single-project-search-wrap">
            <input type="text" data-project_id="<?php echo $project->ID; ?>" placeholder="<?php _e( 'Search...', 'cpm' ); ?>" id="cpm-single-project-search">
        </div>
        <?php
    }

    function filter_project() {
        cpm_project_filters();
    }

    function count_project_file( $total_file, $project_id ) {

        global $wpdb;
        $table        = $wpdb->prefix . 'cpm_file_relationship';
        $sql          = "SELECT  count(id) as total_file FROM $table WHERE project_id = $project_id AND is_dir != 1  ";
        $total = $wpdb->get_row( $sql )->total_file;

        $total_file = $total_file + $total ;

        return $total_file;
    }

    function settings( $settings ) {
        $settings[] = array(
            'name'    => 'task_start_field',
            'label'   => __( 'Task start date', 'cpm' ),
            'type'    => 'checkbox',
            'default' => 'off',
            'desc'    => __( 'Enable task start date field' )
        );

        $settings[] = array(
            'name'  => 'logo',
            'label' => __( 'Logo', 'cpm' ),
            'type'  => 'file'
        );
        $settings[] = array(
            'name'    => 'daily_digest',
            'label'   => __( 'Daily Digest', 'cpm' ),
            'type'    => 'checkbox',
            'default' => 'on',
            'desc'    => __( 'Enable Daily Digest', 'cpm' )
        );

        return $settings;
    }

    function include_file( $file, $project_id, $page, $tab, $action ) {
        switch ( $page ) {
            case 'cpm_projects':

                switch ( $tab ) {
                    case 'settings':

                        $file = CPM_PRO_PATH . '/views/project/settings.php';
                        break;

                    case 'files':
                        $file = CPM_PRO_PATH . '/views/files/index.php';
                        break;
                }
                break;

            case 'cpm_calendar':
                $file = CPM_PRO_PATH . '/views/calendar/index.php';
                break;
            case 'cpm_reports':
                switch ( $action ) {
                    case 'download_csv':
                        $file = CPM_PRO_PATH . '/views/report/export_csv.php';
                        break;
                    case 'advancereport':
                        $file = CPM_PRO_PATH . '/views/report/advance_report.php';
                        break;

                    default:
                        $file = CPM_PRO_PATH . '/views/report/index.php';
                        break;
                }



                break;
            case 'cpm_progress':
                $file = CPM_PRO_PATH . '/views/progress/progress.php';
                break;
        }

        return $file;
    }

    /**
     * Load pro css style
     *
     * @return void
     */
    public function pro_admin_scripts() {
        wp_enqueue_style( 'cpm-pro-style', plugins_url( 'assets/css/pro-style.css', __FILE__ ) );
    }

    /**
     * Load my task scripts
     *
     * @return void
     */
    static function my_task_scripts() {
        cpm()->admin_scripts();

        wp_enqueue_script( 'cpm_mytask', plugins_url( 'assets/js/mytask.js', __FILE__ ), array( 'jquery', 'cpm_task' ), false, true );
        wp_enqueue_style( 'cpm-pro-style', plugins_url( 'assets/css/pro-style.css', __FILE__ ) );
        // For calender
        wp_enqueue_script( 'fullcalendar', plugins_url( 'assets/js/fullcalendar.min.js', __FILE__ ), array( 'jquery' ), false, true );
        wp_enqueue_style( 'fullcalendar', plugins_url( 'assets/css/fullcalendar.css', __FILE__ ) );
        wp_localize_script( 'cpm_admin', 'CPM_Front_Vars', array( 'is_admin' => is_admin() ) );
    }

    /**
     * Load calendar scripts
     *
     * @return void
     */
    public static function calender_scripts() {

        cpm()->admin_scripts();

        wp_enqueue_script( 'fullcalendar', plugins_url( 'assets/js/fullcalendar.min.js', __FILE__ ), array( 'jquery' ), false, true );
        wp_enqueue_style( 'fullcalendar', plugins_url( 'assets/css/fullcalendar.css', __FILE__ ) );
    }

    /**
     * Load calendar scripts
     *
     * @return void
     */
    static function report_scripts() {
        cpm()->admin_scripts();
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'cpm_pro_report', plugins_url( 'assets/js/report-vue.js', __FILE__ ), 'cpm-vuejs' );
        wp_enqueue_script( 'report', plugins_url( 'assets/js/report.js', __FILE__ ), array( 'jquery' ), false, true );
        wp_localize_script( 'report', 'CPM_Vars', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'cpm_nonce' ),
            'message' => cpm_message(),
        ) );
        wp_enqueue_style( 'jquery-ui', CPM_URL . '/assets/css/jquery-ui-1.9.1.custom.css' );
        wp_enqueue_style( 'cpm_admin', CPM_URL . '/assets/css/admin.css' );
    }

    /**
     * Load progress scripts
     *
     * @return void
     */
    static function progress_scripts() {
        cpm()->admin_scripts();
    }

    /**
     * Define cpmrp Constants
     *
     * @since 1.1
     * @return type
     */
    public function define_constants() {

        $this->define( 'CPM_PRO', true );
        $this->define( 'CPM_PRO_PATH', dirname( __FILE__ ) );
    }

    /**
     * Define constant if not already set
     *
     * @since 1.1
     *
     * @param  string $name
     * @param  string|bool $value
     * @return type
     */
    public function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    /**
     * Deactivation actions
     *
     * @since 1.1
     *
     * @return void
     */
    public function deactivate() {
        wp_clear_scheduled_hook( 'cpm_daily_digest' );
    }

    /**
     * Run actions on `plugins_loaded` hook
     *
     * @since 1.1
     *
     * @return void
     */
    public function plugins_loaded() {
        if ( cpm_get_option( 'daily_digest', 'cpm_general' ) == 'off' ) {
            return;
        }
        CPM_Pro_Digest::getInstance();
    }

    public function install() {
        //pro
        //CPM_Pro_Upgrade::getInstance()->plugin_upgrades();
        CPM_Upgrade::getInstance()->plugin_upgrades();
        wp_schedule_event( time(), 'daily', 'cpm_daily_digest' );
    }

    public function instantiate( $cpm ) {

        $cpm->report = CPM_Pro_Report::getInstance();
        $cpm->ajax   = CPM_Pro_Ajax::getInstance();
        CPM_Pro_Task::getInstance();

        if ( is_admin() ) {
            $this->admin  = new CPM_Pro_Admin();
            $cpm->updates = new CPM_Pro_Updates();
        }
        $this->pro_router = CPM_Pro_Router::instance();
    }

    /**
     * Autoload class files on demand
     *
     * @param string $class requested class name
     */
    public function autoload( $class ) {

        $name = explode( '_', $class );

        if ( isset( $name[2] ) ) {
            $class_name = strtolower( $name[2] );
            $filename   = dirname( __FILE__ ) . '/class/' . $class_name . '.php';
            if ( file_exists( $filename ) ) {
                require_once $filename;
            }
        }
    }

    public function task_new_form( $list_id, $project_id, $task ) {
        if ( cpm_user_can_access( $project_id, 'todo_view_private' ) ) {
            ?>
            <div class="cpm-make-privacy">
                <label>
                    <?php
                    $task_ID   = isset( $task->ID ) ? $task->ID : '';
                    $check_val = get_post_meta( $task_ID, '_task_privacy', true );
                    $check_val = empty( $check_val ) ? '' : $check_val;
                    ?>
                    <input type="checkbox" <?php checked( 'yes', $check_val ); ?> value="yes" name="task_privacy">
                    <?php _e( 'Private', 'cpm' ); ?>
                </label>
            </div>
            <?php
        }
    }

    public function tasklist_form( $project_id, $list ) {

        if ( cpm_user_can_access( $project_id, 'tdolist_view_private' ) ) {
            ?>
            <div class="cpm-make-privacy">
                <label>
                    <?php
                    $list_ID   = isset( $list->ID ) ? $list->ID : '';
                    $check_val = get_post_meta( $list_ID, '_tasklist_privacy', true );
                    $check_val = empty( $check_val ) ? '' : $check_val;
                    ?>
                    <input type="checkbox" <?php checked( 'yes', $check_val ); ?> value="yes" name="tasklist_privacy">
                    <?php _e( 'Private', 'cpm' ); ?>
                </label>
            </div>
            <?php
        }
    }

    public function message_privicy_field( $project_id, $message ) {
        if ( cpm_user_can_access( $project_id, 'msg_view_private' ) ) {
            ?>
            <div class="cpm-make-privacy">
                <label>
                    <?php
                    $message_id = isset( $message->ID ) ? $message->ID : '';
                    $check_val  = get_post_meta( $message_id, '_message_privacy', true );
                    $check_val  = empty( $check_val ) ? '' : $check_val;
                    ?>
                    <input type="checkbox" <?php checked( 'yes', $check_val ); ?> value="yes" name="message_privacy">
                    <?php _e( 'Private', 'cpm' ); ?>
                </label>
            </div>
            <?php
        }
    }

    public function milestone_form( $project_id, $milestone ) {

        if ( cpm_user_can_access( $project_id, 'milestone_view_private' ) ) {
            ?>
            <div class="cpm-make-privacy">
                <label>
                    <?php
                    $milestone_ID = isset( $milestone->ID ) ? $milestone->ID : '';
                    $check_val    = get_post_meta( $milestone_ID, '_milestone_privacy', true );
                    $check_val    = empty( $check_val ) ? '' : $check_val;
                    ?>
                    <input type="checkbox" <?php checked( 'yes', $check_val ); ?> value="yes" name="milestone_privacy">
                    <?php _e( 'Private', 'cpm' ); ?>
                </label>
            </div>
            <?php
        }
    }

    public function update_project_client_field( $array, $name ) {
        ?>
        <td>
            <input type="radio" <?php checked( 'client', $array['role'] ); ?> id="cpm-client-<?php echo $name; ?>" name="role[<?php echo $array['id']; ?>]" value="client">
            <label for="cpm-client-<?php echo $name; ?>"><?php _e( 'Client', 'cpm' ); ?></label>
        </td>
        <?php
    }

    public function new_project_client_field( $user_id, $name ) {
        ?>
        <td>

            <input type="radio" id="cpm-client-<?php echo $name; ?>" name="role[<?php echo $user_id; ?>]" value="client">
            <label for="cpm-client-<?php echo $name; ?>"><?php _e( 'Client', 'cpm' ); ?></label>
        </td>

        <?php
    }

    public function admin_menu( $capability ) {

        $capability   = 'read'; //minimum level: subscriber
        $cpm          = cpm();
        $uid          = wp_get_current_user()->ID;
        $count_task   = CPM_Pro_Task::getInstance()->mytask_count( $uid );
        $current_task = isset( $count_task['Current'] ) ? $count_task['Current'] : 0;
        $outstanding  = isset( $count_task['Outstanding'] ) ? $count_task['Outstanding'] : 0;
        $active_task  = $current_task + $outstanding;

        $mytask_text = __( 'My Tasks', 'cpm' );

        if ( $active_task ) {
            $mytask_text = sprintf( __( 'My Tasks %s', 'cpm' ), '<span class="awaiting-mod count-1"><span class="pending-count">' . $active_task . '</span></span>' );
        }

        //$hook = add_menu_page( __( 'Project Manager', 'cpm' ), __( 'Project Manager', 'cpm' ), $capability, 'cpm_projects', array($cpm, 'admin_page_handler'), 'dashicons-networking', 3 );
        //add_submenu_page( 'cpm_projects', __( 'Projects', 'cpm' ), __( 'Projects', 'cpm' ), $capability, 'cpm_projects', array($cpm, 'admin_page_handler') );
        $hook_my_task  = add_submenu_page( 'cpm_projects', __( 'My Tasks', 'cpm' ), $mytask_text, $capability, 'cpm_task', array( $this, 'my_task' ) );
        $hook_calender = add_submenu_page( 'cpm_projects', __( 'Calendar', 'cpm' ), __( 'Calendar', 'cpm' ), $capability, 'cpm_calendar', array( $cpm, 'admin_page_handler' ) );


        if ( cpm_can_manage_projects() ) {
            $hook_reports = add_submenu_page( 'cpm_projects', __( 'Reports', 'cpm' ), __( 'Reports', 'cpm' ), $capability, 'cpm_reports', array( $cpm, 'admin_page_handler' ) );
            add_action( 'admin_print_styles-' . $hook_reports, array( $this, 'report_scripts' ) );

            $hook_progress = add_submenu_page( 'cpm_projects', __( 'Progress', 'cpm' ), __( 'Progress', 'cpm' ), $capability, 'cpm_progress', array( $cpm, 'admin_page_handler' ) );
            add_action( 'admin_print_styles-' . $hook_progress, array( $this, 'progress_scripts' ) );
        }

        //add_submenu_page( 'cpm_projects', __( 'Add-ons', 'cpm' ), __( 'Add-ons', 'cpm' ), 'manage_options', 'cpm_addons', array($cpm, 'admin_page_addons') );
        add_action( 'admin_print_styles-' . $hook_my_task, array( $this, 'my_task_scripts' ) );
        add_action( 'admin_print_styles-' . $hook_calender, array( $this, 'calender_scripts' ) );
    }

    /**
     * Render my tasks page
     *
     * @since 0.5
     * @return void
     */
    public function my_task() {
        $this->pro_router->my_task();
    }

    /**
     * Create Frontend Page if they not exist
     *
     * @since  1.4.3
     *
     */
    public function createpages() {

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
            return;

        $page_data = array(
            'post_status'    => 'publish',
            'post_author'    => 1,
            'comment_status' => 'close',
            'ping_status'    => 'close',
            'post_type'      => 'page',
            'post_parent'    => 0,
        );

        // Create Project Paeg
        $project_page = cpm_get_option( 'project', 'cpm_page' );
        if ( ! $project_page ) {

            $page_title = __( 'Projects', 'cpmf' );

            $page_data['post_title']   = $page_title;
            $page_data['post_content'] = "[cpm]";

            $e = wp_insert_post( $page_data, true );
            if ( ! is_wp_error( $e ) ) {
                $cpm_pages['project'] = $e;
            }
        } else {
            $cpm_pages['project'] = $project_page;
        }

        // Create My Task page
        $mytask_page = cpm_get_option( 'my_task', 'cpm_page' );
        if ( ! $mytask_page ) {
            $page_title                = __( 'My Tasks', 'cpmf' );
            $page_data['post_title']   = $page_title;
            $page_data['post_content'] = "[cpm_my_task]";

            $e = wp_insert_post( $page_data, true );
            if ( ! is_wp_error( $e ) ) {
                $cpm_pages['my_task'] = $e;
            }
        } else {
            $cpm_pages['my_task'] = $mytask_page;
        }

        // Create My Calender page
        $calender_page = cpm_get_option( 'calendar', 'cpm_page' );
        if ( ! $calender_page ) {

            $page_title                = __( 'My Calender', 'cpmf' );
            $page_data['post_title']   = $page_title;
            $page_data['post_content'] = "[cpm_calendar]";

            $e = wp_insert_post( $page_data, true );
            if ( ! is_wp_error( $e ) ) {
                $cpm_pages['calendar'] = $e;
            }
        } else {
            $cpm_pages['calendar'] = $calender_page;
        }

        update_option( 'cpm_page', $cpm_pages );
    }

}

/**
 * Returns the main instance.
 *
 * @since  1.1
 * @return WeDevs_CPM
 */
function cpmpro() {
    return CPM_Pro_Loader::instance();
}

//cpm instance.
cpmpro();

if ( ! class_exists( 'CPM_Frontend' ) ) {
    require_once dirname( __FILE__ ) . '/frontend/frontend.php';
}

if ( ! class_exists( 'CPM_Pro_Files' ) ) {
    require_once dirname( __FILE__ ) . '/class/files.php';
}