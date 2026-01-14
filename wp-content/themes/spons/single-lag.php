<?php
/**
 * Template for single lag (team)
 *
 * Design: MinSponsor Designsystem
 * - Varm korall (#F6A586) som hovedfarge
 * - Terrakotta (#D97757) for CTAs
 * - Beige bakgrunn (#F5EFE6)
 */
get_header();

$lag_id = get_the_ID();
$lag_name = get_the_title();
$lag_slug = get_post_field('post_name', $lag_id);

// Get ACF fields
$kort_beskrivelse = get_field('kort_beskrivelse', $lag_id);
$pengebruk = get_field('pengebruk', $lag_id);
$antall_spillere = get_field('antall_spillere', $lag_id);
$hero_bilde = get_field('hero_bilde', $lag_id);

// Get parent klubb
$klubb_id = minsponsor_get_parent_klubb_id($lag_id);
$klubb = $klubb_id ? get_post($klubb_id) : null;
$klubb_slug = $klubb ? $klubb->post_name : '';
$klubb_name = $klubb ? $klubb->post_title : '';

// Get all spillere (players) for this lag
$spiller_query = new WP_Query([
    'post_type' => 'spiller',
    'posts_per_page' => -1,
    'meta_key' => 'parent_lag',
    'meta_value' => $lag_id,
    'orderby' => 'title',
    'order' => 'ASC',
]);

// Support amounts
$amounts = [50, 100, 200, 300];
$base_url = home_url('/stott/' . $klubb_slug . '/' . $lag_slug . '/');
$share_url = get_permalink($lag_id);
?>

