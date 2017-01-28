<?php

/**
 * Admin options handler class
 *
 * @since 0.4
 * @author Tareq Hasan <tareq@wedevs.com>
 */
class CPM_Pro_Admin {

    function __construct() {

        add_action( 'profile_update', array( $this, 'update_user_profile' ), 10, 2 );
        add_action('show_user_profile', array( $this, 'user_parofile' ) );
        add_action('edit_user_profile', array( $this, 'user_parofile' ) );
    }

    /**
     * User prfile update
     *
     * @since 1.1
     *
     * @return type
     */
    function update_user_profile( $user_id, $old_user_data ) {

        $daily_digest_active_status = isset( $_POST['cpm_daily_digets_status'] ) ? $_POST['cpm_daily_digets_status'] : 'off';
        $email_notification_active_status = isset( $_POST['cpm_email_notification'] ) ? $_POST['cpm_email_notification'] : 'off';

        if ( is_admin() && current_user_can( 'edit_user' ) ) {
            update_user_meta( $user_id, '_user_daily_digets_status', $daily_digest_active_status );
            update_user_meta( $user_id, '_cpm_email_notification', $email_notification_active_status );
        }
    }

    /**
     * User profile custom field add
     *
     * @since 1.1
     *
     * @return type
     */
    function user_parofile( $user ) {
        if ( !is_admin() ) {
            return;
        }

        include CPM_PRO_PATH . '/views/admin/user-profile.php';
    }
}