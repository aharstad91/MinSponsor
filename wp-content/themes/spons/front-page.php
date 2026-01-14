<?php
/**
 * Front Page Template - MinSponsor Landing Page
 * 
 * Basert på React-design fra Figma
 * Designsystem: Varm, inkluderende, pålitelig og enkel.
 * Farger: Korall (#F6A586), Terrakotta (#D97757), Beige (#F5EFE6), Brun (#3D3228)
 */
get_header();
?>

<style>
    /* Front page specific styles */
    .hero-section {
        background-color: var(--color-beige);
    }
    
    /* Blob Icon for feature cards */
    .blob-icon {
        width: 64px;
        height: 64px;
        background-color: var(--color-korall);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
    }
    
    .blob-icon svg {
        width: 32px;
        height: 32px;
        color: var(--color-brun);
    }
    
    .blob-icon.rotate-left {
        transform: rotate(-6deg);
    }
    
    .blob-icon.rotate-right {
        transform: rotate(3deg);
    }
    
    .blob-icon.rotate-slight {
        transform: rotate(-3deg);
    }
    
    /* Glass cards */
    .glass-card {
        background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-radius: 24px;
        padding: 32px;
        box-shadow: 0 4px 20px rgba(61, 50, 40, 0.08);
        transition: box-shadow 0.3s ease;
        height: 100%;
    }
    
    .glass-card:hover {
        box-shadow: 0 8px 30px rgba(61, 50, 40, 0.12);
        transform: translateY(-2px);
    }
    
    /* Clickable card styles */
    a.glass-card {
        display: block;
        text-decoration: none;
        cursor: pointer;
    }
    
    a.glass-card:focus {
        outline: 3px solid var(--color-terrakotta);
        outline-offset: 4px;
    }
    
    /* Process steps section */
    .process-section {
        background: rgba(255, 255, 255, 0.4);
    }
    
    .step-circle {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        background: white;
        border: 4px solid var(--color-korall);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        font-weight: 700;
        margin-bottom: 24px;
        box-shadow: 0 2px 10px rgba(61, 50, 40, 0.06);
        transition: transform 0.2s ease;
    }
    
    .step-circle:hover,
    .step-item:hover .step-circle {
        transform: scale(1.1);
    }
    
    .step-circle.filled {
        background: var(--color-korall);
        color: white;
        border-color: var(--color-korall);
        box-shadow: 0 4px 15px rgba(246, 165, 134, 0.4);
    }
    
    .step-arrow {
        color: rgba(217, 119, 87, 0.4);
        position: absolute;
        top: 32px;
        right: -50%;
    }
    
    /* CTA Button */
    .btn-cta {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: var(--color-terrakotta);
        color: white;
        font-weight: 700;
        font-size: 20px;
        padding: 20px 40px;
        border-radius: 9999px;
        box-shadow: 0 4px 20px rgba(217, 119, 87, 0.3);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .btn-cta:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 25px rgba(217, 119, 87, 0.4);
    }
    
    /* Hero image */
    .hero-image {
        max-width: 280px;
        margin: 0 auto;
    }
    
    @media (min-width: 768px) {
        .hero-image {
            max-width: 350px;
        }
    }
    
    /* Footer styles */
    .footer-section {
        border-top: 1px solid #E5DCCA;
    }
    
    /* Entity Search Styles */
    .entity-search-container {
        position: relative;
        max-width: 500px;
        margin: 0 auto;
    }
    
    .entity-search-input {
        width: 100%;
        padding: 18px 24px 18px 56px;
        font-size: 18px;
        border: 2px solid transparent;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        box-shadow: 0 4px 20px rgba(61, 50, 40, 0.12);
        color: var(--color-brun);
        transition: all 0.2s ease;
    }
    
    .entity-search-input::placeholder {
        color: var(--color-brun);
        opacity: 0.5;
    }
    
    .entity-search-input:focus {
        outline: none;
        border-color: var(--color-terrakotta);
        box-shadow: 0 4px 25px rgba(217, 119, 87, 0.25);
    }
    
    .entity-search-icon {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        width: 24px;
        height: 24px;
        color: var(--color-brun);
        opacity: 0.6;
        pointer-events: none;
    }
    
    .entity-search-spinner {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        width: 24px;
        height: 24px;
        display: none;
    }
    
    .entity-search-spinner.visible {
        display: block;
    }
    
    .entity-search-spinner svg {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .entity-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        margin-top: 8px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-radius: 16px;
        box-shadow: 0 8px 40px rgba(61, 50, 40, 0.15);
        overflow: hidden;
        z-index: 100;
        display: none;
        max-height: 360px;
        overflow-y: auto;
    }
    
    .entity-dropdown.visible {
        display: block;
    }
    
    .entity-dropdown-item {
        display: flex;
        align-items: center;
        padding: 14px 20px;
        cursor: pointer;
        transition: background 0.15s ease;
        text-decoration: none;
        color: var(--color-brun);
    }
    
    .entity-dropdown-item:hover,
    .entity-dropdown-item.active {
        background: rgba(246, 165, 134, 0.2);
    }
    
    .entity-dropdown-item-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--color-korall);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 14px;
        flex-shrink: 0;
    }
    
    .entity-dropdown-item-icon svg {
        width: 20px;
        height: 20px;
        color: var(--color-brun);
    }
    
    .entity-dropdown-item-content {
        flex: 1;
        min-width: 0;
    }
    
    .entity-dropdown-item-name {
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 2px;
    }
    
    .entity-dropdown-item-sublabel {
        font-size: 14px;
        opacity: 0.7;
    }
    
    .entity-type-chip {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 6px;
        background: rgba(217, 119, 87, 0.15);
        color: var(--color-terrakotta);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .entity-no-results {
        padding: 24px;
        text-align: center;
        color: var(--color-brun);
        opacity: 0.8;
    }
    
    .entity-no-results a {
        color: var(--color-terrakotta);
        font-weight: 600;
    }
    
    .search-helper-text {
        font-size: 14px;
        opacity: 0.6;
        margin-top: 12px;
        color: var(--color-brun);
    }
</style>

<!-- 1. HERO SECTION -->
<header class="hero-section relative pt-12 pb-20 px-6 text-center">
    <div class="max-w-4xl mx-auto flex flex-col items-center">
        
        <h1 class="text-4xl md:text-[56px] font-bold leading-tight mb-8 md:mb-12" style="color: var(--color-brun);">
            Mer idrett. Mindre dugnad.
        </h1>
        
        <!-- Hero illustration -->
        <div class="hero-image mb-8">
            <img 
                src="<?php echo get_template_directory_uri(); ?>/assets/images/minsponsor-characters.png" 
                alt="To vennlige figurer som holder et hjerte sammen" 
                class="w-full h-auto drop-shadow-xl"
            />
        </div>
        
        <h2 class="text-2xl md:text-[32px] font-bold mb-4" style="color: var(--color-brun);">
            MinSponsor gjør jobben
        </h2>
        
        <p class="text-lg md:text-xl opacity-80 max-w-lg mb-8" style="color: var(--color-brun);">
            Stabile inntekter - uten salg, leveranser eller ekstra arbeid
        </p>
        
        <!-- Entity Search -->
        <div class="entity-search-container w-full mb-6" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-owns="entity-search-results">
            <div class="entity-search-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input 
                type="text" 
                id="entity-search-input"
                class="entity-search-input"
                placeholder="Søk etter klubb, lag eller utøver..."
                aria-autocomplete="list"
                aria-controls="entity-search-results"
                autocomplete="off"
            />
            <div class="entity-search-spinner" id="entity-search-spinner">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-terrakotta);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <div class="entity-dropdown" id="entity-search-results" role="listbox" aria-label="Søkeresultater">
                <!-- Results populated by JS -->
            </div>
        </div>
        <p class="search-helper-text" id="search-helper-text">
            Søk på klubb, lag eller utøver — eller 
            <a href="#kontakt" class="register-team-link" style="color: var(--color-terrakotta); font-weight: 600; text-decoration: underline; text-underline-offset: 2px;">registrer ditt lag</a>
        </p>
        
        <div class="mt-6">
            <a href="#hvordan" class="text-lg font-medium hover:underline" style="color: var(--color-terrakotta);">
                Slik fungerer det →
            </a>
        </div>
    </div>
