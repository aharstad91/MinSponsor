<?php
/**
 * Checkout Customizer for MinSponsor
 * 
 * Minimizes checkout fields, adds trust signals, and Norwegian translations
 *
 * @package MinSponsor
 * @since 1.0.0
 */

namespace MinSponsor\Checkout;

if (!defined('ABSPATH')) {
    exit;
}

class CheckoutCustomizer {
    
    /**
     * Initialize hooks
     */
    public function init(): void {
        // Simplify checkout fields - remove unnecessary fields
        add_filter('woocommerce_checkout_fields', [$this, 'simplify_checkout_fields'], 20);
        add_filter('woocommerce_billing_fields', [$this, 'simplify_billing_fields'], 20);
        
        // Make address fields optional for virtual products
        add_filter('woocommerce_default_address_fields', [$this, 'make_address_optional'], 20);
        
        // Norwegian translations for checkout (works for both classic and block)
        add_filter('gettext', [$this, 'translate_checkout_strings'], 20, 3);
        add_filter('ngettext', [$this, 'translate_checkout_strings_plural'], 20, 5);
        add_filter('gettext_woocommerce', [$this, 'translate_checkout_strings'], 20, 3);
        add_filter('gettext_woocommerce-subscriptions', [$this, 'translate_checkout_strings'], 20, 3);
        add_filter('gettext_woocommerce-gateway-stripe', [$this, 'translate_checkout_strings'], 20, 3);
        
        // Add trust signals to checkout (classic)
        add_action('woocommerce_review_order_before_payment', [$this, 'add_trust_signals']);
        
        // Add subscription info banner for recurring products (classic)
        add_action('woocommerce_before_checkout_form', [$this, 'add_subscription_banner']);
        
        // For block-based checkout, add via footer
        add_action('wp_footer', [$this, 'add_block_checkout_enhancements']);
        
        // Hide certain payment methods for subscriptions
        add_filter('woocommerce_available_payment_gateways', [$this, 'filter_payment_gateways']);
        
        // Disable "Pay with Link" express checkout - too confusing
        add_filter('wc_stripe_show_payment_request_on_checkout', '__return_false');
        add_filter('wc_stripe_show_link_on_checkout', '__return_false');
        
        // Hide express checkout completely for MinSponsor
        add_filter('woocommerce_store_api_disable_nonce_check', '__return_false');
        
        // Custom checkout page styles
        add_action('wp_head', [$this, 'add_checkout_styles']);
        
        // Add cancel anytime notice near subscription total
        add_action('woocommerce_review_order_after_order_total', [$this, 'add_cancel_anytime_notice']);
        
        // Block checkout: Hide billing address for virtual products
        add_filter('woocommerce_blocks_checkout_requires_billing_address', [$this, 'maybe_hide_billing_address']);
        
        // WooCommerce blocks script translations
        add_filter('woocommerce_blocks_checkout_i18n', [$this, 'translate_block_checkout']);
        
        // Enqueue translation script for block checkout
        add_action('wp_enqueue_scripts', [$this, 'enqueue_translation_script']);
    }
    
    /**
     * Simplify checkout fields - keep only essentials
     *
     * @param array $fields Checkout fields
     * @return array Modified fields
     */
    public function simplify_checkout_fields(array $fields): array {
        // For MinSponsor orders (virtual products), we only need email and name
        if (!$this->cart_has_minsponsor_items()) {
            return $fields;
        }
        
        // Remove shipping fields completely
        unset($fields['shipping']);
        
        // Remove order comments
        unset($fields['order']['order_comments']);
        
        // Simplify billing fields
        $keep_fields = ['billing_email', 'billing_first_name', 'billing_last_name'];
        
        if (isset($fields['billing'])) {
            foreach ($fields['billing'] as $key => $field) {
                if (!in_array($key, $keep_fields)) {
                    unset($fields['billing'][$key]);
                }
            }
            
            // Reorder: email first, then name
            $ordered = [];
            foreach ($keep_fields as $key) {
                if (isset($fields['billing'][$key])) {
                    $ordered[$key] = $fields['billing'][$key];
                }
            }
            $fields['billing'] = $ordered;
            
            // Update labels to Norwegian
            if (isset($fields['billing']['billing_email'])) {
                $fields['billing']['billing_email']['label'] = 'E-postadresse';
                $fields['billing']['billing_email']['placeholder'] = 'din@epost.no';
            }
            if (isset($fields['billing']['billing_first_name'])) {
                $fields['billing']['billing_first_name']['label'] = 'Fornavn';
                $fields['billing']['billing_first_name']['placeholder'] = 'Ola';
            }
            if (isset($fields['billing']['billing_last_name'])) {
                $fields['billing']['billing_last_name']['label'] = 'Etternavn';
                $fields['billing']['billing_last_name']['placeholder'] = 'Nordmann';
            }
        }
        
        return $fields;
    }
    
    /**
     * Simplify billing fields for address display
     *
     * @param array $fields Billing fields
     * @return array Modified fields
     */
    public function simplify_billing_fields(array $fields): array {
        if (!$this->cart_has_minsponsor_items()) {
            return $fields;
        }
        
        // Make most fields not required for MinSponsor items
        $optional_fields = [
            'billing_company',
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_postcode',
            'billing_country',
            'billing_state',
            'billing_phone'
        ];
        
        foreach ($optional_fields as $key) {
            if (isset($fields[$key])) {
                $fields[$key]['required'] = false;
            }
        }
        
        return $fields;
    }
    
