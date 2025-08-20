<?php
/**
 * Player Products Settings for MinSponsor
 * 
 * WooCommerce settings panel for player sponsorship products
 *
 * @package MinSponsor
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MinSponsor_PlayerProducts {
    
    /**
     * Initialize hooks
     */
    public function init() {
        // Only add hooks if WooCommerce is available
        if (!class_exists('WC_Settings_Page')) {
            return;
        }
        
        // Primary method: Use WC_Settings_Page
        add_filter('woocommerce_get_settings_pages', array($this, 'add_settings_page'), 20);
        
        // Backup method: Direct tab registration
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
        add_action('woocommerce_settings_tabs_minsponsor', array($this, 'render_settings'));
        add_action('woocommerce_update_options_minsponsor', array($this, 'save_settings'));
        
        // Always add AJAX and admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_minsponsor_validate_product', array($this, 'ajax_validate_product'));
        
        // Debug hook to verify registration
        add_action('admin_init', array($this, 'debug_registration'), 99);
    }
    
    /**
     * Debug registration
     */
    public function debug_registration() {
        if (isset($_GET['minsponsor_debug']) && current_user_can('manage_options')) {
            error_log('MinSponsor Settings: Class loaded and hooks registered');
        }
    }
    
    /**
     * Add settings tab to WooCommerce
     *
     * @param array $tabs Existing tabs
     * @return array Modified tabs
     */
    public function add_settings_tab($tabs) {
        $tabs['minsponsor'] = __('MinSponsor', 'minsponsor');
        return $tabs;
    }
    
    /**
     * Add settings page to WooCommerce
     *
     * @param array $settings Existing settings pages
     * @return array Modified settings pages
     */
    public function add_settings_page($settings) {
        $settings[] = new MinSponsor_Settings_Page();
        return $settings;
    }
    
    /**
     * Render settings (fallback method)
     */
    public function render_settings() {
        $settings_page = new MinSponsor_Settings_Page();
        $settings_page->output();
    }
    
    /**
     * Save settings (fallback method)
     */
    public function save_settings() {
        $settings_page = new MinSponsor_Settings_Page();
        $settings_page->save();
    }
    
    /**
     * Enqueue admin scripts for settings page
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wc-settings') === false) {
            return;
        }
        
        if (!isset($_GET['tab']) || $_GET['tab'] !== 'minsponsor') {
            return;
        }
        
        wp_enqueue_script(
            'minsponsor-settings',
            get_template_directory_uri() . '/includes/Settings/settings.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('minsponsor-settings', 'minsponsor_settings_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('minsponsor_settings')
        ));
    }
    
    /**
     * AJAX handler for product validation
     */
    public function ajax_validate_product() {
        check_ajax_referer('minsponsor_settings', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions');
        }
        
        $product_id = intval($_POST['product_id']);
        $expected_type = sanitize_text_field($_POST['expected_type']);
        
        $validation = $this->validate_product($product_id, $expected_type);
        
        if ($validation['valid']) {
            wp_send_json_success($validation);
        } else {
            wp_send_json_error($validation);
        }
    }
    
    /**
     * Validate a product for MinSponsor use
     *
     * @param int $product_id Product ID
     * @param string $expected_type Expected type ('one_time' or 'monthly')
     * @return array Validation result
     */
    public function validate_product($product_id, $expected_type) {
        if (!$product_id) {
            return array(
                'valid' => false,
                'message' => 'Ugyldig produkt-ID'
            );
        }
        
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return array(
                'valid' => false,
                'message' => 'Produktet finnes ikke'
            );
        }
        
        if ($product->get_status() !== 'publish') {
            return array(
                'valid' => false,
                'message' => 'Produktet er ikke publisert'
            );
        }
        
        $is_subscription = class_exists('WC_Subscriptions_Product') && 
                          WC_Subscriptions_Product::is_subscription($product);
        
        if ($expected_type === 'one_time' && $is_subscription) {
            return array(
                'valid' => false,
                'message' => 'Engangsprodukt kan ikke være et abonnement'
            );
        }
        
        if ($expected_type === 'monthly' && !$is_subscription) {
            return array(
                'valid' => false,
                'message' => 'Månedlig produkt må være et abonnement'
            );
        }
        
        return array(
            'valid' => true,
            'message' => 'Produktet er gyldig',
            'product_name' => $product->get_name(),
            'product_price' => wc_price($product->get_price()),
            'is_subscription' => $is_subscription
        );
    }
    
    /**
     * Get product options for select fields
     *
     * @param string $type Product type filter ('simple', 'subscription', or 'all')
     * @return array Product options
     */
    public static function get_product_options($type = 'all') {
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(),
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        if ($type === 'simple') {
            $args['meta_query'][] = array(
                'key' => '_subscription_price',
                'compare' => 'NOT EXISTS'
            );
        } elseif ($type === 'subscription') {
            $args['meta_query'][] = array(
                'key' => '_subscription_price',
                'compare' => 'EXISTS'
            );
        }
        
        $products = get_posts($args);
        $options = array('' => 'Velg produkt...');
        
        foreach ($products as $product) {
            $product_obj = wc_get_product($product->ID);
            if ($product_obj) {
                $price = $product_obj->get_price();
                $price_text = $price ? ' (' . wc_price($price) . ')' : '';
                $options[$product->ID] = $product->post_title . $price_text;
            }
        }
        
        return $options;
    }
}

/**
 * MinSponsor Settings Page Class
 */
class MinSponsor_Settings_Page extends WC_Settings_Page {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'minsponsor';
        $this->label = __('MinSponsor', 'minsponsor');
        
