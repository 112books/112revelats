# Sessió 2026-10-23/24 — Formularis de 112 Revelats

> Document de tancament. Recull què s'ha fet, com ha quedat el sistema,
> proves, pendents i com operar-lo.
>
> Documents relacionats:
> - `docs/incident-2026-10-23-formulari-home.md`
> - `docs/auditoria-formularis-2026-10-23.md`
> - `docs/emails-i-arxius-formularis.md`
> - `docs/pla-formularis-propis.md`
> - `.taques/112revelats/2026-10-23.md` i `2026-10-24.md`

## 1. Resum

La sessió va començar amb una queixa: el formulari de la home responia
«No s'ha pogut enviar». S'ha corregit l'incident, s'ha auditat tot el circuit i
s'han afegit correus HTML amb imatge, confirmació automàtica i un arxiu
Markdown per convocatòria. També s'ha corregit el desplegament (GitHub Actions
des de `main`, no `gh-pages`) i s'ha netejat la dada de prova `joanet`.

## 2. Estat final

### Formularis
- Home `#reptes`: **CTA** «Omple el formulari d'inscripció →» cap a
  `/legal/formulari/` (CA/ES/EN). No hi ha el formulari ambigu.
- Formulari complet: `inscripcio` amb `nom`, `email` i `enllac-imatges`.
- Contacte: home `#contacte`.
- Col·laboració: `/legal/col-labora/`.
- Tots porten `convocatoria`, honeypot `_gotcha`, `<noscript>` i
  `forms.js` amb errors concrets (camps que falten, correu, URL, 429).
- Rate limit: **30/hora** per IP i formulari.
- CORS: `112revelats.112books.eu`, `112revelats.org` i
  `www.112revelats.org`.

### Correus
- **Confirmació automàtica al participant** en CA/ES/EN, amb botó i text pla.
- **Notificació interna** a `hola@112books.eu` amb tots els camps i el
  `.md` adjunt.
- HTML amb la imatge de capçalera **incrustada (CID `header112`)**: no és
  remota, així que Apple Mail la mostra sense carregar contingut remot.
- Imatge: `server/forms/assets/email-header.jpg` (1200×420, 36 KB), generada
  amb `scripts/gen-email-header.py` des de `static/img/hero-bg.png`.
- Mida típica del correu: ~55 KB.
- Es pot desactivar la confirmació amb `'send_confirmation' => false`.

### Arxiu `.md` per convocatòria
- `/home/112books/webforms/submissions/<convocatoria>/<data>_id<ID>_<slug>.md`.
- Front matter per a Obsidian (`id`, `formulari`, `convocatoria`, `data`,
  `nom`, `email`, `ip`, `confirmacio`, `tags`) + camps + metadades.
- Backfill fet per a Sergio i Jesús.
- Descàrrega al panell: columna **MD** → `https://112books.eu/api/admin.php?md=<id>`.
- Còpia diària: `backup.sh` genera `forms-*.sqlite` i
  `submissions-*.tar.gz` (30 dies / 60 còpies).

