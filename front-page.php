<?php
/**
 * Front Page Template - MinSponsor Splash Page
 * 
 * Designsystem: Varm, inkluderende, pålitelig og enkel.
 * Farger: Korall, Terrakotta, Beige, Brun
 */
get_header();
?>

<style>
    /* Front page specific styles */
    .hero-section {
        background: linear-gradient(135deg, var(--color-beige) 0%, var(--color-krem) 100%);
        min-height: calc(100vh - 80px);
    }
    
    .hero-blob {
        position: absolute;
        border-radius: 50%;
        filter: blur(60px);
        opacity: 0.4;
    }
    
    .blob-1 {
        width: 400px;
        height: 400px;
        background: var(--color-korall);
        top: 10%;
        right: 10%;
    }
    
    .blob-2 {
        width: 300px;
        height: 300px;
        background: var(--color-terrakotta);
        bottom: 20%;
        left: 5%;
    }
    
    .feature-card {
        background: var(--color-krem);
        border-radius: var(--radius-md);
        padding: 32px;
        box-shadow: var(--shadow-warm);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .feature-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-warm-lg);
    }
    
    .feature-icon {
        width: 56px;
        height: 56px;
        background: linear-gradient(135deg, var(--color-korall) 0%, var(--color-terrakotta) 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
    }
    
    .feature-icon svg {
        width: 28px;
        height: 28px;
        color: var(--color-krem);
    }
    
    .stats-item {
        text-align: center;
    }
    
    .stats-number {
        font-size: 48px;
        font-weight: 700;
        color: var(--color-terrakotta);
        line-height: 1;
    }
    
    .cta-section {
        background: linear-gradient(135deg, var(--color-korall) 0%, var(--color-terrakotta) 100%);
    }
    
    .illustration-container {
        max-width: 320px;
        margin: 0 auto;
    }
    
    @media (max-width: 768px) {
        .hero-section {
            min-height: auto;
            padding-top: 60px;
            padding-bottom: 60px;
        }
        
        .stats-number {
            font-size: 36px;
        }
    }
</style>

<!-- Hero Section -->
<section class="hero-section relative overflow-hidden">
    <!-- Decorative blobs -->
    <div class="hero-blob blob-1 hidden lg:block"></div>
    <div class="hero-blob blob-2 hidden lg:block"></div>
    
    <div class="max-w-6xl mx-auto px-4 py-16 md:py-24 relative z-10">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            
            <!-- Left: Text content -->
            <div class="text-center md:text-left">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6" style="color: var(--color-brun); line-height: 1.1;">
                    Gi barna mer tid til det de elsker
                </h1>
                
                <p class="text-lg md:text-xl mb-8" style="color: var(--color-brun-light); line-height: 1.7;">
                    MinSponsor gjør det enkelt for foreldre og tilhengere å støtte klubben, laget eller spilleren. 
                    <strong style="color: var(--color-brun);">Enkelt. Trygt. Forutsigbart.</strong>
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start">
                    <a href="#hvordan" class="btn-primary text-center">
                        Se hvordan det fungerer
                    </a>
                    <a href="#kontakt" class="btn-secondary text-center">
                        Kontakt oss
                    </a>
                </div>
                
                <!-- Trust indicators -->
                <div class="mt-10 flex flex-wrap gap-6 justify-center md:justify-start text-sm" style="color: var(--color-brun-light);">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-terrakotta);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>100% til mottaker</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-terrakotta);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Ingen binding</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-terrakotta);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Sikker betaling</span>
                    </div>
                </div>
            </div>
            
            <!-- Right: Illustration -->
            <div class="illustration-container order-first md:order-last">
                <!-- SVG illustration inspired by the brand figures -->
                <svg viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
                    <!-- Ground shadow -->
                    <ellipse cx="200" cy="360" rx="150" ry="15" fill="#3D3228" opacity="0.1"/>
                    
                    <!-- Left figure (terrakotta/coral) -->
                    <ellipse cx="140" cy="260" rx="70" ry="90" fill="#D97757"/>
                    <!-- Face -->
                    <path d="M115 235 Q120 240 125 235" stroke="#3D3228" stroke-width="3" stroke-linecap="round" fill="none"/>
                    <path d="M155 235 Q160 240 165 235" stroke="#3D3228" stroke-width="3" stroke-linecap="round" fill="none"/>
                    <path d="M125 260 Q140 275 155 260" stroke="#3D3228" stroke-width="3" stroke-linecap="round" fill="none"/>
                    <!-- Hair/top -->
                    <circle cx="140" cy="170" r="12" fill="#D97757"/>
                    <!-- Arm reaching to heart -->
                    <path d="M185 250 Q200 240 210 260" stroke="#3D3228" stroke-width="3" stroke-linecap="round" fill="none"/>
                    
                    <!-- Right figure (korall/orange) -->
                    <ellipse cx="270" cy="250" rx="75" ry="100" fill="#F6A586"/>
                    <!-- Face -->
                    <path d="M245 220 Q250 225 255 220" stroke="#3D3228" stroke-width="3" stroke-linecap="round" fill="none"/>
                    <path d="M285 220 Q290 225 295 220" stroke="#3D3228" stroke-width="3" stroke-linecap="round" fill="none"/>
                    <path d="M255 248 Q270 263 285 248" stroke="#3D3228" stroke-width="3" stroke-linecap="round" fill="none"/>
                    <!-- Arm reaching to heart -->
                    <path d="M210 250 Q195 240 190 260" stroke="#3D3228" stroke-width="3" stroke-linecap="round" fill="none"/>
                    
                    <!-- Heart in the middle -->
                    <path d="M200 230 C200 210 175 195 175 220 C175 245 200 270 200 270 C200 270 225 245 225 220 C225 195 200 210 200 230" 
                          fill="#F4C85E" stroke="#3D3228" stroke-width="3"/>
                    
                    <!-- Small hearts floating -->
                    <path d="M320 140 C320 132 312 126 312 134 C312 142 320 150 320 150 C320 150 328 142 328 134 C328 126 320 132 320 140" 
                          fill="#F6A586" opacity="0.6"/>
                    <path d="M90 180 C90 174 84 170 84 176 C84 182 90 188 90 188 C90 188 96 182 96 176 C96 170 90 174 90 180" 
                          fill="#D97757" opacity="0.6"/>
                    <path d="M350 220 C350 215 345 212 345 217 C345 222 350 227 350 227 C350 227 355 222 355 217 C355 212 350 215 350 220" 
                          fill="#F4C85E" opacity="0.5"/>
                </svg>
            </div>
        </div>
    </div>
