<?php
/**
 * Uninstall script for WP Chat AI
 *
 * This file is executed when the plugin is deleted through the WordPress admin.
 * It removes all plugin data, options, and database tables.
 *
 * @package WP_Chat_AI
 */

// Prevent direct access
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Include the database class
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpcai-database.php';

/**
 * Remove all plugin data
 */
function wpcai_uninstall_cleanup() {
    global $wpdb;

    // Remove database tables
    WPCAI_Database::drop_tables();

    // Remove all plugin options
    $options = array(
        'wpcai_chat_enabled',
        'wpcai_chat_position',
        'wpcai_chat_title',
        'wpcai_chat_placeholder',
        'wpcai_chat_button_text',
        'wpcai_confidence_threshold',
        'wpcai_max_response_length',
        'wpcai_training_status',
        'wpcai_last_training',
    );

    foreach ( $options as $option ) {
        delete_option( $option );
        delete_site_option( $option ); // For multisite
    }

    // Remove any transients
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpcai_%'" );
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wpcai_%'" );

    // Clear any scheduled events
    wp_clear_scheduled_hook( 'wpcai_train_model' );
    wp_clear_scheduled_hook( 'wpcai_retrain_model' );

    // Remove user meta data (if any)
    $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'wpcai_%'" );

    // Clear any cached data
    if ( function_exists( 'wp_cache_flush' ) ) {
        wp_cache_flush();
    }

    // Remove uploaded files (if any)
    $upload_dir = wp_upload_dir();
    $plugin_upload_dir = $upload_dir['basedir'] . '/wp-chat-ai/';
    
    if ( is_dir( $plugin_upload_dir ) ) {
        wpcai_remove_directory( $plugin_upload_dir );
    }
}

/**
 * Recursively remove directory and its contents
 *
 * @param string $dir Directory path
 * @return bool True on success, false on failure
 */
function wpcai_remove_directory( $dir ) {
    if ( ! is_dir( $dir ) ) {
        return false;
    }

    $files = array_diff( scandir( $dir ), array( '.', '..' ) );
    
    foreach ( $files as $file ) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if ( is_dir( $path ) ) {
            wpcai_remove_directory( $path );
        } else {
            unlink( $path );
        }
    }
    
    return rmdir( $dir );
}

// Execute cleanup
wpcai_uninstall_cleanup();

