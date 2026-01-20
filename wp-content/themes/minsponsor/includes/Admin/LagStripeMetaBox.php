<?php
/**
 * Lag Stripe Connect Meta Box for MinSponsor
 * 
 * Admin meta box displaying Stripe Connect status and actions for Lag CPT.
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class LagStripeMetaBox {
    
    /**
     * Stripe onboarding statuses
     */
    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETE = 'complete';
    
    /**
     * Meta keys for Stripe data
     */
    public const META_ACCOUNT_ID = '_minsponsor_stripe_account_id';
    public const META_ONBOARDING_STATUS = '_minsponsor_stripe_onboarding_status';
    public const META_ONBOARDING_LINK = '_minsponsor_stripe_onboarding_link';
    public const META_LAST_CHECKED = '_minsponsor_stripe_last_checked';
    
    /**
     * Initialize hooks
     */
    public function init(): void {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_minsponsor_refresh_stripe_status', [$this, 'ajax_refresh_status']);
        add_action('wp_ajax_minsponsor_start_onboarding', [$this, 'ajax_start_onboarding']);
        add_action('wp_ajax_minsponsor_copy_onboarding_link', [$this, 'ajax_get_onboarding_link']);
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes(): void {
        add_meta_box(
            'minsponsor-lag-stripe',
            'Stripe Connect',
            [$this, 'render_meta_box'],
            'lag',
            'side',
            'high'
        );
    }
    
    /**
     * Render the meta box
     */
    public function render_meta_box(\WP_Post $post): void {
        if (!current_user_can('edit_posts')) {
            echo '<p>You do not have permission to access this feature.</p>';
            return;
        }
        
        $account_id = get_post_meta($post->ID, self::META_ACCOUNT_ID, true);
        $status = get_post_meta($post->ID, self::META_ONBOARDING_STATUS, true) ?: self::STATUS_NOT_STARTED;
        $onboarding_link = get_post_meta($post->ID, self::META_ONBOARDING_LINK, true);
        $last_checked = get_post_meta($post->ID, self::META_LAST_CHECKED, true);
        $kasserer_email = get_field('kasserer_email', $post->ID);
        
        wp_nonce_field('minsponsor_lag_stripe', 'minsponsor_lag_stripe_nonce');
        
        ?>
        <div class="minsponsor-stripe-meta" data-lag-id="<?php echo esc_attr($post->ID); ?>">
            
            <!-- Status Display -->
            <div class="minsponsor-stripe-status">
                <strong>Status:</strong>
                <?php echo $this->render_status_badge($status); ?>
            </div>
            
            <?php if ($account_id): ?>
            <div class="minsponsor-stripe-account">
                <strong>Account ID:</strong>
                <code style="font-size: 11px; word-break: break-all;"><?php echo esc_html($account_id); ?></code>
            </div>
            <?php endif; ?>
            
            <?php if ($last_checked): ?>
            <div class="minsponsor-stripe-last-checked" style="color: #666; font-size: 11px; margin-top: 8px;">
                Last checked: <?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($last_checked))); ?>
            </div>
            <?php endif; ?>
            
            <hr style="margin: 12px 0;">
            
            <!-- Actions -->
            <div class="minsponsor-stripe-actions" style="display: flex; flex-direction: column; gap: 8px;">
                
                <?php if ($status === self::STATUS_NOT_STARTED): ?>
                    <?php if ($kasserer_email): ?>
                        <button type="button" 
                                class="button button-primary minsponsor-start-onboarding"
                                style="background: #D97757; border-color: #D97757;">
                            Start onboarding
                        </button>
                        <p class="description" style="margin: 0;">
                            Creates Stripe Express account and sends link to treasurer.
                        </p>
                    <?php else: ?>
                        <p class="description" style="color: #d63638; margin: 0;">
                            ⚠️ Enter treasurer email above to start Stripe connection.
                        </p>
                    <?php endif; ?>
                    
                <?php elseif ($status === self::STATUS_PENDING): ?>
                    <?php if ($onboarding_link): ?>
                        <button type="button" 
                                class="button minsponsor-copy-link"
                                data-link="<?php echo esc_attr($onboarding_link); ?>">
                            📋 Copy onboarding link
                        </button>
                    <?php endif; ?>
                    <button type="button" class="button minsponsor-refresh-status">
                        🔄 Check status
                    </button>
                    <p class="description" style="margin: 0;">
                        Waiting for treasurer to complete Stripe registration.
                    </p>
                    
                <?php elseif ($status === self::STATUS_COMPLETE): ?>
                    <div style="background: #d4edda; padding: 8px; border-radius: 4px; text-align: center;">
                        ✅ Ready to receive payments!
                    </div>
                    <button type="button" class="button minsponsor-refresh-status">
                        🔄 Refresh status
                    </button>
                <?php endif; ?>
                
            </div>
            
            <!-- Loading indicator -->
            <div class="minsponsor-stripe-loading" style="display: none; text-align: center; padding: 10px;">
                <span class="spinner is-active" style="float: none;"></span>
            </div>
            
            <!-- Messages -->
            <div class="minsponsor-stripe-message" style="display: none; margin-top: 8px; padding: 8px; border-radius: 4px;"></div>
            
        </div>
        
        <style>
            .minsponsor-stripe-meta { font-size: 13px; }
            .minsponsor-stripe-status { margin-bottom: 8px; }
            .minsponsor-stripe-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }
            .minsponsor-stripe-badge.not-started { background: #f0f0f1; color: #50575e; }
            .minsponsor-stripe-badge.pending { background: #fff3cd; color: #856404; }
            .minsponsor-stripe-badge.complete { background: #d4edda; color: #155724; }
        </style>
        <?php
    }
    
    /**
     * Render status badge HTML
     */
    private function render_status_badge(string $status): string {
        $labels = [
            self::STATUS_NOT_STARTED => 'Not connected',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETE => 'Active',
        ];
        
        $label = $labels[$status] ?? 'Unknown';
        $class = str_replace('_', '-', $status);
        
        return sprintf(
            '<span class="minsponsor-stripe-badge %s">%s</span>',
            esc_attr($class),
            esc_html($label)
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts(string $hook): void {
        global $post_type;
        
        if ($post_type !== 'lag' || !in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }
        
        wp_enqueue_script(
            'minsponsor-lag-stripe',
            get_template_directory_uri() . '/assets/js/admin/lag-stripe.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('minsponsor-lag-stripe', 'minsponsorLagStripe', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('minsponsor_lag_stripe'),
        ]);
    }
    
    /**
     * AJAX: Refresh Stripe account status
     * 
     * Fetches the current account status from Stripe API and updates local meta.
     */
    public function ajax_refresh_status(): void {
        check_ajax_referer('minsponsor_lag_stripe', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        $lag_id = intval($_POST['lag_id'] ?? 0);
        if (!$lag_id) {
            wp_send_json_error(['message' => 'Invalid lag ID']);
        }
        
        $account_id = get_post_meta($lag_id, self::META_ACCOUNT_ID, true);
        if (!$account_id) {
            wp_send_json_error(['message' => 'No Stripe account connected']);
        }
        
        // Get Stripe client
        $stripe = $this->get_stripe_client();
        if (!$stripe) {
            wp_send_json_error(['message' => 'Stripe API keys are not configured']);
        }
        
        try {
            // Fetch account from Stripe
            $account = $stripe->accounts->retrieve($account_id);
            
            // Determine status based on Stripe account state
            // An account is "complete" when it has charges_enabled and payouts_enabled
            $is_complete = $account->charges_enabled && $account->payouts_enabled;
            $new_status = $is_complete ? self::STATUS_COMPLETE : self::STATUS_PENDING;
            
            // Update local meta
            update_post_meta($lag_id, self::META_ONBOARDING_STATUS, $new_status);
            update_post_meta($lag_id, self::META_LAST_CHECKED, current_time('mysql'));
            
            // Clear onboarding link if complete (no longer needed)
            if ($is_complete) {
                delete_post_meta($lag_id, self::META_ONBOARDING_LINK);
            }
            
            wp_send_json_success([
                'message' => $is_complete ? 'Account is active and ready!' : 'Onboarding not yet complete',
                'status' => $new_status,
                'last_checked' => current_time('d.m.Y H:i'),
                'charges_enabled' => $account->charges_enabled,
                'payouts_enabled' => $account->payouts_enabled,
            ]);
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            wp_send_json_error([
                'message' => 'Stripe error: ' . $e->getMessage(),
                'code' => $e->getStripeCode(),
            ]);
        }
    }
    
    /**
     * AJAX: Start Stripe onboarding
     * 
     * Creates Express account via Stripe API and returns onboarding URL.
     */
    public function ajax_start_onboarding(): void {
        check_ajax_referer('minsponsor_lag_stripe', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        $lag_id = intval($_POST['lag_id'] ?? 0);
        if (!$lag_id) {
            wp_send_json_error(['message' => 'Invalid lag ID']);
        }
        
        // Verify it's a lag post type
        if (get_post_type($lag_id) !== 'lag') {
            wp_send_json_error(['message' => 'Invalid post type']);
        }
        
        $kasserer_email = get_field('kasserer_email', $lag_id);
        if (!$kasserer_email) {
            wp_send_json_error(['message' => 'Treasurer email is missing. Fill in the treasurer fields first.']);
        }
        
        // Get Stripe client
        $stripe = $this->get_stripe_client();
        if (!$stripe) {
            wp_send_json_error(['message' => 'Stripe API keys are not configured. Go to MinSponsor → Settings → Stripe.']);
        }
        
        try {
            // Check if account already exists
            $existing_account_id = get_post_meta($lag_id, self::META_ACCOUNT_ID, true);
            
            if (!empty($existing_account_id)) {
                // Account exists, just create a new onboarding link
                $onboarding_url = $this->create_account_link($stripe, $existing_account_id, $lag_id);
                $account_id = $existing_account_id;
            } else {
                // Create new Express account
                $lag_name = get_the_title($lag_id);
                $kasserer_navn = get_field('kasserer_navn', $lag_id) ?: '';
                
                // Get business URL - handle localhost for development
                $business_url = get_permalink($lag_id);
                if (strpos($business_url, 'localhost') !== false || strpos($business_url, '127.0.0.1') !== false) {
                    // For local development, omit URL or use a placeholder
                    // Stripe validates URLs and rejects localhost
                    $business_url = null;
                }
                
                $account_params = [
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
                    ],
                    'metadata' => [
                        'lag_id' => $lag_id,
                        'lag_name' => $lag_name,
                        'kasserer_email' => $kasserer_email,
                        'kasserer_navn' => $kasserer_navn,
                        'source' => 'minsponsor',
                    ],
                ];
                
                // Only add URL if valid
                if ($business_url) {
                    $account_params['business_profile']['url'] = $business_url;
                }
                
                $account = $stripe->accounts->create($account_params);
                
                $account_id = $account->id;
                
                // Save account ID
                update_post_meta($lag_id, self::META_ACCOUNT_ID, $account_id);
                
                // Create onboarding link
                $onboarding_url = $this->create_account_link($stripe, $account_id, $lag_id);
            }
            
            // Update status
            update_post_meta($lag_id, self::META_ONBOARDING_STATUS, self::STATUS_PENDING);
            update_post_meta($lag_id, self::META_LAST_CHECKED, current_time('mysql'));
            update_post_meta($lag_id, self::META_ONBOARDING_LINK, $onboarding_url);
            
            wp_send_json_success([
                'message' => 'Onboarding started',
                'status' => self::STATUS_PENDING,
                'onboarding_url' => $onboarding_url,
                'account_id' => $account_id,
            ]);
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            wp_send_json_error([
                'message' => 'Stripe error: ' . $e->getMessage(),
                'code' => $e->getStripeCode(),
            ]);
        }
    }
    
    /**
     * Get configured Stripe client
     */
    private function get_stripe_client(): ?\Stripe\StripeClient {
        $environment = get_option('minsponsor_stripe_environment', 'test');
        
        if ($environment === 'live') {
            $secret_key = get_option('minsponsor_stripe_live_secret_key', '');
        } else {
            $secret_key = get_option('minsponsor_stripe_test_secret_key', '');
        }
        
        if (empty($secret_key)) {
            return null;
        }
        
        return new \Stripe\StripeClient($secret_key);
    }
    
    /**
     * Create Account Link for onboarding
     * 
     * For local development, Stripe requires an https:// URL.
     * Use the minsponsor_stripe_callback_base option to set a tunnel URL.
     */
    private function create_account_link(\Stripe\StripeClient $stripe, string $account_id, int $lag_id): string {
        // Get callback base URL - can be overridden for local dev (e.g., ngrok tunnel)
        $callback_base = $this->get_callback_base_url();
        
        // Check if this is a localhost URL - if so, we need to use a workaround
        if (strpos($callback_base, 'localhost') !== false || strpos($callback_base, '127.0.0.1') !== false) {
            // For localhost testing, use a real https URL that will redirect back
            // After onboarding, user can manually click "Sjekk status" in admin
            // Using stripe.com/return-test is not real - we'll use our callback mechanism
            $callback_base = 'https://minsponsor.no';
        }
        
        $link = $stripe->accountLinks->create([
            'account' => $account_id,
            'refresh_url' => $callback_base . '/stripe-refresh/?lag_id=' . $lag_id,
            'return_url' => $callback_base . '/stripe-return/?lag_id=' . $lag_id,
            'type' => 'account_onboarding',
        ]);
        
        return $link->url;
    }
    
    /**
     * Get the base URL for Stripe callbacks
     * 
     * Uses site URL by default, but can be overridden for local development
     * via the minsponsor_stripe_callback_base option.
     */
    private function get_callback_base_url(): string {
        // Check for override (e.g., ngrok tunnel for local dev)
        $override = get_option('minsponsor_stripe_callback_base', '');
        
        if (!empty($override)) {
            return rtrim($override, '/');
        }
        
        // Allow filter for programmatic override
        $base_url = apply_filters('minsponsor_stripe_callback_base', home_url());
        
        return rtrim($base_url, '/');
    }
    
    /**
     * AJAX: Get onboarding link for copying
     */
    public function ajax_get_onboarding_link(): void {
        check_ajax_referer('minsponsor_lag_stripe', 'nonce');
        
        $lag_id = intval($_POST['lag_id'] ?? 0);
        $link = get_post_meta($lag_id, self::META_ONBOARDING_LINK, true);
        
        if ($link) {
            wp_send_json_success(['link' => $link]);
        } else {
            wp_send_json_error(['message' => 'No onboarding link found']);
        }
    }
    
    /**
     * Get Stripe status for a lag
     * 
     * @param int $lag_id Lag post ID
     * @return array{account_id: string|null, status: string, is_ready: bool}
     */
    public static function get_stripe_status(int $lag_id): array {
        $account_id = get_post_meta($lag_id, self::META_ACCOUNT_ID, true);
        $status = get_post_meta($lag_id, self::META_ONBOARDING_STATUS, true) ?: self::STATUS_NOT_STARTED;
        
        return [
            'account_id' => $account_id ?: null,
            'status' => $status,
            'is_ready' => $status === self::STATUS_COMPLETE && !empty($account_id),
        ];
    }
    
    /**
     * Check if a lag can receive payments
     * 
     * @param int $lag_id Lag post ID
     * @return bool
     */
    public static function can_receive_payments(int $lag_id): bool {
        $stripe_status = self::get_stripe_status($lag_id);
        return $stripe_status['is_ready'];
    }
}
