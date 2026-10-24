# Correus dels formularis i arxiu Markdown

> Estat: **implementat i en producció** · 2026-10-24
> Complement de `docs/pla-formularis-propis.md` i de
> `docs/incident-2026-10-23-formulari-home.md`.

## 1. Correus

Cada enviament genera **dos correus**:

1. **Confirmació al participant**, en l'idioma del formulari (`lang`), amb
   HTML i text pla.
2. **Notificació interna** a `hola@112books.eu`, amb tots els camps, l'estat de
   la confirmació i el fitxer `.md` adjunt.

Els dos correus porten la imatge de capçalera incrustada (no remota), de manera
que **Apple Mail la mostra sense haver de carregar contingut remot**.

### Estructura MIME

- Confirmació: `multipart/related` → `multipart/alternative` (text + HTML)
  + imatge `image/jpeg` amb `Content-ID: <header112>`.
- Notificació: `multipart/mixed` → `multipart/related` (alternative + imatge)
  + `text/markdown` adjunt.
- L'HTML referencia la imatge amb `src="cid:header112"`; no hi ha cap URL
  remota d'imatge.
- Capçaleres: `From: 112 Revelats <formulari@112books.eu>`,
  `Reply-To` el correu del participant (a la confirmació, `hola@112books.eu`),
  assumpte codificat amb `mb_encode_mimeheader`.
- Mida típica: **~55 KB** per correu (imatge de 36 KB), per sota del límit de
  retall de Gmail.

### Imatge de capçalera

- Fitxer: `server/forms/assets/email-header.jpg` (1200×420, ~36 KB).
- Generació reproducible: `python3 scripts/gen-email-header.py`.
  - Parteix de `static/img/hero-bg.png`.
  - Aplica el vel fosc de la home i hi posa «112 Revelats» i
    «Projecte de fotollibres col·lectius».
  - Requereix Pillow i les fonts Georgia del sistema.
- Al servidor: `/home/112books/webforms/assets/email-header.jpg` (600).
- Config: `'email_image' => __DIR__ . '/assets/email-header.jpg'`.
- Si la imatge no hi és, el correu surt igualment amb una capçalera de text;
  el codi comprova `is_file()`/`is_readable()`.

### Confirmacions

| Formulari | Tema | Botó |
|---|---|---|
| `inscripcio` | Inscripció rebuda | Veure el repte |
| `collaboracio` | Proposta rebuda | Veure el patrocini |
| `contacte` | Missatge rebut | Visitar el web |
| `inscripcio-rapida` (antic) | Dades rebudes | Completar la inscripció |

Cada tema té text en CA/ES/EN, amb terminis, enllaços a bases/privacitat i un
peu amb la marca i l'avís «si no has enviat cap formulari, ignora aquest correu».

Es pot desactivar amb `'send_confirmation' => false` a `config.php`.

## 2. Arxiu Markdown per convocatòria

Cada entrada es desa a:

    /home/112books/webforms/submissions/<convocatoria>/<YYYY-MM-DD_HHMMSS>_id<ID>_<slug>.md

Exemple:

    submissions/retrats-lents/2026-10-23_152204_id7_jesus-joglar.md

### Contingut

- **Front matter** per a Obsidian:
  `id`, `formulari`, `convocatoria`, `data` (UTC), `nom`, `email`, `ip`,
  `confirmacio` i `tags` (`112revelats`, `formulari`, la convocatòria).
- **Enllaç de les imatges** destacat a les inscripcions.
- **Camps del formulari** amb etiquetes llegibles; els enllaços són clicables;
  els camps buits surten com `—` i els checkboxes com `sí`/`no`.
- **Metadades**: formulari, convocatòria, idioma, assumpte, IP i confirmació.

### Convocatòria

Cada formulari porta un camp ocult `convocatoria`:

| Pàgina | Valor |
|---|---|
| `/legal/formulari/` (CA/ES/EN) | `retrats-lents` |
| `/legal/col-labora/` (CA/ES/EN) | `col-laboracio` |
| Home `#contacte` | `contacte` |

Per a la propera convocatòria, canvia el `value` del camp ocult a
`content/legal/formulari/index.{ca,es,en}.md`. El backend també té un mapa de
recolzament per si el camp no arriba (per exemple, pàgines velles cachejades).

