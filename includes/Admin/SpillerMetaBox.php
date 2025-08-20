<?php
/**
 * Spiller Meta Box for MinSponsor
 * 
 * Admin meta box for player sponsorship links and QR codes
 *
 * @package MinSponsor
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MinSponsor_SpillerMetaBox {
    
    private $links_service;
    
    public function __construct() {
        $this->links_service = new MinSponsor_PlayerLinksService();
    }
    
    /**
     * Initialize hooks
     */
    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_spiller', array($this, 'save_player_data'));
        add_action('wp_ajax_minsponsor_regenerate_qr', array($this, 'ajax_regenerate_qr'));
        add_action('wp_ajax_minsponsor_download_qr', array($this, 'ajax_download_qr'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'minsponsor-player-links',
            'MinSponsor - Spillerstøtte',
            array($this, 'render_meta_box'),
            'spiller',
            'side',
            'high'
        );
    }
    
    /**
     * Render the meta box
     */
    public function render_meta_box($post) {
        if (!current_user_can('edit_posts')) {
            echo '<p>Du har ikke tilgang til denne funksjonen.</p>';
            return;
        }
        
        wp_nonce_field('minsponsor_player_meta', 'minsponsor_player_nonce');
        
        $default_amount = $this->links_service->get_player_default_amount($post->ID);
        $links = $this->links_service->get_player_links_with_defaults($post->ID);
        $qr_urls = MinSponsor_QrService::get_player_qr_urls($post->ID);
        
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
            
            <!-- QR Codes -->
            <div class="minsponsor-qr-section">
                <h4>QR-koder:</h4>
                
                <div class="minsponsor-qr-item">
                    <strong>Engang:</strong>
                    <div class="minsponsor-qr-preview">
                        <?php if ($qr_urls['once']): ?>
                            <img src="<?php echo esc_url($qr_urls['once']); ?>" alt="QR-kode for engangslink" class="minsponsor-qr-image">
                            <br>
                            <a href="<?php echo esc_url($qr_urls['once']); ?>" download class="button button-small">Last ned PNG</a>
                        <?php else: ?>
                            <p><em>QR-kode ikke generert ennå.</em></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="minsponsor-qr-item">
                    <strong>Måned:</strong>
                    <div class="minsponsor-qr-preview">
                        <?php if ($qr_urls['month']): ?>
                            <img src="<?php echo esc_url($qr_urls['month']); ?>" alt="QR-kode for månedlig link" class="minsponsor-qr-image">
                            <br>
                            <a href="<?php echo esc_url($qr_urls['month']); ?>" download class="button button-small">Last ned PNG</a>
                        <?php else: ?>
                            <p><em>QR-kode ikke generert ennå.</em></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="minsponsor-qr-actions">
                    <button type="button" id="minsponsor-regenerate-qr" class="button" data-player-id="<?php echo esc_attr($post->ID); ?>">
                        Regenerer QR-koder
                    </button>
                    <span class="spinner" id="minsponsor-qr-spinner"></span>
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
        
        .minsponsor-qr-section {
            border-top: 1px solid #ddd;
            padding-top: 15px;
            margin-top: 20px;
        }
        
        .minsponsor-qr-item {
            margin-bottom: 20px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .minsponsor-qr-preview {
            margin-top: 10px;
            text-align: center;
        }
        
        .minsponsor-qr-image {
            max-width: 150px;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .minsponsor-qr-actions {
            text-align: center;
            margin-top: 15px;
        }
        
        #minsponsor-qr-spinner {
            float: none;
            margin-left: 10px;
        }
        </style>
        <?php
    }
    
    /**
     * Save player data
     */
    public function save_player_data($post_id) {
        if (!isset($_POST['minsponsor_player_nonce']) || 
            !wp_verify_nonce($_POST['minsponsor_player_nonce'], 'minsponsor_player_meta')) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save default amount
        if (isset($_POST['minsponsor_default_amount'])) {
            $amount = $_POST['minsponsor_default_amount'];
            if (empty($amount) || !is_numeric($amount)) {
                delete_post_meta($post_id, 'minsponsor_default_amount');
            } else {
                update_post_meta($post_id, 'minsponsor_default_amount', (int) $amount);
            }
        }
        
        // Generate QR codes if this is a new post or if hierarchical data changed
        $this->generate_qr_codes_if_needed($post_id);
    }
    
    /**
     * Generate QR codes if needed
     */
    private function generate_qr_codes_if_needed($post_id) {
        // Check if we have existing QR codes
        $existing_qr = MinSponsor_QrService::get_player_qr_urls($post_id);
        
        if (!$existing_qr['once'] || !$existing_qr['month']) {
            // Generate missing QR codes
            MinSponsor_QrService::generate_player_qr_codes($post_id, false);
        }
    }
    
    /**
     * Ajax handler for regenerating QR codes
     */
    public function ajax_regenerate_qr() {
        check_ajax_referer('minsponsor_ajax', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions');
        }
        
        $player_id = intval($_POST['player_id']);
        if (!$player_id || get_post_type($player_id) !== 'spiller') {
            wp_die('Invalid player ID');
        }
        
        // Regenerate QR codes
        $result = MinSponsor_QrService::generate_player_qr_codes($player_id, true);
        
        if ($result['once'] && $result['month']) {
            $qr_urls = MinSponsor_QrService::get_player_qr_urls($player_id);
            wp_send_json_success(array(
                'message' => 'QR-koder regenerert!',
                'qr_urls' => $qr_urls
            ));
        } else {
            wp_send_json_error('Kunne ikke regenerere QR-koder. Sjekk at spilleren har gyldig klubb/lag-tilknytning.');
        }
    }
    
    /**
     * Ajax handler for downloading QR codes
     */
    public function ajax_download_qr() {
        check_ajax_referer('minsponsor_ajax', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions');
        }
        
        $attachment_id = intval($_POST['attachment_id']);
        if (!$attachment_id) {
            wp_die('Invalid attachment ID');
        }
        
        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            wp_die('File not found');
        }
        
        $filename = basename($file_path);
        
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        
        readfile($file_path);
        exit;
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        if ($post_type !== 'spiller') {
            return;
        }
        
        wp_enqueue_script(
            'minsponsor-admin',
            get_template_directory_uri() . '/includes/Admin/admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('minsponsor-admin', 'minsponsor_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('minsponsor_ajax')
        ));
    }
}