</header>

<!-- 2. THREE COLUMNS: "Dette løser vi" -->
<section id="fordeler" class="py-20 px-6" style="background-color: var(--color-beige);">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-[48px] font-bold mb-4" style="color: var(--color-brun);">Dette løser vi</h2>
            <p class="text-lg opacity-80" style="color: var(--color-brun);">En enklere hverdag for alle involverte</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Card 1: For Klubben -->
            <a href="#hvordan" class="glass-card flex flex-col items-center text-center">
                <div class="blob-icon rotate-left">
                    <!-- Trophy icon -->
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold mb-4" style="color: var(--color-brun);">For klubben</h3>
                <p class="text-lg leading-relaxed opacity-85" style="color: var(--color-brun);">
                    Sikre stabile inntekter uten administrasjon. Vi håndterer alt det praktiske så dere kan fokusere på sporten.
                </p>
            </a>
            
            <!-- Card 2: For Foreldre (elevated) -->
            <a href="#hvordan" class="glass-card flex flex-col items-center text-center md:-mt-6">
                <div class="blob-icon rotate-right">
                    <!-- Shield icon -->
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold mb-4" style="color: var(--color-brun);">For foreldre</h3>
                <p class="text-lg leading-relaxed opacity-85" style="color: var(--color-brun);">
                    Slipp kakelotteri og dørsalg. Støtt laget gjennom smarte avtaler dere faktisk har bruk for i hverdagen.
                </p>
            </a>
            
            <!-- Card 3: For Barna -->
            <a href="#hvordan" class="glass-card flex flex-col items-center text-center">
                <div class="blob-icon rotate-slight">
                    <!-- Sparkles icon -->
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold mb-4" style="color: var(--color-brun);">For barna</h3>
                <p class="text-lg leading-relaxed opacity-85" style="color: var(--color-brun);">
                    Mer tid til lek og trening. Mindre fokus på inntjening betyr mer glede og samhold i laget.
                </p>
            </a>
        </div>
    </div>
