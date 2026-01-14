<?php
/**
 * Spons Theme Functions - MinSponsor Implementation
 *
 * Refactored to use modular classes for better maintainability.
 *
 * @package MinSponsor
 * @since 1.0.0
 */

// Load Composer autoloader for Stripe SDK and other dependencies
if (file_exists(get_template_directory() . '/vendor/autoload.php')) {
    require_once get_template_directory() . '/vendor/autoload.php';
}

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
 * Initialize MinSponsor core modules (CPT, Routing, Admin)
 *
 * Uses refactored class-based architecture for maintainability.
 *
 * @since 1.0.0
 */
function minsponsor_init_core_modules() {
    // Load the modular classes
    require_once get_template_directory() . '/includes/CPT/PostTypes.php';
    require_once get_template_directory() . '/includes/CPT/DataIntegrity.php';
    require_once get_template_directory() . '/includes/Routing/Permalinks.php';
    require_once get_template_directory() . '/includes/Admin/AdminColumns.php';

    // Initialize CPT registration
    \MinSponsor\CPT\PostTypes::init();

    // Initialize data integrity protection (cascade delete, validation)
    \MinSponsor\CPT\DataIntegrity::init();

    // Initialize routing (permalinks, rewrites, canonical redirects)
    \MinSponsor\Routing\Permalinks::init();

    // Initialize admin columns and filters
    \MinSponsor\Admin\AdminColumns::init();
}
add_action('after_setup_theme', 'minsponsor_init_core_modules');

/**
 * Helper function: Get parent klubb ID for a lag
 *
 * Wrapper around Permalinks class method for backwards compatibility.
 *
 * @param int $lag_id Lag post ID
 * @return int|false Parent klubb ID or false if not found
 * @since 1.0.0
 */
function minsponsor_get_parent_klubb_id($lag_id) {
    return \MinSponsor\Routing\Permalinks::get_parent_klubb_id($lag_id);
}

/**
 * Helper function: Get parent lag ID for a spiller
 *
 * Wrapper around Permalinks class method for backwards compatibility.
 *
 * @param int $spiller_id Spiller post ID
 * @return int|false Parent lag ID or false if not found
 * @since 1.0.0
 */
function minsponsor_get_parent_lag_id($spiller_id) {
    return \MinSponsor\Routing\Permalinks::get_parent_lag_id($spiller_id);
}

/**
 * Helper function: Get post slug with caching
 *
 * Wrapper around Permalinks class method for backwards compatibility.
 *
 * @param int $post_id Post ID
 * @return string|false Post slug or false if not found
 * @since 1.0.0
 */
function minsponsor_get_post_slug($post_id) {
    return \MinSponsor\Routing\Permalinks::get_post_slug($post_id);
}

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
use MinSponsor\Admin\LagStripeMetaBox;
use MinSponsor\Checkout\CartPrice;
use MinSponsor\Checkout\MetaFlow;
use MinSponsor\Checkout\CheckoutCustomizer;
use MinSponsor\Gateways\StripeMeta;
use MinSponsor\Gateways\VippsRecurringMeta;
use MinSponsor\Settings\PlayerProducts;
use MinSponsor\Settings\StripeSettings;
use MinSponsor\Services\StripeCustomerPortal;
use MinSponsor\Api\StripeOnboarding;
use MinSponsor\Webhooks\StripeWebhook;

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
            
            // Stripe Connect meta box for Lag
            $lag_stripe_metabox = new LagStripeMetaBox();
            $lag_stripe_metabox->init();
            
        }
        
        // Stripe Connect Onboarding API (always init for AJAX handlers)
        $stripe_onboarding = new StripeOnboarding();
        
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
        
        // Stripe Webhooks (always init to receive account updates)
        $stripe_webhook = new StripeWebhook();
        $stripe_webhook->init();
        
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

/**
 * Initialize MinSponsor Stripe settings page
 */
function minsponsor_init_stripe_settings() {
    if (!is_admin()) {
        return;
    }
    
    // Load autoloader
    minsponsor_load_autoloader();
    
    // Check if class exists
    if (!class_exists(StripeSettings::class)) {
        return;
    }
    
    $stripe_settings = new StripeSettings();
    $stripe_settings->init();
}
add_action('init', 'minsponsor_init_stripe_settings', 10);

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