    /**
     * Make address fields optional for virtual products
     *
     * @param array $fields Address fields
     * @return array Modified fields
     */
    public function make_address_optional(array $fields): array {
        if (!$this->cart_has_minsponsor_items()) {
            return $fields;
        }
        
        // Make all address fields optional
        foreach ($fields as $key => $field) {
            if (!in_array($key, ['first_name', 'last_name', 'email'])) {
                $fields[$key]['required'] = false;
            }
        }
        
        return $fields;
    }
    
    /**
     * Translate checkout strings to Norwegian
     *
     * @param string $translated Translated string
     * @param string $text Original string
     * @param string $domain Text domain
     * @return string Translated string
     */
    public function translate_checkout_strings(string $translated, string $text, string $domain): string {
        // Translate everywhere on frontend (not in admin)
        if (is_admin()) {
            return $translated;
        }
        
        $translations = [
            // Checkout headings
            'Checkout' => 'Kasse',
            'Billing details' => 'Dine opplysninger',
            'Billing address' => 'Fakturaadresse',
            'Contact information' => 'Kontaktinformasjon',
            'Your order' => 'Din støtte',
            'Order summary' => 'Oppsummering',
            'Payment options' => 'Betalingsmåte',
            'Payment' => 'Betaling',
            
            // Form labels
            'Email address' => 'E-postadresse',
            'E-postadresse' => 'E-postadresse',
            'First name' => 'Fornavn',
            'Last name' => 'Etternavn',
            'Phone' => 'Telefon',
            'Phone (optional)' => 'Telefon (valgfritt)',
            'Company (optional)' => 'Firma (valgfritt)',
            'Address' => 'Adresse',
            'Address line 2 (optional)' => 'Adresselinje 2 (valgfritt)',
            'City' => 'Sted',
            'State / County' => 'Fylke',
            'ZIP Code' => 'Postnummer',
            'Country / Region' => 'Land',
            'Postcode / ZIP' => 'Postnummer',
            'Edit' => 'Endre',
            
            // Contact info descriptions
            "We'll use this email to send you details and updates about your order." => 'Vi sender kvittering og oppdateringer til denne e-postadressen.',
            'Enter the billing address that matches your payment method.' => '',
            
            // Buttons
            'Place order' => 'Fullfør betaling',
            'Place Order' => 'Fullfør betaling',
            'Sign up now' => 'Start abonnement',
            'Return to Basket' => 'Tilbake til handlekurv',
            'Return to basket' => 'Tilbake til handlekurv',
            'Return to cart' => 'Tilbake til handlekurv',
            
            // Payment methods
            'Credit / Debit Card' => 'Bankkort',
            'Credit/Debit Card' => 'Bankkort',
            'Credit card' => 'Bankkort',
            'Debit card' => 'Bankkort',
            'Card number' => 'Kortnummer',
            'Expiry date' => 'Utløpsdato',
            'Security code' => 'CVC-kode',
            'MM / YY' => 'MM / ÅÅ',
            'CVC' => 'CVC',
            'Save payment information to my account for future purchases.' => 'Lagre kortinformasjon for fremtidige betalinger',
            'By providing your card information, you allow Min Sponsor to charge your card for future payments in accordance with their terms.' => 'Ved å oppgi kortinformasjon godtar du at Min Sponsor belaster kortet for fremtidige betalinger.',
            
            // Stripe test mode text (hide most of it)
            'Test mode:' => 'Testmodus:',
            'Test mode' => 'Testmodus',
            'use the test VISA card 4242424242424242 with any expiry date and CVC.' => 'bruk testkortet 4242 4242 4242 4242 med valgfri utløpsdato og CVC.',
            'Other payment methods may redirect to a Stripe test page to authorize payment. More test card numbers are listed here.' => '',
            'More test card numbers are listed here.' => '',
            
            // Order info
            'Subtotal' => 'Delsum',
            'Total' => 'Totalt',
            'Total due today' => 'Å betale i dag',
            'Monthly recurring total' => 'Månedlig trekk',
            'Recurring total' => 'Månedlig trekk',
            'every month' => 'hver måned',
            'per month' => '/måned',
            '/month' => '/mnd',
            'First renewal:' => 'Neste trekk:',
            'Renews' => 'Fornyes',
            'due today' => 'i dag',
            
            // Subscription specific
            'Starting:' => 'Starter:',
            'Details' => 'Detaljer',
            'Subscription' => 'Abonnement',
            'subscription' => 'abonnement',
            'Sign up' => 'Start abonnement',
            'Recurring totals' => 'Månedlig beløp',
            
            // Express checkout
            'Express Checkout' => 'Hurtigbetaling',
            'Or continue below' => 'Eller fortsett under',
            'Pay with' => 'Betal med',
            
            // Coupons
            'Add coupons' => 'Har du en rabattkode?',
            'Apply coupon' => 'Bruk rabattkode',
            'Coupon code' => 'Rabattkode',
            'Apply' => 'Bruk',
            
            // Order notes
            'Add a note to your order' => 'Legg til en melding til oss',
            'Notes about your order, e.g. special notes for delivery.' => 'Meldinger om din bestilling.',
            'Order notes' => 'Ordrekommentarer',
            
            // Terms and privacy
            'By proceeding with your purchase you agree to our Terms and Conditions and Privacy Policy' => 'Ved å fullføre godtar du våre vilkår og personvernerklæring',
            'Terms and Conditions' => 'vilkår',
            'Privacy Policy' => 'personvernerklæring',
            'I have read and agree to the website' => 'Jeg har lest og godtar nettstedets',
            'terms and conditions' => 'vilkår og betingelser',
            
            // Validation messages
            'is a required field' => 'er påkrevd',
            'is a required field.' => 'er påkrevd.',
            'Please enter a valid email address.' => 'Vennligst oppgi en gyldig e-postadresse.',
            'Please enter a valid phone number.' => 'Vennligst oppgi et gyldig telefonnummer.',
            'Please enter your first name.' => 'Vennligst oppgi fornavnet ditt.',
            'Please enter your last name.' => 'Vennligst oppgi etternavnet ditt.',
            'Please complete all required fields.' => 'Vennligst fyll ut alle obligatoriske felt.',
            'There was an error processing your order. Please try again.' => 'Det oppsto en feil. Vennligst prøv igjen.',
            
            // Cart
            'Cart' => 'Handlekurv',
            'Your cart is currently empty.' => 'Handlekurven din er tom.',
            'Continue shopping' => 'Fortsett å handle',
            'Proceed to checkout' => 'Gå til kassen',
            'Update cart' => 'Oppdater handlekurv',
            'Remove item' => 'Fjern',
            'Quantity' => 'Antall',
            'Product' => 'Produkt',
            'Price' => 'Pris',
            
            // Thank you / order received
            'Thank you. Your order has been received.' => 'Takk! Din bestilling er mottatt.',
            'Order received' => 'Bestilling mottatt',
            'Order number:' => 'Ordrenummer:',
            'Date:' => 'Dato:',
            'Email:' => 'E-post:',
            'Total:' => 'Totalt:',
            'Payment method:' => 'Betalingsmåte:',
            
            // Account
            'My account' => 'Min konto',
            'Log in' => 'Logg inn',
            'Log out' => 'Logg ut',
            'Register' => 'Registrer',
            'Lost your password?' => 'Glemt passord?',
            'Remember me' => 'Husk meg',
            'Username or email address' => 'Brukernavn eller e-postadresse',
            'Password' => 'Passord',
            
            // General
            'Shop' => 'Butikk',
            'Search' => 'Søk',
            'Home' => 'Hjem',
            'Read more' => 'Les mer',
            'Add to cart' => 'Legg i handlekurv',
            'View cart' => 'Se handlekurv',
            'Products' => 'Produkter',
            'Loading...' => 'Laster...',
            'Please wait...' => 'Vennligst vent...',
            'Processing...' => 'Behandler...',
            
            // MinSponsor specific
            'Støtt spiller' => 'Støtt spiller',
            'Support' => 'Støtt',
            'Sponsor' => 'Sponsor',
            'Monthly' => 'Månedlig',
            'One-time' => 'Engang',
        ];
        
        // Check for exact match first
        if (isset($translations[$text])) {
            return $translations[$text];
        }
        
        // Also translate the translated string (some plugins translate first)
        if (isset($translations[$translated])) {
            return $translations[$translated];
        }
        
        return $translated;
    }
    
