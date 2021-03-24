<?php

if ( $_SERVER['REQUEST_METHOD'] == 'POST' )
	do_action('cpm_project_settings_update');

$updated = false;
if ( isset( $_POST['sub_seettings'] ) ) {
    unset( $_POST['sub_seettings'] );

    $updated = true;

    update_post_meta( $project_id, '_settings', $_POST );
}

$settings = get_post_meta( $project_id, '_settings', true );

/**
 *  Отключение возможности создавать списки задач
 */
$settings['client']['create_todolist'] = 'no';

$labels   = cpm_settings_label();
cpm_get_header( __( 'Settings', 'cpm' ), $project_id );
?>

<?php if ( $updated ) { ?>
    <div class="updated">
        <p><?php _e( 'Changes saved successfully.', 'cpm' ); ?></p>
    </div>
<?php } ?>

<form id="cpm-settings" method="post" action="">

    <?php wp_nonce_field( 'cpm_settings_nonce' ); ?>

    <table class="widefat cpm-table">
        <thead >
        <th><?php _e( 'Co-worker', 'cpm' ); ?></th>
        <th><?php _e( 'Client', 'cpm' ); ?></th>
        </thead>
        <tbody>
            <?php
            foreach ( $labels as $section => $name ) {
                $tr_class = str_replace( ' ', '-', strtolower( $section ) );
                ?>
                <tr class="<?php echo $tr_class; ?>"><thead><th colspan="2"><?php echo $section; ?></th></thead></tr>

            <?php foreach ( $name as $key => $field ) { ?>
                <tr class="<?php echo $tr_class; ?>">
                    <td>
                        <label>
                            <?php $settings['co_worker'][$key] = isset( $settings['co_worker'][$key] ) ? $settings['co_worker'][$key] : ''; ?>
                            <input type="checkbox" <?php checked( 'yes', $settings['co_worker'][$key] ); ?> value="yes" name="co_worker[<?php echo $key; ?>]">
                            <?php echo $field; ?>
                        </label>
                    </td>
                    <td>
                        <label>
                            <?php $settings['client'][$key]    = isset( $settings['client'][$key] ) ? $settings['client'][$key] : ''; ?>
                            <input type="checkbox" <?php checked( 'yes', $settings['client'][$key] ); ?> value="yes" name="client[<?php echo $key; ?>]">
                            <?php echo $field; ?>
                        </label>
                    </td>
                </tr>
                <?php
            }
        }
        ?>
        </tbody>
		<tbody style="background-color:inherit">
			<tr><td colspan="2">
				<?php do_action('cpm_project_settings') ?>
			</td></tr>	
		</tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="submit">
                    <input type="submit" class="button-primary" name="sub_seettings" value="<?php echo esc_attr_e( 'Save Changes', 'cpm' ); ?>">
                </td>
            </tr>
        </tfoot>
    </table>
</form>

