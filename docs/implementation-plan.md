# MinSponsor Stripe Connect – Implementeringsplan

> Sist oppdatert: 15. desember 2025

## Status

| Fase | Beskrivelse | Status |
|------|-------------|--------|
| Fase 1 | Stripe-felter på Lag | ✅ Fullført |
| Fase 2 | Innstillinger-side | ✅ Fullført |
| Fase 3 | Onboarding-flow | ✅ Fullført |
| Fase 4 | Checkout-gate | ✅ Fullført |
| Fase 5 | Betalingsflyt | 🔲 Ikke startet |
| Fase 6 | Webhooks | 🔲 Ikke startet |
| Fase 7 | Deploy & Migrasjon | 🔲 Ikke startet |

---

## ⚠️ FORUTSETNING: Aktiver Stripe Connect

Før Fase 3 kan testes, må Stripe Connect aktiveres i Stripe Dashboard:

1. Gå til https://dashboard.stripe.com/test/connect/accounts/overview
2. Klikk "Get started" for å aktivere Connect
3. Fyll ut nødvendig informasjon om plattformen
4. Vent på godkjenning (umiddelbart i test mode)

Uten Connect aktivert vil koden returnere feil:
> "You can only create new accounts if you've signed up for Connect"

---

## Fase 1: Stripe-felter på Lag

**Mål:** Legg til kasserer-info og Stripe Connect-status på Lag CPT.

### Oppgaver

- [ ] **1.1** Opprett ACF-feltgruppe "Stripe Connect – Kasserer" med:
  - `kasserer_email` (e-post, påkrevd)
  - `kasserer_navn` (tekst)
  - `kasserer_telefon` (tekst)
  - Plassering: Lag CPT

- [ ] **1.2** Opprett `includes/Admin/LagStripeMetaBox.php`:
  - Vis Stripe Connect-status (ikke tilkoblet / påbegynt / aktiv)
  - Vis `stripe_connected_account_id` når tilkoblet
  - Vis `onboarding_status` og `last_checked`
  - Knapper: "Start onboarding", "Kopier lenke", "Refresh status"

- [ ] **1.3** Registrer meta-box i `functions.php` eller autoloader

- [ ] **1.4** Legg til post meta-felter for Stripe-data:
  - `_minsponsor_stripe_account_id`
  - `_minsponsor_stripe_onboarding_status` (not_started|pending|complete)
  - `_minsponsor_stripe_onboarding_link`
  - `_minsponsor_stripe_last_checked`

### Filer å opprette/endre
```
includes/Admin/LagStripeMetaBox.php (ny)
acf-json/group_stripe_kasserer.json (ny, via ACF UI)
functions.php (registrer meta-box)
```

### Akseptansekriterier
- [ ] Lag-redigering viser kasserer-felter
- [ ] Stripe-status meta-box vises i sidebar
- [ ] Meta-felter lagres korrekt

---

## Fase 2: Innstillinger-side

**Mål:** Sentralisert admin-side for MinSponsor-konfigurasjon.

### Oppgaver

- [ ] **2.1** Opprett `includes/Settings/StripeSettings.php`:
  - Registrer admin-meny under "MinSponsor"
  - Tab: Gebyrer (plattformgebyr %, Stripe-sats)
  - Tab: Stripe (miljø, API-nøkler, webhook secret)
  - Tab: Produkter (eksisterende fra PlayerProducts.php)

- [ ] **2.2** Legg til options:
  - `minsponsor_platform_fee_percent` (default: 6)
  - `minsponsor_stripe_environment` (test|live)
  - `minsponsor_stripe_webhook_secret`

- [ ] **2.3** Styling med designsystemet (terrakotta, beige, etc.)

### Filer å opprette/endre
```
includes/Settings/StripeSettings.php (ny)
includes/Settings/PlayerProducts.php (flytt til tab)
functions.php (registrer settings)
```

### Akseptansekriterier
- [ ] MinSponsor → Innstillinger vises i admin-menyen
- [ ] Alle tabs fungerer og lagrer verdier
- [ ] API-nøkler valideres ved lagring

---

## Fase 3: Onboarding-flow

**Mål:** Kasserer kan fullføre Stripe Express-registrering.

**Status:** ✅ Fullført og testet

### Stripe Connect Setup

For at onboarding skal fungere, må Stripe-kontoen ha Connect aktivert:

1. Gå til Stripe Dashboard → Connect → Get started
2. Velg "Onboarding hosted by Stripe" (ikke embedded)
3. Velg "Sellers collect payments directly"
4. Fullfør Express-oppsett

**Testet med:** Stripe sandbox "Teal Carousel" (Connect enabled)

### Implementert

