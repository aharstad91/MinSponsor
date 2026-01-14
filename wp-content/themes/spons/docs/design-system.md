# MinSponsor Designsystem v1.0

> Dokumentasjon for det visuelle designsystemet brukt i MinSponsor.

## Oversikt

MinSponsor bruker et varmt, inkluderende designspråk som kommuniserer pålitelighet og enkelhet. Designsystemet er bygget med **Tailwind CSS** og **CSS Custom Properties** for maksimal fleksibilitet.

---

## Farger

### Primærfarger

| Farge | Hex | CSS Variable | Bruksområde |
|-------|-----|--------------|-------------|
| Korall | `#F6A586` | `--color-korall` | Primær merkefarge, ikoner, dekorasjoner |
| Korall light | `#F9C4B0` | `--color-korall-light` | Hover-effekter, bakgrunner |
| Korall dark | `#D97757` | `--color-korall-dark` | Samme som terrakotta |

### Handlingsfarger (CTA)

| Farge | Hex | CSS Variable | Bruksområde |
|-------|-----|--------------|-------------|
| Terrakotta | `#D97757` | `--color-terrakotta` / `--color-cta` | Primær CTA-knapper |
| Terrakotta dark | `#B85D42` | `--color-terrakotta-dark` | Hover-tilstand for CTA |

### Bakgrunnsfarger

| Farge | Hex | CSS Variable | Bruksområde |
|-------|-----|--------------|-------------|
| Beige | `#F5EFE6` | `--color-beige` / `--color-background` | Hovedbakgrunn |
| Krem | `#FBF8F3` | `--color-krem` / `--color-surface` | Kort, modaler, seksjoner |
| Soft grå | `#E8E2D9` | `--color-softgra` / `--color-border` | Borders, dividers |

### Tekstfarger

| Farge | Hex | CSS Variable | Bruksområde |
|-------|-----|--------------|-------------|
| Brun | `#3D3228` | `--color-brun` / `--color-text` | Primær tekst, overskrifter |
| Brun light | `#5A4D3F` | `--color-brun-light` / `--color-text-muted` | Sekundær tekst, hjelpetekst |

### Aksentfarger

| Farge | Hex | CSS Variable | Bruksområde |
|-------|-----|--------------|-------------|
| Gul | `#F4C85E` | `--color-gul` | Suksess, feiring |
| Grønn | `#4CAF50` | `--color-success` | Bekreftelser |
| Rød | `#E53935` | `--color-error` | Feilmeldinger |

---

## Typografi

### Font-familier

- **Overskrifter:** Inter, DM Sans, sans-serif (`--font-heading`)
- **Brødtekst:** Inter, Source Sans Pro, sans-serif (`--font-body`)

### Størrelser

| Element | Desktop | CSS Variable | Bruk |
|---------|---------|--------------|------|
| H1 | 48px | `--text-h1` | Sideoverskrifter |
| H1 Hero | 56px | `--text-h1-hero` | Hero-seksjoner |
| H2 | 36px | `--text-h2` | Seksjonsoverskrifter |
| H2 Section | 48px | `--text-h2-section` | Store seksjonsoverskrifter |
| H3 | 28px | `--text-h3` | Underoverskrifter |
| H4 | 20px | `--text-h4` | Korttitler |
| Body | 16px | `--text-body` | Standard tekst |
| Body lg | 18px | `--text-body-lg` | Fremhevet tekst |
| Small | 14px | `--text-small` | Hjelpetekst |
| XS | 12px | `--text-xs` | Badges, metadata |

### Font-weights

- **700** - Overskrifter (h1)
- **600** - Seksjonsoverskrifter (h2, h3), knapper
- **500** - Mellomviktig tekst (h4)
- **400** - Brødtekst

---

## Spacing

Systemet bruker en **8px grid**:

| Navn | Verdi | CSS Variable |
|------|-------|--------------|
| XS | 4px | `--spacing-xs` |
| SM | 8px | `--spacing-sm` |
| MD | 16px | `--spacing-md` |
| LG | 24px | `--spacing-lg` |
| XL | 32px | `--spacing-xl` |
| 2XL | 48px | `--spacing-2xl` |
| 3XL | 64px | `--spacing-3xl` |
| Section | 80px | `--spacing-section` |
| Section LG | 120px | `--spacing-section-lg` |

---

## Border Radius

