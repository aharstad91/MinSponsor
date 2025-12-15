<?php
/**
 * Spons Theme Functions - MinSponsor Implementation
 */

// Theme setup
function spons_theme_setup() {
    // Add theme support for various features
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
    
    // WooCommerce support
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    
    // Register navigation menu
    register_nav_menus(array(
        'primary' => 'Hovedmeny',
    ));
}
add_action('after_setup_theme', 'spons_theme_setup');

// Enqueue styles and scripts
function spons_enqueue_assets() {
    // Enqueue main stylesheet
    wp_enqueue_style('spons-style', get_stylesheet_uri(), array(), '1.0');
    
    // Enqueue Tailwind CSS (will be built version)
    wp_enqueue_style('spons-tailwind', get_template_directory_uri() . '/dist/style.css', array(), '1.0');
}
add_action('wp_enqueue_scripts', 'spons_enqueue_assets');

// Remove WordPress default styles that might conflict
function spons_remove_wp_styles() {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('wc-block-style');
}
add_action('wp_enqueue_scripts', 'spons_remove_wp_styles', 100);

// Clean up wp_head
function spons_cleanup_head() {
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
}
add_action('init', 'spons_cleanup_head');

/* ===========================
 * MINSPONSOR IMPLEMENTATION
 * ===========================*/

// Initialize Entity Search API
require_once get_template_directory() . '/includes/Api/EntitySearch.php';
\MinSponsor\Api\EntitySearch::init();

/**
 * Register all MinSponsor content types, taxonomies, and rewrite rules
 * 
 * @since 1.0.0
 */
function minsponsor_register_everything() {
    // Register Custom Post Types
    minsponsor_register_cpt_klubb();
    minsponsor_register_cpt_lag();
    minsponsor_register_cpt_spiller();
    
    // Register Taxonomies
    minsponsor_register_taxonomy_idrettsgren();
    
    // Add custom rewrite rules
    minsponsor_add_rewrite_rules();
}
add_action('init', 'minsponsor_register_everything');

/**
 * Register klubb custom post type
 * 
 * @since 1.0.0
 */
function minsponsor_register_cpt_klubb() {
    $args = array(
        'labels' => array(
            'name' => 'Klubber',
            'singular_name' => 'Klubb',
            'menu_name' => 'Klubber',
            'add_new' => 'Legg til klubb',
            'add_new_item' => 'Legg til ny klubb',
            'edit_item' => 'Rediger klubb',
            'new_item' => 'Ny klubb',
            'view_item' => 'Vis klubb',
            'search_items' => 'Søk klubber',
            'not_found' => 'Ingen klubber funnet',
            'not_found_in_trash' => 'Ingen klubber funnet i papirkurv',
        ),
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => false,
        'rewrite' => array(
            'slug' => 'stott',
            'with_front' => false,
        ),
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'revisions'),
        'menu_icon' => 'dashicons-groups',
        'capability_type' => 'post',
        'map_meta_cap' => true,
    );
    
    register_post_type('klubb', $args);
}

/**
 * Register lag custom post type
 * 
 * @since 1.0.0
 */
function minsponsor_register_cpt_lag() {
    $args = array(
        'labels' => array(
            'name' => 'Lag',
            'singular_name' => 'Lag',
            'menu_name' => 'Lag',
            'add_new' => 'Legg til lag',
            'add_new_item' => 'Legg til nytt lag',
            'edit_item' => 'Rediger lag',
            'new_item' => 'Nytt lag',
            'view_item' => 'Vis lag',
            'search_items' => 'Søk lag',
            'not_found' => 'Ingen lag funnet',
            'not_found_in_trash' => 'Ingen lag funnet i papirkurv',
        ),
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => false,
        'rewrite' => false,
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'revisions'),
        'menu_icon' => 'dashicons-networking',
        'capability_type' => 'post',
        'map_meta_cap' => true,
    );
    
    register_post_type('lag', $args);
}

/**
 * Register spiller custom post type
 * 
 * @since 1.0.0
 */
