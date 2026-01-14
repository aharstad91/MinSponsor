# AI workflow (Opus som utvikler)

## Always-on regler
- Følg `docs/security-invariants.md` – alltid.
- Ingen refaktorering/"cleanup" uten eksplisitt beskjed.
- Maks 6 filer per endring.
- Maks ~200 linjer netto endring per steg (hvis mer: splitt i flere steg).
- Bruk `manage_todo_list` for å spore fremgang på komplekse oppgaver.

## Outputformat for alle endringer
1. **Plan** (maks 8 punkter)
2. **Fil-liste** som endres
3. **Diff** via edit-verktøy (ikke print kodeblokker)
4. **How to test** (konkrete steg/kommandoer)

## Arbeidsloop (standard)
```
1. Implementer liten slice
2. Kjør Chrome MCP smoke-flow (se under)
3. Skriv MCP run report (bruk docs/mcp-run-report-template.md)
4. Fiks funn i liten diff
5. Kjør samme smoke-flow igjen
```

## Chrome MCP smoke-testing

> **Merk:** Chrome MCP brukes aktivt av både AI-agent og bruker for å teste flyter i nettleseren.

Bruk Chrome MCP-verktøyene for å verifisere endringer:
1. `mcp_io_github_chr_new_page` → åpne test-URL
2. `mcp_io_github_chr_take_snapshot` → les sidens innhold
3. `mcp_io_github_chr_fill` / `mcp_io_github_chr_click` → interager
4. `mcp_io_github_chr_list_console_messages` → sjekk for JS-feil
5. Dokumenter resultat i run report

## Audit (high-stakes: auth, betaling, webhooks)
Etter implementasjon av sikkerhetskritisk kode:
- **Pass 1: Security** – gjennomgå diff + nærliggende kode
- **Pass 2: Ops/robusthet** – retries, logging, edge cases
- Audit skal levere minimal patch (ingen refaktor).

## Git workflow
- Commit ofte med beskrivende meldinger
- Bruk `mcp_gitkraken_git_add_or_commit` for staging og commit
- Push til `develop` branch med `mcp_gitkraken_git_push`

## Stripe MCP

> **Merk:** Stripe MCP lar AI-agenten jobbe direkte mot Stripe API for å opprette og verifisere betalingsobjekter.

### Tilgjengelige verktøy
Når Stripe MCP er aktivert, kan agenten bruke disse for å bygge og teste:
- Opprette **Products**, **Prices**, **Payment Links**
- Opprette **Customers** og **Subscriptions**
- Lese **Payment Intents**, **Charges**, **Events**
- Teste **Stripe Connect** flows med connected accounts

### Bruksområder i MinSponsor
1. **Verifisere Stripe Connect-oppsett** – sjekk at lag har riktig `connected_account_id`
2. **Teste betalingsflyt** – opprett test-betalinger og verifiser metadata
3. **Webhook-debugging** – inspiser events som kommer fra Stripe
4. **Opprette testdata** – lag products/prices for testing uten manuelt Dashboard-arbeid

### VS Code Stripe-utvidelse
Bruker har også `stripe.vscode-stripe` installert som gir:
- `@stripe` chat-participant i Copilot
- Lokal webhook forwarding (`stripe listen`)
- Event-logs og debugging

### Sikkerhet
- Bruk alltid **test mode** API-nøkler under utvikling
- Aldri logg API-nøkler eller secrets
- Verifiser at connected account kommer fra post meta, ikke klient-input