✅ **3.1** `includes/Admin/LagStripeMetaBox.php` oppdatert med:
  - `get_stripe_client()` - Henter Stripe SDK client basert på miljø
  - `ajax_start_onboarding()` - Oppretter Express-konto via Stripe API
  - `create_account_link()` - Genererer onboarding URL med return/refresh
  - `get_callback_base_url()` - Støtter localhost fallback for utvikling
  - Håndterer localhost-URLer (bruker minsponsor.no fallback)
  - Lagrer account_id, status og onboarding_link i post meta

✅ **3.1b** Stripe PHP SDK installert:
  - `composer.json` opprettet
  - stripe/stripe-php v19.0.0 installert
  - Autoloader lastet i `functions.php`

✅ **3.1c** `includes/Settings/StripeSettings.php` oppdatert med:
  - Nytt felt: "Callback Base URL" for lokal utvikling (ngrok-støtte)
  - Dokumentasjon for hvordan bruke ngrok for testing

✅ **3.2** Callback-side for onboarding-retur:
  - Onboarding link åpnes i ny fane
  - "Sjekk status" knapp sjekker status via Stripe API
  - "Kopier onboarding-lenke" for å dele med kasserer

✅ **3.3** Onboarding-lenke tilgjengelig i admin:
  - Lenke vises i meta box når onboarding er påbegynt
  - Kan kopieres og sendes til kasserer manuelt
  - (Automatisk e-post kan legges til senere ved behov)

### Test-verifisering (2025-12-15)

Testet med Lag ID 103 "Gutter 2009":
- ✅ "Start onboarding" knapp oppretter Express-konto
- ✅ Stripe account ID: `acct_1SekAaQjWGMjTPmT`
- ✅ Status endres til "PÅBEGYNT" i UI
- ✅ Onboarding-lenke åpnes i ny fane (Stripe Connect)
- ✅ Post meta lagres korrekt:
  - `_minsponsor_stripe_account_id`
  - `_minsponsor_stripe_onboarding_status`
  - `_minsponsor_stripe_last_checked`
  - `_minsponsor_stripe_onboarding_link`

### Filer opprettet/endret
```
includes/Admin/LagStripeMetaBox.php (endret - ekte Stripe API)
includes/Api/StripeOnboarding.php (opprettet - ikke i bruk, logikk flyttet til MetaBox)
includes/Settings/StripeSettings.php (endret - callback URL felt)
composer.json (ny)
vendor/ (ny - Stripe SDK)
functions.php (endret - Composer autoloader)
```

### Akseptansekriterier
- [x] "Start onboarding" kaller Stripe API
- [x] Express-konto opprettes i Stripe
- [x] Onboarding-lenke åpnes i ny fane
- [ ] Kasserer kan fullføre Stripe-registrering (krever manuell test)
- [ ] Status oppdateres til "complete" etter fullføring
- [x] "Sjekk status" knapp henter oppdatert status fra Stripe
- [x] "Kopier onboarding-lenke" knapp fungerer

---

## Fase 4: Checkout-gate

**Mål:** Blokker kjøp hvis mottaker ikke har aktiv Stripe-konto.

### Oppgaver

- [ ] **4.1** Endre `includes/Frontend/PlayerRoute.php`:
  - Sjekk Stripe-status før redirect til cart
  - Vis feilmelding hvis ikke tilkoblet

- [ ] **4.2** Endre `includes/Checkout/CartPrice.php`:
  - Valider at mottaker har aktiv Stripe ved add_to_cart
  - Fjern produkt hvis status endres

- [ ] **4.3** Legg til brukervenlig feilside:
  - "Dette laget kan dessverre ikke motta støtte ennå"
  - Kontaktinfo eller alternativ handling

### Filer å opprette/endre
```
includes/Frontend/PlayerRoute.php (endre)
includes/Checkout/CartPrice.php (endre)
templates/stripe-not-connected.php (ny)
```

### Akseptansekriterier
- [ ] Kan ikke legge i handlekurv uten aktiv Stripe
- [ ] Bruker ser forklarende feilmelding
- [ ] Eksisterende kurv-items valideres

---

## Fase 5: Betalingsflyt med transfer_data

**Mål:** Penger går til riktig Stripe-konto med korrekt gebyrfordeling.

### Oppgaver

- [ ] **5.1** Endre `includes/Gateways/StripeMeta.php`:
  - Legg til `transfer_data.destination` med lag's account_id
  - Beregn `application_fee_amount` (6% av netto)
  - Inkluder metadata for sporing