function minsponsor_register_cpt_spiller() {
    $args = array(
        'labels' => array(
            'name' => 'Spillere',
            'singular_name' => 'Spiller',
            'menu_name' => 'Spillere',
            'add_new' => 'Legg til spiller',
            'add_new_item' => 'Legg til ny spiller',
            'edit_item' => 'Rediger spiller',
            'new_item' => 'Ny spiller',
            'view_item' => 'Vis spiller',
            'search_items' => 'Søk spillere',
            'not_found' => 'Ingen spillere funnet',
            'not_found_in_trash' => 'Ingen spillere funnet i papirkurv',
        ),
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => false,
        'rewrite' => false,
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'revisions'),
        'menu_icon' => 'dashicons-universal-access',
        'capability_type' => 'post',
        'map_meta_cap' => true,
    );
    
    register_post_type('spiller', $args);
}

/**
 * Register idrettsgren taxonomy
 * 
 * @since 1.0.0
 */
function minsponsor_register_taxonomy_idrettsgren() {
    $args = array(
        'labels' => array(
            'name' => 'Idrettsgrener',
            'singular_name' => 'Idrettsgren',
            'menu_name' => 'Idrettsgrener',
            'all_items' => 'Alle idrettsgrener',
            'edit_item' => 'Rediger idrettsgren',
            'view_item' => 'Vis idrettsgren',
            'update_item' => 'Oppdater idrettsgren',
            'add_new_item' => 'Legg til ny idrettsgren',
            'new_item_name' => 'Ny idrettsgren navn',
            'search_items' => 'Søk idrettsgrener',
            'not_found' => 'Ingen idrettsgrener funnet',
        ),
        'hierarchical' => true,
        'public' => true,
        'show_in_rest' => true,
        'rewrite' => array(
            'slug' => 'idrettsgren',
        ),
    );
    
    register_taxonomy('idrettsgren', array('lag'), $args);
}

/**
 * Add custom rewrite rules for pretty permalinks
 * 
 * @since 1.0.0
 */
function minsponsor_add_rewrite_rules() {
    // Base /stott/ URL - shows all entities
    add_rewrite_rule(
        '^stott/?$',
        'index.php?pagename=stott',
        'top'
    );
    
    add_rewrite_rule(
        '^stott/([^/]+)/([^/]+)/([^/]+)/?$',
        'index.php?post_type=spiller&name=$matches[3]&klubb_slug=$matches[1]&lag_slug=$matches[2]',
        'top'
    );
    
    add_rewrite_rule(
        '^stott/([^/]+)/([^/]+)/?$',
        'index.php?post_type=lag&name=$matches[2]&klubb_slug=$matches[1]',
        'top'
    );
    
    add_rewrite_rule(
        '^stott/([^/]+)/?$',
        'index.php?post_type=klubb&name=$matches[1]',
        'top'
    );
}

/**
 * Add custom query vars for rewrite rules
 * 
 * @param array $vars Existing query vars
 * @return array Modified query vars
 * @since 1.0.0
 */
function minsponsor_add_query_vars($vars) {
    $vars[] = 'klubb_slug';
    $vars[] = 'lag_slug';
    return $vars;
}
add_filter('query_vars', 'minsponsor_add_query_vars');

/**
 * Generate pretty permalinks for custom post types
 * 
 * @param string $permalink Default permalink
 * @param WP_Post $post Post object
 * @return string Pretty permalink
 * @since 1.0.0
 */
function minsponsor_generate_permalink($permalink, $post) {
    if (!is_object($post) || !isset($post->post_type)) {
        return $permalink;
    }
    
    $home_url = trailingslashit(home_url());
    
    switch ($post->post_type) {
        case 'klubb':
            return $home_url . 'stott/' . $post->post_name . '/';
            
        case 'lag':
            $klubb_id = minsponsor_get_parent_klubb_id($post->ID);
            if (!$klubb_id) {
                return $permalink;
            }
            $klubb_slug = minsponsor_get_post_slug($klubb_id);
            if (!$klubb_slug) {
                return $permalink;
            }
            return $home_url . 'stott/' . $klubb_slug . '/' . $post->post_name . '/';
            
        case 'spiller':
            $lag_id = minsponsor_get_parent_lag_id($post->ID);
            if (!$lag_id) {
                return $permalink;
            }
            $klubb_id = minsponsor_get_parent_klubb_id($lag_id);
            if (!$klubb_id) {
                return $permalink;
            }
            $lag_slug = minsponsor_get_post_slug($lag_id);
            $klubb_slug = minsponsor_get_post_slug($klubb_id);
            if (!$lag_slug || !$klubb_slug) {
                return $permalink;
            }
            return $home_url . 'stott/' . $klubb_slug . '/' . $lag_slug . '/' . $post->post_name . '/';
    }
    
    return $permalink;
}
add_filter('post_type_link', 'minsponsor_generate_permalink', 10, 2);

