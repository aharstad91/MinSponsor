<?php
/**
 * Template for single klubb (club)
 * 
 * Design: MinSponsor Designsystem
 * - Varm korall (#F6A586) som hovedfarge
 * - Terrakotta (#D97757) for CTAs
 * - Beige bakgrunn (#F5EFE6)
 */
get_header();

$klubb_id = get_the_ID();
$klubb_name = get_the_title();
$klubb_slug = get_post_field('post_name', $klubb_id);

// Get all lag (teams) for this klubb
$lag_query = new WP_Query([
    'post_type' => 'lag',
    'posts_per_page' => -1,
    'meta_key' => 'parent_klubb',
    'meta_value' => $klubb_id,
    'orderby' => 'title',
    'order' => 'ASC',
]);

// Support amounts
$amounts = [50, 100, 200, 300];
$base_url = home_url('/stott/' . $klubb_slug . '/');
?>

<main class="min-h-screen" style="background-color: var(--color-beige);">
    <div class="max-w-3xl mx-auto px-4 py-12">
        
        <!-- Club Card -->
        <div class="card-lg overflow-hidden" style="background-color: var(--color-krem); border-radius: var(--radius-lg); box-shadow: var(--shadow-warm);">
            
            <!-- Club Header -->
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
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                <?php endif; ?>
                
                <h1 class="text-3xl md:text-4xl font-bold mb-3" style="color: var(--color-krem);">
                    <?php echo esc_html($klubb_name); ?>
                </h1>
            </div>
            
            <!-- Club Content -->
            <div class="p-8 md:p-12">
                <?php if (get_the_content()): ?>
                    <div class="prose max-w-none mb-10" style="color: var(--color-brun-light);">
                        <?php the_content(); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Support Section -->
                <div class="pt-8" style="border-top: 1px solid var(--color-softgra);">
                    <h2 class="text-2xl font-semibold text-center mb-4" style="color: var(--color-brun);">
                        Støtt <?php echo esc_html($klubb_name); ?>
                    </h2>
                    
                    <p class="text-center mb-10" style="color: var(--color-brun-light);">
                        Vis din støtte med et bidrag. 100% av beløpet går til klubben.
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
                <span>100% til klubben</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-terrakotta);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Avslutt når som helst</span>
            </div>
        </div>
        
        <!-- Teams List -->
        <?php if ($lag_query->have_posts()): ?>
            <div class="mt-12 p-8" style="background-color: var(--color-krem); border-radius: var(--radius-lg); box-shadow: var(--shadow-warm);">
                <h2 class="text-2xl font-bold mb-6" style="color: var(--color-brun);">Våre lag</h2>
                
                <div class="grid gap-4 md:grid-cols-2">
                    <?php while ($lag_query->have_posts()): $lag_query->the_post(); ?>
                        <a href="<?php the_permalink(); ?>" 
                           class="block p-4 rounded-xl transition-all hover:scale-[1.02]"
                           style="background-color: var(--color-beige); border: 1px solid var(--color-softgra);">
                            <div class="flex items-center gap-4">
                                <?php if (has_post_thumbnail()): ?>
                                    <?php the_post_thumbnail('thumbnail', [
                                        'class' => 'w-16 h-16 object-cover rounded-lg',
                                        'style' => 'border: 2px solid var(--color-softgra);'
                                    ]); ?>
                                <?php else: ?>
                                    <div class="w-16 h-16 rounded-lg flex items-center justify-center" style="background-color: var(--color-korall); opacity: 0.8;">
                                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24" style="color: var(--color-krem);">
                                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h3 class="font-semibold" style="color: var(--color-brun);"><?php the_title(); ?></h3>
                                    <?php 
                                    $terms = get_the_terms(get_the_ID(), 'idrettsgren');
                                    if ($terms && !is_wp_error($terms)): ?>
                                        <span class="text-sm" style="color: var(--color-brun-light);"><?php echo esc_html($terms[0]->name); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php wp_reset_postdata(); ?>
        <?php endif; ?>
        
    </div>
</main>

<?php get_footer(); ?>
