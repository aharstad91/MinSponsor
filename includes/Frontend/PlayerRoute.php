<?php
/**
 * Sponsorship Route Handler for MinSponsor
 * 
 * Handles template_redirect for klubb, lag, and spiller pages with sponsorship parameters
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Frontend;

use MinSponsor\Services\PlayerLinksService;
use MinSponsor\Admin\LagStripeMetaBox;

if (!defined('ABSPATH')) {
    exit;
}

class PlayerRoute {
    
    private PlayerLinksService $links_service;
    
    public function __construct(?PlayerLinksService $links_service = null) {
        $this->links_service = $links_service ?? new PlayerLinksService();
    }
    
    /**
     * Initialize hooks
     */
    public function init(): void {
        // Handle all MinSponsor content types
        add_action('template_redirect', [$this, 'handle_sponsorship_request'], 5);
    }
    
    /**
     * Handle sponsorship requests on klubb, lag, and spiller pages
     */
    public function handle_sponsorship_request(): void {
        // Only process klubb, lag, or spiller pages
        if (!is_singular(['klubb', 'lag', 'spiller'])) {
            return;
        }
        
        // Check if we have sponsorship parameters
        $has_interval = isset($_GET['interval']);
        $has_amount = isset($_GET['amount']);
        $has_ref = isset($_GET['ref']);
        
        if (!$has_interval && !$has_amount && !$has_ref) {
            return;
        }
        
        global $post;
        if (!$post || !in_array($post->post_type, ['klubb', 'lag', 'spiller'])) {
            return;
        }
        
        // Validate and sanitize parameters
        $params = $this->links_service->validate_link_params($_GET);
        
        // Check if the recipient's team has an active Stripe account
        $stripe_check = $this->check_stripe_status($post);
        if (!$stripe_check['can_receive']) {
            $this->show_stripe_not_connected_error($post, $stripe_check);
            return;
        }
        
        // Get player products (used for all types currently)
        $one_time_product_id = $this->get_player_product_id('one_time');
        $monthly_product_id = $this->get_player_product_id('monthly');
        
        if (!$one_time_product_id || !$monthly_product_id) {
            $this->show_admin_notice_and_return('Produkter for støtte er ikke konfigurert. Kontakt administrator.');
            return;
        }
        
        // Select correct product based on interval
        $product_id = ($params['interval'] === 'once') ? $one_time_product_id : $monthly_product_id;
        
        // Verify product exists and is published
        $product = wc_get_product($product_id);
        if (!$product || $product->get_status() !== 'publish') {
            $this->show_admin_notice_and_return('Det valgte produktet er ikke tilgjengelig.');
            return;
        }
        
        // Clear cart first to avoid confusion
        \WC()->cart->empty_cart();
        
        // Get cart item data based on post type
        $cart_item_data = $this->get_cart_item_data_for_post($post, $params);
        
        // Add product to cart
        $cart_item_key = \WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);
        
        if (!$cart_item_key) {
            $this->show_admin_notice_and_return('Kunne ikke legge produktet i handlekurven. Prøv igjen.');
            return;
        }
        
        // Log successful add to cart
        error_log(sprintf(
            'MinSponsor: Added %s sponsorship to cart - %s: %s (%d), Product: %d, Interval: %s, Amount: %s',
            $post->post_type,
            $post->post_type,
            $post->post_title,
            $post->ID,
            $product_id,
            $params['interval'],
            isset($params['amount']) ? $params['amount'] : 'default'
        ));
        
        // Redirect to checkout
        wp_safe_redirect(wc_get_checkout_url());
        exit;
    }
    
    /**
     * Get cart item data based on post type (klubb, lag, or spiller)
     *
     * @param \WP_Post $post The post object
     * @param array $params Validated parameters
     * @return array Cart item data
     */
    private function get_cart_item_data_for_post(\WP_Post $post, array $params): array {
        $data = [
            'minsponsor_interval' => $params['interval'],
        ];
        
        if (isset($params['amount'])) {
            $data['minsponsor_amount'] = $params['amount'];
        }
        
        if (isset($params['ref'])) {
            $data['minsponsor_ref'] = $params['ref'];
        }
        
        switch ($post->post_type) {
            case 'spiller':
                // For spiller, use existing service method
                return $this->links_service->get_cart_item_data($post->ID, $params);
                
            case 'lag':
                // For lag, include lag and klubb info
                $klubb_id = minsponsor_get_parent_klubb_id($post->ID);
                $klubb = $klubb_id ? get_post($klubb_id) : null;
                
                $data['minsponsor_recipient_type'] = 'lag';
                $data['minsponsor_team_id'] = $post->ID;
                $data['minsponsor_team_name'] = $post->post_title;
                $data['minsponsor_team_slug'] = $post->post_name;
                
                if ($klubb) {
                    $data['minsponsor_club_id'] = $klubb->ID;
                    $data['minsponsor_club_name'] = $klubb->post_title;
                    $data['minsponsor_club_slug'] = $klubb->post_name;
                }
                break;
                
            case 'klubb':
                // For klubb, only klubb info
                $data['minsponsor_recipient_type'] = 'klubb';
                $data['minsponsor_club_id'] = $post->ID;
                $data['minsponsor_club_name'] = $post->post_title;
                $data['minsponsor_club_slug'] = $post->post_name;
                break;
        }
        
        return $data;
    }
    
    /**
     * Legacy method - kept for backwards compatibility
     */
    public function handle_player_sponsorship_request(): void {
        $this->handle_sponsorship_request();
    }
    
    /**
     * Get player product ID from settings or SKU fallback
     *
     * @param string $type 'one_time' or 'monthly'
     * @return int|false Product ID or false if not found
     */
    private function get_player_product_id(string $type): int|false {
        // Try to get from WooCommerce settings first
        $setting_key = "minsponsor_player_product_{$type}_id";
        $product_id = get_option($setting_key);
        
        if ($product_id && is_numeric($product_id)) {
            $product = wc_get_product($product_id);
            if ($product) {
                return (int) $product_id;
            }
        }
        
        // Fallback to SKU lookup
        $sku = ($type === 'one_time') ? 'minsponsor_player_one_time' : 'minsponsor_player_monthly';
        $product_id = wc_get_product_id_by_sku($sku);
        
        if ($product_id) {
            return (int) $product_id;
        }
        
        return false;
    }
    
    /**
     * Show admin notice and return to player page without parameters
     *
     * @param string $message Error message
     */
    private function show_admin_notice_and_return(string $message): void {
        // Store message for display
        set_transient('minsponsor_error_' . get_current_user_id(), $message, 60);
        
        // Redirect to clean player URL
        global $post;
        if ($post) {
            wp_safe_redirect(get_permalink($post));
            exit;
        }
    }
    
    /**
     * Display error notices
     */
    public static function display_error_notices(): void {
        if (!is_singular('spiller')) {
            return;
        }
        
        $error_message = get_transient('minsponsor_error_' . get_current_user_id());
        if ($error_message) {
            delete_transient('minsponsor_error_' . get_current_user_id());
            
            echo '<div class="minsponsor-error-notice" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 20px 0; border-radius: 4px;">';
            echo '<strong>Feil:</strong> ' . esc_html($error_message);
            echo '</div>';
        }
    }
    
    /**
     * Check if the recipient's team has an active Stripe Connect account
     *
     * Stripe accounts are at the lag (team) level. For spillere, we check their parent lag.
     * For klubb, we currently don't require Stripe (money goes to platform).
     *
     * @param \WP_Post $post The recipient post (klubb, lag, or spiller)
     * @return array{can_receive: bool, lag_id: int|null, lag_name: string|null, status: string}
     */
    private function check_stripe_status(\WP_Post $post): array {
        $lag_id = null;
        $lag_name = null;
        
        switch ($post->post_type) {
            case 'spiller':
                // Get parent lag for spiller
                $lag_id = minsponsor_get_parent_lag_id($post->ID);
                if ($lag_id) {
                    $lag_name = get_the_title($lag_id);
                }
                break;
                
            case 'lag':
                // Direct lag sponsorship
                $lag_id = $post->ID;
                $lag_name = $post->post_title;
                break;
                
            case 'klubb':
                // Klubb sponsorship - for now, allow without Stripe
                // In the future, klubb might need its own Stripe or use a default
                return [
                    'can_receive' => true,
                    'lag_id' => null,
                    'lag_name' => null,
                    'status' => 'klubb_allowed',
                ];
        }
        
        // If we couldn't find a lag, block the transaction
        if (!$lag_id) {
            return [
                'can_receive' => false,
                'lag_id' => null,
                'lag_name' => null,
                'status' => 'no_lag_found',
            ];
        }
        
        // Check Stripe status for the lag
        $stripe_status = LagStripeMetaBox::get_stripe_status($lag_id);
        
        return [
            'can_receive' => $stripe_status['is_ready'],
            'lag_id' => $lag_id,
            'lag_name' => $lag_name,
            'status' => $stripe_status['status'],
        ];
    }
    
    /**
     * Show error page when Stripe is not connected
     *
     * @param \WP_Post $post The recipient post
     * @param array $stripe_check The result from check_stripe_status
     */
    private function show_stripe_not_connected_error(\WP_Post $post, array $stripe_check): void {
        // Store error info for template
        set_query_var('minsponsor_stripe_error', [
            'recipient_type' => $post->post_type,
            'recipient_name' => $post->post_title,
            'recipient_url' => get_permalink($post),
            'lag_id' => $stripe_check['lag_id'],
            'lag_name' => $stripe_check['lag_name'],
            'status' => $stripe_check['status'],
        ]);
        
        // Load error template
        $template = locate_template('templates/stripe-not-connected.php');
        if ($template) {
            include $template;
        } else {
            // Fallback inline error
            $this->render_fallback_stripe_error($post, $stripe_check);
        }
        exit;
    }
    
    /**
     * Render fallback error if template is missing
     *
     * @param \WP_Post $post The recipient post
     * @param array $stripe_check The result from check_stripe_status
     */
    private function render_fallback_stripe_error(\WP_Post $post, array $stripe_check): void {
        get_header();
        ?>
        <div style="max-width: 600px; margin: 80px auto; padding: 40px; text-align: center; font-family: Inter, sans-serif;">
            <div style="font-size: 64px; margin-bottom: 20px;">😢</div>
            <h1 style="color: #3D3228; font-size: 28px; margin-bottom: 16px;">
                Kan ikke motta støtte ennå
            </h1>
            <p style="color: #666; font-size: 16px; line-height: 1.6; margin-bottom: 24px;">
                <?php if ($stripe_check['status'] === 'no_lag_found'): ?>
                    Denne utøveren er ikke tilknyttet et lag som kan motta betalinger.
                <?php elseif ($stripe_check['status'] === 'pending'): ?>
                    <strong><?php echo esc_html($stripe_check['lag_name']); ?></strong> 
                    holder på å sette opp betalingsmottak. Prøv igjen senere!
                <?php else: ?>
                    <strong><?php echo esc_html($stripe_check['lag_name']); ?></strong> 
                    har ikke satt opp betalingsmottak ennå.
                <?php endif; ?>
            </p>
            <a href="<?php echo esc_url(get_permalink($post)); ?>" 
               style="display: inline-block; background: #D97757; color: #FBF8F3; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                ← Tilbake
            </a>
        </div>
        <?php
        get_footer();
    }
}
