<?php
/**
 * Template Name: Alle Lag og Klubber
 *
 * Oversiktsside for alle klubber, lag og utøvere
 */

get_header();

/**
 * Format player name for privacy (first name + last initial)
 * E.g., "Adrian Gunnarsen" -> "Adrian G."
 */
function minsponsor_format_private_name($full_name) {
    $parts = explode(' ', trim($full_name));
    if (count($parts) < 2) {
        return $full_name;
    }
    $first_name = $parts[0];
    $last_initial = mb_substr(end($parts), 0, 1);
    return $first_name . ' ' . $last_initial . '.';
}

/**
 * Get supporter count for a team or player
 * Returns the number of completed orders
 */
function minsponsor_get_supporter_count($entity_type, $entity_id) {
    global $wpdb;

    $meta_key = ($entity_type === 'lag') ? '_ms_team_id' : '_ms_player_id';

    $count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT p.ID)
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type IN ('shop_order', 'shop_order_placehold')
        AND p.post_status IN ('wc-completed', 'wc-processing')
        AND pm.meta_key = %s
        AND pm.meta_value = %d
    ", $meta_key, $entity_id));

    return (int) $count;
}
?>

<style>
    .entity-list-section {
        background-color: var(--color-beige);
        padding: 40px 24px 100px;
    }

    .entity-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 20px;
    }

    /* Club cards - featured with logo */
    .entity-card {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-radius: 20px;
        padding: 24px;
        text-decoration: none;
        transition: all 0.25s ease;
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden;
    }

    .entity-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 40px rgba(61, 50, 40, 0.12);
    }

    .entity-card:focus {
        outline: 3px solid var(--color-terrakotta);
        outline-offset: 4px;
    }

    /* Club cards - larger with logo prominence */
    .entity-card--klubb {
        padding: 28px;
    }

    .entity-card--klubb .entity-card-header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 12px;
    }

    .entity-card-logo {
        width: 64px;
        height: 64px;
        border-radius: 12px;
        object-fit: contain;
        background: white;
        padding: 4px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        flex-shrink: 0;
    }

    .entity-card-logo-fallback {
        width: 64px;
        height: 64px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--color-korall) 0%, var(--color-terrakotta) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .entity-card-logo-fallback svg {
        width: 32px;
        height: 32px;
        color: white;
        opacity: 0.9;
    }

    /* Team cards - medium size with icon */
    .entity-card--lag {
        padding: 22px;
    }

    .entity-card--lag .entity-card-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 10px;
    }

    .entity-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .entity-card--lag .entity-card-icon {
        background: linear-gradient(135deg, rgba(217, 119, 87, 0.15) 0%, rgba(217, 119, 87, 0.25) 100%);
    }

    .entity-card-icon svg {
        width: 24px;
        height: 24px;
        color: var(--color-terrakotta);
    }

    /* Player cards - compact */
    .entity-card--spiller {
        padding: 18px 20px;
        flex-direction: row;
        align-items: center;
        gap: 14px;
    }

    .entity-card--spiller .entity-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(61, 50, 40, 0.08) 0%, rgba(61, 50, 40, 0.12) 100%);
    }

    .entity-card--spiller .entity-card-icon svg {
        width: 20px;
        height: 20px;
        color: var(--color-brun);
        opacity: 0.7;
    }

    .entity-card--spiller .entity-card-content {
        flex: 1;
        min-width: 0;
    }

    .entity-card-name {
        font-size: 18px;
        font-weight: 600;
        color: var(--color-brun);
        margin-bottom: 2px;
        line-height: 1.3;
    }

    .entity-card--klubb .entity-card-name {
        font-size: 20px;
    }

    .entity-card--spiller .entity-card-name {
        font-size: 16px;
    }

    .entity-card-sublabel {
        font-size: 14px;
        color: var(--color-brun);
        opacity: 0.65;
        line-height: 1.4;
    }

    .entity-card--spiller .entity-card-sublabel {
        font-size: 13px;
    }

    /* Activity badge */
    .entity-card-activity {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        color: var(--color-brun);
        opacity: 0.6;
        margin-top: 10px;
    }

    .entity-card--spiller .entity-card-activity {
        margin-top: 0;
        margin-left: auto;
        flex-shrink: 0;
    }

    .entity-card-activity svg {
        width: 14px;
        height: 14px;
    }

    .entity-card-activity--active {
        color: #16a34a;
        opacity: 1;
        font-weight: 500;
    }

    /* Section styling */
    .section-header {
        margin: 48px 0 24px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .section-header:first-child {
        margin-top: 0;
    }

    .section-header h2 {
        font-size: 24px;
        font-weight: 600;
        color: var(--color-brun);
        margin: 0;
    }

    .section-header-line {
        flex: 1;
        height: 1px;
        background: rgba(61, 50, 40, 0.12);
    }

    .section-header-count {
        font-size: 14px;
        color: var(--color-brun);
        opacity: 0.5;
        font-weight: 400;
    }

    .hero-mini {
        background-color: var(--color-beige);
        padding: 60px 24px 32px;
        text-align: center;
    }

    .hero-mini h1 {
        font-size: 36px;
        font-weight: 700;
        color: var(--color-brun);
        margin-bottom: 12px;
    }

    .hero-mini p {
        font-size: 17px;
        color: var(--color-brun);
        opacity: 0.75;
        max-width: 460px;
        margin: 0 auto 28px;
    }

    /* Search in hero */
    .inline-search {
        position: relative;
        max-width: 380px;
        margin: 0 auto;
    }

    .inline-search input {
        width: 100%;
        padding: 14px 20px 14px 48px;
        font-size: 16px;
        border: 2px solid rgba(61, 50, 40, 0.08);
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.95);
        color: var(--color-brun);
        transition: all 0.2s ease;
    }

    .inline-search input::placeholder {
        color: var(--color-brun);
        opacity: 0.4;
    }

    .inline-search input:focus {
        outline: none;
        border-color: var(--color-terrakotta);
        box-shadow: 0 4px 20px rgba(217, 119, 87, 0.15);
    }

    .inline-search svg {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        color: var(--color-brun);
        opacity: 0.4;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 80px 20px;
    }

    .empty-state p {
        font-size: 17px;
        color: var(--color-brun);
        opacity: 0.6;
    }

    .empty-state a {
        display: inline-block;
        margin-top: 16px;
        color: var(--color-terrakotta);
        font-weight: 600;
        text-decoration: none;
    }

    .empty-state a:hover {
        text-decoration: underline;
    }

    /* Responsive */
    @media (max-width: 640px) {
        .hero-mini h1 {
            font-size: 28px;
        }

        .entity-grid {
            grid-template-columns: 1fr;
        }

        .entity-card--klubb .entity-card-header {
            gap: 12px;
        }

        .entity-card-logo,
        .entity-card-logo-fallback {
            width: 52px;
            height: 52px;
        }
    }
