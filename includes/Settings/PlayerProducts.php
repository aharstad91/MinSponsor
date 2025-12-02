<?php
/**
 * Player Products Settings for MinSponsor
 * 
 * WooCommerce settings panel for player sponsorship products
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Settings;

if (!defined('ABSPATH')) {
    exit;
}

class PlayerProducts {
    
    /**
     * Initialize hooks
     */
    public function init(): void {
        // Only add hooks if WooCommerce is available
        if (!class_exists('WC_Settings_Page')) {
            return;
        }
        
        // Primary method: Use WC_Settings_Page
        add_filter('woocommerce_get_settings_pages', [$this, 'add_settings_page'], 20);
        
        // Backup method: Direct tab registration
        add_filter('woocommerce_settings_tabs_array', [$this, 'add_settings_tab'], 50);
        add_action('woocommerce_settings_tabs_minsponsor', [$this, 'render_settings']);
        add_action('woocommerce_update_options_minsponsor', [$this, 'save_settings']);
        
        // Always add AJAX and admin scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_minsponsor_validate_product', [$this, 'ajax_validate_product']);
    }
    
    /**
     * Add settings tab to WooCommerce
     *
     * @param array $tabs Existing tabs
     * @return array Modified tabs
     */
    public function add_settings_tab(array $tabs): array {
        $tabs['minsponsor'] = __('MinSponsor', 'minsponsor');
        return $tabs;
    }
    
    /**
     * Add settings page to WooCommerce
     *
     * @param array $settings Existing settings pages
     * @return array Modified settings pages
     */
    public function add_settings_page(array $settings): array {
        $settings[] = new PlayerProductsSettingsPage();
        return $settings;
    }
    
    /**
     * Render settings (fallback method)
     */
    public function render_settings(): void {
        $settings_page = new PlayerProductsSettingsPage();
        $settings_page->output();
    }
    
    /**
     * Save settings (fallback method)
     */
    public function save_settings(): void {
        $settings_page = new PlayerProductsSettingsPage();
        $settings_page->save();
    }
    
    /**
     * Enqueue admin scripts for settings page
     */
    public function enqueue_admin_scripts(string $hook): void {
        if (strpos($hook, 'wc-settings') === false) {
            return;
        }
        
        if (!isset($_GET['tab']) || $_GET['tab'] !== 'minsponsor') {
            return;
        }
        
        wp_add_inline_script('jquery-core', $this->get_inline_script());
    }
    
    /**
     * Get inline script for validation
     */
    private function get_inline_script(): string {
        $ajax_url = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce('minsponsor_settings');
        
        return <<<JS
jQuery(document).ready(function($) {
    'use strict';
    
    $('.minsponsor-validate-btn').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var productType = button.data('type');
        var results = $('#validation-results');
        
        button.prop('disabled', true).text('Validerer...');
        
        $.ajax({
            url: '{$ajax_url}',
            type: 'POST',
            data: {
                action: 'minsponsor_validate_product',
                product_type: productType,
                nonce: '{$nonce}'
            },
            success: function(response) {
                var icon = response.success ? '✓' : '✗';
                var color = response.success ? '#46b450' : '#dc3232';
                var typeLabel = productType === 'one_time' ? 'Engangsprodukt' : 'Månedlig produkt';
                
                var html = '<div style="color: ' + color + '; font-weight: bold; margin-bottom: 5px;">' +
                           icon + ' ' + typeLabel + ': ' + response.data.message + '</div>';
                
                if (response.success && response.data.product_name) {
                    html += '<div style="color: #666; font-size: 12px;">Produkt: ' + response.data.product_name + '</div>';
                }
                
                results.html(results.html() + html);
            },
            error: function() {
                results.html(results.html() + '<div style="color: #dc3232;">AJAX-feil oppstod</div>');
            },
            complete: function() {
                button.prop('disabled', false);
                button.text(productType === 'one_time' ? 'Test engangsprodukt' : 'Test månedlig produkt');
            }
        });
    });
});
JS;
    }
    
    /**
     * AJAX handler for product validation
     */
    public function ajax_validate_product(): void {
        check_ajax_referer('minsponsor_settings', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions');
        }
        
        $product_type = sanitize_text_field($_POST['product_type'] ?? '');
        $setting_key = "minsponsor_player_product_{$product_type}_id";
        $product_id = absint(get_option($setting_key));
        
        $validation = $this->validate_product($product_id, $product_type);
        
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
    public function validate_product(int $product_id, string $expected_type): array {
        if (!$product_id) {
            return [
                'valid' => false,
                'message' => 'Ugyldig produkt-ID'
            ];
        }
        
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return [
                'valid' => false,
                'message' => 'Produktet finnes ikke'
            ];
        }
        
        if ($product->get_status() !== 'publish') {
            return [
                'valid' => false,
                'message' => 'Produktet er ikke publisert'
            ];
        }
        
        $is_subscription = class_exists('WC_Subscriptions_Product') && 
                          \WC_Subscriptions_Product::is_subscription($product);
        
        if ($expected_type === 'one_time' && $is_subscription) {
            return [
                'valid' => false,
                'message' => 'Engangsprodukt kan ikke være et abonnement'
            ];
        }
        
        if ($expected_type === 'monthly' && !$is_subscription) {
            return [
                'valid' => false,
                'message' => 'Månedlig produkt må være et abonnement'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Produktet er gyldig',
            'product_name' => $product->get_name(),
            'product_price' => wc_price($product->get_price()),
            'is_subscription' => $is_subscription
        ];
    }
    
    /**
     * Get product options for select fields
     *
     * @param string $type Product type filter ('simple', 'subscription', or 'all')
     * @return array Product options
     */
    public static function get_product_options(string $type = 'all'): array {
        $options = ['' => 'Velg produkt...'];
        
        // Use WooCommerce's proper product query
        $args = [
            'status' => 'publish',
            'limit' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ];
        
        // Filter by product type
        if ($type === 'simple') {
            $args['type'] = ['simple'];
        } elseif ($type === 'subscription') {
            $args['type'] = ['subscription', 'variable-subscription'];
        }
        
        $products = wc_get_products($args);
        
        foreach ($products as $product) {
            if ($product) {
                $price = $product->get_price();
                $price_text = $price ? ' (' . wc_price($price) . ')' : '';
                $type_label = '';
                if ($product->is_type('subscription') || $product->is_type('variable-subscription')) {
                    $type_label = ' [abonnement]';
                }
                // Use string key for WooCommerce select compatibility
                $options[(string) $product->get_id()] = $product->get_name() . $price_text . $type_label;
            }
        }
        
        return $options;
    }
}