        parent::__construct();
    }
    
    /**
     * Get settings array
     *
     * @return array Settings
     */
    public function get_settings() {
        $settings = array(
            array(
                'title' => __('MinSponsor - Spillerstøtte', 'minsponsor'),
                'type' => 'title',
                'desc' => __('Konfigurer produkter for spillerstøtte og QR-kode generering.', 'minsponsor'),
                'id' => 'minsponsor_player_products_options'
            ),
            
            array(
                'title' => __('Engangsprodukt', 'minsponsor'),
                'desc' => __('Velg hvilket produkt som skal brukes for engangsstøtte til spillere. Dette må være et vanlig (simple) produkt.', 'minsponsor'),
                'id' => 'minsponsor_player_product_one_time_id',
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'css' => 'min-width:300px;',
                'default' => '',
                'options' => MinSponsor_PlayerProducts::get_product_options('simple'),
                'custom_attributes' => array(
                    'data-product-type' => 'one_time'
                )
            ),
            
            array(
                'title' => __('Månedlig abonnement', 'minsponsor'),
                'desc' => __('Velg hvilket abonnementsprodukt som skal brukes for månedlig støtte til spillere. Dette må være et abonnementsprodukt.', 'minsponsor'),
                'id' => 'minsponsor_player_product_monthly_id',
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'css' => 'min-width:300px;',
                'default' => '',
                'options' => MinSponsor_PlayerProducts::get_product_options('subscription'),
                'custom_attributes' => array(
                    'data-product-type' => 'monthly'
                )
            ),
            
            array(
                'type' => 'sectionend',
                'id' => 'minsponsor_player_products_options'
            ),
            
            array(
                'title' => __('Fallback-produkter (SKU)', 'minsponsor'),
                'type' => 'title',
                'desc' => __('Hvis produktene ovenfor ikke er satt, vil systemet automatisk lete etter produkter med disse SKU-ene:', 'minsponsor') . 
                         '<br><strong>Engang:</strong> <code>minsponsor_player_one_time</code>' .
                         '<br><strong>Månedlig:</strong> <code>minsponsor_player_monthly</code>',
                'id' => 'minsponsor_fallback_info'
            ),
            
            array(
                'type' => 'sectionend',
                'id' => 'minsponsor_fallback_info'
            ),
            
            array(
                'title' => __('QR-kode innstillinger', 'minsponsor'),
                'type' => 'title',
                'desc' => __('Innstillinger for generering av QR-koder.', 'minsponsor'),
                'id' => 'minsponsor_qr_options'
            ),
            
            array(
                'title' => __('QR-kode størrelse', 'minsponsor'),
                'desc' => __('Størrelse på genererte QR-koder i piksler.', 'minsponsor'),
                'id' => 'minsponsor_qr_size',
                'type' => 'select',
                'default' => '1024',
                'options' => array(
                    '512' => '512x512 px',
                    '1024' => '1024x1024 px',
                    '2048' => '2048x2048 px'
                )
            ),
            
            array(
                'title' => __('Feilkorrigering', 'minsponsor'),
                'desc' => __('Nivå av feilkorrigering for QR-koder. Høyere nivå gjør QR-koden mer robust, men også mer kompleks.', 'minsponsor'),
                'id' => 'minsponsor_qr_error_correction',
                'type' => 'select',
                'default' => 'H',
                'options' => array(
                    'L' => 'Lav (7%)',
                    'M' => 'Medium (15%)',
                    'Q' => 'Kvartil (25%)',
                    'H' => 'Høy (30%)'
                )
            ),
            
            array(
                'type' => 'sectionend',
                'id' => 'minsponsor_qr_options'
            ),
            
            array(
                'title' => __('Validering', 'minsponsor'),
                'type' => 'title',
                'desc' => __('Test at produktkonfigurasjonen fungerer som forventet.', 'minsponsor'),
                'id' => 'minsponsor_validation'
            ),
        );
        
        // Add validation buttons
        $settings[] = array(
            'title' => __('Test produkter', 'minsponsor'),
            'type' => 'minsponsor_validation_buttons',
            'id' => 'minsponsor_validation_buttons'
        );
        
        $settings[] = array(
            'type' => 'sectionend',
            'id' => 'minsponsor_validation'
        );
        
        return apply_filters('minsponsor_settings', $settings);
    }
    
    /**
     * Output validation buttons
     */
    public function output_minsponsor_validation_buttons() {
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">Produktvalidering</th>
            <td class="forminp">
                <button type="button" id="validate-one-time" class="button minsponsor-validate-btn" data-type="one_time">
                    Test engangsprodukt
                </button>
                <button type="button" id="validate-monthly" class="button minsponsor-validate-btn" data-type="monthly">
                    Test månedlig produkt
                </button>
                <div id="validation-results" style="margin-top: 15px;"></div>
                <p class="description">Klikk for å validere at produktene er riktig konfigurert for spillerstøtte.</p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Output settings page
     */
    public function output() {
        $settings = $this->get_settings();
        
        // Filter out custom field types before passing to WC_Admin_Settings
        $standard_settings = array();
        $custom_fields = array();
        
        foreach ($settings as $setting) {
            if (isset($setting['type']) && $setting['type'] === 'minsponsor_validation_buttons') {
                $custom_fields[] = $setting;
            } else {
                $standard_settings[] = $setting;
            }
        }
        
        // Output standard WooCommerce fields
        WC_Admin_Settings::output_fields($standard_settings);
        
        // Output custom fields
        foreach ($custom_fields as $field) {
            if ($field['type'] === 'minsponsor_validation_buttons') {
                $this->output_minsponsor_validation_buttons();
            }
        }
    }
    
    /**
     * Save settings
     */
    public function save() {
        global $current_section;
        
        $settings = $this->get_settings();
        WC_Admin_Settings::save_fields($settings);
    }
}