/**
 * Handle canonical redirects for lag and spiller
 * 
 * @since 1.0.0
 */
function minsponsor_canonical_redirect() {
    if (!is_singular(array('lag', 'spiller'))) {
        return;
    }
    
    $post = get_queried_object();
    if (!$post) {
        return;
    }
    
    // Get the slugs from query vars (set by our rewrite rules)
    $klubb_slug_from_url = get_query_var('klubb_slug');
    $lag_slug_from_url = get_query_var('lag_slug');
    
    // Only proceed if we have URL slugs (meaning accessed via our custom rules)
    if (empty($klubb_slug_from_url)) {
        return;
    }
    
    $should_redirect = false;
    
    if ($post->post_type === 'lag') {
        // Check if the klubb slug in URL matches the actual parent klubb
        $actual_klubb_id = minsponsor_get_parent_klubb_id($post->ID);
        if ($actual_klubb_id) {
            $actual_klubb_slug = minsponsor_get_post_slug($actual_klubb_id);
            if ($actual_klubb_slug && $actual_klubb_slug !== $klubb_slug_from_url) {
                $should_redirect = true;
            }
        }
    } elseif ($post->post_type === 'spiller') {
        // Check if both klubb and lag slugs match the actual relationships
        $actual_lag_id = minsponsor_get_parent_lag_id($post->ID);
        if ($actual_lag_id) {
            $actual_lag_slug = minsponsor_get_post_slug($actual_lag_id);
            $actual_klubb_id = minsponsor_get_parent_klubb_id($actual_lag_id);
            $actual_klubb_slug = $actual_klubb_id ? minsponsor_get_post_slug($actual_klubb_id) : null;
            
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
add_action('template_redirect', 'minsponsor_canonical_redirect');

/**
 * Get parent klubb ID for a lag
 * 
 * @param int $lag_id Lag post ID
 * @return int|false Parent klubb ID or false if not found
 * @since 1.0.0
 */
function minsponsor_get_parent_klubb_id($lag_id) {
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
 * @return int|false Parent lag ID or false if not found
 * @since 1.0.0
 */
function minsponsor_get_parent_lag_id($spiller_id) {
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
 * @return string|false Post slug or false if not found
 * @since 1.0.0
 */
function minsponsor_get_post_slug($post_id) {
    static $slug_cache = array();
    
    if (!$post_id) {
        return false;
    }
    
    if (isset($slug_cache[$post_id])) {
        return $slug_cache[$post_id];
    }
    
    $post = get_post($post_id);
    if (!$post) {
        $slug_cache[$post_id] = false;
        return false;
    }
    
    $slug_cache[$post_id] = $post->post_name;
    return $post->post_name;
}

/**
 * Add custom admin list table columns for lag
 * 
 * @param array $columns Existing columns
 * @return array Modified columns
 * @since 1.0.0
 */
function minsponsor_lag_admin_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['parent_klubb'] = 'Klubb';
            $new_columns['idrettsgren'] = 'Idrettsgren';
        }
    }
    return $new_columns;
}
add_filter('manage_lag_posts_columns', 'minsponsor_lag_admin_columns');

/**
 * Add custom admin list table columns for spiller
 * 
 * @param array $columns Existing columns
 * @return array Modified columns
 * @since 1.0.0
 */
function minsponsor_spiller_admin_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['parent_lag'] = 'Lag';
            $new_columns['parent_klubb'] = 'Klubb';
        }
    }
    return $new_columns;
}
add_filter('manage_spiller_posts_columns', 'minsponsor_spiller_admin_columns');

/**
 * Populate custom admin columns for lag
 * 
 * @param string $column Column name
 * @param int $post_id Post ID
 * @since 1.0.0
 */
