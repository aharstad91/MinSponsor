# MinSponsor – Stripe Connect (Express per lag)
**Pengestrøm, onboarding, routing og gebyrer**

> **Dette er hoveddokumentet for Stripe Connect-implementasjonen.**
> Alle andre docs refererer hit for pengestrøm og routing.

---

## 1. Formål
Dette dokumentet beskriver hvordan MinSponsor skal håndtere:
- Stripe Connect (kontotype **Express**) for utbetalinger
- onboarding (KYC) og tilgang for kasserer/ansvarlig
- pengestrøm og routing for støtte til **klubb / lag / utøver**
- gebyrmodell (plattformgebyr + betalingsgebyr) lagt **på toppen** av sponsbeløpet

---

## 2. Datamodell (hierarki)

```
Kategori (category_id)
  └── Organisasjon/Klubb (org_id) ← juridisk enhet, har orgnummer
        └── Gruppe/Lag (group_id) ← PAYOUT-ENHET, har Stripe Connect
              └── Utøver/Spiller (singular_id) ← dimensjon, ikke egen konto
```

### Kritisk: Stripe Connect opprettes per LAG
- Hvert **lag** får sin egen **Stripe Connect Express-konto**
- Lagets kasserer får eget Express-dashboard
- Utøver-støtte rutes til lagets konto (utøver er bare dimensjon for sporing)

---

## 3. Roller

| Rolle | Ansvar |
|-------|--------|
| **MinSponsor (plattform)** | Drifter løsningen, definerer gebyrmodell, håndterer routing |
| **Kasserer (lag)** | Gjennomfører Stripe onboarding, har tilgang til Express-dashboard |
| **Supporter (betaler)** | Velger sponsbeløp, betaler total inkl. gebyrer |

---

## 4. Routing-regler (KRITISK)

### URL-struktur
- Lag-side: `/stott/{klubb}/{lag}/`
- Utøver-side: `/stott/{klubb}/{lag}/{spiller}/`

### Routing til Stripe Connect
| Støtte til | Rutes til |
|------------|-----------|
| **Lag** | Lagets `stripe_connected_account_id` |
| **Utøver** | Lagets `stripe_connected_account_id` (samme!) |
| **Klubb** | Må velge lag først, eller eksplisitt klubbkonto |

### Gate før betaling
Checkout blokkeres hvis:
- `stripe_connected_account_id` mangler
- `stripe_onboarding_status` ≠ `complete`

Vis da melding: "Laget er ikke klart til å ta imot betaling ennå."

---

## 5. Gebyrer på toppen (KRITISK)

### Prinsipp
Supporter velger beløp som **laget skal få**. Gebyrer legges på toppen.

```
Supporter velger:  100 kr til laget
Supporter betaler: 100 kr + gebyrer = 110 kr
Laget mottar:      100 kr (ikke 92, ikke 96)
```

### Gebyrkomponenter
- **P** = Plattformgebyr (MinSponsor) – f.eks. 6%
- **S** = Stripe-gebyr – ca. 2,9% + 1,80 kr (varierer)

### Beregning
```
A = sponsbeløp (det laget skal få)
P = plattformgebyr
a = Stripe prosent (0.029)
b = Stripe fast (1.80)

T = (A + P + b) / (1 - a)  ← Total som supporter betaler
```

### Eksempel
```
A = 100 kr (til laget)
P = 5 kr (plattformgebyr)
Stripe: 2,9% + 1,80 kr

T = (100 + 5 + 1.80) / (1 - 0.029) = 110,00 kr

Checkout viser:
  Spons:    100 kr
  Gebyrer:   10 kr
  Total:    110 kr
```

---

## 6. Onboarding-flow

### Steg 1: Opprett klubb og lag i WP-admin
- Klubb: navn, orgnr, adresse
- Lag: navn, kobling til klubb, kasserer-kontakt

### Steg 2: Generer onboarding-lenke
MinSponsor genererer unik Stripe Account Link for laget.

### Steg 3: Kasserer fullfører hos Stripe
- Juridisk info (klubbens orgnr)
- Bankkonto (lagkonto)
- Representative info (kasserer)

### Steg 4: Koble til MinSponsor
Når Stripe returnerer `acct_...`:
- Lagre `stripe_connected_account_id` på laget
- Sett `stripe_onboarding_status = complete`

---

## 7. Admin-UI struktur

### Menystruktur i WP-admin
```
MinSponsor
├── Klubber (CPT: klubb)
├── Lag (CPT: lag)
├── Utøvere (CPT: spiller)
└── Innstillinger
    ├── Gebyrer
    ├── Stripe API-nøkler
    └── Miljø (test/live)
```

