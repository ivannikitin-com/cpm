<ul class="cpm_activity_list">
    <?php
    $activities = CPM_Pro_Task::getInstance()->get_user_activity( $user_id, array() );
    $total = CPM_Pro_Task::getInstance()->get_user_activity_total($user_id);


    if ( $activities ) {
        echo cpm_user_activity_html( $activities, $user_id );
    }
    ?>
</ul>

<?php if ( $total > count( $activities ) ) { ?>
    <a href="#" <?php cpm_data_attr( array('user_id' => $user_id, 'start' => count( $activities ) + 1, 'total' => $total) ); ?> class="button cpm-load-more-ua"><?php _e( 'Load More...', 'cpm' ); ?></a>
<?php } ?>
