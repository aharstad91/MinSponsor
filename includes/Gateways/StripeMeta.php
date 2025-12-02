<?php
/**
 * Stripe Metadata Handler for MinSponsor
 * 
 * Injects MinSponsor metadata into Stripe Payment Intents
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Gateways;

use MinSponsor\Checkout\MetaFlow;

if (!defined('ABSPATH')) {
    exit;
}

class StripeMeta {
    
    /**
     * Initialize hooks
     */
    public function init(): void {
        // Hook into WooCommerce Stripe gateway
        add_filter('wc_stripe_payment_intent_args', [$this, 'add_payment_intent_metadata'], 10, 2);
        add_filter('wc_stripe_setup_intent_args', [$this, 'add_setup_intent_metadata'], 10, 2);
        
        // For subscription renewals
        add_filter('woocommerce_stripe_subscription_payment_intent_args', [$this, 'add_subscription_payment_metadata'], 10, 3);
        
        // Alternative hook names that might be used by different versions
        add_filter('wc_stripe_payment_request_args', [$this, 'add_payment_request_metadata'], 10, 2);
    }
    
    /**
     * Add MinSponsor metadata to Stripe Payment Intent
     *
     * @param array $args Payment Intent arguments
     * @param \WC_Order $order Order object
     * @return array Modified arguments
     */
    public function add_payment_intent_metadata(array $args, \WC_Order $order): array {
        $minsponsor_metadata = MetaFlow::get_payment_metadata($order);
        
        if (!empty($minsponsor_metadata)) {
            if (!isset($args['metadata'])) {
                $args['metadata'] = [];
            }
            
            $args['metadata'] = array_merge($args['metadata'], $minsponsor_metadata);
            
            // Log metadata addition
            error_log(sprintf(
                'MinSponsor Stripe: Added metadata to Payment Intent for order %d: %s',
                $order->get_id(),
                wp_json_encode($minsponsor_metadata)
            ));
        }
        
        return $args;
    }
    
    /**
     * Add MinSponsor metadata to Stripe Setup Intent (for subscription setup)
     *
     * @param array $args Setup Intent arguments
     * @param \WC_Order $order Order object
     * @return array Modified arguments
     */
    public function add_setup_intent_metadata(array $args, \WC_Order $order): array {
        return $this->add_payment_intent_metadata($args, $order);
    }
    
    /**
     * Add MinSponsor metadata to subscription payment
     *
     * @param array $args Payment Intent arguments
     * @param \WC_Subscription $subscription Subscription object
     * @param \WC_Order $order Renewal order
     * @return array Modified arguments
     */
    public function add_subscription_payment_metadata(array $args, \WC_Subscription $subscription, \WC_Order $order): array {
        // For subscription renewals, get metadata from subscription
        $minsponsor_metadata = $this->get_subscription_metadata($subscription);
        
        if (!empty($minsponsor_metadata)) {
            if (!isset($args['metadata'])) {
                $args['metadata'] = [];
            }
            
            $args['metadata'] = array_merge($args['metadata'], $minsponsor_metadata);
            
            // Log metadata addition
            error_log(sprintf(
                'MinSponsor Stripe: Added subscription metadata to Payment Intent for subscription %d: %s',
                $subscription->get_id(),
                wp_json_encode($minsponsor_metadata)
            ));
        }
        
        return $args;
    }
    
    /**
     * Add MinSponsor metadata to payment request
     *
     * @param array $args Payment request arguments
     * @param \WC_Order $order Order object
     * @return array Modified arguments
     */
    public function add_payment_request_metadata(array $args, \WC_Order $order): array {
        return $this->add_payment_intent_metadata($args, $order);
    }
    
    /**
     * Get MinSponsor metadata from subscription
     *
     * @param \WC_Subscription $subscription Subscription object
     * @return array Metadata array
     */
    private function get_subscription_metadata(\WC_Subscription $subscription): array {
        $metadata = [];
        
        $club_name = $subscription->get_meta('_minsponsor_club_name');
        $team_name = $subscription->get_meta('_minsponsor_team_name');
        $player_name = $subscription->get_meta('_minsponsor_player_name');
        $amount = $subscription->get_meta('_minsponsor_amount');
        $interval = $subscription->get_meta('_minsponsor_interval');
        $ref = $subscription->get_meta('_minsponsor_ref');
        
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
        
        // Add subscription ID for tracking
        $metadata['subscription_id'] = $subscription->get_id();
        
        return $metadata;
    }
    
    /**
     * Check if Stripe gateway is available
     *
     * @return bool
     */
    public static function is_stripe_available(): bool {
        return class_exists('WC_Stripe_Payment_Gateway') || 
               class_exists('WC_Gateway_Stripe');
    }
    
    /**
     * Get Stripe customer ID from order
     *
     * @param \WC_Order $order Order object
     * @return string|null Stripe customer ID
     */
    private function get_stripe_customer_id(\WC_Order $order): ?string {
        // Try different meta keys used by Stripe gateway
        $customer_id = $order->get_meta('_stripe_customer_id');
        
        if (!$customer_id) {
            $customer_id = $order->get_meta('_stripe_source_id');
        }
        
        return $customer_id ?: null;
    }
    
    /**
     * Add metadata to existing Stripe customer (if possible)
     *
     * @param \WC_Order $order Order object
     */
    public function update_stripe_customer_metadata(\WC_Order $order): void {
        if (!self::is_stripe_available()) {
            return;
        }
        
        $customer_id = $this->get_stripe_customer_id($order);
        if (!$customer_id) {
            return;
        }
        
        $minsponsor_metadata = MetaFlow::get_payment_metadata($order);
        if (empty($minsponsor_metadata)) {
            return;
        }
        
        try {
            // This would require direct Stripe API access
            // Implementation depends on how the Stripe gateway exposes its API
            do_action('minsponsor_update_stripe_customer', $customer_id, $minsponsor_metadata, $order);
        } catch (\Exception $e) {
            error_log('MinSponsor Stripe: Failed to update customer metadata: ' . $e->getMessage());
        }
    }
}
