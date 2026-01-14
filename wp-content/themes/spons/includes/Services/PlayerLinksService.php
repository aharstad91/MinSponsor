<?php
/**
 * Player Links Service for MinSponsor
 * 
 * Generates player support links for one-time and monthly sponsorship
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Services;

if (!defined('ABSPATH')) {
    exit;
}

class PlayerLinksService {
    
    /**
     * Get player support links
     *
     * @param int $player_id Player post ID
     * @param array $params Optional parameters (amount, ref)
     * @return array|false Array with 'once' and 'month' URLs, or false on failure
     */
    public function get_player_links(int $player_id, array $params = []): array|false {
        if (!$player_id || get_post_type($player_id) !== 'spiller') {
            return false;
        }
        
        $player_data = $this->get_player_hierarchy($player_id);
        if (!$player_data) {
            return false;
        }
        
        $base_url = home_url('/stott/' . $player_data['klubb_slug'] . '/' . $player_data['lag_slug'] . '/' . $player_data['spiller_slug'] . '/');
        
        // Build query parameters
        $query_params = [];
        
        if (isset($params['amount']) && is_numeric($params['amount']) && $params['amount'] > 0) {
            $query_params['amount'] = (int) $params['amount'];
        }
        
        if (isset($params['ref']) && !empty($params['ref'])) {
            $query_params['ref'] = sanitize_text_field($params['ref']);
        }
        
        // Generate links
        $once_params = array_merge($query_params, ['interval' => 'once']);
        $month_params = array_merge($query_params, ['interval' => 'month']);
        
        return [
            'once' => add_query_arg($once_params, $base_url),
            'month' => add_query_arg($month_params, $base_url)
        ];
    }
    
    /**
     * Get player hierarchy data (klubb, lag, spiller slugs and IDs)
     *
     * @param int $player_id Player post ID
     * @return array|false Hierarchy data or false on failure
     */
    public function get_player_hierarchy(int $player_id): array|false {
        $player = get_post($player_id);
        if (!$player || $player->post_type !== 'spiller') {
            return false;
        }
        
        // Get parent lag
        $lag_id = minsponsor_get_parent_lag_id($player_id);
        if (!$lag_id) {
            return false;
        }
        
        $lag = get_post($lag_id);
        if (!$lag || $lag->post_type !== 'lag') {
            return false;
        }
        
        // Get parent klubb
        $klubb_id = minsponsor_get_parent_klubb_id($lag_id);
        if (!$klubb_id) {
            return false;
        }
        
        $klubb = get_post($klubb_id);
        if (!$klubb || $klubb->post_type !== 'klubb') {
            return false;
        }
        
        return [
            'spiller_id' => $player_id,
            'spiller_slug' => $player->post_name,
            'spiller_name' => $player->post_title,
            'lag_id' => $lag_id,
            'lag_slug' => $lag->post_name,
            'lag_name' => $lag->post_title,
            'klubb_id' => $klubb_id,
            'klubb_slug' => $klubb->post_name,
            'klubb_name' => $klubb->post_title
        ];
    }
    
    /**
     * Get default amount for a player
     *
     * @param int $player_id Player post ID
     * @return int|null Default amount or null if not set
     */
    public function get_player_default_amount(int $player_id): ?int {
        $amount = get_post_meta($player_id, 'minsponsor_default_amount', true);
        return $amount ? (int) $amount : null;
    }
    
    /**
     * Set default amount for a player
     *
     * @param int $player_id Player post ID
     * @param int $amount Default amount
     * @return bool Success
     */
    public function set_player_default_amount(int $player_id, int $amount): bool {
        if ($amount <= 0) {
            return delete_post_meta($player_id, 'minsponsor_default_amount');
        }
        
        return (bool) update_post_meta($player_id, 'minsponsor_default_amount', $amount);
    }
    
    /**
     * Get player links with default amount if set
     *
     * @param int $player_id Player post ID
     * @param array $override_params Parameters to override defaults
     * @return array|false Array with 'once' and 'month' URLs, or false on failure
     */
    public function get_player_links_with_defaults(int $player_id, array $override_params = []): array|false {
        $default_amount = $this->get_player_default_amount($player_id);
        
        $params = $override_params;
        if ($default_amount && !isset($params['amount'])) {
            $params['amount'] = $default_amount;
        }
        
        return $this->get_player_links($player_id, $params);
    }
    
    /**
     * Parse player URL and extract hierarchy data
     *
     * @param string $url Full player URL
     * @return array|false Parsed data or false on failure
     */
    public function parse_player_url(string $url): array|false {
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['path'])) {
            return false;
        }
        
        $path = trim($parsed['path'], '/');
        $parts = explode('/', $path);
        
        // Expected format: stott/{klubb}/{lag}/{spiller}
        if (count($parts) !== 4 || $parts[0] !== 'stott') {
            return false;
        }
        
        $klubb_slug = $parts[1];
        $lag_slug = $parts[2];
        $spiller_slug = $parts[3];
        
        // Find klubb
        $klubb = get_page_by_path($klubb_slug, OBJECT, 'klubb');
        if (!$klubb) {
            return false;
        }
        
        // Find lag within this klubb
        $lag_query = new \WP_Query([
            'post_type' => 'lag',
            'name' => $lag_slug,
            'meta_key' => 'parent_klubb',
            'meta_value' => $klubb->ID,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        ]);
        
        if (!$lag_query->have_posts()) {
            return false;
        }
        
        $lag = $lag_query->posts[0];
        
        // Find spiller within this lag
        $spiller_query = new \WP_Query([
            'post_type' => 'spiller',
            'name' => $spiller_slug,
            'meta_key' => 'parent_lag',
            'meta_value' => $lag->ID,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        ]);
        
        if (!$spiller_query->have_posts()) {
            return false;
        }
        
        $spiller = $spiller_query->posts[0];
        
        // Parse query parameters
        $query_params = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query_params);
        }
        
        return [
            'klubb' => $klubb,
            'lag' => $lag,
            'spiller' => $spiller,
            'params' => $query_params
        ];
    }
    
    /**
     * Validate player link parameters
     *
     * @param array $params URL parameters
     * @return array Validated and sanitized parameters
     */
    public function validate_link_params(array $params): array {
        $validated = [];
        
        // Validate interval
        if (isset($params['interval'])) {
            $interval = strtolower(sanitize_text_field($params['interval']));
            if (in_array($interval, ['once', 'month'], true)) {
                $validated['interval'] = $interval;
            }
        }
        
        // Default to month if not specified
        if (!isset($validated['interval'])) {
            $validated['interval'] = 'month';
        }
        
        // Validate amount
        if (isset($params['amount'])) {
            $amount = absint($params['amount']);
            if ($amount > 0) {
                $validated['amount'] = $amount;
            }
        }
        
        // Validate ref
        if (isset($params['ref']) && !empty($params['ref'])) {
            $validated['ref'] = sanitize_text_field($params['ref']);
        }
        
        return $validated;
    }
    
    /**
     * Get all data needed for cart item
     *
     * @param int $player_id Player post ID
     * @param array $params Validated parameters
     * @return array Cart item data
     */
    public function get_cart_item_data(int $player_id, array $params): array {
        $hierarchy = $this->get_player_hierarchy($player_id);
        if (!$hierarchy) {
            return [];
        }
        
        $data = [
            'minsponsor_club_id' => $hierarchy['klubb_id'],
            'minsponsor_club_name' => $hierarchy['klubb_name'],
            'minsponsor_club_slug' => $hierarchy['klubb_slug'],
            'minsponsor_team_id' => $hierarchy['lag_id'],
            'minsponsor_team_name' => $hierarchy['lag_name'],
            'minsponsor_team_slug' => $hierarchy['lag_slug'],
            'minsponsor_player_id' => $hierarchy['spiller_id'],
            'minsponsor_player_name' => $hierarchy['spiller_name'],
            'minsponsor_player_slug' => $hierarchy['spiller_slug'],
            'minsponsor_interval' => $params['interval'],
            'minsponsor_recipient_type' => 'spiller'
        ];
        
        if (isset($params['amount'])) {
            $data['minsponsor_amount'] = $params['amount'];
        }
        
        if (isset($params['ref'])) {
            $data['minsponsor_ref'] = $params['ref'];
        }
        
        return $data;
    }
}
