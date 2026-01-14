# MinSponsor AI Coding Instructions

## Project Overview
MinSponsor is a WordPress theme for sports club sponsorship, built on WooCommerce + WooCommerce Subscriptions. Fans can sponsor clubs, teams, or individual players via one-time or monthly payments. All payments go through Samhold AS's central Stripe account, with automatic distribution to recipients.

---

## 🎨 DESIGNSYSTEM (ALLTID FØLG DETTE!)

### Merkevareessens
**Varm, inkluderende, pålitelig og enkel.** MinSponsor handler om fellesskap, letthet og tillit – en digital venn som tar bort stress og skaper rom for idrettsglede.

### Fargepalett

#### Primærfarger
| Farge | HEX | Tailwind | Bruk |
|-------|-----|----------|------|
| Varm Korall | `#F6A586` | `korall` | Hovedfarge – optimisme, varme, handling |
| Lys Beige | `#F5EFE6` | `beige` | Bakgrunn – ro, nøytralitet, enkelhet |
| Mørk Brun | `#3D3228` | `brun` | Tekst & detaljer – stabilitet, troverdighet |

#### Sekundærfarger
| Farge | HEX | Tailwind | Bruk |
|-------|-----|----------|------|
| Kremhvit | `#FBF8F3` | `krem` | Lys bakgrunn, luftige soner |
| Varm Terrakotta | `#D97757` | `terrakotta` | **CTAs, knapper, fremhevninger** |
| Soft Grå | `#E8E2D9` | `softgra` | Dividers, subtile elementer |

#### Aksentfarger (bruk sparsomt)
| Farge | HEX | Tailwind | Bruk |
|-------|-----|----------|------|
| Dyp Terrakotta | `#B85D42` | `terrakotta-dark` | Hover-states, viktige knapper |
| Varm Gul | `#F4C85E` | `gul` | Positive ikoner, celebrasjoner |

### Typografi
**Font:** Inter (fallback: DM Sans, Source Sans Pro)

| Element | Størrelse | Vekt | Tailwind |
|---------|-----------|------|----------|
| H1 | 48px | Bold (700) | `text-h1` |
| H2 | 36px | Semibold (600) | `text-h2` |
| H3 | 28px | Semibold (600) | `text-h3` |
| H4 | 20px | Medium (500) | `text-h4` |
| Brødtekst | 16-18px | Regular | `text-body` / `text-body-lg` |
| Knapper | 16px | Semibold | `font-semibold` |

Linjehøyde: 1.6-1.8 for brødtekst

### UI Komponenter

#### Primærknapp (CTA)
```html
<button class="btn-primary">Start støtte</button>
```
```css
background: #D97757; color: #FBF8F3; border-radius: 8px;
padding: 14px 32px; font-weight: 600;
hover: background #B85D42;
```

#### Sekundærknapp
```html
<button class="btn-secondary">Les mer</button>
```
```css
background: transparent; border: 2px solid #D97757;
color: #D97757; border-radius: 8px; padding: 12px 30px;
```

#### Input-felter
```html
<input class="input-field" type="text">
```
```css
background: #FBF8F3; border: 1px solid #E8E2D9;
border-radius: 8px; padding: 14px 18px;
focus: border-color #D97757;
```

#### Kort/Cards
```html
<div class="card">...</div>     <!-- 16px radius, 24px padding -->
<div class="card-lg">...</div>  <!-- 24px radius, 32px padding -->
```

### Layout & Spacing
- **Base unit:** 8px
- **Store seksjoner:** 80-120px padding
- **Mellom elementer:** 16-32px margin
- **Border radius:** 8px (små), 16px (kort), 24px (store seksjoner)

### Skygger
```css
box-shadow: 0 4px 20px rgba(61, 50, 40, 0.08);  /* --shadow-warm */
```

### Tone of Voice
- ✅ **Vennlig:** vi snakker med deg, ikke til deg
- ✅ **Ærlig:** vi erkjenner problemet og løser det
- ✅ **Enkel:** ingen jargong, bare klare ord
- ✅ **Oppmuntrende:** vi heier på deg og barna

**Eksempler:**
- ✅ "Gi barna mer tid til det de elsker"
- ✅ "Enkelt. Trygt. Forutsigbart."
- ❌ "Optimalisert inntektsstrøm" (for korporativt)

### CSS Variabler (alltid tilgjengelig)
```css
:root {
  --color-korall: #F6A586;
  --color-beige: #F5EFE6;
  --color-brun: #3D3228;
  --color-terrakotta: #D97757;
  --color-terrakotta-dark: #B85D42;
  --color-krem: #FBF8F3;
  --color-softgra: #E8E2D9;
  --color-gul: #F4C85E;
  
  --radius-sm: 8px;
  --radius-md: 16px;
  --radius-lg: 24px;
  
  --shadow-warm: 0 4px 20px rgba(61, 50, 40, 0.08);
}
```

### Designregler
**DO:**
- Bruk varme, beige bakgrunner
- Hold det luftig med mye whitespace
- Bruk terrakotta for CTAs
- Avrundede, vennlige former

**DON'T:**
- ❌ Unngå aggressive farger (rødt, skarpt blått)
- ❌ Unngå sterke kontraster (svart/hvit)
- ❌ Unngå overlesset design
- ❌ Unngå kalde gråtoner

---

## Business Model
- Sponsor pays: amount + 10% fee (e.g., 110 kr for 100 kr sponsorship)
- Distribution: 100% to recipient, 4% to Stripe, 6% to Samhold AS
- Sponsorship is level-specific: money to a player stays with player, team with team, club with club (no automatic distribution down hierarchy)
- Payment gateway: Stripe (Vipps/MobilePay Recurring planned for later)