</section>

<!-- 3. PROCESS FLOW: "Slik fungerer det" -->
<section id="hvordan" class="process-section py-24 px-6">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-20">
            <h2 class="text-3xl md:text-[48px] font-bold mb-4" style="color: var(--color-brun);">Slik fungerer det</h2>
            <p class="text-lg opacity-80" style="color: var(--color-brun);">Kom i gang på 1-2-3 (og 4)</p>
        </div>
        
        <div class="relative grid grid-cols-1 md:grid-cols-4 gap-8 items-start">
            <!-- Step 1 -->
            <div class="step-item flex flex-col items-center text-center relative z-10">
                <div class="hidden md:block step-arrow">
                    <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </div>
                <div class="step-circle" style="color: var(--color-brun);">1</div>
                <h3 class="text-xl font-bold mb-3" style="color: var(--color-brun);">Registrer laget</h3>
                <p class="opacity-80 px-2" style="color: var(--color-brun);">
                    Opprett en konto for laget eller foreningen din helt gratis.
                </p>
            </div>
            
            <!-- Step 2 -->
            <div class="step-item flex flex-col items-center text-center relative">
                <div class="hidden md:block step-arrow">
                    <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </div>
                <div class="step-circle z-10" style="color: var(--color-brun);">2</div>
                <h3 class="text-xl font-bold mb-3" style="color: var(--color-brun);">Del lenken</h3>
                <p class="opacity-80 px-2" style="color: var(--color-brun);">
                    Send din unike støttelenke til foreldre, familie og venner.
                </p>
            </div>
            
            <!-- Step 3 -->
            <div class="step-item flex flex-col items-center text-center relative">
                <div class="hidden md:block step-arrow">
                    <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </div>
                <div class="step-circle z-10" style="color: var(--color-brun);">3</div>
                <h3 class="text-xl font-bold mb-3" style="color: var(--color-brun);">Velg beløp</h3>
                <p class="opacity-80 px-2" style="color: var(--color-brun);">
                    Supporterne velger hvor mye de vil støtte – engang eller månedlig.
                </p>
            </div>
            
            <!-- Step 4 -->
            <div class="step-item flex flex-col items-center text-center relative">
                <div class="step-circle filled z-10">4</div>
                <h3 class="text-xl font-bold mb-3" style="color: var(--color-brun);">Motta støtte</h3>
                <p class="opacity-80 px-2" style="color: var(--color-brun);">
                    Laget får utbetalt 100% av støtten automatisk.
                </p>
            </div>
        </div>
        
        <div class="mt-20 text-center">
            <a href="#kontakt" class="btn-cta">
                Start innsamlingen nå
            </a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer id="kontakt" class="footer-section py-12 px-6 mt-12" style="background-color: var(--color-beige);">
    <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8 text-sm opacity-80" style="color: var(--color-brun);">
        <div class="col-span-1 md:col-span-2">
            <div class="font-bold text-xl mb-4" style="color: var(--color-brun);">MinSponsor</div>
            <p class="max-w-xs mb-4">
                Gjør dugnaden digital og enkel. Vi hjelper norske lag og foreninger med å realisere drømmene sine.
            </p>
            <p>© <?php echo date('Y'); ?> Samhold AS</p>
        </div>
        <div>
            <h4 class="font-bold mb-4 text-base" style="color: var(--color-brun);">Snarveier</h4>
            <ul class="space-y-2">
                <li><a href="#fordeler" class="hover:underline">Fordeler</a></li>
                <li><a href="#hvordan" class="hover:underline">Hvordan det virker</a></li>
                <li><a href="<?php echo home_url('/personvern/'); ?>" class="hover:underline">Personvern</a></li>
            </ul>
        </div>
        <div>
            <h4 class="font-bold mb-4 text-base" style="color: var(--color-brun);">Kontakt</h4>
            <ul class="space-y-2">
                <li><a href="mailto:hei@minsponsor.no" class="hover:underline">hei@minsponsor.no</a></li>
                <li>Trondheim, Norge</li>
            </ul>
            
            <!-- Demo link -->
            <div class="mt-6 pt-4 border-t" style="border-color: #E5DCCA;">
                <p class="text-xs mb-2 opacity-60">Se en demo:</p>
                <a href="<?php echo home_url('/stott/heimdal-handball/gutter-2009/'); ?>" 
                   class="text-sm font-medium hover:underline" style="color: var(--color-terrakotta);">
                    Støtt Gutter 2009 →
                </a>
            </div>
        </div>
    </div>
