<?php
/**
 * Text tokenization class
 *
 * @package WP_Chat_AI
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WPCAI_Tokenizer class
 */
class WPCAI_Tokenizer {

    /**
     * Stop words to remove during tokenization
     *
     * @var array
     */
    private static $stop_words = array(
        'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from',
        'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the',
        'to', 'was', 'will', 'with', 'would', 'you', 'your', 'yours',
        'i', 'me', 'my', 'myself', 'we', 'our', 'ours', 'ourselves',
        'they', 'them', 'their', 'theirs', 'themselves', 'what', 'which',
        'who', 'whom', 'this', 'that', 'these', 'those', 'am', 'is', 'are',
        'was', 'were', 'being', 'been', 'have', 'has', 'had', 'having',
        'do', 'does', 'did', 'doing', 'would', 'should', 'could', 'ought',
        'im', 'youre', 'hes', 'shes', 'its', 'were', 'theyre', 'ive',
        'youve', 'weve', 'theyve', 'isnt', 'arent', 'wasnt', 'werent',
        'hasnt', 'havent', 'hadnt', 'wont', 'wouldnt', 'dont', 'doesnt',
        'didnt', 'cant', 'couldnt', 'shouldnt', 'mustnt', 'neednt',
        'daren\'t', 'mayn\'t', 'might', 'must', 'shall', 'should', 'will',
        'would', 'can', 'could', 'may', 'might', 'must', 'ought', 'shall',
        'should', 'will', 'would'
    );