</section>

<!-- How it works Section -->
<section id="hvordan" class="py-20 md:py-28" style="background-color: var(--color-krem);">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold mb-4" style="color: var(--color-brun);">
                Slik fungerer det
            </h2>
            <p class="text-lg max-w-2xl mx-auto" style="color: var(--color-brun-light);">
                Tre enkle steg for å støtte din favorittspiller, lag eller klubb
            </p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Step 1 -->
            <div class="feature-card text-center">
                <div class="feature-icon mx-auto">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <div class="text-5xl font-bold mb-4" style="color: var(--color-korall);">1</div>
                <h3 class="text-xl font-semibold mb-3" style="color: var(--color-brun);">Finn din favoritt</h3>
                <p style="color: var(--color-brun-light);">
                    Søk opp klubben, laget eller spilleren du vil støtte
                </p>
            </div>
            
            <!-- Step 2 -->
            <div class="feature-card text-center">
                <div class="feature-icon mx-auto">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <div class="text-5xl font-bold mb-4" style="color: var(--color-korall);">2</div>
                <h3 class="text-xl font-semibold mb-3" style="color: var(--color-brun);">Velg beløp</h3>
                <p style="color: var(--color-brun-light);">
                    Bestem hvor mye du vil gi – engang eller månedlig
                </p>
            </div>
            
            <!-- Step 3 -->
            <div class="feature-card text-center">
                <div class="feature-icon mx-auto">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="text-5xl font-bold mb-4" style="color: var(--color-korall);">3</div>
                <h3 class="text-xl font-semibold mb-3" style="color: var(--color-brun);">Gled noen</h3>
                <p style="color: var(--color-brun-light);">
                    100% av støtten går direkte til mottakeren
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Benefits Section -->
<section class="py-20 md:py-28" style="background-color: var(--color-beige);">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid md:grid-cols-2 gap-16 items-center">
            
            <!-- Left: Benefits list -->
            <div>
                <h2 class="text-3xl md:text-4xl font-bold mb-6" style="color: var(--color-brun);">
                    Hvorfor foreldre elsker MinSponsor
                </h2>
                <p class="text-lg mb-8" style="color: var(--color-brun-light);">
                    Vi fjerner bryet med dugnad og kontanter, slik at du kan fokusere på det viktigste – barna.
                </p>
                
                <div class="space-y-6">
                    <div class="flex gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center" style="background-color: var(--color-terrakotta);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-krem);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-1" style="color: var(--color-brun);">Spar tid på dugnad</h4>
                            <p style="color: var(--color-brun-light);">Færre kaker, flere kamper. Støtt med noen klikk.</p>
                        </div>
                    </div>
                    
                    <div class="flex gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center" style="background-color: var(--color-terrakotta);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-krem);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-1" style="color: var(--color-brun);">Trygg og sikker</h4>
                            <p style="color: var(--color-brun-light);">Betaling via Stripe og Vipps. Ingen overraskelser.</p>
                        </div>
                    </div>
                    
                    <div class="flex gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center" style="background-color: var(--color-terrakotta);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-krem);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-1" style="color: var(--color-brun);">Avslutt når du vil</h4>
                            <p style="color: var(--color-brun-light);">Ingen binding. Stopp abonnementet med ett klikk.</p>
                        </div>
                    </div>
                    
                    <div class="flex gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center" style="background-color: var(--color-terrakotta);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-krem);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-1" style="color: var(--color-brun);">Full åpenhet</h4>
                            <p style="color: var(--color-brun-light);">100% av støtten går til den du velger.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right: Stats -->
            <div class="card-lg" style="background: var(--color-krem);">
                <div class="grid grid-cols-2 gap-8">
                    <div class="stats-item">
                        <div class="stats-number">100%</div>
                        <div class="mt-2" style="color: var(--color-brun-light);">til mottaker</div>
                    </div>
                    <div class="stats-item">
                        <div class="stats-number">0 kr</div>
                        <div class="mt-2" style="color: var(--color-brun-light);">binding</div>
                    </div>
                    <div class="stats-item">
                        <div class="stats-number">2 min</div>
                        <div class="mt-2" style="color: var(--color-brun-light);">å komme i gang</div>
                    </div>
                    <div class="stats-item">
                        <div class="stats-number">24/7</div>
                        <div class="mt-2" style="color: var(--color-brun-light);">full kontroll</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- For Clubs Section -->