</footer>

<!-- Entity Search JavaScript -->
<script>
(function() {
    'use strict';
    
    // Config
    const DEBOUNCE_MS = 200;
    const MIN_CHARS = 2;
    const API_ENDPOINT = '<?php echo esc_url(rest_url('minsponsor/v1/entity-search')); ?>';
    
    // Mock data fallback (used if API not available)
    const MOCK_DATA = [
        { id: 1, type: 'klubb', name: 'Heimdal Håndball', subLabel: 'Klubb', url: '/stott/heimdal-handball/' },
        { id: 2, type: 'lag', name: 'Gutter 2009', subLabel: 'Heimdal Håndball', url: '/stott/heimdal-handball/gutter-2009/' },
        { id: 3, type: 'lag', name: 'Jenter 2010', subLabel: 'Heimdal Håndball', url: '/stott/heimdal-handball/jenter-2010/' },
        { id: 4, type: 'klubb', name: 'Rosenborg BK', subLabel: 'Klubb', url: '/stott/rosenborg-bk/' },
        { id: 5, type: 'lag', name: 'G14', subLabel: 'Rosenborg BK', url: '/stott/rosenborg-bk/g14/' },
        { id: 6, type: 'spiller', name: 'Ola Nordmann', subLabel: 'Gutter 2009', url: '/stott/heimdal-handball/gutter-2009/ola-nordmann/' },
        { id: 7, type: 'klubb', name: 'Byåsen IL', subLabel: 'Klubb', url: '/stott/byasen-il/' },
        { id: 8, type: 'lag', name: 'Herrer Elite', subLabel: 'Byåsen IL', url: '/stott/byasen-il/herrer-elite/' },
        { id: 9, type: 'klubb', name: 'Trondheim Friidrett', subLabel: 'Klubb', url: '/stott/trondheim-friidrett/' },
        { id: 10, type: 'spiller', name: 'Kari Hansen', subLabel: 'Jenter 2010', url: '/stott/heimdal-handball/jenter-2010/kari-hansen/' },
    ];
    
    // Cache for previous searches
    const searchCache = new Map();
    const MAX_CACHE_SIZE = 20;
    
    // DOM Elements
    const input = document.getElementById('entity-search-input');
    const dropdown = document.getElementById('entity-search-results');
    const spinner = document.getElementById('entity-search-spinner');
    const container = input?.closest('.entity-search-container');
    
    if (!input || !dropdown || !container) return;
    
    // State
    let activeIndex = -1;
    let currentResults = [];
    let debounceTimer = null;
    let useMockData = false;
    
    // Type icons
    const typeIcons = {
        klubb: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
        lag: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
        spiller: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>'
    };
    
    const typeLabels = {
        klubb: 'Klubb',
        lag: 'Lag',
        spiller: 'Utøver'
    };
    
    // Highlight matching text
    function highlightMatch(text, query) {
        if (!query) return text;
        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.replace(regex, '<strong>$1</strong>');
    }
    
    // Render results
    function renderResults(results, query) {
        if (results.length === 0) {
            dropdown.innerHTML = `
                <div class="entity-no-results">
                    <p>Ingen treff på «${query}»</p>
                    <p><a href="<?php echo home_url('/stott/'); ?>">Se alle lag og klubber →</a></p>
                </div>
            `;
            return;
        }
        
        dropdown.innerHTML = results.map((result, index) => `
            <a href="${result.url}" 
               class="entity-dropdown-item${index === activeIndex ? ' active' : ''}" 
               role="option" 
               aria-selected="${index === activeIndex}"
               data-index="${index}">
                <div class="entity-dropdown-item-icon">
                    ${typeIcons[result.type] || typeIcons.klubb}
                </div>
                <div class="entity-dropdown-item-content">
                    <div class="entity-dropdown-item-name">${highlightMatch(result.name, query)}</div>
                    <div class="entity-dropdown-item-sublabel">${result.subLabel}</div>
                </div>
                <span class="entity-type-chip">${typeLabels[result.type] || result.type}</span>
            </a>
        `).join('');
    }
    
    // Show/hide dropdown
    function showDropdown() {
        dropdown.classList.add('visible');
        container.setAttribute('aria-expanded', 'true');
    }
    
    function hideDropdown() {
        dropdown.classList.remove('visible');
        container.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
    }
    
    // Show/hide spinner
    function showSpinner() {
        spinner.classList.add('visible');
    }
    
    function hideSpinner() {
        spinner.classList.remove('visible');
    }
    
    // Mock search (fallback)
    function mockSearch(query) {
        const q = query.toLowerCase();
        return MOCK_DATA.filter(item => 
            item.name.toLowerCase().includes(q) || 
            item.subLabel.toLowerCase().includes(q)
        ).slice(0, 8);
    }
    
    // API search
    async function apiSearch(query) {
        // Check cache first
        if (searchCache.has(query)) {
            return searchCache.get(query);
        }
        
        try {
            const response = await fetch(`${API_ENDPOINT}?q=${encodeURIComponent(query)}&limit=8`);
            
            if (!response.ok) {
                throw new Error('API error');
            }
            
            const data = await response.json();
            const results = data.results || [];
            
            // Cache result
            if (searchCache.size >= MAX_CACHE_SIZE) {
                const firstKey = searchCache.keys().next().value;
                searchCache.delete(firstKey);
            }
            searchCache.set(query, results);
            
            return results;
        } catch (error) {
            console.warn('Entity search API error, using mock data:', error);
            useMockData = true;
            return mockSearch(query);
        }
    }
    
    // Perform search
    async function performSearch(query) {
        if (query.length < MIN_CHARS) {
            hideDropdown();
            return;
        }
        
        showSpinner();
        
        let results;
        if (useMockData) {
            results = mockSearch(query);
        } else {
            results = await apiSearch(query);
        }
        
        hideSpinner();
        currentResults = results;
        activeIndex = -1;
        renderResults(results, query);
        showDropdown();
    }
    
    // Debounced search
    function debouncedSearch(query) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => performSearch(query), DEBOUNCE_MS);
    }
    
    // Navigate to result
    function navigateToResult(index) {
        if (index >= 0 && index < currentResults.length) {
            window.location.href = currentResults[index].url;
        }
    }
    
    // Update active item
    function updateActiveItem(newIndex) {
        if (currentResults.length === 0) return;
        
        activeIndex = newIndex;
        if (activeIndex < 0) activeIndex = currentResults.length - 1;
        if (activeIndex >= currentResults.length) activeIndex = 0;
        
        const items = dropdown.querySelectorAll('.entity-dropdown-item');
        items.forEach((item, i) => {
            item.classList.toggle('active', i === activeIndex);
            item.setAttribute('aria-selected', i === activeIndex);
        });
    }
    
    // Event: Input
    input.addEventListener('input', (e) => {
        debouncedSearch(e.target.value.trim());
    });
    
    // Event: Focus
    input.addEventListener('focus', () => {
        if (input.value.length >= MIN_CHARS && currentResults.length > 0) {
            showDropdown();
        }
    });
    
    // Event: Keyboard
    input.addEventListener('keydown', (e) => {
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                if (!dropdown.classList.contains('visible') && input.value.length >= MIN_CHARS) {
                    performSearch(input.value.trim());
                } else {
                    updateActiveItem(activeIndex + 1);
                }
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                updateActiveItem(activeIndex - 1);
                break;
                
            case 'Enter':
                e.preventDefault();
                if (activeIndex >= 0) {
                    navigateToResult(activeIndex);
                }
                break;
                
            case 'Escape':
                hideDropdown();
                input.blur();
                break;
        }
    });
    
    // Event: Click outside
    document.addEventListener('click', (e) => {
        if (!container.contains(e.target)) {
            hideDropdown();
        }
    });
    
    // Event: Mouse over items
    dropdown.addEventListener('mouseover', (e) => {
        const item = e.target.closest('.entity-dropdown-item');
        if (item) {
            const index = parseInt(item.dataset.index, 10);
            if (!isNaN(index)) {
                updateActiveItem(index);
            }
        }
    });
})();
</script>

<?php // Note: We don't call get_footer() here since we have our own footer ?>
