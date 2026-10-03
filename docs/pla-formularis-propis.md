# Formularis propis (112books.eu) — implementació

> Estat: **implementat i en producció** · 2026-10-03
> Objectiu assolit: cap servei extern de formularis; enviament amb el correu propi.

## Arquitectura

    [GitHub Pages: 112revelats.112books.eu]
          |  fetch() POST JSON  +  CORS
          v
    https://112books.eu/api/submit.php   (PHP 8.4, docroot /home/112books/www)
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

    curl -s -X POST https://112books.eu/api/submit.php -H "Content-Type: application/json" \
      -d '{"form":"contacte","nom":"Test","email":"prova@example.com","missatge":"Hola","_ts":0}'

Resposta esperada: {"ok":true,"stored":true,"mailed":true}

## Integracio amb el web (Hugo)

- hugo.toml: paràmetre formEndpoint.
- assets/js/forms.js: intercepta els enviaments i fa fetch a l'endpoint.
- Cada formulari porta: data-form (inscripcio/contacte/collaboracio),
  data-msg-ok, data-msg-err, i els camps ocults form i _ts.
- Anti-bot: honeypot _gotcha + temps minim (_ts) + rate limit 6/hora per IP.
- CORS restringit a https://112revelats.112books.eu.

## Manteniment

- Copia de seguretat diària (cron del servidor) de forms.sqlite i dels logs.
- Retencio de dades: esborrar entrades antigues periodicament (GDPR).
- Debian 11 es EOL; planificar Debian 12/13.
- WordPress viu al mateix servidor: mantenir-lo actualitzat.

## Pendents d'afinitat (fora de formularis)

- Autoallotjar Google Fonts (l'usuari ho vol).
- Decidir si es manté la cerca del footer per DuckDuckGo.
- GoatCounter es manté extern (l'usuari ho accepta).
