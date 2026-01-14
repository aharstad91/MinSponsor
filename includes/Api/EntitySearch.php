<?php
/**
 * Entity Search REST API Endpoint
 * 
 * GitHub-style typeahead/autocomplete for klubb/lag/spiller (club/team/player)
 * 
 * @package MinSponsor
 */

namespace MinSponsor\Api;

class EntitySearch {
    
    /**
     * Initialize the API
     */
    public static function init() {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }
    
    /**
     * Register REST API routes
     */
    public static function register_routes() {
        register_rest_route('minsponsor/v1', '/entity-search', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'handle_search'],
            'permission_callback' => '__return_true', // Public endpoint
            'args'                => [
                'q' => [
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($param) {
                        return strlen($param) >= 2;
                    }
                ],
                'limit' => [
                    'default'           => 8,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return $param >= 1 && $param <= 20;
                    }
                ],
                'type' => [
                    'default'           => 'all',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($param) {
                        return in_array($param, ['all', 'klubb', 'lag', 'spiller']);
                    }
                ]
            ]
        ]);
    }
    
    /**
     * Handle search request
     */
    public static function handle_search(\WP_REST_Request $request) {
        $query = $request->get_param('q');
        $limit = $request->get_param('limit');
        $type  = $request->get_param('type');
        
        $results = [];
        
        // Define post types to search
        $post_types = [];
        if ($type === 'all') {
            $post_types = ['klubb', 'lag', 'spiller'];
        } else {
            $post_types = [$type];
        }
        
        // Collect all results with relevance scoring
        $all_matches = [];
        
        foreach ($post_types as $post_type) {
            $posts = self::search_post_type($post_type, $query, $limit * 2);
            
            foreach ($posts as $post) {
                $score = self::calculate_relevance($post->post_title, $query);
                $all_matches[] = [
                    'post'  => $post,
                    'type'  => $post_type,
                    'score' => $score
                ];
            }
        }
        
        // Sort by relevance score (higher = better)
        usort($all_matches, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        // Take top results
        $all_matches = array_slice($all_matches, 0, $limit);
        
        // Format results
        foreach ($all_matches as $match) {
            $results[] = self::format_result($match['post'], $match['type']);
        }
        
        return rest_ensure_response([
            'query'   => $query,
            'results' => $results
        ]);
    }
    
    /**
     * Search a specific post type
     */
    private static function search_post_type($post_type, $query, $limit) {
        global $wpdb;
        
        // Use LIKE for flexible matching
        $like_query = '%' . $wpdb->esc_like($query) . '%';
        
        $sql = $wpdb->prepare(
            "SELECT ID, post_title, post_name 
             FROM {$wpdb->posts} 
             WHERE post_type = %s 
               AND post_status = 'publish' 
               AND post_title LIKE %s 
             ORDER BY 
               CASE 
                 WHEN post_title LIKE %s THEN 1  -- Prefix match
                 WHEN post_title LIKE %s THEN 2  -- Word prefix
                 ELSE 3                           -- Contains
               END,
               post_title ASC
             LIMIT %d",
            $post_type,
            $like_query,
            $wpdb->esc_like($query) . '%',      // Prefix
            '% ' . $wpdb->esc_like($query) . '%', // Word prefix
            $limit
        );
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Calculate relevance score
     */
    private static function calculate_relevance($title, $query) {
        $title_lower = mb_strtolower($title);
        $query_lower = mb_strtolower($query);
        
        // Exact match = highest score
        if ($title_lower === $query_lower) {
            return 100;
        }
        
        // Prefix match
        if (strpos($title_lower, $query_lower) === 0) {
            return 80;
        }
        
        // Word prefix match
        if (preg_match('/\b' . preg_quote($query_lower, '/') . '/', $title_lower)) {
            return 60;
        }
        
        // Contains
        if (strpos($title_lower, $query_lower) !== false) {
            return 40;
        }
        
        return 0;
    }
    
    /**
     * Format a single result
     */
    private static function format_result($post, $type) {
        $result = [
            'id'       => $post->ID,
            'type'     => $type,
            'name'     => $post->post_title,
            'subLabel' => self::get_type_label($type),
            'url'      => self::get_entity_url($post, $type)
        ];
        
        // Add parent info for teams and players
        if ($type === 'lag') {
            $parent_klubb_id = get_post_meta($post->ID, 'parent_klubb', true);
            if ($parent_klubb_id) {
                $parent = get_post($parent_klubb_id);
                if ($parent) {
                    $result['subLabel'] = $parent->post_title;
                }
            }
        } elseif ($type === 'spiller') {
            $parent_lag_id = get_post_meta($post->ID, 'parent_lag', true);
            if ($parent_lag_id) {
                $parent = get_post($parent_lag_id);
                if ($parent) {
                    $result['subLabel'] = $parent->post_title;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Get type label in Norwegian
     */
    private static function get_type_label($type) {
        $labels = [
            'klubb'   => 'Klubb',
            'lag'     => 'Lag',
            'spiller' => 'Utøver'
        ];
        return $labels[$type] ?? $type;
    }
    
    /**
     * Build the /stott/ URL for an entity
     */
    private static function get_entity_url($post, $type) {
        $base = home_url('/stott/');
        
        switch ($type) {
            case 'klubb':
                return $base . $post->post_name . '/';
                
            case 'lag':
                $parent_klubb_id = get_post_meta($post->ID, 'parent_klubb', true);
                if ($parent_klubb_id) {
                    $parent = get_post($parent_klubb_id);
                    if ($parent) {
                        return $base . $parent->post_name . '/' . $post->post_name . '/';
                    }
                }
                return $base . $post->post_name . '/';
                
            case 'spiller':
                $parent_lag_id = get_post_meta($post->ID, 'parent_lag', true);
                if ($parent_lag_id) {
                    $lag = get_post($parent_lag_id);
                    if ($lag) {
                        $parent_klubb_id = get_post_meta($lag->ID, 'parent_klubb', true);
                        if ($parent_klubb_id) {
                            $klubb = get_post($parent_klubb_id);
                            if ($klubb) {
                                return $base . $klubb->post_name . '/' . $lag->post_name . '/' . $post->post_name . '/';
                            }
                        }
                        return $base . $lag->post_name . '/' . $post->post_name . '/';
                    }
                }
                return $base . $post->post_name . '/';
                
            default:
                return $base;
        }
    }
}
