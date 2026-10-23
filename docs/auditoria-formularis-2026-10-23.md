# Auditoria dels formularis — 2026-10-23

> Revisió completa després de l'incident del formulari de la home
> (`docs/incident-2026-10-23-formulari-home.md`).
> Abast: web CA/ES/EN, backend PHP+SQLite, correu, panell, còpies, CORS i límits.

## Resum

Tot verificat i funcionant. Les úniques incidències trobades durant la revisió
s'han resolt: hi havia còpies del codi PHP dins del docroot i el rate limit
(10/h) era baix per a aules o tallers amb IP compartida.

## Formularis

| Formulari | On | Camps obligatoris (servidor) | Prova |
|---|---|---|---|
| Inscripció completa | `/legal/formulari/` | `nom`, `email`, `enllac-imatges` | Navegador headless + participant real |
| Contacte | Home `#contacte` | `nom`, `email` | POST 200, desat i correu enviat |
| Col·laboració | `/legal/col-labora/` | `nom-contacte`, `email`, `cif`, `adreca-fiscal`, `tipus`, `visibilitat-web`, `visibilitat-publicacio`, `rgpd` | POST 200, desat i correu enviat |

- Home: el CTA «Omple el formulari d'inscripció →» apunta a
  `/legal/formulari` (CA), `/es/legal/formulari` (ES) i
  `/en/legal/formulari` (EN). No queda cap formulari ambigu a `#reptes`.
- Markup de les 9 pàgines: `action` i `data-endpoint` correctes, `form`
  ocult coincident amb `data-form`, honeypot `_gotcha`, missatges
  `data-msg-ok` / `data-msg-err`, `forms.js` carregat i cap `_ts`.

## Backend

- CORS: `112revelats.112books.eu`, `112revelats.org` i
  `www.112revelats.org` responen 204 amb `Access-Control-Allow-Origin`;
  un origen desconegut no rep capçalera.
- Errors: 422 amb el camp o camps que falten; `invalid_email`;
  `invalid_url`; honeypot (200 sense desar); 429 amb `Retry-After: 900`.
- Rate limit: 30/hora per IP i formulari. Provat amb una configuració de 2/h:
  el tercer enviament retorna 429.
- Assumpte del correu codificat amb `mb_encode_mimeheader`; una capçalera amb
  salts de línia queda codificada (sense injecció).
- `_ts` eliminat del client i del servidor.

## Correu

- Els correus de prova han arribat a `hola@112books.eu` (contacte, inscripció
  i col·laboració).
- El cos inclou formulari, data, IP, camps i enllaç al panell. `Reply-To` és
  el correu del participant.

## Base de dades i panell

- `PRAGMA integrity_check` → `ok`.
- Entrades actuals: Sergio Català (21/10) i Jesús Joglar (23/10). Les files de
  prova de la revisió (8–11) s'han esborrat. Queda una prova antiga del 3/10
  (`joanet`, id 4) que es pot esborrar si es vol.
- Panell: 401 sense autenticació, 200 amb Basic auth, export CSV 200. El
  participant hi apareix.
- Permisos: `webforms` 700, `data` 700, `backups/source` 700 i fitxers 600.

## Còpies de seguretat

- `cron` diari a les 03:30 (`backup.sh`), retenció de 30 dies i màxim 60
  còpies. Prova manual correcta (`backup_ok`).
- Les còpies del codi PHP que s'havien deixat a `/home/112books/www/api/`
  s'han mogut a `webforms/backups/source/` (fora del docroot, 600).

## Interfície

- Viewport mòbil 390×844: sense desbordament horitzontal; CTA i formulari de
  contacte visibles.
- Errors localitzats provats en ES i EN: missatge concret, camp marcat amb
  `field-error` i `aria-invalid`.

## Canvis fets durant l'auditoria

- Rate limit 10 → 30/h (config del servidor, `config.sample.php` i docs).
- Còpies de codi fora del docroot.
- Files de prova 8–11 esborrades.

## Limitacions i recomanacions

- Els formularis necessiten JavaScript. Sense JS, el POST natiu arriba al
  backend però el navegador mostra el JSON en cru. Es podria afegir un
  `<noscript>` amb el correu de contacte.
- No hi ha correu automàtic de confirmació al participant; només la notificació
  interna.
- Si un taller comparteix IP i supera les 30 inscripcions/hora, cal apujar
  `rate_per_hour`.
- Hugo mostra avisos de deprecació per `languageName` i `.Site.LanguageCode`
  (cosmètic; no afecta el build).
