<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Custom upload directory filter.
 */
function spmv_custom_secure_pdf_upload_dir( $param ) {
    // Check if this is our CPT save action or specific upload context
    // This is a simplified check; a more robust check might involve verifying a nonce or action field.
    if ( isset( $_POST['post_type'] ) && $_POST['post_type'] === 'secure_document' ) {
        $secure_dir_name = SPMV_SECURE_UPLOAD_DIR_NAME;
        $param['path'] = rtrim(ABSPATH, '/') . '/' . $secure_dir_name;
        $param['url']  = site_url( '/' . $secure_dir_name ); // This URL won't be used directly by end-users
        $param['subdir']  = ''; // Optional: No year/month subfolders inside secure dir
        // Create the directory if it doesn't exist (though activation hook should handle this)
        if ( ! file_exists( $param['path'] ) ) {
            wp_mkdir_p( $param['path'] );
        }
    }
    return $param;
}


/**
 * Save PDF Meta Box Data.
 */
function spmv_save_pdf_meta_box_data( $post_id ) {
    // Check if our nonce is set.
    if ( ! isset( $_POST['spmv_pdf_meta_box_nonce'] ) ) {
        return;
    }
    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $_POST['spmv_pdf_meta_box_nonce'], 'spmv_save_pdf_meta_box_data' ) ) {
        return;
    }
    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    // Check the user's permissions.
    if ( isset( $_POST['post_type'] ) && 'secure_document' == $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    } else {
        return; // Not our CPT
    }

    $current_file_path_meta = get_post_meta( $post_id, '_secure_pdf_file_path_meta', true );

    // Handle file deletion if checked
    if ( isset( $_POST['spmv_delete_current_file'] ) && $current_file_path_meta && file_exists( $current_file_path_meta ) ) {
        unlink( $current_file_path_meta );
        delete_post_meta( $post_id, '_secure_pdf_file_name' );
        delete_post_meta( $post_id, '_secure_pdf_file_path_meta' );
        $current_file_path_meta = ''; // Clear it as it's deleted
    }


    // Handle file upload
    if ( ! empty( $_FILES['spmv_pdf_file']['name'] ) ) {
        // Check if we should replace the existing file
        $replace_file = isset( $_POST['spmv_replace_file'] ) ? true : false;

        if ( $current_file_path_meta && file_exists( $current_file_path_meta ) && $replace_file ) {
            unlink( $current_file_path_meta ); // Delete old file if replacing
        } elseif ($current_file_path_meta && !$replace_file) {
            // A file exists, and we are not replacing, so do nothing with the new upload.
            // Or, add a message to the user. For now, we just skip.
            return;
        }


        // Temporarily add our custom upload directory filter
        add_filter( 'upload_dir', 'spmv_custom_secure_pdf_upload_dir' );

        // WordPress's own file handling function
        // We need to override the default 'action' for wp_handle_upload to work outside of admin-post.php
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        $uploaded_file = wp_handle_upload( $_FILES['spmv_pdf_file'], array( 'test_form' => false ) );

        // Remove the filter so it doesn't affect other uploads
        remove_filter( 'upload_dir', 'spmv_custom_secure_pdf_upload_dir' );

        if ( $uploaded_file && ! isset( $uploaded_file['error'] ) ) {
            // $uploaded_file['file'] is the full path to the file
            // $uploaded_file['url'] is the URL to the file (which we don't use directly)
            // $uploaded_file['type'] is the MIME type
            update_post_meta( $post_id, '_secure_pdf_file_name', basename( $uploaded_file['file'] ) );
            update_post_meta( $post_id, '_secure_pdf_file_path_meta', $uploaded_file['file'] ); // Store the full server path
        } else {
            // File upload failed, add an error message to be displayed
            // You might want to use `add_settings_error` or a transient for this.
            // For simplicity, we'll just log it if WP_DEBUG is on.
            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                error_log('SPMV PDF Upload Error: ' . $uploaded_file['error']);
            }
        }
    }
}
// Hook into the save_post action for our CPT
add_action( 'save_post_secure_document', 'spmv_save_pdf_meta_box_data' );

?>