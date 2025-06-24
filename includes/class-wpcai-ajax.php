<?php
/**
 * AJAX handler class
 *
 * @package WP_Chat_AI
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WPCAI_Ajax class
 */
class WPCAI_Ajax {

    /**
     * Class instance
     *
     * @var WPCAI_Ajax
     */
    private static $instance = null;

    /**
     * Get class instance
     *
     * @return WPCAI_Ajax
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
        // AJAX actions for logged-in users
        add_action( 'wp_ajax_wpcai_ask_question', array( $this, 'handle_ask_question' ) );
        add_action( 'wp_ajax_wpcai_train_model', array( $this, 'handle_train_model' ) );
        add_action( 'wp_ajax_wpcai_get_training_status', array( $this, 'handle_get_training_status' ) );

        // AJAX actions for non-logged-in users
        add_action( 'wp_ajax_nopriv_wpcai_ask_question', array( $this, 'handle_ask_question' ) );

        // Scheduled action for model training
        add_action( 'wpcai_train_model', array( $this, 'train_model_background' ) );
    }

    /**
     * Handle ask question AJAX request
     */
    public function handle_ask_question() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'wpcai_ask_question' ) ) {
            wp_die( __( 'Security check failed', 'wp-chat-ai' ) );
        }

        $question = sanitize_text_field( $_POST['question'] );

        if ( empty( $question ) ) {
            wp_send_json_error( array(
                'message' => __( 'Please enter a question.', 'wp-chat-ai' )
            ) );
        }

        // Check if model is trained
        $training_status = get_option( 'wpcai_training_status', 'pending' );
        
        if ( $training_status !== 'completed' ) {
            wp_send_json_error( array(
                'message' => __( 'The AI model is still being trained. Please try again in a few minutes.', 'wp-chat-ai' )
            ) );
        }

        try {
            // Get answer using Naive Bayes
            $naive_bayes = new WPCAI_Naive_Bayes();
            $result = $naive_bayes->predict( $question );

            if ( $result  ) { // we have a problem here, the most answers have a too low confidence
                // Log the interaction
                WPCAI_Database::log_chat( array(
                    'user_question' => $question,
                    'matched_post_id' => $result['post_id'],
                    'confidence_score' => $result['confidence'],
                    'response_text' => $result['answer'],
                    'user_ip' => $this->get_user_ip(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT']
                ) );

                wp_send_json_success( array(
                    'answer' => $result['answer'],
                    'confidence' => $result['confidence'],
                    'source_title' => $result['source_title'],
                    'source_url' => $result['source_url']
                ) );
            } else {
                wp_send_json_error( array(
                    'message' => __( 'I\'m sorry, I couldn\'t find a relevant answer to your question. Please try rephrasing your question or contact us directly.', 'wp-chat-ai' )
                ) );
            }

        } catch ( Exception $e ) {
            error_log( 'WP Chat AI Error: ' . $e->getMessage() );
            
            wp_send_json_error( array(
                'message' => __( 'An error occurred while processing your question. Please try again later.', 'wp-chat-ai' )
            ) );
        }
    }

    /**
     * Handle train model AJAX request (admin only)
     */
    public function handle_train_model() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to perform this action.', 'wp-chat-ai' ) );
        }

        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'wpcai_train_model' ) ) {
            wp_die( __( 'Security check failed', 'wp-chat-ai' ) );
        }

        // Schedule background training
        wp_schedule_single_event( time() + 5, 'wpcai_train_model' );

        wp_send_json_success( array(
            'message' => __( 'Model training has been scheduled and will begin shortly.', 'wp-chat-ai' )
        ) );
    }

    /**
     * Handle get training status AJAX request
     */
    public function handle_get_training_status() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'wpcai_get_status' ) ) {
            wp_die( __( 'Security check failed', 'wp-chat-ai' ) );
        }

        $status = get_option( 'wpcai_training_status', 'pending' );
        $last_training = get_option( 'wpcai_last_training', '' );
        $total_posts = wp_count_posts()->publish;
        $trained_posts = count( WPCAI_Database::get_all_training_data() );

        wp_send_json_success( array(
            'status' => $status,
            'last_training' => $last_training,
            'total_posts' => $total_posts,
            'trained_posts' => $trained_posts,
            'progress' => $total_posts > 0 ? round( ( $trained_posts / $total_posts ) * 100 ) : 0
        ) );
    }

    /**
     * Train model in background
     */
    public function train_model_background() {
        try {
            update_option( 'wpcai_training_status', 'training' );

            // Get all published posts
            $args = array(
                'post_type' => 'any',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids'
            );

            $post_ids = get_posts( $args );

            if ( empty( $post_ids ) ) {
                update_option( 'wpcai_training_status', 'no_data' );
                return;
            }

            // Process posts in batches to avoid memory issues
            $batch_size = 50;
            $batches = array_chunk( $post_ids, $batch_size );

            foreach ( $batches as $batch ) {
                $this->process_post_batch( $batch );
                
                // Small delay to prevent overwhelming the server
                usleep( 100000 ); // 0.1 seconds
            }

            // Train the Naive Bayes model
            $naive_bayes = new WPCAI_Naive_Bayes();
            $naive_bayes->train();

            update_option( 'wpcai_training_status', 'completed' );
            update_option( 'wpcai_last_training', current_time( 'mysql' ) );

        } catch ( Exception $e ) {
            error_log( 'WP Chat AI Training Error: ' . $e->getMessage() );
            update_option( 'wpcai_training_status', 'error' );
        }
    }

    /**
     * Process a batch of posts for training
     *
     * @param array $post_ids Array of post IDs
     */
    private function process_post_batch( $post_ids ) {
        foreach ( $post_ids as $post_id ) {
            $post = get_post( $post_id );
            
            if ( ! $post ) {
                continue;
            }

            // Get post content
            $content = $post->post_content;
            $title = $post->post_title;
            
            // Apply content filters (like shortcodes)
            $content = apply_filters( 'the_content', $content );
            
            // Combine title and content
            $full_text = $title . ' ' . $content;
            
            // Tokenize the content
            $tokens = WPCAI_Tokenizer::tokenize( $full_text );
            
            if ( empty( $tokens ) ) {
                continue;
            }

            // Save training data
            WPCAI_Database::save_training_data( array(
                'post_id' => $post_id,
                'post_type' => $post->post_type,
                'post_title' => $title,
                'post_content' => $content,
                'tokens' => json_encode( $tokens )
            ) );

            // Update vocabulary
            $word_frequency = WPCAI_Tokenizer::get_word_frequency( $tokens );
            
            foreach ( $word_frequency as $word => $frequency ) {
                WPCAI_Database::save_vocabulary_word( $word, $frequency, 1 );
            }
        }
    }

    /**
     * Get user IP address
     *
     * @return string User IP address
     */
    private function get_user_ip() {
        $ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
        
        foreach ( $ip_keys as $key ) {
            if ( array_key_exists( $key, $_SERVER ) === true ) {
                foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
                    $ip = trim( $ip );
                    
                    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
}

