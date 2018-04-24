<?php

class CPM_Notification {

    private static $_instance;

    function __construct() {

        //notify users
        add_action( 'cpm_project_new', array( $this, 'project_new' ), 10, 2 );
        add_action( 'cpm_project_update', array( $this, 'project_update' ), 10, 2 );

        add_action( 'cpm_comment_new', array( $this, 'new_comment' ), 10, 3 );
        add_action( 'cpm_comment_update', array( $this, 'update_comment' ), 10, 3 );
        add_action( 'cpm_message_new', array( $this, 'new_message' ), 10, 2 );

        add_action( 'cpm_task_new', array( $this, 'new_task' ), 10, 3 );
        add_action( 'cpm_task_update', array( $this, 'new_task' ), 10, 3 );
    }

    public static function getInstance() {
        if ( ! self::$_instance ) {
            self::$_instance = new CPM_Notification();
        }

        return self::$_instance;
    }

    /**
     * check email link url to front-end
     * @since 1.4.0
     */
    public function check_email_url() {
        if ( cpm_get_option( 'email_url_link', 'cpm_mails' ) == 'frontend' ) {
            new CPM_Frontend_URLs();
        }
    }

    /**
     * Get site name
     *
     * @since 1.3
     *
     * @return string
     */
    public function get_site_name() {
        return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
    }

    function prepare_contacts() {
        $to         = array();
        $bcc_status = cpm_get_option( 'email_bcc_enable' , 'cpm_mails');

        if ( isset( $_POST['notify_user'] ) && is_array( $_POST['notify_user'] ) ) {

            foreach ( $_POST['notify_user'] as $user_id ) {
                $user_info = get_user_by( 'id', $user_id );
                if ( ! $this->filter_email( $user_info->ID ) ) {
                    continue;
                }
                if ( $user_info && $bcc_status == 'on' ) {
                    $to[] = sprintf( '%s', $user_info->user_email );
                    // $to[] = sprintf( '%s (%s)', $user_info->display_name, $user_info->user_email );
                } else if ( $user_info && $bcc_status != 'on' ) {
                    $to[] = sprintf( '%s', $user_info->user_email );
                }
            }
        }

        return $to;
    }

    /**
     * Notify users about the new project creation
     *
     * @uses `cpm_new_project` hook
     * @param int $project_id
     */
    function project_new( $project_id, $data ) {

        if ( isset( $_POST['project_notify'] ) && $_POST['project_notify'] == 'yes' ) {
            $project_users = CPM_Project::getInstance()->get_users( $project_id );
            $users         = array();

            if ( is_array( $project_users ) && count( $project_users ) ) {

                foreach ( $project_users as $user_id => $role_array ) {

                    if ( $this->filter_email( $user_id ) ) {
                        $users[$user_id] = sprintf( '%s', $role_array['email'] );
                        // $users[$user_id] = sprintf( '%s (%s)', $role_array['name'], $role_array['email'] );
                    }
                }
            }

            //if any users left, get their mail addresses and send mail
            if ( ! $users ) {
                return;
            }

            $this->check_email_url();
            $file_name = 'emails/new-project.php';

            $subject = sprintf( __( '[%s] New Project Invitation: %s', 'cpm' ), $this->get_site_name(), get_post_field( 'post_title', $project_id ) );

            // cutoff at 78th character
            if ( cpm_strlen( $subject ) > 78 ) {
                // PATCH: Using mb_substr to safety cutting UTF...   
				$subject = mb_substr( $subject, 0, 78 ) . '...';
            }

			// PATCH: Call filter to customising subkect
			// Filter cpm_email_project_new_subject
			//   Args:
			//		$subject - The subject text
			//		$project - The project title
			$subject = apply_filters('cpm_email_project_new_subject', $subject, get_post_field( 'post_title', $project_id ) );
			
			
            ob_start();
            $arg     = array(
                'project_id' => $project_id,
                'data'       => $data,
            );
            cpm_load_template( $file_name, $arg );
            $message = ob_get_clean();

            if ( $message ) {
                $this->send( implode( ', ', $users ), $subject, $message );
            }
        }
    }

