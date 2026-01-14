<?php
/**
 * MinSponsor Settings Page
 * 
 * Central admin page for MinSponsor configuration with tabs for
 * fees, Stripe settings, and environment configuration.
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Settings;

if (!defined('ABSPATH')) {
    exit;
}

class StripeSettings {
    
    /**
     * Option keys
     */
    public const OPTION_PLATFORM_FEE = 'minsponsor_platform_fee_percent';
    public const OPTION_STRIPE_ENV = 'minsponsor_stripe_environment';
    public const OPTION_STRIPE_TEST_SECRET = 'minsponsor_stripe_test_secret_key';
    public const OPTION_STRIPE_TEST_PUBLISHABLE = 'minsponsor_stripe_test_publishable_key';
    public const OPTION_STRIPE_LIVE_SECRET = 'minsponsor_stripe_live_secret_key';
    public const OPTION_STRIPE_LIVE_PUBLISHABLE = 'minsponsor_stripe_live_publishable_key';
    public const OPTION_STRIPE_WEBHOOK_SECRET = 'minsponsor_stripe_webhook_secret';
    
    /**
     * Default values
     */
    private const DEFAULT_PLATFORM_FEE = 6;
    private const DEFAULT_STRIPE_ENV = 'test';
    
    /**
     * Initialize hooks
     */
    public function init(): void {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu(): void {
        add_menu_page(
            'MinSponsor',
            'MinSponsor',
            'manage_options',
            'minsponsor-settings',
            [$this, 'render_settings_page'],
            'dashicons-heart',
            58
        );
        
        add_submenu_page(
            'minsponsor-settings',
            'Settings',
            'Settings',
            'manage_options',
            'minsponsor-settings',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings(): void {
        // Fees section
        register_setting('minsponsor_fees', self::OPTION_PLATFORM_FEE, [
            'type' => 'number',
            'default' => self::DEFAULT_PLATFORM_FEE,
            'sanitize_callback' => [$this, 'sanitize_fee_percent'],
        ]);
        
        // Stripe section
        register_setting('minsponsor_stripe', self::OPTION_STRIPE_ENV, [
            'type' => 'string',
            'default' => self::DEFAULT_STRIPE_ENV,
            'sanitize_callback' => [$this, 'sanitize_stripe_env'],
        ]);
        
        register_setting('minsponsor_stripe', self::OPTION_STRIPE_TEST_SECRET, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        
        register_setting('minsponsor_stripe', self::OPTION_STRIPE_TEST_PUBLISHABLE, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        
        register_setting('minsponsor_stripe', self::OPTION_STRIPE_LIVE_SECRET, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        
        register_setting('minsponsor_stripe', self::OPTION_STRIPE_LIVE_PUBLISHABLE, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        
        register_setting('minsponsor_stripe', self::OPTION_STRIPE_WEBHOOK_SECRET, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        
        // Callback base URL for local development
        register_setting('minsponsor_stripe', 'minsponsor_stripe_callback_base', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
        ]);
    }
    
    /**
     * Sanitize fee percent
     */
    public function sanitize_fee_percent($value): int {
        $value = absint($value);
        return min(max($value, 0), 50); // Between 0-50%
    }
    
    /**
     * Sanitize Stripe environment
     */
    public function sanitize_stripe_env($value): string {
        return in_array($value, ['test', 'live']) ? $value : 'test';
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles(string $hook): void {
        if (strpos($hook, 'minsponsor') === false) {
            return;
        }
        
        wp_add_inline_style('wp-admin', $this->get_inline_styles());
    }
    
    /**
     * Get inline styles following MinSponsor design system
     */
    private function get_inline_styles(): string {
        return <<<CSS
.minsponsor-settings-wrap {
    max-width: 800px;
    margin: 20px 0;
}

.minsponsor-settings-wrap h1 {
    color: #3D3228;
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 20px;
}

.minsponsor-tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid #E8E2D9;
    margin-bottom: 30px;
}

.minsponsor-tab {
    padding: 12px 24px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #3D3228;
    text-decoration: none;
    transition: all 0.2s ease;
}

.minsponsor-tab:hover {
    background: #FBF8F3;
    color: #D97757;
}

.minsponsor-tab.active {
    border-bottom-color: #D97757;
    color: #D97757;
    font-weight: 600;
}

.minsponsor-card {
    background: #FBF8F3;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 4px 20px rgba(61, 50, 40, 0.08);
}

.minsponsor-card h2 {
    color: #3D3228;
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 16px 0;
    padding-bottom: 12px;
    border-bottom: 1px solid #E8E2D9;
}

.minsponsor-field {
    margin-bottom: 20px;
}

.minsponsor-field label {
    display: block;
    color: #3D3228;
    font-weight: 500;
    margin-bottom: 6px;
}

.minsponsor-field input[type="text"],
.minsponsor-field input[type="number"],
.minsponsor-field input[type="password"],
.minsponsor-field select {
    width: 100%;
    max-width: 400px;
    padding: 12px 16px;
    border: 1px solid #E8E2D9;
    border-radius: 8px;
    background: #fff;
    font-size: 14px;
    transition: border-color 0.2s ease;
}

.minsponsor-field input:focus,
.minsponsor-field select:focus {
    outline: none;
    border-color: #D97757;
}

.minsponsor-field .description {
    color: #666;
    font-size: 12px;
    margin-top: 6px;
}

.minsponsor-field-inline {
    display: flex;
    align-items: center;
    gap: 8px;
}

.minsponsor-field-inline input {
    width: 80px !important;
}

.minsponsor-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.minsponsor-badge.test {
    background: #fff3cd;
    color: #856404;
}

.minsponsor-badge.live {
    background: #d4edda;
    color: #155724;
}

.minsponsor-env-indicator {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #F5EFE6;
    border-radius: 8px;
    margin-bottom: 20px;
}

.minsponsor-env-indicator.live {
    background: #d4edda;
}

.minsponsor-env-indicator .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.minsponsor-submit-btn {
    background: #D97757 !important;
    border-color: #D97757 !important;
    color: #FBF8F3 !important;
    padding: 10px 24px !important;
    border-radius: 8px !important;
    font-weight: 600 !important;
    transition: background 0.2s ease !important;
}

.minsponsor-submit-btn:hover {
    background: #B85D42 !important;
    border-color: #B85D42 !important;
}

.minsponsor-webhook-url {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    padding: 12px 16px;
    border-radius: 8px;
    border: 1px solid #E8E2D9;
    max-width: 600px;
}

.minsponsor-webhook-url code {
    flex: 1;
    word-break: break-all;
    font-size: 13px;
}

.minsponsor-webhook-url button {
    flex-shrink: 0;
}

.minsponsor-warning {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 12px 16px;
    border-radius: 0 8px 8px 0;
    margin-bottom: 20px;
}

.minsponsor-warning strong {
    color: #856404;
}
CSS;
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $active_tab = $_GET['tab'] ?? 'fees';
        $stripe_env = get_option(self::OPTION_STRIPE_ENV, 'test');
        
        ?>
        <div class="wrap minsponsor-settings-wrap">
            <h1>MinSponsor Settings</h1>
            
            <!-- Environment Indicator -->
            <div class="minsponsor-env-indicator <?php echo $stripe_env === 'live' ? 'live' : ''; ?>">
                <span class="dashicons <?php echo $stripe_env === 'live' ? 'dashicons-yes-alt' : 'dashicons-info'; ?>"></span>
                <div>
                    <strong>Stripe environment:</strong>
                    <span class="minsponsor-badge <?php echo esc_attr($stripe_env); ?>">
                        <?php echo $stripe_env === 'live' ? 'LIVE' : 'TEST'; ?>
                    </span>
                    <?php if ($stripe_env === 'live'): ?>
                        – Real payments are enabled!
                    <?php else: ?>
                        – Test payments only
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tabs -->
            <nav class="minsponsor-tabs">
                <a href="?page=minsponsor-settings&tab=fees" 
                   class="minsponsor-tab <?php echo $active_tab === 'fees' ? 'active' : ''; ?>">
                    Fees
                </a>
                <a href="?page=minsponsor-settings&tab=stripe" 
                   class="minsponsor-tab <?php echo $active_tab === 'stripe' ? 'active' : ''; ?>">
                    Stripe
                </a>
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=minsponsor'); ?>" 
                   class="minsponsor-tab">
                    Products ↗
                </a>
            </nav>
            
            <?php
            switch ($active_tab) {
                case 'stripe':
                    $this->render_stripe_tab();
                    break;
                case 'fees':
                default:
                    $this->render_fees_tab();
                    break;
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Render fees tab
     */
    private function render_fees_tab(): void {
        $platform_fee = get_option(self::OPTION_PLATFORM_FEE, self::DEFAULT_PLATFORM_FEE);
        
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('minsponsor_fees'); ?>
            
            <div class="minsponsor-card">
                <h2>Fee Settings</h2>
                
                <div class="minsponsor-field">
                    <label for="<?php echo self::OPTION_PLATFORM_FEE; ?>">Platform Fee (%)</label>
                    <div class="minsponsor-field-inline">
                        <input type="number" 
                               id="<?php echo self::OPTION_PLATFORM_FEE; ?>"
                               name="<?php echo self::OPTION_PLATFORM_FEE; ?>"
                               value="<?php echo esc_attr($platform_fee); ?>"
                               min="0" 
                               max="50" 
                               step="1">
                        <span>%</span>
                    </div>
                    <p class="description">
                        The fee that Samhold AS takes from each transaction. Default is 6%.
                    </p>
                </div>
                
                <div class="minsponsor-card" style="background: #fff; margin-top: 20px;">
                    <h2>Fee Calculation Example</h2>
                    <p>For a sponsorship amount of <strong>100 kr</strong>:</p>
                    <ul style="margin-left: 20px;">
                        <li>Sponsor pays: <strong>110 kr</strong> (100 kr + 10% surcharge)</li>
                        <li>Stripe takes: ~3.69 kr (2.9% + 2.50 kr)</li>
                        <li>Platform fee: <?php echo $platform_fee; ?> kr (<?php echo $platform_fee; ?>%)</li>
                        <li>Recipient gets: <strong>100 kr</strong></li>
                    </ul>
                </div>
            </div>
            
            <p>
                <input type="submit" class="button minsponsor-submit-btn" value="Save Changes">
            </p>
        </form>
        <?php
    }
    
    /**
     * Render Stripe tab
     */
    private function render_stripe_tab(): void {
        $stripe_env = get_option(self::OPTION_STRIPE_ENV, 'test');
        $webhook_url = rest_url('minsponsor/v1/stripe-webhook');
        
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('minsponsor_stripe'); ?>
            
            <!-- Environment Selection -->
            <div class="minsponsor-card">
                <h2>Stripe Environment</h2>
                
                <?php if ($stripe_env === 'live'): ?>
                <div class="minsponsor-warning">
                    <strong>⚠️ Warning:</strong> Live mode is enabled. All payments are real!
                </div>
                <?php endif; ?>
                
                <div class="minsponsor-field">
                    <label for="<?php echo self::OPTION_STRIPE_ENV; ?>">Select environment</label>
                    <select id="<?php echo self::OPTION_STRIPE_ENV; ?>" 
                            name="<?php echo self::OPTION_STRIPE_ENV; ?>">
                        <option value="test" <?php selected($stripe_env, 'test'); ?>>
                            Test (test payments)
                        </option>
                        <option value="live" <?php selected($stripe_env, 'live'); ?>>
                            Live (real payments)
                        </option>
                    </select>
                    <p class="description">
                        Use test environment during development. Switch to live only when ready for production.
                    </p>
                </div>
            </div>
            
            <!-- Test Keys -->
            <div class="minsponsor-card">
                <h2>Test API Keys</h2>
                <p class="description" style="margin-bottom: 16px;">
                    Find your test keys at <a href="https://dashboard.stripe.com/test/apikeys" target="_blank">Stripe Dashboard → Test → API keys</a>
                </p>
                
                <div class="minsponsor-field">
                    <label for="<?php echo self::OPTION_STRIPE_TEST_PUBLISHABLE; ?>">Publishable Key (test)</label>
                    <input type="text" 
                           id="<?php echo self::OPTION_STRIPE_TEST_PUBLISHABLE; ?>"
                           name="<?php echo self::OPTION_STRIPE_TEST_PUBLISHABLE; ?>"
                           value="<?php echo esc_attr(get_option(self::OPTION_STRIPE_TEST_PUBLISHABLE)); ?>"
                           placeholder="pk_test_...">
                </div>
                
                <div class="minsponsor-field">
                    <label for="<?php echo self::OPTION_STRIPE_TEST_SECRET; ?>">Secret Key (test)</label>
                    <input type="password" 
                           id="<?php echo self::OPTION_STRIPE_TEST_SECRET; ?>"
                           name="<?php echo self::OPTION_STRIPE_TEST_SECRET; ?>"
                           value="<?php echo esc_attr(get_option(self::OPTION_STRIPE_TEST_SECRET)); ?>"
                           placeholder="sk_test_...">
                </div>
            </div>
            
            <!-- Live Keys -->
            <div class="minsponsor-card">
                <h2>Live API Keys</h2>
                <p class="description" style="margin-bottom: 16px;">
                    Find your live keys at <a href="https://dashboard.stripe.com/apikeys" target="_blank">Stripe Dashboard → API keys</a>
                </p>
                
                <div class="minsponsor-field">
                    <label for="<?php echo self::OPTION_STRIPE_LIVE_PUBLISHABLE; ?>">Publishable Key (live)</label>
                    <input type="text" 
                           id="<?php echo self::OPTION_STRIPE_LIVE_PUBLISHABLE; ?>"
                           name="<?php echo self::OPTION_STRIPE_LIVE_PUBLISHABLE; ?>"
                           value="<?php echo esc_attr(get_option(self::OPTION_STRIPE_LIVE_PUBLISHABLE)); ?>"
                           placeholder="pk_live_...">
                </div>
                
                <div class="minsponsor-field">
                    <label for="<?php echo self::OPTION_STRIPE_LIVE_SECRET; ?>">Secret Key (live)</label>
                    <input type="password" 
                           id="<?php echo self::OPTION_STRIPE_LIVE_SECRET; ?>"
                           name="<?php echo self::OPTION_STRIPE_LIVE_SECRET; ?>"
                           value="<?php echo esc_attr(get_option(self::OPTION_STRIPE_LIVE_SECRET)); ?>"
                           placeholder="sk_live_...">
                </div>
            </div>
            
            <!-- Webhook -->
            <div class="minsponsor-card">
                <h2>Webhook</h2>
                
                <div class="minsponsor-field">
                    <label>Webhook URL</label>
                    <div class="minsponsor-webhook-url">
                        <code id="webhook-url"><?php echo esc_html($webhook_url); ?></code>
                        <button type="button" class="button" onclick="copyWebhookUrl()">Copy</button>
                    </div>
                    <p class="description">
                        Add this URL in <a href="https://dashboard.stripe.com/webhooks" target="_blank">Stripe Dashboard → Webhooks</a>
                    </p>
                </div>
                
                <div class="minsponsor-field">
                    <label for="<?php echo self::OPTION_STRIPE_WEBHOOK_SECRET; ?>">Webhook Signing Secret</label>
                    <input type="password" 
                           id="<?php echo self::OPTION_STRIPE_WEBHOOK_SECRET; ?>"
                           name="<?php echo self::OPTION_STRIPE_WEBHOOK_SECRET; ?>"
                           value="<?php echo esc_attr(get_option(self::OPTION_STRIPE_WEBHOOK_SECRET)); ?>"
                           placeholder="whsec_...">
                    <p class="description">
                        Obtained from Stripe Dashboard after the webhook is created.
                    </p>
                </div>
            </div>
            
            <!-- Local Development -->
            <div class="minsponsor-card">
                <h2>Local Development</h2>
                
                <div class="minsponsor-field">
                    <label for="minsponsor_stripe_callback_base">Callback Base URL (optional)</label>
                    <input type="url" 
                           id="minsponsor_stripe_callback_base"
                           name="minsponsor_stripe_callback_base"
                           value="<?php echo esc_attr(get_option('minsponsor_stripe_callback_base')); ?>"
                           placeholder="https://abc123.ngrok.io"
                           style="width: 100%;">
                    <p class="description">
                        For local testing: Use <a href="https://ngrok.com/" target="_blank">ngrok</a> to expose localhost. 
                        Run <code>ngrok http 8888</code> and paste the https URL here.
                        Leave empty in production.
                    </p>
                </div>
            </div>
            
            <p>
                <input type="submit" class="button minsponsor-submit-btn" value="Save Changes">
            </p>
        </form>
        
        <script>
        function copyWebhookUrl() {
            var url = document.getElementById('webhook-url').textContent;
            navigator.clipboard.writeText(url).then(function() {
                alert('Webhook URL copied!');
            });
        }
        </script>
        <?php
    }
    
    /**
     * Get current Stripe API keys based on environment
     * 
     * @return array{secret: string, publishable: string}
     */
    public static function get_active_stripe_keys(): array {
        $env = get_option(self::OPTION_STRIPE_ENV, 'test');
        
        if ($env === 'live') {
            return [
                'secret' => get_option(self::OPTION_STRIPE_LIVE_SECRET, ''),
                'publishable' => get_option(self::OPTION_STRIPE_LIVE_PUBLISHABLE, ''),
            ];
        }
        
        return [
            'secret' => get_option(self::OPTION_STRIPE_TEST_SECRET, ''),
            'publishable' => get_option(self::OPTION_STRIPE_TEST_PUBLISHABLE, ''),
        ];
    }
    
    /**
     * Get platform fee percentage
     * 
     * @return int Fee percentage (0-50)
     */
    public static function get_platform_fee_percent(): int {
        return (int) get_option(self::OPTION_PLATFORM_FEE, self::DEFAULT_PLATFORM_FEE);
    }
    
    /**
     * Check if we're in live mode
     * 
     * @return bool
     */
    public static function is_live_mode(): bool {
        return get_option(self::OPTION_STRIPE_ENV, 'test') === 'live';
    }
    
    /**
     * Get webhook secret
     * 
     * @return string
     */
    public static function get_webhook_secret(): string {
        return get_option(self::OPTION_STRIPE_WEBHOOK_SECRET, '');
    }
    
    /**
     * Get active secret key (based on environment)
     * 
     * @return string The secret key for the current environment
     */
    public static function get_secret_key(): string {
        if (self::is_live_mode()) {
            return get_option(self::OPTION_STRIPE_LIVE_SECRET, '');
        }
        return get_option(self::OPTION_STRIPE_TEST_SECRET, '');
    }
}
