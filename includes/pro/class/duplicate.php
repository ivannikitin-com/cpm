<?php

class CPM_Pro_Duplicate {

    private static $_instance;

    public static function getInstance() {
        if ( !self::$_instance ) {
            self::$_instance = new CPM_Pro_Duplicate();
        }

        return self::$_instance;
    }

    function fatch_projcet_data( $project_id ) {

        $args = array(
            'post_parent' => $project_id,
            'post_type'   => array('cpm_message', 'cpm_task_list', 'cpm_milestone'),
            'post_status' => 'publish',
            'order'       => 'ASC',
            'orderby'     => 'ID',
            'numberposts' => -1
        );

        $prev_pro_data = get_children( $args );

        $prev_pro_data[$project_id] = get_post( $project_id );

        return $prev_pro_data;
    }

    function create_duplicate( $project_id ) {

        //Get all data post type project, message, task_list, milestone
        $prev_pro_data = $this->fatch_projcet_data( $project_id );

        $new_pro_arg = $this->fill_array( $prev_pro_data[$project_id] );

        //create duplicate new project
        $new_pro_id = $this->insert_duplicate( $new_pro_arg, $project_id );

        if ( !$new_pro_id ) {
            wp_send_json_error( 'Unknown Error', 'cpm' );
        }

        //remove project post type from data array
        unset( $prev_pro_data[$project_id] );

        foreach ($prev_pro_data as $prev_post_id => $post_obj) {
            if( $post_obj->post_type == 'cpm_milestone' ) {
                $args = $this->fill_array( $post_obj, $new_pro_id );

                //Insert message, task list and milestone
                $new_milestone_id[$post_obj->ID] = $this->insert_duplicate( $args, $post_obj->ID );

                unset( $prev_pro_data[$prev_post_id] );
            }
        }

        foreach ( $prev_pro_data as $prev_post_id => $post_obj ) {
            $args = $this->fill_array( $post_obj, $new_pro_id );

            $new_milestone_id = isset( $new_milestone_id ) ? $new_milestone_id : array();

            //Insert message, task list and milestone
            $id = $this->insert_duplicate( $args, $post_obj->ID, $new_milestone_id );



            //If post type task list then fatch task and insert duplicate
            if ( $post_obj->post_type == 'cpm_task_list' ) {

                $task = array(
                    'post_parent' => $post_obj->ID,
                    'post_type'   => 'cpm_task',
                    'post_status' => 'publish',
                    'order'       => 'ASC',
                    'orderby'     => 'ID'
                );

                $task_data = get_children( $task );

                $this->insert_duplicate_task( $task_data, $id );
            }
        }

        /**
         * @since 1.4
         */
        do_action( 'cpm_project_duplicate', $project_id, $new_pro_id );

        return $new_pro_id;
    }

    function fill_array( $post_obj, $new_post_id = '' ) {
        $args = array(
            'post_parent'    => $new_post_id,
            'comment_status' => $post_obj->comment_status,
            'ping_status'    => $post_obj->ping_status,
            'post_author'    => $post_obj->post_author,
            'post_content'   => $post_obj->post_content,
            'post_name'      => $post_obj->post_name,
            'post_status'    => 'publish',
            'post_title'     => $post_obj->post_title,
            'post_type'      => $post_obj->post_type,
        );

        return $args;
    }

    function insert_duplicate_task( $task_data, $new_task_list_id ) {

        foreach ($task_data as $pro_task_post_id => $post_obj) {
           $args = $this->fill_array( $post_obj, $new_task_list_id );
           $new_task_id =  $this->insert_duplicate( $args, $post_obj->ID );

           /**
           * @since 1.4
           */
           do_action( 'cpm_task_duplicate_after', $post_obj->ID, $new_task_id  );
        }
    }



    function insert_duplicate( $args, $project_id, $new_milestone_id=array() ) {
        global $wpdb;

        /*
         * insert the post by wp_insert_post() function
         */
        $new_post_id = wp_insert_post( $args );

        /*
         * get all current post terms ad set them to the new post draft
         */
        $taxonomies = get_object_taxonomies( $args['post_type'] ); // returns array of taxonomy names for post type, ex array("category", "post_tag");
        foreach ($taxonomies as $taxonomy) {
            $post_terms = wp_get_object_terms( $project_id, $taxonomy, array('fields' => 'slugs') );
            wp_set_object_terms( $new_post_id, $post_terms, $taxonomy, false );
        }

        /*
         * duplicate all post meta
         */
        $post_meta_infos = $wpdb->get_results( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$project_id" );

        if ( count( $post_meta_infos ) != 0 ) {
            $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";

            foreach ($post_meta_infos as $meta_info) {
                $meta_key = $meta_info->meta_key;
                $meta_value = addslashes( $meta_info->meta_value );
                if( $meta_key == '_milestone' && ( $args['post_type'] == 'cpm_task_list' || $args['post_type'] == 'cpm_message' ) ) {

                    $meta_info->meta_value = isset( $new_milestone_id[$meta_info->meta_value] ) ? $new_milestone_id[$meta_info->meta_value] : '';
                    $meta_value = addslashes( $meta_info->meta_value );
                }
                $sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";
            }

            $sql_query.= implode( " UNION ALL ", $sql_query_sel );
            $wpdb->query( $sql_query );
        }

        if( $args['post_type'] == 'cpm_project' ) {

            $get_all_user = CPM_Project::getInstance()->get_users( $project_id );

            if ( is_array( $get_all_user ) && count( $get_all_user ) ) {
                foreach ($get_all_user as $user_id => $role_meta) {
                    CPM_Project::getInstance()->insert_user( $new_post_id, $user_id, $role_meta['role'] );
                }
            }
        }

        return $new_post_id;
    }

}