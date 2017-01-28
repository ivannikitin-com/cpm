<?php
$check_satus = get_user_meta( $user->ID, '_user_daily_digets_status', true );
$check_satus = ( ! in_array( $check_satus, array('on', 'off' ) ) ) ? 'on' : $check_satus;
?>
<table class="form-table">
<tr>
	<th>
		<label for="tc_location"><?php _e( 'Daily Digest', 'cpm' ); ?></label>
	</th>
	<td>
		<label for="cpm_daily_digest_status">
			<input type="checkbox" id="cpm_daily_digest_status" <?php checked( 'on', $check_satus ); ?> name="cpm_daily_digets_status"  value="on"/>
			<span class="description"><?php _e('Enable project manager Daily Digest', 'cpm'); ?></span>
		</label>
	</td>
</tr>

</table>
<?php
$user_email_notification = get_user_meta( $user->ID, '_cpm_email_notification', true );
$user_email_notification = ( ! in_array( $user_email_notification, array('on', 'off' ) ) ) ? 'on' : $user_email_notification;         
?>
<table class="form-table">
    <tr>
        <th><?php _e( 'Email Notification', 'cpm' ); ?> </th>
        <td>
            <label for="cpm-email-notification">
                <input type="checkbox" value="on" <?php checked(  'on', $user_email_notification ); ?> id="cpm-email-notification" name="cpm_email_notification">
                <span class="description"><?php _e( 'Enable project manager email', 'cpm' ); ?></span></em>
            </label>
        </td>
    </tr>
</table>