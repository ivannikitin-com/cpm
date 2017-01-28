<?php
cpm_get_email_header();
$task_data = cpm()->task->get_task( $task_id );
$due_date  = cpm_get_date( $task_data->due_date );
if ( ! empty( $due_date ) ) {
    $next_name = sprintf( '<em style="font-family: lato; color: #B3B3B3; ">%s</em>
                    <strong style="font-family: lato; color: #7e7e7e;">
                        <span style="padding-right: 5px;">%s</span>', __( 'Due Date ', 'cpm' ), $due_date );
} else {
    $next_name = '';
}

$tpbk   = CPM_URL . '/assets/images/tpbk.png';
$author = wp_get_current_user();
$linkto = cpm_get_option( 'email_url_link', 'cpm_mails' );

?>

<div style="width:600px;  background: #fff;">

    <div style="width: 600px;">
        <div style="background-image: url('<?php echo $tpbk; ?>'); background-repeat: no-repeat; height: 174px; width: 600px;">
            <div style="font-family: 'Lato', sans-serif; font-wight: bold; color: #fff; font-size: 30px; padding-top: 26px; text-align: center;">
<?php _e( 'New task has been assigned to you', 'cpm' ); ?>
            </div>
        </div>

        <div style="padding: 0 50px; text-align: justify; background-repeat: no-repeat;">
            <div style="margin: 40px 0 10px; margin-bottom: 20px;">
                <em style="font-family: lato; color: #B3B3B3;padding-right: 5px;"><?php _e( 'Created By', 'cpm' ); ?></em>
                <strong style="font-family: lato; color: #7e7e7e; padding-right: 10px;">
<?php echo $author->display_name; ?>
                </strong>
<?php echo $next_name; ?>
            </div>

            <div style="font-family: arial; font-size: 14px; line-height: 24px;">
                <span style="float: left; width: 459px;"><?php echo $data['post_title']; ?></span>
                <div style="clear: both;"></div>
            </div>

        </div>
        <center>
            <div style="padding: 18px; margin: 30px 0 45px; border-radius: 30px; background: #00b1e7; width: 171px;">

                <a href="<?php echo cpm_url_single_task( $project_id, $list_id, $task_id ); ?>" style="font-family: lato; font-size: 16px; text-decoration: none; color: #fff;">
<?php _e( 'View Task', 'cpm' ); ?>
                </a>

            </div>
        </center>
    </div>
<?php cpm_get_email_footer(); ?>
