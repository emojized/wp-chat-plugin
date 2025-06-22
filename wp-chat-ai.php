<?php
/**
 * Plugin Name: WP Chat AI
 * Plugin URI: https://example.com/wp-chat-ai
 * Description: A WordPress plugin that provides an AI-powered chat interface using Naive Bayes algorithm to answer questions based on post content.
 * Version: 1.0.0
 * Author: Manus AI
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-chat-ai
 * Domain Path: /languages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define( 'WPCAI_VERSION', '1.0.0' );
define( 'WPCAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPCAI_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPCAI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class
 */
class WP_Chat_AI {

    /**
     * Plugin instance
     *
     * @var WP_Chat_AI
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return WP_Chat_AI
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        register_uninstall_hook( __FILE__, array( 'WP_Chat_AI', 'uninstall' ) );

        add_action( 'init', array( $this, 'init' ) );
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core includes
        require_once WPCAI_PLUGIN_PATH . 'includes/class-wpcai-naive-bayes.php';
        require_once WPCAI_PLUGIN_PATH . 'includes/class-wpcai-tokenizer.php';
        require_once WPCAI_PLUGIN_PATH . 'includes/class-wpcai-database.php';
        require_once WPCAI_PLUGIN_PATH . 'includes/class-wpcai-ajax.php';

        // Admin includes
        if ( is_admin() ) {
            require_once WPCAI_PLUGIN_PATH . 'admin/class-wpcai-admin.php';
        }

        // Public includes
        if ( ! is_admin() ) {
            require_once WPCAI_PLUGIN_PATH . 'public/class-wpcai-public.php';
        }
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize admin area
        if ( is_admin() ) {
            WPCAI_Admin::get_instance();
        }

        // Initialize public area
        if ( ! is_admin() ) {
            WPCAI_Public::get_instance();
        }

        // Initialize AJAX handlers
        WPCAI_Ajax::get_instance();
    }

    /**
     * Load plugin textdomain for internationalization
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wp-chat-ai',
            false,
            dirname( WPCAI_PLUGIN_BASENAME ) . '/languages/'
        );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        WPCAI_Database::create_tables();

        // Set default options
        $default_options = array(
            'chat_enabled' => true,
            'chat_position' => 'bottom-right',
            'chat_title' => __( 'Ask a Question', 'wp-chat-ai' ),
            'training_status' => 'pending',
            'last_training' => '',
        );

        foreach ( $default_options as $option => $value ) {
            if ( ! get_option( 'wpcai_' . $option ) ) {
                add_option( 'wpcai_' . $option, $value );
            }
        }

        // Schedule training if posts exist
        if ( wp_count_posts()->publish > 0 ) {
            wp_schedule_single_event( time() + 60, 'wpcai_train_model' );
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook( 'wpcai_train_model' );
        wp_clear_scheduled_hook( 'wpcai_retrain_model' );

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Remove database tables
        WPCAI_Database::drop_tables();

        // Remove options
        $options = array(
            'wpcai_chat_enabled',
            'wpcai_chat_position',
            'wpcai_chat_title',
            'wpcai_training_status',
            'wpcai_last_training',
        );

        foreach ( $options as $option ) {
            delete_option( $option );
        }

        // Clear any remaining scheduled events
        wp_clear_scheduled_hook( 'wpcai_train_model' );
        wp_clear_scheduled_hook( 'wpcai_retrain_model' );
    }
}

// Initialize the plugin
WP_Chat_AI::get_instance();

