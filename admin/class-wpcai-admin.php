<?php
/**
 * Admin area class
 *
 * @package WP_Chat_AI
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WPCAI_Admin class
 */
class WPCAI_Admin {

    /**
     * Class instance
     *
     * @var WPCAI_Admin
     */
    private static $instance = null;

    /**
     * Get class instance
     *
     * @return WPCAI_Admin
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
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_filter( 'plugin_action_links_' . WPCAI_PLUGIN_BASENAME, array( $this, 'add_plugin_action_links' ) );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'WP Chat AI', 'wp-chat-ai' ),
            __( 'WP Chat AI', 'wp-chat-ai' ),
            'manage_options',
            'wp-chat-ai',
            array( $this, 'admin_page' ),
            'dashicons-format-chat',
            30
        );

        add_submenu_page(
            'wp-chat-ai',
            __( 'Settings', 'wp-chat-ai' ),
            __( 'Settings', 'wp-chat-ai' ),
            'manage_options',
            'wp-chat-ai',
            array( $this, 'admin_page' )
        );

        add_submenu_page(
            'wp-chat-ai',
            __( 'Training', 'wp-chat-ai' ),
            __( 'Training', 'wp-chat-ai' ),
            'manage_options',
            'wp-chat-ai-training',
            array( $this, 'training_page' )
        );

        add_submenu_page(
            'wp-chat-ai',
            __( 'Chat Logs', 'wp-chat-ai' ),
            __( 'Chat Logs', 'wp-chat-ai' ),
            'manage_options',
            'wp-chat-ai-logs',
            array( $this, 'logs_page' )
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting( 'wpcai_settings', 'wpcai_chat_enabled' );
        register_setting( 'wpcai_settings', 'wpcai_chat_position' );
        register_setting( 'wpcai_settings', 'wpcai_chat_title' );
        register_setting( 'wpcai_settings', 'wpcai_chat_placeholder' );
        register_setting( 'wpcai_settings', 'wpcai_chat_button_text' );
        register_setting( 'wpcai_settings', 'wpcai_confidence_threshold' );
        register_setting( 'wpcai_settings', 'wpcai_max_response_length' );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( strpos( $hook, 'wp-chat-ai' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'wpcai-admin-style',
            WPCAI_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            WPCAI_VERSION
        );

        wp_enqueue_script(
            'wpcai-admin-script',
            WPCAI_PLUGIN_URL . 'admin/js/admin.js',
            array( 'jquery' ),
            WPCAI_VERSION,
            true
        );

        wp_localize_script( 'wpcai-admin-script', 'wpcai_admin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'train_nonce' => wp_create_nonce( 'wpcai_train_model' ),
            'status_nonce' => wp_create_nonce( 'wpcai_get_status' ),
            'strings' => array(
                'training_started' => __( 'Training started...', 'wp-chat-ai' ),
                'training_error' => __( 'Training failed. Please try again.', 'wp-chat-ai' ),
                'status_error' => __( 'Could not get training status.', 'wp-chat-ai' )
            )
        ) );
    }

    /**
     * Add plugin action links
     */
    public function add_plugin_action_links( $links ) {
        $settings_link = '<a href="' . admin_url( 'admin.php?page=wp-chat-ai' ) . '">' . __( 'Settings', 'wp-chat-ai' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Main admin page
     */
    public function admin_page() {
        if ( isset( $_POST['submit'] ) ) {
            $this->save_settings();
        }

        $chat_enabled = get_option( 'wpcai_chat_enabled', true );
        $chat_position = get_option( 'wpcai_chat_position', 'bottom-right' );
        $chat_title = get_option( 'wpcai_chat_title', __( 'Ask a Question', 'wp-chat-ai' ) );
        $chat_placeholder = get_option( 'wpcai_chat_placeholder', __( 'Type your question here...', 'wp-chat-ai' ) );
        $chat_button_text = get_option( 'wpcai_chat_button_text', __( 'Send', 'wp-chat-ai' ) );
        $confidence_threshold = get_option( 'wpcai_confidence_threshold', 0.1 );
        $max_response_length = get_option( 'wpcai_max_response_length', 500 );

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'wpcai_save_settings', 'wpcai_settings_nonce' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e( 'Enable Chat', 'wp-chat-ai' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="wpcai_chat_enabled" value="1" <?php checked( $chat_enabled ); ?> />
                                <?php _e( 'Enable the chat interface on the frontend', 'wp-chat-ai' ); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e( 'Chat Position', 'wp-chat-ai' ); ?></th>
                        <td>
                            <select name="wpcai_chat_position">
                                <option value="bottom-right" <?php selected( $chat_position, 'bottom-right' ); ?>><?php _e( 'Bottom Right', 'wp-chat-ai' ); ?></option>
                                <option value="bottom-left" <?php selected( $chat_position, 'bottom-left' ); ?>><?php _e( 'Bottom Left', 'wp-chat-ai' ); ?></option>
                                <option value="top-right" <?php selected( $chat_position, 'top-right' ); ?>><?php _e( 'Top Right', 'wp-chat-ai' ); ?></option>
                                <option value="top-left" <?php selected( $chat_position, 'top-left' ); ?>><?php _e( 'Top Left', 'wp-chat-ai' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e( 'Chat Title', 'wp-chat-ai' ); ?></th>
                        <td>
                            <input type="text" name="wpcai_chat_title" value="<?php echo esc_attr( $chat_title ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e( 'Input Placeholder', 'wp-chat-ai' ); ?></th>
                        <td>
                            <input type="text" name="wpcai_chat_placeholder" value="<?php echo esc_attr( $chat_placeholder ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e( 'Button Text', 'wp-chat-ai' ); ?></th>
                        <td>
                            <input type="text" name="wpcai_chat_button_text" value="<?php echo esc_attr( $chat_button_text ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e( 'Confidence Threshold', 'wp-chat-ai' ); ?></th>
                        <td>
                            <input type="number" name="wpcai_confidence_threshold" value="<?php echo esc_attr( $confidence_threshold ); ?>" min="0" max="1" step="0.01" class="small-text" />
                            <p class="description"><?php _e( 'Minimum confidence score required to show an answer (0.0 - 1.0)', 'wp-chat-ai' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e( 'Max Response Length', 'wp-chat-ai' ); ?></th>
                        <td>
                            <input type="number" name="wpcai_max_response_length" value="<?php echo esc_attr( $max_response_length ); ?>" min="100" max="2000" class="small-text" />
                            <p class="description"><?php _e( 'Maximum number of characters in chat responses', 'wp-chat-ai' ); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Training page
     */
    public function training_page() {
        $training_status = get_option( 'wpcai_training_status', 'pending' );
        $last_training = get_option( 'wpcai_last_training', '' );
        $total_posts = wp_count_posts()->publish;
        $trained_posts = count( WPCAI_Database::get_all_training_data() );

        ?>
        <div class="wrap">
            <h1><?php _e( 'Model Training', 'wp-chat-ai' ); ?></h1>
            
            <div class="wpcai-training-status">
                <h2><?php _e( 'Training Status', 'wp-chat-ai' ); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e( 'Current Status', 'wp-chat-ai' ); ?></th>
                        <td>
                            <span class="wpcai-status wpcai-status-<?php echo esc_attr( $training_status ); ?>">
                                <?php echo esc_html( ucfirst( $training_status ) ); ?>
                            </span>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e( 'Total Posts', 'wp-chat-ai' ); ?></th>
                        <td><?php echo esc_html( $total_posts ); ?></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e( 'Trained Posts', 'wp-chat-ai' ); ?></th>
                        <td><?php echo esc_html( $trained_posts ); ?></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e( 'Progress', 'wp-chat-ai' ); ?></th>
                        <td>
                            <?php
                            $progress = $total_posts > 0 ? round( ( $trained_posts / $total_posts ) * 100 ) : 0;
                            echo esc_html( $progress ) . '%';
                            ?>
                        </td>
                    </tr>
                    
                    <?php if ( $last_training ) : ?>
                    <tr>
                        <th scope="row"><?php _e( 'Last Training', 'wp-chat-ai' ); ?></th>
                        <td><?php echo esc_html( $last_training ); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <p>
                    <button type="button" id="wpcai-train-model" class="button button-primary">
                        <?php _e( 'Start Training', 'wp-chat-ai' ); ?>
                    </button>
                    
                    <button type="button" id="wpcai-refresh-status" class="button">
                        <?php _e( 'Refresh Status', 'wp-chat-ai' ); ?>
                    </button>
                </p>
                
                <div id="wpcai-training-messages"></div>
            </div>
            
            <div class="wpcai-training-info">
                <h2><?php _e( 'About Training', 'wp-chat-ai' ); ?></h2>
                <p><?php _e( 'The training process analyzes all your published posts and creates a machine learning model that can answer questions based on your content.', 'wp-chat-ai' ); ?></p>
                <p><?php _e( 'Training may take several minutes depending on the number of posts. The process runs in the background, so you can continue using your site normally.', 'wp-chat-ai' ); ?></p>
                <p><?php _e( 'You should retrain the model whenever you publish new content or make significant changes to existing posts.', 'wp-chat-ai' ); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Chat logs page
     */
    public function logs_page() {
        $logs = WPCAI_Database::get_chat_logs( 100 );

        ?>
        <div class="wrap">
            <h1><?php _e( 'Chat Logs', 'wp-chat-ai' ); ?></h1>
            
            <?php if ( empty( $logs ) ) : ?>
                <p><?php _e( 'No chat interactions yet.', 'wp-chat-ai' ); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e( 'Date', 'wp-chat-ai' ); ?></th>
                            <th><?php _e( 'Question', 'wp-chat-ai' ); ?></th>
                            <th><?php _e( 'Matched Post', 'wp-chat-ai' ); ?></th>
                            <th><?php _e( 'Confidence', 'wp-chat-ai' ); ?></th>
                            <th><?php _e( 'Response', 'wp-chat-ai' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $logs as $log ) : ?>
                            <tr>
                                <td><?php echo esc_html( $log->created_at ); ?></td>
                                <td><?php echo esc_html( wp_trim_words( $log->user_question, 10 ) ); ?></td>
                                <td>
                                    <?php if ( $log->matched_post_id ) : ?>
                                        <a href="<?php echo esc_url( get_edit_post_link( $log->matched_post_id ) ); ?>">
                                            <?php echo esc_html( get_the_title( $log->matched_post_id ) ); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php _e( 'No match', 'wp-chat-ai' ); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( number_format( $log->confidence_score, 3 ) ); ?></td>
                                <td><?php echo esc_html( wp_trim_words( $log->response_text, 15 ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Save settings
     */
    private function save_settings() {
        if ( ! wp_verify_nonce( $_POST['wpcai_settings_nonce'], 'wpcai_save_settings' ) ) {
            wp_die( __( 'Security check failed', 'wp-chat-ai' ) );
        }

        update_option( 'wpcai_chat_enabled', isset( $_POST['wpcai_chat_enabled'] ) );
        update_option( 'wpcai_chat_position', sanitize_text_field( $_POST['wpcai_chat_position'] ) );
        update_option( 'wpcai_chat_title', sanitize_text_field( $_POST['wpcai_chat_title'] ) );
        update_option( 'wpcai_chat_placeholder', sanitize_text_field( $_POST['wpcai_chat_placeholder'] ) );
        update_option( 'wpcai_chat_button_text', sanitize_text_field( $_POST['wpcai_chat_button_text'] ) );
        update_option( 'wpcai_confidence_threshold', floatval( $_POST['wpcai_confidence_threshold'] ) );
        update_option( 'wpcai_max_response_length', intval( $_POST['wpcai_max_response_length'] ) );

        echo '<div class="notice notice-success"><p>' . __( 'Settings saved successfully.', 'wp-chat-ai' ) . '</p></div>';
    }
}

