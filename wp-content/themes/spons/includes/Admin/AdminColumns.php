<?php
/**
 * Admin List Table Columns
 *
 * Custom columns and filters for Lag and Spiller admin lists.
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Admin;

use MinSponsor\Routing\Permalinks;

if (!defined('ABSPATH')) {
    exit;
}

class AdminColumns {

    /**
     * Initialize admin columns
     */
    public static function init(): void {
        // Lag columns
        add_filter('manage_lag_posts_columns', [self::class, 'lag_columns']);
        add_action('manage_lag_posts_custom_column', [self::class, 'lag_column_content'], 10, 2);
        add_action('restrict_manage_posts', [self::class, 'lag_filters']);
        add_action('pre_get_posts', [self::class, 'lag_filter_query']);

        // Spiller columns
        add_filter('manage_spiller_posts_columns', [self::class, 'spiller_columns']);
        add_action('manage_spiller_posts_custom_column', [self::class, 'spiller_column_content'], 10, 2);
        add_action('restrict_manage_posts', [self::class, 'spiller_filters']);
        add_action('pre_get_posts', [self::class, 'spiller_filter_query']);
    }

    /**
     * Add custom columns for lag
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public static function lag_columns(array $columns): array {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['parent_klubb'] = 'Klubb';
                $new_columns['idrettsgren'] = 'Idrettsgren';
            }
        }
        return $new_columns;
    }

    /**
     * Populate custom columns for lag
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public static function lag_column_content(string $column, int $post_id): void {
        switch ($column) {
            case 'parent_klubb':
                $klubb_id = Permalinks::get_parent_klubb_id($post_id);
                if ($klubb_id) {
                    $klubb = get_post($klubb_id);
                    if ($klubb) {
                        $edit_url = get_edit_post_link($klubb_id);
                        echo '<a href="' . esc_url($edit_url) . '">' . esc_html($klubb->post_title) . '</a>';
                    } else {
                        echo '—';
                    }
                } else {
                    echo '—';
                }
                break;

            case 'idrettsgren':
                $terms = get_the_terms($post_id, 'idrettsgren');
                if ($terms && !is_wp_error($terms)) {
                    $term_names = [];
                    foreach ($terms as $term) {
                        $term_names[] = esc_html($term->name);
                    }
                    echo implode(', ', $term_names);
                } else {
                    echo '—';
                }
                break;
        }
    }

    /**
     * Add custom columns for spiller
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public static function spiller_columns(array $columns): array {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['parent_lag'] = 'Lag';
                $new_columns['parent_klubb'] = 'Klubb';
            }
        }
        return $new_columns;
    }

    /**
     * Populate custom columns for spiller
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public static function spiller_column_content(string $column, int $post_id): void {
        switch ($column) {
            case 'parent_lag':
                $lag_id = Permalinks::get_parent_lag_id($post_id);
                if ($lag_id) {
                    $lag = get_post($lag_id);
                    if ($lag) {
                        $edit_url = get_edit_post_link($lag_id);
                        echo '<a href="' . esc_url($edit_url) . '">' . esc_html($lag->post_title) . '</a>';
                    } else {
                        echo '—';
                    }
                } else {
                    echo '—';
                }
                break;

            case 'parent_klubb':
                $lag_id = Permalinks::get_parent_lag_id($post_id);
                if ($lag_id) {
                    $klubb_id = Permalinks::get_parent_klubb_id($lag_id);
                    if ($klubb_id) {
                        $klubb = get_post($klubb_id);
                        if ($klubb) {
                            $edit_url = get_edit_post_link($klubb_id);
                            echo '<a href="' . esc_url($edit_url) . '">' . esc_html($klubb->post_title) . '</a>';
                        } else {
                            echo '—';
                        }
                    } else {
                        echo '—';
                    }
                } else {
                    echo '—';
                }
                break;
        }
    }

    /**
     * Add filters for lag list
     */
    public static function lag_filters(): void {
        global $typenow;

        if ($typenow !== 'lag') {
            return;
        }

        $klubber = get_posts([
            'post_type' => 'klubb',
            'posts_per_page' => 100, // Limit for performance
            'post_status' => 'any',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $selected_klubb = isset($_GET['klubb_filter']) ? (int) $_GET['klubb_filter'] : 0;

        echo '<select name="klubb_filter">';
        echo '<option value="">Alle klubber</option>';
        foreach ($klubber as $klubb) {
            $selected = selected($selected_klubb, $klubb->ID, false);
            echo '<option value="' . esc_attr($klubb->ID) . '"' . $selected . '>' . esc_html($klubb->post_title) . '</option>';
        }
        echo '</select>';
    }

    /**
     * Apply filters for lag list
     *
     * @param \WP_Query $query Query object
     */
    public static function lag_filter_query(\WP_Query $query): void {
        global $pagenow, $typenow;

        if ($pagenow !== 'edit.php' || $typenow !== 'lag' || !$query->is_admin || !$query->is_main_query()) {
            return;
        }

        if (isset($_GET['klubb_filter']) && !empty($_GET['klubb_filter'])) {
            $klubb_id = (int) $_GET['klubb_filter'];
            if ($klubb_id > 0) {
                $query->set('meta_query', [
                    [
                        'key' => 'parent_klubb',
                        'value' => $klubb_id,
                        'compare' => '=',
                    ]
                ]);
            }
        }
    }

    /**
     * Add filters for spiller list
     */
    public static function spiller_filters(): void {
        global $typenow;

        if ($typenow !== 'spiller') {
            return;
        }

        // Klubb filter
        $klubber = get_posts([
            'post_type' => 'klubb',
            'posts_per_page' => 100,
            'post_status' => 'any',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $selected_klubb = isset($_GET['klubb_filter']) ? (int) $_GET['klubb_filter'] : 0;

        echo '<select name="klubb_filter">';
        echo '<option value="">Alle klubber</option>';
        foreach ($klubber as $klubb) {
            $selected = selected($selected_klubb, $klubb->ID, false);
            echo '<option value="' . esc_attr($klubb->ID) . '"' . $selected . '>' . esc_html($klubb->post_title) . '</option>';
        }
        echo '</select>';

        // Lag filter
        $lag = get_posts([
            'post_type' => 'lag',
            'posts_per_page' => 100,
            'post_status' => 'any',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $selected_lag = isset($_GET['lag_filter']) ? (int) $_GET['lag_filter'] : 0;

        echo '<select name="lag_filter">';
        echo '<option value="">Alle lag</option>';
        foreach ($lag as $l) {
            $selected = selected($selected_lag, $l->ID, false);
            echo '<option value="' . esc_attr($l->ID) . '"' . $selected . '>' . esc_html($l->post_title) . '</option>';
        }
        echo '</select>';
    }

    /**
     * Apply filters for spiller list
     *
     * @param \WP_Query $query Query object
     */
    public static function spiller_filter_query(\WP_Query $query): void {
        global $pagenow, $typenow;

        if ($pagenow !== 'edit.php' || $typenow !== 'spiller' || !$query->is_admin || !$query->is_main_query()) {
            return;
        }

        if (isset($_GET['lag_filter']) && !empty($_GET['lag_filter'])) {
            $lag_id = (int) $_GET['lag_filter'];
            if ($lag_id > 0) {
                $query->set('meta_query', [
                    [
                        'key' => 'parent_lag',
                        'value' => $lag_id,
                        'compare' => '=',
                    ]
                ]);
            }
        } elseif (isset($_GET['klubb_filter']) && !empty($_GET['klubb_filter'])) {
            $klubb_id = (int) $_GET['klubb_filter'];
            if ($klubb_id > 0) {
                // Find all lag posts with this klubb as parent
                $lag_posts = get_posts([
                    'post_type' => 'lag',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        [
                            'key' => 'parent_klubb',
                            'value' => $klubb_id,
                            'compare' => '=',
                        ]
                    ],
                    'fields' => 'ids',
                ]);

                if (!empty($lag_posts)) {
                    $query->set('meta_query', [
                        [
                            'key' => 'parent_lag',
                            'value' => $lag_posts,
                            'compare' => 'IN',
                        ],
                    ]);
                } else {
                    // No lag found for this klubb, show no results
                    $query->set('meta_query', [
                        [
                            'key' => 'parent_lag',
                            'value' => '-1',
                            'compare' => '=',
                        ],
                    ]);
                }
            }
        }
    }
}
