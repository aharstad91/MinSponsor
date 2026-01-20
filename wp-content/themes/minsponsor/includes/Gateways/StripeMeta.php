<?php
/**
 * Stripe Metadata Handler for MinSponsor
 * 
 * Injects MinSponsor metadata and transfer_data into Stripe Payment Intents
 * for Stripe Connect integration.
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Gateways;

use MinSponsor\Checkout\MetaFlow;
use MinSponsor\Services\FeeCalculator;
use MinSponsor\Settings\StripeSettings;

if (!defined('ABSPATH')) {
    exit;
}

class StripeMeta {
    
    /**
     * Initialize hooks
     */
    public function init(): void {
        // Primary hook for WooCommerce Stripe gateway - this is the main hook for intent creation
        add_filter('wc_stripe_generate_create_intent_request', [$this, 'add_intent_request_data'], 10, 3);
        
        // Hook for updating existing intents
        add_filter('wc_stripe_update_existing_intent_request', [$this, 'add_intent_request_data'], 10, 3);
        
        // Fallback hooks for older versions
        add_filter('wc_stripe_payment_intent_args', [$this, 'add_payment_intent_metadata'], 10, 2);
        add_filter('wc_stripe_setup_intent_args', [$this, 'add_setup_intent_metadata'], 10, 2);
        
        // For subscription renewals
        add_filter('woocommerce_stripe_subscription_payment_intent_args', [$this, 'add_subscription_payment_metadata'], 10, 3);
        
        // Alternative hook names that might be used by different versions
        add_filter('wc_stripe_payment_request_args', [$this, 'add_payment_request_metadata'], 10, 2);
    }
    
    /**
     * Add MinSponsor data to Stripe intent request
     * 
     * This is the primary hook for modern WooCommerce Stripe versions.
     *
     * @param array $request Intent request arguments
     * @param \WC_Order $order Order object
     * @param mixed $prepared_source Prepared payment source
     * @return array Modified request
     */
    public function add_intent_request_data(array $request, \WC_Order $order, $prepared_source = null): array {
        error_log('MinSponsor Stripe: add_intent_request_data called for order ' . $order->get_id());
        
        // Add metadata
        $minsponsor_metadata = MetaFlow::get_payment_metadata($order);
        
        if (!empty($minsponsor_metadata)) {
            if (!isset($request['metadata'])) {
                $request['metadata'] = [];
            }
            $request['metadata'] = array_merge($request['metadata'], $minsponsor_metadata);
        }
        
        // Add Stripe Connect transfer_data
        $request = $this->add_transfer_data($request, $order);
        
        error_log(sprintf(
            'MinSponsor Stripe: Intent request for order %d: transfer_data=%s, metadata=%s',
            $order->get_id(),
            isset($request['transfer_data']) ? wp_json_encode($request['transfer_data']) : 'null',
            isset($request['metadata']) ? wp_json_encode(array_filter($request['metadata'], fn($k) => str_starts_with($k, 'minsponsor') || in_array($k, ['org_id', 'group_id', 'singular_id', 'destination_account']), ARRAY_FILTER_USE_KEY)) : 'null'
        ));
        
        return $request;
    }
    
    /**
     *
     * @param array $args Payment Intent arguments
     * @param \WC_Order $order Order object
     * @return array Modified arguments
     */
    public function add_payment_intent_metadata(array $args, \WC_Order $order): array {
        // Add standard metadata
        $minsponsor_metadata = MetaFlow::get_payment_metadata($order);
        
        if (!empty($minsponsor_metadata)) {
            if (!isset($args['metadata'])) {
                $args['metadata'] = [];
            }
            
            $args['metadata'] = array_merge($args['metadata'], $minsponsor_metadata);
        }
        
        // Add Stripe Connect transfer_data if applicable
        $args = $this->add_transfer_data($args, $order);
        
        // Log for debugging
        error_log(sprintf(
            'MinSponsor Stripe: Payment Intent args for order %d: %s',
            $order->get_id(),
            wp_json_encode(array_intersect_key($args, array_flip(['metadata', 'transfer_data', 'application_fee_amount'])))
        ));
        
        return $args;
    }
    
    /**
     * Add Stripe Connect transfer_data to Payment Intent
     *
     * Routes payment to connected Stripe account with calculated fees.
     *
     * @param array $args Payment Intent arguments
     * @param \WC_Order $order Order object
     * @return array Modified arguments
     */
    private function add_transfer_data(array $args, \WC_Order $order): array {
        // Get the team ID from order (for both team and player sponsorships)
        $team_id = $this->get_destination_team_id($order);
        
        if (!$team_id) {
            error_log('MinSponsor Stripe: No team ID found for order ' . $order->get_id() . ', skipping transfer_data');
            return $args;
        }
        
        // Get Stripe account ID for the team
        $stripe_account_id = get_post_meta($team_id, '_minsponsor_stripe_account_id', true);
        
        if (!$stripe_account_id) {
            error_log('MinSponsor Stripe: No Stripe account for team ' . $team_id . ', skipping transfer_data');
            return $args;
        }
        
        // Check if account is active
        $stripe_status = get_post_meta($team_id, '_minsponsor_stripe_status', true);
        if ($stripe_status !== 'complete') {
            error_log('MinSponsor Stripe: Team ' . $team_id . ' Stripe status is ' . $stripe_status . ', skipping transfer_data');
            return $args;
        }
        
        // Get sponsor amount from order
        $sponsor_amount = $this->get_sponsor_amount($order);
        
        if (!$sponsor_amount || $sponsor_amount <= 0) {
            error_log('MinSponsor Stripe: No valid sponsor amount for order ' . $order->get_id());
            return $args;
        }
        
        // Calculate fees
        $calculation = FeeCalculator::calculate($sponsor_amount);
        $total_ore = FeeCalculator::toOre($calculation['total']);
        $sponsor_amount_ore = FeeCalculator::toOre($sponsor_amount);
        $application_fee_ore = $total_ore - $sponsor_amount_ore;
        
        // Add transfer_data to Payment Intent
        $args['transfer_data'] = [
            'destination' => $stripe_account_id,
            'amount' => $sponsor_amount_ore, // What the recipient gets
        ];
        
        // Add application_fee_amount (alternative to transfer_data.amount)
        // Note: When using transfer_data.amount, application_fee is implicit
        // $args['application_fee_amount'] = $application_fee_ore;
        
        // Add metadata about the transfer
        if (!isset($args['metadata'])) {
            $args['metadata'] = [];
        }
        $args['metadata']['destination_account'] = $stripe_account_id;
        $args['metadata']['destination_team_id'] = $team_id;
        $args['metadata']['sponsor_amount'] = $sponsor_amount;
        $args['metadata']['platform_fee'] = $calculation['platform_fee'];
        
        error_log(sprintf(
            'MinSponsor Stripe: Added transfer_data for order %d: destination=%s, amount=%d øre, total=%d øre',
            $order->get_id(),
            $stripe_account_id,
            $sponsor_amount_ore,
            $total_ore
        ));
        
        return $args;
    }
    
    /**
     * Get the team ID that should receive the payment
     *
     * For player sponsorships, we route to the parent team.
     * For team sponsorships, we route to the team itself.
     *
     * @param \WC_Order $order Order object
     * @return int|null Team ID or null
     */
    private function get_destination_team_id(\WC_Order $order): ?int {
        // First try to get team_id directly from order meta
        $team_id = $order->get_meta('_minsponsor_team_id');
        
        if ($team_id) {
            return (int) $team_id;
        }
        
        // Check order items for team_id
        foreach ($order->get_items() as $item) {
            $item_team_id = $item->get_meta('minsponsor_team_id');
            if ($item_team_id) {
                return (int) $item_team_id;
            }
            
            // For player sponsorships, get parent team
            $player_id = $item->get_meta('minsponsor_player_id');
            if ($player_id) {
                $parent_team_id = get_post_meta($player_id, 'parent_lag', true);
                if ($parent_team_id) {
                    return (int) $parent_team_id;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get sponsor amount from order
     *
     * @param \WC_Order $order Order object
     * @return float|null Sponsor amount or null
     */
    private function get_sponsor_amount(\WC_Order $order): ?float {
        // Try order meta first
        $amount = $order->get_meta('_minsponsor_amount');
        
        if ($amount) {
            return (float) $amount;
        }
        
        // Check order items
        foreach ($order->get_items() as $item) {
            $item_amount = $item->get_meta('minsponsor_amount');
            if ($item_amount) {
                return (float) $item_amount;
            }
        }
        
        return null;
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
        }
        
        // Add Stripe Connect transfer_data for renewals
        $args = $this->add_subscription_transfer_data($args, $subscription);
        
        // Log for debugging
        error_log(sprintf(
            'MinSponsor Stripe: Subscription Payment Intent args for subscription %d: %s',
            $subscription->get_id(),
            wp_json_encode(array_intersect_key($args, array_flip(['metadata', 'transfer_data', 'application_fee_amount'])))
        ));
        
        return $args;
    }
    
    /**
     * Add Stripe Connect transfer_data for subscription renewals
     *
     * @param array $args Payment Intent arguments
     * @param \WC_Subscription $subscription Subscription object
     * @return array Modified arguments
     */
    private function add_subscription_transfer_data(array $args, \WC_Subscription $subscription): array {
        // Get team ID from subscription
        $team_id = $subscription->get_meta('_minsponsor_team_id');
        
        if (!$team_id) {
            // Try to get from player
            $player_id = $subscription->get_meta('_minsponsor_player_id');
            if ($player_id) {
                $team_id = get_post_meta($player_id, 'parent_lag', true);
            }
        }
        
        if (!$team_id) {
            error_log('MinSponsor Stripe: No team ID for subscription ' . $subscription->get_id());
            return $args;
        }
        
        // Get Stripe account ID
        $stripe_account_id = get_post_meta($team_id, '_minsponsor_stripe_account_id', true);
        $stripe_status = get_post_meta($team_id, '_minsponsor_stripe_status', true);
        
        if (!$stripe_account_id || $stripe_status !== 'complete') {
            error_log('MinSponsor Stripe: Team ' . $team_id . ' Stripe not ready for subscription renewal');
            return $args;
        }
        
        // Get sponsor amount
        $sponsor_amount = (float) $subscription->get_meta('_minsponsor_amount');
        
        if (!$sponsor_amount || $sponsor_amount <= 0) {
            error_log('MinSponsor Stripe: No valid sponsor amount for subscription ' . $subscription->get_id());
            return $args;
        }
        
        // Calculate and add transfer_data
        $sponsor_amount_ore = FeeCalculator::toOre($sponsor_amount);
        
        $args['transfer_data'] = [
            'destination' => $stripe_account_id,
            'amount' => $sponsor_amount_ore,
        ];
        
        if (!isset($args['metadata'])) {
            $args['metadata'] = [];
        }
        $args['metadata']['destination_account'] = $stripe_account_id;
        $args['metadata']['destination_team_id'] = $team_id;
        
        error_log(sprintf(
            'MinSponsor Stripe: Added subscription transfer_data: destination=%s, amount=%d øre',
            $stripe_account_id,
            $sponsor_amount_ore
        ));
        
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