- [ ] **5.2** Implementer routing-logikk:
  - Spiller → hent parent lag's Stripe-konto
  - Lag → bruk lag's egen Stripe-konto
  - Klubb → (fremtidig: klubb's konto)

- [ ] **5.3** Gebyrberegning per stripe-connect-spec.md:
  - Sponsor betaler: beløp + 10%
  - Stripe tar: ~2.9% + 2.50 kr
  - Plattform får: 6%
  - Mottaker får: 100% av sponsbeløp

### Filer å opprette/endre
```
includes/Gateways/StripeMeta.php (endre)
includes/Services/FeeCalculator.php (ny)
```

### Akseptansekriterier
- [ ] Payment Intent har korrekt transfer_data
- [ ] application_fee_amount beregnes riktig
- [ ] Stripe Dashboard viser korrekt fordeling
- [ ] Kan verifiseres med Stripe CLI

---

## Fase 6: Webhooks

**Mål:** Håndter Stripe-events for pålitelig statusoppdatering.

### Oppgaver

- [ ] **6.1** Opprett `includes/Api/StripeWebhook.php`:
  - Verifiser webhook-signatur
  - Håndter: `account.updated`, `payment_intent.succeeded`, `charge.refunded`

- [ ] **6.2** Registrer webhook-endpoint:
  - URL: `/wp-json/minsponsor/v1/stripe-webhook`
  - Konfigurer i Stripe Dashboard

- [ ] **6.3** Implementer event-handlers:
  - `account.updated` → oppdater onboarding_status
  - `payment_intent.succeeded` → bekreft ordre
  - `charge.refunded` → håndter refusjon

- [ ] **6.4** Logging for debugging

### Filer å opprette/endre
```
includes/Api/StripeWebhook.php (ny)
functions.php (registrer REST-endpoint)
```

### Akseptansekriterier
- [ ] Webhook mottas og verifiseres
- [ ] Events oppdaterer korrekt data
- [ ] Feil logges for debugging
- [ ] Stripe CLI kan teste lokalt: `stripe listen --forward-to`

---

## Fase 7: Deploy & Migrasjon

**Mål:** Sømløs overgang fra localhost til Servebolt produksjon.

### Pre-deploy sjekkliste

- [ ] **7.1** Stripe-konfigurasjon:
  - [ ] Opprett live webhook i Stripe Dashboard
  - [ ] Sett webhook URL: `https://dittdomene.no/wp-json/minsponsor/v1/stripe-webhook`
  - [ ] Hent webhook signing secret for live
  - [ ] Bekreft at live API-nøkler er klare

- [ ] **7.2** Servebolt cache-unntak:
  - [ ] Exclude `/checkout/*` fra cache
  - [ ] Exclude `/cart/*` fra cache
  - [ ] Exclude `/my-account/*` fra cache
  - [ ] Exclude `/wp-json/minsponsor/*` fra cache

- [ ] **7.3** Environment-sjekk i admin:
  - [ ] Vis nåværende miljø (localhost/production)
  - [ ] Vis aktiv Stripe-modus (test/live)
  - [ ] Vis webhook-URL for kopiering
  - [ ] Advarsel hvis live miljø bruker test-nøkler

### Deploy-prosess

- [ ] **7.4** Før deploy:
  - [ ] Commit alle endringer til `develop`
  - [ ] Merge til `main` branch
  - [ ] Verifiser at GitHub webhook til Servebolt fungerer

- [ ] **7.5** Etter deploy:
  - [ ] Sett live Stripe API-nøkler i MinSponsor → Innstillinger
  - [ ] Sett live webhook secret
  - [ ] Bytt miljø til "live"
  - [ ] Test med ekte Stripe-betaling (liten sum)
  - [ ] Verifiser at webhook mottas

- [ ] **7.6** Servebolt-spesifikk konfig:
  - [ ] Bekreft PHP 8.1+ er aktivert
  - [ ] Sjekk at SSL/HTTPS fungerer
  - [ ] Vurder ekte cron vs WP-Cron

### Rollback-plan

Hvis noe går galt:
1. Bytt tilbake til test-modus i admin
2. Deaktiver Stripe webhook i Dashboard
3. Fiks problemet lokalt
4. Re-deploy

### Filer å opprette/endre
```
includes/Settings/StripeSettings.php (miljø-indikator)
includes/Admin/EnvironmentNotice.php (admin-varsler, ny)
```

### Akseptansekriterier
- [ ] Admin viser tydelig hvilken modus (test/live)
- [ ] Webhook fungerer på produksjon
- [ ] Cache-regler er konfigurert
- [ ] Første live-betaling går igjennom

---

## Testing-kommandoer

```bash
# Stripe CLI – lytt til webhooks lokalt
stripe listen --forward-to localhost:8888/spons/wp-json/minsponsor/v1/stripe-webhook

# Trigger test-events
stripe trigger payment_intent.succeeded
stripe trigger account.updated

# Sjekk konto-status
stripe accounts retrieve acct_XXXXX

# Test webhook på produksjon (etter deploy)
stripe trigger payment_intent.succeeded --api-key sk_live_XXX
```

---

## Referanser

- [stripe-connect-spec.md](./stripe-connect-spec.md) – Fullstendig Stripe Connect-spesifikasjon
- [acceptance-tests.md](./acceptance-tests.md) – Akseptansetester
- [security-invariants.md](./security-invariants.md) – Sikkerhetsregler

---

## Notater

_Legg til notater underveis her:_

- 
