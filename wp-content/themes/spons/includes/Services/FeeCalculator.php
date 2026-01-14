<?php
/**
 * Fee Calculator Service for MinSponsor
 * 
 * Calculates fees and totals for sponsorship payments according to
 * the MinSponsor fee model where fees are added ON TOP of the sponsor amount.
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Services;

use MinSponsor\Settings\StripeSettings;

if (!defined('ABSPATH')) {
    exit;
}

class FeeCalculator {
    
    /**
     * Stripe fee percentage (Norwegian market)
     */
    public const STRIPE_FEE_PERCENT = 0.029; // 2.9%
    
    /**
     * Stripe fixed fee in NOK (Norwegian market)
     */
    public const STRIPE_FIXED_FEE = 1.80; // kr
    
    /**
     * Default platform fee percentage
     */
    public const DEFAULT_PLATFORM_FEE_PERCENT = 6;
    
    /**
     * Calculate total amount to charge (sponsor pays)
     * 
     * Formula: T = (A + P + b) / (1 - a)
     * Where:
     *   A = sponsor amount (what recipient gets)
     *   P = platform fee
     *   a = Stripe percentage (0.029)
     *   b = Stripe fixed fee (1.80)
     *
     * @param float $sponsorAmount The amount the recipient should receive
     * @return array{total: float, sponsor_amount: float, platform_fee: float, stripe_fee_estimate: float}
     */
    public static function calculate(float $sponsorAmount): array {
        $platformFeePercent = StripeSettings::get_platform_fee_percent();
        $platformFee = $sponsorAmount * ($platformFeePercent / 100);
        
        // Calculate total using the reverse fee formula
        // T = (A + P + b) / (1 - a)
        $a = self::STRIPE_FEE_PERCENT;
        $b = self::STRIPE_FIXED_FEE;
        
        $numerator = $sponsorAmount + $platformFee + $b;
        $total = $numerator / (1 - $a);
        
        // Round to 2 decimal places
        $total = round($total, 2);
        
        // Calculate actual Stripe fee (for reference)
        $stripeFeeEstimate = ($total * $a) + $b;
        
        return [
            'total' => $total,
            'sponsor_amount' => $sponsorAmount,
            'platform_fee' => round($platformFee, 2),
            'stripe_fee_estimate' => round($stripeFeeEstimate, 2),
        ];
    }
    
    /**
     * Calculate application fee amount (what platform keeps)
     * 
     * This is the amount that stays with the platform after the transfer.
     * It equals: Total - Sponsor Amount = Platform Fee + Stripe Fee
     *
     * @param float $total Total amount charged
     * @param float $sponsorAmount Amount to transfer to recipient
     * @return int Application fee in øre (cents)
     */
    public static function calculateApplicationFee(float $total, float $sponsorAmount): int {
        $fee = $total - $sponsorAmount;
        return (int) round($fee * 100); // Convert to øre
    }
    
    /**
     * Convert NOK amount to øre (Stripe uses smallest currency unit)
     *
     * @param float $amount Amount in NOK
     * @return int Amount in øre
     */
    public static function toOre(float $amount): int {
        return (int) round($amount * 100);
    }
    
    /**
     * Convert øre to NOK
     *
     * @param int $ore Amount in øre
     * @return float Amount in NOK
     */
    public static function fromOre(int $ore): float {
        return $ore / 100;
    }
    
    /**
     * Get breakdown for display in checkout
     *
     * @param float $sponsorAmount The sponsor amount
     * @return array{lines: array, total: float}
     */
    public static function getBreakdown(float $sponsorAmount): array {
        $calc = self::calculate($sponsorAmount);
        $feeAmount = $calc['total'] - $calc['sponsor_amount'];
        
        return [
            'lines' => [
                [
                    'label' => __('Sponsorship amount', 'minsponsor'),
                    'amount' => $calc['sponsor_amount'],
                ],
                [
                    'label' => __('Processing fee', 'minsponsor'),
                    'amount' => round($feeAmount, 2),
                ],
            ],
            'total' => $calc['total'],
        ];
    }
    
    /**
     * Validate sponsor amount is within allowed range
     *
     * @param float $amount Sponsor amount in NOK
     * @return bool
     */
    public static function isValidAmount(float $amount): bool {
        // Minimum 10 NOK, maximum 10000 NOK
        return $amount >= 10 && $amount <= 10000;
    }
    
    /**
     * Get fixed amount options
     *
     * @return array<int> Available fixed amounts
     */
    public static function getFixedAmounts(): array {
        return [50, 100, 200, 300];
    }
}
