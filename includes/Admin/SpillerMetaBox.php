<?php
/**
 * Spiller Meta Box for MinSponsor
 * 
 * Admin meta box for player sponsorship links
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Admin;

use MinSponsor\Services\PlayerLinksService;

if (!defined('ABSPATH')) {
    exit;
}

class SpillerMetaBox {
    
    private PlayerLinksService $links_service;
    
    public function __construct(?PlayerLinksService $links_service = null) {
        $this->links_service = $links_service ?? new PlayerLinksService();
    }
    
    /**
     * Initialize hooks
     */
    public function init(): void {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_spiller', [$this, 'save_player_data']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes(): void {
        add_meta_box(
            'minsponsor-player-links',
            'MinSponsor - Spillerstøtte',
            [$this, 'render_meta_box'],
            'spiller',
            'side',
            'high'
        );
    }
    
    /**
     * Render the meta box
     */
    public function render_meta_box(\WP_Post $post): void {
        if (!current_user_can('edit_posts')) {
            echo '<p>Du har ikke tilgang til denne funksjonen.</p>';
            return;
        }
        
        wp_nonce_field('minsponsor_player_meta', 'minsponsor_player_nonce');
        
        $default_amount = $this->links_service->get_player_default_amount($post->ID);
        $links = $this->links_service->get_player_links_with_defaults($post->ID);
        
        ?>
        <div class="minsponsor-player-meta">
            
            <!-- Default Amount Setting -->
            <div class="minsponsor-field">
                <label for="minsponsor_default_amount"><strong>Standardbeløp (valgfritt):</strong></label>
                <select id="minsponsor_default_amount" name="minsponsor_default_amount">
                    <option value="">Ikke satt</option>
                    <option value="50" <?php selected($default_amount, 50); ?>>50 kr</option>
                    <option value="100" <?php selected($default_amount, 100); ?>>100 kr</option>
                    <option value="200" <?php selected($default_amount, 200); ?>>200 kr</option>
                    <option value="300" <?php selected($default_amount, 300); ?>>300 kr</option>
                </select>
                <p class="description">Standardbeløp som forhåndsutfylles i lenkene nedenfor.</p>
            </div>
            
            <?php if ($links): ?>
            
            <!-- One-time Link -->
            <div class="minsponsor-field">
                <label><strong>Engangslink:</strong></label>
                <div class="minsponsor-link-field">
                    <input type="text" class="minsponsor-link-input" value="<?php echo esc_attr($links['once']); ?>" readonly>
                    <button type="button" class="button minsponsor-copy-button" data-url="<?php echo esc_attr($links['once']); ?>">Kopiér</button>
                </div>
            </div>
            
            <!-- Monthly Link -->
            <div class="minsponsor-field">
                <label><strong>Månedlig link:</strong></label>
                <div class="minsponsor-link-field">
                    <input type="text" class="minsponsor-link-input" value="<?php echo esc_attr($links['month']); ?>" readonly>
                    <button type="button" class="button minsponsor-copy-button" data-url="<?php echo esc_attr($links['month']); ?>">Kopiér</button>
                </div>
            </div>
            
            <?php else: ?>
            
            <div class="notice notice-warning inline">
                <p><strong>Obs:</strong> Kan ikke generere lenker. Kontroller at spilleren er koblet til et lag, og at laget er koblet til en klubb.</p>
            </div>
            
            <?php endif; ?>
            
        </div>
        
        <style>
        .minsponsor-player-meta .minsponsor-field {
            margin-bottom: 20px;
        }
        
        .minsponsor-link-field {
            display: flex;
            gap: 10px;
            margin-top: 5px;
        }
        
        .minsponsor-link-input {
            flex: 1;
            font-family: monospace;
            font-size: 11px;
            padding: 4px 6px;
        }
        </style>
        <?php
    }
    
    /**
     * Save player data
     */
    public function save_player_data(int $post_id): void {
        if (!isset($_POST['minsponsor_player_nonce']) || 
            !wp_verify_nonce($_POST['minsponsor_player_nonce'], 'minsponsor_player_meta')) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save default amount
        if (isset($_POST['minsponsor_default_amount'])) {
            $amount = absint($_POST['minsponsor_default_amount']);
            if ($amount === 0) {
                delete_post_meta($post_id, 'minsponsor_default_amount');
            } else {
                update_post_meta($post_id, 'minsponsor_default_amount', $amount);
            }
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts(string $hook): void {
        global $post_type;
        
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        if ($post_type !== 'spiller') {
            return;
        }
        
        // Inline script for copy functionality
        wp_add_inline_script('jquery-core', $this->get_inline_script());
    }
    
    /**
     * Get inline script for copy functionality
     */
    private function get_inline_script(): string {
        return <<<'JS'
jQuery(document).ready(function($) {
    $('.minsponsor-copy-button').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var url = button.data('url');
        var originalText = button.text();
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function() {
                button.text('Kopiert!').prop('disabled', true);
                setTimeout(function() {
                    button.text(originalText).prop('disabled', false);
                }, 2000);
            });
        } else {
            var textArea = $('<textarea>').val(url).css({position: 'fixed', left: '-999999px'}).appendTo('body');
            textArea[0].select();
            document.execCommand('copy');
            textArea.remove();
            button.text('Kopiert!').prop('disabled', true);
            setTimeout(function() {
                button.text(originalText).prop('disabled', false);
            }, 2000);
        }
    });
});
JS;
    }
}