    /**
     * Translate plural strings
     *
     * @param string $translated Translated string
     * @param string $single Singular form
     * @param string $plural Plural form
     * @param int $number Number
     * @param string $domain Text domain
     * @return string Translated string
     */
    public function translate_checkout_strings_plural(string $translated, string $single, string $plural, int $number, string $domain): string {
        if (!is_checkout() && !is_cart()) {
            return $translated;
        }
        
        // Handle plural forms
        if ($single === '%s item' || $plural === '%s items') {
            return $number === 1 ? '1 produkt' : $number . ' produkter';
        }
        
        return $translated;
    }
    
    /**
     * Add trust signals before payment section
     */
    public function add_trust_signals(): void {
        if (!$this->cart_has_minsponsor_items()) {
            return;
        }
        
        ?>
        <div class="minsponsor-trust-signals">
            <div class="trust-item">
                <svg class="trust-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <span>Sikker betaling med kryptering</span>
            </div>
            <div class="trust-item">
                <svg class="trust-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                <span>Trygg håndtering av dine data</span>
            </div>
            <div class="trust-item">
                <svg class="trust-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Avslutt når som helst</span>
            </div>
            <div class="trust-logos">
                <span class="powered-by">Drevet av</span>
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/stripe-badge.svg" alt="Stripe" class="payment-logo" onerror="this.style.display='none'">
            </div>
        </div>
        <?php
    }
    