> Els textos de la confirmació (tema «Retrats Lents», termini i enllaços) són
> específics de la convocatòria actual. En canviar de repte, cal actualitzar
> també aquests textos a `server/forms/lib.php`.

### Descarregar i arxivar

- **Panell**: `https://112books.eu/api/admin.php` → columna **MD** →
  `?md=<id>` (Basic auth; `Content-Type: text/markdown`).
- **Adjunt**: la notificació interna porta el `.md` com a adjunt.
- **Còpia diària**: `backup.sh` genera cada dia un `submissions-*.tar.gz`
  (retenció 30 dies / 60 còpies) a més del `forms-*.sqlite`.
- **Sincronitzar a l'Obsidian** (opcional):

      rsync -avz --progress \
        112books@vl28359.dinaserver.com:/home/112books/webforms/submissions/ \
        ~/Documents/Obsidian/112revelats/submissions/

  Si no vols dades personals al repositori, afegeix `/submissions/` al
  `.gitignore`.

## 3. Configuració i desplegament

Claus noves a `/home/112books/webforms/config.php`:

    'email_image'       => __DIR__ . '/assets/email-header.jpg',
    'submissions_path'  => __DIR__ . '/submissions',
    'send_confirmation' => true,

Desplegament:

    ssh 112books@vl28359.dinaserver.com "mkdir -p /home/112books/webforms/assets /home/112books/webforms/submissions && chmod 700 /home/112books/webforms/assets /home/112books/webforms/submissions"
    scp server/forms/assets/email-header.jpg 112books@vl28359.dinaserver.com:/home/112books/webforms/assets/
    ssh 112books@vl28359.dinaserver.com "chmod 600 /home/112books/webforms/assets/email-header.jpg"
    scp server/forms/lib.php 112books@vl28359.dinaserver.com:/home/112books/webforms/
    scp server/forms/public/submit.php server/forms/public/admin.php 112books@vl28359.dinaserver.com:/home/112books/www/api/
    scp server/forms/backup.sh 112books@vl28359.dinaserver.com:/home/112books/webforms/
    ssh 112books@vl28359.dinaserver.com "chmod 640 /home/112books/www/api/*.php /home/112books/webforms/lib.php; chmod 700 /home/112books/webforms/backup.sh"

El correu surt amb el wrapper `mail()` de Dinaserver; el procés web corre com
`112books`, que pot llegir la imatge (600) i escriure a `submissions/` (700).

## 4. Proves

- Generar i revisar un MIME sense enviar correu:

      # wrapper que només imprimeix el missatge
      printf '#!/bin/sh\nexec cat\n' > /tmp/catsendmail && chmod +x /tmp/catsendmail
      php85 -d sendmail_path=/tmp/catsendmail -r 'require "/home/112books/webforms/lib.php"; wf_confirmation_mail("inscripcio", ["lang" => "ca"], "Test", "test@example.com");'

  Ha de contenir `multipart/related`, `cid:header112`, `Content-ID: <header112>`
  i la imatge en base64.
- Notificació amb adjunt: `multipart/mixed` amb `multipart/related` a dins i
  `Content-Disposition: attachment`.
- Enviament real -> fila a la BD, fitxer `.md` a `submissions/<conv>/`,
  confirmació rebuda i notificació amb el `.md` adjunt.
- Panell -> descàrrega `?md=<id>`; `backup.sh` -> tar amb `submissions/`.

## 5. Notes

- **Apple Mail**: la imatge va incrustada (CID); no depèn de «Carrega contingut
  remot». No hi ha imatges remotes ni píxels de seguiment.
- **Bounces / backscatter**: la confirmació s'envia al correu que ha posat el
  participant. Si algú posa un correu aliè, rebrà la confirmació; el rate limit
  i el honeypot ho limiten. Es pot desactivar amb `send_confirmation`.
- **Privadesa**: `submissions/` és 700, els fitxers 600, i el tar de còpia 600.
  El panell i la descàrrega `.md` van protegits amb Basic auth.
- **No** s'adjunta el `.md` al participant; només a la notificació interna.
- Si canvies la imatge, torna a executar `scripts/gen-email-header.py` i
  desplega el JPG.
