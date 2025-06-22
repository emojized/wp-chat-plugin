<?php
/**
 * Naive Bayes classifier implementation
 *
 * @package WP_Chat_AI
 */
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WPCAI_Naive_Bayes class
 */
class WPCAI_Naive_Bayes {
    /**
     * Vocabulary size for Laplace smoothing
     *
     * @var int
     */
    private $vocabulary_size = 0;

    /**
     * Total number of documents (posts)
     *
     * @var int
     */
    private $total_documents = 0;

    /**
     * Document frequencies for each post
     *
     * @var array
     */
    private $document_frequencies = array();

    /**
     * Word frequencies for each post
     *
     * @var array
     */
    private $word_frequencies = array();

    /**
     * Prior probabilities for each post
     *
     * @var array
     */
    private $prior_probabilities = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->load_model_data();
    }

    /**
     * Train the Naive Bayes model
     *
     * @return bool True on success, false on failure
     */
    public function train() {
        try {
            // Clear existing model data
            WPCAI_Database::clear_model_data();

            // Get all training data
            $training_data = WPCAI_Database::get_all_training_data();
            if ( empty( $training_data ) ) {
                return false;
            }

            $this->total_documents = count( $training_data );
            $vocabulary = array();
            $post_word_counts = array();
            $post_total_words = array();

            // First pass: collect vocabulary and word counts
            foreach ( $training_data as $data ) {
                $post_id = $data->post_id;
                $tokens = json_decode( $data->tokens, true );
                if ( empty( $tokens ) ) {
                    continue;
                }

                // Count word frequencies for this post
                $word_frequency = WPCAI_Tokenizer::get_word_frequency( $tokens );
                $post_word_counts[ $post_id ] = $word_frequency;
                $post_total_words[ $post_id ] = array_sum( $word_frequency );

                // Add words to vocabulary
                foreach ( $word_frequency as $word => $frequency ) {
                    if ( ! isset( $vocabulary[ $word ] ) ) {
                        $vocabulary[ $word ] = 0;
                    }
                    $vocabulary[ $word ] += $frequency;
                }
            }

            $this->vocabulary_size = count( $vocabulary );

            // Calculate prior probabilities
            foreach ( $training_data as $data ) {
                $this->prior_probabilities[ $data->post_id ] = 1.0 / max(1, $this->total_documents);
            }

            // Second pass: calculate word probabilities for each post
            foreach ( $training_data as $data ) {
                $post_id = $data->post_id;
                if ( ! isset( $post_word_counts[ $post_id ] ) ) {
                    continue;
                }

                $word_counts = $post_word_counts[ $post_id ];
                $total_words = $post_total_words[ $post_id ];

                foreach ( $vocabulary as $word => $global_frequency ) {
                    $word_count = isset( $word_counts[ $word ] ) ? $word_counts[ $word ] : 0;

                    // Apply Laplace smoothing
                    $probability = ( $word_count + 1 ) / ( $total_words + $this->vocabulary_size );

                    // Save to database
                    WPCAI_Database::save_model_data( array(
                        'post_id' => $post_id,
                        'word' => $word,
                        'frequency' => $word_count,
                        'probability' => $probability
                    ) );
                }
            }

            // Store model metadata
            $this->word_frequencies = $post_word_counts;
            $this->document_frequencies = $post_total_words;

            return true;
        } catch ( Exception $e ) {
            error_log( 'Naive Bayes Training Error: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Predict the best matching post for a given query
     *
     * @param string $query User query
     * @return array|false Prediction result or false on failure
     */
    public function predict( $query ) {
        if ( empty( $query ) ) {
            return false;
        }

        // Preprocess the query
        $query_tokens = WPCAI_Tokenizer::preprocess_query( $query );

        if ( empty( $query_tokens ) ) {
            error_log("No tokens extracted from query.");
            return false;
        }

        error_log("Query Tokens: " . print_r($query_tokens, true));

        // Get all training data
        $training_data = WPCAI_Database::get_all_training_data();
        if ( empty( $training_data ) ) {
            error_log("No training data found.");
            return false;
        }

        $post_scores = array();

        foreach ( $training_data as $data ) {
            $post_id = $data->post_id;
            $score = $this->calculate_post_score( $post_id, $query_tokens );

            if ( is_infinite( $score ) || is_nan( $score ) ) {
                error_log("Invalid score for post ID $post_id: $score");
                continue;
            }

            if ( $score > -INF && $score < INF ) {
                $post_scores[ $post_id ] = array(
                    'score' => $score,
                    'post_data' => $data
                );
            }
        }

        error_log("Post Scores: " . print_r($post_scores, true));

        if ( empty( $post_scores ) ) {
            error_log("No post scores were calculated.");
            return false;
        }

        // Sort by score (highest first)
        uasort( $post_scores, function( $a, $b ) {
            return $b['score'] <=> $a['score'];
        });

        // Get the best match
        $best_match = reset( $post_scores );
        $best_post_data = $best_match['post_data'];

        // Calculate confidence score (normalized)
        $confidence = $this->calculate_confidence( $best_match['score'], $post_scores );

        // Get post URL
        $post_url = get_permalink( $best_post_data->post_id );

        // Generate response text
        $response_text = $this->generate_response( $best_post_data, $query_tokens );

        return array(
            'post_id' => $best_post_data->post_id,
            'confidence' => $confidence,
            'answer' => $response_text,
            'source_title' => $best_post_data->post_title,
            'source_url' => $post_url,
            'raw_score' => $best_match['score']
        );
    }

    /**
     * Calculate Naive Bayes score for a specific post
     *
     * @param int $post_id Post ID
     * @param array $query_tokens Query tokens
     * @return float Score
     */
    private function calculate_post_score( $post_id, $query_tokens ) {
        $log_score = log( $this->prior_probabilities[ $post_id ] ?? ( 1.0 / max(1, $this->total_documents) ) );

        foreach ( $query_tokens as $token ) {
            $word_probability = 0.0;
            $word_data = WPCAI_Database::get_model_data_for_word( $token );

            if ( ! empty( $word_data ) ) {
                foreach ( $word_data as $data ) {
                    if ( $data->post_id == $post_id ) {
                        $word_probability = $data->probability;
                        break;
                    }
                }
            }

            // Fallback smoothing
            if ( $word_probability <= 0 ) {
                $word_probability = 1.0 / ( $this->vocabulary_size + 1 );
            }

            $log_score += log( $word_probability );
        }

        return $log_score;
    }

    /**
     * Load model data from database
     */
    private function load_model_data() {
        $vocabulary = WPCAI_Database::get_vocabulary();
        $this->vocabulary_size = count( $vocabulary );

        $training_data = WPCAI_Database::get_all_training_data();
        $this->total_documents = count( $training_data );

        $this->prior_probabilities = [];

        foreach ( $training_data as $data ) {
            $this->prior_probabilities[ $data->post_id ] = 1.0 / max(1, $this->total_documents);
        }
    }

    /**
     * Calculate confidence score
     */
    private function calculate_confidence( $best_score, $all_scores ) {
        if ( count( $all_scores ) < 2 ) {
            return 1.0;
        }

        $scores = array_column( $all_scores, 'score' );
        rsort( $scores );

        $best = $scores[0];
        $second_best = $scores[1];

        if ( $second_best == 0 ) {
            return 1.0;
        }

        $confidence = ( $best - $second_best ) / abs( $best );
        return max( 0.0, min( 1.0, $confidence ) );
    }

    /**
     * Generate response text from post content
     */
    private function generate_response( $post_data, $query_tokens ) {
        $content = wp_strip_all_tags( html_entity_decode( $post_data->post_content ) );
        $sentences = $this->extract_sentences( $content );
        $best_sentence = $this->find_best_sentence( $sentences, $query_tokens );

        $response = $best_sentence ?: wp_trim_words( $content, 50 );

        $max_length = get_option( 'wpcai_max_response_length', 500 );
        if ( strlen( $response ) > $max_length ) {
            $response = wp_trim_words( $response, $max_length / 6 );
        }

        $response .= "\n" . sprintf( __( 'Source: %s', 'wp-chat-ai' ), $post_data->post_title );

        return trim( $response );
    }

    /**
     * Extract sentences from text
     */
    private function extract_sentences( $text ) {
        $sentences = preg_split( '/[.!?]+/', $text );
        $cleaned = array();

        foreach ( $sentences as $sentence ) {
            $sentence = trim( $sentence );
            if ( strlen( $sentence ) > 20 ) {
                $cleaned[] = $sentence;
            }
        }

        return $cleaned;
    }

    /**
     * Find best sentence based on Jaccard similarity
     */
    private function find_best_sentence( $sentences, $query_tokens ) {
        $best_sentence = false;
        $best_score = 0;

        foreach ( $sentences as $sentence ) {
            $sentence_tokens = WPCAI_Tokenizer::tokenize( $sentence );
            $score = WPCAI_Tokenizer::calculate_jaccard_similarity( $query_tokens, $sentence_tokens );

            if ( $score > $best_score ) {
                $best_score = $score;
                $best_sentence = $sentence;
            }
        }

        return $best_score > 0.1 ? $best_sentence : false;
    }

    /**
     * Get model statistics
     */
    public function get_model_stats() {
        return array(
            'vocabulary_size' => $this->vocabulary_size,
            'total_documents' => $this->total_documents,
            'training_status' => get_option( 'wpcai_training_status', 'pending' ),
            'last_training' => get_option( 'wpcai_last_training', '' )
        );
    }

    /**
     * Evaluate model performance
     */
    public function evaluate( $test_queries ) {
        $correct_predictions = 0;
        $total_predictions = count( $test_queries );
        $results = array();

        foreach ( $test_queries as $query_data ) {
            $query = $query_data['query'];
            $expected_post_id = $query_data['expected_post_id'];
            $prediction = $this->predict( $query );

            if ( $prediction && $prediction['post_id'] == $expected_post_id ) {
                $correct_predictions++;
                $results[] = array(
                    'query' => $query,
                    'expected' => $expected_post_id,
                    'predicted' => $prediction['post_id'],
                    'confidence' => $prediction['confidence'],
                    'correct' => true
                );
            } else {
                $results[] = array(
                    'query' => $query,
                    'expected' => $expected_post_id,
                    'predicted' => $prediction ? $prediction['post_id'] : null,
                    'confidence' => $prediction ? $prediction['confidence'] : 0,
                    'correct' => false
                );
            }
        }

        return array(
            'accuracy' => $total_predictions > 0 ? $correct_predictions / $total_predictions : 0,
            'correct_predictions' => $correct_predictions,
            'total_predictions' => $total_predictions,
            'detailed_results' => $results
        );
    }

    /**
     * Get similar posts based on content similarity
     */
    public function get_similar_posts( $post_id, $limit = 5 ) {
        $reference_data = WPCAI_Database::get_training_data( $post_id );
        if ( ! $reference_data ) {
            return array();
        }

        $reference_tokens = json_decode( $reference_data->tokens, true );
        if ( empty( $reference_tokens ) ) {
            return array();
        }

        $all_training_data = WPCAI_Database::get_all_training_data();
        $similarities = array();

        foreach ( $all_training_data as $data ) {
            if ( $data->post_id == $post_id ) {
                continue; // Skip self
            }

            $tokens = json_decode( $data->tokens, true );
            if ( empty( $tokens ) ) {
                continue;
            }

            $similarity = WPCAI_Tokenizer::calculate_jaccard_similarity( $reference_tokens, $tokens );
            if ( $similarity > 0 ) {
                $similarities[] = array(
                    'post_id' => $data->post_id,
                    'post_title' => $data->post_title,
                    'similarity' => $similarity,
                    'post_url' => get_permalink( $data->post_id )
                );
            }
        }

        usort( $similarities, function( $a, $b ) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return array_slice( $similarities, 0, $limit );
    }
}