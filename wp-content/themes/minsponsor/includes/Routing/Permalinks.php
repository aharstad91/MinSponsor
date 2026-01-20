<?php
/**
 * Custom Permalinks and Routing
 *
 * Handles pretty permalinks and rewrite rules for Klubb/Lag/Spiller.
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Routing;

if (!defined('ABSPATH')) {
    exit;
}

class Permalinks {

    /**
     * Slug cache for performance
     */
    private static array $slug_cache = [];

    /**
     * Initialize routing
     */
    public static function init(): void {
        add_action('init', [self::class, 'add_rewrite_rules']);
        add_filter('query_vars', [self::class, 'add_query_vars']);
        add_filter('post_type_link', [self::class, 'generate_permalink'], 10, 2);
        add_action('template_redirect', [self::class, 'canonical_redirect']);
        add_action('after_switch_theme', [self::class, 'flush_rewrite_rules']);
        add_action('pre_get_posts', [self::class, 'disambiguate_queries']);
    }

    /**
     * Add custom rewrite rules
     */
    public static function add_rewrite_rules(): void {
        // Base /stott/ URL
        add_rewrite_rule(
            '^stott/?$',
            'index.php?pagename=stott',
            'top'
        );

        // /stott/{klubb}/{lag}/{spiller}/
        add_rewrite_rule(
            '^stott/([^/]+)/([^/]+)/([^/]+)/?$',
            'index.php?post_type=spiller&name=$matches[3]&klubb_slug=$matches[1]&lag_slug=$matches[2]',
            'top'
        );

        // /stott/{klubb}/{lag}/
        add_rewrite_rule(
            '^stott/([^/]+)/([^/]+)/?$',
            'index.php?post_type=lag&name=$matches[2]&klubb_slug=$matches[1]',
            'top'
        );

        // /stott/{klubb}/
        add_rewrite_rule(
            '^stott/([^/]+)/?$',
            'index.php?post_type=klubb&name=$matches[1]',
            'top'
        );
    }

    /**
     * Add custom query vars
     *
     * @param array $vars Existing query vars
     * @return array Modified query vars
     */
    public static function add_query_vars(array $vars): array {
        $vars[] = 'klubb_slug';
        $vars[] = 'lag_slug';
        return $vars;
    }

    /**
     * Generate pretty permalinks
     *
     * @param string $permalink Default permalink
     * @param \WP_Post $post Post object
     * @return string Pretty permalink
     */
    public static function generate_permalink(string $permalink, $post): string {
        if (!is_object($post) || !isset($post->post_type)) {
            return $permalink;
        }

        $home_url = trailingslashit(home_url());

        switch ($post->post_type) {
            case 'klubb':
                return $home_url . 'stott/' . $post->post_name . '/';

            case 'lag':
                $klubb_id = self::get_parent_klubb_id($post->ID);
                if (!$klubb_id) {
                    return $permalink;
                }
                $klubb_slug = self::get_post_slug($klubb_id);
                if (!$klubb_slug) {
                    return $permalink;
                }
                return $home_url . 'stott/' . $klubb_slug . '/' . $post->post_name . '/';

            case 'spiller':
                $lag_id = self::get_parent_lag_id($post->ID);
                if (!$lag_id) {
                    return $permalink;
                }
                $klubb_id = self::get_parent_klubb_id($lag_id);
                if (!$klubb_id) {
                    return $permalink;
                }
                $lag_slug = self::get_post_slug($lag_id);
                $klubb_slug = self::get_post_slug($klubb_id);
                if (!$lag_slug || !$klubb_slug) {
                    return $permalink;
                }
                return $home_url . 'stott/' . $klubb_slug . '/' . $lag_slug . '/' . $post->post_name . '/';
        }

        return $permalink;
    }

    /**
     * Handle canonical redirects
     */
    public static function canonical_redirect(): void {
        if (!is_singular(['lag', 'spiller'])) {
            return;
        }

        $post = get_queried_object();
        if (!$post) {
            return;
        }

        $klubb_slug_from_url = get_query_var('klubb_slug');
        $lag_slug_from_url = get_query_var('lag_slug');

        if (empty($klubb_slug_from_url)) {
            return;
        }

        $should_redirect = false;

        if ($post->post_type === 'lag') {
            $actual_klubb_id = self::get_parent_klubb_id($post->ID);
            if ($actual_klubb_id) {
                $actual_klubb_slug = self::get_post_slug($actual_klubb_id);
                if ($actual_klubb_slug && $actual_klubb_slug !== $klubb_slug_from_url) {
                    $should_redirect = true;
                }
            }
        } elseif ($post->post_type === 'spiller') {
            $actual_lag_id = self::get_parent_lag_id($post->ID);
            if ($actual_lag_id) {
                $actual_lag_slug = self::get_post_slug($actual_lag_id);
                $actual_klubb_id = self::get_parent_klubb_id($actual_lag_id);
                $actual_klubb_slug = $actual_klubb_id ? self::get_post_slug($actual_klubb_id) : null;

                if (($actual_klubb_slug && $actual_klubb_slug !== $klubb_slug_from_url) ||
                    ($actual_lag_slug && $actual_lag_slug !== $lag_slug_from_url)) {
                    $should_redirect = true;
                }
            }
        }

        if ($should_redirect) {
            $correct_url = get_permalink($post);
            wp_redirect($correct_url, 301);
            exit;
        }
    }

    /**
     * Disambiguate queries for colliding slugs
     *
     * @param \WP_Query $q Query object
     */
    public static function disambiguate_queries(\WP_Query $q): void {
        if (is_admin() || !$q->is_main_query()) {
            return;
        }

        $pt = $q->get('post_type');
        $name = $q->get('name');
        $klubb_slug = $q->get('klubb_slug');
        $lag_slug = $q->get('lag_slug');

        // /stott/{klubb}/{lag}/ -> post_type=lag
        if ($pt === 'lag' && $name && $klubb_slug) {
            $klubb = get_page_by_path($klubb_slug, OBJECT, 'klubb');
            if ($klubb) {
                $mq = (array) $q->get('meta_query');
                $mq[] = ['key' => 'parent_klubb', 'value' => (int) $klubb->ID, 'compare' => '='];
                $q->set('meta_query', $mq);
                $q->set('posts_per_page', 1);
            }
        }

        // /stott/{klubb}/{lag}/{spiller}/ -> post_type=spiller
        if ($pt === 'spiller' && $name && $klubb_slug && $lag_slug) {
            $klubb = get_page_by_path($klubb_slug, OBJECT, 'klubb');
            $lag_id = 0;

            if ($klubb) {
                $lag_q = new \WP_Query([
                    'post_type' => 'lag',
                    'name' => $lag_slug,
                    'meta_query' => [
                        [
                            'key' => 'parent_klubb',
                            'value' => (int) $klubb->ID,
                            'compare' => '=',
                        ]
                    ],
                    'post_status' => 'any',
                    'fields' => 'ids',
                    'no_found_rows' => true,
                    'posts_per_page' => 1,
                    'suppress_filters' => true,
                ]);
                if ($lag_q->have_posts()) {
                    $lag_id = (int) $lag_q->posts[0];
                }
                wp_reset_postdata();
            }

            if ($lag_id) {
                $mq = (array) $q->get('meta_query');
                $mq[] = ['key' => 'parent_lag', 'value' => $lag_id, 'compare' => '='];
                $q->set('meta_query', $mq);
                $q->set('posts_per_page', 1);
            }
        }
    }

    /**
     * Flush rewrite rules
     */
    public static function flush_rewrite_rules(): void {
        self::add_rewrite_rules();
        flush_rewrite_rules();
    }

    /**
     * Get parent klubb ID for a lag
     *
     * @param int $lag_id Lag post ID
     * @return int|false Parent klubb ID or false
     */
    public static function get_parent_klubb_id(int $lag_id) {
        if (!$lag_id) {
            return false;
        }

        $parent_klubb = get_post_meta($lag_id, 'parent_klubb', true);
        return $parent_klubb ? (int) $parent_klubb : false;
    }

    /**
     * Get parent lag ID for a spiller
     *
     * @param int $spiller_id Spiller post ID
     * @return int|false Parent lag ID or false
     */
    public static function get_parent_lag_id(int $spiller_id) {
        if (!$spiller_id) {
            return false;
        }

        $parent_lag = get_post_meta($spiller_id, 'parent_lag', true);
        return $parent_lag ? (int) $parent_lag : false;
    }

    /**
     * Get post slug with caching
     *
     * @param int $post_id Post ID
     * @return string|false Post slug or false
     */
    public static function get_post_slug(int $post_id) {
        if (!$post_id) {
            return false;
        }

        if (isset(self::$slug_cache[$post_id])) {
            return self::$slug_cache[$post_id];
        }

        $post = get_post($post_id);
        if (!$post) {
            self::$slug_cache[$post_id] = false;
            return false;
        }

        self::$slug_cache[$post_id] = $post->post_name;
        return $post->post_name;
    }
}
