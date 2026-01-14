<?php
/**
 * Stripe Customer Portal Service
 * 
 * Enables customers to manage their subscriptions without login
 * via Stripe's hosted Customer Portal
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Services;

if (!defined('ABSPATH')) {
    exit;
}

class StripeCustomerPortal {
    
    /**
     * Initialize hooks
     */
    public function init(): void {
        // Add endpoint for portal access
        add_action('init', [$this, 'add_rewrite_endpoints']);
        add_action('template_redirect', [$this, 'handle_portal_redirect']);
        
        // Add portal link to order emails
        add_action('woocommerce_email_after_order_table', [$this, 'add_portal_link_to_email'], 10, 4);
        
        // CLEAN THANK YOU PAGE: Remove all WooCommerce default content for MinSponsor orders
        add_action('woocommerce_before_thankyou', [$this, 'maybe_cleanup_thankyou_page'], 5);
        
        // Add our custom thank you content
        add_action('woocommerce_thankyou', [$this, 'add_portal_link_to_thankyou'], 5);
        
        // Add admin column for quick portal access
        add_filter('woocommerce_admin_order_actions', [$this, 'add_portal_admin_action'], 10, 2);
        
        // AJAX endpoint for generating portal URL
        add_action('wp_ajax_minsponsor_get_portal_url', [$this, 'ajax_get_portal_url']);
        add_action('wp_ajax_nopriv_minsponsor_get_portal_url', [$this, 'ajax_get_portal_url']);
        
        // Add styles to thank you page head
        add_action('wp_head', [$this, 'add_thankyou_styles']);
    }
    
    /**
     * Add thank you page styles
     */
    public function add_thankyou_styles(): void {
        if (!is_wc_endpoint_url('order-received')) {
            return;
        }
        
        // Check if this is a MinSponsor order
        global $wp;
        $order_id = absint($wp->query_vars['order-received'] ?? 0);
        if (!$order_id) {
            return;
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Check if it's a MinSponsor order
        $player_name = $order->get_meta('_minsponsor_player_name');
        $team_name = $order->get_meta('_minsponsor_team_name');
        $club_name = $order->get_meta('_minsponsor_club_name');
        
        if (!$player_name && !$team_name && !$club_name) {
            return;
        }
        
        ?>
        <style id="minsponsor-thankyou-styles">
            /* Hide ALL WooCommerce thank you page elements for MinSponsor orders */
            .woocommerce-order-overview,
            .woocommerce-order-overview.woocommerce-thankyou-order-details,
            .woocommerce-order-details,
            .woocommerce-customer-details,
            .woocommerce-table--order-details,
            h2.woocommerce-order-details__title,
            h2.woocommerce-column__title,
            section.woocommerce-order-details,
            section.woocommerce-customer-details,
            .wc-block-order-confirmation-summary,
            .wc-block-order-confirmation-totals,
            .wc-block-order-confirmation-downloads,
            .wc-block-order-confirmation-shipping-address,
            .wc-block-order-confirmation-billing-address,
            .wc-block-order-confirmation-additional-information,
            /* Related subscriptions */
            h2:has(+ .shop_table_responsive),
            .shop_table_responsive,
            table.shop_table,
            /* View subscription link */
            p:has(a[href*='view-subscription']),
            .woocommerce-thankyou-order-received + p:has(a) {
                display: none !important;
            }
            
            /* Also hide default thank you message */
            .woocommerce-thankyou-order-received {
                display: none !important;
            }
            
            /* Style our custom content nicely */
            .woocommerce-order article.hentry,
            .woocommerce-checkout .woocommerce {
                max-width: 600px;
                margin: 0 auto;
            }
        </style>
        <?php
    }
    
    /**
     * Remove default WooCommerce thank you content for MinSponsor orders
     */
    public function maybe_cleanup_thankyou_page($order_id): void {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Check if it's a MinSponsor order
        $player_name = $order->get_meta('_minsponsor_player_name');
        $team_name = $order->get_meta('_minsponsor_team_name');
        $club_name = $order->get_meta('_minsponsor_club_name');
        
        if (!$player_name && !$team_name && !$club_name) {
            return;
        }
        
        // Remove all default WooCommerce thank you actions
        remove_action('woocommerce_thankyou', 'woocommerce_order_details_table', 10);
        
        // Remove subscriptions table
        if (class_exists('WC_Subscriptions')) {
            remove_action('woocommerce_thankyou', array('WC_Subscriptions_Order', 'add_subscriptions_to_view_order_templates'), 10);
        }
    }
    
    /**
     * Add rewrite endpoints for portal access
     */
    public function add_rewrite_endpoints(): void {
        add_rewrite_endpoint('administrer-abonnement', EP_ROOT);
    }
    
    /**
     * Handle portal redirect
     */
    public function handle_portal_redirect(): void {
        global $wp_query;
        
        if (!isset($wp_query->query_vars['administrer-abonnement'])) {
            return;
        }
        
        // Get the token/key from the URL
        $token = sanitize_text_field($wp_query->query_vars['administrer-abonnement']);
        
        if (empty($token)) {
            wp_die('Ugyldig lenke. Vennligst bruk lenken fra e-posten din.', 'MinSponsor', ['response' => 400]);
        }
        
        // Look up the order/subscription by token
        $order_id = $this->get_order_id_from_token($token);
        
        if (!$order_id) {
            wp_die('Lenken er ugyldig eller utløpt. Kontakt oss for hjelp.', 'MinSponsor', ['response' => 404]);
        }
        
        // Get the Stripe customer ID
        $stripe_customer_id = $this->get_stripe_customer_id($order_id);
        
        if (!$stripe_customer_id) {
            wp_die('Kunne ikke finne abonnementsdata. Kontakt oss for hjelp.', 'MinSponsor', ['response' => 404]);
        }
        
        // Create portal session and redirect
        $portal_url = $this->create_portal_session($stripe_customer_id);
        
        if ($portal_url) {
            wp_redirect($portal_url);
            exit;
        } else {
            wp_die('Kunne ikke opprette administrasjonsportal. Prøv igjen senere.', 'MinSponsor', ['response' => 500]);
        }
    }
    
    /**
     * Create Stripe Customer Portal session
     *
     * @param string $customer_id Stripe customer ID
     * @return string|null Portal URL or null on failure
     */
    public function create_portal_session(string $customer_id): ?string {
        // Check if Stripe is available
        if (!class_exists('WC_Stripe_API')) {
            error_log('MinSponsor: WC_Stripe_API not found');
            return null;
        }
        
        $secret_key = $this->get_stripe_secret_key();
        
        if (!$secret_key) {
            error_log('MinSponsor: Stripe secret key not found');
            return null;
        }
        
        try {
            $response = wp_remote_post('https://api.stripe.com/v1/billing_portal/sessions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secret_key,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'customer' => $customer_id,
                    'return_url' => home_url('/'),
                ],
                'timeout' => 30,
            ]);
            
            if (is_wp_error($response)) {
                error_log('MinSponsor Portal Error: ' . $response->get_error_message());
                return null;
            }
            
            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if (isset($body['url'])) {
                return $body['url'];
            }
            
            if (isset($body['error'])) {
                error_log('MinSponsor Portal Error: ' . print_r($body['error'], true));
            }
            
            return null;
            
        } catch (\Exception $e) {
            error_log('MinSponsor Portal Exception: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get Stripe secret key from settings
     *
     * @return string|null
     */
    private function get_stripe_secret_key(): ?string {
        $settings = get_option('woocommerce_stripe_settings', []);
        
        $testmode = isset($settings['testmode']) && $settings['testmode'] === 'yes';
        
        if ($testmode) {
            return $settings['test_secret_key'] ?? null;
        }
        
        return $settings['secret_key'] ?? null;
    }
    
    /**
     * Get Stripe customer ID from order
     *
     * @param int $order_id Order ID
     * @return string|null Stripe customer ID
     */
    private function get_stripe_customer_id(int $order_id): ?string {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return null;
        }
        
        // Check order meta for Stripe customer
        $customer_id = $order->get_meta('_stripe_customer_id');
        
        if ($customer_id) {
            return $customer_id;
        }
        
        // Try to get from user meta if logged in
        $user_id = $order->get_user_id();
        if ($user_id) {
            $customer_id = get_user_meta($user_id, 'wp_stripe_customer_id', true);
            if ($customer_id) {
                return $customer_id;
            }
        }
        
        // Try to find from subscriptions
        if (function_exists('wcs_get_subscriptions_for_order')) {
            $subscriptions = wcs_get_subscriptions_for_order($order_id);
            foreach ($subscriptions as $subscription) {
                $customer_id = $subscription->get_meta('_stripe_customer_id');
                if ($customer_id) {
                    return $customer_id;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get order ID from token
     *
     * @param string $token Token from URL
     * @return int|null Order ID
     */
    private function get_order_id_from_token(string $token): ?int {
        // The token is a hash of order_id + order_key
        // Format: base64(order_id:hash)
        
        $decoded = base64_decode($token);
        
        if (!$decoded || strpos($decoded, ':') === false) {
            // Try as direct order key
            $orders = wc_get_orders([
                'order_key' => 'wc_order_' . $token,
                'limit' => 1,
            ]);
            
            if (!empty($orders)) {
                return $orders[0]->get_id();
            }
            
            return null;
        }
        
        list($order_id, $hash) = explode(':', $decoded, 2);
        $order_id = absint($order_id);
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return null;
        }
        
        // Verify hash
        $expected_hash = $this->generate_token_hash($order);
        
        if (!hash_equals($expected_hash, $hash)) {
            return null;
        }
        
        return $order_id;
    }
    
    /**
     * Generate token for order
     *
     * @param \WC_Order $order Order object
     * @return string Token
     */
    public function generate_portal_token(\WC_Order $order): string {
        $order_id = $order->get_id();
        $hash = $this->generate_token_hash($order);
        
        return base64_encode($order_id . ':' . $hash);
    }
    
    /**
     * Generate hash for token verification
     *
     * @param \WC_Order $order Order object
     * @return string Hash
     */
    private function generate_token_hash(\WC_Order $order): string {
        $data = $order->get_id() . $order->get_order_key() . $order->get_billing_email();
        return wp_hash($data);
    }
    
    /**
     * Get portal URL for an order
     *
     * @param \WC_Order $order Order object
     * @return string Portal URL
     */
    public function get_portal_url(\WC_Order $order): string {
        $token = $this->generate_portal_token($order);
        return home_url('/administrer-abonnement/' . $token . '/');
    }
    
    /**
     * Add portal link to order emails
     *
     * @param \WC_Order $order Order object
     * @param bool $sent_to_admin Sent to admin?
     * @param bool $plain_text Plain text?
     * @param \WC_Email $email Email object
     */
    public function add_portal_link_to_email(\WC_Order $order, bool $sent_to_admin, bool $plain_text, \WC_Email $email): void {
        // Only add to customer emails, not admin
        if ($sent_to_admin) {
            return;
        }
        
        // Only for MinSponsor orders with subscriptions
        if (!$order->get_meta('_minsponsor_interval') || $order->get_meta('_minsponsor_interval') !== 'month') {
            return;
        }
        
        $portal_url = $this->get_portal_url($order);
        
        if ($plain_text) {
            echo "\n\n";
            echo "ADMINISTRER ABONNEMENTET DITT\n";
            echo "============================\n";
            echo "Du kan endre betalingsmetode eller avslutte abonnementet når som helst:\n";
            echo $portal_url . "\n\n";
        } else {
            ?>
            <div style="margin: 30px 0; padding: 20px; background: #f0fdf4; border-radius: 8px; border-left: 4px solid #10b981;">
                <h3 style="margin: 0 0 10px; color: #065f46; font-size: 16px;">📋 Administrer abonnementet ditt</h3>
                <p style="margin: 0 0 15px; color: #047857; font-size: 14px;">
                    Du kan endre betalingsmetode eller avslutte abonnementet når som helst.
                </p>
                <a href="<?php echo esc_url($portal_url); ?>" 
                   style="display: inline-block; background: #10b981; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600;">
                    Administrer abonnement
                </a>
            </div>
            <?php
        }
    }
    
    /**
     * Add portal link to thank you page - CLEAN VERSION
     * Replaces the cluttered WooCommerce order details with a simple, clear message
     *
     * @param int $order_id Order ID
     */
    public function add_portal_link_to_thankyou(int $order_id): void {
        error_log('MinSponsor: add_portal_link_to_thankyou called with order ID: ' . $order_id);
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            error_log('MinSponsor: Order not found for ID: ' . $order_id);
            return;
        }
        
        // Check if this is a MinSponsor order
        $interval = $order->get_meta('_minsponsor_interval');
        $is_subscription = ($interval === 'month');
        
        // Get recipient info
        $player_name = $order->get_meta('_minsponsor_player_name');
        $team_name = $order->get_meta('_minsponsor_team_name');
        $club_name = $order->get_meta('_minsponsor_club_name');
        $amount = $order->get_meta('_minsponsor_amount');
        
        error_log('MinSponsor: Order meta - player: ' . $player_name . ', team: ' . $team_name . ', club: ' . $club_name . ', amount: ' . $amount);
        
        // If not a MinSponsor order, don't show custom content
        if (!$player_name && !$team_name && !$club_name) {
            error_log('MinSponsor: No recipient found, exiting');
            return;
        }
        
        // Determine recipient and type
        $recipient_name = $player_name ?: $team_name ?: $club_name;
        $recipient_type = $player_name ? 'spiller' : ($team_name ? 'lag' : 'klubb');
        $recipient_type_text = $player_name ? 'spilleren' : ($team_name ? 'laget' : 'klubben');
        
        // Build hierarchy text
        $hierarchy_parts = [];
        if ($recipient_type === 'spiller') {
            if ($team_name) $hierarchy_parts[] = $team_name;
            if ($club_name) $hierarchy_parts[] = $club_name;
        } elseif ($recipient_type === 'lag') {
            if ($club_name) $hierarchy_parts[] = $club_name;
        }
        $hierarchy_text = !empty($hierarchy_parts) ? implode(' i ', array_reverse($hierarchy_parts)) : '';
        
        // Get next payment date for subscriptions
        $next_payment_date = '';
        if ($is_subscription) {
            // Get related subscription
            $subscriptions = wcs_get_subscriptions_for_order($order_id);
            if (!empty($subscriptions)) {
                $subscription = reset($subscriptions);
                $next_date = $subscription->get_date('next_payment');
                if ($next_date) {
                    $next_payment_date = date_i18n('j. F Y', strtotime($next_date));
                }
            }
        }
        
        // Get customer email
        $customer_email = $order->get_billing_email();
        
        // Get portal token for subscription management
        $portal_token = $order->get_meta('_minsponsor_portal_token');
        $portal_url = '';
        if ($portal_token) {
            $portal_url = home_url('/administrer-abonnement/' . $portal_token);
        }
        
        // CSS Variables and Hide default WooCommerce content
        $css = "
            <style id='minsponsor-thankyou-inline'>
                /* === MinSponsor Thank You Page === */
                
                /* CSS Variables */
                .minsponsor-thankyou-wrapper {
                    --ms-korall: #F6A586;
                    --ms-terrakotta: #D97757;
                    --ms-terrakotta-dark: #B85D42;
                    --ms-beige: #F5EFE6;
                    --ms-krem: #FBF8F3;
                    --ms-brun: #3D3228;
                    --ms-brun-light: #5A4D3F;
                    --ms-softgra: #E8E2D9;
                    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
                    max-width: 520px;
                    margin: 0 auto;
                }
                
                /* HIDE ALL DEFAULT WOOCOMMERCE CONTENT */
                .woocommerce-order-overview,
                .woocommerce-order-details,
                .woocommerce-customer-details,
                .woocommerce-table--order-details,
                h2.woocommerce-order-details__title,
                h2.woocommerce-column__title,
                section.woocommerce-order-details,
                section.woocommerce-customer-details,
                .woocommerce-thankyou-order-received,
                .shop_table_responsive,
                table.shop_table,
                table.woocommerce-table,
                .wc-block-order-confirmation-summary,
                .wc-block-order-confirmation-totals,
                .wc-block-order-confirmation-downloads,
                .wc-block-order-confirmation-shipping-address,
                .wc-block-order-confirmation-billing-address,
                .wc-block-order-confirmation-additional-information {
                    display: none !important;
                }
                
                /* Hide Related subscriptions heading */
                .woocommerce-order h2 {
                    display: none !important;
                }
                
                /* Hide \"View the status of your subscription\" paragraph */
                .woocommerce-order > p:not(.minsponsor-thankyou-wrapper p) {
                    display: none !important;
                }
                
                /* Re-show only our content */
                .minsponsor-thankyou-wrapper,
                .minsponsor-thankyou-wrapper * {
                    display: revert !important;
                }
                .minsponsor-thankyou-wrapper h2,
                .minsponsor-thankyou-wrapper h3 {
                    display: block !important;
                }
                .minsponsor-thankyou-wrapper p {
                    display: block !important;
                }
                .minsponsor-thankyou-wrapper .info-row {
                    display: flex !important;
                }
                
                .minsponsor-thankyou-box {
                    background: linear-gradient(135deg, var(--ms-korall) 0%, var(--ms-terrakotta) 100%);
                    color: var(--ms-krem);
                    padding: 32px 24px;
                    border-radius: 20px;
                    margin: 0 0 24px;
                    text-align: center;
                    box-shadow: 0 8px 32px rgba(217, 119, 87, 0.25);
                }
                
                .minsponsor-thankyou-box .emoji {
                    font-size: 56px;
                    margin-bottom: 16px;
                    display: block !important;
                }
                
                .minsponsor-thankyou-box h2 {
                    margin: 0 0 16px !important;
                    font-size: 26px !important;
                    font-weight: 700 !important;
                    color: inherit !important;
                }
                
                .minsponsor-thankyou-box .message {
                    font-size: 17px;
                    line-height: 1.6;
                    opacity: 0.95;
                    margin: 0 !important;
                }
                
                .minsponsor-thankyou-box .message strong {
                    font-weight: 600;
                }
                
                .minsponsor-thankyou-box .sub-message {
                    font-size: 14px;
                    opacity: 0.85;
                    margin-top: 12px !important;
                }
                
                .minsponsor-info-card {
                    background: var(--ms-krem);
                    border: 1px solid var(--ms-softgra);
                    border-radius: 16px;
                    padding: 24px;
                    margin-bottom: 20px;
                }
                
                .minsponsor-info-card h3 {
                    margin: 0 0 16px !important;
                    font-size: 15px !important;
                    font-weight: 600 !important;
                    color: var(--ms-brun) !important;
                    display: flex !important;
                    align-items: center;
                    gap: 8px;
                }
                
                .minsponsor-info-card .info-row {
                    display: flex !important;
                    justify-content: space-between;
                    align-items: center;
                    padding: 12px 0;
                    border-bottom: 1px solid var(--ms-softgra);
                    font-size: 14px;
                    color: var(--ms-brun-light);
                }
                
                .minsponsor-info-card .info-row:last-of-type {
                    border-bottom: none;
                    padding-bottom: 0;
                }
                
                .minsponsor-info-card .info-row .label {
                    color: var(--ms-brun-light);
                }
                
                .minsponsor-info-card .info-row .value {
                    font-weight: 500;
                    color: var(--ms-brun);
                }
                
                .minsponsor-manage-link {
                    display: inline-flex !important;
                    align-items: center;
                    gap: 8px;
                    background: var(--ms-terrakotta);
                    color: var(--ms-krem) !important;
                    padding: 14px 28px;
                    border-radius: 10px;
                    font-weight: 600;
                    font-size: 15px;
                    text-decoration: none !important;
                    transition: all 0.2s;
                    margin-top: 8px;
                }
                
                .minsponsor-manage-link:hover {
                    background: var(--ms-terrakotta-dark);
                    transform: translateY(-1px);
                    box-shadow: 0 4px 16px rgba(217, 119, 87, 0.3);
                    color: var(--ms-krem) !important;
                }
                
                .minsponsor-email-notice {
                    background: rgba(217, 119, 87, 0.08);
                    border-radius: 10px;
                    padding: 16px 20px;
                    margin-top: 16px;
                    font-size: 13px;
                    color: var(--ms-brun-light);
                    display: flex !important;
                    align-items: flex-start;
                    gap: 10px;
                }
                
                .minsponsor-email-notice svg {
                    flex-shrink: 0;
                    margin-top: 2px;
                    color: var(--ms-terrakotta);
                }
                
                @media (max-width: 480px) {
                    .minsponsor-thankyou-wrapper {
                        padding: 0 16px;
                    }
                    
                    .minsponsor-thankyou-box {
                        padding: 24px 16px;
                    }
                    
                    .minsponsor-thankyou-box h2 {
                        font-size: 22px !important;
                    }
                    
                    .minsponsor-thankyou-box .message {
                        font-size: 15px;
                    }
                    
                    .minsponsor-info-card .info-row {
                        flex-direction: column !important;
                        align-items: flex-start !important;
                        gap: 4px;
                    }
                }
            </style>
        ";
        
        echo $css;
        ?>
        <div class="minsponsor-thankyou-wrapper">
            <div class="minsponsor-thankyou-box">
                <span class="emoji">🎉</span>
                <h2>Tusen takk for støtten!</h2>
                <p class="message">
                    <?php if ($is_subscription): ?>
                        Du støtter nå <strong><?php echo esc_html($recipient_name); ?></strong>
                        <?php if ($hierarchy_text): ?>
                            <?php echo ($recipient_type === 'spiller') ? 'på' : 'i'; ?> 
                            <?php echo esc_html($hierarchy_text); ?>
                        <?php endif; ?>
                        med <strong>kr <?php echo number_format((float)$amount, 0, ',', ' '); ?></strong> i måneden.
                    <?php else: ?>
                        Du har gitt <strong>kr <?php echo number_format((float)$amount, 0, ',', ' '); ?></strong> til 
                        <strong><?php echo esc_html($recipient_name); ?></strong>
                        <?php if ($hierarchy_text): ?>
                            <?php echo ($recipient_type === 'spiller') ? 'på' : 'i'; ?> 
                            <?php echo esc_html($hierarchy_text); ?>
                        <?php endif; ?>.
                    <?php endif; ?>
                </p>
                <p class="sub-message">
                    <?php echo esc_html(ucfirst($recipient_type_text)); ?> mottar hele støttebeløpet.
                </p>
            </div>
            
            <?php if ($is_subscription): ?>
            <div class="minsponsor-info-card">
                <h3>
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Ditt abonnement
                </h3>
                <div class="info-row">
                    <span class="label">Beløp</span>
                    <span class="value">kr <?php echo number_format((float)$amount, 0, ',', ' '); ?> / måned</span>
                </div>
                <?php if ($next_payment_date): ?>
                <div class="info-row">
                    <span class="label">Neste trekk</span>
                    <span class="value"><?php echo esc_html($next_payment_date); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="label">Kvittering sendes til</span>
                    <span class="value"><?php echo esc_html($customer_email); ?></span>
                </div>
                
                <?php if ($portal_url): ?>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="<?php echo esc_url($portal_url); ?>" class="minsponsor-manage-link">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Administrer abonnement
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="minsponsor-email-notice">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <span>
                        Du har fått en e-post med kvittering og lenke for å endre betalingsmåte eller avslutte abonnementet når som helst.
                    </span>
                </div>
            </div>
            <?php else: ?>
            <div class="minsponsor-info-card">
                <h3>
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Betalingskvittering
                </h3>
                <div class="info-row">
                    <span class="label">Beløp</span>
                    <span class="value">kr <?php echo number_format((float)$amount, 0, ',', ' '); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Kvittering sendt til</span>
                    <span class="value"><?php echo esc_html($customer_email); ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Add portal action to admin order actions
     *
     * @param array $actions Existing actions
     * @param \WC_Order $order Order object
     * @return array Modified actions
     */
    public function add_portal_admin_action(array $actions, \WC_Order $order): array {
        // Only for MinSponsor monthly orders
        if ($order->get_meta('_minsponsor_interval') !== 'month') {
            return $actions;
        }
        
        $stripe_customer = $this->get_stripe_customer_id($order->get_id());
        
        if ($stripe_customer) {
            $actions['minsponsor_portal'] = [
                'url' => admin_url('admin-ajax.php?action=minsponsor_get_portal_url&order_id=' . $order->get_id() . '&_wpnonce=' . wp_create_nonce('minsponsor_portal')),
                'name' => 'Portal',
                'action' => 'minsponsor-portal',
            ];
        }
        
        return $actions;
    }
    
    /**
     * AJAX handler for getting portal URL
     */
    public function ajax_get_portal_url(): void {
        // Check nonce for admin requests
        if (is_admin() && !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'minsponsor_portal')) {
            wp_die('Ugyldig forespørsel');
        }
        
        $order_id = absint($_GET['order_id'] ?? 0);
        
        if (!$order_id) {
            wp_die('Mangler ordre-ID');
        }
        
        $stripe_customer_id = $this->get_stripe_customer_id($order_id);
        
        if (!$stripe_customer_id) {
            wp_die('Kunne ikke finne Stripe-kunde');
        }
        
        $portal_url = $this->create_portal_session($stripe_customer_id);
        
        if ($portal_url) {
            wp_redirect($portal_url);
            exit;
        } else {
            wp_die('Kunne ikke opprette portal-sesjon');
        }
    }
}
