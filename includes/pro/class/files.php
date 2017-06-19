<?php

/**
 *  Pro File Upload and create Document
 *
 * @since 1.4.3
 */
class CPM_Pro_Files {

    protected static $_instance = null;

    public  $_files_per_page = 10 ;
    public $_files_name_show = 20;


    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    function __construct() {

        add_action( 'cpm_show_file_before', array( $this, 'cpm_show_file_propart' ) );
        add_action( 'cpm_admin_scripts', array( $this, 'files_scripts' ) );
        add_filter( 'cpm_message', array( $this, 'show_message' ) );
        add_filter( 'init', array( $this, 'register_post_type' ) );

        // Handle all AJAX call.
        add_action( 'wp_ajax_cpm_pro_folder_new', array( $this, 'new_folder_create' ) );
        add_action( 'wp_ajax_cpm_pro_folder_rename', array( $this, 'folder_rename' ) );
        add_action( 'wp_ajax_cpm_pro_change_ff_privacy', array( $this, 'change_ff_privacy' ) );
        add_action( 'wp_ajax_cpm_pro_folder_delete', array( $this, 'folder_delete' ) );
        add_action( 'wp_ajax_cpm_pro_get_file_folder', array( $this, 'get_files_folder' ) );
        add_action( 'wp_ajax_cpm_pro_get_more_files', array( $this, 'get_more_files' ) );
        add_action( 'wp_ajax_cpm_pro_get_folderpath', array( $this, 'get_folder_path' ) );
        add_action( 'cpm_pro_get_folderinfo', array( $this, 'get_folderinfo' ) );

        add_action( 'wp_ajax_cpm_pro_file_new', array( $this, 'file_new_uplaod' ) );
        add_action( 'wp_ajax_cpm_delete_uploded_file', array( $this, 'delete_uploded_file' ) );
        add_action( 'wp_ajax_cpm_change_file_privacy', array( $this, 'change_file_privacy' ) );
        add_action( 'wp_ajax_cpm_create_new_doc', array( $this, 'create_new_doc' ) );
        add_action( 'wp_ajax_cpm_create_goole_doc', array( $this, 'create_newgoogle_doc' ) );
        add_action( 'wp_ajax_cpm_pro_doc_update', array( $this, 'doc_update' ) );
        add_action( 'wp_ajax_cpm_get_doc_comments', array( $this, 'get_doc_comments' ) );
        add_action( 'wp_ajax_cpm_get_doc_revision', array( $this, 'cpm_pro_doc_revision' ) );
        add_action( 'wp_ajax_cpm_pro_create_comment', array( $this, 'cpm_pro_create_comment' ) );
        $this->_files_name_show = apply_filters('cpm_files_name_show', $this->_files_name_show) ;
    }

    function register_post_type() {
        register_post_type( 'cpm_docs', array(
            'label'               => __( 'Documents', 'cpm' ),
            'description'         => __( 'Documents', 'cpm' ),
            'public'              => false,
            'show_in_admin_bar'   => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'show_in_admin_bar'   => false,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'rewrite'             => array( 'slug' => 'cpm-docs' ),
            'query_var'           => true,
            'supports'            => array( 'title', 'editor', 'delete', 'revisions' ),
            'show_in_json'        => true,
            'labels'              => array(
                'name'               => __( 'Documents', 'cpm' ),
                'singular_name'      => __( 'Document', 'cpm' ),
                'menu_name'          => __( 'Documents', 'cpm' ),
                'add_new'            => __( 'Add Document', 'cpm' ),
                'add_new_item'       => __( 'Add New Document', 'cpm' ),
                'edit'               => __( 'Edit', 'cpm' ),
                'edit_item'          => __( 'Edit Document', 'cpm' ),
                'new_item'           => __( 'New Document', 'cpm' ),
                'view'               => __( 'View Document', 'cpm' ),
                'view_item'          => __( 'View Document', 'cpm' ),
                'search_items'       => __( 'Search Documents', 'cpm' ),
                'not_found'          => __( 'No Documents Found', 'cpm' ),
                'not_found_in_trash' => __( 'No Documentst Found in Trash', 'cpm' ),
                'parent'             => __( 'Parent Document', 'cpm' ),
            ),
        ) );
    }