/**
 * MinSponsor Settings Page Class
 */
class PlayerProductsSettingsPage extends \WC_Settings_Page {
    
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
    public function get_settings(): array {
        $settings = [
            [
                'title' => __('MinSponsor - Spillerstøtte', 'minsponsor'),
                'type' => 'title',
                'desc' => __('Konfigurer produkter for spillerstøtte.', 'minsponsor'),
                'id' => 'minsponsor_player_products_options'
            ],
            
            [
                'title' => __('Engangsprodukt', 'minsponsor'),
                'desc' => __('Velg hvilket produkt som skal brukes for engangsstøtte til spillere. Dette må være et vanlig (simple) produkt.', 'minsponsor'),
                'id' => 'minsponsor_player_product_one_time_id',
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'css' => 'min-width:300px;',
                'default' => '',
                'options' => PlayerProducts::get_product_options('simple'),
                'custom_attributes' => [
                    'data-product-type' => 'one_time'
                ]
            ],
            
            [
                'title' => __('Månedlig abonnement', 'minsponsor'),
                'desc' => __('Velg hvilket abonnementsprodukt som skal brukes for månedlig støtte til spillere. Dette må være et abonnementsprodukt.', 'minsponsor'),
                'id' => 'minsponsor_player_product_monthly_id',
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'css' => 'min-width:300px;',
                'default' => '',
                'options' => PlayerProducts::get_product_options('subscription'),
                'custom_attributes' => [
                    'data-product-type' => 'monthly'
                ]
            ],
            
            [
                'type' => 'sectionend',
                'id' => 'minsponsor_player_products_options'
            ],
            
            [
                'title' => __('Fallback-produkter (SKU)', 'minsponsor'),
                'type' => 'title',
                'desc' => __('Hvis produktene ovenfor ikke er satt, vil systemet automatisk lete etter produkter med disse SKU-ene:', 'minsponsor') . 
                         '<br><strong>Engang:</strong> <code>minsponsor_player_one_time</code>' .
                         '<br><strong>Månedlig:</strong> <code>minsponsor_player_monthly</code>',
                'id' => 'minsponsor_fallback_info'
            ],
            
            [
                'type' => 'sectionend',
                'id' => 'minsponsor_fallback_info'
            ],
            
            [
                'title' => __('Validering', 'minsponsor'),
                'type' => 'title',
                'desc' => __('Test at produktkonfigurasjonen fungerer som forventet.', 'minsponsor'),
                'id' => 'minsponsor_validation'
            ],
        ];
        
        return apply_filters('minsponsor_settings', $settings);
    }
    
    /**
     * Output settings page
     */
    public function output(): void {
        $settings = $this->get_settings();
        
        \WC_Admin_Settings::output_fields($settings);
        
        // Add validation buttons
        $this->output_validation_buttons();
    }
    
    /**
     * Output validation buttons
     */
    private function output_validation_buttons(): void {
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
        </table>
        <?php
    }
    
    /**
     * Save settings
     */
    public function save(): void {
        $settings = $this->get_settings();
        \WC_Admin_Settings::save_fields($settings);
    }
}
