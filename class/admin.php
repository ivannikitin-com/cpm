<?php

require_once CPM_PATH . '/includes/lib/class.settings-api.php';

/**
 * Admin options handler class
 *
 * @since 0.4
 * @author Tareq Hasan <tareq@wedevs.com>
 */
class CPM_Admin {

    private $settings_api;

    function __construct() {
        $this->settings_api = new WeDevs_Settings_API();

        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ), 50 );
    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_menu() {
        add_submenu_page( 'cpm_projects', __( 'Settings', 'cpm' ), __( 'Settings', 'cpm' ), 'manage_options', 'cpm_settings', array( $this, 'settings_page' ) );
    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id'    => 'cpm_general',
                'title' => __( 'General', 'cpm' )
            ),
            array(
                'id'    => 'cpm_mails',
                'title' => __( 'E-Mail Settings', 'cpm' )
            ),
        );

        return apply_filters( 'cpm_settings_sections', $sections );
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    static function get_settings_fields() {
        global $wp_roles;

        $settings_fields = array();

        if ( ! $wp_roles ) {
            $wp_roles = new WP_Roles();
        }
        $role_names = $wp_roles->get_names();

        $url_links['backend'] = 'Link to Backend';
        if ( cpm_is_pro() ) {
            $url_links['frontend'] = 'Link to Front-end';
        };

        $settings_fields['cpm_general'] = apply_filters( 'cpm_settings_field_general', array(
            array(
                'name'    => 'upload_limit',
                'label'   => __( 'File Upload Limit', 'cpm' ),
                'default' => '2',
                'desc'    => __( 'File Size in Megabytes. e.g: 2' )
            ),
            array(
                'name'    => 'pagination',
                'label'   => __( 'Show Projects Per Page', 'cpm' ),
                'type'    => 'text',
                'default' => '10',
                'desc'    => __( '-1 for unlimited', 'cpm' )
            ),
            array(
                'name'    => 'todolist_show',
                'label'   => __( 'To-do List Style', 'cpm' ),
                'type'    => 'radio',
                'default' => 'pagination',
                'options' => array( 'pagination' => 'Pagination', 'load_more' => 'Load More', 'lazy_load' => 'Lazy Load' )
            ),
            array(
                'name'    => 'show_todo',
                'label'   => __( 'Show To-do Lists Per Page', 'cpm' ),
                'type'    => 'text',
                'default' => '5',
            ),
            array(
                'name'    => 'project_manage_role',
                'label'   => __( 'Project Managing Capability', 'cpm' ),
                'default' => array( 'editor' => 'editor', 'author' => 'author', 'administrator' => 'administrator' ),
                'desc'    => __( 'Select the user role who can see and manage all projects', 'cpm' ),
                'type'    => 'multicheck',
                'options' => $role_names,
            ),
            array(
                'name'    => 'project_create_role',
                'label'   => __( 'Project Creation Capability', 'cpm' ),
                'default' => array( 'editor' => 'editor', 'author' => 'author', 'administrator' => 'administrator' ),
                'desc'    => __( 'Select the user role who can create projects', 'cpm' ),
                'type'    => 'multicheck',
                'options' => $role_names,
            ),
                ) );

        $settings_fields['cpm_mails'] = apply_filters( 'cpm_settings_field_mail', array(
            array(
                'name'    => 'email_from',
                'label'   => __( 'From Email', 'cpm' ),
                'type'    => 'text',
                'desc'    => '',
                'default' => get_option( 'admin_email' )
            ),
            array(
                'name'    => 'email_url_link',
                'label'   => __( 'Links in the Email', 'cpm' ),
                'type'    => 'radio',
                'desc'    => __( 'Select where do you want to take the user. Notification emails contain links.', 'cpm' ),
                'default' => 'backend',
                'options' => $url_links
            ),
            array(
                'name'    => 'email_type',
                'label'   => __( 'E-Mail Type', 'cpm' ),
                'type'    => 'select',
                'default' => 'text/plain',
                'options' => array(
                    'text/html'  => __( 'HTML Mail', 'cpm' ),
                    'text/plain' => __( 'Plain Text', 'cpm' )
                )
            ),
            array(
                'name'    => 'email_bcc_enable',
                'label'   => __( 'Send email via Bcc', 'cpm' ),
                'type'    => 'checkbox',
                'default' => 'off',
                'desc'    => __( 'Enable Bcc' )
            ),
        ) );

        return apply_filters( 'cpm_settings_fields', $settings_fields );
    }

    public static function get_post_type( $post_type ) {
        $pages_array = array( '-1' => __( '- select -', 'cpm' ) );
        $pages       = get_posts( array( 'post_type' => $post_type, 'numberposts' => -1 ) );

        if ( $pages ) {
            foreach ( $pages as $page ) {
                $pages_array[$page->ID] = $page->post_title;
            }
        }

        return $pages_array;
    }

    function settings_page() {
        echo '<div class="wrap">';
        settings_errors();

        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';
    }

}