</style>

<div class="hero-mini">
    <h1>Våre lag og klubber</h1>
    <p>Finn klubben, laget eller utøveren du vil støtte</p>

    <div class="inline-search">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text" id="filter-search" placeholder="Søk etter klubb, lag eller utøver...">
    </div>
</div>

<section class="entity-list-section">
    <div class="max-w-6xl mx-auto">

        <?php
        // Get all clubs
        $klubber = get_posts([
            'post_type'      => 'klubb',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish'
        ]);

        if (!empty($klubber)) : ?>
            <div class="section-header">
                <h2>Klubber</h2>
                <span class="section-header-count"><?php echo count($klubber); ?></span>
                <div class="section-header-line"></div>
            </div>

            <div class="entity-grid" data-type="klubb">
                <?php foreach ($klubber as $klubb) :
                    $logo = get_field('klubb_logo', $klubb->ID);
                    $klubb_farge = get_field('klubb_farge', $klubb->ID);

                    // Count teams in this club
                    $lag_count = count(get_posts([
                        'post_type' => 'lag',
                        'meta_key' => 'parent_klubb',
                        'meta_value' => $klubb->ID,
                        'posts_per_page' => -1,
                        'fields' => 'ids'
                    ]));
                ?>
                    <a href="<?php echo home_url('/stott/' . $klubb->post_name . '/'); ?>"
                       class="entity-card entity-card--klubb"
                       data-name="<?php echo esc_attr(strtolower($klubb->post_title)); ?>">
                        <div class="entity-card-header">
                            <?php if ($logo && !empty($logo['url'])) : ?>
                                <img src="<?php echo esc_url($logo['sizes']['medium'] ?? $logo['url']); ?>"
                                     alt="<?php echo esc_attr($klubb->post_title); ?> logo"
                                     class="entity-card-logo"
                                     loading="lazy">
                            <?php else : ?>
                                <div class="entity-card-logo-fallback"<?php echo $klubb_farge ? ' style="background: ' . esc_attr($klubb_farge) . ';"' : ''; ?>>
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <div>
                                <div class="entity-card-name"><?php echo esc_html($klubb->post_title); ?></div>
                                <div class="entity-card-sublabel">
                                    <?php echo $lag_count; ?> <?php echo $lag_count === 1 ? 'lag' : 'lag'; ?>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php
        // Get all teams
        $lag = get_posts([
            'post_type'      => 'lag',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish'
        ]);

        if (!empty($lag)) : ?>
            <div class="section-header">
                <h2>Lag</h2>
                <span class="section-header-count"><?php echo count($lag); ?></span>
                <div class="section-header-line"></div>
            </div>

            <div class="entity-grid" data-type="lag">
                <?php foreach ($lag as $team) :
                    $parent_klubb_id = get_post_meta($team->ID, 'parent_klubb', true);
                    $parent_klubb = $parent_klubb_id ? get_post($parent_klubb_id) : null;
                    $klubb_slug = $parent_klubb ? $parent_klubb->post_name : '';
                    $supporter_count = minsponsor_get_supporter_count('lag', $team->ID);
                ?>
                    <a href="<?php echo home_url('/stott/' . ($klubb_slug ? $klubb_slug . '/' : '') . $team->post_name . '/'); ?>"
                       class="entity-card entity-card--lag"
                       data-name="<?php echo esc_attr(strtolower($team->post_title . ' ' . ($parent_klubb ? $parent_klubb->post_title : ''))); ?>">
                        <div class="entity-card-header">
                            <div class="entity-card-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="entity-card-name"><?php echo esc_html($team->post_title); ?></div>
                                <div class="entity-card-sublabel">
                                    <?php echo $parent_klubb ? esc_html($parent_klubb->post_title) : 'Uavhengig lag'; ?>
                                </div>
                            </div>
                        </div>
                        <?php if ($supporter_count > 0) : ?>
                            <div class="entity-card-activity entity-card-activity--active">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <?php echo $supporter_count; ?> støttespiller<?php echo $supporter_count !== 1 ? 'e' : ''; ?>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php
        // Get all players
        $spillere = get_posts([
            'post_type'      => 'spiller',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish'
        ]);

        if (!empty($spillere)) : ?>
            <div class="section-header">
                <h2>Utøvere</h2>
                <span class="section-header-count"><?php echo count($spillere); ?></span>
                <div class="section-header-line"></div>
            </div>

            <div class="entity-grid" data-type="spiller">
                <?php foreach ($spillere as $spiller) :
                    $parent_lag_id = get_post_meta($spiller->ID, 'parent_lag', true);
                    $parent_lag = $parent_lag_id ? get_post($parent_lag_id) : null;
                    $supporter_count = minsponsor_get_supporter_count('spiller', $spiller->ID);

                    // Privacy: show "Adrian G." instead of full name
                    $display_name = minsponsor_format_private_name($spiller->post_title);

                    // Build URL
                    $url_path = '';
                    if ($parent_lag) {
                        $parent_klubb_id = get_post_meta($parent_lag->ID, 'parent_klubb', true);
                        $parent_klubb = $parent_klubb_id ? get_post($parent_klubb_id) : null;
                        if ($parent_klubb) {
                            $url_path = $parent_klubb->post_name . '/' . $parent_lag->post_name . '/' . $spiller->post_name . '/';
                        } else {
                            $url_path = $parent_lag->post_name . '/' . $spiller->post_name . '/';
                        }
                    } else {
                        $url_path = $spiller->post_name . '/';
                    }
                ?>
                    <a href="<?php echo home_url('/stott/' . $url_path); ?>"
                       class="entity-card entity-card--spiller"
                       data-name="<?php echo esc_attr(strtolower($spiller->post_title . ' ' . ($parent_lag ? $parent_lag->post_title : ''))); ?>">
                        <div class="entity-card-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div class="entity-card-content">
                            <div class="entity-card-name"><?php echo esc_html($display_name); ?></div>
                            <div class="entity-card-sublabel">
                                <?php echo $parent_lag ? esc_html($parent_lag->post_title) : 'Uavhengig utøver'; ?>
                            </div>
                        </div>
                        <?php if ($supporter_count > 0) : ?>
                            <div class="entity-card-activity entity-card-activity--active">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <?php echo $supporter_count; ?>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($klubber) && empty($lag) && empty($spillere)) : ?>
            <div class="empty-state">
                <p>Ingen klubber, lag eller utøvere er registrert ennå.</p>
                <a href="<?php echo home_url('/#kontakt'); ?>">
                    Kontakt oss for å registrere din klubb →
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>

<script>
// Filter functionality
(function() {
    const filterInput = document.getElementById('filter-search');
    if (!filterInput) return;

    let debounceTimer;

    filterInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const query = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.entity-card');
            const sections = document.querySelectorAll('.section-header');

            cards.forEach(card => {
                const name = card.dataset.name || '';
                if (query === '' || name.includes(query)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });

            // Hide section headers if all cards in that section are hidden
            sections.forEach(header => {
                const grid = header.nextElementSibling;
                if (grid && grid.classList.contains('entity-grid')) {
                    const visibleCards = grid.querySelectorAll('.entity-card:not([style*="display: none"])');
                    header.style.display = visibleCards.length === 0 ? 'none' : '';
                    grid.style.display = visibleCards.length === 0 ? 'none' : '';
                }
            });
        }, 150);
    });
})();
</script>

<?php get_footer(); ?>
