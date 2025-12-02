<?php
/**
 * Sponsorship Route Handler for MinSponsor
 * 
 * Handles template_redirect for klubb, lag, and spiller pages with sponsorship parameters
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Frontend;

use MinSponsor\Services\PlayerLinksService;

if (!defined('ABSPATH')) {
    exit;
}

class PlayerRoute {
    
    private PlayerLinksService $links_service;
    
    public function __construct(?PlayerLinksService $links_service = null) {
        $this->links_service = $links_service ?? new PlayerLinksService();
    }
    
    /**
     * Initialize hooks
     */
    public function init(): void {
        // Handle all MinSponsor content types
        add_action('template_redirect', [$this, 'handle_sponsorship_request'], 5);
    }
    
    /**
     * Handle sponsorship requests on klubb, lag, and spiller pages
     */
    public function handle_sponsorship_request(): void {
        // Only process klubb, lag, or spiller pages
        if (!is_singular(['klubb', 'lag', 'spiller'])) {
            return;
        }
        
        // Check if we have sponsorship parameters
        $has_interval = isset($_GET['interval']);
        $has_amount = isset($_GET['amount']);
        $has_ref = isset($_GET['ref']);
        
        if (!$has_interval && !$has_amount && !$has_ref) {
            return;
        }
        
        global $post;
        if (!$post || !in_array($post->post_type, ['klubb', 'lag', 'spiller'])) {
            return;
        }
        
        // Validate and sanitize parameters
        $params = $this->links_service->validate_link_params($_GET);
        
        // Get player products (used for all types currently)
        $one_time_product_id = $this->get_player_product_id('one_time');
        $monthly_product_id = $this->get_player_product_id('monthly');
        
        if (!$one_time_product_id || !$monthly_product_id) {
            $this->show_admin_notice_and_return('Produkter for støtte er ikke konfigurert. Kontakt administrator.');
            return;
        }
        
        // Select correct product based on interval
        $product_id = ($params['interval'] === 'once') ? $one_time_product_id : $monthly_product_id;
        
        // Verify product exists and is published
        $product = wc_get_product($product_id);
        if (!$product || $product->get_status() !== 'publish') {
            $this->show_admin_notice_and_return('Det valgte produktet er ikke tilgjengelig.');
            return;
        }
        
        // Clear cart first to avoid confusion
        \WC()->cart->empty_cart();
        
        // Get cart item data based on post type
        $cart_item_data = $this->get_cart_item_data_for_post($post, $params);
        
        // Add product to cart
        $cart_item_key = \WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);
        
        if (!$cart_item_key) {
            $this->show_admin_notice_and_return('Kunne ikke legge produktet i handlekurven. Prøv igjen.');
            return;
        }
        
        // Log successful add to cart
        error_log(sprintf(
            'MinSponsor: Added %s sponsorship to cart - %s: %s (%d), Product: %d, Interval: %s, Amount: %s',
            $post->post_type,
            $post->post_type,
            $post->post_title,
            $post->ID,
            $product_id,
            $params['interval'],
            isset($params['amount']) ? $params['amount'] : 'default'
        ));
        
        // Redirect to checkout
        wp_safe_redirect(wc_get_checkout_url());
        exit;
    }
    
    /**
     * Get cart item data based on post type (klubb, lag, or spiller)
     *
     * @param \WP_Post $post The post object
     * @param array $params Validated parameters
     * @return array Cart item data
     */
    private function get_cart_item_data_for_post(\WP_Post $post, array $params): array {
        $data = [
            'minsponsor_interval' => $params['interval'],
        ];
        
        if (isset($params['amount'])) {
            $data['minsponsor_amount'] = $params['amount'];
        }
        
        if (isset($params['ref'])) {
            $data['minsponsor_ref'] = $params['ref'];
        }
        
        switch ($post->post_type) {
            case 'spiller':
                // For spiller, use existing service method
                return $this->links_service->get_cart_item_data($post->ID, $params);
                
            case 'lag':
                // For lag, include lag and klubb info
                $klubb_id = minsponsor_get_parent_klubb_id($post->ID);
                $klubb = $klubb_id ? get_post($klubb_id) : null;
                
                $data['minsponsor_recipient_type'] = 'lag';
                $data['minsponsor_team_id'] = $post->ID;
                $data['minsponsor_team_name'] = $post->post_title;
                $data['minsponsor_team_slug'] = $post->post_name;
                
                if ($klubb) {
                    $data['minsponsor_club_id'] = $klubb->ID;
                    $data['minsponsor_club_name'] = $klubb->post_title;
                    $data['minsponsor_club_slug'] = $klubb->post_name;
                }
                break;
                
            case 'klubb':
                // For klubb, only klubb info
                $data['minsponsor_recipient_type'] = 'klubb';
                $data['minsponsor_club_id'] = $post->ID;
                $data['minsponsor_club_name'] = $post->post_title;
                $data['minsponsor_club_slug'] = $post->post_name;
                break;
        }
        
        return $data;
    }
    
    /**
     * Legacy method - kept for backwards compatibility
     */
    public function handle_player_sponsorship_request(): void {
        $this->handle_sponsorship_request();
    }
    
    /**
     * Get player product ID from settings or SKU fallback
     *
     * @param string $type 'one_time' or 'monthly'
     * @return int|false Product ID or false if not found
     */
    private function get_player_product_id(string $type): int|false {
        // Try to get from WooCommerce settings first
        $setting_key = "minsponsor_player_product_{$type}_id";
        $product_id = get_option($setting_key);
        
        if ($product_id && is_numeric($product_id)) {
            $product = wc_get_product($product_id);
            if ($product) {
                return (int) $product_id;
            }
        }
        
        // Fallback to SKU lookup
        $sku = ($type === 'one_time') ? 'minsponsor_player_one_time' : 'minsponsor_player_monthly';
        $product_id = wc_get_product_id_by_sku($sku);
        
        if ($product_id) {
            return (int) $product_id;
        }
        
        return false;
    }
    
    /**
     * Show admin notice and return to player page without parameters
     *
     * @param string $message Error message
     */
    private function show_admin_notice_and_return(string $message): void {
        // Store message for display
        set_transient('minsponsor_error_' . get_current_user_id(), $message, 60);
        
        // Redirect to clean player URL
        global $post;
        if ($post) {
            wp_safe_redirect(get_permalink($post));
            exit;
        }
    }
    
    /**
     * Display error notices
     */
    public static function display_error_notices(): void {
        if (!is_singular('spiller')) {
            return;
        }
        
        $error_message = get_transient('minsponsor_error_' . get_current_user_id());
        if ($error_message) {
            delete_transient('minsponsor_error_' . get_current_user_id());
            
            echo '<div class="minsponsor-error-notice" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 20px 0; border-radius: 4px;">';
            echo '<strong>Feil:</strong> ' . esc_html($error_message);
            echo '</div>';
        }
    }
}
