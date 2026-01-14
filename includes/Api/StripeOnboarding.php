<?php
/**
 * Stripe Connect Onboarding API
 * 
 * Handles Express account creation and onboarding flow for Lag (teams).
 * 
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Api;

use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripeOnboarding {
    
    /**
     * Stripe client instance
     */
    private ?StripeClient $stripe = null;
    
    /**
     * Initialize the onboarding API
     */
    public function __construct() {
        // Register AJAX endpoints
        add_action('wp_ajax_minsponsor_start_onboarding', [$this, 'ajax_start_onboarding']);
        add_action('wp_ajax_minsponsor_refresh_status', [$this, 'ajax_refresh_status']);
        add_action('wp_ajax_minsponsor_get_dashboard_link', [$this, 'ajax_get_dashboard_link']);
        
        // Register REST API endpoints for callbacks
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    /**
     * Get configured Stripe client
     */
    private function get_stripe(): ?StripeClient {
        if ($this->stripe !== null) {
            return $this->stripe;
        }
        
        $environment = get_option('minsponsor_stripe_environment', 'test');
        
        if ($environment === 'live') {
            $secret_key = get_option('minsponsor_stripe_live_secret_key', '');
        } else {
            $secret_key = get_option('minsponsor_stripe_test_secret_key', '');
        }
        
        if (empty($secret_key)) {
            return null;
        }
        
        $this->stripe = new StripeClient($secret_key);
        return $this->stripe;
    }
    
    /**
     * Register REST API routes for onboarding callbacks
     */
    public function register_rest_routes(): void {
        register_rest_route('minsponsor/v1', '/stripe/return/(?P<lag_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_onboarding_return'],
            'permission_callback' => '__return_true', // Public endpoint
            'args' => [
                'lag_id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);
        
        register_rest_route('minsponsor/v1', '/stripe/refresh/(?P<lag_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_onboarding_refresh'],
            'permission_callback' => '__return_true', // Public endpoint
            'args' => [
                'lag_id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);
    }
    
    /**
     * AJAX: Start onboarding process for a Lag
     */
    public function ajax_start_onboarding(): void {
        // Verify nonce
        if (!check_ajax_referer('minsponsor_stripe_admin', 'nonce', false)) {
            wp_send_json_error(['message' => 'Ugyldig sikkerhetstoken']);
        }

        $lag_id = absint($_POST['lag_id'] ?? 0);
        if (!$lag_id) {
            wp_send_json_error(['message' => 'Mangler lag-ID']);
        }

        // Check permissions - require ability to edit this specific lag post
        if (!current_user_can('edit_post', $lag_id)) {
            wp_send_json_error(['message' => 'Ingen tilgang']);
        }
        
        // Verify it's a lag post type
        if (get_post_type($lag_id) !== 'lag') {
            wp_send_json_error(['message' => 'Ugyldig innleggstype']);
        }
        
        // Get kasserer email
        $kasserer_email = get_field('kasserer_email', $lag_id);
        if (empty($kasserer_email)) {
            wp_send_json_error(['message' => 'Kasserer e-post mangler. Fyll ut kasserer-feltene først.']);
        }
        
        // Get Stripe client
        $stripe = $this->get_stripe();
        if (!$stripe) {
            wp_send_json_error(['message' => 'Stripe API-nøkler er ikke konfigurert. Gå til MinSponsor → Innstillinger → Stripe.']);
        }
        
        try {
            // Check if account already exists
            $existing_account_id = get_post_meta($lag_id, '_minsponsor_stripe_account_id', true);
            
            if (!empty($existing_account_id)) {
                // Account exists, just create a new onboarding link
                $onboarding_url = $this->create_account_link($stripe, $existing_account_id, $lag_id);
            } else {
                // Create new Express account
                $lag_name = get_the_title($lag_id);
                $kasserer_navn = get_field('kasserer_navn', $lag_id) ?: '';

                // Generate idempotency key to prevent duplicate account creation
                $idempotency_key = 'create_account_lag_' . $lag_id . '_' . md5($kasserer_email . $lag_name);

                $account = $stripe->accounts->create([
                    'type' => 'express',
                    'country' => 'NO',
                    'email' => $kasserer_email,
                    'capabilities' => [
                        'card_payments' => ['requested' => true],
                        'transfers' => ['requested' => true],
                    ],
                    'business_type' => 'non_profit', // Most sports clubs are non-profit
                    'business_profile' => [
                        'name' => $lag_name,
                        'mcc' => '8699', // Membership organizations
                        'url' => get_permalink($lag_id),
                    ],
                    'metadata' => [
                        'lag_id' => $lag_id,
                        'lag_name' => $lag_name,
                        'kasserer_email' => $kasserer_email,
                        'kasserer_navn' => $kasserer_navn,
                        'source' => 'minsponsor',
                    ],
                ], ['idempotency_key' => $idempotency_key]);
                
                // Save account ID
                update_post_meta($lag_id, '_minsponsor_stripe_account_id', $account->id);
                update_post_meta($lag_id, '_minsponsor_stripe_onboarding_status', 'pending');
                update_post_meta($lag_id, '_minsponsor_stripe_last_checked', current_time('mysql'));
                
                // Create onboarding link
                $onboarding_url = $this->create_account_link($stripe, $account->id, $lag_id);
            }
            
            // Save onboarding URL
            update_post_meta($lag_id, '_minsponsor_stripe_onboarding_url', $onboarding_url);
            
            wp_send_json_success([
                'message' => 'Onboarding startet',
                'onboarding_url' => $onboarding_url,
                'account_id' => get_post_meta($lag_id, '_minsponsor_stripe_account_id', true),
            ]);
            
        } catch (ApiErrorException $e) {
            wp_send_json_error([
                'message' => 'Stripe-feil: ' . $e->getMessage(),
                'code' => $e->getStripeCode(),
            ]);
        }
    }
    
    /**
     * Create Account Link for onboarding
     */
    private function create_account_link(StripeClient $stripe, string $account_id, int $lag_id): string {
        // Use time-based idempotency key (links expire, so new ones may be needed)
        // Include minute granularity to allow new link creation after expiry
        $idempotency_key = 'account_link_' . $account_id . '_' . gmdate('YmdHi');

        $link = $stripe->accountLinks->create([
            'account' => $account_id,
            'refresh_url' => home_url('/wp-json/minsponsor/v1/stripe/refresh/' . $lag_id),
            'return_url' => home_url('/wp-json/minsponsor/v1/stripe/return/' . $lag_id),
            'type' => 'account_onboarding',
        ], ['idempotency_key' => $idempotency_key]);

        return $link->url;
    }
    
    /**
     * AJAX: Refresh Stripe account status
     */
    public function ajax_refresh_status(): void {
        // Verify nonce
        if (!check_ajax_referer('minsponsor_stripe_admin', 'nonce', false)) {
            wp_send_json_error(['message' => 'Ugyldig sikkerhetstoken']);
        }

        $lag_id = absint($_POST['lag_id'] ?? 0);
        if (!$lag_id) {
            wp_send_json_error(['message' => 'Mangler lag-ID']);
        }

        // Check permissions - require ability to edit this specific lag post
        if (!current_user_can('edit_post', $lag_id)) {
            wp_send_json_error(['message' => 'Ingen tilgang']);
        }
        
        $account_id = get_post_meta($lag_id, '_minsponsor_stripe_account_id', true);
        if (empty($account_id)) {
            wp_send_json_error(['message' => 'Ingen Stripe-konto koblet til dette laget']);
        }
        
        $stripe = $this->get_stripe();
        if (!$stripe) {
            wp_send_json_error(['message' => 'Stripe API-nøkler er ikke konfigurert']);
        }
        
        try {
            $account = $stripe->accounts->retrieve($account_id);
            
            // Determine status
            $status = 'pending';
            if ($account->charges_enabled && $account->payouts_enabled) {
                $status = 'complete';
            } elseif ($account->details_submitted) {
                $status = 'pending'; // Submitted but not yet approved
            }
            
            // Update meta
            update_post_meta($lag_id, '_minsponsor_stripe_onboarding_status', $status);
            update_post_meta($lag_id, '_minsponsor_stripe_charges_enabled', $account->charges_enabled ? '1' : '0');
            update_post_meta($lag_id, '_minsponsor_stripe_payouts_enabled', $account->payouts_enabled ? '1' : '0');
            update_post_meta($lag_id, '_minsponsor_stripe_details_submitted', $account->details_submitted ? '1' : '0');
            update_post_meta($lag_id, '_minsponsor_stripe_last_checked', current_time('mysql'));
            
            wp_send_json_success([
                'status' => $status,
                'charges_enabled' => $account->charges_enabled,
                'payouts_enabled' => $account->payouts_enabled,
                'details_submitted' => $account->details_submitted,
                'message' => $this->get_status_message($status),
            ]);
            
        } catch (ApiErrorException $e) {
            wp_send_json_error([
                'message' => 'Stripe-feil: ' . $e->getMessage(),
            ]);
        }
    }
    
    /**
     * AJAX: Get Stripe Express dashboard link
     */
    public function ajax_get_dashboard_link(): void {
        // Verify nonce
        if (!check_ajax_referer('minsponsor_stripe_admin', 'nonce', false)) {
            wp_send_json_error(['message' => 'Ugyldig sikkerhetstoken']);
        }

        $lag_id = absint($_POST['lag_id'] ?? 0);
        if (!$lag_id) {
            wp_send_json_error(['message' => 'Mangler lag-ID']);
        }

        // Check permissions - require ability to edit this specific lag post
        if (!current_user_can('edit_post', $lag_id)) {
            wp_send_json_error(['message' => 'Ingen tilgang']);
        }

        $account_id = get_post_meta($lag_id, '_minsponsor_stripe_account_id', true);
        if (empty($account_id)) {
            wp_send_json_error(['message' => 'Ingen Stripe-konto koblet til dette laget']);
        }
        
        $stripe = $this->get_stripe();
        if (!$stripe) {
            wp_send_json_error(['message' => 'Stripe API-nøkler er ikke konfigurert']);
        }
        
        try {
            // Login links can be created multiple times, use time-based idempotency
            $idempotency_key = 'login_link_' . $account_id . '_' . gmdate('YmdHi');
            $link = $stripe->accounts->createLoginLink($account_id, [], ['idempotency_key' => $idempotency_key]);

            wp_send_json_success([
                'dashboard_url' => $link->url,
            ]);
            
        } catch (ApiErrorException $e) {
            // If login link fails, the account may not be fully set up
            // Try to create an account link instead
            try {
                $onboarding_url = $this->create_account_link($stripe, $account_id, $lag_id);
                update_post_meta($lag_id, '_minsponsor_stripe_onboarding_url', $onboarding_url);
                
                wp_send_json_success([
                    'onboarding_url' => $onboarding_url,
                    'message' => 'Onboarding er ikke fullført. Bruk denne lenken for å fortsette.',
                ]);
            } catch (ApiErrorException $e2) {
                wp_send_json_error([
                    'message' => 'Kunne ikke opprette lenke: ' . $e2->getMessage(),
                ]);
            }
        }
    }
    
    /**
     * Handle onboarding return callback
     */
    public function handle_onboarding_return(\WP_REST_Request $request): \WP_REST_Response {
        $lag_id = $request->get_param('lag_id');
        
        // Refresh status from Stripe
        $this->refresh_account_status($lag_id);
        
        // Redirect to admin edit page with success message
        $redirect_url = add_query_arg([
            'post' => $lag_id,
            'action' => 'edit',
            'minsponsor_stripe_message' => 'onboarding_return',
        ], admin_url('post.php'));
        
        return new \WP_REST_Response(null, 302, ['Location' => $redirect_url]);
    }
    
    /**
     * Handle onboarding refresh callback (when link expires)
     */
    public function handle_onboarding_refresh(\WP_REST_Request $request): \WP_REST_Response {
        $lag_id = $request->get_param('lag_id');
        
        $stripe = $this->get_stripe();
        $account_id = get_post_meta($lag_id, '_minsponsor_stripe_account_id', true);
        
        if ($stripe && $account_id) {
            try {
                // Create new onboarding link
                $onboarding_url = $this->create_account_link($stripe, $account_id, $lag_id);
                update_post_meta($lag_id, '_minsponsor_stripe_onboarding_url', $onboarding_url);
                
                // Redirect to new onboarding
                return new \WP_REST_Response(null, 302, ['Location' => $onboarding_url]);
            } catch (ApiErrorException $e) {
                // Redirect to admin with error
                $redirect_url = add_query_arg([
                    'post' => $lag_id,
                    'action' => 'edit',
                    'minsponsor_stripe_message' => 'refresh_error',
                ], admin_url('post.php'));
                
                return new \WP_REST_Response(null, 302, ['Location' => $redirect_url]);
            }
        }
        
        // Redirect to admin
        $redirect_url = add_query_arg([
            'post' => $lag_id,
            'action' => 'edit',
        ], admin_url('post.php'));
        
        return new \WP_REST_Response(null, 302, ['Location' => $redirect_url]);
    }
    
    /**
     * Refresh account status from Stripe
     */
    private function refresh_account_status(int $lag_id): bool {
        $stripe = $this->get_stripe();
        $account_id = get_post_meta($lag_id, '_minsponsor_stripe_account_id', true);
        
        if (!$stripe || empty($account_id)) {
            return false;
        }
        
        try {
            $account = $stripe->accounts->retrieve($account_id);
            
            // Determine status
            $status = 'pending';
            if ($account->charges_enabled && $account->payouts_enabled) {
                $status = 'complete';
            }
            
            // Update meta
            update_post_meta($lag_id, '_minsponsor_stripe_onboarding_status', $status);
            update_post_meta($lag_id, '_minsponsor_stripe_charges_enabled', $account->charges_enabled ? '1' : '0');
            update_post_meta($lag_id, '_minsponsor_stripe_payouts_enabled', $account->payouts_enabled ? '1' : '0');
            update_post_meta($lag_id, '_minsponsor_stripe_details_submitted', $account->details_submitted ? '1' : '0');
            update_post_meta($lag_id, '_minsponsor_stripe_last_checked', current_time('mysql'));
            
            return true;
        } catch (ApiErrorException $e) {
            return false;
        }
    }
    
    /**
     * Get human-readable status message
     */
    private function get_status_message(string $status): string {
        return match ($status) {
            'complete' => 'Stripe-konto er aktiv og klar til å motta betalinger',
            'pending' => 'Onboarding pågår eller venter på godkjenning',
            default => 'Onboarding er ikke startet',
        };
    }
    
    /**
     * Get Stripe account for a Lag (static helper for other classes)
     */
    public static function get_lag_stripe_account(int $lag_id): ?string {
        $account_id = get_post_meta($lag_id, '_minsponsor_stripe_account_id', true);
        return !empty($account_id) ? $account_id : null;
    }
    
    /**
     * Check if a Lag is ready to receive payments
     */
    public static function is_lag_ready_for_payments(int $lag_id): bool {
        $status = get_post_meta($lag_id, '_minsponsor_stripe_onboarding_status', true);
        return $status === 'complete';
    }
}