| Navn | Verdi | CSS Variable | Bruk |
|------|-------|--------------|------|
| XS | 4px | `--radius-xs` | Små elementer |
| SM | 8px | `--radius-sm` | Knapper, inputs |
| MD | 16px | `--radius-md` | Kort, seksjoner |
| LG | 24px | `--radius-lg` | Store kort |
| Full | 9999px | `--radius-full` | Pills, avatarer |

---

## Shadows

| Navn | CSS Variable | Bruk |
|------|--------------|------|
| Warm SM | `--shadow-warm-sm` | Subtile elementer |
| Warm | `--shadow-warm` | Standard kort |
| Warm LG | `--shadow-warm-lg` | Hover-tilstand |
| Warm XL | `--shadow-warm-xl` | Fremhevede elementer |

**Farge:** Alle skygger bruker `rgba(61, 50, 40, x)` for varm estetikk.

---

## Komponenter

### Knapper

#### Primær knapp (`.btn-primary`)
```html
<a href="#" class="btn-primary">Støtt nå</a>
```
- **Bakgrunn:** Terrakotta (`--color-cta`)
- **Tekst:** Krem
- **Padding:** 14px 32px
- **Hover:** Mørkere terrakotta, løft -1px

#### Sekundær knapp (`.btn-secondary`)
```html
<a href="#" class="btn-secondary">Les mer</a>
```
- **Border:** 2px terrakotta
- **Tekst:** Terrakotta
- **Hover:** Fylt terrakotta bakgrunn

#### Hero CTA (`.btn-cta`)
```html
<a href="#" class="btn-cta">Start innsamlingen nå</a>
```
- **Bakgrunn:** Terrakotta
- **Border-radius:** Full (pill)
- **Padding:** 20px 40px
- **Hover:** Scale 1.05

### Kort

#### Standard kort (`.card`)
```html
<div class="card">
  <!-- innhold -->
</div>
```

#### Stort kort (`.card-lg`)
For hovedinnhold som profiler, støttevalg.

#### Glass-kort (`.glass-card`)
```html
<a href="#" class="glass-card">
  <!-- innhold -->
</a>
```
Semi-transparent bakgrunn med backdrop blur. Brukes på forsiden.

#### Entity-kort (`.entity-card`)
For klubb/lag/spiller-lister på `/stott/`-siden.

### Blob-ikoner

```html
<div class="blob-icon rotate-left">
  <svg>...</svg>
</div>
```

Varianter: `.rotate-left` (-6deg), `.rotate-right` (3deg), `.rotate-slight` (-3deg)

### Steg/Prosess

```html
<div class="step-circle">1</div>
<div class="step-circle filled">4</div>
```

### Trust Signals

```html
<div class="trust-signal">
  <svg>...</svg>
  <span>Sikker betaling</span>
</div>
```

### Input-felter

```html
<input type="text" class="input-field" placeholder="...">
<input type="text" class="search-input" placeholder="...">
```

### Badges

```html
<span class="badge badge-terrakotta">Klubb</span>
```

### Gradient Header

```html
<div class="gradient-header py-12 px-8 text-center">
  <h1>Tittel</h1>
</div>
```
Brukes på single-klubb, single-lag, single-spiller for profilheader.

---

## Bruk i templates

### Tailwind + CSS Variables

Kombiner Tailwind-klasser med CSS custom properties:

```html
<!-- Tailwind for layout, CSS vars for farger -->
<div class="max-w-3xl mx-auto p-8" style="background-color: var(--color-surface);">
```

### Klasser fra designsystemet

Bruk designsystem-klasser fremfor inline CSS:

```html
<!-- Bra -->
<button class="btn-primary">Støtt</button>

<!-- Unngå -->
<button style="background: #D97757; color: white; padding: 14px 32px;">Støtt</button>
```

---

## Filer

- **CSS Variables & Komponenter:** `src/style.css`
- **Tailwind config:** `tailwind.config.js`
- **Kompilert CSS:** `dist/style.css`

---

## Bygg CSS

```bash
# Utvikling (watch)
npm run watch

# Produksjon
npm run build-prod
```

---

## Akseptansekriterier

- [x] Alle sider føles som del av samme produkt
- [x] Design-tokens dokumentert i CSS custom properties
- [ ] Egne ikoner som matcher blob-estetikken (fremtidig)
