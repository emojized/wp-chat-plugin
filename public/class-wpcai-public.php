<?php
/**
 * Public area class
 *
 * @package WP_Chat_AI
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WPCAI_Public class
 */
class WPCAI_Public {

    /**
     * Class instance
     *
     * @var WPCAI_Public
     */
    private static $instance = null;

    /**
     * Get class instance
     *
     * @return WPCAI_Public
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
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_scripts' ) );
        add_action( 'wp_footer', array( $this, 'render_chat_interface' ) );
    }

    /**
     * Enqueue public scripts and styles
     */
    public function enqueue_public_scripts() {
        // Only load if chat is enabled
        if ( ! get_option( 'wpcai_chat_enabled', true ) ) {
            return;
        }

        wp_enqueue_style(
            'wpcai-public-style',
            WPCAI_PLUGIN_URL . 'public/css/public.css',
            array(),
            WPCAI_VERSION
        );

        wp_enqueue_script(
            'wpcai-public-script',
            WPCAI_PLUGIN_URL . 'public/js/public.js',
            array( 'jquery' ),
            WPCAI_VERSION,
            true
        );

        wp_localize_script( 'wpcai-public-script', 'wpcai_public', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'ask_nonce' => wp_create_nonce( 'wpcai_ask_question' ),
            'strings' => array(
                'thinking' => __( 'Thinking...', 'wp-chat-ai' ),
                'error' => __( 'Sorry, something went wrong. Please try again.', 'wp-chat-ai' ),
                'empty_question' => __( 'Please enter a question.', 'wp-chat-ai' ),
                'close' => __( 'Close', 'wp-chat-ai' ),
                'minimize' => __( 'Minimize', 'wp-chat-ai' ),
                'send' => __( 'Send', 'wp-chat-ai' )
            ),
            'settings' => array(
                'position' => get_option( 'wpcai_chat_position', 'bottom-right' ),
                'title' => get_option( 'wpcai_chat_title', __( 'Ask a Question', 'wp-chat-ai' ) ),
                'placeholder' => get_option( 'wpcai_chat_placeholder', __( 'Type your question here...', 'wp-chat-ai' ) ),
                'button_text' => get_option( 'wpcai_chat_button_text', __( 'Send', 'wp-chat-ai' ) )
            )
        ) );
    }

    /**
     * Render chat interface in footer
     */
    public function render_chat_interface() {
        // Only render if chat is enabled
        if ( ! get_option( 'wpcai_chat_enabled', true ) ) {
            return;
        }

        $position = get_option( 'wpcai_chat_position', 'bottom-right' );
        $title = get_option( 'wpcai_chat_title', __( 'Ask a Question', 'wp-chat-ai' ) );
        $placeholder = get_option( 'wpcai_chat_placeholder', __( 'Type your question here...', 'wp-chat-ai' ) );
        $button_text = get_option( 'wpcai_chat_button_text', __( 'Send', 'wp-chat-ai' ) );

        ?>
        <div id="wpcai-chat-widget" class="wpcai-chat-widget wpcai-position-<?php echo esc_attr( $position ); ?>">
            <!-- Chat Toggle Button -->
            <div id="wpcai-chat-toggle" class="wpcai-chat-toggle">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2ZM20 16H5.17L4 17.17V4H20V16Z" fill="currentColor"/>
                    <path d="M7 9H17V11H7V9ZM7 12H15V14H7V12Z" fill="currentColor"/>
                </svg>
            </div>

            <!-- Chat Window -->
            <div id="wpcai-chat-window" class="wpcai-chat-window">
                <!-- Chat Header -->
                <div class="wpcai-chat-header">
                    <h3 class="wpcai-chat-title"><?php echo esc_html( $title ); ?></h3>
                    <div class="wpcai-chat-controls">
                        <button id="wpcai-chat-minimize" class="wpcai-chat-control" title="<?php esc_attr_e( 'Minimize', 'wp-chat-ai' ); ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19 13H5V11H19V13Z" fill="currentColor"/>
                            </svg>
                        </button>
                        <button id="wpcai-chat-close" class="wpcai-chat-control" title="<?php esc_attr_e( 'Close', 'wp-chat-ai' ); ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Chat Messages -->
                <div id="wpcai-chat-messages" class="wpcai-chat-messages">
                    <div class="wpcai-message wpcai-message-bot">
                        <div class="wpcai-message-content">
                            <?php _e( 'Hello! I can help answer questions about our content. What would you like to know?', 'wp-chat-ai' ); ?>
                        </div>
                    </div>
                </div>

                <!-- Chat Input -->
                <div class="wpcai-chat-input-container">
                    <form id="wpcai-chat-form" class="wpcai-chat-form">
                        <div class="wpcai-input-group">
                            <input 
                                type="text" 
                                id="wpcai-chat-input" 
                                class="wpcai-chat-input" 
                                placeholder="<?php echo esc_attr( $placeholder ); ?>"
                                autocomplete="off"
                            />
                            <button type="submit" id="wpcai-chat-submit" class="wpcai-chat-submit">
                                <span class="wpcai-submit-text"><?php echo esc_html( $button_text ); ?></span>
                                <span class="wpcai-submit-loading" style="display: none;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="12" r="3" fill="currentColor">
                                            <animate attributeName="r" values="3;6;3" dur="1s" repeatCount="indefinite"/>
                                            <animate attributeName="opacity" values="1;0.5;1" dur="1s" repeatCount="indefinite"/>
                                        </circle>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Chat Styles (inline for better control) -->
        <style>
            .wpcai-chat-widget {
                position: fixed;
                z-index: 999999;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }

            .wpcai-position-bottom-right {
                bottom: 20px;
                right: 20px;
            }

            .wpcai-position-bottom-left {
                bottom: 20px;
                left: 20px;
            }

            .wpcai-position-top-right {
                top: 20px;
                right: 20px;
            }

            .wpcai-position-top-left {
                top: 20px;
                left: 20px;
            }

            .wpcai-chat-toggle {
                width: 60px;
                height: 60px;
                background: #007cba;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                transition: all 0.3s ease;
                color: white;
            }

            .wpcai-chat-toggle:hover {
                background: #005a87;
                transform: scale(1.05);
            }

            .wpcai-chat-window {
                position: absolute;
                bottom: 70px;
                right: 0;
                width: 350px;
                height: 500px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                display: none;
                flex-direction: column;
                overflow: hidden;
            }

            .wpcai-position-bottom-left .wpcai-chat-window,
            .wpcai-position-top-left .wpcai-chat-window {
                right: auto;
                left: 0;
            }

            .wpcai-position-top-right .wpcai-chat-window,
            .wpcai-position-top-left .wpcai-chat-window {
                bottom: auto;
                top: 70px;
            }

            .wpcai-chat-header {
                background: #007cba;
                color: white;
                padding: 15px 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .wpcai-chat-title {
                margin: 0;
                font-size: 16px;
                font-weight: 600;
            }

            .wpcai-chat-controls {
                display: flex;
                gap: 8px;
            }

            .wpcai-chat-control {
                background: none;
                border: none;
                color: white;
                cursor: pointer;
                padding: 4px;
                border-radius: 4px;
                transition: background 0.2s ease;
            }

            .wpcai-chat-control:hover {
                background: rgba(255, 255, 255, 0.2);
            }

            .wpcai-chat-messages {
                flex: 1;
                padding: 20px;
                overflow-y: auto;
                background: #f9f9f9;
            }

            .wpcai-message {
                margin-bottom: 15px;
                display: flex;
                flex-direction: column;
            }

            .wpcai-message-user {
                align-items: flex-end;
            }

            .wpcai-message-bot {
                align-items: flex-start;
            }

            .wpcai-message-content {
                max-width: 80%;
                padding: 12px 16px;
                border-radius: 18px;
                font-size: 14px;
                line-height: 1.4;
            }

            .wpcai-message-user .wpcai-message-content {
                background: #007cba;
                color: white;
            }

            .wpcai-message-bot .wpcai-message-content {
                background: white;
                color: #333;
                border: 1px solid #e0e0e0;
            }

            .wpcai-chat-input-container {
                padding: 20px;
                background: white;
                border-top: 1px solid #e0e0e0;
            }

            .wpcai-input-group {
                display: flex;
                gap: 10px;
            }

            .wpcai-chat-input {
                flex: 1;
                padding: 12px 16px;
                border: 1px solid #ddd;
                border-radius: 24px;
                font-size: 14px;
                outline: none;
                transition: border-color 0.2s ease;
            }

            .wpcai-chat-input:focus {
                border-color: #007cba;
            }

            .wpcai-chat-submit {
                background: #007cba;
                color: white;
                border: none;
                border-radius: 24px;
                padding: 12px 20px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                transition: background 0.2s ease;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .wpcai-chat-submit:hover {
                background: #005a87;
            }

            .wpcai-chat-submit:disabled {
                background: #ccc;
                cursor: not-allowed;
            }

            @media (max-width: 480px) {
                .wpcai-chat-window {
                    width: calc(100vw - 40px);
                    height: calc(100vh - 140px);
                    bottom: 70px;
                    right: 20px;
                    left: 20px;
                }

                .wpcai-position-bottom-left .wpcai-chat-window,
                .wpcai-position-top-left .wpcai-chat-window {
                    left: 20px;
                }
            }
        </style>
        <?php
    }
}

