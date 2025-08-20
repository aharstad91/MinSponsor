# MinSponsor STEG 5 - Spillerstøtte med QR og Metadata

## Oversikt

STEG 5 implementerer komplett spillerstøtte-funksjonalitet med automatisk generering av sponsorlenker, QR-koder og metadata-flyt gjennom hele betalingsprosessen.

## Funksjoner implementert

### 1. Spillerstøtte-lenker
- **Engangslink**: `/stott/{klubb}/{lag}/{spiller}?interval=once`
- **Månedlig link**: `/stott/{klubb}/{lag}/{spiller}?interval=month`
- Støtte for dynamisk beløp: `&amount=200`
- Referanse-sporing: `&ref=flyer01`

### 2. QR-kode generering
- Automatisk generering av PNG QR-koder (1024px)
- Lagring i WordPress Media Library
- Filnavn: `qr-spiller-{slug}-once.png` / `qr-spiller-{slug}-month.png`
- Høy feilkorrigering for optimal lesbarhet

### 3. Admin-funksjonalitet
- Metaboks på spiller-poster i admin
- "Kopiér lenke" funksjonalitet for begge lenketyper
- QR-forhåndsvisning og nedlasting
- Standardbeløp konfigurasjon (50/100/200/300 kr)
- Regenerer QR-koder ved behov

### 4. Dynamisk pris i handlekurv
- Overstyr produktpris med amount-parameter
- Virker for både engangsprodukter og abonnementer
- Korrekt prisvisning i handlekurv og kasse

### 5. Metadata-flyt
- **Cart → Order**: Alle MinSponsor-data følger med
- **Order → Subscription**: Metadata kopieres til abonnement
- **Stripe**: Metadata sendes til Payment Intent
- **Vipps/MobilePay**: Metadata sendes til agreement/charge

## Installasjon og konfigurasjon

### 1. Opprett produkter
Før du kan bruke spillerstøtte må du opprette to WooCommerce-produkter:

**Engangsprodukt:**
- Type: Simple product
- SKU: `minsponsor_player_one_time` (valgfritt)
- Pris: Standardpris for engangsstøtte

**Abonnementsprodukt:**
- Type: Subscription product (krever WooCommerce Subscriptions)
- SKU: `minsponsor_player_monthly` (valgfritt)
- Pris: Standardpris for månedlig støtte
- Billing interval: Monthly

### 2. Konfigurer produkter
Gå til **WooCommerce → Innstillinger → MinSponsor** og velg:
- Hvilke produkter som skal brukes for spillerstøtte
- QR-kode innstillinger (størrelse, feilkorrigering)

### 3. Test konfigurasjonen
Bruk "Test produkter" knappene i innstillingene for å validere at produktene er korrekt satt opp.

## Bruk

### 1. Opprette spiller
1. Opprett en klubb
2. Opprett et lag knyttet til klubben (via ACF parent_klubb felt)
3. Opprett en spiller knyttet til laget (via ACF parent_lag felt)
4. QR-koder genereres automatisk ved lagring

### 2. Bruke sponsorlenker
1. Gå til spiller-posten i admin
2. Se "MinSponsor - Spillerstøtte" metaboks i høyre kolonne
3. Kopier ønsket lenke eller skann QR-kode
4. Del lenken eller QR-koden med potensielle sponsorer

### 3. Sponsorprosess
1. Bruker besøker sponsorlenke eller skanner QR-kode
2. Automatisk videresending til handlekurv med riktig produkt
3. Korrekt pris settes hvis amount-parameter er oppgitt
4. Automatisk videresending til kasse
5. Alle metadata følger gjennom til betaling og abonnement

## URL-eksempler

```
# Basis spillerside
https://example.com/stott/heimdal-if/handballg09/ole-hansen/

# Engangsstøtte uten beløp (bruker produktets standardpris)
https://example.com/stott/heimdal-if/handballg09/ole-hansen/?interval=once

# Månedlig støtte med 200 kr
https://example.com/stott/heimdal-if/handballg09/ole-hansen/?interval=month&amount=200

# Med referanse for sporing
https://example.com/stott/heimdal-if/handballg09/ole-hansen/?interval=once&amount=150&ref=flyer01
```

## Metadata som sendes til betalingsgateway