<section class="py-20 md:py-28" style="background-color: var(--color-krem);">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold mb-4" style="color: var(--color-brun);">
                For klubber og lag
            </h2>
            <p class="text-lg max-w-2xl mx-auto" style="color: var(--color-brun-light);">
                Gi medlemmene en enkel måte å støtte på – uten ekstra administrasjon
            </p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
            <div class="feature-card">
                <div class="feature-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-3" style="color: var(--color-brun);">Kom raskt i gang</h3>
                <p style="color: var(--color-brun-light);">
                    Registrer klubben på minutter. Vi setter opp alt for dere.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-3" style="color: var(--color-brun);">Alle nivåer</h3>
                <p style="color: var(--color-brun-light);">
                    Støtt klubben, laget eller enkeltspillere – pengene går dit de skal.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-3" style="color: var(--color-brun);">Full oversikt</h3>
                <p style="color: var(--color-brun-light);">
                    Se hvem som støtter, hvor mye, og når – i sanntid.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section id="kontakt" class="cta-section py-20 md:py-28">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-6" style="color: var(--color-krem);">
            Klar til å forenkle støtten?
        </h2>
        <p class="text-lg mb-10 opacity-90" style="color: var(--color-krem);">
            Ta kontakt for en uforpliktende prat om hvordan MinSponsor kan hjelpe din klubb.
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="mailto:hei@minsponsor.no" class="inline-flex items-center justify-center gap-2 font-semibold py-4 px-8 rounded-lg transition-all" 
               style="background-color: var(--color-krem); color: var(--color-terrakotta);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                hei@minsponsor.no
            </a>
        </div>
        
        <p class="mt-8 text-sm opacity-75" style="color: var(--color-krem);">
            Eller se en demo: 
            <a href="<?php echo home_url('/stott/heimdal-if/handball-g09/ola-nordmann/'); ?>" 
               class="underline hover:no-underline" style="color: var(--color-krem);">
                Støtt Ola Nordmann →
            </a>
        </p>
    </div>
</section>

<?php get_footer(); ?>