    function show_message( $message ) {
        if ( isset( $_GET['pid'] ) OR isset( $_GET['project_id'] ) ) {

            if ( isset( $_GET['pid'] ) )
                $pid = $_GET['pid'];
            if ( isset( $_GET['project_id'] ) )
                $pid = $_GET['project_id'];

            $project_obj = CPM_Project::getInstance()->get_info($pid);


            wp_localize_script( 'cpm_admin', 'CPM_pro_files', array(
                'current_project' => $pid,
                'project_obj' => $project_obj,
                'base_url'        => CPM_URL,
                'static_text'     => array(
                    'create_folder'         => __( 'Create a folder', 'cpm' ),
                    'upload_file'           => __( 'Upload a file', 'cpm' ),
                    'create_doc'            => __( 'Create a doc', 'cpm' ),
                    'create_document'       => __( 'Create a document', 'cpm' ),
                    'link_google_doc'       => __( 'Link to Docs', 'cpm' ),
                    'back_previus'          => __( 'Back to previous', 'cpm' ),
                    'file_upload'           => __( 'File uploads', 'cpm' ),
                    'attach_file'           => __( 'Attach a File', 'cpm' ),
                    'make_file_private'     => __( 'Make files private.', 'cpm' ),
                    'make_private'          => __( 'Make this private.', 'cpm' ),
                    'submit'                => __( 'Submit', 'cpm' ),
                    'file_folder_not_found' => __( 'Folder or Documents not found. Please create new folder or document.', 'cpm' ),
                    'folder_create'         => __( 'Create Folder', 'cpm' ),
                    'title'                 => __( 'Title', 'cpm' ),
                    'delete_file'           => __( 'Delete File', 'cpm' ),
                    'to_attach'             => __( 'To attach', 'cpm' ),
                    'select_file'           => __( 'select files', 'cpm' ),
                    'from_computer'         => __( 'from your computer', 'cpm' ),
                    'google_link'           => __( 'Link for  Docs', 'cpm' ),
                    'download'              => __( 'Download', 'cpm'),
                    'note'                  => __( 'Note', 'cpm' ),
                    'view_current_post'     => __( 'View Current Post', 'cpm' ),
                    'view_on_google'        => __( 'View Online', 'cpm' ),
                    'attachment'            => __( 'Attachments', 'cpm' ),
                    'comments'              => __( 'Comments', 'cpm' ),
                    'on'                    => __( 'On', 'cpm' ),
                    'load_more_file'                    => __( ' Load More Files', 'cpm' ),
                    'add_comment'           => __( 'Add Comment', 'cpm' ),
                    'update_doc'            => __( 'Update Doc', 'cpm' ),
                    'cancel_edit'          => __( 'Cancel Edit', 'cpm' ),
                    'revisions'             => __( 'Revisions', 'cpm' ),
                    'no_revision'           => __( 'No Revision', 'cpm' ),
                    'delete_file_confirm'   => __( 'Are you sure to delete this file?', 'cpm' ),
                    'delete_folder'         => __( 'Are you sure to delete this folder?', 'cpm' ),
                    'change_file_privacy'   => __( 'Are you sure to change privacy for this file or folder?', 'cpm' ),
                    'empty_comment'         => __( 'Please write something in comments!', 'cpm' ),
                ),
            ) );
            return $message;
        }
    }

    function get_files_folder() {
        check_ajax_referer( 'cpm_nonce' );
        $base_image_url      = admin_url( 'admin-ajax.php?action=cpm_file_get' );
        $posted              = $_POST;
        $project_id          = isset( $posted['project_id'] ) ? intval( $posted['project_id'] ) : 0;
        $parent              = isset( $posted['parent'] ) ? intval( $posted['parent'] ) : 0;
        $response['success'] = TRUE;
        $backto              = 0;
        $comment_obj         = new CPM_Comment();


        global $wpdb;
        $table        = $wpdb->prefix . 'cpm_file_relationship';
        $sql          = "SELECT * FROM $table WHERE project_id = $project_id  AND parent_id = $parent ";
        $files_folder = $wpdb->get_results( $sql );

        if ( false === $files_folder ) {
            $response['success'] = FALSE;
        }
        $response['folder_list'] = NULL;
        $pro_folder              = array();
        $pro_files               = array();

        foreach ( $files_folder as $ff ) {
            $is_private = $ff->private;
            if ( ! cpm_user_can_access( $project_id, 'file_view_private', $is_private ) ) {
                continue;
            }
            $del_edit_permit = ( $ff->created_by == get_current_user_id() ) ? TRUE : FALSE;
            $user            = get_user_by( 'id', $ff->created_by );

            if ( $ff->is_dir == 1 ) {
                $folder = array(
                    'id'         => $ff->id,
                    'name'       => $ff->dir_name,
                    'parent'     => $ff->parent_id,
                    'private'    => $ff->private,
                    'created_by' => $ff->created_by,
                    'permission' => $del_edit_permit,
                );
                array_push( $pro_folder, $folder );
            } else {
                $title         = '';
                $content       = '';
                $doclink       = '';
                $comment_count = 0;
                $attac_data    = array();
                $file_url      = '';
                $thumb_url     = '';
                $class         = '';
                $content_type  = '';
                $post_id       = $ff->post_id;
                if ( $ff->type == 'attach' ) {

                    $file     = $comment_obj->get_file( $ff->attachment_id );
                    $file_url = sprintf( '%s&file_id=%d&project_id=%d', $base_image_url, $file['id'], $project_id );
                    if ( $file['type'] == 'image' ) {
                        $thumb_url    = sprintf( '%s&file_id=%d&project_id=%d&type=thumb', $base_image_url, $file['id'], $project_id );
                        $class        = 'cpm-colorbox-img';
                        $content_type = 'image';
                    } else {
                        $thumb_url    = $file['thumb'];
                        $class        = '';
                        $content_type = 'file';
                    }
                    $title   = $file['name'];
                    $post_id = $ff->attachment_id;
                } else {
                    $post          = get_post( $post_id );
                    $title         = $post->post_title;
                    $content       = $post->post_content;
                    $doclink       = $post->post_excerpt;
                    $comment_count = $post->comment_count;
                    $class         = '';
                    $attachments   = get_posts( array(
                        'post_type'      => 'attachment',
                        'posts_per_page' => -1,
                        'post_parent'    => $ff->post_id,
                        'exclude'        => get_post_thumbnail_id()
                    ) );

                    if ( $attachments ) {
                        foreach ( $attachments as $attachment ) {
                            $attach       = $comment_obj->get_file( $attachment->ID );
                            $attac_data[] = $attach;
                        }
                    }
                }
                $comments = '';

				// PATCHED! FILES Multibyte strings!!!!
                //$sname    = (strlen( $title ) > $this->_files_name_show) ? substr( $title, 0, $this->_files_name_show ) . "..." : $title;
                $sname    = (mb_strlen( $title ) > $this->_files_name_show) ? mb_substr( $title, 0, $this->_files_name_show ) . "..." : $title;

                $file_obj = array(
                    'id'            => $ff->id,
                    'attachment_id' => $ff->attachment_id,
                    'parent'        => $ff->parent_id,
                    'private'       => $ff->private,
                    'thumb'         => $thumb_url,
                    'file_url'      => $file_url,
                    'css_class'     => $class,
                    'name'          => $sname,
                    'full_name'     => $title,
                    'content'       => $content,
                    'content_type'  => $content_type,
                    'doclink'       => $doclink,
                    'attachment'    => $attac_data,
                    'comments'      => $comments,
                    'comment_count' => $comment_count,
                    'type'          => $ff->type,
                    'post_id'       => $post_id,
                    'created_by'    => $ff->created_by,
                    'created_name'  => $user->display_name,
                    'created_at'    => cpm_get_date_without_html( $ff->created_at, true ),
                    'permission'    => $del_edit_permit,
                );
                array_push( $pro_files, $file_obj );
            }
        }

        if ( $parent != 0 ) {
            $sqlcf  = " SELECT * FROM $table WHERE id = $parent  ";
            $cfinfo = $wpdb->get_row( $sqlcf );
            $backto = intval( $cfinfo->parent_id );
        }
        if ( $parent == 0 ) {
            $org_doc = $this->get_attach_other_doc( $project_id, 0 );
            if ( $org_doc ) {
                $pro_files = array_merge( $pro_files, $org_doc );
            }
        }
        if ( ! empty( $pro_folder ) ) {
            // Get Current Folder Info
            $response['folder_list'] = $pro_folder;
        }
		
        if ( ! empty( $pro_files ) ) {
            // Get Current Folder Info
			//file_put_contents ('/var/www/ivannikitin.com/www/wp-content/pro_files.log', var_export($pro_files, true));
            $response['file_list'] = $pro_files;
        }

        $response['file_offset'] = $this->_files_per_page ;
        $response['current_folder'] = $parent;
        $response['backto']         = $backto;
		
        $json =  json_encode( $response );
		//file_put_contents ('/var/www/ivannikitin.com/www/wp-content/response.log', var_export($response, true));
		//file_put_contents ('/var/www/ivannikitin.com/www/wp-content/response-json.log', var_export($json, true));

		echo $json;
        exit();
    }

