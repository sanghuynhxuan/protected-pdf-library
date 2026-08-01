<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Custom Post Type "Secure Document".
 */
function spmv_register_cpt() {
    $labels = array(
        'name'                  => _x( 'Secure Documents', 'Post Type General Name', 'spmv' ),
        'singular_name'         => _x( 'Secure Document', 'Post Type Singular Name', 'spmv' ),
        'menu_name'             => __( 'Secure Docs', 'spmv' ),
        'name_admin_bar'        => __( 'Secure Document', 'spmv' ),
        'archives'              => __( 'Document Archives', 'spmv' ),
        'attributes'            => __( 'Document Attributes', 'spmv' ),
        'parent_item_colon'     => __( 'Parent Document:', 'spmv' ),
        'all_items'             => __( 'All Documents', 'spmv' ),
        'add_new_item'          => __( 'Add New Document', 'spmv' ),
        'add_new'               => __( 'Add New', 'spmv' ),
        'new_item'              => __( 'New Document', 'spmv' ),
        'edit_item'             => __( 'Edit Document', 'spmv' ),
        'update_item'           => __( 'Update Document', 'spmv' ),
        'view_item'             => __( 'View Document', 'spmv' ),
        'view_items'            => __( 'View Documents', 'spmv' ),
        'search_items'          => __( 'Search Document', 'spmv' ),
        'not_found'             => __( 'Not found', 'spmv' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'spmv' ),
        'featured_image'        => __( 'Featured Image', 'spmv' ),
        'set_featured_image'    => __( 'Set featured image', 'spmv' ),
        'remove_featured_image' => __( 'Remove featured image', 'spmv' ),
        'use_featured_image'    => __( 'Use as featured image', 'spmv' ),
        'insert_into_item'      => __( 'Insert into document', 'spmv' ),
        'uploaded_to_this_item' => __( 'Uploaded to this document', 'spmv' ),
        'items_list'            => __( 'Documents list', 'spmv' ),
        'items_list_navigation' => __( 'Documents list navigation', 'spmv' ),
        'filter_items_list'     => __( 'Filter documents list', 'spmv' ),
    );
    $args = array(
        'label'                 => __( 'Secure Document', 'spmv' ),
        'description'           => __( 'For managing secure PDF documents.', 'spmv' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor' ), // Add 'editor' for description
        'hierarchical'          => false,
        'public'                => false, // Not publicly queryable by default
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 20,
        'menu_icon'             => 'dashicons-media-document',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false, // No public archive page
        'exclude_from_search'   => true,
        'publicly_queryable'    => false, // Set to true if you need single CPT pages (not recommended for secure files)
        'rewrite'               => false, // No rewrite rules needed if not public
        'capability_type'       => 'post',
        'show_in_rest'          => true, // Enable for Gutenberg editor if needed
    );
    register_post_type( 'secure_document', $args );
}
add_action( 'init', 'spmv_register_cpt', 0 );

/**
 * Add Meta Boxes for PDF upload.
 */
function spmv_add_meta_boxes() {
    add_meta_box(
        'spmv_pdf_upload_meta_box',
        __( 'PDF File Management', 'spmv' ),
        'spmv_pdf_upload_meta_box_callback',
        'secure_document', // CPT slug
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'spmv_add_meta_boxes' );

/**
 * Callback for PDF Upload Meta Box.
 */
function spmv_pdf_upload_meta_box_callback( $post ) {
    wp_nonce_field( 'spmv_save_pdf_meta_box_data', 'spmv_pdf_meta_box_nonce' );

    $file_name = get_post_meta( $post->ID, '_secure_pdf_file_name', true );
    $file_path_meta = get_post_meta( $post->ID, '_secure_pdf_file_path_meta', true ); // Full server path

    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row">
                <label for="spmv_pdf_file"><?php _e( 'Upload PDF', 'spmv' ); ?></label>
            </th>
            <td>
                <input type="file" id="spmv_pdf_file" name="spmv_pdf_file" accept=".pdf" />
                <?php if ( $file_name ) : ?>
                    <p>
                        <?php _e( 'Current file:', 'spmv' ); ?> <strong><?php echo esc_html( $file_name ); ?></strong>
                        (<?php
                            if (file_exists($file_path_meta)) {
                                echo esc_html( round( filesize( $file_path_meta ) / 1024 ) . ' KB' );
                            } else {
                                echo '<span style="color:red;">' . __('File missing on server!', 'spmv') . '</span>';
                            }
                        ?>)
                    </p>
                    <label>
                        <input type="checkbox" name="spmv_replace_file" value="1" />
                        <?php _e( 'Replace current file if a new file is uploaded.', 'spmv' ); ?>
                    </label>
                    <br>
                    <label style="color: #a00;">
                        <input type="checkbox" name="spmv_delete_current_file" value="1" />
                        <?php _e( 'Delete current file (use with caution).', 'spmv' ); ?>
                    </label>
                <?php endif; ?>
                <p class="description">
                    <?php _e( 'Select a PDF file to upload. Max file size: ', 'spmv' ); ?>
                    <?php echo esc_html( size_format( wp_max_upload_size() ) ); ?>.
                </p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Add custom columns to the CPT admin list.
 */
function spmv_set_custom_edit_secure_document_columns( $columns ) {
    $new_columns = array();
    foreach ($columns as $key => $title) {
        $new_columns[$key] = $title;
        if ($key == 'title') { // Add after title
            $new_columns['pdf_file_name'] = __( 'File Name', 'spmv' );
            $new_columns['shortcode'] = __( 'Shortcode', 'spmv' );
        }
    }
    return $new_columns;
}
add_filter( 'manage_secure_document_posts_columns', 'spmv_set_custom_edit_secure_document_columns' );

/**
 * Display data for custom columns.
 */
function spmv_custom_secure_document_column( $column, $post_id ) {
    switch ( $column ) {
        case 'pdf_file_name':
            $file_name = get_post_meta( $post_id, '_secure_pdf_file_name', true );
            echo $file_name ? esc_html( $file_name ) : __( 'N/A', 'spmv' );
            break;

        case 'shortcode':
            echo '<code>[secure_pdf_viewer id="' . intval( $post_id ) . '"]</code>';
            break;
    }
}
add_action( 'manage_secure_document_posts_custom_column', 'spmv_custom_secure_document_column', 10, 2 );

?>