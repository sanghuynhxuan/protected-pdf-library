<?php
/**
 * Plugin Name:       Secure PDF Manager & Viewer
 * Plugin URI:        https://example.test
 * Description:       Manages and securely displays PDF files via a custom post type and shortcode.
 * Version:           1.0.1
 * Author:            Sang Huynh Xuan
 * Author URI:        https://example.test
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       spmv
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'SPMV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPMV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SPMV_SECURE_UPLOAD_DIR_NAME', 'secure-files' ); // Tên thư mục bảo mật

/**
 * Activation hook.
 * Creates the secure upload directory and .htaccess file.
 */
function spmv_activate_plugin() {
    $secure_path = rtrim(ABSPATH, '/') . '/' . SPMV_SECURE_UPLOAD_DIR_NAME;

    if ( ! file_exists( $secure_path ) ) {
        wp_mkdir_p( $secure_path );
    }

    $htaccess_content = "Order Allow,Deny\nDeny from all";
    $htaccess_file = $secure_path . '/.htaccess';

    if ( ! file_exists( $htaccess_file ) ) {
        // Mở file .htaccess để ghi (w)
        $file_handle = @fopen( $htaccess_file, 'w' );
        if ( $file_handle ) {
            @fwrite( $file_handle, $htaccess_content );
            @fclose( $file_handle );
        }
    }
    // Đăng ký lại CPT để flush rewrite rules nếu cần
    require_once SPMV_PLUGIN_DIR . 'includes/cpt-handler.php';
    spmv_register_cpt();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'spmv_activate_plugin' );

/**
 * Deactivation hook (optional).
 */
function spmv_deactivate_plugin() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'spmv_deactivate_plugin' );


// Include plugin files
require_once SPMV_PLUGIN_DIR . 'includes/cpt-handler.php';
require_once SPMV_PLUGIN_DIR . 'includes/file-operations.php';
require_once SPMV_PLUGIN_DIR . 'includes/shortcode-handler.php';
require_once SPMV_PLUGIN_DIR . 'includes/admin-hooks.php';

/**
 * Enqueue admin scripts and styles.
 */
function spmv_admin_enqueue_scripts( $hook ) {
    global $post_type;
    if ( 'secure_document' == $post_type && ( 'post.php' == $hook || 'post-new.php' == $hook ) ) {
        wp_enqueue_media(); // For WordPress media uploader
        // wp_enqueue_script('spmv-admin-js', SPMV_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('spmv-admin-css', SPMV_PLUGIN_URL . 'assets/css/admin-styles.css', array(), '1.0.0');
    }
}
add_action( 'admin_enqueue_scripts', 'spmv_admin_enqueue_scripts' );

?>