### Lag-admin: Stripe Connect-panel
Vis alltid disse feltene (read-only):
- `stripe_connected_account_id` – tom eller `acct_...`
- `stripe_onboarding_status` – `not_started` / `pending` / `complete`
- "Sist sjekket" – timestamp

**Knapper:**
- **Start onboarding** – oppretter account + genererer lenke
- **Kopier onboarding-lenke** – for manuell sending
- **Refresh status** – henter status fra Stripe API
- **Send på e-post** – sender lenke til kasserer (valgfritt)

### "Klar for støtte?"-indikator
Synlig boks på lag-siden:
```
✅ Stripe-konto koblet (acct_xxx)
✅ Onboarding fullført
✅ Klar til å ta imot betaling

— eller —

❌ Onboarding ikke fullført
   [Start onboarding] [Refresh status]
```

### Ved kasserer-bytte
1. Oppdater kasserer-kontaktfelt (navn/e-post)
2. Generer ny login-lenke til Express-dashboard
3. **Viktig:** Bytt IKKE `acct_...` – lagets konto består

---

## 8. Statusfelter per lag

| Felt | Verdier |
|------|---------|
| `stripe_connected_account_id` | `acct_...` eller tomt |
| `stripe_onboarding_status` | `not_started` / `pending` / `complete` |
| `stripe_charges_enabled` | bool fra Stripe |
| `stripe_payouts_enabled` | bool fra Stripe |
| `stripe_onboarding_url` | Aktiv onboarding-lenke (midlertidig) |
| `kasserer_email` | Kontakt for support |
| `stripe_last_checked` | Timestamp for siste status-refresh |

---

## 9. Innstillinger-side

### MinSponsor → Innstillinger → Gebyrer
| Felt | Beskrivelse |
|------|-------------|
| Plattformgebyr (fast) | F.eks. 0 kr |
| Plattformgebyr (prosent) | F.eks. 6% |
| Stripe-gebyrsats | 2,9% + 1,80 kr (standard NO) |
| Visningstekst | "Gebyr kommer i tillegg til sponsbeløpet" |

### MinSponsor → Innstillinger → Stripe
| Felt | Beskrivelse |
|------|-------------|
| Miljø | Test / Live |
| Test Secret Key | `sk_test_...` |
| Live Secret Key | `sk_live_...` |
| Webhook Secret | `whsec_...` |

---

## 10. Teknisk implementasjon

### Meta-felter på `lag` CPT
```php
// Post meta for lag
'stripe_connected_account_id'  // acct_xxxxx
'stripe_onboarding_status'     // not_started | pending | complete
'stripe_charges_enabled'       // bool fra Stripe
'stripe_payouts_enabled'       // bool fra Stripe
```

### Stripe API-kall (hovedoperasjoner)

**Opprett Connected Account:**
```php
$account = \Stripe\Account::create([
    'type' => 'express',
    'country' => 'NO',
    'email' => $kasserer_email,
    'capabilities' => [
        'card_payments' => ['requested' => true],
        'transfers' => ['requested' => true],
    ],
]);
```

**Generer Onboarding-lenke:**
```php
$link = \Stripe\AccountLink::create([
    'account' => $account->id,
    'refresh_url' => home_url('/stripe/refresh/' . $lag_id),
    'return_url' => home_url('/stripe/return/' . $lag_id),
    'type' => 'account_onboarding',
]);
```

**Opprett Payment Intent med destination:**
```php
$intent = \Stripe\PaymentIntent::create([
    'amount' => $total_amount_ore,  // T * 100
    'currency' => 'nok',
    'transfer_data' => [
        'destination' => $stripe_connected_account_id,
        'amount' => $sponsor_amount_ore,  // A * 100 (det laget får)
    ],
    'metadata' => [
        'org_id' => $klubb_id,
        'group_id' => $lag_id,
        'singular_id' => $spiller_id,
        'minsponsor_amount' => $sponsor_amount,
    ],
]);
```

---

## 11. Sikkerhetsinvarianter (referanse)

Se `docs/security-invariants.md` for fullstendig liste. Kritisk for Stripe:

1. **Connected account bestemmes server-side** – aldri fra URL/klient
2. **Webhook-signatur verifiseres alltid**
3. **Idempotent webhook-håndtering** – samme event kan komme flere ganger
4. **Betalingsstatus kun fra Stripe** – ikke redirect-URL

---

## 12. Webhook-håndtering

