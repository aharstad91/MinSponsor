# Spons WordPress Theme

Et minimalt WordPress-tema bygget med Tailwind CSS.

## Installasjon

1. Last ned eller klon temaet til `wp-content/themes/spons/`
2. Naviger til tema-mappen: `cd wp-content/themes/spons/`
3. Installer avhengigheter: `npm install`
4. Bygg CSS: `npm run build`
5. Aktiver temaet i WordPress admin

## Utvikling

- `npm run watch` - Overvåker endringer og bygger CSS automatisk
- `npm run build` - Bygger CSS for utvikling
- `npm run build-prod` - Bygger minifisert CSS for produksjon

## Struktur

- `src/style.css` - Tailwind CSS kildefil
- `dist/style.css` - Bygget CSS-fil (brukes av WordPress)
- `index.php` - Hovedmal
- `functions.php` - Tema-funksjoner
- `style.css` - WordPress tema-header

## Tilpasning

Rediger `tailwind.config.js` for å tilpasse farger, fonter og andre designelementer.
