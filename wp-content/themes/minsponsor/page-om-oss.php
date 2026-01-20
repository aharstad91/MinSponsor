<?php
/**
 * Template Name: Om oss
 *
 * Design: MinSponsor Designsystem
 * - Varm korall (#F6A586) som hovedfarge
 * - Terrakotta (#D97757) for CTAs
 * - Beige bakgrunn (#F5EFE6)
 */
get_header();
?>

<main class="min-h-screen" style="background-color: var(--color-beige);">

    <!-- Hero Section -->
    <section class="py-16 md:py-24 px-4" style="background: linear-gradient(135deg, var(--color-korall) 0%, var(--color-terrakotta) 100%);">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-6" style="color: var(--color-krem);">
                Vi gjør det enklere å støtte lokalidretten
            </h1>
            <p class="text-xl md:text-2xl opacity-90" style="color: var(--color-krem);">
                MinSponsor ble startet med ett mål: Gi barna mer tid til det de elsker.
            </p>
        </div>
    </section>

    <!-- Story Section -->
    <section class="py-16 px-4">
        <div class="max-w-3xl mx-auto">
            <div class="glass-card p-8 md:p-12">
                <h2 class="text-3xl font-bold mb-8 text-center" style="color: var(--color-brun);">
                    Historien bak MinSponsor
                </h2>

                <div class="prose max-w-none" style="color: var(--color-brun-light);">
                    <p class="text-lg mb-6">
                        Det startet med en frustrasjon mange kjenner seg igjen i: Dugnadslapper, loddtreff,
                        kakeaksjoner og innsamlingsbokser. For travle familier ble det stadig vanskeligere
                        å bidra til barnas aktiviteter på tradisjonelle måter.
                    </p>

                    <p class="text-lg mb-6">
                        <strong style="color: var(--color-brun);">«Hvorfor kan jeg ikke bare sette opp et
                        fast beløp som trekkes hver måned?»</strong> – Det spørsmålet stilte Vegard seg
                        da han sto på sidelinjen under sønnens håndballtrening. Rundt ham så han foreldre
                        og besteforeldre som gjerne ville bidra mer, men som ikke hadde tid til dugnader.
                    </p>

                    <p class="text-lg mb-6">
                        Svaret ble MinSponsor – en plattform som gjør det like enkelt å støtte klubben
                        som å betale strømregningen. Ingen lapper, ingen kalendere, ingen koordinering.
                        Bare et fast beløp som går rett til laget hver måned.
                    </p>

                    <p class="text-lg">
                        I dag hjelper vi klubber over hele Norge med å samle inn støtte på en moderne måte.
                        Foreldrene slipper dugnadsmaset, besteforeldrene kan bidra fra sofaen, og barna?
                        De får mer tid til å gjøre det de liker best.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="py-16 px-4" style="background-color: var(--color-krem);">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-3xl font-bold mb-8" style="color: var(--color-brun);">
                Vår misjon
            </h2>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="p-6">
                    <div class="blob-icon mx-auto mb-4" style="background-color: var(--color-korall);">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-krem);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3" style="color: var(--color-brun);">Spare tid</h3>
                    <p style="color: var(--color-brun-light);">
                        Mindre tid på innsamling betyr mer tid til trening, kamper og sosialt samvær.
                    </p>
                </div>

                <div class="p-6">
                    <div class="blob-icon mx-auto mb-4" style="background-color: var(--color-korall);">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-krem);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3" style="color: var(--color-brun);">Inkludere alle</h3>
                    <p style="color: var(--color-brun-light);">
                        Besteforeldre, onkler og støttespillere kan bidra like enkelt som foreldrene.
                    </p>
                </div>

                <div class="p-6">
                    <div class="blob-icon mx-auto mb-4" style="background-color: var(--color-korall);">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-krem);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3" style="color: var(--color-brun);">Bygge tillit</h3>
                    <p style="color: var(--color-brun-light);">
                        Full transparens på hvor pengene går, med sikker betaling via Stripe.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="py-16 px-4">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-3xl font-bold mb-10 text-center" style="color: var(--color-brun);">
                Det vi tror på
            </h2>

            <div class="space-y-6">
                <div class="glass-card p-6 flex items-start gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center" style="background-color: var(--color-korall);">
                        <span class="text-lg font-bold" style="color: var(--color-krem);">1</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg mb-2" style="color: var(--color-brun);">Ærlighet først</h3>
                        <p style="color: var(--color-brun-light);">
                            Vi er åpne om avgifter og hvordan pengene fordeles. Ingen skjulte kostnader,
                            ingen overraskelser.
                        </p>
                    </div>
                </div>

                <div class="glass-card p-6 flex items-start gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center" style="background-color: var(--color-korall);">
                        <span class="text-lg font-bold" style="color: var(--color-krem);">2</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg mb-2" style="color: var(--color-brun);">Enkelt skal være enkelt</h3>
                        <p style="color: var(--color-brun-light);">
                            Registrering på under 5 minutter. Støtte på under 1 minutt.
                            Vi jobber konstant for å fjerne friksjon.
                        </p>
                    </div>
                </div>

                <div class="glass-card p-6 flex items-start gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center" style="background-color: var(--color-korall);">
                        <span class="text-lg font-bold" style="color: var(--color-krem);">3</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg mb-2" style="color: var(--color-brun);">Barna i sentrum</h3>
                        <p style="color: var(--color-brun-light);">
                            Alt vi gjør handler om å gi barn og unge bedre muligheter til å drive
                            med det de elsker.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 px-4" style="background: linear-gradient(135deg, var(--color-korall) 0%, var(--color-terrakotta) 100%);">
        <div class="max-w-3xl mx-auto text-center">
            <h2 class="text-3xl font-bold mb-6" style="color: var(--color-krem);">
                Vil du bli med?
            </h2>
            <p class="text-xl mb-8 opacity-90" style="color: var(--color-krem);">
                Enten du vil støtte et lag eller registrere klubben din – vi er her for å hjelpe.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="<?php echo home_url('/stott/'); ?>"
                   class="btn-cta inline-block px-8 py-4 text-lg"
                   style="background-color: var(--color-krem); color: var(--color-terrakotta);">
                    Finn din klubb
                </a>
                <a href="<?php echo home_url('/#kontakt'); ?>"
                   class="inline-block px-8 py-4 text-lg rounded-full font-semibold transition-all hover:scale-105"
                   style="background-color: transparent; color: var(--color-krem); border: 2px solid var(--color-krem);">
                    Registrer klubb
                </a>
            </div>
        </div>
    </section>

    <!-- FAQ Link -->
    <section class="py-12 px-4 text-center">
        <p class="text-lg" style="color: var(--color-brun-light);">
            Har du spørsmål? Sjekk vår
            <a href="<?php echo home_url('/faq/'); ?>" class="font-semibold hover:underline" style="color: var(--color-terrakotta);">
                FAQ-side
            </a>
            for svar på vanlige spørsmål.
        </p>
    </section>

</main>

<?php get_footer(); ?>
