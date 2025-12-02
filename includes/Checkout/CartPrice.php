<?php
/**
 * Cart Price Handler for MinSponsor
 * 
 * Handles dynamic pricing for player sponsorship items
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Checkout;

if (!defined('ABSPATH')) {
    exit;
}

class CartPrice {
    
    /**
     * Initialize hooks
     */
    public function init(): void {
        add_action('woocommerce_before_calculate_totals', [$this, 'apply_dynamic_pricing'], 20);
        add_filter('woocommerce_cart_item_name', [$this, 'customize_cart_item_name'], 10, 3);
        add_filter('woocommerce_cart_item_price', [$this, 'customize_cart_item_price'], 10, 3);
        
        // Add item data display (works for both classic and block checkout)
        add_filter('woocommerce_get_item_data', [$this, 'add_cart_item_data_display'], 10, 2);
    }
    
    /**
     * Initialize validation hooks
     */
    public function init_validation(): void {
        add_action('woocommerce_check_cart_items', [$this, 'validate_cart_items']);
    }
    
    /**
     * Apply dynamic pricing to cart items
     *
     * @param \WC_Cart $cart Cart object
     */
    public function apply_dynamic_pricing(\WC_Cart $cart): void {
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
                $cart_item['data']->set_price((float) $custom_amount);
                
                // For subscription products, also set the subscription price
                if ($this->is_subscription_product($cart_item['data'])) {
                    $this->set_subscription_price($cart_item['data'], (float) $custom_amount);
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
    private function is_minsponsor_item(array $cart_item): bool {
        return isset($cart_item['minsponsor_player_id']) || 
               isset($cart_item['minsponsor_interval']);
    }
    
    /**
     * Check if product is a subscription product
     *
     * @param \WC_Product $product Product object
     * @return bool
     */
    private function is_subscription_product(\WC_Product $product): bool {
        return class_exists('WC_Subscriptions_Product') && 
               \WC_Subscriptions_Product::is_subscription($product);
    }
    
    /**
     * Set subscription price for subscription products
     *
     * @param \WC_Product $product Product object
     * @param float $price New price
     */
    private function set_subscription_price(\WC_Product $product, float $price): void {
        if (!$this->is_subscription_product($product)) {
            return;
        }
        
        // Use setters for internal price fields (WooCommerce 3.2+ requirement)
        $product->set_price($price);
        $product->set_regular_price($price);
        
        // Set subscription-specific price using the correct method
        if (method_exists($product, 'set_subscription_price')) {
            $product->set_subscription_price($price);
        } else {
            // Fallback for older WooCommerce Subscriptions versions
            $product->update_meta_data('_subscription_price', $price);
        }
    }
    
    /**
     * Customize cart item name to show sponsorship details
     *
     * @param string $name Item name
     * @param array $cart_item Cart item data
     * @param string $cart_item_key Cart item key
     * @return string Modified name
     */
    public function customize_cart_item_name(string $name, array $cart_item, string $cart_item_key): string {
        if (!$this->is_minsponsor_item($cart_item)) {
            return $name;
        }
        
        $player_name = isset($cart_item['minsponsor_player_name']) ? $cart_item['minsponsor_player_name'] : '';
        $team_name = isset($cart_item['minsponsor_team_name']) ? $cart_item['minsponsor_team_name'] : '';
        $club_name = isset($cart_item['minsponsor_club_name']) ? $cart_item['minsponsor_club_name'] : '';
        $recipient_type = isset($cart_item['minsponsor_recipient_type']) ? $cart_item['minsponsor_recipient_type'] : '';
        $interval = isset($cart_item['minsponsor_interval']) ? $cart_item['minsponsor_interval'] : '';
        
        // Determine recipient name based on type
        $recipient_name = '';
        if ($recipient_type === 'spiller' && $player_name) {
            $recipient_name = $player_name;
        } elseif ($recipient_type === 'lag' && $team_name) {
            $recipient_name = $team_name;
        } elseif ($recipient_type === 'klubb' && $club_name) {
            $recipient_name = $club_name;
        } elseif ($player_name) {
            $recipient_name = $player_name;
        } elseif ($team_name) {
            $recipient_name = $team_name;
        } elseif ($club_name) {
            $recipient_name = $club_name;
        }
        
        if ($recipient_name) {
            $sponsorship_details = '<br><small style="color: #666;">';
            $sponsorship_details .= '<strong>Støtte til:</strong> ' . esc_html($recipient_name);
            
            // Add hierarchy info
            $hierarchy_parts = [];
            if ($recipient_type === 'spiller') {
                if ($club_name) $hierarchy_parts[] = $club_name;
                if ($team_name) $hierarchy_parts[] = $team_name;
            } elseif ($recipient_type === 'lag') {
                if ($club_name) $hierarchy_parts[] = $club_name;
            }
            
            if (!empty($hierarchy_parts)) {
                $sponsorship_details .= '<br><span style="opacity: 0.8;">' . esc_html(implode(' › ', $hierarchy_parts)) . '</span>';
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
     * Customize cart item name for block checkout (Store API)
     *
     * @param string $name Item name
     * @param array $cart_item Cart item data
     * @return string Modified name
     */
    public function customize_block_cart_item_name(string $name, array $cart_item): string {
        if (!$this->is_minsponsor_item($cart_item)) {
            return $name;
        }
        
        $player_name = isset($cart_item['minsponsor_player_name']) ? $cart_item['minsponsor_player_name'] : '';
        $team_name = isset($cart_item['minsponsor_team_name']) ? $cart_item['minsponsor_team_name'] : '';
        $club_name = isset($cart_item['minsponsor_club_name']) ? $cart_item['minsponsor_club_name'] : '';
        $recipient_type = isset($cart_item['minsponsor_recipient_type']) ? $cart_item['minsponsor_recipient_type'] : '';
        
        // Determine recipient name based on type
        $recipient_name = '';
        if ($recipient_type === 'spiller' && $player_name) {
            $recipient_name = $player_name;
        } elseif ($recipient_type === 'lag' && $team_name) {
            $recipient_name = $team_name;
        } elseif ($recipient_type === 'klubb' && $club_name) {
            $recipient_name = $club_name;
        } elseif ($player_name) {
            $recipient_name = $player_name;
        } elseif ($team_name) {
            $recipient_name = $team_name;
        } elseif ($club_name) {
            $recipient_name = $club_name;
        }
        
        if ($recipient_name) {
            // Build hierarchy for context
            $hierarchy_parts = [];
            if ($recipient_type === 'spiller') {
                if ($club_name) $hierarchy_parts[] = $club_name;
                if ($team_name) $hierarchy_parts[] = $team_name;
            } elseif ($recipient_type === 'lag') {
                if ($club_name) $hierarchy_parts[] = $club_name;
            }
            
            // For block checkout, we return a cleaner format
            // The name will be shown in order summary
            $name = 'Støtt ' . $recipient_name;
            if (!empty($hierarchy_parts)) {
                $name .= ' (' . implode(' › ', $hierarchy_parts) . ')';
            }
        }
        
        return $name;
    }
    
    /**
     * Add cart item data display for order summary
     * This works for both classic checkout and block checkout
     *
     * @param array $item_data Existing item data
     * @param array $cart_item Cart item
     * @return array Modified item data
     */
    public function add_cart_item_data_display(array $item_data, array $cart_item): array {
        if (!$this->is_minsponsor_item($cart_item)) {
            return $item_data;
        }
        
        $player_name = isset($cart_item['minsponsor_player_name']) ? $cart_item['minsponsor_player_name'] : '';
        $team_name = isset($cart_item['minsponsor_team_name']) ? $cart_item['minsponsor_team_name'] : '';
        $club_name = isset($cart_item['minsponsor_club_name']) ? $cart_item['minsponsor_club_name'] : '';
        $recipient_type = isset($cart_item['minsponsor_recipient_type']) ? $cart_item['minsponsor_recipient_type'] : '';
        
        // Determine recipient name based on type
        $recipient_name = '';
        if ($recipient_type === 'spiller' && $player_name) {
            $recipient_name = $player_name;
        } elseif ($recipient_type === 'lag' && $team_name) {
            $recipient_name = $team_name;
        } elseif ($recipient_type === 'klubb' && $club_name) {
            $recipient_name = $club_name;
        } elseif ($player_name) {
            $recipient_name = $player_name;
        } elseif ($team_name) {
            $recipient_name = $team_name;
        } elseif ($club_name) {
            $recipient_name = $club_name;
        }
        
        if ($recipient_name) {
            // Add recipient info
            $item_data[] = [
                'key' => 'Støtter',
                'value' => $recipient_name,
            ];
            
            // Add hierarchy info if applicable
            $hierarchy_parts = [];
            if ($recipient_type === 'spiller') {
                if ($club_name) $hierarchy_parts[] = $club_name;
                if ($team_name) $hierarchy_parts[] = $team_name;
            } elseif ($recipient_type === 'lag') {
                if ($club_name) $hierarchy_parts[] = $club_name;
            }
            
            if (!empty($hierarchy_parts)) {
                $item_data[] = [
                    'key' => 'Tilhørighet',
                    'value' => implode(' › ', $hierarchy_parts),
                ];
            }
        }
        
        return $item_data;
    }
    
    /**
     * Customize cart item price display
     *
     * @param string $price Price HTML
     * @param array $cart_item Cart item data
     * @param string $cart_item_key Cart item key
     * @return string Modified price HTML
     */
    public function customize_cart_item_price(string $price, array $cart_item, string $cart_item_key): string {
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
     * Validate cart items
     */
    public function validate_cart_items(): void {
        foreach (\WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (!$this->is_minsponsor_item($cart_item)) {
                continue;
            }
            
            // Validate player still exists and is published
            $player_id = isset($cart_item['minsponsor_player_id']) ? $cart_item['minsponsor_player_id'] : null;
            if ($player_id) {
                $player = get_post($player_id);
                if (!$player || $player->post_status !== 'publish' || $player->post_type !== 'spiller') {
                    wc_add_notice('Spilleren du prøver å støtte er ikke lenger tilgjengelig.', 'error');
                    \WC()->cart->remove_cart_item($cart_item_key);
                    continue;
                }
            }
            
            // Validate amount
            $amount = isset($cart_item['minsponsor_amount']) ? $cart_item['minsponsor_amount'] : null;
            if ($amount && (!is_numeric($amount) || $amount <= 0)) {
                wc_add_notice('Ugyldig støttebeløp. Produktet er fjernet fra handlekurven.', 'error');
                \WC()->cart->remove_cart_item($cart_item_key);
            }
        }
    }
}
