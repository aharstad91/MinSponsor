<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-gray-50 min-h-screen'); ?>>
<?php wp_body_open(); ?>

<header class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-4">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="text-xl font-bold text-gray-900 no-underline">
                <?php bloginfo('name'); ?>
            </a>
            <nav class="hidden md:flex space-x-6">
                <?php 
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'container' => false,
                    'menu_class' => 'flex space-x-6',
                    'fallback_cb' => false,
                    'depth' => 1,
                ]); 
                ?>
            </nav>
        </div>
    </div>
</header>
