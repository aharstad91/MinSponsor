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

// Support amounts
$amounts = [50, 100, 200, 300];
$base_url = home_url('/stott/' . $klubb_slug . '/' . $lag_slug . '/' . $spiller_slug . '/');
$share_url = get_permalink($spiller_id);
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
            <?php if ($lag): ?>
                <span class="mx-2 opacity-50">›</span>
                <a href="<?php echo get_permalink($lag_id); ?>" class="hover:underline" style="color: var(--color-terrakotta);">
                    <?php echo esc_html($lag_name); ?>
                </a>
            <?php endif; ?>
            <span class="mx-2 opacity-50">›</span>
            <span><?php echo esc_html($spiller_name); ?></span>
        </nav>
        
        <!-- Player Card -->
        <div class="card-lg overflow-hidden" style="background-color: var(--color-krem); border-radius: var(--radius-lg); box-shadow: var(--shadow-warm);">
            
            <!-- Player Header -->
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
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </div>
                <?php endif; ?>
                
                <h1 class="text-3xl md:text-4xl font-bold mb-3" style="color: var(--color-krem);">
                    <?php echo esc_html($spiller_name); ?>
                </h1>
                
                <?php if ($lag): ?>
                    <p class="text-lg opacity-90" style="color: var(--color-krem);">
                        <?php echo esc_html($lag_name); ?>
                        <?php if ($klubb): ?>
                            <span class="mx-2">•</span>
                            <?php echo esc_html($klubb_name); ?>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Player Content -->
            <div class="p-8 md:p-12">
                <?php if (get_the_content()): ?>
                    <div class="prose max-w-none mb-10" style="color: var(--color-brun-light);">
                        <?php the_content(); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Support Section -->
                <div class="pt-8" style="border-top: 1px solid var(--color-softgra);">
                    <h2 class="text-2xl font-semibold text-center mb-4" style="color: var(--color-brun);">
                        Støtt <?php echo esc_html($spiller_name); ?>
                    </h2>
                    
                    <p class="text-center mb-6" style="color: var(--color-brun-light);">
                        Spilleren mottar hele støttebeløpet du velger.
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
                <span>Full støtte til spilleren</span>
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
                    onclick="shareUrl('<?php echo esc_js($share_url); ?>', '<?php echo esc_js($spiller_name); ?>')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-medium transition-all hover:scale-105"
                    style="background-color: var(--color-krem); color: var(--color-brun); border: 1px solid var(--color-softgra);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
                Del denne siden
            </button>
            <p id="share-feedback" class="text-sm mt-2 opacity-0 transition-opacity" style="color: var(--color-terrakotta);"></p>
        </div>

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
