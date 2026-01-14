<?php
/**
 * Data Integrity Protection
 *
 * Handles cascade delete protection and parent relationship validation.
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\CPT;

if (!defined('ABSPATH')) {
    exit;
}

class DataIntegrity {

    /**
     * Initialize data integrity hooks
     */
    public static function init(): void {
        add_action('before_delete_post', [self::class, 'prevent_orphan_deletion']);
        add_action('save_post_lag', [self::class, 'validate_parent_relationships'], 20, 2);
        add_action('save_post_spiller', [self::class, 'validate_parent_relationships'], 20, 2);
    }

    /**
     * Prevent deletion of Klubb/Lag if they have child posts
     *
     * @param int $post_id Post ID being deleted
     */
    public static function prevent_orphan_deletion(int $post_id): void {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        // Check if Klubb has Lag children
        if ($post->post_type === 'klubb') {
            $children = get_posts([
                'post_type' => 'lag',
                'meta_query' => [
                    [
                        'key' => 'parent_klubb',
                        'value' => $post_id,
                        'compare' => '=',
                    ]
                ],
                'posts_per_page' => 1,
                'fields' => 'ids',
                'post_status' => 'any',
            ]);

            if (!empty($children)) {
                $count = count(get_posts([
                    'post_type' => 'lag',
                    'meta_query' => [
                        [
                            'key' => 'parent_klubb',
                            'value' => $post_id,
                            'compare' => '=',
                        ]
                    ],
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'post_status' => 'any',
                ]));
                wp_die(
                    sprintf(
                        __('Kan ikke slette klubben "%s" fordi den har %d tilknyttede lag. Slett lagene forst, eller flytt dem til en annen klubb.', 'minsponsor'),
                        esc_html($post->post_title),
                        $count
                    ),
                    __('Sletting ikke tillatt', 'minsponsor'),
                    ['back_link' => true]
                );
            }
        }

        // Check if Lag has Spiller children
        if ($post->post_type === 'lag') {
            $children = get_posts([
                'post_type' => 'spiller',
                'meta_query' => [
                    [
                        'key' => 'parent_lag',
                        'value' => $post_id,
                        'compare' => '=',
                    ]
                ],
                'posts_per_page' => 1,
                'fields' => 'ids',
                'post_status' => 'any',
            ]);

            if (!empty($children)) {
                $count = count(get_posts([
                    'post_type' => 'spiller',
                    'meta_query' => [
                        [
                            'key' => 'parent_lag',
                            'value' => $post_id,
                            'compare' => '=',
                        ]
                    ],
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'post_status' => 'any',
                ]));
                wp_die(
                    sprintf(
                        __('Kan ikke slette laget "%s" fordi det har %d tilknyttede spillere. Slett spillerne forst, eller flytt dem til et annet lag.', 'minsponsor'),
                        esc_html($post->post_title),
                        $count
                    ),
                    __('Sletting ikke tillatt', 'minsponsor'),
                    ['back_link' => true]
                );
            }
        }
    }

    /**
     * Validate parent relationships when saving Lag/Spiller
     *
     * @param int $post_id Post ID being saved
     * @param \WP_Post $post Post object
     */
    public static function validate_parent_relationships(int $post_id, \WP_Post $post): void {
        // Skip autosaves and revisions
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        // Validate Lag -> Klubb relationship
        if ($post->post_type === 'lag') {
            $parent_klubb = get_post_meta($post_id, 'parent_klubb', true);
            if ($parent_klubb && get_post_type($parent_klubb) !== 'klubb') {
                delete_post_meta($post_id, 'parent_klubb');
                error_log("MinSponsor: Removed invalid parent_klubb reference from Lag {$post_id}");
            }
        }

        // Validate Spiller -> Lag relationship
        if ($post->post_type === 'spiller') {
            $parent_lag = get_post_meta($post_id, 'parent_lag', true);
            if ($parent_lag && get_post_type($parent_lag) !== 'lag') {
                delete_post_meta($post_id, 'parent_lag');
                error_log("MinSponsor: Removed invalid parent_lag reference from Spiller {$post_id}");
            }
        }
    }
}
