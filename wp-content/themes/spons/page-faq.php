<?php
/**
 * Template Name: FAQ
 *
 * Design: MinSponsor Designsystem
 * - Accordion-style FAQ items
 * - Trust-building answers about payments, fees, security
 */
get_header();

// FAQ items with questions and answers
$faqs = [
    [
        'category' => 'Betaling',
        'items' => [
            [
                'question' => 'Hvordan fungerer betalingen?',
                'answer' => 'Du velger et beløp og om du vil gi en engangsstøtte eller månedlig. Betalingen skjer trygt via Stripe – samme betalingsløsning som brukes av Spotify, Shopify og millioner av andre bedrifter. Du kan betale med kort (Visa, Mastercard) eller via Vipps.'
            ],
            [
                'question' => 'Hvor mye går til klubben/laget?',
                'answer' => 'Klubben eller laget mottar <strong>hele beløpet du velger</strong>. Vi legger en liten plattformavgift (10%) på toppen av beløpet ditt. Hvis du velger å støtte med 100 kr, betaler du 110 kr – og laget mottar 100 kr. Stripe trekker også sine vanlige kortgebyrer (~2.9% + 1.80 kr) fra plattformavgiften, ikke fra støttebeløpet.'
            ],
            [
                'question' => 'Kan jeg avslutte abonnementet mitt?',
                'answer' => 'Ja, absolutt! Du kan avslutte når som helst, uten bindingstid eller oppsigelsesgebyr. Du får en lenke på e-post etter hver betaling der du enkelt kan administrere eller avslutte abonnementet ditt.'
            ],
            [
                'question' => 'Får jeg kvittering?',
                'answer' => 'Ja, du mottar automatisk en kvittering på e-post etter hver betaling. Denne kan brukes til egne notater, men merk at støtte til idrettslag normalt ikke er fradragsberettiget.'
            ],
        ]
    ],
    [
        'category' => 'Sikkerhet',
        'items' => [
            [
                'question' => 'Er det trygt å betale via MinSponsor?',
                'answer' => 'Ja. Vi bruker Stripe som betalingsløsning – en av verdens mest pålitelige og sikre betalingsplattformer. All kortinformasjon håndteres direkte av Stripe og lagres aldri på våre servere. Stripe er PCI DSS-sertifisert (høyeste sikkerhetsnivå for kortbetalinger) og krypterer all data.'
            ],
            [
                'question' => 'Hvem har tilgang til mine opplysninger?',
                'answer' => 'Kun det som er nødvendig. Klubben/laget ser navnet ditt og beløpet (så de kan takke deg!). Vi lagrer e-postadressen din for å sende kvitteringer. Kortinformasjonen din ser vi aldri – den håndteres direkte av Stripe.'
            ],
            [
                'question' => 'Hva skjer hvis jeg angrer?',
                'answer' => 'For engangsstøtte: Kontakt oss innen 24 timer, så ordner vi refusjon. For abonnement: Du kan avslutte når som helst, og du belastes ikke for kommende måneder.'
            ],
        ]
    ],
    [
        'category' => 'Om MinSponsor',
        'items' => [
            [
                'question' => 'Hvem står bak MinSponsor?',
                'answer' => 'MinSponsor er et norsk selskap startet av idrettsforeldre som selv kjente på dugnadsmaset. Vi er basert i Norge og alle data lagres på europeiske servere. Les mer om oss på vår <a href="' . home_url('/om-oss/') . '" class="font-semibold" style="color: var(--color-terrakotta);">Om oss-side</a>.'
            ],
            [
                'question' => 'Hvordan tjener MinSponsor penger?',
                'answer' => 'Vi tar en plattformavgift på 10% som legges på toppen av støttebeløpet. Dette dekker drift, utvikling og support. Vi tar aldri av beløpet som går til klubben.'
            ],
            [
                'question' => 'Hva brukes pengene til?',
                'answer' => 'Det bestemmer klubben/laget selv. Vanlige bruksområder er treningsavgifter, cupdeltakelse, utstyr, draktinnkjøp, eller å holde egenandelen lav for familier. Noen lag oppgir spesifikt hva støtten går til på sin side.'
            ],
        ]
    ],
    [
        'category' => 'For klubber og lag',
        'items' => [
            [
                'question' => 'Hvordan registrerer jeg klubben min?',
                'answer' => 'Ta kontakt med oss via <a href="' . home_url('/#kontakt') . '" class="font-semibold" style="color: var(--color-terrakotta);">kontaktskjemaet</a> på forsiden, så hjelper vi deg i gang. Registreringen tar under 5 minutter når vi har fått informasjonen vi trenger.'
            ],
            [
                'question' => 'Koster det noe for klubben?',
                'answer' => 'Nei, det er helt gratis å være på MinSponsor. Vi tar kun en avgift fra støtterne, og denne legges på toppen av beløpet – så klubben alltid mottar hele støttebeløpet.'
            ],
            [
                'question' => 'Hvordan får vi utbetalt pengene?',
                'answer' => 'Pengene går direkte til lagets bankkonto via Stripe Connect. Kassereren kobler opp lagets konto én gang, og deretter går utbetalingene automatisk. Dere kan velge daglig, ukentlig eller månedlig utbetaling.'
            ],
            [
                'question' => 'Kan flere lag i samme klubb bruke MinSponsor?',
                'answer' => 'Ja! Hver klubb kan ha flere lag, og hvert lag kan ha sin egen støtteside og bankkonto. Støttere kan velge å støtte hele klubben eller et spesifikt lag/spiller.'
            ],
        ]
    ],
];
?>

