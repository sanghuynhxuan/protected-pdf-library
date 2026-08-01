<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Shortcode [secure_pdf_viewer id="POST_ID"]
 */
function spmv_secure_pdf_viewer_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'id' => 0, // Post ID of the 'secure_document' CPT
            'width' => '100%',
            'height' => '700px',
        ),
        $atts,
        'secure_pdf_viewer'
    );

    $post_id = intval( $atts['id'] );

    if ( ! $post_id || get_post_type( $post_id ) !== 'secure_document' ) {
        return '<p class="spmv-error">' . __( 'Invalid Document ID or type.', 'spmv' ) . '</p>';
    }

    $file_name = get_post_meta( $post_id, '_secure_pdf_file_name', true );
    $file_path_meta = get_post_meta( $post_id, '_secure_pdf_file_path_meta', true );

    if ( ! $file_name || ! $file_path_meta || ! file_exists( $file_path_meta ) ) {
        return '<p class="spmv-error">' . __( 'PDF file not found for this document.', 'spmv' ) . '</p>';
    }

    // Create a nonce for the file serving URL
    $nonce = wp_create_nonce( 'spmv_serve_secure_pdf_nonce_' . $post_id );
    // Build the URL to our AJAX handler that will serve the file
    $pdf_serve_url = admin_url( 'admin-ajax.php?action=spmv_serve_secure_pdf&post_id=' . $post_id . '&_wpnonce=' . $nonce );

    // Path to PDF.js viewer.html
    // IMPORTANT: Adjust this path if your PDF.js structure is different
    $pdfjs_viewer_url = SPMV_PLUGIN_URL . 'assets/js/pdfjs/web/viewer.html';

    $output = '<div class="spmv-pdf-viewer-container" style="width: ' . esc_attr( $atts['width'] ) . '; height: ' . esc_attr( $atts['height'] ) . ';">';

    // Construct the URL for viewer.html, passing our secure file URL as a parameter
    // The #pagemode=none&download=0&print=0 etc. are attempts to control PDF.js viewer.
    // You may need to customize viewer.js in PDF.js for more robust control if these URL params are not sufficient.
    $iframe_src = esc_url( $pdfjs_viewer_url . '?file=' . rawurlencode( $pdf_serve_url ) . '#pagemode=none&disableDownload=true&print=0&view=FitH' );

    $output .= '<iframe src="' . $iframe_src . '" width="100%" height="100%" style="border: none;">';
    $output .= '<p>' . __( 'Your browser does not support iframes. Please update your browser.', 'spmv' ) . '</p>';
    $output .= '</iframe>';
    $output .= '</div>';

    // Enqueue PDF.js worker if not already done (simplistic check)
    // A more robust way is to have PDF.js viewer handle its own worker.
    // wp_enqueue_script('pdfjs-worker', SPMV_PLUGIN_URL . 'assets/js/pdfjs/build/pdf.worker.js', array(), false, true);


    return $output;
}
add_shortcode( 'secure_pdf_viewer', 'spmv_secure_pdf_viewer_shortcode' );

?>