<?php
/**
 * QR Code Service for MinSponsor
 * 
 * Simple PHP QR Code generator without external dependencies
 *
 * @package MinSponsor
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MinSponsor_QrService {
    
    /**
     * QR Error Correction Levels
     */
    const ERROR_CORRECTION_L = 1; // Low (7%)
    const ERROR_CORRECTION_M = 2; // Medium (15%)
    const ERROR_CORRECTION_Q = 3; // Quartile (25%)
    const ERROR_CORRECTION_H = 4; // High (30%)
    
    /**
     * Generate QR code PNG for given URL
     *
     * @param string $url URL to encode
     * @param int $size Image size (default 1024px)
     * @param int $error_correction Error correction level
     * @return string|false PNG binary data or false on failure
     */
    public static function generate_qr_png($url, $size = 1024, $error_correction = self::ERROR_CORRECTION_H) {
        try {
            // Use Google Charts API as fallback for now (simple and reliable)
            // In production, you might want to use a local library like phpqrcode
            $api_url = sprintf(
                'https://chart.googleapis.com/chart?chs=%dx%d&cht=qr&chl=%s&choe=UTF-8&chld=%s|4',
                $size,
                $size,
                urlencode($url),
                self::get_error_correction_param($error_correction)
            );
            
            $response = wp_remote_get($api_url, array(
                'timeout' => 30,
                'user-agent' => 'MinSponsor-QR/1.0'
            ));
            
            if (is_wp_error($response)) {
                error_log('MinSponsor QR: Failed to generate QR code: ' . $response->get_error_message());
                return false;
            }
            
            $body = wp_remote_retrieve_body($response);
            $response_code = wp_remote_retrieve_response_code($response);
            
            if ($response_code !== 200 || empty($body)) {
                error_log('MinSponsor QR: Invalid response from QR API');
                return false;
            }
            
            return $body;
            
        } catch (Exception $e) {
            error_log('MinSponsor QR: Exception generating QR code: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save QR code as attachment in Media Library
     *
     * @param string $url URL to encode
     * @param string $filename Filename without extension
     * @param int $post_id Post ID to attach to (optional)
     * @param int $size Image size
     * @return int|false Attachment ID or false on failure
     */
    public static function save_qr_attachment($url, $filename, $post_id = 0, $size = 1024) {
        $png_data = self::generate_qr_png($url, $size);
        
        if (!$png_data) {
            return false;
        }
        
        // Ensure filename is safe
        $filename = sanitize_file_name($filename);
        if (!str_ends_with($filename, '.png')) {
            $filename .= '.png';
        }
        
        // Upload directory
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;
        $file_url = $upload_dir['url'] . '/' . $filename;
        
        // Write file
        $result = file_put_contents($file_path, $png_data);
        if (!$result) {
            error_log('MinSponsor QR: Failed to write QR file to ' . $file_path);
            return false;
        }
        
        // Prepare attachment data
        $attachment = array(
            'guid' => $file_url,
            'post_mime_type' => 'image/png',
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        // Insert attachment
        $attachment_id = wp_insert_attachment($attachment, $file_path, $post_id);
        
        if (is_wp_error($attachment_id) || !$attachment_id) {
            error_log('MinSponsor QR: Failed to insert attachment');
            return false;
        }
        
        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        return $attachment_id;
    }
    
    /**
     * Generate and save QR codes for a player
     *
     * @param int $player_id Player post ID
     * @param bool $force_regenerate Force regeneration of existing QR codes
     * @return array Array with 'once' and 'month' attachment IDs, or false values
     */
    public static function generate_player_qr_codes($player_id, $force_regenerate = false) {
        if (!$player_id || get_post_type($player_id) !== 'spiller') {
            return array('once' => false, 'month' => false);
        }
        
        $player_slug = get_post_field('post_name', $player_id);
        if (!$player_slug) {
            return array('once' => false, 'month' => false);
        }
        
        // Get links service
        $links_service = new MinSponsor_PlayerLinksService();
        $links = $links_service->get_player_links($player_id);
        
        if (!$links || !isset($links['once']) || !isset($links['month'])) {
            return array('once' => false, 'month' => false);
        }
        
        $results = array();
        
        // Generate QR for one-time link
        $once_filename = "qr-spiller-{$player_slug}-once";
        $existing_once = self::get_existing_qr_attachment($player_id, $once_filename);
        
        if ($force_regenerate && $existing_once) {
            wp_delete_attachment($existing_once, true);
            $existing_once = false;
        }
        
        if (!$existing_once) {
            $results['once'] = self::save_qr_attachment($links['once'], $once_filename, $player_id);
            if ($results['once']) {
                update_post_meta($player_id, '_minsponsor_qr_once_id', $results['once']);
            }
        } else {
            $results['once'] = $existing_once;
        }
        
        // Generate QR for monthly link
        $month_filename = "qr-spiller-{$player_slug}-month";
        $existing_month = self::get_existing_qr_attachment($player_id, $month_filename);
        
        if ($force_regenerate && $existing_month) {
            wp_delete_attachment($existing_month, true);
            $existing_month = false;
        }
        
        if (!$existing_month) {
            $results['month'] = self::save_qr_attachment($links['month'], $month_filename, $player_id);
            if ($results['month']) {
                update_post_meta($player_id, '_minsponsor_qr_month_id', $results['month']);
            }
        } else {
            $results['month'] = $existing_month;
        }
        
        return $results;
    }
    
    /**
     * Get existing QR attachment for player
     *
     * @param int $player_id Player post ID
     * @param string $filename_pattern Filename pattern to search for
     * @return int|false Attachment ID or false if not found
     */
    private static function get_existing_qr_attachment($player_id, $filename_pattern) {
        $attachments = get_attached_media('image/png', $player_id);
        
        foreach ($attachments as $attachment) {
            $filename = basename(get_attached_file($attachment->ID));
            if (strpos($filename, $filename_pattern) !== false) {
                return $attachment->ID;
            }
        }
        
        return false;
    }
    
    /**
     * Get error correction parameter for Google Charts API
     *
     * @param int $level Error correction level constant
     * @return string API parameter
     */
    private static function get_error_correction_param($level) {
        switch ($level) {
            case self::ERROR_CORRECTION_L:
                return 'L';
            case self::ERROR_CORRECTION_M:
                return 'M';
            case self::ERROR_CORRECTION_Q:
                return 'Q';
            case self::ERROR_CORRECTION_H:
            default:
                return 'H';
        }
    }
    
    /**
     * Get QR code URLs for a player
     *
     * @param int $player_id Player post ID
     * @return array Array with 'once' and 'month' URLs, or false values
     */
    public static function get_player_qr_urls($player_id) {
        $once_id = get_post_meta($player_id, '_minsponsor_qr_once_id', true);
        $month_id = get_post_meta($player_id, '_minsponsor_qr_month_id', true);
        
        return array(
            'once' => $once_id ? wp_get_attachment_url($once_id) : false,
            'month' => $month_id ? wp_get_attachment_url($month_id) : false
        );
    }
    
    /**
     * Delete QR codes for a player
     *
     * @param int $player_id Player post ID
     * @return bool Success
     */
    public static function delete_player_qr_codes($player_id) {
        $once_id = get_post_meta($player_id, '_minsponsor_qr_once_id', true);
        $month_id = get_post_meta($player_id, '_minsponsor_qr_month_id', true);
        
        $success = true;
        
        if ($once_id) {
            $result = wp_delete_attachment($once_id, true);
            if (!$result) {
                $success = false;
            } else {
                delete_post_meta($player_id, '_minsponsor_qr_once_id');
            }
        }
        
        if ($month_id) {
            $result = wp_delete_attachment($month_id, true);
            if (!$result) {
                $success = false;
            } else {
                delete_post_meta($player_id, '_minsponsor_qr_month_id');
            }
        }
        
        return $success;
    }
}
