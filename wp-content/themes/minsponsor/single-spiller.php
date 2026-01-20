<?php
/**
 * Template for single spiller (player)
 *
 * Design: MinSponsor Designsystem
 * - Varm korall (#F6A586) som hovedfarge
 * - Terrakotta (#D97757) for CTAs
 * - Beige bakgrunn (#F5EFE6)
 */
get_header();

$spiller_id = get_the_ID();
$spiller_name = get_the_title();
$spiller_slug = get_post_field('post_name', $spiller_id);

// Get parent lag
$lag_id = minsponsor_get_parent_lag_id($spiller_id);
$lag = $lag_id ? get_post($lag_id) : null;
$lag_slug = $lag ? $lag->post_name : '';
$lag_name = $lag ? $lag->post_title : '';

// Get parent klubb (via lag)
$klubb_id = $lag_id ? minsponsor_get_parent_klubb_id($lag_id) : null;
$klubb = $klubb_id ? get_post($klubb_id) : null;
$klubb_slug = $klubb ? $klubb->post_name : '';
$klubb_name = $klubb ? $klubb->post_title : '';
$klubb_logo = $klubb_id ? get_field('klubb_logo', $klubb_id) : null;

// Get lag info
$lag_hero_bilde = $lag_id ? get_field('lag_hero_bilde', $lag_id) : null;
$pengene_brukes_til = $lag_id ? get_field('lag_pengene_brukes_til', $lag_id) : null;

// Get sport from lag
$terms = $lag_id ? get_the_terms($lag_id, 'idrettsgren') : null;
$sport_name = ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';

// Support amounts
$amounts = [50, 100, 200, 300];
$base_url = home_url('/stott/' . $klubb_slug . '/' . $lag_slug . '/' . $spiller_slug . '/');
$share_url = get_permalink($spiller_id);
?>