    function get_more_files(){
        check_ajax_referer( 'cpm_nonce' );
        $base_image_url      = admin_url( 'admin-ajax.php?action=cpm_file_get' );
        $posted              = $_POST;
        $project_id          = isset( $posted['project_id'] ) ? intval( $posted['project_id'] ) : 0;
        $offset              = isset( $posted['offset'] ) ? intval( $posted['offset'] ) : 0;
        $response['success'] = TRUE;

        $response['file_list'] = $this->get_attach_other_doc( $project_id, $offset );

        $response['file_offset'] = $offset+$this->_files_per_page ;

        echo json_encode( $response );

        exit() ;
    }

    function get_attach_other_doc( $project_id, $offset = 0 ) {
        $args = array(
            'post_type'   => 'attachment',
            'meta_key'    => '_project',
            'meta_value'  => $project_id,
            'offset'      => $offset,
            'numberposts' => $this->_files_per_page,
        );

        $attachments    = get_posts( $args );
        $base_image_url = admin_url( 'admin-ajax.php?action=cpm_file_get' );
        $doc_list       = array();

        if ( $attachments ) {
            foreach ( $attachments as $attachment ) {
                $file      = CPM_Comment::getInstance()->get_file( $attachment->ID );
                $topic_url = '#';

                if ( ! $attachment->post_parent ) {
                    $parent_id = get_post_meta( $attachment->ID, '_parent', true );
                    $parent    = get_post( $parent_id );
                } else {
                    $parent = get_post( $attachment->post_parent );
                }
                $post_type_object = get_post_type_object( $parent->post_type );


                if ( 'cpm_task' == $parent->post_type ) {
                    $is_private = get_post_meta( $attachment->post_parent, '_task_privacy', true );

                    if ( ! cpm_user_can_access_file( $project_id, 'todo_view_private', $is_private ) ) {
                        continue;
                    }

                    $task_list = get_post( $parent->post_parent );
                    $topic_url = cpm_url_single_task( $project_id, $task_list->ID, $parent->ID );
                } else if ( 'cpm_task_list' == $parent->post_type ) {
                    $is_private = get_post_meta( $attachment->post_parent, '_tasklist_privacy', true );

                    if ( ! cpm_user_can_access_file( $project_id, 'todolist_view_private', $is_private ) ) {
                        continue;
                    }

                    $topic_url = cpm_url_single_tasklist( $project_id, $parent->ID );
                } else if ( $parent->post_type == 'cpm_message' ) {
                    $is_private = get_post_meta( $attachment->post_parent, '_message_privacy', true );

                    if ( ! cpm_user_can_access_file( $project_id, 'msg_view_private', $is_private ) ) {
                        continue;
                    }

                    $topic_url = cpm_url_single_message( $project_id, $parent->ID );
                }


                $file_url     = sprintf( '%s&file_id=%d&project_id=%d', $base_image_url, $file['id'], $project_id );
                $content_type = '';
                if ( $file['type'] == 'image' ) {
                    $thumb_url    = sprintf( '%s&file_id=%d&project_id=%d&type=thumb', $base_image_url, $file['id'], $project_id );
                    $class        = 'cpm-colorbox-img';
                    $content_type = 'image';
                } else {
                    $thumb_url    = $file['thumb'];
                    $class        = '';
                    $content_type = 'file';
                }
                $thumb_url = apply_filters( 'cpm_attachment_url_thum', $thumb_url, $project_id, $file['id'] );
                $file_url  = apply_filters( 'cpm_attachment_url', $file_url, $project_id, $file['id'] );

                $sname   = (mb_strlen( $file['name'] ) > $this->_files_name_show) ? mb_substr( $file['name'], 0, $this->_files_name_show ) . "..." : $file['name'];
                $doc_obj = array(
                    'id'            => '',
                    'attachment_id' => '',
                    'parent'        => '0',
                    'private'       => '',
                    'thumb'         => $thumb_url,
                    'file_url'      => $file_url,
                    'topic_url'     => $topic_url,
                    'attach_text'   => __( 'Attached to ', 'cpm' ) . "<a href='{$topic_url}'>{$post_type_object->labels->singular_name}</a> " . __( " by ", 'cpm' ) . cpm_url_user( $attachment->post_author ),
                    'css_class'     => $class,
                    'full_name'     => $file['name'],
                    'name'          => $sname,
                    'content'       => '',
                    'content_type'  => $content_type,
                    'doclink'       => '',
                    'attachment'    => '',
                    'comments'      => '',
                    'comment_count' => get_comments_number( $parent->ID ),
                    'type'          => 'regular_doc_' . $file['type'],
                    'post_id'       => $attachment->ID,
                    'created_by'    => '',
                    'created_name'  => '',
                    'created_at'    => '',
                    'permission'    => '',
                );
                array_push( $doc_list, $doc_obj );
            }
        }
        return $doc_list;
    }

