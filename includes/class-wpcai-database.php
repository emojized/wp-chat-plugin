<?php
/**
 * Database management class
 *
 * @package WP_Chat_AI
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WPCAI_Database class
 */
class WPCAI_Database {

    /**
     * Create plugin database tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Table for storing training data
        $training_table = $wpdb->prefix . 'wpcai_training_data';
        $training_sql = "CREATE TABLE $training_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            post_type varchar(20) NOT NULL,
            post_title text NOT NULL,
            post_content longtext NOT NULL,
            tokens longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY post_type (post_type)
        ) $charset_collate;";

        // Table for storing vocabulary
        $vocabulary_table = $wpdb->prefix . 'wpcai_vocabulary';
        $vocabulary_sql = "CREATE TABLE $vocabulary_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            word varchar(255) NOT NULL,
            frequency int(11) DEFAULT 0,
            document_frequency int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY word (word),
            KEY frequency (frequency)
        ) $charset_collate;";

        // Table for storing model data
        $model_table = $wpdb->prefix . 'wpcai_model_data';
        $model_sql = "CREATE TABLE $model_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            word varchar(255) NOT NULL,
            frequency int(11) DEFAULT 0,
            probability decimal(10,8) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY word (word),
            KEY probability (probability)
        ) $charset_collate;";

        // Table for storing chat logs
        $chat_logs_table = $wpdb->prefix . 'wpcai_chat_logs';
        $chat_logs_sql = "CREATE TABLE $chat_logs_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_question text NOT NULL,
            matched_post_id bigint(20),
            confidence_score decimal(5,4) DEFAULT 0,
            response_text longtext,
            user_ip varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY matched_post_id (matched_post_id),
            KEY confidence_score (confidence_score),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        
        dbDelta( $training_sql );
        dbDelta( $vocabulary_sql );
        dbDelta( $model_sql );
        dbDelta( $chat_logs_sql );
    }

    /**
     * Drop plugin database tables
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'wpcai_training_data',
            $wpdb->prefix . 'wpcai_vocabulary',
            $wpdb->prefix . 'wpcai_model_data',
            $wpdb->prefix . 'wpcai_chat_logs'
        );

        foreach ( $tables as $table ) {
            $wpdb->query( "DROP TABLE IF EXISTS $table" );
        }
    }

    /**
     * Get training data for a specific post
     *
     * @param int $post_id Post ID
     * @return object|null Training data object or null if not found
     */
    public static function get_training_data( $post_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'wpcai_training_data';
        
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE post_id = %d",
            $post_id
        ) );
    }

    /**
     * Insert or update training data
     *
     * @param array $data Training data
     * @return int|false Insert ID on success, false on failure
     */
    public static function save_training_data( $data ) {
        global $wpdb;

        $table = $wpdb->prefix . 'wpcai_training_data';
        
        $existing = self::get_training_data( $data['post_id'] );
        
        if ( $existing ) {
            return $wpdb->update(
                $table,
                array(
                    'post_type' => $data['post_type'],
                    'post_title' => $data['post_title'],
                    'post_content' => $data['post_content'],
                    'tokens' => $data['tokens'],
                ),
                array( 'post_id' => $data['post_id'] ),
                array( '%s', '%s', '%s', '%s' ),
                array( '%d' )
            );
        } else {
            return $wpdb->insert(
                $table,
                $data,
                array( '%d', '%s', '%s', '%s', '%s' )
            );
        }
    }

    /**
     * Get all training data
     *
     * @return array Array of training data objects
     */
    public static function get_all_training_data() {
        global $wpdb;

        $table = $wpdb->prefix . 'wpcai_training_data';
        
        return $wpdb->get_results( "SELECT * FROM $table ORDER BY updated_at DESC" );
    }

    /**
     * Save vocabulary word
     *
     * @param string $word Word to save
     * @param int $frequency Word frequency
     * @param int $doc_frequency Document frequency
     * @return int|false Insert ID on success, false on failure
     */
    public static function save_vocabulary_word( $word, $frequency = 1, $doc_frequency = 1 ) {
        global $wpdb;

        $table = $wpdb->prefix . 'wpcai_vocabulary';
        
        $existing = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE word = %s",
            $word
        ) );
        
        if ( $existing ) {
            return $wpdb->update(
                $table,
                array(
                    'frequency' => $existing->frequency + $frequency,
                    'document_frequency' => $existing->document_frequency + $doc_frequency,
                ),
                array( 'word' => $word ),
                array( '%d', '%d' ),
                array( '%s' )
            );
        } else {
            return $wpdb->insert(
                $table,
                array(
                    'word' => $word,
                    'frequency' => $frequency,
                    'document_frequency' => $doc_frequency,
                ),
                array( '%s', '%d', '%d' )
            );
        }
    }

    /**
     * Get vocabulary
     *
     * @param int $limit Limit number of results
     * @return array Array of vocabulary objects
     */
    public static function get_vocabulary( $limit = 0 ) {
        global $wpdb;

        $table = $wpdb->prefix . 'wpcai_vocabulary';
        
        $sql = "SELECT * FROM $table ORDER BY frequency DESC";
        
        if ( $limit > 0 ) {
            $sql .= $wpdb->prepare( " LIMIT %d", $limit );
        }
        
        return $wpdb->get_results( $sql );
    }

    /**
     * Save model data
     *
     * @param array $data Model data
     * @return int|false Insert ID on success, false on failure
     */
    public static function save_model_data( $data ) {
        global $wpdb;

        $table = $wpdb->prefix . 'wpcai_model_data';
        
        return $wpdb->insert(
            $table,
            $data,
            array( '%d', '%s', '%d', '%f' )
        );
    }

    /**
     * Clear model data
     */
    public static function clear_model_data() {
        global $wpdb;

        $table = $wpdb->prefix . 'wpcai_model_data';
        $wpdb->query( "TRUNCATE TABLE $table" );
    }

    /**
     * Get model data for a word
     *
     * @param string $word Word to search for
     * @return array Array of model data objects
     */
    public static function get_model_data_for_word( $word ) {
        global $wpdb;

        $table = $wpdb->prefix . 'wpcai_model_data';
        
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table WHERE word = %s ORDER BY probability DESC",
            $word
        ) );
    }

    /**
     * Log chat interaction
     *
     * @param array $data Chat log data
     * @return int|false Insert ID on success, false on failure
     */
    public static function log_chat( $data ) {
        global $wpdb;

        $table = $wpdb->prefix . 'wpcai_chat_logs';
        
        return $wpdb->insert(
            $table,
            $data,
            array( '%s', '%d', '%f', '%s', '%s', '%s' )
        );
    }

    /**
     * Get chat logs
     *
     * @param int $limit Limit number of results
     * @return array Array of chat log objects
     */
    public static function get_chat_logs( $limit = 50 ) {
        global $wpdb;

        $table = $wpdb->prefix . 'wpcai_chat_logs';
        
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d",
            $limit
        ) );
    }
}