<main class="min-h-screen" style="background-color: var(--color-beige);">

    <!-- Hero Section -->
    <section class="py-16 md:py-20 px-4" style="background: linear-gradient(135deg, var(--color-korall) 0%, var(--color-terrakotta) 100%);">
        <div class="max-w-3xl mx-auto text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4" style="color: var(--color-krem);">
                Ofte stilte spørsmål
            </h1>
            <p class="text-xl opacity-90" style="color: var(--color-krem);">
                Finn svar på det du lurer på om MinSponsor
            </p>
        </div>
    </section>

    <!-- FAQ Content -->
    <section class="py-12 px-4">
        <div class="max-w-3xl mx-auto">

            <?php foreach ($faqs as $category): ?>
                <div class="mb-12">
                    <h2 class="text-2xl font-bold mb-6 flex items-center gap-3" style="color: var(--color-brun);">
                        <span class="w-2 h-8 rounded-full" style="background-color: var(--color-korall);"></span>
                        <?php echo esc_html($category['category']); ?>
                    </h2>

                    <div class="space-y-4">
                        <?php foreach ($category['items'] as $index => $faq): ?>
                            <details class="glass-card group" style="border-radius: var(--radius-md);">
                                <summary class="flex items-center justify-between p-5 cursor-pointer list-none font-semibold text-lg" style="color: var(--color-brun);">
                                    <span><?php echo esc_html($faq['question']); ?></span>
                                    <svg class="w-5 h-5 flex-shrink-0 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-terrakotta);">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </summary>
                                <div class="px-5 pb-5 pt-0" style="color: var(--color-brun-light);">
                                    <div class="pt-3" style="border-top: 1px solid var(--color-softgra);">
                                        <?php echo wp_kses_post($faq['answer']); ?>
                                    </div>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </section>

    <!-- Still have questions -->
    <section class="py-12 px-4">
        <div class="max-w-xl mx-auto text-center glass-card p-8" style="border-radius: var(--radius-lg);">
            <div class="blob-icon mx-auto mb-4" style="background-color: var(--color-korall);">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-krem);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold mb-4" style="color: var(--color-brun);">
                Fant du ikke svaret?
            </h2>
            <p class="mb-6" style="color: var(--color-brun-light);">
                Vi hjelper gjerne! Send oss en melding så svarer vi så fort vi kan.
            </p>
            <a href="mailto:hei@minsponsor.no"
               class="btn-primary inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                hei@minsponsor.no
            </a>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-12 px-4">
        <div class="max-w-3xl mx-auto text-center">
            <p class="text-lg mb-6" style="color: var(--color-brun-light);">
                Klar til å støtte lokalidretten?
            </p>
            <a href="<?php echo home_url('/stott/'); ?>"
               class="btn-cta inline-block px-8 py-4 text-lg">
                Finn din klubb
            </a>
        </div>
    </section>

</main>

<?php get_footer(); ?>
