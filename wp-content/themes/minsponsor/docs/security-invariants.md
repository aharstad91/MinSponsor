# Security invariants (MinSponsor)

> ⚠️ **Disse reglene er ufravikelige.** Bryt dem aldri – selv ikke for "rask test".

---

## A. Aldri stol på klienten

**Regel:** URL-parametre, skjema-input og JS kan alltid manipuleres.

**I praksis:**
- `minsponsor_amount` fra URL → valider mot tillatte verdier (50, 100, 200, 300)
- `minsponsor_recipient_type` → bestem server-side basert på resolved entity
- Stripe connected account → hent fra post meta, aldri fra klient

```php
// ❌ FEIL
$account_id = $_GET['stripe_account'];

// ✅ RIKTIG
$account_id = get_post_meta($team_id, 'stripe_connected_account_id', true);
```

---

## B. Stripe / betaling

**Webhook-krav:**
1. Verifiser signatur (`Stripe\Webhook::constructEvent`)
2. Vær idempotent (sjekk om event allerede er prosessert)
3. Tål retries/timeouts (ikke anta én levering)

**Betalingsstatus:**
- Aldri stol på redirect-URL for å bekrefte betaling
- Kun Stripe webhook-event eller API-verifisering er gyldig

---

## C. Multi-tenant (klubb/lag/spiller)

**Regel:** En bruker kan aldri lese/endre data utenfor sin egen scope.

**I praksis:**
- Scope bestemmes server-side via `current_user_can()` + custom meta
- Alle database-queries må inkludere scope-filter
- Admin-sider må validere tilgang før visning

---

## D. WordPress hard rules

**For alle skrive-operasjoner:**
```php
// 1. Capability check
if (!current_user_can('edit_post', $post_id)) {
    wp_die('Ingen tilgang');
}

// 2. Nonce verification
if (!wp_verify_nonce($_POST['_wpnonce'], 'minsponsor_action')) {
    wp_die('Ugyldig forespørsel');
}

// 3. Sanitize input
$amount = absint($_POST['amount']);

// 4. Escape output
echo esc_html($player_name);
```

---

## E. Logging

**Aldri logg:**
- API-nøkler, tokens, secrets
- Kortdata eller bankinfo
- Personnummer, passord

**Alltid logg:**
- Stripe event_id, order_id, subscription_id
- Request-ID for sporbarhet
- Hvilket steg som feilet (ikke hvorfor i detalj)

---

## F. Endringsregel

**Hvis en invariant brytes:**
1. STOPP all feature-utvikling
2. Fiks invariant-bruddet
3. Verifiser med test
4. Fortsett deretter
