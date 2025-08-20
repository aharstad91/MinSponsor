<?php
/**
 * Meta Flow Handler for MinSponsor
 * 
 * Handles metadata flow from cart to order to subscription
 *
 * @package MinSponsor
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MinSponsor_MetaFlow {
    
    /**
     * Initialize hooks
     */
    public function init() {
        // Order item meta
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_order_line_item_meta'), 10, 4);
        
        // Order meta
        add_action('woocommerce_checkout_create_order', array($this, 'add_order_meta'), 10, 2);
        
        // Subscription meta (when subscription is created from order)
        add_action('woocommerce_subscriptions_created_subscription', array($this, 'add_subscription_meta'), 10, 2);
        
        // Display meta in admin
        add_action('woocommerce_admin_order_item_headers', array($this, 'add_admin_order_item_header'));
        add_action('woocommerce_admin_order_item_values', array($this, 'add_admin_order_item_values'), 10, 3);
        
        // Display meta on order details (customer view)
        add_filter('woocommerce_order_item_display_meta_key', array($this, 'customize_meta_display_key'), 10, 3);
        add_filter('woocommerce_order_item_display_meta_value', array($this, 'customize_meta_display_value'), 10, 3);
    }
    
    /**
     * Add MinSponsor metadata to order line items
     *
     * @param WC_Order_Item_Product $item Order item
     * @param string $cart_item_key Cart item key
     * @param array $values Cart item data
     * @param WC_Order $order Order object
     */
    public function add_order_line_item_meta($item, $cart_item_key, $values, $order) {
        if (!$this->is_minsponsor_cart_item($values)) {
            return;
        }
        
        $meta_keys = array(
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
        );
        
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
     * @param WC_Order $order Order object
     * @param array $data Checkout data
     */
    public function add_order_meta($order, $data) {
        $minsponsor_items = array();
        
        foreach ($order->get_items() as $item_id => $item) {
            $meta = $item->get_meta_data();
            $minsponsor_data = array();
            
            foreach ($meta as $meta_item) {
                $key = $meta_item->key;
                if (strpos($key, 'minsponsor_') === 0) {
                    $minsponsor_data[$key] = $meta_item->value;
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
            }
            
            $order->save_meta_data();
        }
    }
    
    /**
     * Add MinSponsor metadata to subscriptions
     *
     * @param WC_Subscription $subscription Subscription object
     * @param WC_Order $order Parent order
     */
    public function add_subscription_meta($subscription, $order) {
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
    private function is_minsponsor_cart_item($cart_item) {
        return isset($cart_item['minsponsor_player_id']) || 
               isset($cart_item['minsponsor_interval']);
    }
    
    /**
     * Add header for MinSponsor data in admin order items
     */
    public function add_admin_order_item_header() {
        echo '<th class="minsponsor-meta">MinSponsor</th>';
    }
    
    /**
     * Display MinSponsor data in admin order items
     *
     * @param WC_Product $product Product object
     * @param WC_Order_Item $item Order item
     * @param int $item_id Item ID
     */
    public function add_admin_order_item_values($product, $item, $item_id) {
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
     *
     * @param string $display_key Display key
     * @param WC_Meta_Data $meta Meta data object
     * @param WC_Order_Item $item Order item
     * @return string
     */
    public function customize_meta_display_key($display_key, $meta, $item) {
        $key = $meta->key;
        
        $labels = array(
            'minsponsor_player_name' => 'Spiller',
            'minsponsor_team_name' => 'Lag',
            'minsponsor_club_name' => 'Klubb',
            'minsponsor_interval' => 'Type',
            'minsponsor_amount' => 'Beløp',
            'minsponsor_ref' => 'Referanse'
        );
        
        if (isset($labels[$key])) {
            return $labels[$key];
        }
        
        // Hide internal IDs and slugs from customer view
        $hidden_keys = array(
            'minsponsor_club_id',
            'minsponsor_club_slug',
            'minsponsor_team_id',
            'minsponsor_team_slug',
            'minsponsor_player_id',
            'minsponsor_player_slug',
            'minsponsor_source'
        );
        
        if (in_array($key, $hidden_keys)) {
            return false; // This will hide the meta from customer view
        }
        
        return $display_key;
    }
    
    /**
     * Customize meta value display for customers
     *
     * @param string $display_value Display value
     * @param WC_Meta_Data $meta Meta data object
     * @param WC_Order_Item $item Order item
     * @return string
     */
    public function customize_meta_display_value($display_value, $meta, $item) {
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
     * @param WC_Order $order Order object
     * @return array Formatted metadata for payment gateways
     */
    public static function get_payment_metadata($order) {
        $metadata = array();
        
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