## Architecture

### Content Hierarchy (CPT → parent meta)
```
klubb (Club) → lag (Team) → spiller (Player)
```
Each level can receive sponsorships independently.
- `lag` stores `parent_klubb` post ID in meta
- `spiller` stores `parent_lag` post ID in meta
- Use `minsponsor_get_parent_klubb_id()` and `minsponsor_get_parent_lag_id()` helpers in `functions.php`

### URL Structure
All sponsorship content uses `/stott/` prefix with nested slugs:
- `/stott/{klubb}/` - Club page (can receive sponsorship)
- `/stott/{klubb}/{lag}/` - Team page (can receive sponsorship)
- `/stott/{klubb}/{lag}/{spiller}/` - Player page (can receive sponsorship)
- Sponsorship params: `?interval=once|month&amount=50|100|200|300&ref=tracking`

### Class Architecture (PSR-4 in `includes/`)
| Namespace | Purpose |
|-----------|---------|
| `MinSponsor\Frontend\PlayerRoute` | Handles URL params → cart → checkout redirect |
| `MinSponsor\Checkout\CartPrice` | Dynamic pricing from `minsponsor_amount` param |
| `MinSponsor\Checkout\MetaFlow` | Passes metadata: cart → order → subscription |
| `MinSponsor\Gateways\StripeMeta` | Injects metadata into Stripe Payment Intents |
| `MinSponsor\Gateways\VippsRecurringMeta` | Injects metadata into Vipps/MobilePay |
| `MinSponsor\Services\PlayerLinksService` | Generates sponsorship URLs |
| `MinSponsor\Settings\PlayerProducts` | WooCommerce settings tab for product config |

Autoloader: `includes/autoload.php` (PSR-4 for `MinSponsor\` namespace)

## Key Patterns

### Metadata Keys (prefixed with `minsponsor_`)
Cart/order items use these keys:
- `minsponsor_player_id`, `minsponsor_player_name`, `minsponsor_player_slug`
- `minsponsor_team_id`, `minsponsor_team_name`, `minsponsor_team_slug`
- `minsponsor_club_id`, `minsponsor_club_name`, `minsponsor_club_slug`
- `minsponsor_amount`, `minsponsor_interval` (once|month), `minsponsor_ref`
- `minsponsor_recipient_type` (klubb|lag|spiller) - identifies which level receives the money

Order/subscription meta uses underscore prefix: `_minsponsor_player_name`, etc.

### Fixed Amount Options
Currently: 50, 100, 200, 300 kr (flexible amounts planned for later)

### WooCommerce Integration Pattern
- Check gateway availability before initializing (see `StripeMeta::is_stripe_available()`)
- Use standard WooCommerce email templates (add MinSponsor data, don't create custom templates)
- Stripe must work before launch (Vipps/MobilePay planned for later)

### ACF Field Groups
JSON sync enabled in `acf-json/`. Field groups define parent relationships for CPTs.

## Development Environment

### Local Setup
- MAMP for local development
- No staging environment (work directly localhost → production)
- Keep local and live in sync manually

### AI Agent WordPress Access
- URL: http://localhost:8888/spons/wp-admin
- Email: claude@agent.com
- Password: Ph(!mzzos&Keo^U9lq8JhQbo

### Build Commands
```bash
npm run watch      # Tailwind CSS watch mode
npm run build      # Build CSS
npm run build-prod # Minified production CSS
```

CSS source: `src/style.css` → Output: `dist/style.css`

### Deployment
- Hosting: Servebolt (minspo-28365.jana-osl.servebolt.cloud)
- Auto-deploy via GitHub webhooks on push
- Content sync: WP All Import/Export or similar plugin for database migration

## Conventions

### Language Policy
- **Code, comments, variable names, class names:** Always English
- **Admin UI (wp-admin):** English for error messages, labels, descriptions
- **Frontend UI (customer-facing):** Norwegian for all user-visible text
- **CPT names/slugs:** Norwegian (klubb, lag, spiller) - these are part of URL structure

### Code Style
- All rewrite rules registered in `minsponsor_add_rewrite_rules()`
- After adding new rewrite rules: flush with `flush_rewrite_rules()` or visit Permalinks settings
- WooCommerce product settings stored as `minsponsor_player_product_one_time_id` and `minsponsor_player_product_monthly_id` options
- SKU fallbacks: `minsponsor_player_one_time`, `minsponsor_player_monthly`

## Current Priority
1. Frontend display of clubs/teams/players (Gutenberg blocks later)
2. Stripe integration that actually works
3. Entity search/autocomplete on landing page

## Stripe Connect (KRITISK)
Se `docs/stripe-connect-spec.md` for fullstendig spesifikasjon av:
- Express-konto per lag (ikke klubb)
- Routing-regler for lag vs utøver
- Gebyrberegning (på toppen av sponsbeløp)
- Onboarding-flow

## Future Features (not yet implemented)
- Club admin self-service dashboards
- Player profile management
- Flexible custom amounts
- Visual profiles per club (colors, logos)
- Sponsor lists on pages ("These people support us")
- Progress indicators ("50% of goal reached")

## Testing
- Debug URL parameter: `?minsponsor_debug` shows registration status
- Product validation via AJAX in WooCommerce → MinSponsor settings tab

## Dependencies
- WordPress, WooCommerce, WooCommerce Subscriptions
- ACF (Advanced Custom Fields) for parent relationship fields
- Stripe gateway for payments
- Tailwind CSS 3.4+