    /**
     * Tokenize text content
     *
     * @param string $text Text to tokenize
     * @param bool $remove_stop_words Whether to remove stop words
     * @param int $min_length Minimum word length to include
     * @return array Array of tokens
     */
    public static function tokenize( $text, $remove_stop_words = true, $min_length = 2 ) {
        if ( empty( $text ) ) {
            return array();
        }

        // Remove HTML tags
        $text = wp_strip_all_tags( $text );

        // Convert to lowercase
        $text = strtolower( $text );

        // Remove URLs
        $text = preg_replace( '/https?:\/\/[^\s]+/', '', $text );

        // Remove email addresses
        $text = preg_replace( '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '', $text );

        // Remove numbers (optional - you might want to keep some numbers)
        $text = preg_replace( '/\b\d+\b/', '', $text );

        // Remove punctuation and special characters, keep only letters and spaces
        $text = preg_replace( '/[^a-z\s]/', ' ', $text );

        // Split into words
        $tokens = preg_split( '/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY );

        // Filter by minimum length
        if ( $min_length > 0 ) {
            $tokens = array_filter( $tokens, function( $token ) use ( $min_length ) {
                return strlen( $token ) >= $min_length;
            });
        }

        // Remove stop words
        if ( $remove_stop_words ) {
            $tokens = array_diff( $tokens, self::$stop_words );
        }

        // Apply stemming (basic)
        $tokens = array_map( array( self::class, 'stem_word' ), $tokens );

        // Remove duplicates and re-index
        $tokens = array_values( array_unique( $tokens ) );

        return $tokens;
    }

    /**
     * Get word frequency from tokens
     *
     * @param array $tokens Array of tokens
     * @return array Associative array of word => frequency
     */
    public static function get_word_frequency( $tokens ) {
        return array_count_values( $tokens );
    }

    /**
     * Basic stemming function
     * This is a simplified stemming algorithm
     *
     * @param string $word Word to stem
     * @return string Stemmed word
     */
    public static function stem_word( $word ) {
        // Skip very short words
        if ( strlen( $word ) <= 3 ) {
            return $word;
        }

        // Common suffix removal rules
        $suffixes = array(
            'ies' => 'y',
            'ied' => 'y',
            'ying' => 'y',
            'ing' => '',
            'ly' => '',
            'ed' => '',
            'ies' => 'y',
            'ied' => 'y',
            'ies' => 'y',
            'ied' => 'y',
            's' => ''
        );

        foreach ( $suffixes as $suffix => $replacement ) {
            if ( substr( $word, -strlen( $suffix ) ) === $suffix ) {
                $stem = substr( $word, 0, -strlen( $suffix ) ) . $replacement;
                // Ensure the stem is not too short
                if ( strlen( $stem ) >= 3 ) {
                    return $stem;
                }
            }
        }

        return $word;
    }

    /**
     * Calculate TF-IDF scores for tokens
     *
     * @param array $tokens Document tokens
     * @param array $all_documents All documents for IDF calculation
     * @return array TF-IDF scores
     */
    public static function calculate_tfidf( $tokens, $all_documents ) {
        $tf_scores = array();
        $token_count = count( $tokens );
        $total_documents = count( $all_documents );

        // Calculate term frequency (TF)
        $word_frequency = self::get_word_frequency( $tokens );
        
        foreach ( $word_frequency as $word => $frequency ) {
            $tf_scores[ $word ] = $frequency / $token_count;
        }

        // Calculate TF-IDF
        $tfidf_scores = array();
        
        foreach ( $tf_scores as $word => $tf ) {
            // Count documents containing this word
            $documents_with_word = 0;
            
            foreach ( $all_documents as $doc_tokens ) {
                if ( in_array( $word, $doc_tokens ) ) {
                    $documents_with_word++;
                }
            }
            
            // Calculate IDF
            $idf = log( $total_documents / ( $documents_with_word + 1 ) );
            
            // Calculate TF-IDF
            $tfidf_scores[ $word ] = $tf * $idf;
        }

        return $tfidf_scores;
    }

    /**
     * Extract keywords from text using TF-IDF
     *
     * @param string $text Text to extract keywords from
     * @param array $all_documents All documents for comparison
     * @param int $max_keywords Maximum number of keywords to return
     * @return array Array of keywords with scores
     */
    public static function extract_keywords( $text, $all_documents, $max_keywords = 10 ) {
        $tokens = self::tokenize( $text );
        
        if ( empty( $tokens ) ) {
            return array();
        }

        $tfidf_scores = self::calculate_tfidf( $tokens, $all_documents );
        
        // Sort by TF-IDF score in descending order
        arsort( $tfidf_scores );
        
        // Return top keywords
        return array_slice( $tfidf_scores, 0, $max_keywords, true );
    }

    /**
     * Calculate similarity between two sets of tokens using Jaccard similarity
     *
     * @param array $tokens1 First set of tokens
     * @param array $tokens2 Second set of tokens
     * @return float Similarity score between 0 and 1
     */
    public static function calculate_jaccard_similarity( $tokens1, $tokens2 ) {
        if ( empty( $tokens1 ) && empty( $tokens2 ) ) {
            return 1.0;
        }

        if ( empty( $tokens1 ) || empty( $tokens2 ) ) {
            return 0.0;
        }

        $intersection = array_intersect( $tokens1, $tokens2 );
        $union = array_unique( array_merge( $tokens1, $tokens2 ) );

        return count( $intersection ) / count( $union );
    }

    /**
     * Calculate cosine similarity between two frequency vectors
     *
     * @param array $freq1 First frequency array
     * @param array $freq2 Second frequency array
     * @return float Similarity score between 0 and 1
     */
    public static function calculate_cosine_similarity( $freq1, $freq2 ) {
        // Get all unique words
        $all_words = array_unique( array_merge( array_keys( $freq1 ), array_keys( $freq2 ) ) );

        if ( empty( $all_words ) ) {
            return 0.0;
        }

        $dot_product = 0;
        $norm1 = 0;
        $norm2 = 0;

        foreach ( $all_words as $word ) {
            $val1 = isset( $freq1[ $word ] ) ? $freq1[ $word ] : 0;
            $val2 = isset( $freq2[ $word ] ) ? $freq2[ $word ] : 0;

            $dot_product += $val1 * $val2;
            $norm1 += $val1 * $val1;
            $norm2 += $val2 * $val2;
        }

        $norm1 = sqrt( $norm1 );
        $norm2 = sqrt( $norm2 );

        if ( $norm1 == 0 || $norm2 == 0 ) {
            return 0.0;
        }

        return $dot_product / ( $norm1 * $norm2 );
    }

    /**
     * Preprocess query for better matching
     *
     * @param string $query User query
     * @return array Processed query tokens
     */
    public static function preprocess_query( $query ) {
        // Remove question words that don't add semantic value
        $question_words = array( 'what', 'where', 'when', 'why', 'how', 'which', 'who', 'whom' );
        
        $tokens = self::tokenize( $query, true, 2 );
        
        // Remove question words
        $tokens = array_diff( $tokens, $question_words );
        
        return array_values( $tokens );
    }

    /**
     * Get stop words list
     *
     * @return array Array of stop words
     */
    public static function get_stop_words() {
        return self::$stop_words;
    }

    /**
     * Add custom stop words
     *
     * @param array $words Array of words to add to stop words list
     */
    public static function add_stop_words( $words ) {
        self::$stop_words = array_unique( array_merge( self::$stop_words, $words ) );
    }
}

