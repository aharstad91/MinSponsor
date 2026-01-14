<?php
/**
 * Template Name: Alle Lag og Klubber
 * 
 * Oversiktsside for alle klubber, lag og utøvere
 */

get_header();
?>

<style>
    .entity-list-section {
        background-color: var(--color-beige);
        padding: 60px 24px 100px;
    }
    
    .entity-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
    }
    
    .entity-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-radius: 16px;
        padding: 24px;
        text-decoration: none;
        transition: all 0.2s ease;
        display: block;
    }
    
    .entity-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(61, 50, 40, 0.15);
    }
    
    .entity-card:focus {
        outline: 3px solid var(--color-terrakotta);
        outline-offset: 4px;
    }
    
    .entity-card-icon {
        width: 48px;
        height: 48px;
        background: var(--color-korall);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
    }
    
    .entity-card-icon svg {
        width: 24px;
        height: 24px;
        color: var(--color-brun);
    }
    
    .entity-card-name {
        font-size: 20px;
        font-weight: 600;
        color: var(--color-brun);
        margin-bottom: 4px;
    }
    
    .entity-card-sublabel {
        font-size: 14px;
        color: var(--color-brun);
        opacity: 0.7;
    }
    
    .entity-type-badge {
        display: inline-block;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        background: rgba(217, 119, 87, 0.15);
        color: var(--color-terrakotta);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 12px;
    }
    
    .section-divider {
        margin: 48px 0 32px;
        text-align: center;
    }
    
    .section-divider h2 {
        font-size: 28px;
        font-weight: 600;
        color: var(--color-brun);
        display: inline-block;
        padding: 0 24px;
        background: var(--color-beige);
        position: relative;
    }
    
    .section-divider::before {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        top: 50%;
        height: 1px;
        background: rgba(61, 50, 40, 0.15);
    }
    
    .hero-mini {
        background-color: var(--color-beige);
        padding: 60px 24px 40px;
        text-align: center;
    }
    
    .hero-mini h1 {
        font-size: 40px;
        font-weight: 700;
        color: var(--color-brun);
        margin-bottom: 16px;
    }
    
    .hero-mini p {
        font-size: 18px;
        color: var(--color-brun);
        opacity: 0.8;
        max-width: 500px;
        margin: 0 auto 32px;
    }
    
    /* Search in hero */
    .inline-search {
        position: relative;
        max-width: 400px;
        margin: 0 auto;
    }
    
    .inline-search input {
        width: 100%;
        padding: 14px 20px 14px 48px;
        font-size: 16px;
        border: 2px solid transparent;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.9);
        color: var(--color-brun);
        transition: all 0.2s ease;
    }
    
    .inline-search input:focus {
        outline: none;
        border-color: var(--color-terrakotta);
    }
    
    .inline-search svg {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        color: var(--color-brun);
        opacity: 0.5;
    }
</style>

<div class="hero-mini">
    <h1>Våre lag og klubber</h1>
    <p>Finn klubben, laget eller utøveren du vil støtte</p>
    
    <div class="inline-search">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text" id="filter-search" placeholder="Filtrer listen...">
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
            <div class="section-divider" style="position: relative;">
                <h2>Klubber</h2>
            </div>
            
            <div class="entity-grid" data-type="klubb">
                <?php foreach ($klubber as $klubb) : ?>
                    <a href="<?php echo home_url('/stott/' . $klubb->post_name . '/'); ?>" 
                       class="entity-card" 
                       data-name="<?php echo esc_attr(strtolower($klubb->post_title)); ?>">
                        <div class="entity-card-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div class="entity-card-name"><?php echo esc_html($klubb->post_title); ?></div>
                        <div class="entity-card-sublabel">
                            <?php 
                            $lag_count = count(get_posts([
                                'post_type' => 'lag',
                                'meta_key' => 'parent_klubb',
                                'meta_value' => $klubb->ID,
                                'posts_per_page' => -1
                            ]));
                            echo $lag_count . ' ' . ($lag_count === 1 ? 'lag' : 'lag');
                            ?>
                        </div>
                        <span class="entity-type-badge">Klubb</span>
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
            <div class="section-divider" style="position: relative; margin-top: 60px;">
                <h2>Lag</h2>
            </div>
            
            <div class="entity-grid" data-type="lag">
                <?php foreach ($lag as $team) : 
                    $parent_klubb_id = get_post_meta($team->ID, 'parent_klubb', true);
                    $parent_klubb = $parent_klubb_id ? get_post($parent_klubb_id) : null;
                    $klubb_slug = $parent_klubb ? $parent_klubb->post_name : '';
                ?>
                    <a href="<?php echo home_url('/stott/' . ($klubb_slug ? $klubb_slug . '/' : '') . $team->post_name . '/'); ?>" 
                       class="entity-card"
                       data-name="<?php echo esc_attr(strtolower($team->post_title . ' ' . ($parent_klubb ? $parent_klubb->post_title : ''))); ?>">
                        <div class="entity-card-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div class="entity-card-name"><?php echo esc_html($team->post_title); ?></div>
                        <div class="entity-card-sublabel">
                            <?php echo $parent_klubb ? esc_html($parent_klubb->post_title) : 'Uavhengig lag'; ?>
                        </div>
                        <span class="entity-type-badge">Lag</span>
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
            <div class="section-divider" style="position: relative; margin-top: 60px;">
                <h2>Utøvere</h2>
            </div>
            
            <div class="entity-grid" data-type="spiller">
                <?php foreach ($spillere as $spiller) : 
                    $parent_lag_id = get_post_meta($spiller->ID, 'parent_lag', true);
                    $parent_lag = $parent_lag_id ? get_post($parent_lag_id) : null;
                    
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
                       class="entity-card"
                       data-name="<?php echo esc_attr(strtolower($spiller->post_title . ' ' . ($parent_lag ? $parent_lag->post_title : ''))); ?>">
                        <div class="entity-card-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div class="entity-card-name"><?php echo esc_html($spiller->post_title); ?></div>
                        <div class="entity-card-sublabel">
                            <?php echo $parent_lag ? esc_html($parent_lag->post_title) : 'Uavhengig utøver'; ?>
                        </div>
                        <span class="entity-type-badge">Utøver</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($klubber) && empty($lag) && empty($spillere)) : ?>
            <div style="text-align: center; padding: 60px 20px;">
                <p style="font-size: 18px; color: var(--color-brun); opacity: 0.7;">
                    Ingen klubber, lag eller utøvere er registrert ennå.
                </p>
                <a href="<?php echo home_url('/#kontakt'); ?>" 
                   style="display: inline-block; margin-top: 20px; color: var(--color-terrakotta); font-weight: 600;">
                    Kontakt oss for å registrere din klubb →
                </a>
            </div>
        <?php endif; ?>
        
    </div>
</section>

<script>
// Simple filter functionality
(function() {
    const filterInput = document.getElementById('filter-search');
    if (!filterInput) return;
    
    filterInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.entity-card');
        
        cards.forEach(card => {
            const name = card.dataset.name || '';
            if (query === '' || name.includes(query)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
})();
</script>

<?php get_footer(); ?>
