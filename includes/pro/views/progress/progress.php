<?php
$projects = cpm()->project->get_projects();
unset( $projects['total_projects'] );
$projects_id = wp_list_pluck( $projects, 'ID' );

?>
<div id="cpm-progress-wrap">
    <h2><?php _e( 'Progress', 'cpm' ); ?></h2>
    <ul class="cpm-activity dash">
        <?php
        $count = cpm()->activity->get_projects_comment_count( $projects );

        $tatal_activity = 0;

    	$activities     = CPM_project::getInstance()->get_projects_activity( $projects_id, array() );

    	if ( $activities ) {
            echo cpm_projects_activity_html( $activities );
        } else {
            ?>
            <li><h2><?php _e( 'No Progress Found!', 'cpm' ); ?></h2></li>
            <?php
        }


        ?>
    </ul>
</div>

<?php if ( $count['approved'] > count( $activities ) ) { ?>
    <a href="#" <?php  cpm_data_attr( array( 'projects_id' => json_encode( $projects_id ), 'start' => count( $activities ), 'total' => $count['approved']) ); ?> class="button cpm-loads-more"><?php _e( 'Load More...', 'cpm' ); ?></a>
<?php }