    function get_folder_path() {
        check_ajax_referer( 'cpm_nonce' );

        $posted              = $_POST;
        $project_id          = isset( $posted['project_id'] ) ? intval( $posted['project_id'] ) : 0;
        $fid                 = isset( $posted['fid'] ) ? intval( $posted['fid'] ) : 0;
        $response['success'] = TRUE;
        $data                = array();
        $fd                  = array();
        if ( $fid != 0 ) {
            $data = $this->get_path( $fid, $data );
            $data = array_reverse( $data );
        }

        $response['list'] = $data;
        echo json_encode( $response );
        exit();
    }

    function get_path( $fid, &$data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'cpm_file_relationship';
        $query = "SELECT * FROM $table WHERE id=" . $fid;
        $dir   = $wpdb->get_row( $query );

        if ( null === $dir OR $dir->parent_id === 0 ) {
            return $data;
        } else {
            $data[] = $dir;
            $this->get_path( $dir->parent_id, $data );
            return $data;
        }
    }

    function getproattachement( $project_id ) {
        $args = array(
            'post_type'   => 'attachment',
            'meta_key'    => '_project_uploaded',
            'meta_value'  => $project_id,
            'numberposts' => -1,
        );

        $all_posts['attachment'] = get_posts( $args );
        //var_dump($pro_attachments) ;

        $args = array(
            'post_type'   => 'cpm_docs',
            'meta_key'    => '_project_uploaded',
            'meta_value'  => $project_id,
            'numberposts' => -1,
        );

        $all_posts['docs'] = get_posts( $args );


        // $all_posts = array_merge( $pro_attachments, $pro_docs );
        // $post_ids = wp_list_pluck( $all_posts, 'ID' );

        return $all_posts;
    }

    function cpm_show_file_propart( $project_id ) {
        require_once CPM_PRO_PATH . '/views/files/index.php';
    }

    function files_scripts() {
        if ( isset( $_GET['tab'] ) AND $_GET['tab'] == 'files' ) {
            wp_enqueue_script( 'cpm_pro_files', plugins_url( '../assets/js/build.js', __FILE__ ) );
            wp_enqueue_style( 'cpm_pro_files', plugins_url( '../assets/css/files.css', __FILE__ ) );
        }
    }

    function uploded_media( $project_id ) {
        require_once CPM_PRO_PATH . '/views/files/filelist.php';
    }

    function file_new_uplaod() {
        check_ajax_referer( 'cpm_nonce' );
        $base_image_url      = admin_url( 'admin-ajax.php?action=cpm_file_get' );
        $posted              = $_POST;
        $files               = array();
        $project_id          = isset( $posted['project_id'] ) ? intval( $posted['project_id'] ) : 0;
        $privacy             = isset( $posted['privacy'] ) ? 'yes' : 'no';
        $parent              = isset( $posted['parent'] ) ? intval( $posted['parent'] ) : 0;
        $response['success'] = FALSE;

        global $wpdb;
        $table = $wpdb->prefix . 'cpm_file_relationship';

        if ( cpm_user_can_access( $project_id, 'upload_file_doc' ) ) {
            if ( isset( $posted['cpm_attachment'] ) ) {
                $files       = $posted['cpm_attachment'];
                $files_array = array();
                foreach ( $files as $file_id ) {
                    $file_obj   = array();
                    $created_by = get_current_user_id();
                    $data       = array(
                        'project_id'    => $project_id,
                        'parent_id'     => $parent,
                        'is_dir'        => '0',
                        'type'          => 'attach',
                        'private'       => $privacy,
                        'attachment_id' => $file_id,
                        'created_by'    => $created_by,
                        'created_at'    => date( "Y-m-d H:i:s" )
                    );

                    if ( $wpdb->insert( $table, $data ) ) {
                        $id       = $wpdb->insert_id;
                        $file     = CPM_Comment::getInstance()->get_file( $file_id );
                        $file_url = sprintf( '%s&file_id=%d&project_id=%d', $base_image_url, $file['id'], $project_id );
                        if ( $file['type'] == 'image' ) {
                            $thumb_url = sprintf( '%s&file_id=%d&project_id=%d&type=thumb', $base_image_url, $file['id'], $project_id );
                            $class     = 'cpm-colorbox-img';
                        } else {
                            $thumb_url = $file['thumb'];
                            $class     = '';
                        }
                        $sname    = (mb_strlen( $file['name'] ) > $this->_files_name_show) ? mb_substr( $file['name'], 0, $this->_files_name_show ) . "..." : $file['name'];
                        $file_obj = array(
                            'id'            => $id,
                            'full_name'     => $file['name'],
                            'name'          => $sname,
                            'attachment_id' => $file_id,
                            'post_id'       => $file_id,
                            'parent'        => $parent,
                            'private'       => $privacy,
                            'thumb'         => $thumb_url,
                            'file_url'      => $file_url,
                            'type'          => 'attach',
                            'css_class'     => $class,
                            'created_by'    => $created_by,
                            'permission'    => TRUE,
                        );
                        array_push( $files_array, $file_obj );

                        $response['success'] = TRUE;
                    }
                }

                $response['file_list'] = $files_array;
            }
        }

        echo json_encode( $response );
        exit();
    }

