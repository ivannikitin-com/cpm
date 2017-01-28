<?php
$args = array(
    'post_type'   => 'attachment',
    'meta_key'    => '_project_uploaded',
    'meta_value'  => $project_id,
    'numberposts' => -1,
);

$pro_attachments = get_posts( $args );
$base_image_url  = admin_url( 'admin-ajax.php?action=cpm_file_get' );


echo '<div class="cpm-files-page">';
echo '<ul class="cpm-files">';

foreach ( $pro_attachments as $attachment ) {

    $file       = CPM_Comment::getInstance()->get_file( $attachment->ID );
    $is_private = get_post_meta( $attachment->ID, '_files_privacy', true );
    if ( ! cpm_user_can_access_file( $project_id, 'file_view_private', $is_private ) ) {
        continue;
    }

    if ( ! $attachment->post_parent ) {
        $parent_id = get_post_meta( $attachment->ID, '_parent', true );
        $parent    = get_post( $parent_id );
    } else {
        $parent = get_post( $attachment->post_parent );
    }

    $post_type_object = get_post_meta( $attachment->ID, '_doc_type', true );

    $file_url = sprintf( '%s&file_id=%d&project_id=%d', $base_image_url, $file['id'], $project_id );

    if ( $file['type'] == 'image' ) {
        $thumb_url = sprintf( '%s&file_id=%d&project_id=%d&type=thumb', $base_image_url, $file['id'], $project_id );
        $class     = 'cpm-colorbox-img';
    } else {
        $thumb_url = $file['thumb'];
        $class     = '';
    }
    ?>
    <li id="pro-<?php echo $file['id'] ?>">
        <div class="cpm-thumb">
            <a class="<?php echo $class; ?>" title="<?php echo esc_attr( $file['name'] ); ?>" href="<?php echo $file_url; ?>"><img src="<?php echo $thumb_url; ?>" alt="<?php echo esc_attr( $file['name'] ); ?>" /></a>
        </div>
        <div class="">
            <h3 class="cpm-file-name"><?php echo $file['name']; ?></h3>

            <div class="cpm-file-meta">
                <?php // printf( __( 'Attached to <a href="%s">%s</a> by %s', 'cpm' ), $topic_url, strtolower( $post_type_object ), cpm_url_user( $attachment->post_author ) ) ?>
            </div>

            <div class="cpm-file-action">
                <ul>
                    <li class="cpm-download-file"> <a href="<?php echo $file_url ?>" > </a> </li>
                    <?php
                    if ( get_current_user_id() == $attachment->post_author ) {
                        $privacy_css = ( $is_private =='yes' ) ? 'dashicons-lock' : 'dashicons-unlock';
                        $privacy_title  = $is_private ? __( 'File is private. Click to make file public.', 'cpm' ) : __( 'File is public. Click to make file private.' );
                        ?>
                        <li class="cpm-pro-delete-file"> <a href="JavaScript:void(0)" class="dashicons-before dashicons-trash" data-id="<?php echo $file['id'] ?>" data-pid="<?php echo $project_id ?>"> </a> </li>
                        <li class="cpm-pro-file-privacy">
                            <span class="cpm-loading cpm-right cpm-hide"></span>
                            <a href="JavaScript:void(0)" class="dashicons-before  <?php echo $privacy_css ?>" title="<?php echo $privacy_title; ?>" data-privacy="<?php echo $is_private; ?>" data-id="<?php echo $file['id'] ?>" data-pid="<?php echo $project_id ?>"> </a> </li>

                    <?php } ?>
                </ul>
            </div>
        </div>
    </li>

    <?php
}
echo '</ul> </div>';
?>

