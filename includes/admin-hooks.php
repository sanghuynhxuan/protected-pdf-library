<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX handler to serve secure PDF files.
 * This is called by the PDF.js viewer via the URL generated in the shortcode.
 */
function spmv_ajax_serve_secure_pdf() {
    $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;

    // Verify nonce
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'spmv_serve_secure_pdf_nonce_' . $post_id ) ) {
        status_header( 403 );
        wp_die( 'Security check failed (nonce).', 'Forbidden', array( 'response' => 403 ) );
    }

    if ( ! $post_id || get_post_type( $post_id ) !== 'secure_document' ) {
        status_header( 404 );
        wp_die( 'Invalid document ID or type specified.', 'Not Found', array( 'response' => 404 ) );
    }

    // Get the stored full server path of the PDF
    $file_path_on_server = get_post_meta( $post_id, '_secure_pdf_file_path_meta', true );
    $file_name_meta = get_post_meta( $post_id, '_secure_pdf_file_name', true );


    if ( ! $file_path_on_server || ! $file_name_meta || ! file_exists( $file_path_on_server ) ) {
        status_header( 404 );
        wp_die( 'PDF file not found on server for this document.', 'Not Found', array( 'response' => 404 ) );
    }

    // Ensure the file is actually within our secure directory (extra safety check)
    $expected_base_path = rtrim(ABSPATH, '/') . '/' . SPMV_SECURE_UPLOAD_DIR_NAME;

    $real_file_path = realpath($file_path_on_server);
    $real_expected_base = realpath($expected_base_path);

    if ( ! $real_file_path || ! $real_expected_base || 
        (strpos( $real_file_path, $real_expected_base . DIRECTORY_SEPARATOR ) !== 0 && $real_file_path !== $real_expected_base) ) {
    
        status_header( 403 );
        wp_die( 'Access to this file is restricted (path mismatch).', 'Forbidden', array( 'response' => 403 ) );
    }


    // Serve the file
    header( 'Content-Type: application/pdf' );
    header( 'Content-Disposition: inline; filename="' . esc_attr( $file_name_meta ) . '"' ); // 'inline' for viewing in browser
    header( 'Content-Transfer-Encoding: binary' );
    header( 'Content-Length: ' . filesize( $file_path_on_server ) );
    header( 'Accept-Ranges: bytes' );
    header( 'Cache-Control: private, max-age=0, must-revalidate'); // Prevent caching if needed, or set appropriate caching
    header( 'Pragma: public'); // For IE

    // Clean output buffer
    if ( ob_get_level() ) {
        ob_end_clean();
    }

    @readfile( $file_path_on_server );
    exit;
}
// Hook for logged-in users
add_action( 'wp_ajax_spmv_serve_secure_pdf', 'spmv_ajax_serve_secure_pdf' );
// Hook for non-logged-in users (as per client's "view only" requirement for general readers)
add_action( 'wp_ajax_nopriv_spmv_serve_secure_pdf', 'spmv_ajax_serve_secure_pdf' );

?>