    /**
     * Create a new folder
     * @global int id (cretaed folder if)
     */
    function new_folder_create() {
        check_ajax_referer( 'cpm_nonce' );
        $posted = $_POST;

        $project_id  = isset( $posted['project_id'] ) ? intval( $posted['project_id'] ) : 0;
        $folder_name = isset( $posted['name'] ) ? sanitize_text_field( $posted['name'] ) : '';
        $privacy     = isset( $posted['privacy'] ) ? 'yes' : 'no';
        $parent      = isset( $posted['parent'] ) ? intval( $posted['parent'] ) : 0;

        $response['success'] = FALSE;

        if ( cpm_user_can_access( $project_id, 'upload_file_doc' ) ) {
            if ( isset( $posted ) ) {
                if ( $this->check_existing_folder_by_parent( $folder_name, $parent, $project_id ) ) {
                    $response['success'] = FALSE;
                    $response['error']   = __( 'Folder name already exist! Please check again.', 'cpm' );
                } else {
                    global $wpdb;
                    $table = $wpdb->prefix . 'cpm_file_relationship';


                    $data = array(
                        'project_id' => $project_id,
                        'dir_name'   => $folder_name,
                        'parent_id'  => $parent,
                        'is_dir'     => '1',
                        'private'    => $privacy,
                        'created_by' => get_current_user_id(),
                        'created_at' => date( "Y-m-d H:i:s" )
                    );

                    if ( $wpdb->insert( $table, $data ) ) {
                        $response['id']      = $wpdb->insert_id;
                        $response['private'] = $privacy;
                        $response['success'] = TRUE;
                    }
                    // $sql = "ALTER TABLE {$table} ADD CONSTRAINT child_rel FOREIGN KEY (`parent_id`) REFERENCES $table (`id`) ON DELETE CASCADE ";
                }
            }
        } else {
            $response['error'] = __( 'You have not access to creat folder.', 'cpm' );
        }
        echo json_encode( $response );
        exit();
    }

    /**
     * Update a existing folder
     * @global type $wpdb
     */
    function folder_rename() {
        check_ajax_referer( 'cpm_nonce' );
        $posted = $_POST;

        $project_id          = isset( $posted['project_id'] ) ? intval( $posted['project_id'] ) : 0;
        $folder_name         = isset( $posted['name'] ) ? sanitize_text_field( $posted['name'] ) : '';
        $parent              = isset( $posted['parent'] ) ? intval( $posted['parent'] ) : 0;
        $folder_id           = isset( $posted['folderid'] ) ? intval( $posted['folderid'] ) : 0;
        // $privacy     = isset( $posted['privacy'] ) ? 'yes' : 'no';
        $response['success'] = FALSE;
        if ( cpm_user_can_access( $project_id, 'upload_file_doc' ) ) {
            if ( isset( $posted ) ) {
                if ( ! $this->check_existing_folder_by_parent( $folder_name, $parent, $project_id ) ) {
                    $data  = array(
                        'dir_name' => $posted['name'],
                    );
                    global $wpdb;
                    $table = $wpdb->prefix . 'cpm_file_relationship';
                    if ( $wpdb->update( $table, $data, array( 'id' => $folder_id ) ) ) {
                        $response['success'] = TRUE;
                    }
                } else {
                    $response['success'] = FALSE;
                    $response['error']   = __( 'Folder name already exist! Please check again.', 'cpm' );
                }
            }
        }

        echo json_encode( $response );
        exit();
    }

    function change_ff_privacy() {
        check_ajax_referer( 'cpm_nonce' );
        $posted     = $_POST;
        $project_id = isset( $posted['project_id'] ) ? intval( $posted['project_id'] ) : 0;
        $parent     = isset( $posted['parent'] ) ? intval( $posted['parent'] ) : 0;
        $attach_id  = isset( $posted['attach_id'] ) ? intval( $posted['attach_id'] ) : 0;
        $privacy    = ($posted['cprivacy'] === 'yes') ? 'no' : 'yes';

        $response['success'] = FALSE;

        global $wpdb;
        $table         = $wpdb->prefix . 'cpm_file_relationship';
        $file_relation = $wpdb->get_row( "SELECT * FROM $table WHERE id = $attach_id " );

        if ( cpm_user_can_access( $project_id, 'upload_file_doc' ) ) {
            if ( $file_relation->created_by == get_current_user_id() ) {
                if ( isset( $posted ) ) {
                    $data = array(
                        'private' => $privacy,
                    );
                    if ( $wpdb->update( $table, $data, array( 'id' => $attach_id ) ) ) {
                        $response['privacy'] = $privacy;
                        $response['success'] = TRUE;
                    }
                }
            }
        }

        echo json_encode( $response );
        exit();
    }