    /**
     * Add subscription info banner at top of checkout
     */
    public function add_subscription_banner(): void {
        if (!$this->cart_has_subscription()) {
            return;
        }
        
        $cart = WC()->cart;
        $player_name = '';
        $team_name = '';
        $club_name = '';
        $recipient_type = '';
        $amount = 0;
        
        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['minsponsor_player_name'])) {
                $player_name = $cart_item['minsponsor_player_name'];
            }
            if (isset($cart_item['minsponsor_team_name'])) {
                $team_name = $cart_item['minsponsor_team_name'];
            }
            if (isset($cart_item['minsponsor_club_name'])) {
                $club_name = $cart_item['minsponsor_club_name'];
            }
            if (isset($cart_item['minsponsor_recipient_type'])) {
                $recipient_type = $cart_item['minsponsor_recipient_type'];
            }
            if (isset($cart_item['minsponsor_amount'])) {
                $amount = (float) $cart_item['minsponsor_amount'];
            }
        }
        
        // Infer recipient type if not set (for backwards compatibility)
        if (!$recipient_type) {
            if ($player_name) {
                $recipient_type = 'spiller';
            } elseif ($team_name) {
                $recipient_type = 'lag';
            } elseif ($club_name) {
                $recipient_type = 'klubb';
            }
        }
        
        // Determine the primary recipient name based on recipient type
        $recipient_name = '';
        if ($recipient_type === 'spiller' && $player_name) {
            $recipient_name = $player_name;
        } elseif ($recipient_type === 'lag' && $team_name) {
            $recipient_name = $team_name;
        } elseif ($recipient_type === 'klubb' && $club_name) {
            $recipient_name = $club_name;
        } elseif ($player_name) {
            $recipient_name = $player_name;
        } elseif ($team_name) {
            $recipient_name = $team_name;
        } elseif ($club_name) {
            $recipient_name = $club_name;
        }
        
        // Build hierarchy breadcrumb showing parent levels
        $hierarchy_parts = [];
        if ($recipient_type === 'spiller') {
            // For players: show Club › Team
            if ($club_name) {
                $hierarchy_parts[] = $club_name;
            }
            if ($team_name) {
                $hierarchy_parts[] = $team_name;
            }
        } elseif ($recipient_type === 'lag') {
            // For teams: show Club
            if ($club_name) {
                $hierarchy_parts[] = $club_name;
            }
        }
        // For clubs: no hierarchy needed
        
        if ($amount > 0) {
            ?>
            <div class="minsponsor-subscription-banner">
                <div class="banner-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </div>
                <div class="banner-content">
                    <strong>Du støtter <?php echo esc_html($recipient_name); ?></strong>
                    <?php if (!empty($hierarchy_parts)): ?>
                        <span class="hierarchy"><?php echo esc_html(implode(' › ', $hierarchy_parts)); ?></span>
                    <?php endif; ?>
                    <span class="amount">kr <?php echo number_format($amount, 0, ',', ' '); ?> trekkes hver måned</span>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Add cancel anytime notice near total
     */
    public function add_cancel_anytime_notice(): void {
        if (!$this->cart_has_subscription()) {
            return;
        }
        
        ?>
        <tr class="minsponsor-cancel-notice">
            <td colspan="2">
                <small class="cancel-text">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" style="display:inline;vertical-align:middle;margin-right:4px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Du kan avslutte abonnementet når som helst via lenke i e-posten
                </small>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Filter payment gateways based on cart contents
     *
     * @param array $gateways Available gateways
     * @return array Filtered gateways
     */
    public function filter_payment_gateways(array $gateways): array {
        if (!is_checkout()) {
            return $gateways;
        }
        
        // For subscriptions, remove payment methods that don't support recurring
        if ($this->cart_has_subscription()) {
            // Klarna doesn't support subscriptions well - remove it
            unset($gateways['stripe_klarna']);
            unset($gateways['klarna']);
            
            // Keep only Stripe card and Vipps Recurring
            $allowed = ['stripe', 'vipps_recurring', 'vipps_mobilepay_recurring'];
            foreach ($gateways as $id => $gateway) {
                if (!in_array($id, $allowed) && strpos($id, 'stripe') === false) {
                    // Allow stripe-based gateways
                    if (strpos($id, 'stripe') === false || $id === 'stripe_klarna') {
                        unset($gateways[$id]);
                    }
                }
            }
        }
        
        return $gateways;
    }
    
    /**
     * Add custom checkout styles
     */
    public function add_checkout_styles(): void {
        if (!is_checkout()) {
            return;
        }
        
        ?>
        <style>
            /* ===========================================
               MinSponsor Checkout - Designsystem
               Farger: Korall, Terrakotta, Beige, Brun
               =========================================== */
            
            :root {
                --ms-korall: #F6A586;
                --ms-terrakotta: #D97757;
                --ms-terrakotta-dark: #B85D42;
                --ms-beige: #F5EFE6;
                --ms-krem: #FBF8F3;
                --ms-brun: #3D3228;
                --ms-brun-light: #5A4D3F;
                --ms-softgra: #E8E2D9;
                --ms-gul: #F4C85E;
                --ms-shadow: 0 4px 20px rgba(61, 50, 40, 0.08);
            }
            
            /* Page background */
            .woocommerce-checkout,
            body.woocommerce-checkout {
                background-color: var(--ms-beige) !important;
            }
            
            /* Replace "Checkout" heading with "Kasse" using CSS */
            main h1.text-3xl,
            .entry-content h1:first-child,
            .woocommerce-checkout h1 {
                visibility: hidden;
                position: relative;
                height: 48px;
                color: var(--ms-brun) !important;
            }
            main h1.text-3xl::after,
            .entry-content h1:first-child::after,
            .woocommerce-checkout h1::after {
                content: 'Kasse';
                visibility: visible;
                position: absolute;
                left: 0;
                top: 0;
                font-weight: 700;
                font-size: 36px;
                color: var(--ms-brun);
            }
            
            /* Subscription banner - Korall/Terrakotta gradient */
            .minsponsor-subscription-banner {
                display: flex;
                align-items: center;
                gap: 16px;
                background: linear-gradient(135deg, var(--ms-korall) 0%, var(--ms-terrakotta) 100%);
                color: var(--ms-krem);
                padding: 20px 24px;
                border-radius: 16px;
                margin-bottom: 24px;
                box-shadow: var(--ms-shadow);
            }
            
            .minsponsor-subscription-banner .banner-icon {
                flex-shrink: 0;
                background: rgba(255,255,255,0.2);
                border-radius: 50%;
                padding: 12px;
            }
            
            .minsponsor-subscription-banner .banner-content {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }
            
            .minsponsor-subscription-banner strong {
                font-size: 18px;
            }
            
            .minsponsor-subscription-banner .hierarchy {
                opacity: 0.85;
                font-size: 13px;
                font-weight: 500;
            }
            
            .minsponsor-subscription-banner .amount {
                opacity: 0.9;
                font-size: 14px;
            }
            
            /* Trust signals - Krem bakgrunn */
            .minsponsor-trust-signals {
                display: flex;
                flex-wrap: wrap;
                gap: 16px;
                padding: 20px;
                background: var(--ms-krem);
                border-radius: 12px;
                margin-bottom: 20px;
                border: 1px solid var(--ms-softgra);
            }
            
            .minsponsor-trust-signals .trust-item {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 13px;
                color: var(--ms-brun-light);
            }
            
            .minsponsor-trust-signals .trust-icon {
                color: var(--ms-terrakotta);
                flex-shrink: 0;
            }
            
            .minsponsor-trust-signals .trust-logos {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-left: auto;
            }
            
            .minsponsor-trust-signals .powered-by {
                font-size: 11px;
                color: var(--ms-brun-light);
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .minsponsor-trust-signals .payment-logo {
                height: 24px;
                opacity: 0.7;
            }
            
            /* Cancel notice */
            .minsponsor-cancel-notice td {
                padding-top: 12px !important;
                border-top: 1px solid var(--ms-softgra);
            }
            
            .minsponsor-cancel-notice .cancel-text {
                color: var(--ms-brun-light);
                display: flex;
                align-items: center;
            }
            
            .minsponsor-cancel-notice-block {
                padding: 12px 16px;
                background: var(--ms-krem);
                border-radius: 8px;
                margin-top: 16px;
                color: var(--ms-brun-light);
                font-size: 13px;
                border: 1px solid var(--ms-softgra);
            }
            
            /* Hide Express Checkout section for MinSponsor */
            .wc-block-components-express-payment,
            .wc-block-checkout__payment-method .wc-block-components-express-payment,
            .wc-block-components-express-payment-continue-rule {
                display: none !important;
            }
            
            /* Hide billing address (we only need email + name) */
            .wc-block-checkout__billing-fields .wc-block-components-address-form__company,
            .wc-block-checkout__billing-fields .wc-block-components-address-form__address_1,
            .wc-block-checkout__billing-fields .wc-block-components-address-form__address_2,
            .wc-block-checkout__billing-fields .wc-block-components-address-form__city,
            .wc-block-checkout__billing-fields .wc-block-components-address-form__state,
            .wc-block-checkout__billing-fields .wc-block-components-address-form__postcode,
            .wc-block-checkout__billing-fields .wc-block-components-address-form__country,
            .wc-block-checkout__billing-fields .wc-block-components-address-form__phone {
                display: none !important;
            }
            
            /* Keep the billing address heading but hide the description */
            .wc-block-checkout__billing-fields .wc-block-components-checkout-step__description {
                display: none !important;
            }
            
            /* Better heading for billing section */
            .wc-block-checkout__billing-fields .wc-block-components-checkout-step__heading {
                margin-bottom: 16px;
            }
            
            /* Shipping methods - hide for virtual products */
            .wc-block-checkout__shipping-option,
            .wc-block-checkout__shipping-method,
            .wc-block-checkout__pickup-options {
                display: none !important;
            }
            
            /* Better form styling - MinSponsor design */
            .wc-block-components-text-input input,
            .wc-block-components-select .wc-block-components-select__select {
                background-color: var(--ms-krem) !important;
                border: 1px solid var(--ms-softgra) !important;
                border-radius: 8px !important;
                padding: 14px 18px !important;
                font-size: 16px !important;
                color: var(--ms-brun) !important;
                transition: border-color 0.2s, box-shadow 0.2s !important;
            }
            
            .wc-block-components-text-input input:focus,
            .wc-block-components-select .wc-block-components-select__select:focus {
                border-color: var(--ms-terrakotta) !important;
                outline: none !important;
                box-shadow: 0 0 0 3px rgba(217, 119, 87, 0.1) !important;
            }
            
            /* Payment method styling */
            .wc-block-components-radio-control__option {
                background-color: var(--ms-krem) !important;
                border: 1px solid var(--ms-softgra) !important;
                margin-bottom: 8px !important;
                border-radius: 8px !important;
                padding: 16px !important;
            }
            
            .wc-block-components-radio-control__option--checked {
                border-color: var(--ms-terrakotta) !important;
                background: rgba(217, 119, 87, 0.05) !important;
            }
            
            /* Order button - Terrakotta CTA */
            .wc-block-components-checkout-place-order-button,
            .wc-block-checkout__actions button {
                background: var(--ms-terrakotta) !important;
                border: none !important;
                border-radius: 8px !important;
                padding: 16px 32px !important;
                font-size: 16px !important;
                font-weight: 600 !important;
                text-transform: none !important;
                letter-spacing: 0 !important;
                color: var(--ms-krem) !important;
                box-shadow: var(--ms-shadow) !important;
                transition: all 0.2s !important;
                width: 100% !important;
            }
            
            .wc-block-components-checkout-place-order-button:hover,
            .wc-block-checkout__actions button:hover {
                background: var(--ms-terrakotta-dark) !important;
                transform: translateY(-1px) !important;
                box-shadow: 0 6px 24px rgba(217, 119, 87, 0.3) !important;
            }
            
            /* Order summary styling */
            .wc-block-components-order-summary {
                background: var(--ms-krem);
                border-radius: 16px;
                padding: 20px;
                border: 1px solid var(--ms-softgra);
            }
            
            /* Total styling */
            .wc-block-components-totals-footer-item {
                font-size: 18px !important;
                font-weight: 700 !important;
                color: var(--ms-brun) !important;
            }
            
            /* Recurring total styling */
            .wc-block-components-totals-item--recurring {
                background: rgba(217, 119, 87, 0.08);
                padding: 12px;
                border-radius: 8px;
                margin-top: 8px;
            }
            
            /* Return to basket link */
            .wc-block-components-checkout-return-to-cart-button {
                color: var(--ms-brun-light) !important;
            }
            
            .wc-block-components-checkout-return-to-cart-button:hover {
                color: var(--ms-terrakotta) !important;
            }
            
            /* Checkout card container */
            .wc-block-checkout__main,
            .wc-block-checkout__sidebar {
                background-color: var(--ms-krem) !important;
                border-radius: 16px !important;
                padding: 24px !important;
                box-shadow: var(--ms-shadow) !important;
            }
            
            /* Headings in checkout */
            .wc-block-components-checkout-step__heading,
            .wc-block-components-title {
                color: var(--ms-brun) !important;
                font-weight: 600 !important;
            }
            
            /* Links */
            .wc-block-checkout a {
                color: var(--ms-terrakotta) !important;
            }
            
            .wc-block-checkout a:hover {
                color: var(--ms-terrakotta-dark) !important;
            }
            
            /* Mobile responsive */
            @media (max-width: 768px) {
                .minsponsor-trust-signals {
                    flex-direction: column;
                    gap: 12px;
                }
                
                .minsponsor-trust-signals .trust-logos {
                    margin-left: 0;
                    margin-top: 8px;
                    padding-top: 12px;
                    border-top: 1px solid var(--ms-softgra);
                    width: 100%;
                    justify-content: center;
                }
                
                .minsponsor-subscription-banner {
                    flex-direction: column;
                    text-align: center;
                }
                
                .wc-block-checkout {
                    padding: 16px !important;
                }
            }
            
            /* Hide "Create an account" for guest checkout */
            .wc-block-checkout__create-account,
            .wc-block-checkout__use-address-for-shipping {
                display: none !important;
            }
            
            /* Style Stripe test mode notice - will be hidden in production */
            .wc-block-components-radio-control-accordion-option__content > div:first-child:not(:has(iframe)) {
                font-size: 11px !important;
                background: #fef3c7 !important;
                border-radius: 6px !important;
                padding: 8px 12px !important;
                margin-bottom: 12px !important;
                color: #92400e !important;
            }
            
            /* Hide the "More test card" link since it's only for devs */
            .wc-block-components-radio-control-accordion-option__content a[href*="stripe.com/testing"] {
                display: none !important;
            }
            
            /* Style payment consent text */
            .wc-block-components-radio-control-accordion-option__content > div:last-of-type:not(:has(iframe)) {
                font-size: 11px !important;
                color: #6b7280 !important;
            }
        </style>
        <?php
    }
    
    /**
     * Check if cart contains MinSponsor items
     *
     * @return bool
     */
    private function cart_has_minsponsor_items(): bool {
        if (!WC()->cart) {
            return false;
        }
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['minsponsor_player_id']) || 
                isset($cart_item['minsponsor_interval'])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if cart contains subscription products
     *
     * @return bool
     */
    private function cart_has_subscription(): bool {
        if (!WC()->cart) {
            return false;
        }
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['minsponsor_interval']) && 
                $cart_item['minsponsor_interval'] === 'month') {
                return true;
            }
            
            // Also check if product itself is a subscription
            $product = $cart_item['data'] ?? null;
            if ($product && class_exists('WC_Subscriptions_Product')) {
                if (\WC_Subscriptions_Product::is_subscription($product)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Maybe hide billing address for virtual products (block checkout)
     *
     * @param bool $requires_billing Whether billing address is required
     * @return bool
     */
    public function maybe_hide_billing_address(bool $requires_billing): bool {
        if ($this->cart_has_minsponsor_items()) {
            // For MinSponsor items, we don't need full billing address
            // But we still need it for Stripe - return true but we'll hide fields with CSS
            return true;
        }
        return $requires_billing;
    }
    
    /**
     * Add block checkout enhancements via JavaScript
     */
    public function add_block_checkout_enhancements(): void {
        if (!is_checkout()) {
            return;
        }
        
        if (!$this->cart_has_minsponsor_items()) {
            return;
        }
        
        $is_subscription = $this->cart_has_subscription();
        $cart = WC()->cart;
        $player_name = '';
        $team_name = '';
        $club_name = '';
        $recipient_type = '';
        $amount = 0;
        
        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['minsponsor_player_name'])) {
                $player_name = $cart_item['minsponsor_player_name'];
            }
            if (isset($cart_item['minsponsor_team_name'])) {
                $team_name = $cart_item['minsponsor_team_name'];
            }
            if (isset($cart_item['minsponsor_club_name'])) {
                $club_name = $cart_item['minsponsor_club_name'];
            }
            if (isset($cart_item['minsponsor_recipient_type'])) {
                $recipient_type = $cart_item['minsponsor_recipient_type'];
            }
            if (isset($cart_item['minsponsor_amount'])) {
                $amount = (float) $cart_item['minsponsor_amount'];
            }
        }
        
        // Infer recipient type if not set
        if (!$recipient_type) {
            if ($player_name) {
                $recipient_type = 'spiller';
            } elseif ($team_name) {
                $recipient_type = 'lag';
            } elseif ($club_name) {
                $recipient_type = 'klubb';
            }
        }
        
        // Determine recipient name and hierarchy for display
        $recipient_name = '';
        $hierarchy_html = '';
        
        if ($recipient_type === 'spiller' && $player_name) {
            $recipient_name = $player_name;
            // Build hierarchy: Club › Team
            $parts = [];
            if ($club_name) $parts[] = $club_name;
            if ($team_name) $parts[] = $team_name;
            if (!empty($parts)) {
                $hierarchy_html = '<span class="hierarchy">' . esc_html(implode(' › ', $parts)) . '</span>';
            }
        } elseif ($recipient_type === 'lag' && $team_name) {
            $recipient_name = $team_name;
            // Build hierarchy: Club
            if ($club_name) {
                $hierarchy_html = '<span class="hierarchy">' . esc_html($club_name) . '</span>';
            }
        } elseif ($recipient_type === 'klubb' && $club_name) {
            $recipient_name = $club_name;
            // No hierarchy for clubs
        } else {
            // Fallback
            $recipient_name = $player_name ?: $team_name ?: $club_name;
        }
        
        ?>
        <script>
        (function() {
            'use strict';
            
            // Norwegian translations for dynamic text replacement
            var norwegianTranslations = {
                // Headings and labels
                'Checkout': 'Kasse',
                'Contact information': 'Kontaktinformasjon',
                'Billing address': 'Fakturaadresse',
                'Order summary': 'Oppsummering',
                'Payment options': 'Betalingsmåte',
                'Add coupons': 'Har du en rabattkode?',
                'Add a note to your order': 'Legg til en melding',
                
                // Form fields
                'Email address': 'E-postadresse',
                'First name': 'Fornavn',
                'Last name': 'Etternavn',
                'Card number': 'Kortnummer',
                'Expiry date': 'Utløpsdato',
                'Security code': 'CVC-kode',
                'Edit': 'Endre',
                
                // Descriptions
                "We'll use this email to send you details and updates about your order.": 'Vi sender kvittering og oppdateringer til denne e-postadressen.',
                
                // Payment
                'Credit / Debit Card': 'Bankkort',
                'Credit/Debit Card': 'Bankkort',
                
                // Totals
                'Subtotal': 'Delsum',
                'Total': 'Totalt',
                'Total due today': 'Å betale i dag',
                'every month': 'hver måned',
                
                // Buttons and links
                'Return to Basket': 'Tilbake til handlekurv',
                'Return to basket': 'Tilbake til handlekurv',
                
                // Terms
                'By proceeding with your purchase you agree to our Terms and Conditions and Privacy Policy': 'Ved å fullføre godtar du våre vilkår og personvernerklæring',
                'Terms and Conditions': 'vilkår',
                'Privacy Policy': 'personvernerklæring',
                
                // Test mode - hide long English text
                'Test mode:': 'Testmodus:',
                'use the test VISA card 4242424242424242 with any expiry date and CVC.': 'bruk testkortet 4242 4242 4242 4242.',
                'Other payment methods may redirect to a Stripe test page to authorize payment. More test card numbers are listed here.': '',
                'More test card numbers are listed here.': '',
                'By providing your card information, you allow Min Sponsor to charge your card for future payments in accordance with their terms.': 'Ved å oppgi kortinformasjon godtar du fremtidige betalinger.',
            };
            
            // Direct replacements for specific elements
            function translateSpecificElements() {
                // Fix the main Checkout heading (handled by CSS now, but backup)
                var h1 = document.querySelector('main h1');
                if (h1 && h1.textContent.trim() === 'Checkout') {
                    h1.textContent = 'Kasse';
                }
                
                // Fix Stripe test mode message - replace the entire content
                var stripeContent = document.querySelectorAll('.wc-block-components-radio-control-accordion-option__content');
                stripeContent.forEach(function(el) {
                    var text = el.textContent;
                    if (text && (text.includes('Test mode') || text.includes('4242'))) {
                        // Find the test mode div
                        var testModeDiv = el.querySelector('div');
                        if (testModeDiv && testModeDiv.textContent.includes('Test mode')) {
                            testModeDiv.innerHTML = '<strong>Testmodus:</strong> bruk testkortet 4242 4242 4242 4242 med valgfri utløpsdato og CVC.';
                        }
                    }
                    
                    // Also translate the consent text
                    if (text && text.includes('By providing your card information')) {
                        var consentDiv = Array.from(el.querySelectorAll('div')).find(d => d.textContent.includes('By providing'));
                        if (consentDiv) {
                            consentDiv.textContent = 'Ved å oppgi kortinformasjon godtar du fremtidige betalinger.';
                        }
                    }
                });
                
                // Also check for test mode text in any element
                document.querySelectorAll('*').forEach(function(el) {
                    if (el.childNodes.length === 1 && el.childNodes[0].nodeType === 3) {
                        var text = el.textContent;
                        if (text.includes('use the test VISA card')) {
                            el.innerHTML = '<strong>Testmodus:</strong> bruk testkortet 4242 4242 4242 4242 med valgfri utløpsdato og CVC.';
                        }
                        if (text.includes('More test card numbers are listed here')) {
                            el.textContent = '';
                        }
                        if (text.includes('By providing your card information')) {
                            el.textContent = 'Ved å oppgi kortinformasjon godtar du fremtidige betalinger.';
                        }
                    }
                });
            }
            
            // Function to translate text nodes
            function translateTextNodes(element) {
                var walker = document.createTreeWalker(
                    element,
                    NodeFilter.SHOW_TEXT,
                    null,
                    false
                );
                
                var node;
                while (node = walker.nextNode()) {
                    var text = node.textContent.trim();
                    if (text && norwegianTranslations[text]) {
                        node.textContent = node.textContent.replace(text, norwegianTranslations[text]);
                    }
                }
            }
            
            // Function to translate placeholders and labels
            function translateAttributes(element) {
                // Translate placeholders
                element.querySelectorAll('[placeholder]').forEach(function(el) {
                    var placeholder = el.getAttribute('placeholder');
                    if (norwegianTranslations[placeholder]) {
                        el.setAttribute('placeholder', norwegianTranslations[placeholder]);
                    }
                });
                
                // Translate aria-labels
                element.querySelectorAll('[aria-label]').forEach(function(el) {
                    var label = el.getAttribute('aria-label');
                    if (norwegianTranslations[label]) {
                        el.setAttribute('aria-label', norwegianTranslations[label]);
                    }
                });
            }
            
            // Wait for DOM to be ready
            function initMinSponsorCheckout() {
                var checkoutForm = document.querySelector('.wc-block-checkout');
                if (!checkoutForm) return;
                
                // Translate specific elements first (h1, test mode, etc)
                translateSpecificElements();
                
                // Translate existing text nodes
                translateTextNodes(checkoutForm);
                translateAttributes(checkoutForm);
                
                // Add subscription banner at top of checkout
                <?php if ($is_subscription && $amount > 0 && $recipient_name): ?>
                if (!document.querySelector('.minsponsor-subscription-banner')) {
                    var banner = document.createElement('div');
                    banner.className = 'minsponsor-subscription-banner';
                    banner.innerHTML = `
                        <div class="banner-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <div class="banner-content">
                            <strong>Du støtter <?php echo esc_js($recipient_name); ?></strong>
                            <?php echo $hierarchy_html; ?>
                            <span class="amount">kr <?php echo number_format($amount, 0, ',', ' '); ?> trekkes hver måned</span>
                        </div>
                    `;
                    checkoutForm.parentNode.insertBefore(banner, checkoutForm);
                }
                <?php endif; ?>
                
                // Add trust signals before payment section
                var paymentBlock = document.querySelector('.wc-block-checkout__payment-method');
                if (paymentBlock && !document.querySelector('.minsponsor-trust-signals')) {
                    var trustSignals = document.createElement('div');
                    trustSignals.className = 'minsponsor-trust-signals';
                    trustSignals.innerHTML = `
                        <div class="trust-item">
                            <svg class="trust-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <span>Sikker betaling med kryptering</span>
                        </div>
                        <div class="trust-item">
                            <svg class="trust-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            <span>Trygg håndtering av dine data</span>
                        </div>
                        <div class="trust-item">
                            <svg class="trust-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Avslutt når som helst</span>
                        </div>
                    `;
                    paymentBlock.parentNode.insertBefore(trustSignals, paymentBlock);
                }
                
                // Add cancel notice after order summary
                <?php if ($is_subscription): ?>
                var orderSummary = document.querySelector('.wc-block-components-order-summary');
                if (orderSummary && !document.querySelector('.minsponsor-cancel-notice-block')) {
                    var notice = document.createElement('div');
                    notice.className = 'minsponsor-cancel-notice-block';
                    notice.innerHTML = `
                        <small>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" style="display:inline;vertical-align:middle;margin-right:4px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Du kan avslutte abonnementet når som helst via lenke i e-posten
                        </small>
                    `;
                    orderSummary.parentNode.insertBefore(notice, orderSummary.nextSibling);
                }
                <?php endif; ?>
            }
            
            // Run when DOM is ready and on mutation
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initMinSponsorCheckout);
            } else {
                initMinSponsorCheckout();
            }
            
            // Also observe for React re-renders and translate new content
            var observer = new MutationObserver(function(mutations) {
                initMinSponsorCheckout();
                
                // Re-translate on any DOM change
                var checkoutForm = document.querySelector('.wc-block-checkout');
                if (checkoutForm) {
                    translateTextNodes(checkoutForm);
                    translateAttributes(checkoutForm);
                }
            });
            
            setTimeout(function() {
                var target = document.querySelector('.wc-block-checkout');
                if (target) {
                    observer.observe(target, { childList: true, subtree: true, characterData: true });
                }
                initMinSponsorCheckout();
            }, 500);
            
            // Also run periodically for a few seconds to catch late renders
            var runCount = 0;
            var interval = setInterval(function() {
                initMinSponsorCheckout();
                var checkoutForm = document.querySelector('.wc-block-checkout');
                if (checkoutForm) {
                    translateTextNodes(checkoutForm);
                    translateAttributes(checkoutForm);
                }
                runCount++;
                if (runCount > 10) {
                    clearInterval(interval);
                }
            }, 300);
        })();
        </script>
        <?php
    }
    
    /**
     * Translate block checkout i18n
     *
     * @param array $i18n Translations array
     * @return array Modified translations
     */
    public function translate_block_checkout(array $i18n): array {
        $i18n['checkout'] = 'Kasse';
        $i18n['billing_address'] = 'Fakturaadresse';
        $i18n['contact_information'] = 'Kontaktinformasjon';
        $i18n['order_summary'] = 'Oppsummering';
        $i18n['payment_options'] = 'Betalingsmåte';
        return $i18n;
    }
    
    /**
     * Enqueue translation script for block checkout
     */
    public function enqueue_translation_script(): void {
        if (!is_checkout()) {
            return;
        }
        
        // Add inline script to handle WooCommerce blocks translations
        wp_add_inline_script(
            'wc-blocks-checkout',
            '
            // Override WooCommerce block checkout translations
            if (window.wp && window.wp.i18n) {
                wp.i18n.setLocaleData({
                    "Checkout": ["Kasse"],
                    "Contact information": ["Kontaktinformasjon"],
                    "Billing address": ["Fakturaadresse"],
                    "Order summary": ["Oppsummering"],
                    "Payment options": ["Betalingsmåte"],
                    "Email address": ["E-postadresse"],
                    "First name": ["Fornavn"],
                    "Last name": ["Etternavn"],
                    "Return to Basket": ["Tilbake til handlekurv"],
                    "Place Order": ["Fullfør betaling"],
                    "Subtotal": ["Delsum"],
                    "Total": ["Totalt"],
                    "Add coupons": ["Har du en rabattkode?"],
                }, "woocommerce");
            }
            ',
            'before'
        );
    }
}