function minsponsor_lag_admin_column_content($column, $post_id) {
    switch ($column) {
        case 'parent_klubb':
            $klubb_id = minsponsor_get_parent_klubb_id($post_id);
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
                $term_names = array();
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
add_action('manage_lag_posts_custom_column', 'minsponsor_lag_admin_column_content', 10, 2);

/**
 * Populate custom admin columns for spiller
 * 
 * @param string $column Column name
 * @param int $post_id Post ID
 * @since 1.0.0
 */
function minsponsor_spiller_admin_column_content($column, $post_id) {
    switch ($column) {
        case 'parent_lag':
            $lag_id = minsponsor_get_parent_lag_id($post_id);
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
            $lag_id = minsponsor_get_parent_lag_id($post_id);
            if ($lag_id) {
                $klubb_id = minsponsor_get_parent_klubb_id($lag_id);
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
add_action('manage_spiller_posts_custom_column', 'minsponsor_spiller_admin_column_content', 10, 2);

/**
 * Add admin filters for lag posts
 * 
 * @since 1.0.0
 */
function minsponsor_lag_admin_filters() {
    global $typenow;
    
    if ($typenow !== 'lag') {
        return;
    }
    
    $klubber = get_posts(array(
        'post_type' => 'klubb',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'orderby' => 'title',
        'order' => 'ASC',
    ));
    
    $selected_klubb = isset($_GET['klubb_filter']) ? (int) $_GET['klubb_filter'] : 0;
    
    echo '<select name="klubb_filter">';
    echo '<option value="">Alle klubber</option>';
    foreach ($klubber as $klubb) {
        $selected = selected($selected_klubb, $klubb->ID, false);
        echo '<option value="' . esc_attr($klubb->ID) . '"' . $selected . '>' . esc_html($klubb->post_title) . '</option>';
    }
    echo '</select>';
}
add_action('restrict_manage_posts', 'minsponsor_lag_admin_filters');

/**
 * Add admin filters for spiller posts
 * 
 * @since 1.0.0
 */
function minsponsor_spiller_admin_filters() {
    global $typenow;
    
    if ($typenow !== 'spiller') {
        return;
    }
    
    // Klubb filter
    $klubber = get_posts(array(
        'post_type' => 'klubb',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'orderby' => 'title',
        'order' => 'ASC',
    ));
    
    $selected_klubb = isset($_GET['klubb_filter']) ? (int) $_GET['klubb_filter'] : 0;
    
    echo '<select name="klubb_filter">';
    echo '<option value="">Alle klubber</option>';
    foreach ($klubber as $klubb) {
        $selected = selected($selected_klubb, $klubb->ID, false);
        echo '<option value="' . esc_attr($klubb->ID) . '"' . $selected . '>' . esc_html($klubb->post_title) . '</option>';
    }
    echo '</select>';
    
    // Lag filter
    $lag = get_posts(array(
        'post_type' => 'lag',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'orderby' => 'title',
        'order' => 'ASC',
    ));
    
    $selected_lag = isset($_GET['lag_filter']) ? (int) $_GET['lag_filter'] : 0;
    
    echo '<select name="lag_filter">';
    echo '<option value="">Alle lag</option>';
    foreach ($lag as $l) {
        $selected = selected($selected_lag, $l->ID, false);
        echo '<option value="' . esc_attr($l->ID) . '"' . $selected . '>' . esc_html($l->post_title) . '</option>';
    }
    echo '</select>';
}
add_action('restrict_manage_posts', 'minsponsor_spiller_admin_filters');

/**
 * Apply admin filters for lag posts
 * 
 * @param WP_Query $query Query object
 * @since 1.0.0
 */
function minsponsor_lag_admin_filter_query($query) {
    global $pagenow, $typenow;
    
    if ($pagenow !== 'edit.php' || $typenow !== 'lag' || !$query->is_admin || !$query->is_main_query()) {
        return;
    }
    
    if (isset($_GET['klubb_filter']) && !empty($_GET['klubb_filter'])) {
        $klubb_id = (int) $_GET['klubb_filter'];
        if ($klubb_id > 0) {
            $query->set('meta_key', 'parent_klubb');
            $query->set('meta_value', $klubb_id);
        }
    }
}
add_action('pre_get_posts', 'minsponsor_lag_admin_filter_query');

/**
 * Apply admin filters for spiller posts
 * 
 * @param WP_Query $query Query object
 * @since 1.0.0
 */
function minsponsor_spiller_admin_filter_query($query) {
    global $pagenow, $typenow;
    
    if ($pagenow !== 'edit.php' || $typenow !== 'spiller' || !$query->is_admin || !$query->is_main_query()) {
        return;
    }
    
    if (isset($_GET['lag_filter']) && !empty($_GET['lag_filter'])) {
        $lag_id = (int) $_GET['lag_filter'];
        if ($lag_id > 0) {
            $query->set('meta_key', 'parent_lag');
            $query->set('meta_value', $lag_id);
        }
    } elseif (isset($_GET['klubb_filter']) && !empty($_GET['klubb_filter'])) {
        $klubb_id = (int) $_GET['klubb_filter'];
        if ($klubb_id > 0) {
            // Find all lag posts with this klubb as parent
            $lag_posts = get_posts(array(
                'post_type' => 'lag',
                'posts_per_page' => -1,
                'meta_key' => 'parent_klubb',
                'meta_value' => $klubb_id,
                'fields' => 'ids',
            ));
            
            if (!empty($lag_posts)) {
                $query->set('meta_query', array(
                    array(
                        'key' => 'parent_lag',
                        'value' => $lag_posts,
                        'compare' => 'IN',
                    ),
                ));
            } else {
                // No lag found for this klubb, show no results
                $query->set('meta_query', array(
                    array(
                        'key' => 'parent_lag',
                        'value' => '-1',
                        'compare' => '=',
                    ),
                ));
            }
        }
    }
}
add_action('pre_get_posts', 'minsponsor_spiller_admin_filter_query');

/**
 * Add body classes for MinSponsor content types
 * 
 * @param array $classes Existing body classes
 * @return array Modified body classes
 * @since 1.0.0
 */
function minsponsor_body_classes($classes) {
    if (!is_singular(array('klubb', 'lag', 'spiller'))) {
        return $classes;
    }
    
    global $post;
    
    switch ($post->post_type) {
        case 'klubb':
            $classes[] = 'klubb-' . $post->post_name;
            break;
            
        case 'lag':
            $klubb_id = minsponsor_get_parent_klubb_id($post->ID);
            if ($klubb_id) {
                $klubb_slug = minsponsor_get_post_slug($klubb_id);
                if ($klubb_slug) {
                    $classes[] = 'klubb-' . $klubb_slug;
                }
            }
            $classes[] = 'lag-' . $post->post_name;
            break;
            
        case 'spiller':
            $lag_id = minsponsor_get_parent_lag_id($post->ID);
            if ($lag_id) {
                $lag_slug = minsponsor_get_post_slug($lag_id);
                if ($lag_slug) {
                    $classes[] = 'lag-' . $lag_slug;
                }
                
                $klubb_id = minsponsor_get_parent_klubb_id($lag_id);
                if ($klubb_id) {
                    $klubb_slug = minsponsor_get_post_slug($klubb_id);
                    if ($klubb_slug) {
                        $classes[] = 'klubb-' . $klubb_slug;
                    }
                }
            }
            break;
    }
    
    return $classes;
}
add_filter('body_class', 'minsponsor_body_classes');

/**
 * Set ACF JSON save point (if ACF is active)
 * 
 * @param string $path Default path
 * @return string Custom path
 * @since 1.0.0
 */
function minsponsor_acf_json_save_point($path) {
    return get_stylesheet_directory() . '/acf-json';
}

/**
 * Set ACF JSON load point (if ACF is active)
 * 
 * @param array $paths Default paths
 * @return array Custom paths
 * @since 1.0.0
 */
function minsponsor_acf_json_load_point($paths) {
    $custom_path = get_stylesheet_directory() . '/acf-json';
    if (is_dir($custom_path)) {
        $paths[] = $custom_path;
    }
    return $paths;
}

// Add ACF filters only if ACF is active
if (function_exists('acf')) {
    add_filter('acf/settings/save_json', 'minsponsor_acf_json_save_point');
    add_filter('acf/settings/load_json', 'minsponsor_acf_json_load_point');
}

/**
 * Flush rewrite rules on theme activation
 * 
 * @since 1.0.0
 */
function minsponsor_flush_rewrite_rules() {
    minsponsor_register_everything();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'minsponsor_flush_rewrite_rules');

// Original theme body classes (enhanced)
function spons_body_classes($classes) {
    if (!is_user_logged_in()) {
        $classes[] = 'not-logged-in';
    }
    
    // Apply MinSponsor body classes
    $classes = minsponsor_body_classes($classes);
    
    return $classes;
}
add_filter('body_class', 'spons_body_classes');

// ACF: vis mer kontekst i Post Object-lista for Spiller → parent_lag
add_filter('acf/fields/post_object/result/name=parent_lag', function ($title, $post, $field, $post_id) {
    if (!($post instanceof WP_Post)) {
        return $title;
    }

    // Finn klubb for dette laget (laget er $post)
    $klubb_id = (int) get_post_meta($post->ID, 'parent_klubb', true);
    $parts = [];

    if ($klubb_id) {
        $klubb_title = get_the_title($klubb_id);
        if ($klubb_title) {
            $parts[] = $klubb_title;
        }
    }

    // Ta med idrettsgren-termer hvis satt på laget
    $terms = get_the_terms($post->ID, 'idrettsgren');
    if ($terms && !is_wp_error($terms)) {
        $parts[] = implode(', ', wp_list_pluck($terms, 'name'));
    }

    // (Valgfritt) ta med post-ID for entydighet ved like navn
    $parts[] = '#' . $post->ID;

    if ($parts) {
        // Eksempel: "Håndball G09 — Heimdal IF • Håndball • #1234"
        $title .= ' — ' . implode(' • ', $parts);
    }
    return $title;
}, 10, 4);

/* ===========================
 * MINSPONSOR STEP 5 - PLAYER SPONSORSHIP
 * ===========================*/

use MinSponsor\Frontend\PlayerRoute;
use MinSponsor\Admin\SpillerMetaBox;
use MinSponsor\Checkout\CartPrice;
use MinSponsor\Checkout\MetaFlow;
use MinSponsor\Checkout\CheckoutCustomizer;
use MinSponsor\Gateways\StripeMeta;
use MinSponsor\Gateways\VippsRecurringMeta;
use MinSponsor\Settings\PlayerProducts;
use MinSponsor\Services\StripeCustomerPortal;

/**
 * Load MinSponsor autoloader
 */
function minsponsor_load_autoloader() {
    $autoloader_path = get_template_directory() . '/includes/autoload.php';
    if (file_exists($autoloader_path)) {
        require_once $autoloader_path;
    }
}

/**
 * Initialize MinSponsor Step 5 functionality
 */
function minsponsor_init_step5() {
    // Load autoloader
    minsponsor_load_autoloader();
    
    // Initialize services if WooCommerce is active
    if (class_exists('WooCommerce')) {
        // Frontend
        $player_route = new PlayerRoute();
        $player_route->init();
        
        // Admin
        if (is_admin()) {
            $spiller_metabox = new SpillerMetaBox();
            $spiller_metabox->init();
        }
        
        // Checkout
        $cart_price = new CartPrice();
        $cart_price->init();
        $cart_price->init_validation();
        
        $meta_flow = new MetaFlow();
        $meta_flow->init();
        
        // Checkout customization (simplified fields, Norwegian, trust signals)
        $checkout_customizer = new CheckoutCustomizer();
        $checkout_customizer->init();
        
        // Stripe Customer Portal for subscription management
        if (class_exists(StripeCustomerPortal::class)) {
            $portal = new StripeCustomerPortal();
            $portal->init();
        }
        
        // Gateways
        if (StripeMeta::is_stripe_available()) {
            $stripe_meta = new StripeMeta();
            $stripe_meta->init();
        }
        
        if (VippsRecurringMeta::is_vipps_recurring_available() || 
            VippsRecurringMeta::is_mobilepay_recurring_available()) {
            $vipps_meta = new VippsRecurringMeta();
            $vipps_meta->init();
        }
        
        // Frontend scripts for error handling
        add_action('wp_footer', [PlayerRoute::class, 'display_error_notices']);
    }
}

/**
 * Initialize MinSponsor admin settings
 */
function minsponsor_init_admin_settings() {
    // Only run in admin and if WooCommerce is active
    if (!is_admin() || !class_exists('WooCommerce') || !class_exists('WC_Settings_Page')) {
        return;
    }
    
    // Ensure autoloader is loaded first
    minsponsor_load_autoloader();
    
    // Check if class exists after loading
    if (!class_exists(PlayerProducts::class)) {
        return;
    }
    
    // Initialize settings with proper timing
    $player_products = new PlayerProducts();
    $player_products->init();
}

// Initialize main functionality after theme setup
// Note: We use 'init' hook instead of 'plugins_loaded' because themes load AFTER plugins_loaded fires
add_action('init', 'minsponsor_init_step5', 5);

// Initialize admin settings separately with later timing to ensure WC is fully loaded
add_action('admin_init', 'minsponsor_init_admin_settings', 15);

/**
 * Add admin notices for MinSponsor configuration
 */
function minsponsor_admin_notices() {
    if (!class_exists('WooCommerce')) {
        echo '<div class="notice notice-error"><p><strong>MinSponsor:</strong> WooCommerce er påkrevd for spillerstøtte-funksjonalitet.</p></div>';
        return;
    }
    
    // Check if products are configured
    $one_time_id = get_option('minsponsor_player_product_one_time_id');
    $monthly_id = get_option('minsponsor_player_product_monthly_id');
    
    if (!$one_time_id || !$monthly_id) {
        $settings_url = admin_url('admin.php?page=wc-settings&tab=minsponsor');
        echo '<div class="notice notice-warning"><p><strong>MinSponsor:</strong> Spillerstøtte-produkter er ikke konfigurert. <a href="' . esc_url($settings_url) . '">Konfigurer nå</a></p></div>';
    }
}
add_action('admin_notices', 'minsponsor_admin_notices');

/**
 * Debug function to check if settings are registered
 */
function minsponsor_debug_settings() {
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_GET['minsponsor_debug'])) {
        echo '<div class="notice notice-info"><p><strong>MinSponsor Debug:</strong><br>';
        echo 'WooCommerce active: ' . (class_exists('WooCommerce') ? 'Yes' : 'No') . '<br>';
        echo 'WC_Settings_Page exists: ' . (class_exists('WC_Settings_Page') ? 'Yes' : 'No') . '<br>';
        echo 'Settings file exists: ' . (file_exists(get_template_directory() . '/includes/Settings/PlayerProducts.php') ? 'Yes' : 'No') . '<br>';
        
        if (function_exists('wc_get_settings_pages')) {
            $pages = wc_get_settings_pages();
            $minsponsor_found = false;
            foreach ($pages as $page) {
                if (method_exists($page, 'get_id') && $page->get_id() === 'minsponsor') {
                    $minsponsor_found = true;
                    break;
                }
            }
            echo 'MinSponsor settings page registered: ' . ($minsponsor_found ? 'Yes' : 'No') . '<br>';
        }
        echo '</p></div>';
    }
}
add_action('admin_notices', 'minsponsor_debug_settings');

// Gjør single-oppslag entydig ved kolliderende slugs (lag/spiller)
add_action('pre_get_posts', function ($q) {
    if (is_admin() || !$q->is_main_query()) return;

    $pt         = $q->get('post_type');
    $name       = $q->get('name');
    $klubb_slug = $q->get('klubb_slug');
    $lag_slug   = $q->get('lag_slug');

    // /stott/{klubb}/{lag}/  →  post_type=lag
    if ($pt === 'lag' && $name && $klubb_slug) {
        $klubb = get_page_by_path($klubb_slug, OBJECT, 'klubb');
        if ($klubb) {
            $mq   = (array) $q->get('meta_query');
            $mq[] = ['key' => 'parent_klubb', 'value' => (int) $klubb->ID, 'compare' => '='];
            $q->set('meta_query', $mq);
            $q->set('posts_per_page', 1);
        }
    }

    // /stott/{klubb}/{lag}/{spiller}/  →  post_type=spiller
    if ($pt === 'spiller' && $name && $klubb_slug && $lag_slug) {
        $klubb  = get_page_by_path($klubb_slug, OBJECT, 'klubb');
        $lag_id = 0;

        if ($klubb) {
            // Finn laget med slug = {lag_slug} INNENFOR denne klubben
            $lag_q = new WP_Query([
                'post_type'        => 'lag',
                'name'             => $lag_slug,
                'meta_key'         => 'parent_klubb',
                'meta_value'       => (int) $klubb->ID,
                'post_status'      => 'any',
                'fields'           => 'ids',
                'no_found_rows'    => true,
                'posts_per_page'   => 1,
                'suppress_filters' => true,
            ]);
            if ($lag_q->have_posts()) {
                $lag_id = (int) $lag_q->posts[0];
            }
            wp_reset_postdata();
        }

        if ($lag_id) {
            $mq   = (array) $q->get('meta_query');
            $mq[] = ['key' => 'parent_lag', 'value' => $lag_id, 'compare' => '='];
            $q->set('meta_query', $mq);
            $q->set('posts_per_page', 1);
        }
    }
});

?>