<style>
    .hero-section {
        position: relative;
        min-height: 280px;
        display: flex;
        align-items: flex-end;
        overflow: hidden;
    }

    .hero-section--with-image {
        min-height: 320px;
    }

    .hero-bg {
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, var(--color-korall) 0%, var(--color-terrakotta) 100%);
    }

    .hero-bg-image {
        position: absolute;
        inset: 0;
        background-size: cover;
        background-position: center;
    }

    .hero-bg-image::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(61, 50, 40, 0.85) 0%, rgba(61, 50, 40, 0.4) 50%, rgba(61, 50, 40, 0.2) 100%);
    }

    .hero-content {
        position: relative;
        z-index: 10;
        width: 100%;
        padding: 32px 24px;
    }

    .hero-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid white;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }

    .hero-avatar-fallback {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 4px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(8px);
    }

    .hero-avatar-fallback svg {
        width: 40px;
        height: 40px;
        color: white;
        opacity: 0.9;
    }

    .hero-club-logo {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        object-fit: contain;
        background: white;
        padding: 3px;
        position: absolute;
        bottom: -4px;
        right: -4px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .breadcrumb a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: color 0.2s;
    }

    .breadcrumb a:hover {
        color: white;
        text-decoration: underline;
    }

    .breadcrumb-separator {
        color: rgba(255, 255, 255, 0.5);
    }

    .breadcrumb-current {
        color: white;
        font-weight: 500;
    }

    .context-section {
        padding: 32px 24px;
        background: white;
        border-bottom: 1px solid rgba(61, 50, 40, 0.08);
    }

    .context-description {
        font-size: 17px;
        line-height: 1.6;
        color: var(--color-brun);
        margin-bottom: 20px;
    }

    .context-meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--color-brun);
        opacity: 0.8;
    }

    .context-meta-item svg {
        width: 18px;
        height: 18px;
        color: var(--color-terrakotta);
        flex-shrink: 0;
    }

    .money-usage {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .money-usage-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: rgba(217, 119, 87, 0.1);
        color: var(--color-terrakotta);
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
    }

    .money-usage-tag svg {
        width: 14px;
        height: 14px;
    }

    .share-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        background: var(--color-beige);
        color: var(--color-brun);
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .share-button:hover {
        background: rgba(61, 50, 40, 0.1);
    }

    .share-button svg {
        width: 18px;
        height: 18px;
    }

    .share-button--copied {
        background: #16a34a;
        color: white;
    }

    .support-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(61, 50, 40, 0.08);
    }

    .team-link-card {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px;
        background: var(--color-beige);
        border-radius: 12px;
        text-decoration: none;
        transition: all 0.2s;
    }

    .team-link-card:hover {
        background: rgba(61, 50, 40, 0.08);
    }

    .team-link-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: linear-gradient(135deg, var(--color-korall) 0%, var(--color-terrakotta) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .team-link-icon svg {
        width: 22px;
        height: 22px;
        color: white;
    }

    .team-link-content {
        flex: 1;
    }

    .team-link-name {
        font-weight: 600;
        color: var(--color-brun);
        font-size: 15px;
    }

    .team-link-sublabel {
        font-size: 13px;
        color: var(--color-brun);
        opacity: 0.6;
    }

    .team-link-arrow {
        width: 20px;
        height: 20px;
        color: var(--color-brun);
        opacity: 0.3;
        flex-shrink: 0;
    }
</style>

<main class="min-h-screen" style="background-color: var(--color-beige);">

    <!-- Hero Section -->
    <div class="hero-section<?php echo ($lag_hero_bilde || has_post_thumbnail()) ? ' hero-section--with-image' : ''; ?>">
        <?php if ($lag_hero_bilde && !empty($lag_hero_bilde['url'])) : ?>
            <div class="hero-bg-image" style="background-image: url('<?php echo esc_url($lag_hero_bilde['sizes']['large'] ?? $lag_hero_bilde['url']); ?>');"></div>
        <?php else : ?>
            <div class="hero-bg"></div>
        <?php endif; ?>

        <div class="hero-content max-w-3xl mx-auto">
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <a href="<?php echo home_url('/stott/'); ?>">MinSponsor</a>
                <span class="breadcrumb-separator">›</span>
                <?php if ($klubb) : ?>
                    <a href="<?php echo get_permalink($klubb_id); ?>"><?php echo esc_html($klubb_name); ?></a>
                    <span class="breadcrumb-separator">›</span>
                <?php endif; ?>
                <?php if ($lag) : ?>
                    <a href="<?php echo get_permalink($lag_id); ?>"><?php echo esc_html($lag_name); ?></a>
                    <span class="breadcrumb-separator">›</span>
                <?php endif; ?>
                <span class="breadcrumb-current"><?php echo esc_html($spiller_name); ?></span>
            </nav>

            <div class="flex items-end gap-4">
                <div class="relative">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('medium', ['class' => 'hero-avatar']); ?>
                    <?php else : ?>
                        <div class="hero-avatar-fallback">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                    <?php if ($klubb_logo && !empty($klubb_logo['url'])) : ?>
                        <img src="<?php echo esc_url($klubb_logo['sizes']['thumbnail'] ?? $klubb_logo['url']); ?>"
                             alt="<?php echo esc_attr($klubb_name); ?>"
                             class="hero-club-logo">
                    <?php endif; ?>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white mb-1">
                        <?php echo esc_html($spiller_name); ?>
                    </h1>
                    <?php if ($lag) : ?>
                        <p class="text-white opacity-80 text-sm">
                            <?php echo esc_html($lag_name); ?>
                            <?php if ($klubb) : ?>
                                <span class="mx-1 opacity-60">•</span>
                                <?php echo esc_html($klubb_name); ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 py-8">

        <!-- Context Section -->
        <?php if ($pengene_brukes_til || get_the_content()) : ?>
            <div class="context-section support-card mb-6">
                <?php if (get_the_content()): ?>
                    <div class="context-description">
                        <?php the_content(); ?>
                    </div>
                <?php endif; ?>

                <?php if ($pengene_brukes_til) : ?>
                    <div class="context-meta-item mb-4">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span style="font-weight: 500;">Støtten går til:</span>
                    </div>
                    <div class="money-usage">
                        <?php
                        $uses = array_map('trim', explode(',', $pengene_brukes_til));
                        foreach ($uses as $use) :
                            if (empty($use)) continue;
                        ?>
                            <span class="money-usage-tag">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <?php echo esc_html($use); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Share button -->
                <div class="mt-6 pt-5" style="border-top: 1px solid rgba(61, 50, 40, 0.08);">
                    <button type="button" class="share-button" id="share-btn" data-url="<?php echo esc_url($share_url); ?>">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        Del denne siden
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Support Card -->
        <div class="support-card mb-8">
            <div class="p-8 md:p-10">
                <!-- Support Section -->
                <div>
                    <h2 class="text-2xl font-semibold text-center mb-3" style="color: var(--color-brun);">
                        Støtt <?php echo esc_html($spiller_name); ?>
                    </h2>

                    <p class="text-center mb-6" style="color: var(--color-brun-light);">
                        <?php echo $lag ? 'Laget' : 'Spilleren'; ?> mottar hele støttebeløpet du velger.
                    </p>
                    <p class="text-center text-sm mb-10" style="color: var(--color-brun-light); opacity: 0.8;">
                        En liten plattformavgift (10%) legges på toppen av beløpet.
                    </p>

                    <!-- Monthly support (primary - show first) -->
                    <div class="mb-10">
                        <h3 class="text-lg font-medium mb-5 text-center" style="color: var(--color-brun);">
                            Månedlig støtte
                        </h3>
                        <div class="flex flex-wrap justify-center gap-3">
                            <?php foreach ($amounts as $amount): ?>
                                <a href="<?php echo esc_url(add_query_arg(['interval' => 'month', 'amount' => $amount], $base_url)); ?>"
                                   class="btn-primary text-center min-w-[120px]"
                                   style="display: inline-block; text-decoration: none;">
                                    <?php echo $amount; ?> kr/mnd
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-center text-sm mt-4" style="color: var(--color-brun-light);">
                            Du kan når som helst avslutte abonnementet
                        </p>
                    </div>

                    <!-- One-time support (secondary) -->
                    <div>
                        <h3 class="text-lg font-medium mb-5 text-center" style="color: var(--color-brun);">
                            Engangsstøtte
                        </h3>
                        <div class="flex flex-wrap justify-center gap-3">
                            <?php foreach ($amounts as $amount): ?>
                                <a href="<?php echo esc_url(add_query_arg(['interval' => 'once', 'amount' => $amount], $base_url)); ?>"
                                   class="btn-secondary text-center min-w-[100px]"
                                   style="display: inline-block; text-decoration: none;">
                                    <?php echo $amount; ?> kr
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trust signals -->
        <div class="flex flex-wrap justify-center gap-6 text-sm mb-10" style="color: var(--color-brun-light);">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-terrakotta);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span>Sikker betaling</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-terrakotta);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                <span>Full støtte til <?php echo $lag ? 'laget' : 'spilleren'; ?></span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-terrakotta);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Avslutt når som helst</span>
            </div>
        </div>

        <!-- Link to team -->
        <?php if ($lag) : ?>
            <div class="support-card p-6">
                <p class="text-sm mb-4" style="color: var(--color-brun); opacity: 0.7;">Vil du heller støtte hele laget?</p>
                <a href="<?php echo get_permalink($lag_id); ?>" class="team-link-card">
                    <div class="team-link-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div class="team-link-content">
                        <div class="team-link-name"><?php echo esc_html($lag_name); ?></div>
                        <div class="team-link-sublabel"><?php echo esc_html($klubb_name); ?><?php echo $sport_name ? ' • ' . esc_html($sport_name) : ''; ?></div>
                    </div>
                    <svg class="team-link-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        <?php endif; ?>

    </div>
</main>

<script>
// Share functionality
(function() {
    const shareBtn = document.getElementById('share-btn');
    if (!shareBtn) return;

    shareBtn.addEventListener('click', async function() {
        const url = this.dataset.url;
        const title = document.title;

        // Try native share first (mobile)
        if (navigator.share) {
            try {
                await navigator.share({ title, url });
                return;
            } catch (e) {
                // User cancelled or error, fall through to clipboard
            }
        }

        // Fallback to clipboard
        try {
            await navigator.clipboard.writeText(url);
            const originalHTML = this.innerHTML;
            this.innerHTML = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Lenke kopiert!';
            this.classList.add('share-button--copied');

            setTimeout(() => {
                this.innerHTML = originalHTML;
                this.classList.remove('share-button--copied');
            }, 2000);
        } catch (e) {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = url;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);

            const originalHTML = this.innerHTML;
            this.innerHTML = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Lenke kopiert!';
            this.classList.add('share-button--copied');

            setTimeout(() => {
                this.innerHTML = originalHTML;
                this.classList.remove('share-button--copied');
            }, 2000);
        }
    });
})();
</script>

<?php get_footer(); ?>