```json
{
  "club": "Heimdal IF",
  "team": "Håndball G09",
  "player": "Ole Hansen", 
  "amount": "200",
  "interval": "month",
  "ref": "flyer01",
  "minsponsor_source": "player-link"
}
```

## Feilsøking

### Ingen QR-koder genereres
**Årsaker:**
- Spilleren er ikke knyttet til lag
- Laget er ikke knyttet til klubb
- Manglende tillatelser for Media Library

**Løsning:**
1. Kontroller ACF-relasjoner (parent_lag, parent_klubb)
2. Klikk "Regenerer QR-koder" i metaboksen
3. Sjekk error_log for feilmeldinger

### Lenker virker ikke
**Årsaker:**
- Produkter ikke konfigurert i WooCommerce-innstillinger
- WooCommerce eller WooCommerce Subscriptions ikke aktivert
- Permalink-struktur ikke oppdatert

**Løsning:**
1. Gå til WooCommerce → Innstillinger → MinSponsor
2. Kontroller at produkter er valgt og validert
3. Gå til Innstillinger → Permalenker og klikk "Lagre endringer"

### Feil pris i handlekurv
**Årsaker:**
- amount-parameter er ikke numerisk eller negativ
- Produktet støtter ikke prisendring
- Cache-problemer

**Løsning:**
1. Kontroller at amount er et positivt heltall
2. Tøm handlekurv og prøv igjen
3. Deaktiver caching-plugins midlertidig

### Metadata mangler i Stripe/Vipps
**Årsaker:**
- Gateway-plugin har endret hook-navn
- Metadata er for store (Stripe har 500 tegn grense per felt)
- Plugin-konfigurasjon

**Løsning:**
1. Sjekk error_log for metadata-relaterte feilmeldinger
2. Test med kortere klub/lag/spillernavn
3. Kontakt utvikler hvis hooks har endret seg

### Abonnement opprettes ikke
**Årsaker:**
- WooCommerce Subscriptions ikke aktivert
- Månedlig produkt er ikke et subscription product
- Checkout-feil

**Løsning:**
1. Aktiver WooCommerce Subscriptions
2. Kontroller at produktet er type "Subscription"
3. Test med standardprodukt først

## Teknisk oversikt

### Hooks registrert
- `template_redirect`: PlayerRoute for å håndtere sponsorlenker
- `woocommerce_before_calculate_totals`: Dynamisk pris
- `woocommerce_checkout_create_order_line_item`: Order line metadata
- `woocommerce_checkout_create_order`: Order metadata
- `woocommerce_subscriptions_created_subscription`: Subscription metadata
- `wc_stripe_payment_intent_args`: Stripe metadata
- `vipps_recurring_agreement_data`: Vipps metadata
- `save_post_spiller`: QR-generering
- `before_delete_post`: QR-opprydding

### Klasser opprettet
- `MinSponsor_QrService`: QR-kode generering og lagring
- `MinSponsor_PlayerLinksService`: Lenke-generering og validering
- `MinSponsor_PlayerRoute`: Frontend routing og add-to-cart
- `MinSponsor_SpillerMetaBox`: Admin metaboks
- `MinSponsor_CartPrice`: Dynamisk pris i handlekurv
- `MinSponsor_MetaFlow`: Metadata-flyt cart→order→subscription
- `MinSponsor_StripeMeta`: Stripe metadata injection
- `MinSponsor_VippsRecurringMeta`: Vipps/MobilePay metadata
- `MinSponsor_PlayerProducts`: WooCommerce innstillinger

### Filer opprettet/endret
```
includes/
├── Services/
│   ├── QrService.php
│   └── PlayerLinksService.php
├── Frontend/
│   └── PlayerRoute.php
├── Admin/
│   ├── SpillerMetaBox.php
│   └── admin.js
├── Checkout/
│   ├── CartPrice.php
│   └── MetaFlow.php
├── Gateways/
│   ├── StripeMeta.php
│   └── VippsRecurringMeta.php
└── Settings/
    ├── PlayerProducts.php
    └── settings.js
docs/
└── step5.md
functions.php (oppdatert)
```

## Support og utvikling

For feilrapporter eller feature requests, legg til issues i prosjektets repository.

Ved problemer med spesifikke betalingsgateway-integrasjoner, kontroller om plugin-leverandøren har endret hook-navn i nyere versjoner.
