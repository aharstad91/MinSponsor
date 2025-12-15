# Acceptance tests (MinSponsor)

Denne fila beskriver “smoke”-tester vi kjører etter hver liten kode-endring, primært via Chrome MCP.
Hold testene smale og repeterbare. Hvis noe feiler: stopp, logg funn, fiks, og kjør samme test på nytt.

---

## Testmiljø og forutsetninger

**Base URL (lokal):** `http://localhost:8888/spons/`

**Testdata (minst 3 entities):**
- Club/org: `Heimdal IL` → slug: `heimdal-il`
- Team/lag: `Gutter 2009` → slug: `gutter-2009`
- Player/utøver: `Ola Nordmann` → slug: `ola-nordmann`

**Viktig:**
- For dev uten backend-endpoint: autocomplete kan bruke mock-liste i JS.
- For betaling/Stripe: bruk test mode.

---

## Smoke suite A — Landingsside + entity-søk

### MS-LP-01 — Hero: søk er primær handling
**Pre:** Åpne landingssiden (baseline `Spons.html`).
**Steg:**
1) Last siden.
2) Finn hero-området.
**Forventet:**
- Tydelig søkefelt i hero (større enn øvrige inputs).
- Helper text under søk: “Søk på klubb, lag eller utøver” (eller tilsvarende).
- “Kom i gang”-CTA er sekundær (demotert) eller fjernet.

---

### MS-LP-02 — Autocomplete viser relevante resultater
**Steg:**
1) Klikk i søkefeltet.
2) Skriv `hei`.
**Forventet:**
- Dropdown åpner med resultater (club/team minst).
- Hver rad har navn + subLabel (f.eks. “Klubb” eller klubbnavn under team).
- Match i teksten er markert (f.eks. bold på substring).

---

### MS-LP-03 — Tastatur-navigasjon i søk
**Steg:**
1) Skriv `hei` så listen vises.
2) Bruk piltaster opp/ned.
3) Trykk Enter.
**Forventet:**
- Fokus flytter seg mellom resultater.
- Enter navigerer til valgt resultat sin URL.
- Escape lukker listen uten å slette input.

---

### MS-LP-04 — Ranking (minimum)
**Steg:**
1) Søk etter et ord som finnes både som prefix og “contains”.
**Forventet:**
- Prefix-match rangeres over “contains”.
- Word-prefix rangeres over “contains”.

(OK å verifisere “godt nok” visuelt i tidlig fase.)

---

### MS-LP-05 — No results-state
**Steg:**
1) Skriv en streng som ikke matcher noe (f.eks. `zzzzzz`).
**Forventet:**
- Tydelig “Ingen treff” state i dropdown.
- Ingen JS-feil i console.

---

### MS-LP-06 — Riktig URL-routing fra søkeresultat
**Steg:**
1) Velg Team-resultat.
2) Gå tilbake, velg Player-resultat.
**Forventet:**
- Team → `/stott/{klubb}/{lag}/`
- Player → `/stott/{klubb}/{lag}/{spiller}/`
- URL-ene er konsistente med slugs.

---

### MS-LP-07 — “Dette løser vi”-kort er klikkbare
**Steg:**
1) Scroll til “Dette løser vi”.
2) Hover + klikk på hvert kort.
**Forventet:**
- Hele kortflaten er klikkbar (ikke bare tekst).
- Cursor/fokus-stil viser at det er interaktivt.
- Klikk tar deg til relevant seksjon/side (definerte lenker).

---

### MS-LP-08 — Klient-cache for siste søk (valgfri, men ønsket)
**Steg:**
1) Gjør 2–3 søk (f.eks. `hei`, `gut`, `ola`).
2) Refresh siden.
3) Klikk i søkefeltet.
**Forventet:**
- Tidligere søk/resultater kan dukke opp raskere (cache), uten å gi feil.
- Maks 20 søk lagres (om implementert).

---

## Smoke suite B — Stripe Connect pengeflyt (Express per lag)

> Se `docs/stripe-connect-spec.md` for fullstendig spesifikasjon.
> Må valideres mot invariant: *Ingen betaling skal sendes til feil connected account.*

### MS-PAY-01 — Team-side rutes til teamets connected account
**Pre:**
- Team `{klubb}/{lag}` har:
  - `stripe_connected_account_id` satt
  - `stripe_onboarding_status = complete`
**Steg:**
1) Åpne `/stott/{klubb}/{lag}/`
2) Start støtte/checkout (test).
3) Fullfør betaling i test.
**Forventet:**
- Checkout/charge blir opprettet “på vegne av” teamets `stripe_connected_account_id`.
- I logger (eller admin/debug) kan du se hvilken account id som ble brukt, og den matcher laget.

---

### MS-PAY-02 — Player-side rutes fortsatt til lagets connected account (default)
**Pre:** Samme som over.
**Steg:**
1) Åpne `/stott/{klubb}/{lag}/{spiller}/`
2) Start støtte/checkout (test).
**Forventet:**
- Bruker betaler til lagets connected account (spillere er dimensjon, ikke egen Stripe-konto).

---

### MS-PAY-03 — Blokker betaling når onboarding ikke er complete
**Pre:** Sett `stripe_onboarding_status = pending` (eller `not_started`).
**Steg:**
1) Åpne team-side.
2) Forsøk å starte checkout.
**Forventet:**
- Checkout startes ikke.
- Bruker får tydelig melding (f.eks. “Laget er ikke klart til å ta imot betaling ennå”).
- Ingen Stripe-objekter opprettes.

---

### MS-PAY-04 — Manipulert URL kan ikke endre payout-mottaker
**Steg:**
1) Forsøk å manipulere query params / payload (om mulig) for å peke mot en annen connected account.
**Forventet:**
- Server ignorerer klient-input og bruker connected account basert på resolved entity (team).
- Betaling kan ikke rutes til annet lag.

---

### MS-PAY-05 — Ordre/subscription meta har riktig entity-info
**Steg:**
1) Fullfør en testbetaling.
2) Inspiser order/subscription meta (i WP admin eller i logg).
**Forventet:**
- `org_id`, `group_id`, `singular_id` (valgfri), `ref` (valgfri) er satt riktig.
- Dette brukes til rapportering og tilgang.

---

## Smoke suite C — Robusthet (når webhooks er på plass)

### MS-RB-01 — Webhook idempotency (samme event flere ganger)
**Steg:**
1) Trigger samme webhook-event to ganger (replay).
**Forventet:**
- Systemet prosesserer kun én gang.
- Ingen dupliserte order updates / payout-markeringer.

### MS-RB-02 — Webhook signature verifisering
**Steg:**
1) Send webhook uten gyldig signatur.
**Forventet:**
- Avvises (4xx), ingen sideeffekter.

---

## Chrome MCP: standard kjøreregel
For hver PR/iterasjon:
1) Kjør **MS-LP-01 → MS-LP-06** (grunnleggende søk + routing)
2) Hvis betaling er berørt: kjør **MS-PAY-01 → MS-PAY-03**
3) Logg resultat i `docs/mcp-run-report-YYYY-MM-DD.md` etter mal
4) Fiks maks 3 funn → re-run samme smoke-script
