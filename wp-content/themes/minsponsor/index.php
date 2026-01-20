<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php wp_title('|', true, 'right'); ?><?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <div class="min-h-screen bg-gray-50">
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <h1 class="text-2xl font-bold text-gray-900">
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="text-gray-900 no-underline">
                            <?php bloginfo('name'); ?>
                        </a>
                    </h1>
                    <nav class="hidden md:flex space-x-8">
                        <?php 
                        wp_nav_menu(array(
                            'theme_location' => 'primary',
                            'container' => false,
                            'menu_class' => 'flex space-x-8',
                            'fallback_cb' => false,
                        )); 
                        ?>
                    </nav>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php if (have_posts()) : ?>
                <div class="space-y-8">
                    <?php while (have_posts()) : the_post(); ?>
                        <article class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">
                                <a href="<?php the_permalink(); ?>" class="text-gray-900 no-underline hover:text-blue-600">
                                    <?php the_title(); ?>
                                </a>
                            </h2>
                            <div class="text-gray-600 mb-4">
                                <?php echo get_the_date(); ?> av <?php the_author(); ?>
                            </div>
                            <div class="prose max-w-none">
                                <?php the_excerpt(); ?>
                            </div>
                            <a href="<?php the_permalink(); ?>" class="inline-block mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors">
                                Les mer
                            </a>
                        </article>
                    <?php endwhile; ?>
                </div>
                
                <div class="mt-8">
                    <?php the_posts_pagination(array(
                        'prev_text' => '← Forrige',
                        'next_text' => 'Neste →',
                        'class' => 'flex justify-center space-x-4'
                    )); ?>
                </div>
            <?php else : ?>
                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Ingen innlegg funnet</h2>
                    <p class="text-gray-600">Det ser ut til at det ikke er noe innhold her ennå.</p>
                </div>
            <?php endif; ?>
        </main>

        <footer class="bg-gray-800 text-white mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="text-center">
                    <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. Alle rettigheter reservert.</p>
                </div>
            </div>
        </footer>
    </div>
    <?php wp_footer(); ?>
</body>
</html>