### Base de dades
- Només les inscripcions reals:
  - id 5 — Sergio Català Gómez (2026-10-21)
  - id 7 — Jesús Joglar (2026-10-23, amb enllaç d'imatges)
- `joanet` (id 4) esborrat de la BD, de **tots els backups** i amb
  `VACUUM`; també s'han eliminat les files de prova 8–13 de la BD actual.
- `PRAGMA integrity_check` → `ok`.

### Desplegament
- GitHub Pages es publica amb `.github/workflows/deploy.yml` en push a
  `main` (Actions). La branca `gh-pages` ja no s'utilitza.
- `sync-112revelats.sh` fa commit/push a `main`; no publica `gh-pages`.
- Runs verificats: `40047785553` i `40051563221` (`success`).

## 3. Correus de prova pendents d'esborrar

No s'han pogut esborrar des del servidor: la bústia és
`/home/112books/Maildir`, propietat de `vmail` (700); l'usuari SSH
`112books` no hi pot entrar, no té `sudo`, `doveadm` necessita configuració
de root, WordPress no té contrasenya de bústia i al Mac no hi ha compte de Mail
configurat (AppleScript dona error de privilegis).

Per acabar-ho cal **una** d'aquestes opcions:
1. Contrasenya IMAP de `hola@112books.eu` (o app password).
2. Accés root/Plesk al servidor.
3. Esborrar-los manualment des del client o webmail.

**Esborrar** (buscar `PROVA` i `joanet`):

| Data (UTC) | Assumpte |
|---|---|
| 2026-10-03 04:56 | `[Inscripcio Retrats Lents] joanet` |
| 2026-10-23 15:47 | `[Contacte] — PROVA CLOCK (eliminar)` |
| 2026-10-23 15:52 | `Inscripcio Retrats Lents — PROVA CTA FINAL (eliminar)` |
| 2026-10-23 16:25 | `Col·laboracio 112 Revelats — PROVA COLLAB (eliminar)` |
| 2026-10-23 22:39 | `[Contacte] — PROVA CONFIRMACIO (eliminar)` |
| 2026-10-23 22:39 | `Hem rebut el teu missatge — 112 Revelats` (confirmació) |
| 2026-10-24 00:40 | `[Contacte] — PROVA ARXIU (eliminar)` |
| 2026-10-24 00:40 | `Hem rebut el teu missatge — 112 Revelats` (confirmació HTML) |

**No esborrar** (reals):
- `Inscripcio Retrats Lents — Sergio Català Gómez`
- `Inscripcio Retrats Lents — Jesús Joglar`

## 4. Proves fetes

- Endpoint aïllat amb PHP 8.5 i BD temporal: 422, 429, honeypot, CORS.
- Producció: inscripció real de Jesús (15:22 UTC) desada i notificada.
- Navegador headless: formulari complet i contacte; confirmació i notificació.
- MIME amb `sendmail_path` que imprimeix el missatge:
  confirmació `multipart/related` + imatge CID; notificació
  `multipart/mixed` + `.md`.
- Enviament real amb `.md` adjunt i descàrrega al panell.
- `backup.sh` manual: `forms-*.sqlite` + `submissions-*.tar.gz`.
- Mòbil 390×844: sense desbordament; errors ES/EN localitzats.
- `<noscript>` visible amb JavaScript desactivat.
- Build Hugo sense avisos de deprecació (`label`, `.Site.Language.Locale`).

## 5. Com operar

- **Desplegar el web**: `git push origin main` (o `./sync-112revelats.sh deploy`).
  El workflow compila i publica.
- **Desplegar el backend**: `scp` de `server/forms/lib.php`,
  `server/forms/public/{submit,admin}.php` i
  `server/forms/assets/email-header.jpg`; `server/forms/backup.sh` a
  `/home/112books/webforms/`. Comandes a `docs/pla-formularis-propis.md`.
- **Panell**: `https://112books.eu/api/admin.php` (Basic auth).
- **Propera convocatòria**:
  1. Canviar `<input name="convocatoria" value="...">` a
     `content/legal/formulari/index.{ca,es,en}.md`.
  2. Actualitzar els textos de Retrats Lents/termini de la confirmació a
     `server/forms/lib.php`.
  3. Opcional: regenerar la imatge amb `scripts/gen-email-header.py`.

## 6. Control horari

- `.taques/112revelats/2026-10-23.md`: 3 tasques, **7h**.
- `.taques/112revelats/2026-10-24.md`: 2 tasques, **1h 45min**.
- `.taques/112revelats/weekly-report.md`: setmana 2026-W43, **8h 45min**.
- Registre manual estimat: no hi havia marques automàtiques de sessió i les
  pauses llargues no s'han comptat.

## 7. Commits i desplegament

- `73a6f8a` — formularis, correus HTML, arxiu `.md`, noscript i desplegament.
- `a9d5cce` — documentació i `sync-112revelats.sh`.
- Commit de tancament: el que genera `sync-112revelats.sh deploy` en aquesta
  sessió (docs + control horari).
- Workflows de Pages: `success` (runs 40047785553 i 40051563221).
