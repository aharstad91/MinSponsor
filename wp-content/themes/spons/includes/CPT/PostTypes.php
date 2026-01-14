<?php
/**
 * Custom Post Types Registration
 *
 * Handles registration of Klubb, Lag, and Spiller post types.
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\CPT;

if (!defined('ABSPATH')) {
    exit;
}

class PostTypes {

    /**
     * Initialize CPT registration
     */
    public static function init(): void {
        add_action('init', [self::class, 'register_post_types']);
        add_action('init', [self::class, 'register_taxonomies']);
    }

    /**
     * Register all custom post types
     */
    public static function register_post_types(): void {
        self::register_klubb();
        self::register_lag();
        self::register_spiller();
    }

    /**
     * Register klubb custom post type
     */
    private static function register_klubb(): void {
        $args = [
            'labels' => [
                'name' => 'Klubber',
                'singular_name' => 'Klubb',
                'menu_name' => 'Klubber',
                'add_new' => 'Legg til klubb',
                'add_new_item' => 'Legg til ny klubb',
                'edit_item' => 'Rediger klubb',
                'new_item' => 'Ny klubb',
                'view_item' => 'Vis klubb',
                'search_items' => 'Sok klubber',
                'not_found' => 'Ingen klubber funnet',
                'not_found_in_trash' => 'Ingen klubber funnet i papirkurv',
            ],
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => false,
            'rewrite' => [
                'slug' => 'stott',
                'with_front' => false,
            ],
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
            'menu_icon' => 'dashicons-groups',
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ];

        register_post_type('klubb', $args);
    }

    /**
     * Register lag custom post type
     */
    private static function register_lag(): void {
        $args = [
            'labels' => [
                'name' => 'Lag',
                'singular_name' => 'Lag',
                'menu_name' => 'Lag',
                'add_new' => 'Legg til lag',
                'add_new_item' => 'Legg til nytt lag',
                'edit_item' => 'Rediger lag',
                'new_item' => 'Nytt lag',
                'view_item' => 'Vis lag',
                'search_items' => 'Sok lag',
                'not_found' => 'Ingen lag funnet',
                'not_found_in_trash' => 'Ingen lag funnet i papirkurv',
            ],
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => false,
            'rewrite' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
            'menu_icon' => 'dashicons-networking',
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ];

        register_post_type('lag', $args);
    }

    /**
     * Register spiller custom post type
     */
    private static function register_spiller(): void {
        $args = [
            'labels' => [
                'name' => 'Spillere',
                'singular_name' => 'Spiller',
                'menu_name' => 'Spillere',
                'add_new' => 'Legg til spiller',
                'add_new_item' => 'Legg til ny spiller',
                'edit_item' => 'Rediger spiller',
                'new_item' => 'Ny spiller',
                'view_item' => 'Vis spiller',
                'search_items' => 'Sok spillere',
                'not_found' => 'Ingen spillere funnet',
                'not_found_in_trash' => 'Ingen spillere funnet i papirkurv',
            ],
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => false,
            'rewrite' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
            'menu_icon' => 'dashicons-universal-access',
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ];

        register_post_type('spiller', $args);
    }

    /**
     * Register taxonomies
     */
    public static function register_taxonomies(): void {
        self::register_idrettsgren();
    }

    /**
     * Register idrettsgren taxonomy
     */
    private static function register_idrettsgren(): void {
        $args = [
            'labels' => [
                'name' => 'Idrettsgrener',
                'singular_name' => 'Idrettsgren',
                'menu_name' => 'Idrettsgrener',
                'all_items' => 'Alle idrettsgrener',
                'edit_item' => 'Rediger idrettsgren',
                'view_item' => 'Vis idrettsgren',
                'update_item' => 'Oppdater idrettsgren',
                'add_new_item' => 'Legg til ny idrettsgren',
                'new_item_name' => 'Ny idrettsgren navn',
                'search_items' => 'Sok idrettsgrener',
                'not_found' => 'Ingen idrettsgrener funnet',
            ],
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => [
                'slug' => 'idrettsgren',
            ],
        ];

        register_taxonomy('idrettsgren', ['lag'], $args);
    }
}