<main class="min-h-screen" style="background-color: var(--color-beige);">
    <div class="max-w-3xl mx-auto px-4 py-12">
    
        <!-- Breadcrumb -->
        <nav class="mb-8 text-sm" style="color: var(--color-brun-light);">
            <a href="<?php echo home_url('/stott/'); ?>" class="hover:underline" style="color: var(--color-terrakotta);">
                MinSponsor
            </a>
            <?php if ($klubb): ?>
                <span class="mx-2 opacity-50">›</span>
                <a href="<?php echo get_permalink($klubb_id); ?>" class="hover:underline" style="color: var(--color-terrakotta);">
                    <?php echo esc_html($klubb_name); ?>
                </a>
            <?php endif; ?>
            <span class="mx-2 opacity-50">›</span>
            <span><?php echo esc_html($lag_name); ?></span>
        </nav>
        
        <!-- Team Card -->
        <div class="card-lg overflow-hidden" style="background-color: var(--color-krem); border-radius: var(--radius-lg); box-shadow: var(--shadow-warm);">

            <!-- Team Header -->
            <?php if ($hero_bilde): ?>
                <!-- Hero with team photo -->
                <div class="relative text-center py-16 px-8" style="min-height: 280px;">
                    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo esc_url($hero_bilde['url']); ?>');"></div>
                    <div class="absolute inset-0" style="background: linear-gradient(to top, rgba(61, 50, 40, 0.9) 0%, rgba(61, 50, 40, 0.4) 100%);"></div>
                    <div class="relative z-10 flex flex-col justify-end h-full" style="min-height: 200px;">
                        <h1 class="text-3xl md:text-4xl font-bold mb-3" style="color: var(--color-krem);">
                            <?php echo esc_html($lag_name); ?>
                        </h1>

                        <?php if ($klubb): ?>
                            <p class="text-lg opacity-90" style="color: var(--color-krem);">
                                <?php echo esc_html($klubb_name); ?>
                            </p>
                        <?php endif; ?>

                        <div class="flex flex-wrap justify-center gap-3 mt-4">
                            <?php
                            $terms = get_the_terms($lag_id, 'idrettsgren');
                            if ($terms && !is_wp_error($terms)): ?>
                                <span class="inline-block px-4 py-1 rounded-full text-sm font-medium"
                                      style="background-color: rgba(255,255,255,0.2); color: var(--color-krem);">
                                    <?php echo esc_html($terms[0]->name); ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($antall_spillere): ?>
                                <span class="inline-block px-4 py-1 rounded-full text-sm font-medium"
                                      style="background-color: rgba(255,255,255,0.2); color: var(--color-krem);">
                                    <?php echo intval($antall_spillere); ?> spillere
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Standard gradient header -->
                <div class="text-center py-12 px-8" style="background: linear-gradient(135deg, var(--color-korall) 0%, var(--color-terrakotta) 100%);">
                    <?php if (has_post_thumbnail()): ?>
                        <div class="mb-6">
                            <?php the_post_thumbnail('medium', [
                                'class' => 'w-32 h-32 object-cover rounded-full mx-auto shadow-lg',
                                'style' => 'border: 4px solid var(--color-krem);'
                            ]); ?>
                        </div>
                    <?php else: ?>
                        <div class="w-32 h-32 rounded-full mx-auto mb-6 flex items-center justify-center" style="background-color: rgba(255,255,255,0.2); border: 4px solid rgba(255,255,255,0.3);">
                            <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24" style="color: var(--color-krem);">
                                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                            </svg>
                        </div>
                    <?php endif; ?>

                    <h1 class="text-3xl md:text-4xl font-bold mb-3" style="color: var(--color-krem);">
                        <?php echo esc_html($lag_name); ?>
                    </h1>

                    <?php if ($klubb): ?>
                        <p class="text-lg opacity-90" style="color: var(--color-krem);">
                            <?php echo esc_html($klubb_name); ?>
                        </p>
                    <?php endif; ?>

                    <div class="flex flex-wrap justify-center gap-3 mt-4">
                        <?php
                        $terms = get_the_terms($lag_id, 'idrettsgren');
                        if ($terms && !is_wp_error($terms)): ?>
                            <span class="inline-block px-4 py-1 rounded-full text-sm font-medium"
                                  style="background-color: rgba(255,255,255,0.2); color: var(--color-krem);">
                                <?php echo esc_html($terms[0]->name); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($antall_spillere): ?>
                            <span class="inline-block px-4 py-1 rounded-full text-sm font-medium"
                                  style="background-color: rgba(255,255,255,0.2); color: var(--color-krem);">
                                <?php echo intval($antall_spillere); ?> spillere
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Team Content -->
            <div class="p-8 md:p-12">

                <!-- Description Section -->
                <?php if ($kort_beskrivelse || $pengebruk): ?>
                    <div class="mb-10 text-center">
                        <?php if ($kort_beskrivelse): ?>
                            <p class="text-lg mb-4" style="color: var(--color-brun);">
                                <?php echo esc_html($kort_beskrivelse); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($pengebruk): ?>
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm" style="background-color: var(--color-beige); color: var(--color-brun-light);">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-terrakotta);">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Støtten går til: <?php echo esc_html($pengebruk); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (get_the_content()): ?>
                    <div class="prose max-w-none mb-10" style="color: var(--color-brun-light);">
                        <?php the_content(); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Support Section -->
                <div class="pt-8" style="border-top: 1px solid var(--color-softgra);">
                    <h2 class="text-2xl font-semibold text-center mb-4" style="color: var(--color-brun);">
                        Støtt <?php echo esc_html($lag_name); ?>
                    </h2>
                    
                    <p class="text-center mb-6" style="color: var(--color-brun-light);">
                        Laget mottar hele støttebeløpet du velger.
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
        <div class="mt-8 flex flex-wrap justify-center gap-6 text-sm" style="color: var(--color-brun-light);">
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
                <span>Full støtte til laget</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-terrakotta);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Avslutt når som helst</span>
            </div>
        </div>

        <!-- Share Section -->
        <div class="mt-8 text-center">
            <button type="button"
                    onclick="shareUrl('<?php echo esc_js($share_url); ?>', '<?php echo esc_js($lag_name); ?>')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-medium transition-all hover:scale-105"
                    style="background-color: var(--color-krem); color: var(--color-brun); border: 1px solid var(--color-softgra);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
                Del denne siden
            </button>
            <p id="share-feedback" class="text-sm mt-2 opacity-0 transition-opacity" style="color: var(--color-terrakotta);"></p>
        </div>

        <!-- Players List -->
        <?php if ($spiller_query->have_posts()): ?>
            <div class="mt-12 p-6 md:p-8" style="background-color: var(--color-krem); border-radius: var(--radius-lg); box-shadow: var(--shadow-warm);">
                <h2 class="text-xl font-bold mb-4" style="color: var(--color-brun);">Spillere</h2>
                
                <div class="divide-y" style="border-color: var(--color-softgra);">
                    <?php while ($spiller_query->have_posts()): $spiller_query->the_post(); ?>
                        <a href="<?php the_permalink(); ?>" 
                           class="flex items-center gap-3 py-3 px-2 -mx-2 rounded-lg transition-colors hover:bg-beige"
                           style="text-decoration: none;">
                            <?php if (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('thumbnail', [
                                    'class' => 'w-10 h-10 object-cover rounded-full flex-shrink-0',
                                    'style' => 'border: 2px solid var(--color-softgra);'
                                ]); ?>
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center" 
                                     style="background-color: var(--color-korall);">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" style="color: var(--color-krem);">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <span class="font-medium text-sm" style="color: var(--color-brun);"><?php the_title(); ?></span>
                            <svg style="width: 16px; height: 16px; flex-shrink: 0; margin-left: auto; opacity: 0.4;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php wp_reset_postdata(); ?>
        <?php endif; ?>
        
    </div>
</main>

<!-- Share functionality -->
<script>
function shareUrl(url, title) {
    const shareData = {
        title: 'Støtt ' + title,
        text: 'Bli med å støtte ' + title + ' på MinSponsor!',
        url: url
    };

    if (navigator.share && navigator.canShare && navigator.canShare(shareData)) {
        navigator.share(shareData).catch(() => {});
    } else {
        navigator.clipboard.writeText(url).then(() => {
            const feedback = document.getElementById('share-feedback');
            if (feedback) {
                feedback.textContent = 'Lenke kopiert!';
                feedback.classList.remove('opacity-0');
                feedback.classList.add('opacity-100');
                setTimeout(() => {
                    feedback.classList.remove('opacity-100');
                    feedback.classList.add('opacity-0');
                }, 2000);
            }
        });
    }
}
</script>

<?php get_footer(); ?>