    function filter_email( $user_id ) 
	{
        // Пока так... Надо разобраться почему сбрасывается настройка...
		return TRUE;
		
		$user_email_notification = get_user_meta( $user_id, '_cpm_email_notification', true );

        if ( $user_email_notification == 'off' ) {
            return false;
        }

        return true;
    }

    /**
     * Notify users about the update project creation
     *
     * @uses `cpm_new_project` hook
     * @param int $project_id
     */
    function project_update( $project_id, $data ) {

        if ( isset( $_POST['project_notify'] ) && $_POST['project_notify'] == 'yes' ) {
            $project_users = CPM_Project::getInstance()->get_users( $project_id );
            $users         = array();

            if ( is_array( $project_users ) && count( $project_users ) ) {

                foreach ( $project_users as $user_id => $role_array ) {

                    if ( $this->filter_email( $user_id ) ) {
                        $users[$user_id] = sprintf( '%s', $role_array['email'] );
                        // $users[$user_id] = sprintf( '%s (%s)', $role_array['name'], $role_array['email'] );
                    }
                }
            }

            //if any users left, get their mail addresses and send mail
            if ( ! $users ) {
                return;
            }


            $this->check_email_url();
            $file_name = 'emails/update-project.php';


            $subject = sprintf( __( '[%s] Updated Project Invitation: %s', 'cpm' ), $this->get_site_name(), get_post_field( 'post_title', $project_id ) );

            // cutoff at 78th character
            if ( cpm_strlen( $subject ) > 78 ) {
				// PATCH: Using mb_substr to safety cutting UTF...
                $subject = mb_substr( $subject, 0, 78 ) . '...';
            }
			
			// PATCH: Call filter to customising subject
			// Filter cpm_email_project_update_subject
			//   Args:
			//		$subject - The subject text
			//		$project - The project title
			$subject = apply_filters('cpm_email_project_update_subject', $subject, get_post_field( 'post_title', $project_id ) );
			
                ob_start();
                $arg = array(
                    'project_id' => $project_id,
                    'data'       => $data,
                );
                cpm_load_template( $file_name, $arg );

                $message = ob_get_clean();
                if ( $message ) {
                    $this->send( implode( ', ', $users ), $subject, $message );
                }

        }
    }

    function complete_task( $list_id, $task_id, $data, $project_id ) 
	{
        // Ничего не делаем! В будущем отправлять только тем, кто был в задаче
		return;
		
		$project_users = CPM_Project::getInstance()->get_users( $project_id );
        $users         = array();

        if ( is_array( $project_users ) && count( $project_users ) ) {
            foreach ( $project_users as $user_id => $role_array ) {
                if ( $role_array['role'] == 'manager' ) {
                    if ( $this->filter_email( $user_id ) ) {
                        // $users[$user_id] = sprintf( '%s (%s)', $role_array['name'], $role_array['email'] );
                        $users[$user_id] = sprintf( '%s', $role_array['email'] );
                    }
                }
            }
        }

        if ( ! $users ) {
            return;
        }

        $this->check_email_url();
        $file_name = 'emails/complete-task.php';
        $subject   = sprintf( __( '[%s][%s] Task Completed: %s', 'cpm' ), $this->get_site_name(), get_post_field( 'post_title', $project_id ), get_post_field( 'post_title', $task_id ) );

        // cutoff at 78th character
        if ( cpm_strlen( $subject ) > 78 ) {
			// PATCH: Using mb_substr to safety cutting UTF...			
            $subject = mb_substr( $subject, 0, 78 ) . '...';
        }
		
			// PATCH: Call filter to customising subject
			// Filter cpm_email_complete_task_subject
			//   Args:
			//		$subject 	- The subject text
			//		$project 	- The project title
			//		$task 		- The task title
			$subject = apply_filters('cpm_email_complete_task_subject', $subject, get_post_field( 'post_title', $project_id ), get_post_field( 'post_title', $task_id ) );
		

        ob_start();

        $arg = array(
            'list_id'    => $list_id,
            'task_id'    => $task_id,
            'project_id' => $project_id,
            'data'       => $data,
        );
        cpm_load_template( $file_name, $arg );

        $message = ob_get_clean();

        if ( $message ) {
            $this->send( implode( ', ', $users ), $subject, $message );
        }
    }

