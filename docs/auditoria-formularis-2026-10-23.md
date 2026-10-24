# Auditoria dels formularis — 2026-10-23 / 2026-10-24

> Revisió completa després de l'incident del formulari de la home
> (`docs/incident-2026-10-23-formulari-home.md`).
> Abast: web CA/ES/EN, backend PHP+SQLite, correu, panell, còpies, CORS,
> límits, correus HTML i arxiu `.md`.

## Resum

Tot verificat i funcionant. S'han resolt: el formulari ambigu de la home, el
filtre `_ts` que descartava enviaments amb rellotges desfasats, les còpies de
codi dins del docroot, el rate limit baix per a IP compartides, els correus
sense imatge i la manca d'arxiu estructurat per convocatòria.

## Formularis

| Formulari | On | Camps obligatoris (servidor) | Prova |
|---|---|---|---|
| Inscripció completa | `/legal/formulari/` | `nom`, `email`, `enllac-imatges` | Navegador headless + participant real |
| Contacte | Home `#contacte` | `nom`, `email` | POST 200, desat i correu enviat |
| Col·laboració | `/legal/col-labora/` | `nom-contacte`, `email`, `cif`, `adreca-fiscal`, `tipus`, `visibilitat-web`, `visibilitat-publicacio`, `rgpd` | POST 200, desat i correu enviat |

- Home: CTA cap a `/legal/formulari` (CA/ES/EN); no queda cap formulari ambigu.
- Markup de les 9 pàgines: `action`/`data-endpoint` correctes, `form` ocult
  coincident, `convocatoria`, honeypot, missatges, `forms.js` i `<noscript>`.
- `<noscript>` visible amb JavaScript desactivat (provat amb Chrome).

## Correus

- Cada enviament genera **confirmació al participant** i **notificació interna**.
- HTML + text pla; imatge de capçalera incrustada (CID `header112`), sense
  imatges remotes: Apple Mail la mostra.
- MIME provat amb un `sendmail_path` que imprimeix el missatge:
  - confirmació: `multipart/related` → `multipart/alternative` + JPEG;
  - notificació: `multipart/mixed` → related/alternative + `.md` adjunt.
- Assumpte codificat; una capçalera amb salts de línia queda dins d'un sol
  `encoded-word` (sense injecció).
- Mida ~55 KB per correu.

## Arxiu Markdown

- `/home/112books/webforms/submissions/<convocatoria>/*.md`, 700/600.
- Front matter per a Obsidian + camps llegibles + metadades.
- `convocatoria` oculta a tots els formularis; fallback al backend.
- Descàrrega des del panell (`?md=<id>`) i adjunt a la notificació interna.
- `backup.sh` inclou un `submissions-*.tar.gz` diari.

## Backend

- CORS: els 3 orígens responen 204 amb capçalera; un origen desconegut no.
- Errors: 422 amb camps; `invalid_email`; `invalid_url`; honeypot; 429 +
  `Retry-After: 900`.
- Rate limit: 30/hora per IP i formulari (provat amb 2/h → 429).
- `_ts` eliminat del client i del servidor.
- `wf_clean` normalitza correctament CRLF/CR.

## Base de dades i panell

- `PRAGMA integrity_check` → `ok`.
- Entrades reals: Sergio Català (21/10) i Jesús Joglar (23/10). Les files de
  prova (4 i 8–13) s'han esborrat; `joanet` també de tots els backups, amb
  `VACUUM`.
- Panell: 401 sense auth; 200 amb Basic auth; CSV 200; descàrrega `.md` 200.
- Permisos: `webforms` 700, `data` 700, `submissions` 700, fitxers 600.

## Còpies de seguretat

- `cron` diari 03:30; `forms-*.sqlite` + `submissions-*.tar.gz`; retenció
  30 dies / 60 còpies.
- Còpies de codi fora del docroot (`webforms/backups/source/`, 600).

## Interfície

- Mòbil 390×844: sense desbordament; CTA i formulari visibles.
- Errors localitzats ES/EN amb `field-error` i `aria-invalid`.

## Desplegament

- GitHub Pages es publica amb `.github/workflows/deploy.yml` en push a `main`
  (run 40047785553, `success`). La branca `gh-pages` ja no s'utilitza.
- `sync-112revelats.sh` fa commit/push a `main`; no publica `gh-pages`.

## Canvis fets

- Formulari de la home → CTA al formulari complet.
- `_ts` eliminat; honeypot + rate limit 30/h.
- Errors concrets a `forms.js` i marcatge de camps.
- Correus HTML amb imatge CID + confirmació automàtica CA/ES/EN.
- Arxiu `.md` per convocatòria + adjunt + descàrrega al panell + còpia diària.
- `<noscript>` amb `hola@112books.eu`.
- Hugo: `label` i `.Site.Language.Locale` (sense avisos de deprecació).
- Còpies de codi fora del docroot; files de prova esborrades.

## Limitacions i recomanacions

- Si un taller comparteix IP i supera les 30 inscripcions/hora, cal apujar
  `rate_per_hour`.
- La confirmació s'envia al correu indicat; un correu aliè podria rebre-la
  (backscatter). Es pot desactivar amb `send_confirmation`.
- Sense JavaScript, el POST natiu arriba al backend i el navegador mostra el
  JSON en cru; el `<noscript>` ja indica el correu alternatiu.
- En canviar de convocatòria cal actualitzar el camp ocult `convocatoria` i els
  textos de la confirmació (Retrats Lents/termini) a `server/forms/lib.php`.
