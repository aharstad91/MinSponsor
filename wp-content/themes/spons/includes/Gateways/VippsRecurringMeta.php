<?php
/**
 * Vipps/MobilePay Recurring Metadata Handler for MinSponsor
 * 
 * Injects MinSponsor metadata into Vipps/MobilePay Recurring agreements
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Gateways;

use MinSponsor\Checkout\MetaFlow;

if (!defined('ABSPATH')) {
    exit;
}

class VippsRecurringMeta {
    
    /**
     * Initialize hooks
     */
    public function init(): void {
        // Hook into Vipps/MobilePay Recurring plugin
        // Note: These hooks depend on the specific Vipps plugin being used
        
        // Common hooks for Vipps Recurring
        add_filter('vipps_recurring_agreement_data', [$this, 'add_agreement_metadata'], 10, 2);
        add_filter('vipps_recurring_charge_data', [$this, 'add_charge_metadata'], 10, 3);
        
        // Alternative hook names
        add_filter('woo_vipps_recurring_agreement_args', [$this, 'add_agreement_metadata'], 10, 2);
        add_filter('woo_vipps_recurring_charge_args', [$this, 'add_charge_metadata'], 10, 3);
        
        // MobilePay specific hooks (if different plugin is used)
        add_filter('mobilepay_recurring_agreement_data', [$this, 'add_agreement_metadata'], 10, 2);
        add_filter('mobilepay_recurring_charge_data', [$this, 'add_charge_metadata'], 10, 3);
        
        // Generic hooks that might be used by different implementations
        add_action('woocommerce_subscription_payment_method_updated', [$this, 'update_agreement_metadata'], 10, 2);
    }
    
    /**
     * Add MinSponsor metadata to Vipps Recurring agreement
     *
     * @param array $agreement_data Agreement data
     * @param \WC_Order $order Order object
     * @return array Modified agreement data
     */
    public function add_agreement_metadata(array $agreement_data, \WC_Order $order): array {
        $minsponsor_metadata = MetaFlow::get_payment_metadata($order);
        
        if (!empty($minsponsor_metadata)) {
            // Add metadata to agreement
            if (!isset($agreement_data['metadata'])) {
                $agreement_data['metadata'] = [];
            }
            
            $agreement_data['metadata'] = array_merge($agreement_data['metadata'], $minsponsor_metadata);
            
            // Also add to userDefined fields if supported
            if (!isset($agreement_data['userDefined'])) {
                $agreement_data['userDefined'] = [];
            }
            
            // Format metadata for userDefined (key-value pairs)
            foreach ($minsponsor_metadata as $key => $value) {
                $agreement_data['userDefined'][$key] = (string) $value;
            }
            
            // Add description with sponsorship details
            $description = $this->build_sponsorship_description($minsponsor_metadata);
            if ($description) {
                $agreement_data['productDescription'] = $description;
            }
            
            // Log metadata addition
            error_log(sprintf(
                'MinSponsor Vipps: Added metadata to agreement for order %d: %s',
                $order->get_id(),
                wp_json_encode($minsponsor_metadata)
            ));
        }
        
        return $agreement_data;
    }
    
    /**
     * Add MinSponsor metadata to Vipps Recurring charge
     *
     * @param array $charge_data Charge data
     * @param \WC_Subscription $subscription Subscription object
     * @param \WC_Order $order Renewal order
     * @return array Modified charge data
     */
    public function add_charge_metadata(array $charge_data, \WC_Subscription $subscription, \WC_Order $order): array {
        // For charges, get metadata from subscription
        $minsponsor_metadata = $this->get_subscription_metadata($subscription);
        
        if (!empty($minsponsor_metadata)) {
            // Add metadata to charge
            if (!isset($charge_data['metadata'])) {
                $charge_data['metadata'] = [];
            }
            
            $charge_data['metadata'] = array_merge($charge_data['metadata'], $minsponsor_metadata);
            
            // Add to userDefined fields
            if (!isset($charge_data['userDefined'])) {
                $charge_data['userDefined'] = [];
            }
            
            foreach ($minsponsor_metadata as $key => $value) {
                $charge_data['userDefined'][$key] = (string) $value;
            }
            
            // Add description
            $description = $this->build_sponsorship_description($minsponsor_metadata);
            if ($description) {
                $charge_data['description'] = $description;
            }
            
            // Log metadata addition
            error_log(sprintf(
                'MinSponsor Vipps: Added metadata to charge for subscription %d: %s',
                $subscription->get_id(),
                wp_json_encode($minsponsor_metadata)
            ));
        }
        
        return $charge_data;
    }
    
    /**
     * Update agreement metadata when payment method changes
     *
     * @param \WC_Subscription $subscription Subscription object
     * @param string $new_payment_method New payment method
     */
    public function update_agreement_metadata(\WC_Subscription $subscription, string $new_payment_method): void {
        // Only process if new payment method is Vipps/MobilePay
        if (!in_array($new_payment_method, ['vipps_recurring', 'mobilepay_recurring'], true)) {
            return;
        }
        
        $minsponsor_metadata = $this->get_subscription_metadata($subscription);
        
        if (!empty($minsponsor_metadata)) {
            // Trigger hook for plugin to update agreement
            do_action('minsponsor_update_vipps_agreement', $subscription, $minsponsor_metadata);
        }
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
     * Build a human-readable description for sponsorship
     *
     * @param array $metadata MinSponsor metadata
     * @return string Description
     */
    private function build_sponsorship_description(array $metadata): string {
        $parts = [];
        
        if (isset($metadata['player'])) {
            $parts[] = 'Støtte til ' . $metadata['player'];
        }
        
        if (isset($metadata['team'])) {
            $parts[] = $metadata['team'];
        }
        
        if (isset($metadata['club'])) {
            $parts[] = $metadata['club'];
        }
        
        if (isset($metadata['interval'])) {
            $type = $metadata['interval'] === 'once' ? 'Engangsbeløp' : 'Månedlig';
            $parts[] = $type;
        }
        
        return implode(' - ', $parts);
    }
    
    /**
     * Check if Vipps Recurring gateway is available
     *
     * @return bool
     */
    public static function is_vipps_recurring_available(): bool {
        return class_exists('WC_Gateway_Vipps_Recurring') || 
               class_exists('Vipps_Recurring_Gateway') ||
               function_exists('vipps_recurring_init');
    }
    
    /**
     * Check if MobilePay Recurring gateway is available
     *
     * @return bool
     */
    public static function is_mobilepay_recurring_available(): bool {
        return class_exists('WC_Gateway_MobilePay_Recurring') || 
               class_exists('MobilePay_Recurring_Gateway') ||
               function_exists('mobilepay_recurring_init');
    }
    
    /**
     * Get Vipps agreement ID from subscription
     *
     * @param \WC_Subscription $subscription Subscription object
     * @return string|null Agreement ID
     */
    private function get_vipps_agreement_id(\WC_Subscription $subscription): ?string {
        // Try different meta keys used by Vipps gateway
        $agreement_id = $subscription->get_meta('_vipps_agreement_id');
        
        if (!$agreement_id) {
            $agreement_id = $subscription->get_meta('vipps_recurring_agreement_id');
        }
        
        if (!$agreement_id) {
            $agreement_id = $subscription->get_meta('_vipps_recurring_id');
        }
        
        return $agreement_id ?: null;
    }
    
    /**
     * Manual metadata update via API (if needed)
     *
     * @param \WC_Subscription $subscription Subscription object
     * @param array $metadata Metadata to update
     */
    public function manual_update_agreement(\WC_Subscription $subscription, array $metadata): void {
        $agreement_id = $this->get_vipps_agreement_id($subscription);
        
        if (!$agreement_id) {
            error_log('MinSponsor Vipps: No agreement ID found for subscription ' . $subscription->get_id());
            return;
        }
        
        // This would require direct API access to Vipps
        // Implementation depends on how the Vipps plugin exposes its API
        do_action('minsponsor_manual_update_vipps_agreement', $agreement_id, $metadata, $subscription);
        
        error_log(sprintf(
            'MinSponsor Vipps: Manual update requested for agreement %s with metadata: %s',
            $agreement_id,
            wp_json_encode($metadata)
        ));
    }
}