    function new_message( $message_id, $project_id ) {
        $users = $this->prepare_contacts();
        if ( ! $users ) {
            return;
        }
        $this->check_email_url();
        $file_name = 'emails/new-message.php';

        $subject = sprintf( __( '[%s][%s] New Message: %s', 'cpm' ), $this->get_site_name(), get_post_field( 'post_title', $project_id ), get_post_field( 'post_title', $message_id ) );

        // cutoff at 78th character
        if ( cpm_strlen( $subject ) > 78 ) {
			// PATCH: Using mb_substr to safety cutting UTF...
            $subject = mb_substr( $subject, 0, 78 ) . '...';
        }
		
		// PATCH: Call filter to customising subject
		// Filter cpm_email_new_message_subject
		//   Args:
		//		$subject 	- The subject text
		//		$project 	- The project title
		//		$message 	- The task message
		$subject = apply_filters('cpm_email_new_message_subject', $subject, get_post_field( 'post_title', $project_id ), get_post_field( 'post_title', $message_id ) );
		
		
		
        ob_start();
        $arg     = array(
            'project_id' => $project_id,
            'message_id' => $message_id,
        );
        cpm_load_template( $file_name, $arg );
        $message = ob_get_clean();

        if ( $message ) {
            $this->send( implode( ', ', $users ), $subject, $message );
        }
    }

    /**
     * Send email to all about a new comment
     *
     * @param int $comment_id
     * @param array $comment_info the post data
     */
    function new_comment( $comment_id, $project_id, $data ) {

        $users = $this->prepare_contacts();
        if ( ! $users ) {
            return;
        }

        $this->check_email_url();
        $file_name   = 'emails/new-comment.php';
        $parent_post = get_comment( $comment_id );
        $subject     = sprintf( __( '[%s][%s] New Comment on: %s', 'cpm' ), $this->get_site_name(), get_post_field( 'post_title', $project_id ), get_post_field( 'post_title', $parent_post->comment_post_ID ) );

        // cutoff at 78th character
        if ( cpm_strlen( $subject ) > 78 ) {
			// PATCH: Using mb_substr to safety cutting UTF...
            $subject = mb_substr( $subject, 0, 78 ) . '...';
        }
		
		// PATCH: Call filter to customising subject
		// Filter cpm_email_new_comment_subject
		//   Args:
		//		$subject 	- The subject text
		//		$project 	- The project title
		//		$task 		- The task title
		$subject = apply_filters('cpm_email_new_comment_subject', $subject, get_post_field( 'post_title', $project_id ), get_post_field( 'post_title', $parent_post->comment_post_ID ) );
		
		
        ob_start();
        $arg     = array(
            'project_id' => $project_id,
            'comment_id' => $comment_id,
            'data'       => $data
        );
        cpm_load_template( $file_name, $arg );
        $message = ob_get_clean();

        if ( $message ) {
            $this->send( implode( ', ', $users ), $subject, $message, $parent_post->comment_post_ID );
        }
    }

    /**
     * Send email to all about a  comment Update
     * @since 1.5.1
     * @param int $comment_id
     * @param array $comment_info the post data
     */
    function update_comment( $comment_id, $project_id, $data ) {

        $users = $this->prepare_contacts();
        if ( ! $users ) {
            return;
        }

        $this->check_email_url();
        $file_name   = 'emails/update-comment.php';
        $parent_post = get_comment( $comment_id );
        $subject     = sprintf( __( '[%s][%s] Uudate Comment on: %s', 'cpm' ), $this->get_site_name(), get_post_field( 'post_title', $project_id ), get_post_field( 'post_title', $parent_post->comment_post_ID ) );

        // cutoff at 78th character
        if ( cpm_strlen( $subject ) > 78 ) {
			// PATCH: Using mb_substr to safety cutting UTF...
            $subject = mb_substr( $subject, 0, 78 ) . '...';
        }
		
		// PATCH: Call filter to customising subject
		// Filter cpm_email_update_comment_subject
		//   Args:
		//		$subject 	- The subject text
		//		$project 	- The project title
		//		$task 		- The task title
		$subject = apply_filters('cpm_email_update_comment_subject', $subject, get_post_field( 'post_title', $project_id ), get_post_field( 'post_title', $parent_post->comment_post_ID ) );
		
		
        ob_start();
        $arg     = array(
            'project_id' => $project_id,
            'comment_id' => $comment_id,
            'data'       => $data
        );
        cpm_load_template( $file_name, $arg );
        $message = ob_get_clean();
      
        if ( $message ) {
           $this->send( implode( ', ', $users ), $subject, $message, $parent_post->comment_post_ID );
        }
    }

