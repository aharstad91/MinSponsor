<?php
/**
 * Stripe Webhook Handler for MinSponsor
 * 
 * Handles incoming webhooks from Stripe for Connect account updates.
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Webhooks;

use MinSponsor\Settings\StripeSettings;
use MinSponsor\Admin\LagStripeMetaBox;

if (!defined('ABSPATH')) {
    exit;
}

class StripeWebhook {
    
    /**
     * Webhook endpoint slug
     */
    public const ENDPOINT_SLUG = 'minsponsor-stripe-webhook';
    
    /**
     * Initialize webhook handler
     */
    public function init(): void {
        // Register REST API endpoint
        add_action('rest_api_init', [$this, 'register_webhook_endpoint']);
        
        // Also register as rewrite rule for non-REST access
        add_action('init', [$this, 'register_rewrite_rules']);
        add_action('template_redirect', [$this, 'handle_webhook_request']);
    }
    
    /**
     * Register REST API webhook endpoint
     */
    public function register_webhook_endpoint(): void {
        register_rest_route('minsponsor/v1', '/stripe-webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => '__return_true', // Webhook signature validates access
        ]);
    }
    
    /**
     * Register rewrite rules for webhook endpoint
     */
    public function register_rewrite_rules(): void {
        add_rewrite_rule(
            '^' . self::ENDPOINT_SLUG . '/?$',
            'index.php?' . self::ENDPOINT_SLUG . '=1',
            'top'
        );
        add_rewrite_tag('%' . self::ENDPOINT_SLUG . '%', '1');
    }
    
    /**
     * Handle webhook request via template redirect
     */
    public function handle_webhook_request(): void {
        if (!get_query_var(self::ENDPOINT_SLUG)) {
            return;
        }
        
        $this->handle_webhook(new \WP_REST_Request('POST'));
        exit;
    }
    
    /**
     * Handle incoming Stripe webhook
     *
     * @param \WP_REST_Request $request REST request object
     * @return \WP_REST_Response|\WP_Error Response
     */
    public function handle_webhook(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
        // Get raw payload
        $payload = file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        
        // Verify webhook signature
        $event = $this->verify_webhook_signature($payload, $sig_header);
        
        if (is_wp_error($event)) {
            error_log('MinSponsor Webhook: Signature verification failed - ' . $event->get_error_message());
            return new \WP_REST_Response(['error' => $event->get_error_message()], 400);
        }
        
        error_log('MinSponsor Webhook: Received event ' . $event->type . ' (ID: ' . $event->id . ')');
        
        // Handle specific event types
        $result = $this->process_event($event);
        
        if (is_wp_error($result)) {
            error_log('MinSponsor Webhook: Error processing event - ' . $result->get_error_message());
            return new \WP_REST_Response(['error' => $result->get_error_message()], 500);
        }
        
        return new \WP_REST_Response(['received' => true], 200);
    }
    
    /**
     * Verify Stripe webhook signature
     *
     * @param string $payload Raw webhook payload
     * @param string $sig_header Stripe signature header
     * @return \Stripe\Event|\WP_Error Verified event or error
     */
    private function verify_webhook_signature(string $payload, string $sig_header): \Stripe\Event|\WP_Error {
        $webhook_secret = StripeSettings::get_webhook_secret();

        // SECURITY: Always require webhook secret - never accept unsigned webhooks
        if (empty($webhook_secret)) {
            error_log('MinSponsor Webhook: REJECTED - No webhook secret configured. Configure webhook secret in MinSponsor settings.');
            return new \WP_Error('no_webhook_secret', 'Webhook secret not configured. Unsigned webhooks are not accepted.');
        }

        if (empty($sig_header)) {
            error_log('MinSponsor Webhook: REJECTED - Missing Stripe signature header');
            return new \WP_Error('missing_signature', 'Missing Stripe-Signature header');
        }

        // Verify signature
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $webhook_secret
            );
            return $event;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            error_log('MinSponsor Webhook: REJECTED - Signature verification failed: ' . $e->getMessage());
            return new \WP_Error('signature_verification_failed', $e->getMessage());
        } catch (\Exception $e) {
            return new \WP_Error('webhook_error', $e->getMessage());
        }
    }
    
    /**
     * Process webhook event
     *
     * @param \Stripe\Event $event Stripe event
     * @return bool|\WP_Error Success or error
     */
    private function process_event(\Stripe\Event $event): bool|\WP_Error {
        switch ($event->type) {
            case 'account.updated':
                return $this->handle_account_updated($event);
                
            case 'account.application.authorized':
                return $this->handle_account_authorized($event);
                
            case 'account.application.deauthorized':
                return $this->handle_account_deauthorized($event);
                
            case 'capability.updated':
                return $this->handle_capability_updated($event);
                
            case 'payment_intent.succeeded':
                return $this->handle_payment_succeeded($event);
                
            case 'transfer.created':
                return $this->handle_transfer_created($event);
                
            default:
                error_log('MinSponsor Webhook: Unhandled event type ' . $event->type);
                return true; // Return success for unhandled events
        }
    }
    
    /**
     * Handle account.updated event
     * 
     * This is fired when a Connected account's status changes.
     *
     * @param \Stripe\Event $event Stripe event
     * @return bool|\WP_Error Success or error
     */
    private function handle_account_updated(\Stripe\Event $event): bool|\WP_Error {
        $account = $event->data->object;
        $account_id = $account->id;
        
        error_log('MinSponsor Webhook: Processing account.updated for ' . $account_id);
        
        // Find the lag with this Stripe account
        $lag_id = $this->find_lag_by_stripe_account($account_id);
        
        if (!$lag_id) {
            error_log('MinSponsor Webhook: No lag found for account ' . $account_id);
            return true; // Not an error, just not our account
        }
        
        // Determine new status based on account capabilities
        $charges_enabled = $account->charges_enabled ?? false;
        $payouts_enabled = $account->payouts_enabled ?? false;
        $details_submitted = $account->details_submitted ?? false;
        
        if ($charges_enabled && $payouts_enabled) {
            $new_status = LagStripeMetaBox::STATUS_COMPLETE;
            error_log('MinSponsor Webhook: Account ' . $account_id . ' is now COMPLETE');
        } elseif ($details_submitted) {
            $new_status = LagStripeMetaBox::STATUS_PENDING;
            error_log('MinSponsor Webhook: Account ' . $account_id . ' is PENDING (details submitted)');
        } else {
            $new_status = LagStripeMetaBox::STATUS_PENDING;
            error_log('MinSponsor Webhook: Account ' . $account_id . ' is PENDING');
        }
        
        // Update lag meta
        update_post_meta($lag_id, LagStripeMetaBox::META_ONBOARDING_STATUS, $new_status);
        update_post_meta($lag_id, LagStripeMetaBox::META_LAST_CHECKED, current_time('mysql'));
        
        // Store additional account info
        update_post_meta($lag_id, '_minsponsor_stripe_charges_enabled', $charges_enabled ? '1' : '0');
        update_post_meta($lag_id, '_minsponsor_stripe_payouts_enabled', $payouts_enabled ? '1' : '0');
        
        // Fire action for other plugins/code to hook into
        do_action('minsponsor_stripe_account_updated', $lag_id, $account_id, $new_status, $account);
        
        error_log('MinSponsor Webhook: Updated lag ' . $lag_id . ' status to ' . $new_status);
        
        return true;
    }
    
    /**
     * Handle account.application.authorized event
     *
     * @param \Stripe\Event $event Stripe event
     * @return bool|\WP_Error Success or error
     */
    private function handle_account_authorized(\Stripe\Event $event): bool|\WP_Error {
        $account = $event->data->object;
        error_log('MinSponsor Webhook: Account authorized - ' . $account->id);
        return true;
    }
    
    /**
     * Handle account.application.deauthorized event
     *
     * @param \Stripe\Event $event Stripe event
     * @return bool|\WP_Error Success or error
     */
    private function handle_account_deauthorized(\Stripe\Event $event): bool|\WP_Error {
        $account = $event->data->object;
        $account_id = $account->id;
        
        error_log('MinSponsor Webhook: Account deauthorized - ' . $account_id);
        
        // Find and update the lag
        $lag_id = $this->find_lag_by_stripe_account($account_id);
        
        if ($lag_id) {
            update_post_meta($lag_id, LagStripeMetaBox::META_ONBOARDING_STATUS, LagStripeMetaBox::STATUS_NOT_STARTED);
            delete_post_meta($lag_id, LagStripeMetaBox::META_ACCOUNT_ID);
            delete_post_meta($lag_id, LagStripeMetaBox::META_ONBOARDING_LINK);
            
            do_action('minsponsor_stripe_account_deauthorized', $lag_id, $account_id);
            
            error_log('MinSponsor Webhook: Cleared Stripe data for lag ' . $lag_id);
        }
        
        return true;
    }
    
    /**
     * Handle capability.updated event
     *
     * @param \Stripe\Event $event Stripe event
     * @return bool|\WP_Error Success or error
     */
    private function handle_capability_updated(\Stripe\Event $event): bool|\WP_Error {
        $capability = $event->data->object;
        error_log('MinSponsor Webhook: Capability updated - ' . $capability->id . ' status: ' . $capability->status);
        
        // If this is a card_payments capability becoming active, trigger account check
        if ($capability->id === 'card_payments' && $capability->status === 'active') {
            $account_id = $capability->account;
            $lag_id = $this->find_lag_by_stripe_account($account_id);
            
            if ($lag_id) {
                // Trigger a full account status refresh
                do_action('minsponsor_refresh_stripe_status', $lag_id);
            }
        }
        
        return true;
    }
    
    /**
     * Handle payment_intent.succeeded event
     *
     * @param \Stripe\Event $event Stripe event
     * @return bool|\WP_Error Success or error
     */
    private function handle_payment_succeeded(\Stripe\Event $event): bool|\WP_Error {
        $payment_intent = $event->data->object;
        
        $metadata = $payment_intent->metadata ?? [];
        $order_id = $metadata['order_id'] ?? null;
        
        if ($order_id) {
            error_log('MinSponsor Webhook: Payment succeeded for order ' . $order_id);
            do_action('minsponsor_payment_succeeded', $order_id, $payment_intent);
        }
        
        return true;
    }
    
    /**
     * Handle transfer.created event
     *
     * @param \Stripe\Event $event Stripe event
     * @return bool|\WP_Error Success or error
     */
    private function handle_transfer_created(\Stripe\Event $event): bool|\WP_Error {
        $transfer = $event->data->object;
        
        error_log('MinSponsor Webhook: Transfer created - ' . $transfer->id . 
                  ' Amount: ' . $transfer->amount . ' ' . $transfer->currency .
                  ' Destination: ' . $transfer->destination);
        
        do_action('minsponsor_transfer_created', $transfer);
        
        return true;
    }
    
    /**
     * Find lag post ID by Stripe account ID
     *
     * @param string $account_id Stripe account ID
     * @return int|null Lag post ID or null
     */
    private function find_lag_by_stripe_account(string $account_id): ?int {
        $query = new \WP_Query([
            'post_type' => 'lag',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => LagStripeMetaBox::META_ACCOUNT_ID,
                    'value' => $account_id,
                ]
            ],
            'fields' => 'ids',
        ]);
        
        if ($query->have_posts()) {
            return $query->posts[0];
        }
        
        return null;
    }
    
    /**
     * Get webhook URL
     *
     * @return string Webhook URL
     */
    public static function get_webhook_url(): string {
        return rest_url('minsponsor/v1/stripe-webhook');
    }
}
