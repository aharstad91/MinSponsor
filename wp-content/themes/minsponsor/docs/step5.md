# MinSponsor - Spillerstøtte Dokumentasjon

## Oversikt

Spillerstøtte-funksjonaliteten lar sponsorer støtte individuelle spillere via unike lenker. Systemet håndterer både engangsstøtte og månedlige abonnementer.

## Funksjoner

### 1. Spillerstøtte-lenker
- **Engangslink**: `/stott/{klubb}/{lag}/{spiller}?interval=once`
- **Månedlig link**: `/stott/{klubb}/{lag}/{spiller}?interval=month`
- Støtte for dynamisk beløp: `&amount=200`
- Referanse-sporing: `&ref=flyer01`

### 2. Admin-funksjonalitet
- Metaboks på spiller-poster i admin
- "Kopiér lenke" funksjonalitet for begge lenketyper
- Standardbeløp konfigurasjon (50/100/200/300 kr)

### 3. Dynamisk pris i handlekurv
- Overstyr produktpris med amount-parameter
- Virker for både engangsprodukter og abonnementer
- Korrekt prisvisning i handlekurv og kasse

### 4. Metadata-flyt
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

### 3. Test konfigurasjonen
Bruk "Test produkter" knappene i innstillingene for å validere at produktene er korrekt satt opp.

## Bruk

### 1. Opprette spiller
1. Opprett en klubb
2. Opprett et lag knyttet til klubben (via ACF parent_klubb felt)
3. Opprett en spiller knyttet til laget (via ACF parent_lag felt)

### 2. Bruke sponsorlenker
1. Gå til spiller-posten i admin
2. Se "MinSponsor - Spillerstøtte" metaboks i høyre kolonne
3. Kopier ønsket lenke
4. Del lenken med potensielle sponsorer

### 3. Sponsorprosess
1. Bruker besøker sponsorlenke
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

## Arkitektur

### Namespace-struktur

```
MinSponsor\
├── Admin\
│   └── SpillerMetaBox         # Admin metaboks for spillere
├── Checkout\
│   ├── CartPrice              # Dynamisk prissetting
│   └── MetaFlow               # Cart→Order→Subscription metadata
├── Frontend\
│   └── PlayerRoute            # Frontend routing og add-to-cart
├── Gateways\
│   ├── StripeMeta             # Stripe metadata injection
│   └── VippsRecurringMeta     # Vipps/MobilePay metadata
├── Services\
│   └── PlayerLinksService     # Lenke-generering og validering
└── Settings\
    └── PlayerProducts         # WooCommerce innstillinger
```

### Autoloading

Prosjektet bruker PSR-4 autoloading via `includes/autoload.php`. Klasser lastes automatisk basert på namespace.

## Feilsøking

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

## Fremtidige funksjoner

- QR-kode generering (planlagt)
- Fee-beregning og splitting
- Stripe Connect for utbetaling til klubber
- Webhook-håndtering for betalingsstatus
- Dunning/retry for mislykkede betalinger
