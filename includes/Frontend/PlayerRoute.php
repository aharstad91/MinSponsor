<?php
/**
 * Player Route Handler for MinSponsor
 * 
 * Handles template_redirect for player pages with sponsorship parameters
 *
 * @package MinSponsor
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MinSponsor_PlayerRoute {
    
    private $links_service;
    
    public function __construct() {
        $this->links_service = new MinSponsor_PlayerLinksService();
    }
    
    /**
     * Initialize hooks
     */
    public function init() {
        add_action('template_redirect', array($this, 'handle_player_sponsorship_request'), 5);
    }
    
    /**
     * Handle sponsorship requests on player pages
     */
    public function handle_player_sponsorship_request() {
        // Only process spiller pages
        if (!is_singular('spiller')) {
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
        if (!$post || $post->post_type !== 'spiller') {
            return;
        }
        
        // Validate and sanitize parameters
        $params = $this->links_service->validate_link_params($_GET);
        
        // Get player products
        $one_time_product_id = $this->get_player_product_id('one_time');
        $monthly_product_id = $this->get_player_product_id('monthly');
        
        if (!$one_time_product_id || !$monthly_product_id) {
            $this->show_admin_notice_and_return('Produkter for spillerstøtte er ikke konfigurert. Kontakt administrator.');
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
        WC()->cart->empty_cart();
        
        // Get cart item data
        $cart_item_data = $this->links_service->get_cart_item_data($post->ID, $params);
        
        // Add product to cart
        $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);
        
        if (!$cart_item_key) {
            $this->show_admin_notice_and_return('Kunne ikke legge produktet i handlekurven. Prøv igjen.');
            return;
        }
        
        // Log successful add to cart
        error_log(sprintf(
            'MinSponsor: Added player sponsorship to cart - Player: %s (%d), Product: %d, Interval: %s, Amount: %s',
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
     * Get player product ID from settings or SKU fallback
     *
     * @param string $type 'one_time' or 'monthly'
     * @return int|false Product ID or false if not found
     */
    private function get_player_product_id($type) {
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
    private function show_admin_notice_and_return($message) {
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
    public function display_error_notices() {
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
     * Add JavaScript for QR code handling on player pages
     */
    public function add_qr_scripts() {
        if (!is_singular('spiller')) {
            return;
        }
        
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle QR code copy link functionality
            const copyButtons = document.querySelectorAll('.minsponsor-copy-link');
            copyButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = this.getAttribute('data-url');
                    
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(url).then(function() {
                            showCopyFeedback(button, 'Lenke kopiert!');
                        }).catch(function() {
                            fallbackCopyText(url, button);
                        });
                    } else {
                        fallbackCopyText(url, button);
                    }
                });
            });
            
            function fallbackCopyText(text, button) {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    document.execCommand('copy');
                    showCopyFeedback(button, 'Lenke kopiert!');
                } catch (err) {
                    showCopyFeedback(button, 'Kunne ikke kopiere');
                }
                
                document.body.removeChild(textArea);
            }
            
            function showCopyFeedback(button, message) {
                const originalText = button.textContent;
                button.textContent = message;
                button.disabled = true;
                
                setTimeout(function() {
                    button.textContent = originalText;
                    button.disabled = false;
                }, 2000);
            }
        });
        </script>
        <style>
        .minsponsor-link-field {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            align-items: center;
        }
        
        .minsponsor-link-input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f9f9f9;
            font-family: monospace;
            font-size: 12px;
        }
        
        .minsponsor-copy-button {
            padding: 8px 12px;
            background: #0073aa;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .minsponsor-copy-button:hover {
            background: #005a87;
        }
        
        .minsponsor-copy-button:disabled {
            background: #666;
            cursor: not-allowed;
        }
        
        .minsponsor-qr-preview {
            margin: 10px 0;
            text-align: center;
        }
        
        .minsponsor-qr-image {
            max-width: 200px;
            height: auto;
            border: 1px solid #ddd;
            margin-bottom: 10px;
        }
        
        .minsponsor-qr-download {
            display: inline-block;
            padding: 6px 12px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .minsponsor-qr-download:hover {
            background: #218838;
            color: white;
        }
        </style>
        <?php
    }
}
