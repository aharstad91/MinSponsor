<?php
/**
 * Template: Stripe Not Connected Error Page
 * 
 * Displayed when a user tries to sponsor a recipient whose team
 * has not completed Stripe Connect onboarding.
 *
 * @package MinSponsor
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get error data passed via set_query_var
$error = get_query_var('minsponsor_stripe_error', []);
$recipient_type = $error['recipient_type'] ?? 'spiller';
$recipient_name = $error['recipient_name'] ?? '';
$recipient_url = $error['recipient_url'] ?? home_url();
$lag_name = $error['lag_name'] ?? '';
$status = $error['status'] ?? 'not_started';

get_header();
?>

<style>
.minsponsor-error-page {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-beige, #F5EFE6);
    padding: 40px 20px;
}

.minsponsor-error-card {
    background: var(--color-krem, #FBF8F3);
    border-radius: var(--radius-lg, 24px);
    padding: 48px;
    max-width: 560px;
    text-align: center;
    box-shadow: var(--shadow-warm, 0 4px 20px rgba(61, 50, 40, 0.08));
}

.minsponsor-error-icon {
    font-size: 72px;
    margin-bottom: 24px;
    line-height: 1;
}

.minsponsor-error-title {
    color: var(--color-brun, #3D3228);
    font-size: 28px;
    font-weight: 600;
    margin: 0 0 16px 0;
    font-family: Inter, -apple-system, sans-serif;
}

.minsponsor-error-message {
    color: #666;
    font-size: 16px;
    line-height: 1.7;
    margin: 0 0 32px 0;
}

.minsponsor-error-message strong {
    color: var(--color-brun, #3D3228);
}

.minsponsor-error-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: #fff3cd;
    color: #856404;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 32px;
}

.minsponsor-error-status.pending {
    background: #fff3cd;
    color: #856404;
}

.minsponsor-error-status.not-started {
    background: #f0f0f1;
    color: #50575e;
}

.minsponsor-error-btn {
    display: inline-block;
    background: var(--color-terrakotta, #D97757);
    color: var(--color-krem, #FBF8F3);
    padding: 14px 32px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 16px;
    transition: background 0.2s ease;
}

.minsponsor-error-btn:hover {
    background: var(--color-terrakotta-dark, #B85D42);
    color: var(--color-krem, #FBF8F3);
}

.minsponsor-error-hint {
    margin-top: 24px;
    color: #999;
    font-size: 13px;
}
</style>

<main class="minsponsor-error-page">
    <div class="minsponsor-error-card">
        
        <div class="minsponsor-error-icon">
            <?php if ($status === 'pending'): ?>
                ⏳
            <?php elseif ($status === 'no_lag_found'): ?>
                🔍
            <?php else: ?>
                😢
            <?php endif; ?>
        </div>
        
        <h1 class="minsponsor-error-title">
            <?php if ($status === 'pending'): ?>
                Snart klar!
            <?php elseif ($status === 'no_lag_found'): ?>
                Mangler tilknytning
            <?php else: ?>
                Kan ikke motta støtte ennå
            <?php endif; ?>
        </h1>
        
        <p class="minsponsor-error-message">
            <?php if ($status === 'no_lag_found'): ?>
                <?php if ($recipient_type === 'spiller'): ?>
                    <strong><?php echo esc_html($recipient_name); ?></strong> 
                    er ikke tilknyttet et lag som kan motta betalinger.
                <?php else: ?>
                    Denne mottakeren er ikke satt opp til å motta betalinger.
                <?php endif; ?>
            <?php elseif ($status === 'pending'): ?>
                <strong><?php echo esc_html($lag_name); ?></strong> 
                holder på å sette opp betalingsmottak. 
                Registreringen er påbegynt, men ikke fullført ennå.
            <?php else: ?>
                <strong><?php echo esc_html($lag_name); ?></strong> 
                har ikke satt opp betalingsmottak ennå. 
                Kontakt laget hvis du ønsker å støtte dem.
            <?php endif; ?>
        </p>
        
        <?php if ($lag_name && $status !== 'no_lag_found'): ?>
        <div class="minsponsor-error-status <?php echo esc_attr($status === 'pending' ? 'pending' : 'not-started'); ?>">
            <?php if ($status === 'pending'): ?>
                <span>🔄</span> Venter på at kasserer fullfører registrering
            <?php else: ?>
                <span>⚠️</span> Betalingsmottak ikke aktivert
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <a href="<?php echo esc_url($recipient_url); ?>" class="minsponsor-error-btn">
            ← Tilbake til <?php 
                if ($recipient_type === 'spiller') {
                    echo 'utøver';
                } elseif ($recipient_type === 'lag') {
                    echo 'lag';
                } else {
                    echo 'klubb';
                }
            ?>
        </a>
        
        <p class="minsponsor-error-hint">
            Har du spørsmål? Kontakt laget eller klubben direkte.
        </p>
        
    </div>
</main>

<?php
get_footer();