    function new_task( $list_id, $task_id, $data ) {
        $new_task_notification = apply_filters( 'cpm_new_task_notification', true );

        if ( ! $new_task_notification ) {
            return;
        }

        $this->check_email_url();
        $file_name = 'emails/new-task.php';


        $_POST['task_assign'] = isset( $_POST['task_assign'] ) ? $_POST['task_assign'] : array();
        if ( $_POST['task_assign'] == '-1' ) {
            return;
        }

        $project_id = 0;

        if ( isset( $_POST['project_id'] ) ) {
            $project_id = intval( $_POST['project_id'] );
        }

        $subject = sprintf( __( '[%s][%s] New Task Assigned: %s', 'cpm' ),  // Текст
                           $this->get_site_name(),                          // Название сайта
                           get_post_field( 'post_title', $project_id ),     // Проект
                           get_post_field( 'post_title', $list_id ) );      // Лист

        // cutoff at 78th character
        if ( cpm_strlen( $subject ) > 78 ) {
			// PATCH: Using mb_substr to safety cutting UTF...
            $subject = mb_substr( $subject, 0, 78 ) . '...';
        }

		// PATCH: Call filter to customising subject
		// Filter cpm_email_new_task_subject
		//   Args:
		//		$subject 	- The subject text
		//		$project 	- The project title
		//		$list 		- The list title
		$subject = apply_filters('cpm_email_new_task_subject', $subject,                                                  // Тема
								 get_post_field( 'post_title', $project_id ),                                                         // Проект
								 get_post_field( 'post_title', $list_id ) . ': ' .  get_post_field( 'post_title', $task_id )  );      // Список  задача
		
		
		
		
        foreach ( $_POST['task_assign'] as $key => $user_id ) {
            $user = get_user_by( 'id', intval( $user_id ) );

            if ( ! $this->filter_email( $user_id ) ) {
                continue;
            }

            $to = sprintf( '%s', $user->user_email );


            ob_start();
            $arg = array(
                'project_id' => $project_id,
                'list_id'    => $list_id,
                'task_id'    => $task_id,
                'data'       => $data,
            );
            cpm_load_template( $file_name, $arg );
            $message = ob_get_clean();

            if ( $message ) {
                $this->send( $to, $subject, $message );
            }
        }
    }

    function send( $to, $subject, $message, $comment_post_id = 0 ) {

        $bcc_status   = cpm_get_option( 'email_bcc_enable', 'cpm_mails' );
        $blogname     = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
        $reply        = 'no-reply@' . preg_replace( '#^www\.#', '', strtolower( $_SERVER['SERVER_NAME'] ) );
        $no_reply     = 'no-reply@' . preg_replace( '#^www\.#', '', strtolower( $_SERVER['SERVER_NAME'] ) );
        $content_type = 'Content-Type: text/html';
        $charset      = 'Charset: UTF-8';
        $from_email   = cpm_get_option( 'email_from', 'cpm_mails' );
		
		// PATCHED: Формируем заголовок Reply-To
		$current_user = wp_get_current_user();
		if ( $current_user instanceof WP_User )
		{
			$reply = $current_user->user_email;			
		}
		
		// PATCHED: Add filter
        $from         = apply_filters( 'cpm_from_email', "From: $blogname <$from_email>") ;
        $reply_to     = "Reply-To: $reply";
        $return_path     = "Return-Path: $reply";

        if ( $bcc_status == 'on' ) {
            $bcc     = 'Bcc: ' . $to;
            $headers = array(
                $bcc,
                $reply_to,
                $return_path,
                $content_type,
                $charset,
                $from,
            );

            wp_mail( $no_reply, $subject, $message, $headers );
        } else {

            $headers = array(
                $reply_to,
				$return_path,
                $content_type,
                $charset,
                $from,
            );

            wp_mail( $to, $subject, $message, $headers );
        }
    }

}
