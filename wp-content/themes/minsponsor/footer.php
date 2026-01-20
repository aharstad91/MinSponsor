<footer class="mt-16" style="background-color: var(--color-brun);">
    <div class="max-w-7xl mx-auto px-6 py-12">
        <div class="grid md:grid-cols-4 gap-8 mb-8">
            <!-- Brand -->
            <div class="md:col-span-1">
                <a href="<?php echo home_url('/'); ?>" class="font-bold text-2xl no-underline" style="color: var(--color-krem);">
                    MinSponsor
                </a>
                <p class="mt-3 text-sm" style="color: var(--color-beige); opacity: 0.8;">
                    Enkel og trygg støtte til lokalidretten.
                </p>
            </div>

            <!-- For supportere -->
            <div>
                <h4 class="font-semibold mb-4" style="color: var(--color-krem);">For supportere</h4>
                <ul class="space-y-2 text-sm" style="color: var(--color-beige); opacity: 0.8;">
                    <li><a href="<?php echo home_url('/stott/'); ?>" class="hover:opacity-100 transition-opacity no-underline" style="color: inherit;">Finn lag</a></li>
                    <li><a href="<?php echo home_url('/faq/'); ?>" class="hover:opacity-100 transition-opacity no-underline" style="color: inherit;">FAQ</a></li>
                </ul>
            </div>

            <!-- For klubber -->
            <div>
                <h4 class="font-semibold mb-4" style="color: var(--color-krem);">For klubber</h4>
                <ul class="space-y-2 text-sm" style="color: var(--color-beige); opacity: 0.8;">
                    <li><a href="<?php echo home_url('/#kontakt'); ?>" class="hover:opacity-100 transition-opacity no-underline" style="color: inherit;">Registrer klubb</a></li>
                    <li><a href="<?php echo home_url('/faq/#for-klubber'); ?>" class="hover:opacity-100 transition-opacity no-underline" style="color: inherit;">Slik fungerer det</a></li>
                </ul>
            </div>

            <!-- Om MinSponsor -->
            <div>
                <h4 class="font-semibold mb-4" style="color: var(--color-krem);">Om MinSponsor</h4>
                <ul class="space-y-2 text-sm" style="color: var(--color-beige); opacity: 0.8;">
                    <li><a href="<?php echo home_url('/om-oss/'); ?>" class="hover:opacity-100 transition-opacity no-underline" style="color: inherit;">Om oss</a></li>
                    <li><a href="mailto:hei@minsponsor.no" class="hover:opacity-100 transition-opacity no-underline" style="color: inherit;">Kontakt</a></li>
                </ul>
            </div>
        </div>

        <!-- Bottom bar -->
        <div class="pt-8 flex flex-col md:flex-row justify-between items-center gap-4" style="border-top: 1px solid rgba(255,255,255,0.1);">
            <p class="text-sm" style="color: var(--color-beige); opacity: 0.6;">
                &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>
            </p>
            <div class="flex items-center gap-4 text-sm" style="color: var(--color-beige); opacity: 0.6;">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Sikker betaling via Stripe
                </span>
            </div>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
