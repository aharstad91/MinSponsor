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
        
        // Add portal link to thank you page
        add_action('woocommerce_thankyou', [$this, 'add_portal_link_to_thankyou'], 5);
        
        // Add admin column for quick portal access
        add_filter('woocommerce_admin_order_actions', [$this, 'add_portal_admin_action'], 10, 2);
        
        // AJAX endpoint for generating portal URL
        add_action('wp_ajax_minsponsor_get_portal_url', [$this, 'ajax_get_portal_url']);
        add_action('wp_ajax_nopriv_minsponsor_get_portal_url', [$this, 'ajax_get_portal_url']);
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
     * Add portal link to thank you page
     *
     * @param int $order_id Order ID
     */
    public function add_portal_link_to_thankyou(int $order_id): void {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Only for monthly subscriptions
        if ($order->get_meta('_minsponsor_interval') !== 'month') {
            return;
        }
        
        $player_name = $order->get_meta('_minsponsor_player_name') ?: 
                       $order->get_meta('_minsponsor_team_name') ?: 
                       $order->get_meta('_minsponsor_club_name');
        
        $amount = $order->get_meta('_minsponsor_amount');
        
        ?>
        <div class="minsponsor-thankyou-box" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 30px; border-radius: 16px; margin: 30px 0; text-align: center;">
            <div style="font-size: 48px; margin-bottom: 16px;">🎉</div>
            <h2 style="margin: 0 0 8px; font-size: 24px; font-weight: 700;">Tusen takk for støtten!</h2>
            <?php if ($player_name): ?>
                <p style="margin: 0 0 16px; opacity: 0.9; font-size: 16px;">
                    Du støtter nå <strong><?php echo esc_html($player_name); ?></strong> med 
                    <strong>kr <?php echo number_format((float)$amount, 0, ',', ' '); ?></strong> hver måned.
                </p>
            <?php endif; ?>
            <p style="margin: 0; font-size: 14px; opacity: 0.8;">
                ✉️ Du har fått en bekreftelse på e-post med lenke for å administrere abonnementet.
            </p>
        </div>
        
        <div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 30px;">
            <h3 style="margin: 0 0 12px; font-size: 16px; color: #1e293b;">💡 Viktig informasjon</h3>
            <ul style="margin: 0; padding-left: 20px; color: #475569; font-size: 14px; line-height: 1.8;">
                <li>Beløpet trekkes automatisk hver måned</li>
                <li>Du kan endre betalingsmetode eller avslutte når som helst</li>
                <li>Bruk lenken i bekreftelsesmailen for å administrere abonnementet</li>
                <li>Ingen binding - avslutt når du vil</li>
            </ul>
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
