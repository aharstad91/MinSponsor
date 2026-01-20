<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('min-h-screen'); ?> style="background-color: var(--color-beige);">
<?php wp_body_open(); ?>

<header class="px-6 py-6 max-w-7xl mx-auto flex justify-between items-center" style="background-color: var(--color-beige);">
    <a href="<?php echo esc_url(home_url('/')); ?>" class="font-bold text-3xl md:text-4xl tracking-tight no-underline" style="color: var(--color-brun);">
        MinSponsor
    </a>
    <nav class="hidden md:flex gap-8 font-medium text-lg">
        <a href="<?php echo home_url('/stott/'); ?>" class="hover:opacity-70 transition-opacity no-underline" style="color: var(--color-terrakotta);">Finn lag</a>
        <a href="<?php echo home_url('/om-oss/'); ?>" class="hover:opacity-70 transition-opacity no-underline" style="color: var(--color-brun);">Om oss</a>
        <a href="<?php echo home_url('/faq/'); ?>" class="hover:opacity-70 transition-opacity no-underline" style="color: var(--color-brun);">FAQ</a>
        <a href="<?php echo home_url('/#kontakt'); ?>" class="hover:opacity-70 transition-opacity no-underline" style="color: var(--color-brun);">Kontakt</a>
    </nav>
</header>