    function folder_delete() {
        check_ajax_referer( 'cpm_nonce' );
        $posted    = $_POST;
        $folder_id = isset( $posted['folderid'] ) ? intval( $posted['folderid'] ) : 0;

        $response['success'] = FALSE;

        global $wpdb;
        $table = $wpdb->prefix . 'cpm_file_relationship';
        if ( $wpdb->delete( $table, array( 'id' => $folder_id ) ) ) {
            $response['success'] = TRUE;
        } else {
            $response['error'] = __( 'There is an error while delete, please try again!', 'cpm' );
        }

        echo json_encode( $response );
        exit();
    }

    function check_existing_folder_by_parent( $folder, $parent, $project_id ) {

        global $wpdb;
        $table = $wpdb->prefix . 'cpm_file_relationship';

        $mylink = $wpdb->get_row( "SELECT * FROM $table WHERE dir_name = '$folder' AND  parent_id = $parent AND project_id = $project_id " );


        if ( empty( $mylink ) )
            return false;
        else
            return true;
    }

    function delete_uploded_file() {
        check_ajax_referer( 'cpm_nonce' );

        $posted              = $_POST;
        $force               = TRUE;
        $file_id             = isset( $posted['file_id'] ) ? intval( $posted['file_id'] ) : 0;
        $project_id          = isset( $posted['project_id'] ) ? intval( $posted['project_id'] ) : 0;
        $response['success'] = FALSE;

        global $wpdb;
        $table         = $wpdb->prefix . 'cpm_file_relationship';
        $file_relation = $wpdb->get_row( "SELECT * FROM $table WHERE id = $file_id " );

        if ( $file_relation->type == 'doc' OR $file_relation->type == 'google_doc'  ) {

            if ( get_current_user_id() == $file_relation->created_by ) {
                if ( wp_delete_post( $file_relation->post_id, $force ) ) {
                    $wpdb->delete( $table, array( 'id' => $file_id ) );
                    $response['success'] = TRUE;
                    do_action( 'cpm_delete_attachment', $file_id, $force );
                } else {
                    $response['error'] = __( 'There is an error while delete, please try again!', 'cpm' );
                }
            } else {
                $response['error'] = __( 'Permission Problem', 'cpm' );
            }
        }
        if ( $file_relation->type == 'attach' ) {

            $files = get_post( $file_relation->attachment_id );
            if ( get_current_user_id() == $files->post_author ) {
                if ( wp_delete_attachment( $files->ID, $force ) ) {
                    $wpdb->delete( $table, array( 'id' => $file_id ) );
                    $response['success'] = TRUE;
                    do_action( 'cpm_delete_attachment', $file_id, $force );
                } else {
                    $response['error'] = __( 'There is an error while delete, please try again!', 'cpm' );
                }
            } else {
                $response['error'] = __( 'Permission Problem', 'cpm' );
            }
        }

        echo json_encode( $response );
        exit();
    }

    function change_file_privacy() {
        check_ajax_referer( 'cpm_nonce' );
        $posted              = $_POST;
        $file_id             = isset( $posted['file_id'] ) ? intval( $posted['file_id'] ) : 0;
        $project_id          = isset( $posted['project_id'] ) ? intval( $posted['project_id'] ) : 0;
        $privacy             = isset( $posted['current_privacy'] ) ? $posted['current_privacy'] : 'no';
        $new_privacy         = ( $privacy == 'yes' ) ? 'no' : 'yes';
        $new_css_class       = ( $privacy == 'yes' ) ? 'dashicons-before dashicons-unlock' : 'dashicons-before dashicons-lock';
        $response['success'] = FALSE;
        $files               = get_post( $file_id );

        if ( get_current_user_id() == $files->post_author ) {
            $up = update_post_meta( $file_id, '_files_privacy', $new_privacy );

            if ( $up ) {
                $response['success']     = TRUE;
                $response['new_privacy'] = $new_privacy;
                $response['css_class']   = $new_css_class;
            }
        }
        echo json_encode( $response );
        exit();
    }

    function create_new_doc() {
        check_ajax_referer( 'cpm_nonce' );
        $posted              = $_POST;
        $files               = array();
        $project_id          = isset( $posted['project_id'] ) ? intval( $posted['project_id'] ) : 0;
        $title               = isset( $posted['title'] ) ? $posted['title'] : '';
        $content             = isset( $posted['description'] ) ? $posted['description'] : '';
        $privacy             = isset( $posted['private'] ) ? 'yes' : 'no';
        $parent              = isset( $posted['parent'] ) ? intval( $posted['parent'] ) : 0;
        $response['success'] = FALSE;
        global $wpdb;
        $table               = $wpdb->prefix . 'cpm_file_relationship';

        if ( cpm_user_can_access( $project_id, 'upload_file_doc' ) ) {
            $data_doc = array(
                'post_parent'  => $project_id,
                'post_title'   => $title,
                'post_content' => $content,
                'post_type'    => 'cpm_docs',
                'post_status'  => 'publish'
            );

            $doc_id = wp_insert_post( $data_doc );

            if ( $doc_id ) {
                update_post_meta( $doc_id, '_project_uploaded', $project_id );
                update_post_meta( $doc_id, '_doc_type', '_custom_doc' );
                $table      = $wpdb->prefix . 'cpm_file_relationship';
                $created_by = get_current_user_id();
                $user       = get_user_by( 'id', $created_by );

                $data = array(
                    'project_id' => $project_id,
                    'parent_id'  => $parent,
                    'is_dir'     => '0',
                    'private'    => $privacy,
                    'post_id'    => $doc_id,
                    'type'       => 'doc',
                    'created_by' => $created_by,
                    'created_at' => date( "Y-m-d H:i:s" )
                );

                $ins = $wpdb->insert( $table, $data );

                $rid        = $wpdb->insert_id;
                $attac_data = array();
                if ( isset( $posted['cpm_attachment'] ) ) {
                    $files = $posted['cpm_attachment'];

                    foreach ( $files as $file_id ) {
                        wp_update_post( array(
                            'ID'          => $file_id,
                            'post_parent' => $doc_id
                        ) );
                        $comment_obj  = new CPM_Comment();
                        $attach       = $comment_obj->get_file( $file_id );
                        $attac_data[] = $attach;
                    }
                }
                $sname                = (mb_strlen( $title ) > $this->_files_name_show) ? mb_substr( $title, 0, $this->_files_name_show ) . "..." : $title;
                $file_obj             = array(
                    'id'            => $rid,
                    'attachment_id' => '',
                    'parent'        => $parent,
                    'private'       => $privacy,
                    'thumb'         => '',
                    'file_url'      => '',
                    'css_class'     => '',
                    'full_name'     => $title,
                    'name'          => $sname,
                    'content'       => $content,
                    'attachment'    => $attac_data,
                    'comment_count' => 0,
                    'type'          => 'doc',
                    'post_id'       => $doc_id,
                    'created_by'    => $created_by,
                    'created_name'  => $user->display_name,
                    'created_at'    => date( "Y-m-d H:i:s" ),
                    'permission'    => true,
                );
                $response['document'] = $file_obj;


                $response['success'] = TRUE;
            }
        }

        echo json_encode( $response );

        exit();
    }

