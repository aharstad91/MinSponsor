<?php
/**
 * Meta Flow Handler for MinSponsor
 * 
 * Handles metadata flow from cart to order to subscription
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Checkout;

if (!defined('ABSPATH')) {
    exit;
}

class MetaFlow {
    
    /**
     * Initialize hooks
     */
    public function init(): void {
        // Order item meta
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'add_order_line_item_meta'], 10, 4);
        
        // Order meta - use multiple hooks to ensure we catch it
        add_action('woocommerce_checkout_create_order', [$this, 'add_order_meta'], 20, 2);
        
        // Also hook into order created (block checkout compatibility)
        add_action('woocommerce_checkout_order_created', [$this, 'sync_order_meta_from_items'], 10, 1);
        add_action('woocommerce_store_api_checkout_order_processed', [$this, 'sync_order_meta_from_items'], 10, 1);
        
        // Subscription meta (when subscription is created from order)
        add_action('woocommerce_subscriptions_created_subscription', [$this, 'add_subscription_meta'], 10, 2);
        
        // Display meta in admin
        add_action('woocommerce_admin_order_item_headers', [$this, 'add_admin_order_item_header']);
        add_action('woocommerce_admin_order_item_values', [$this, 'add_admin_order_item_values'], 10, 3);
        
        // Display meta on order details (customer view)
        add_filter('woocommerce_order_item_display_meta_key', [$this, 'customize_meta_display_key'], 10, 3);
        add_filter('woocommerce_order_item_display_meta_value', [$this, 'customize_meta_display_value'], 10, 3);
    }
    
    /**
     * Sync order meta from line items after order is fully created
     * This is needed for block-based checkout compatibility
     *
     * @param \WC_Order $order Order object
     */
    public function sync_order_meta_from_items(\WC_Order $order): void {
        error_log('MinSponsor MetaFlow: sync_order_meta_from_items called for order ' . $order->get_id());
        
        // Check if we already have the meta
        if ($order->get_meta('_minsponsor_player_name') || 
            $order->get_meta('_minsponsor_team_name') || 
            $order->get_meta('_minsponsor_club_name')) {
            error_log('MinSponsor MetaFlow: Order already has minsponsor meta, skipping');
            return;
        }
        
        $minsponsor_items = [];
        
        foreach ($order->get_items() as $item_id => $item) {
            $minsponsor_data = [];
            
            // Get meta from the item
            foreach ($item->get_meta_data() as $meta_item) {
                $key = $meta_item->key;
                if (str_starts_with($key, 'minsponsor_')) {
                    $minsponsor_data[$key] = $meta_item->value;
                    error_log('MinSponsor MetaFlow: Found item meta ' . $key . ' = ' . $meta_item->value);
                }
            }
            
            if (!empty($minsponsor_data)) {
                $minsponsor_items[$item_id] = $minsponsor_data;
            }
        }
        
        if (!empty($minsponsor_items)) {
            // Store aggregated data on order
            $order->update_meta_data('_minsponsor_items', $minsponsor_items);
            
            // Store first item's data as main order meta for easy access
            $first_item = reset($minsponsor_items);
            foreach ($first_item as $key => $value) {
                $order->update_meta_data('_' . $key, $value);
                error_log('MinSponsor MetaFlow: Setting order meta _' . $key . ' = ' . $value);
            }
            
            $order->save();
            error_log('MinSponsor MetaFlow: Order meta saved successfully');
        } else {
            error_log('MinSponsor MetaFlow: No minsponsor items found in order items');
        }
    }
    
    /**
     * Add MinSponsor metadata to order line items
     *
     * @param \WC_Order_Item_Product $item Order item
     * @param string $cart_item_key Cart item key
     * @param array $values Cart item data
     * @param \WC_Order $order Order object
     */
    public function add_order_line_item_meta(\WC_Order_Item_Product $item, string $cart_item_key, array $values, \WC_Order $order): void {
        if (!$this->is_minsponsor_cart_item($values)) {
            return;
        }
        
        $meta_keys = [
            'minsponsor_club_id',
            'minsponsor_club_name',
            'minsponsor_club_slug',
            'minsponsor_team_id',
            'minsponsor_team_name',
            'minsponsor_team_slug',
            'minsponsor_player_id',
            'minsponsor_player_name',
            'minsponsor_player_slug',
            'minsponsor_amount',
            'minsponsor_interval',
            'minsponsor_ref'
        ];
        
        foreach ($meta_keys as $key) {
            if (isset($values[$key])) {
                $item->add_meta_data($key, $values[$key]);
            }
        }
        
        // Add a source identifier
        $item->add_meta_data('minsponsor_source', 'player-link');
        
        // Save the item
        $item->save_meta_data();
    }
    
    /**
     * Add MinSponsor metadata to order
     *
     * @param \WC_Order $order Order object
     * @param array $data Checkout data
     */
    public function add_order_meta(\WC_Order $order, array $data): void {
        error_log('MinSponsor MetaFlow: add_order_meta called for order ' . $order->get_id());
        
        $minsponsor_items = [];
        
        foreach ($order->get_items() as $item_id => $item) {
            $meta = $item->get_meta_data();
            $minsponsor_data = [];
            
            error_log('MinSponsor MetaFlow: Checking order item ' . $item_id);
            
            foreach ($meta as $meta_item) {
                $key = $meta_item->key;
                if (str_starts_with($key, 'minsponsor_')) {
                    $minsponsor_data[$key] = $meta_item->value;
                    error_log('MinSponsor MetaFlow: Found meta ' . $key . ' = ' . $meta_item->value);
                }
            }
            
            if (!empty($minsponsor_data)) {
                $minsponsor_items[$item_id] = $minsponsor_data;
            }
        }
        
        error_log('MinSponsor MetaFlow: Total minsponsor items found: ' . count($minsponsor_items));
        
        if (!empty($minsponsor_items)) {
            // Store aggregated data on order
            $order->update_meta_data('_minsponsor_items', $minsponsor_items);
            
            // Store first item's data as main order meta for easy access
            $first_item = reset($minsponsor_items);
            foreach ($first_item as $key => $value) {
                $order->update_meta_data('_' . $key, $value);
                error_log('MinSponsor MetaFlow: Setting order meta _' . $key . ' = ' . $value);
            }
            
            $order->save_meta_data();
            error_log('MinSponsor MetaFlow: Order meta saved');
        } else {
            error_log('MinSponsor MetaFlow: No minsponsor items found in order items');
        }
    }
    
    /**
     * Add MinSponsor metadata to subscriptions
     *
     * @param \WC_Subscription $subscription Subscription object
     * @param \WC_Order $order Parent order
     */
    public function add_subscription_meta(\WC_Subscription $subscription, \WC_Order $order): void {
        $minsponsor_items = $order->get_meta('_minsponsor_items');
        
        if (empty($minsponsor_items)) {
            return;
        }
        
        // Copy all MinSponsor metadata to subscription
        foreach ($minsponsor_items as $item_id => $item_data) {
            foreach ($item_data as $key => $value) {
                $subscription->update_meta_data('_' . $key, $value);
            }
            break; // Only process first MinSponsor item for now
        }
        
        // Store original order ID for reference
        $subscription->update_meta_data('_minsponsor_original_order_id', $order->get_id());
        
        $subscription->save_meta_data();
        
        // Log subscription creation
        error_log(sprintf(
            'MinSponsor: Subscription %d created with metadata from order %d',
            $subscription->get_id(),
            $order->get_id()
        ));
    }
    
    /**
     * Check if cart item contains MinSponsor data
     *
     * @param array $cart_item Cart item data
     * @return bool
     */
    private function is_minsponsor_cart_item(array $cart_item): bool {
        return isset($cart_item['minsponsor_player_id']) || 
               isset($cart_item['minsponsor_interval']);
    }
    
    /**
     * Add header for MinSponsor data in admin order items
     */
    public function add_admin_order_item_header(): void {
        echo '<th class="minsponsor-meta">MinSponsor</th>';
    }
    
    /**
     * Display MinSponsor data in admin order items
     *
     * @param \WC_Product|null $product Product object
     * @param \WC_Order_Item $item Order item
     * @param int $item_id Item ID
     */
    public function add_admin_order_item_values(?\WC_Product $product, \WC_Order_Item $item, int $item_id): void {
        echo '<td class="minsponsor-meta">';
        
        $player_name = $item->get_meta('minsponsor_player_name');
        $team_name = $item->get_meta('minsponsor_team_name');
        $club_name = $item->get_meta('minsponsor_club_name');
        $interval = $item->get_meta('minsponsor_interval');
        $amount = $item->get_meta('minsponsor_amount');
        $ref = $item->get_meta('minsponsor_ref');
        
        if ($player_name) {
            echo '<strong>' . esc_html($player_name) . '</strong><br>';
            
            if ($team_name) {
                echo '<small>' . esc_html($team_name);
                if ($club_name) {
                    echo ', ' . esc_html($club_name);
                }
                echo '</small><br>';
            }
            
            if ($interval) {
                echo '<small><em>' . ($interval === 'once' ? 'Engang' : 'Månedlig') . '</em></small>';
            }
            
            if ($amount) {
                echo '<br><small>Beløp: ' . wc_price($amount) . '</small>';
            }
            
            if ($ref) {
                echo '<br><small>Ref: ' . esc_html($ref) . '</small>';
            }
        } else {
            echo '—';
        }
        
        echo '</td>';
    }
    
    /**
     * Customize meta key display for customers
     * Returns false to completely hide meta from customer view
     *
     * @param string $display_key Display key
     * @param \WC_Meta_Data $meta Meta data object
     * @param \WC_Order_Item $item Order item
     * @return string|false
     */
    public function customize_meta_display_key(string $display_key, \WC_Meta_Data $meta, \WC_Order_Item $item): string|false {
        $key = $meta->key;
        
        // On the thank you page and customer order view, hide ALL MinSponsor meta
        // since we display it in a custom format via StripeCustomerPortal
        if (!is_admin()) {
            // Hide all minsponsor_ prefixed keys from customer view
            if (str_starts_with($key, 'minsponsor_')) {
                return false;
            }
        }
        
        // For admin view, show nice labels
        $labels = [
            'minsponsor_player_name' => 'Spiller',
            'minsponsor_team_name' => 'Lag',
            'minsponsor_club_name' => 'Klubb',
            'minsponsor_interval' => 'Type',
            'minsponsor_amount' => 'Beløp',
            'minsponsor_ref' => 'Referanse'
        ];
        
        if (isset($labels[$key])) {
            return $labels[$key];
        }
        
        // Hide internal IDs and slugs from admin view too
        $hidden_keys = [
            'minsponsor_club_id',
            'minsponsor_club_slug',
            'minsponsor_team_id',
            'minsponsor_team_slug',
            'minsponsor_player_id',
            'minsponsor_player_slug',
            'minsponsor_source'
        ];
        
        if (in_array($key, $hidden_keys, true)) {
            return false;
        }
        
        return $display_key;
    }
    
    /**
     * Customize meta value display for customers
     *
     * @param string $display_value Display value
     * @param \WC_Meta_Data $meta Meta data object
     * @param \WC_Order_Item $item Order item
     * @return string
     */
    public function customize_meta_display_value(string $display_value, \WC_Meta_Data $meta, \WC_Order_Item $item): string {
        $key = $meta->key;
        $value = $meta->value;
        
        switch ($key) {
            case 'minsponsor_interval':
                return $value === 'once' ? 'Engangsbeløp' : 'Månedlig abonnement';
                
            case 'minsponsor_amount':
                return wc_price($value);
                
            default:
                return $display_value;
        }
    }
    
    /**
     * Get MinSponsor metadata for payment gateways
     *
     * @param \WC_Order $order Order object
     * @return array Formatted metadata for payment gateways
     */
    public static function get_payment_metadata(\WC_Order $order): array {
        $metadata = [];
        
        // Get MinSponsor data from order
        $club_name = $order->get_meta('_minsponsor_club_name');
        $team_name = $order->get_meta('_minsponsor_team_name');
        $player_name = $order->get_meta('_minsponsor_player_name');
        $amount = $order->get_meta('_minsponsor_amount');
        $interval = $order->get_meta('_minsponsor_interval');
        $ref = $order->get_meta('_minsponsor_ref');
        
        if ($club_name) {
            $metadata['club'] = $club_name;
        }
        
        if ($team_name) {
            $metadata['team'] = $team_name;
        }
        
        if ($player_name) {
            $metadata['player'] = $player_name;
        }
        
        if ($amount) {
            $metadata['amount'] = $amount;
        }
        
        if ($interval) {
            $metadata['interval'] = $interval;
        }
        
        if ($ref) {
            $metadata['ref'] = $ref;
        }
        
        // Always add source identifier
        $metadata['minsponsor_source'] = 'player-link';
        
        return $metadata;
    }
}
