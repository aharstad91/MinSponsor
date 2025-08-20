<?php
/**
 * Cart Price Handler for MinSponsor
 * 
 * Handles dynamic pricing for player sponsorship items
 *
 * @package MinSponsor
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MinSponsor_CartPrice {
    
    /**
     * Initialize hooks
     */
    public function init() {
        add_action('woocommerce_before_calculate_totals', array($this, 'apply_dynamic_pricing'), 20);
        add_filter('woocommerce_cart_item_name', array($this, 'customize_cart_item_name'), 10, 3);
        add_filter('woocommerce_cart_item_price', array($this, 'customize_cart_item_price'), 10, 3);
    }
    
    /**
     * Apply dynamic pricing to cart items
     *
     * @param WC_Cart $cart Cart object
     */
    public function apply_dynamic_pricing($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        if (did_action('woocommerce_before_calculate_totals') >= 2) {
            return;
        }
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (!$this->is_minsponsor_item($cart_item)) {
                continue;
            }
            
            $custom_amount = isset($cart_item['minsponsor_amount']) ? $cart_item['minsponsor_amount'] : null;
            
            if ($custom_amount && is_numeric($custom_amount) && $custom_amount > 0) {
                $cart_item['data']->set_price($custom_amount);
                
                // For subscription products, also set the subscription price
                if ($this->is_subscription_product($cart_item['data'])) {
                    $this->set_subscription_price($cart_item['data'], $custom_amount);
                }
            }
        }
    }
    
    /**
     * Check if cart item is a MinSponsor item
     *
     * @param array $cart_item Cart item data
     * @return bool
     */
    private function is_minsponsor_item($cart_item) {
        return isset($cart_item['minsponsor_player_id']) || 
               isset($cart_item['minsponsor_interval']);
    }
    
    /**
     * Check if product is a subscription product
     *
     * @param WC_Product $product Product object
     * @return bool
     */
    private function is_subscription_product($product) {
        return class_exists('WC_Subscriptions_Product') && 
               WC_Subscriptions_Product::is_subscription($product);
    }
    
    /**
     * Set subscription price for subscription products
     *
     * @param WC_Product $product Product object
     * @param float $price New price
     */
    private function set_subscription_price($product, $price) {
        if (!$this->is_subscription_product($product)) {
            return;
        }
        
        // Set subscription price
        $product->update_meta_data('_subscription_price', $price);
        $product->update_meta_data('_price', $price);
        $product->update_meta_data('_regular_price', $price);
    }
    
    /**
     * Customize cart item name to show sponsorship details
     *
     * @param string $name Item name
     * @param array $cart_item Cart item data
     * @param string $cart_item_key Cart item key
     * @return string Modified name
     */
    public function customize_cart_item_name($name, $cart_item, $cart_item_key) {
        if (!$this->is_minsponsor_item($cart_item)) {
            return $name;
        }
        
        $player_name = isset($cart_item['minsponsor_player_name']) ? $cart_item['minsponsor_player_name'] : '';
        $team_name = isset($cart_item['minsponsor_team_name']) ? $cart_item['minsponsor_team_name'] : '';
        $club_name = isset($cart_item['minsponsor_club_name']) ? $cart_item['minsponsor_club_name'] : '';
        $interval = isset($cart_item['minsponsor_interval']) ? $cart_item['minsponsor_interval'] : '';
        
        if ($player_name) {
            $sponsorship_details = '<br><small style="color: #666;">';
            $sponsorship_details .= '<strong>Støtte til:</strong> ' . esc_html($player_name);
            
            if ($team_name) {
                $sponsorship_details .= ' (' . esc_html($team_name);
                if ($club_name) {
                    $sponsorship_details .= ', ' . esc_html($club_name);
                }
                $sponsorship_details .= ')';
            }
            
            if ($interval === 'once') {
                $sponsorship_details .= ' - <em>Engangsbeløp</em>';
            } elseif ($interval === 'month') {
                $sponsorship_details .= ' - <em>Månedlig</em>';
            }
            
            $sponsorship_details .= '</small>';
            
            $name .= $sponsorship_details;
        }
        
        return $name;
    }
    
    /**
     * Customize cart item price display
     *
     * @param string $price Price HTML
     * @param array $cart_item Cart item data
     * @param string $cart_item_key Cart item key
     * @return string Modified price HTML
     */
    public function customize_cart_item_price($price, $cart_item, $cart_item_key) {
        if (!$this->is_minsponsor_item($cart_item)) {
            return $price;
        }
        
        $custom_amount = isset($cart_item['minsponsor_amount']) ? $cart_item['minsponsor_amount'] : null;
        $interval = isset($cart_item['minsponsor_interval']) ? $cart_item['minsponsor_interval'] : '';
        
        if ($custom_amount && is_numeric($custom_amount) && $custom_amount > 0) {
            $formatted_price = wc_price($custom_amount);
            
            if ($interval === 'month' && $this->is_subscription_product($cart_item['data'])) {
                $formatted_price .= ' <small>/mnd</small>';
            }
            
            return $formatted_price;
        }
        
        return $price;
    }
    
    /**
     * Add custom validation for MinSponsor cart items
     */
    public function validate_cart_items() {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (!$this->is_minsponsor_item($cart_item)) {
                continue;
            }
            
            // Validate player still exists and is published
            $player_id = isset($cart_item['minsponsor_player_id']) ? $cart_item['minsponsor_player_id'] : null;
            if ($player_id) {
                $player = get_post($player_id);
                if (!$player || $player->post_status !== 'publish' || $player->post_type !== 'spiller') {
                    wc_add_notice('Spilleren du prøver å støtte er ikke lenger tilgjengelig.', 'error');
                    WC()->cart->remove_cart_item($cart_item_key);
                    continue;
                }
            }
            
            // Validate amount
            $amount = isset($cart_item['minsponsor_amount']) ? $cart_item['minsponsor_amount'] : null;
            if ($amount && (!is_numeric($amount) || $amount <= 0)) {
                wc_add_notice('Ugyldig støttebeløp. Produktet er fjernet fra handlekurven.', 'error');
                WC()->cart->remove_cart_item($cart_item_key);
            }
        }
    }
    
    /**
     * Initialize validation hooks
     */
    public function init_validation() {
        add_action('woocommerce_check_cart_items', array($this, 'validate_cart_items'));
    }
}