    function create_newgoogle_doc() {
        check_ajax_referer( 'cpm_nonce' );
        $posted              = $_POST;
        $files               = array();
        $project_id          = isset( $posted['project_id'] ) ? intval( $posted['project_id'] ) : 0;
        $title               = isset( $posted['title'] ) ? $posted['title'] : '';
        $doc_link            = isset( $posted['doclink'] ) ? $posted['doclink'] : '';
        $content             = isset( $posted['description'] ) ? $posted['description'] : '';
        $privacy             = isset( $posted['private'] ) ? 'yes' : 'no';
        $parent              = isset( $posted['parent'] ) ? intval( $posted['parent'] ) : 0;
        $response['success'] = FALSE;
        global $wpdb;
        $table               = $wpdb->prefix . 'cpm_file_relationship';

        if ( cpm_user_can_access( $project_id, 'upload_file_doc' ) ) {
            $data_doc = array(
                'post_parent'  => $project_id,
                'post_title'   => $title,
                'post_excerpt' => $doc_link,
                'post_content' => $content,
                'post_type'    => 'cpm_docs',
                'post_status'  => 'publish'
            );

            $doc_id = wp_insert_post( $data_doc );

            if ( $doc_id ) {
                update_post_meta( $doc_id, '_project_uploaded', $project_id );
                update_post_meta( $doc_id, '_doc_type', '_google_doc' );
                $table      = $wpdb->prefix . 'cpm_file_relationship';
                $created_by = get_current_user_id();
                $user       = get_user_by( 'id', $created_by );

                $data = array(
                    'project_id' => $project_id,
                    'parent_id'  => $parent,
                    'is_dir'     => '0',
                    'private'    => $privacy,
                    'post_id'    => $doc_id,
                    'type'       => 'google_doc',
                    'created_by' => $created_by,
                    'created_at' => date( "Y-m-d H:i:s" )
                );

                $ins = $wpdb->insert( $table, $data );

                $rid        = $wpdb->insert_id;
                $attac_data = array();

                $sname                = (mb_strlen( $title ) > $this->_files_name_show) ? mb_substr( $title, 0, $this->_files_name_show ) . "..." : $title;
                $file_obj             = array(
                    'id'            => $rid,
                    'attachment_id' => '',
                    'parent'        => $parent,
                    'private'       => $privacy,
                    'thumb'         => '',
                    'file_url'      => '',
                    'css_class'     => '',
                    'full_name'     => $title,
                    'name'          => $sname,
                    'content'       => $content,
                    'doclink'       => $doc_link,
                    'attachment'    => $attac_data,
                    'comment_count' => 0,
                    'type'          => 'google_doc',
                    'post_id'       => $doc_id,
                    'created_by'    => $created_by,
                    'created_name'  => $user->display_name,
                    'created_at'    => date( "Y-m-d H:i:s" ),
                    'permission'    => true,
                );
                $response['document'] = $file_obj;


                $response['success'] = TRUE;
            }
        }

        echo json_encode( $response );

        exit();
    }

    function doc_update() {
        check_ajax_referer( 'cpm_nonce' );
        $posted              = $_POST;
        $files               = array();
        $project_id          = isset( $posted['project_id'] ) ? intval( $posted['project_id'] ) : 0;
        $title               = isset( $posted['name'] ) ? $posted['name'] : '';
        $doc_link            = isset( $posted['doclink'] ) ? $posted['doclink'] : '';
        $content             = isset( $posted['description'] ) ? $posted['description'] : '';
        $privacy             = isset( $posted['private'] ) ? 'yes' : 'no';
        $doc_id              = isset( $posted['doc_id'] ) ? intval( $posted['doc_id'] ) : 0;
        $response['success'] = FALSE;
        global $wpdb;
        $table               = $wpdb->prefix . 'cpm_file_relationship';


        $data_doc = array(
            'ID'           => $doc_id,
            'post_title'   => $title,
            'post_content' => $content,
            'post_excerpt' => $doc_link,
        );

        $doc_id = wp_update_post( $data_doc );

        if ( $doc_id ) {

            $table      = $wpdb->prefix . 'cpm_file_relationship';
            $created_by = get_current_user_id();
            $user       = get_user_by( 'id', $created_by );

            $data = array(
                'private' => $privacy,
            );

            $ins = $wpdb->update( $table, $data, array( 'post_id' => $doc_id ) );

            $attac_data = array();
            if ( isset( $posted['cpm_attachment'] ) ) {
                $files = $posted['cpm_attachment'];

                foreach ( $files as $file_id ) {
                    wp_update_post( array(
                        'ID'          => $file_id,
                        'post_parent' => $doc_id
                    ) );
                    $comment_obj  = new CPM_Comment();
                    $attach       = $comment_obj->get_file( $file_id );
                    $attac_data[] = $attach;
                }
            }
            $sname    = (mb_strlen( $title ) > $this->_files_name_show) ? mb_substr( $title, 0, $this->_files_name_show ) . "..." : $title;
            $file_obj             = array(
                'private'    => $privacy,
                'name'       => $sname,
                'full_name'  => $title,
                'content'    => $content,
                'doclink'    => $doc_link,
                'attachment' => $attac_data,
                'permission' => true,
            );
            $response['document'] = $file_obj;


            $response['success'] = TRUE;
        }


        echo json_encode( $response );

        exit();
    }

