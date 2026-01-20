<?php
/**
 * Template for pages
 * 
 * This template uses the_content() to properly render 
 * WooCommerce checkout, cart, and other block-based pages.
 */
get_header();
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">
                <?php the_title(); ?>
            </h1>
            <div class="prose max-w-none woocommerce">
                <?php the_content(); ?>
            </div>
        </article>
    <?php endwhile; endif; ?>
</main>

<?php get_footer(); ?>
