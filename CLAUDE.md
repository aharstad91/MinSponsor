# MinSponsor - Project Documentation for Claude

> **FIRST STEP IN EVERY SESSION:** Read `wp-content/_claude/session-log.md` and `wp-content/_claude/instructions.md` for workflow and latest status.

---

## PROTECTED FILES - DO NOT OVERWRITE

**`.mcp.json`** - MCP server configuration (in project root).
- NEVER overwrite this entire file
- Only ADD new servers, NEVER remove existing ones
- Use `claude mcp add` command to add new MCP servers

---

## Project Overview

MinSponsor is a Norwegian crowdfunding/sponsorship platform for sports clubs, teams, and players. It enables supporters to sponsor individual players or teams with one-time or recurring donations via Stripe and Vipps.

**Core Concept:**
- Sports clubs (Klubb) contain Teams (Lag) which contain Players (Spiller)
- Each Team can have a Stripe Connect Express account to receive payments
- Supporters can sponsor at team or player level (funds route to team's Stripe account)
- QR codes and direct links enable easy sponsorship discovery

**Tech Stack:**
- WordPress (classic theme)
- WooCommerce + WooCommerce Subscriptions (payment processing)
- ACF Pro for custom fields
- Stripe Connect Express (per-team payouts)
- Vipps Recurring Payments
- Tailwind CSS
- Custom PHP namespace structure (`MinSponsor\*`)

**Local Dev:** MAMP at `/Applications/MAMP/htdocs/spons/`

---

## Architecture

### Directory Structure
```
wp-content/
├── themes/spons/                   # Main theme
│   ├── includes/                   # PHP classes (namespaced)
│   │   ├── Admin/                  # Admin UI (meta boxes, columns)
│   │   │   ├── AdminColumns.php    # Admin list columns & filters
│   │   │   ├── SpillerMetaBox.php  # Player meta box
│   │   │   └── LagStripeMetaBox.php # Stripe Connect meta box for teams
│   │   ├── Api/                    # REST API & external integrations
│   │   │   ├── EntitySearch.php    # Entity search endpoint
│   │   │   └── StripeOnboarding.php # Stripe onboarding API
│   │   ├── CPT/                    # Custom Post Types
│   │   │   ├── PostTypes.php       # CPT registration
│   │   │   └── DataIntegrity.php   # Cascade delete, validation
│   │   ├── Checkout/               # WooCommerce checkout customization
│   │   │   ├── CartPrice.php       # Dynamic pricing & validation
│   │   │   ├── MetaFlow.php        # Order meta handling
│   │   │   └── CheckoutCustomizer.php # Norwegian checkout UI
│   │   ├── Frontend/               # Public-facing routes
│   │   │   └── PlayerRoute.php     # /stott/{klubb}/{lag}/{spiller}/ routing
│   │   ├── Gateways/               # Payment gateway integrations
│   │   │   ├── StripeMeta.php      # Stripe metadata handling
│   │   │   └── VippsRecurringMeta.php # Vipps recurring
│   │   ├── Routing/                # URL handling
│   │   │   └── Permalinks.php      # Custom permalink structure
│   │   ├── Services/               # Business logic services
│   │   │   └── StripeCustomerPortal.php # Stripe portal management
│   │   ├── Settings/               # WooCommerce settings pages
│   │   │   ├── PlayerProducts.php  # Product configuration
│   │   │   └── StripeSettings.php  # Stripe API settings
│   │   ├── Webhooks/               # Webhook handlers
│   │   │   └── StripeWebhook.php   # Stripe webhook receiver
│   │   └── autoload.php            # PSR-4 style autoloader
│   ├── acf-json/                   # ACF field groups
│   ├── docs/                       # Technical documentation
│   │   ├── stripe-connect-spec.md  # Stripe Connect implementation
│   │   ├── implementation-plan.md  # Phase-by-phase plan
│   │   ├── acceptance-tests.md     # Test scenarios
│   │   └── security-invariants.md  # Security requirements
│   ├── dist/                       # Compiled CSS
│   ├── src/                        # Source CSS (Tailwind)
│   ├── templates/                  # Page templates
│   ├── front-page.php              # Homepage
│   ├── page-stott.php              # Sponsorship page template
│   ├── single-klubb.php            # Single club view
│   ├── single-lag.php              # Single team view
│   ├── single-spiller.php          # Single player view
│   └── functions.php               # Theme bootstrap
└── plugins/
    ├── minsponsor/                 # Core plugin (currently minimal)
    ├── minsponsor-automator/       # Product & QR automation
    ├── woocommerce/                # WooCommerce
    ├── woocommerce-subscriptions/  # Subscription billing
    ├── woocommerce-gateway-stripe/ # Stripe gateway
    ├── vipps-recurring-payments-gateway-for-woocommerce/
    └── advanced-custom-fields-pro/
```

---

## Custom Post Types (CPTs)

All CPTs are registered in `includes/CPT/PostTypes.php`:

| Post Type | Slug | Description |
|-----------|------|-------------|
| `klubb` | `stott/{slug}` | Sports clubs (organizations) |
| `lag` | `stott/{klubb}/{slug}` | Teams within clubs |
| `spiller` | `stott/{klubb}/{lag}/{slug}` | Individual players |

### Taxonomies

| Taxonomy | Slug | Applies to |
|----------|------|------------|
| `idrettsgren` | `idrettsgren` | `lag` (e.g., Handball, Football) |

### CPT Relationships (via ACF/meta)

```
Klubb (parent)
  └── Lag (child via `parent_klubb` meta)
        └── Spiller (child via `parent_lag` meta)
```

---

## URL Structure

The site uses a hierarchical URL structure for sponsorship pages:

```
/stott/{klubb}/                    → Club page (single-klubb.php)
/stott/{klubb}/{lag}/              → Team page (single-lag.php)
/stott/{klubb}/{lag}/{spiller}/    → Player page (single-spiller.php)
```

This routing is handled by `includes/Routing/Permalinks.php`.

---

## Stripe Connect Architecture

### Key Concept
- Each **Lag (team)** has its own Stripe Connect Express account
- **Spillere (players)** don't have accounts - their donations route to their team
- Payments use `transfer_data` to send funds to connected accounts

### Meta Fields on Lag
```php
'_minsponsor_stripe_account_id'        // acct_xxxxx
'_minsponsor_stripe_onboarding_status' // not_started | pending | complete
'_minsponsor_stripe_onboarding_link'   // Temporary onboarding URL
'_minsponsor_stripe_last_checked'      // Timestamp
```

### ACF Fields on Lag
```php
'kasserer_email'    // Treasurer email for onboarding
'kasserer_navn'     // Treasurer name
'kasserer_telefon'  // Treasurer phone
```

### Fee Model (Fees on Top)
```
Supporter chooses: 100 kr to team
Supporter pays:    110 kr (100 + fees)
Team receives:     100 kr
Platform gets:     6% of 100 = 6 kr
Stripe takes:      ~2.9% + 1.80 kr
```

---

## WooCommerce Integration

### Products
MinSponsor uses shared WooCommerce products for sponsorships:

| Product Type | SKU Pattern | Description |
|--------------|-------------|-------------|
| One-time | `MS-PLAYER-ONE` | Single donation |
| Subscription | `MS-PLAYER-MONTH` | Monthly recurring |

Products are created/managed by `minsponsor-automator` plugin.

### Order Meta
Sponsorship context is stored on orders:
```php
'_ms_club_id'    // Club post ID
'_ms_team_id'    // Team post ID
'_ms_player_id'  // Player post ID (optional)
'_ms_ref'        // Attribution reference
'_ms_amount'     // Sponsor amount
'_ms_interval'   // one-time | monthly
```

---

## Key Helper Functions

```php
// Get parent club ID for a team
minsponsor_get_parent_klubb_id($lag_id)

// Get parent team ID for a player
minsponsor_get_parent_lag_id($spiller_id)

// Get post slug with caching
minsponsor_get_post_slug($post_id)
```

---

## Important Files

### Core Theme Files
- `functions.php` - Theme bootstrap, initializes all modules
- `includes/autoload.php` - PSR-4 autoloader for MinSponsor classes

### Stripe Connect
- `includes/Admin/LagStripeMetaBox.php` - Onboarding UI in admin
- `includes/Settings/StripeSettings.php` - API keys & webhook config
- `includes/Webhooks/StripeWebhook.php` - Webhook handler

### Documentation
- `docs/stripe-connect-spec.md` - Full Stripe Connect specification
- `docs/implementation-plan.md` - Current implementation status
- `docs/security-invariants.md` - Security requirements

---

## Development Notes

### Namespace Structure
All theme classes use the `MinSponsor` namespace:
```php
namespace MinSponsor\Admin;
namespace MinSponsor\Checkout;
namespace MinSponsor\Frontend;
// etc.
```

### Stripe Testing
```bash
# Listen to webhooks locally
stripe listen --forward-to localhost:8888/spons/wp-json/minsponsor/v1/stripe-webhook

# Test events
stripe trigger payment_intent.succeeded
stripe trigger account.updated
```

### ACF JSON
Field groups are synced to `acf-json/` directory for version control.

### Tailwind CSS
```bash
# Development
npm run watch

# Production build
npm run build-prod
```

---

## Current Implementation Status

See `docs/implementation-plan.md` for detailed status:

| Phase | Description | Status |
|-------|-------------|--------|
| Phase 1 | Stripe fields on Lag | Done |
| Phase 2 | Settings page | Done |
| Phase 3 | Onboarding flow | Done |
| Phase 4 | Checkout gate | Done |
| Phase 5 | Payment flow | Not started |
| Phase 6 | Webhooks | Not started |
| Phase 7 | Deploy | Not started |

---

## Owner Context

**Andreas Harstad** - Produktdesigner
- Comfortable with HTML, CSS, WordPress
- Needs help with advanced PHP and complex code logic
- Focus: Stripe Connect implementation and checkout flow