### Verifisering (KRITISK)
```php
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$webhook_secret = get_option('minsponsor_stripe_webhook_secret');

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $webhook_secret
    );
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit('Invalid signature');
}
```

### Idempotens
```php
// Sjekk om event allerede er prosessert
$processed = get_option('stripe_processed_events', []);
if (in_array($event->id, $processed)) {
    http_response_code(200);
    exit('Already processed');
}

// Prosesser event...

// Marker som prosessert
$processed[] = $event->id;
update_option('stripe_processed_events', array_slice($processed, -1000));
```

### Events vi lytter på
| Event | Handling |
|-------|----------|
| `account.updated` | Oppdater onboarding-status på lag |
| `payment_intent.succeeded` | Bekreft ordre, send kvittering |
| `payment_intent.payment_failed` | Logg feil, varsle om nødvendig |
| `charge.dispute.created` | Varsle admin, se disputt-håndtering |

---

## 13. Refund-håndtering

### Prinsipp
Ved refund må vi håndtere at midler er splittet mellom plattform og connected account.

### Implementasjon
```php
// Full refund - Stripe håndterer automatisk fra connected account
$refund = \Stripe\Refund::create([
    'payment_intent' => $payment_intent_id,
    'reverse_transfer' => true,  // Reverserer transfer til connected account
    'refund_application_fee' => true,  // Refunderer også platform fee
]);
```

### Viktig
- Refund trekkes fra connected account sin balanse
- Hvis utilstrekkelig saldo: Stripe debiterer fra connected account
- Logg alltid refund med `refund_id`, `payment_intent_id`, `amount`

---

## 14. Disputt-håndtering (chargebacks)

### Varsling
Når `charge.dispute.created` mottas:
1. Send e-post til MinSponsor-admin
2. Send e-post til lagets kasserer med info
3. Logg disputt i database

### Respons
- MinSponsor må samle bevis (kvittering, støtte-bekreftelse)
- Last opp via Stripe Dashboard eller API
- Frist: vanligvis 7-21 dager

### Forebygging
- Tydelig "MinSponsor" som statement descriptor
- Kvittering på e-post med detaljer
- Sponsor-bekreftelse før betaling

---

## 15. Feilhåndtering

### API-feil
```php
try {
    $intent = \Stripe\PaymentIntent::create([...]);
} catch (\Stripe\Exception\CardException $e) {
    // Kortfeil - vis til bruker
    $error = $e->getError();
    return ['error' => $error->message];
} catch (\Stripe\Exception\RateLimitException $e) {
    // For mange requests - retry med backoff
    sleep(2);
    return retry_payment();
} catch (\Stripe\Exception\ApiErrorException $e) {
    // Generell API-feil - logg og varsle
    error_log('Stripe API error: ' . $e->getMessage());
    return ['error' => 'Betalingstjenesten er midlertidig utilgjengelig'];
}
```

### Idempotency keys
```php
// For kritiske operasjoner - forhindrer duplikater ved retry
$intent = \Stripe\PaymentIntent::create([
    'amount' => $amount,
    'currency' => 'nok',
    // ...
], [
    'idempotency_key' => 'order_' . $order_id . '_' . $timestamp,
]);
```

---

## 16. Fremtidige utvidelser (ikke i pilot)

| Feature | Status |
|---------|--------|
| Multi-valuta | Ikke planlagt (kun NOK) |
| Skatt/MVA-beregning | Avklares med revisor |
| Automatisk payout-schedule | Bruker Stripe default |
| Subscription billing | Eksisterer via WooCommerce Subscriptions |

---

## 17. Pilot-sjekkliste

### Per lag (før go-live):
- [ ] Klubb er opprettet
- [ ] Lag er opprettet og koblet til klubb
- [ ] Kasserer er registrert (navn + e-post)
- [ ] `stripe_onboarding_status = complete`
- [ ] "Klar for støtte?"-indikator viser ✅

### Test støtte-URLer:
- [ ] `/stott/{klubb}/{lag}/` fungerer
- [ ] `/stott/{klubb}/{lag}/{utover}/` fungerer
- [ ] Begge ruter til samme `acct_...`

### Verifiser gebyrberegning:
- [ ] 100 kr spons → laget får 100 kr
- [ ] Supporter betaler 100 kr + gebyr
- [ ] Checkout viser tydelig "Spons: X, Gebyr: Y, Total: Z"

### Verifiser gate:
- [ ] Checkout blokkeres hvis onboarding ikke er ferdig
- [ ] Bruker ser forklarende melding