    function get_doc_comments() {
        check_ajax_referer( 'cpm_nonce' );
        $posted               = $_POST;
        $post_id              = isset( $posted['doc_id'] ) ? intval( $posted['doc_id'] ) : 0;
        $comment_obj          = new CPM_Comment();
        $comments             = $comment_obj->get_comments( $post_id );
        $response['success']  = TRUE;
        $response['comments'] = $comments;
        echo json_encode( $response );

        exit();
    }

    function cpm_pro_create_comment() {
        check_ajax_referer( 'cpm_nonce' );
        $posted              = $_POST;
        $files               = array();
        $response['success'] = FALSE;
        $text                = trim( $posted['description'] );
        $parent_id           = isset( $posted['parent_id'] ) ? intval( $posted['parent_id'] ) : 0;
        $project_id          = isset( $posted['project_id'] ) ? intval( $posted['project_id'] ) : 0;
        $comment_obj         = new CPM_Comment();
        if ( isset( $posted['cpm_attachment'] ) ) {
            $files = $posted['cpm_attachment'];
        }

        $user_id = get_current_user_id();
        $user    = get_user_by( 'id', $user_id );

        $data = array(
            'comment_post_ID'      => $parent_id,
            'comment_content'      => $text,
            'user_id'              => get_current_user_id(),
            'comment_author_IP'    => preg_replace( '/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR'] ),
            'comment_agent'        => mb_substr( $_SERVER['HTTP_USER_AGENT'], 0, $this->_files_name_show4 ),
            'comment_author'       => $user->display_name,
            'comment_author_email' => $user->user_email
        );

        $comment_id = wp_insert_comment( $data );

        if ( $comment_id ) {
            add_comment_meta( $comment_id, '_files', $files );

            do_action( 'cpm_comment_new', $comment_id, $_POST['project_id'], $data );

            $response['success'] = TRUE;
            $comment             = $comment_obj->get( $comment_id );
            //  $comment['avata'] = get_avatar($comment->comment_author_email) ;
            $response['comment'] = $comment;
        }
        echo json_encode( $response );

        exit();
    }

    function cpm_pro_doc_revision() {
        check_ajax_referer( 'cpm_nonce' );
        $posted              = $_POST;
        $response['success'] = FALSE;
        $doc_id              = isset( $posted['doc_id'] ) ? intval( $posted['doc_id'] ) : 0;


        $comment_obj   = new CPM_Comment();
        $revisions     = array();
        $attac_data    = array();
        $revision_list = wp_get_post_revisions( $doc_id );

        if ( $revision_list ) {
            foreach ( $revision_list as $rev ) {
                //  var_dump($rev) ;
                $attac_data  = array();
                $attachments = get_posts( array(
                    'post_type'      => 'attachment',
                    'posts_per_page' => -1,
                    'post_parent'    => $doc_id,
                    'exclude'        => get_post_thumbnail_id()
                ) );

                if ( $attachments ) {
                    foreach ( $attachments as $attachment ) {
                        $attach       = $comment_obj->get_file( $attachment->ID );
                        $attac_data[] = $attach;
                    }
                }

                $del_edit_permit = ( $rev->created_by == get_current_user_id() ) ? TRUE : FALSE;
                $user            = get_user_by( 'id', $rev->post_author );
                $sname           = (mb_strlen( $rev->post_title ) > $this->_files_name_show) ? mb_substr( $rev->post_title, 0, $this->_files_name_show ) . "..." : $rev->post_title;
                $file_obj        = array(
                    'id'            => $rev->id,
                    'attachment_id' => $rev->attachment_id,
                    'parent'        => $rev->post_parent,
                    'private'       => '',
                    'thumb'         => '',
                    'file_url'      => '',
                    'css_class'     => '',
                    'full_name'     => $rev->post_title,
                    'name'          => $sname,
                    'content'       => $rev->post_content,
                    'attachment'    => $attac_data,
                    'comments'      => '',
                    'comment_count' => '',
                    'type'          => '',
                    'post_id'       => $rev->ID,
                    'created_by'    => $rev->post_author,
                    'created_name'  => $user->display_name,
                    'created_at'    => cpm_get_date_without_html( $rev->post_date, true ),
                    'permission'    => FALSE,
                );
                // var_dump($file_obj) ;
                array_push( $revisions, $file_obj );
            }
        }
        $response['revisions'] = $revisions;
        $response['success']   = TRUE;
        echo json_encode( $response );
        exit();
    }

}

//new CPM_Pro_Files();

function cpmprofile() {
    return CPM_Pro_Files::instance();
}

//cpm instance.
$profiles = cpmprofile();
