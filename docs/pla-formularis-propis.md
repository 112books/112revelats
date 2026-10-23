# Formularis propis (112books.eu) — implementació

> Estat: **implementat i en producció** · 2026-10-03
> Revisió post-incident: 2026-10-23 (vegeu `docs/incident-2026-10-23-formulari-home.md`)
> Objectiu assolit: cap servei extern de formularis; enviament amb el correu propi.

## Arquitectura

    [GitHub Pages: 112revelats.112books.eu]
          |  fetch() POST JSON  +  CORS
          v
    https://112books.eu/api/submit.php   (PHP 8.5, docroot /home/112books/www)
          |
          +-- SQLite  /home/112books/webforms/data/forms.sqlite
          +-- mail()  (wrapper /usr/bin/phpmailer de Dinaserver)
          |      -> hola@112books.eu
          +-- /api/admin.php (Basic auth)  llistat + export CSV

## On és cada cosa

Servidor: 112books@vl28359.dinaserver.com (Dinaserver, Debian 11).

    /home/112books/www/api/submit.php      endpoint public
    /home/112books/www/api/admin.php       panell (Basic auth)
    /home/112books/webforms/lib.php        llibreria (BD, rate limit, correu)
    /home/112books/webforms/config.php     configuracio (600) - NO al repo
    /home/112books/webforms/data/          SQLite (700)

Repo (aquest projecte): carpeta server/forms/ (lib.php, public/submit.php,
public/admin.php, config.sample.php).

## Tipus de formulari

| `form` | On és | Camps obligatoris (servidor) |
|---|---|---|
| `inscripcio` | `/legal/formulari/` | `nom`, `email`, `enllac-imatges` |
| `inscripcio-rapida` | Home (`#reptes`) | `nom`, `email` |
| `collaboracio` | `/legal/col-labora/` | `nom-contacte`, `email`, `cif`, `adreca-fiscal`, `tipus`, `visibilitat-web`, `visibilitat-publicacio`, `rgpd` |
| `contacte` | Home (`#contacte`) | `nom`, `email` |

El formulari ràpid de la home **no** és la inscripció completa: recull el contacte i
enllaça al formulari complet. El correu de notificació ho indica i hi porta l'enllaç.

Tots els formularis envien també `subject` (tema del correu) i `lang` (ca/es/en).

## Desplegament (des del repo)

    ssh 112books@vl28359.dinaserver.com "mkdir -p /home/112books/webforms/data /home/112books/www/api"
    scp server/forms/lib.php 112books@vl28359.dinaserver.com:/home/112books/webforms/lib.php
    scp server/forms/public/submit.php server/forms/public/admin.php 112books@vl28359.dinaserver.com:/home/112books/www/api/
    ssh 112books@vl28359.dinaserver.com "chmod 640 /home/112books/www/api/*.php"

La config.php NO es desplega; es crea un cop al servidor (contrasenya aleatoria).

## Administracio

- Panell: https://112books.eu/api/admin.php (usuari form + contrasenya).
- Per veure la contrasenya:

    ssh 112books@vl28359.dinaserver.com "grep admin_password /home/112books/webforms/config.php"

- Export CSV: https://112books.eu/api/admin.php?export=csv

## Proves rapides

    B=https://112books.eu/api/submit.php

    # contacte (desa i envia correu)
    curl -s -X POST $B -H "Content-Type: application/json" \
      -d '{"form":"contacte","nom":"Test","email":"prova@example.com","missatge":"Hola","_ts":0}'

    # formulari rapid de la home (inscripcio-rapida)
    curl -s -X POST $B -H "Content-Type: application/json" \
      -d '{"form":"inscripcio-rapida","nom":"Test","email":"prova@example.com","_ts":0}'

    # la inscripcio completa exigeix l'enllac d'imatges
    curl -s -X POST $B -H "Content-Type: application/json" \
      -d '{"form":"inscripcio","nom":"Test","email":"prova@example.com","_ts":0}'
    # -> {"ok":false,"error":"missing_fields","fields":["enllac-imatges"]}

    # preflight CORS
    curl -s -i -X OPTIONS $B -H "Origin: https://112revelats.112books.eu" \
      -H "Access-Control-Request-Method: POST" -H "Access-Control-Request-Headers: content-type"

Atencio: les proves amb dades valides desen una entrada i envien correu a
hola@112books.eu; esborra-les despres des del panell o amb sqlite3.

## Integracio amb el web (Hugo)

- hugo.toml: paràmetre formEndpoint.
- assets/js/forms.js: intercepta els enviaments i fa fetch a l'endpoint.
- Cada formulari porta: data-form (inscripcio / inscripcio-rapida / contacte / collaboracio),
  data-msg-ok, data-msg-err, i els camps ocults form i _ts.
- forms.js mostra l'error concret (camps que falten, correu invalid, URL, 429) en
  l'idioma de la pagina i marca els camps afectats.
- Anti-bot: honeypot _gotcha + temps minim (_ts, 1 s) + rate limit 10/hora per IP i formulari.
- CORS: https://112revelats.112books.eu, https://112revelats.org i https://www.112revelats.org.

## Manteniment

- Copia de seguretat diària (cron del servidor) de forms.sqlite i dels logs.
- Retencio de dades: esborrar entrades antigues periodicament (GDPR).
- Debian 11 es EOL; planificar Debian 12/13.
- WordPress viu al mateix servidor: mantenir-lo actualitzat.

## Pendents d'afinitat (fora de formularis)

- Autoallotjar Google Fonts (l'usuari ho vol).
- Decidir si es manté la cerca del footer per DuckDuckGo.
- GoatCounter es manté extern (l'usuari ho accepta).
