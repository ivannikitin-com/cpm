<?php

/**
 * Daily digest Email class
 *
 * @since 1.1
 *
 * @author Wedevs
 */
class CPM_Pro_Digest {

    private $check_time;
    protected static $_instance = null;
    private $_link_option;
    private $_page_id;

    /**
     * Main Cpmrp Instance
     *
     * @since 0.1
     * @return CPMRP_Admin_Pageload - Main instance
     */
    public static function getInstance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new CPM_Pro_Digest();
        }
        return self::$_instance;
    }

    function __construct() {
        add_action( 'cpm_daily_digest', array( $this, 'active_event' ) );
        $this->_link_option = cpm_get_option( 'email_url_link', 'cpm_mails' );
        $this->_page_id     = cpm_get_option( 'project', 'cpm_general' );
    }

    /**
     * Schedule event action
     *
     * @since 1.1
     */
    function active_event() {
        $this->check_time = date( 'Y-m-d H:i:s', strtotime( current_time( 'mysql' ) . '-24 hour' ) );
        $this->get_all_users();
    }

    /**
     * Get all users from all project
     *
     * @since 1.1
     */
    function get_all_users() {

        global $wpdb;

        $table   = $wpdb->prefix . 'cpm_user_role';
        $dbusers = $wpdb->get_results( "SELECT * FROM $table" );
        $users   = array();

        foreach ( $dbusers as $key => $dbuser ) {
            $users[$dbuser->user_id][$dbuser->project_id] = $dbuser->role;
        }

        $co_worker_items                  = $this->tasks_query( 0 );
        $co_worker_complete_items         = $this->tasks_query();
        $co_worker_due_items              = $this->due_tasks_query();
        $manager_milestone_items          = $this->milestone_query( 0 );
        $manager_complete_milestone_items = $this->milestone_query();

        $co_worker_tasks          = array();
        $co_worker_complete_tasks = array();
        $co_worker_due_tasks      = array();

        $manager_task               = array();
        $manager_complete_task      = array();
        $manager_due_task           = array();
        $manager_milestone          = array();
        $manager_complete_milestone = array();

        foreach ( $co_worker_items as $key => $co_worker_item ) {
            $project_id = $co_worker_item->project_id;
            $list_id    = $co_worker_item->list_id;
            $task_id    = $co_worker_item->task_id;
            $user_id    = $co_worker_item->user_id;
            $author     = $co_worker_item->author;

            $manager_task[$project_id][$list_id][$task_id]              = $co_worker_items[$key];
            $co_worker_tasks[$project_id][$user_id][$list_id][$task_id] = $co_worker_items[$key];
        }

        foreach ( $co_worker_complete_items as $key => $co_worker_complete_item ) {
            $project_id = $co_worker_complete_item->project_id;
            $list_id    = $co_worker_complete_item->list_id;
            $task_id    = $co_worker_complete_item->task_id;
            $user_id    = $co_worker_complete_item->user_id;

            $manager_complete_task[$project_id][$list_id][$task_id]              = $co_worker_complete_items[$key];
            $co_worker_complete_tasks[$project_id][$user_id][$list_id][$task_id] = $co_worker_complete_items[$key];
        }

        /**
         * Manager and Co-worker Due item
         * @var [type]
         */
        foreach ( $co_worker_due_items as $key => $co_worker_due_item ) {
            $project_id = $co_worker_due_item->project_id;
            $list_id    = $co_worker_due_item->list_id;
            $task_id    = $co_worker_due_item->task_id;
            $user_id    = $co_worker_due_item->user_id;

            $manager_due_task[$project_id][$list_id][$task_id]              = $co_worker_due_items[$key];
            $co_worker_due_tasks[$project_id][$user_id][$list_id][$task_id] = $co_worker_due_items[$key];
        }

        /**
         * Manager Milestone
         * @var [type]
         */
        foreach ( $manager_milestone_items as $key => $manager_milestone_item ) {
            $project_id    = $manager_milestone_item->project_id;
            $milsestone_id = $manager_milestone_item->milestone_id;

            $manager_milestone[$project_id][$milsestone_id] = $manager_milestone_items[$key];
        }

        foreach ( $manager_complete_milestone_items as $key => $manager_complete_milestones ) {
            $project_id    = $manager_complete_milestones->project_id;
            $milsestone_id = $manager_complete_milestones->milestone_id;

            $manager_complete_milestone[$project_id][$milsestone_id] = $manager_complete_milestone_items[$key];
        }

        foreach ( $users as $user_id => $user ) {
            $co_worker_message = '';
            $manager_message   = '';

            foreach ( $user as $project_id => $role ) {

                switch ( $role ) {
                    case 'manager':

                        ob_start();
                        $this->manager_per_day_activity( $user_id, $project_id, $manager_task, $manager_complete_task, $manager_due_task, $manager_milestone, $manager_complete_milestone );
                        $content = ob_get_clean();

                        if ( $content ) {
                            ob_start();
                            // $this->table_start();
                            $this->tbl_project_title( $project_id );
                            echo $content . '</table>';
                            // $this->table_close();
                            $manager_message .= ob_get_clean();
                        }
                        break;
                    case 'co_worker':

                        ob_start();
                        $this->co_worker_per_day_activity( $user_id, $project_id, $co_worker_tasks, $co_worker_complete_tasks, $co_worker_due_tasks );
                        $content = ob_get_clean();

                        if ( $content ) {
                            ob_start();
                            // $this->table_start();
                            $this->tbl_project_title( $project_id );
                            echo $content . '</table>';
                            // $this->table_close();
                            $co_worker_message .= ob_get_clean();
                        }
                        break;
                }
            }

            $project_id_info = get_user_by( 'id', $user_id );
            $date            = date( 'jS F, Y', strtotime( current_time( 'mysql' ) ) );
            $title           = sprintf( __( '[%s] - Project Manager Daily Digest (%s)', 'cpm' ), get_bloginfo( 'name' ), $date );

            if ( isset( $manager_message ) && $manager_message ) {
                ob_start();
                $this->table_start();
                echo $manager_message;
                $this->table_close();
                $message = ob_get_clean();

                // echo $message;
                wp_mail( $project_id_info->user_email, $title, $message, array( 'Content-Type: text/html' ) );
            }
            if ( isset( $co_worker_message ) && $co_worker_message ) {
                ob_start();
                $this->table_start();
                echo $co_worker_message;
                $this->table_close();
                $message = ob_get_clean();

                // echo $message;
                wp_mail( $project_id_info->user_email, $title, $message, array( 'Content-Type: text/html' ) );
            }
        }
    }

    /**
     * Co_worker new query within 24 hour
     *
     * @since 1.1
     */
    function tasks_query( $complete_status = 1 ) {
        global $wpdb;

        $post_table = $wpdb->prefix . 'posts';
        $item_table = $wpdb->prefix . 'cpm_project_items';
        $task_table = $wpdb->prefix . 'cpm_tasks';

        $current_time = current_time( 'mysql' );

        $sql = "SELECT pp.post_title as task_title, tl.post_title as list_title, pj.post_title as project_title,
            pj.post_author as author, it.complete_status as complete, itk.user_id as user_id, it.private as task_private, it.project_id as project_id,
            it.object_id as task_id, it.parent as list_id, itl.private as list_parivate, it.complete_date as complete_date
            FROM {$post_table} as pp
            LEFT JOIN {$item_table} as it ON pp.ID = it.object_id
            LEFT JOIN {$item_table} as itl ON it.parent = itl.object_id
            LEFT JOIN {$post_table} as tl ON pp.post_parent = tl.ID
            LEFT JOIN {$post_table} as pj ON tl.post_parent = pj.ID
            LEFT JOIN {$task_table} as itk ON it.id = itk.item_id
            WHERE
            pp.post_type ='cpm_task'
            AND
            ( pp.post_date >= '$this->check_time' AND pp.post_date <= '$current_time' )
            AND
            it.complete_status = $complete_status
            ";
        return $wpdb->get_results( $sql );
    }

    /**
     * Co worker Due Tasks
     * @param  integer $complete_status [complete task = 0]
     * @return [array]                   [due task]
     */
    function due_tasks_query( $complete_status = 0 ) {
        global $wpdb;

        $post_table   = $wpdb->prefix . 'posts';
        $item_table   = $wpdb->prefix . 'cpm_project_items';
        $task_table   = $wpdb->prefix . 'cpm_tasks';
        $current_time = current_time( 'mysql' );
        $sql          = "SELECT pp.post_title as task_title, tl.post_title as list_title, pj.post_title as project_title,
                pj.post_author as author, it.complete_status as complete, itk.user_id as user_id, itk.due as due_date, it.private as task_private, it.project_id as project_id,
                it.object_id as task_id, it.parent as list_id, itl.private as list_parivate, it.complete_date as complete_date
                FROM {$post_table} as pp
                LEFT JOIN {$item_table} as it ON pp.ID = it.object_id
                LEFT JOIN {$item_table} as itl ON it.parent = itl.object_id
                LEFT JOIN {$post_table} as tl ON pp.post_parent = tl.ID
                LEFT JOIN {$post_table} as pj ON tl.post_parent = pj.ID
                LEFT JOIN {$task_table} as itk ON it.id = itk.item_id
                WHERE pp.post_type ='cpm_task'
                AND ( (it.complete_status = $complete_status)  AND ('$current_time' > itk.due) )
            ";
        return $wpdb->get_results( $sql );
    }

    /**
     * Manager new milestone query within 24 hour
     *
     * @since 1.1
     */
    function milestone_query( $complete_status = 1 ) {
        global $wpdb;

        $post_table   = $wpdb->prefix . 'posts';
        $item_table   = $wpdb->prefix . 'cpm_project_items';
        $current_time = current_time( 'mysql' );

        $sql = "SELECT ml.post_title as milestone_title, pj.post_title as project_title, pj.post_author as author, it.complete_status as complete,
            it.object_id as milestone_id, it.project_id as project_id
            FROM {$post_table} as ml
            LEFT JOIN {$item_table} as it ON ml.ID = it.object_id
            LEFT JOIN {$post_table} as pj ON ml.post_parent = pj.ID
            WHERE
            ml.post_type ='cpm_milestone'
            AND
            ( ml.post_date >= '$this->check_time' AND ml.post_date <= '$current_time' )
            AND
            it.complete_status = $complete_status
            ";
        return $wpdb->get_results( $sql );
    }

    /**
     * Start table
     *
     * @since 1.1
     */
    function table_start() {
        ?>
        <!DOCTYPE html>
        <html>
            <head>
                <meta charset="utf-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <title><?php _e( 'Project Daily Digest', 'cpm' ); ?></title>
            </head>
            <body style="background-color: #f4f4f4; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:14px; color: #666; margin: 0; padding-top: 30px;">
                <div style="width: 600px; padding: 20px; border: 1px solid #eee; margin: 0 auto; background-color: #fff;">
                    <table width="100%" bgcolor="#fff" style="border-collapse: collapse; border: none;">
                        <tr>
                            <td style="font-weight: bold; font-size: 22px; padding-bottom: 5px;"><?php _e( 'Project Daily Digest', 'cpm' ); ?></td>
                        </tr>
                        <tr>
                            <td style="font-size: 11px; color: #9d9d9d; padding: 0;"><?php _e( 'Project items due or added or completed in the last 24 hours', 'cpm' ); ?></td>
                        </tr>
                        <?php
                    }

                    /**
                     * Close table
                     *
                     * @since 1.1
                     */
    function table_close() {
                        $site_link = sprintf( '<a href="%s" style="color: #1993c4;text-decoration: none;">%s</a>', site_url(), get_bloginfo( 'name' ) );
                        $plug_link = sprintf( '<a href="%s" style="color: #1993c4;text-decoration: none;">%s</a>', 'https://wedevs.com/products/plugins/wp-project-manager-pro/?utm_source=cpm_digest&utm_medium=email&utm_campaign=cpm', 'WP Project Manager' );
                        ?>
                    </table>
                </div>
                <div style="width: 600px; padding: 20px; margin: 0 auto;">
                    <span style="font-size: 11px; text-align: center; display: block; font-style: italic;">
                        <?php printf( __( 'This is an automated email from %s, powered by %s.', 'cpm' ), $site_link, $plug_link ); ?>
                    </span>
                </div>
            </body>
        </html>
        <?php
    }

    function tbl_project_title( $project_id ) {
        $project = get_post( $project_id );
        if ( ! $project ) {
            return;
        }
        ?>
        <table width="100%" bgcolor="#fff" style="border-collapse: collapse; border: 1px solid #f4f2f2; margin-top: 25px;">
            <tr>
                <td style="padding: 15px; background-color: #f4f2f2; border-bottom: 1px solid #f4f2f2;">
                    <a style="color: #336364; font-size: 16px;"
                       href="<?php echo $this->cpm_url_project_details( $project_id ); ?>"><?php echo esc_html( $project->post_title ); ?></a>
                </td>
            </tr>
            <?php
        }

    function task_private( $project_id, $task, $settings_role ) {
            $private = false;

            if ( $task->task_private ) {
                if ( ! isset( $settings_role['co_worker']['todo_view_private'] ) ) {
                    $private = true;
                }
            }

            return $private;
    }

    function is_list_parivate( $project_id, $tasks, $settings_role ) {
            $task    = reset( $tasks );
            $private = false;

            if ( $task->list_parivate ) {
                if ( ! isset( $settings_role['co_worker']['tdolist_view_private'] ) ) {
                    $private = true;
                }
            }

            return $private;
    }

        /**
         * Get per day activity for co-worker
         *
         * @param int $user_id
         * @param int $project_id
         * @param array $co_worker_tasks
         * @param array $co_worker_complete_tasks
         *
         * @since 1.1
         */
    function co_worker_per_day_activity( $user_id, $project_id, $co_worker_tasks, $co_worker_complete_tasks, $co_worker_due_tasks ) {

            $settings_role = get_post_meta( $project_id, '_settings', true );

            if ( isset( $co_worker_tasks[$project_id][$user_id] ) && $co_worker_tasks[$project_id][$user_id] ) {
                $lists = $co_worker_tasks[$project_id][$user_id];
                ?>
                <tr>
                    <td style="font-weight: bold; color:#336364; padding: 15px;  border-top: 0; border-bottom: 0"><?php _e( 'New to-do items:', 'cpm' ); ?></td>
                </tr>
                <?php
                foreach ( $lists as $list_id => $tasks ) {

                    $list_parivate = $this->is_list_parivate( $project_id, $tasks, $settings_role );

                    if ( $list_parivate ) {
                        continue;
                    }
                    $task = reset( $tasks );
                    ?>
                    <tr>
                        <td style="color:#1993c4; padding-left: 20px;">
                            <a style="color: #1993c4; font-size: 12px;"
                               href="<?php echo $this->cpm_url_single_tasklist( $task->project_id, $task->list_id ); ?>"><?php echo esc_html( $task->list_title ); ?></a>
                        </td>
                    </tr>
                    <?php
                    foreach ( $tasks as $task_id => $task ) {
                        $private = $this->task_private( $project_id, $task, $settings_role );

                        if ( $private ) {
                            continue;
                        }
                        $this->render_task_html( $task );
                    }
                    ?>
                    <tr><td style="padding-bottom: 8px; border-top: 0; border-bottom: 0" ></td></tr>
                    <?php
                }
            }
            /**
             * Co-worker Complete Task
             */
            if ( isset( $co_worker_complete_tasks[$project_id][$user_id] ) && $co_worker_complete_tasks[$project_id][$user_id] ) {

                $lists = $co_worker_complete_tasks[$project_id][$user_id];
                ?>
                <tr>
                    <td style="color:#1993c4; padding-left: 20px;"><?php _e( 'Completed to-do items:', 'cpm' ); ?></td>
                </tr>
            <?php
            foreach ( $lists as $list_id => $tasks ) {

                $list_parivate = $this->is_list_parivate( $project_id, $tasks, $settings_role );

                if ( $list_parivate ) {
                    continue;
                }
                $task = reset( $tasks );
                ?>
                    <tr>
                        <td style="color:#1993c4; padding-left: 20px;">
                            <a style="color: #1993c4; font-size: 12px;"
                               href="<?php echo $this->cpm_url_single_tasklist( $task->project_id, $task->list_id ); ?>"><?php echo esc_html( $task->list_title ); ?></a>
                        </td>
                    </tr>
                <?php
                foreach ( $tasks as $task_id => $task ) {
                    $private = $this->task_private( $project_id, $task, $settings_role );

                    if ( $private ) {
                        continue;
                    }

                    $this->render_task_html( $task, 1 );
                }
                ?>
                    <tr><td style="padding-bottom: 8px;  border-top: 0; border-bottom: 0" ></td></tr>
                    <?php
                }
            }

            /**
             * Co-worker Due Task
             */
            if ( isset( $co_worker_due_tasks[$project_id][$user_id] ) && $co_worker_due_tasks[$project_id][$user_id] ) {

                $lists = $co_worker_due_tasks[$project_id][$user_id];
                ?>
                <tr>
                    <td style="color:#1993c4; padding-left: 20px;"><?php _e( 'Due to-do items:', 'cpm' ); ?></td>
                </tr>
                <?php
                foreach ( $lists as $list_id => $tasks ) {

                    $list_parivate = $this->is_list_parivate( $project_id, $tasks, $settings_role );

                    if ( $list_parivate ) {
                        continue;
                    }
                    $task = reset( $tasks );
                    ?>
                    <tr>
                        <td style="color:#1993c4; padding-left: 20px;">
                            <a style="color: #1993c4; font-size: 12px;"
                               href="<?php echo $this->cpm_url_single_tasklist( $task->project_id, $task->list_id ); ?>"><?php echo esc_html( $task->list_title ); ?></a>
                        </td>
                    </tr>
                <?php
                foreach ( $tasks as $task_id => $task ) {
                    $private = $this->task_private( $project_id, $task, $settings_role );

                    if ( $private ) {
                        continue;
                    }
                    ?>

                        <?php
                        $this->render_task_html( $task, 0 ); // for due task status 0
                    }
                    ?>
                    <tr><td style="padding-bottom: 8px;  border-top: 0; border-bottom: 0" ></td></tr>
                    <?php
                }
            }
        }

        /**
         * Get per day activity for manager
         *
         * @param int $user_id
         * @param int $project_id
         * @param array $manager_tasks
         * @param array $manager_complete_tasks
         * @param array $manager_milestone
         * @param array $manager_complete_milestone
         *
         * @since 1.1
         */
    function manager_per_day_activity( $user_id, $project_id, $manager_tasks, $manager_complete_tasks, $manager_due_task, $manager_milestone, $manager_complete_milestone ) {

            if ( isset( $manager_tasks[$project_id] ) && $manager_tasks[$project_id] ) {
                $lists = $manager_tasks[$project_id];
                ?>
                <tr>
                    <td style="font-weight: bold; color:#336364; padding: 15px;  border-top: 1px solid #f5f5f5; border-bottom: 0"><?php _e( 'New to-do items:', 'cpm' ); ?></td>
                </tr>
                <?php
                foreach ( $lists as $list_id => $tasks ) {
                    $task = reset( $tasks );
                    ?>
                    <tr>
                        <td style="color:#1993c4; padding-left: 20px; padding-bottom: 5px;">
                            <a style="color: #1993c4; font-size: 12px;"
                               href="<?php echo $this->cpm_url_single_tasklist( $task->project_id, $task->list_id ); ?>"><?php echo esc_html( $task->list_title ); ?></a>
                        </td>
                    </tr>
                <?php
                foreach ( $tasks as $task_id => $task ) {

                    $this->render_task_html( $task );
                }
                ?>
                    <tr><td style="padding-bottom: 8px;  border-top: 0; border-bottom: 0" ></td></tr>
                    <?php
                }
            }

            if ( isset( $manager_complete_tasks[$project_id] ) && $manager_complete_tasks[$project_id] ) {

                $lists = $manager_complete_tasks[$project_id];
                ?>
                <tr>
                    <td style="font-weight: bold; color:#336364; padding: 15px; border-top: 1px solid #f5f5f5; border-bottom: 0"><?php _e( 'Completed to-do items:', 'cpm' ); ?></td>
                </tr>
                <?php
                foreach ( $lists as $list_id => $tasks ) {

                    $task = reset( $tasks );
                    ?>
                    <tr>
                        <td style="color:#1993c4; padding-left: 20px; padding-bottom: 5px;">
                            <a style="color: #1993c4; font-size: 12px;"
                               href="<?php echo $this->cpm_url_single_tasklist( $task->project_id, $task->list_id ); ?>"><?php echo esc_html( $task->list_title ); ?></a>
                        </td>
                    </tr>
                <?php
                foreach ( $tasks as $task_id => $task ) {
                    $this->render_task_html( $task, 1 ); // for Complete task status 1
                }
                ?>
                    <tr><td style="padding-bottom: 8px; border-top: 0; border-bottom: 0" ></td></tr>
                    <?php
                }
            }
            /**
             * Manager Due Task
             */
            if ( isset( $manager_due_task[$project_id] ) && $manager_due_task[$project_id] ) {

                $lists = $manager_due_task[$project_id];
                ?>
                <tr>
                    <td style="font-weight: bold; color:#336364; padding: 15px; border-top: 1px solid #f5f5f5; border-bottom: 0"><?php _e( 'Due to-do items:', 'cpm' ); ?></td>
                </tr>
                <?php
                foreach ( $lists as $list_id => $tasks ) {

                    $task = reset( $tasks );
                    ?>
                    <tr>
                        <td style="color:#1993c4; padding-left: 20px; padding-bottom: 5px;">
                            <a style="color: #1993c4; font-size: 12px;"
                               href="<?php echo $this->cpm_url_single_tasklist( $task->project_id, $task->list_id ); ?>"><?php echo esc_html( $task->list_title ); ?></a>
                        </td>
                    </tr>
                <?php
                foreach ( $tasks as $task_id => $task ) {
                    $this->render_task_html( $task, 0 ); // for due task status 0
                }
                ?>
                    <tr><td style="padding-bottom: 8px; border-top: 0; border-bottom: 0" ></td></tr>
                    <?php
                }
            }
            /**
             * Manager Milestone
             */
            if ( isset( $manager_milestone[$project_id] ) && $manager_milestone[$project_id] ) {
                ?>
                <tr>
                    <td style="font-weight: bold; color:#336364; padding: 15px; border-top: 1px solid #f5f5f5; border-bottom: 0"><?php _e( 'New milestones:', 'cpm' ); ?></td>
                </tr>
                <?php
                foreach ( $manager_milestone[$project_id] as $key => $milestone ) {
                    ?>
                    <tr>
                        <td style="padding-left: 36px;  border-top: 0; border-bottom: 0;">
                            <span
                                style="font-size: 12px; font-weight: bold; color:#3E3E3E; text-decoration: none;">
                                <a href="<?php echo $this->cpm_url_single_milestone( $milestone->project_id, $milestone->milestone_id ); ?>" ><?php echo esc_html( $milestone->milestone_title ); ?></a></span>
                            <span
                                style="padding:2px; color: #6A6C6D; font-size:11px;  border-radius: 3px;"><?php //printf( '%s %s - %s', human_time_diff( time(), $due ), $string, cpm_get_date( $milestone->due_date ) );     ?>
                            </span>
                        </td>
                    </tr>

                <?php
            }
            echo '<tr><td style="padding-bottom: 8px;  border-top: 0; border-bottom: 0"></td></tr>';
        }

        if ( isset( $manager_complete_milestone[$project_id] ) && $manager_complete_milestone[$project_id] ) {
            ?>
                <tr>
                    <td style="font-weight: bold; color:#336364; padding: 15px; border-top: 1px solid #f5f5f5; border-bottom: 0"><?php _e( 'New milestones:', 'cpm' ); ?></td>
                </tr>
                <?php
                foreach ( $manager_complete_milestone[$project_id] as $key => $milestone ) {
                    ?>
                    <tr>
                        <td style="padding-left: 36px; border-top: 0; border-bottom: 0;">
                            <span
                                style="font-size: 12px; font-weight: bold; color:#3E3E3E; text-decoration: none;">
                                <a href="<?php echo $this->cpm_url_single_milestone( $milestone->project_id, $milestone->milestone_id ); ?>" ><?php echo esc_html( $milestone->milestone_title ); ?></a></span>
                            <span
                                style="padding:2px; color: #6A6C6D; font-size:11px;  border-radius: 3px;"><?php //printf( '%s %s - %s', human_time_diff( time(), $due ), $string, cpm_get_date( $milestone->due_date ) );     ?>
                            </span>
                        </td>
                    </tr>

                <?php
            }
            echo '<tr><td style="padding-bottom: 8px; border-top: 0; border-bottom: 0"></td></tr>';
        }
    }

    function render_task_html( $task, $status = 0 ) {
        // $imgurl = ( $status == 1 ) ?  '&#9745;' : '&#x25a2;' ;
        ?>
            <tr>
                <td style="padding-left: 20px; border-top: 0; border-bottom: 0;"><?php $this->complete_task_html( $task ); ?></td>
            </tr>
            <?php
        }

        /**
         * Single task list URL
         *
         * @param int $project_id
         * @param int $list_id
         * @return string
         */
    function cpm_url_single_tasklist( $project_id, $list_id ) {
            if ( $this->_link_option == 'frontend' ) {
                $url = add_query_arg( array(
                    'project_id' => $project_id,
                    'tab'        => 'task',
                    'action'     => 'single',
                    'list_id'    => $list_id
                        ), get_permalink( $this->_page_id ) );
            } else {
                $url = sprintf( '%s?page=cpm_projects&tab=task&action=single&pid=%d&tl_id=%d', admin_url( 'admin.php' ), $project_id, $list_id );
            }
            return apply_filters( 'cpm_url_digest_single_tasklislt', $url, $project_id, $list_id );
        }

        /**
         * Single task URL
         *
         * @param int $project_id
         * @param int $list_id
         * @param int $task_id
         * @return string
         */
    function cpm_url_single_task( $project_id, $list_id, $task_id ) {
            if ( $this->_link_option == 'frontend' ) {
                $url = add_query_arg( array(
                    'project_id' => $project_id,
                    'tab'        => 'task',
                    'action'     => 'todo',
                    'list_id'    => $list_id,
                    'task_id'    => $task_id
                        ), get_permalink( $this->_page_id ) );
            } else {
                $url = sprintf( '%s?page=cpm_projects&tab=task&action=task_single&pid=%d&tl_id=%d&task_id=%d', admin_url( 'admin.php' ), $project_id, $list_id, $task_id );
            }

            return apply_filters( 'cpm_url_digest_single_task', $url, $project_id, $list_id, $task_id );
        }

        /**
         * Milestone single page URL
         *
         * @param int $project_id
         * @param int $milestone_id
         * @return string
         */
    function cpm_url_single_milestone( $project_id, $milestone_id ) {
            $url = sprintf( '%s?page=cpm_projects&tab=milestone&action=single&pid=%d&ml_id=%d', admin_url( 'admin.php' ), $project_id, $milestone_id );

            return apply_filters( 'cpm_url_digest_single_milestone', $url, $project_id, $milestone_id );
        }

        /**
         * Displays a single project URL
         *
         * @since 0.1
         * @param int $project_id
         * @return string
         */
    function cpm_url_project_details( $project_id ) {
            if ( $this->_link_option == 'frontend' ) {
                $url = add_query_arg( array(
                    'project_id' => $project_id,
                    'tab'        => 'project',
                    'action'     => 'index'
                        ), get_permalink( $this->_page_id ) );
            } else {
                $url = sprintf( '%s?page=cpm_projects&tab=project&action=index&pid=%d', admin_url( 'admin.php' ), $project_id );
            }

            return apply_filters( 'cpm_url_digest_project_details', $url, $project_id );
        }

        /**
         * HTML generator compelete task content
         *
         * @param obj $task
         *
         * @return string
         */
    function complete_task_html_( $task ) {
            if ( ! $task ) {
                return;
            }
            ?>
            <div class="cpm-todo-wrap">
                <span class="cpm-todo-text" style="text-decoration:line-through; font-size: 12px; font-weight: bold; color:#3E3E3E;">
            <?php echo esc_html( $task->post_content ); ?>
                </span>
            <?php
            if ( $task->completed == '1' && $task->completed_by ) {
                //$completion_time = cpm_get_date( $task->completed_on, true );
                ?>
                    <span style="color: #999; font-size: 11px;">
                    <?php //printf( __( '(Completed by %s on %s)', 'cpm' ), cpm_url_user( $task->completed_by ), $completion_time )  ?>
                    </span>
                <?php } ?>
            </div>

                    <?php
                }

                /**
                 * HTML generator Active / Complete task content
                 *
                 * @param  object $task
                 * @param  [type] $imgurl [string]
                 *
                 * @return string
                 */
    function complete_task_html( $task ) {
                    $start_date = isset( $task->start_date ) ? $task->start_date : '';
                    $check      = ( $task->complete == '1' ) ? '<span style="font-size: 23px;font-weight: normal;line-height: 13px;vertical-align: top;">&#9745;</span>' : '&#9634;';
                    $span       = ( $task->complete == '1' ) ? '<span style="text-decoration: line-through;">' : '<span>';
                    ?>
            <a style="font-size: 13px; font-weight: bold; color:#3E3E3E; text-decoration: none;"
               href="<?php echo $this->cpm_url_single_task( $task->project_id, $task->list_id, $task->task_id ); ?>">
            <?php echo $check . ' ' . $span . esc_html( $task->task_title ) . '</span>'; ?>
            </a>
            <?php
            if ( $task->complete == '1' ) {
                $user_id = get_post_meta( $task->task_id, '_completed_by', true );
                $user    = get_user_by( 'id', $user_id );
                ?>
                <span style="color: #999; font-size: 11px;">
                <?php printf( __( '(Completed by %s)', 'cpm' ), $user->display_name ) ?>
                </span>
            <?php } ?>
            <?php
        }

    }
