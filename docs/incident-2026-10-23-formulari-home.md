# Incident — formulari de la home (2026-10-23)

## Simptoma

Un participant va intentar inscriure's des del formulari ràpid de la home
(`#reptes` → «Inscriu-te al repte»). En enviar-lo, la pàgina responia sempre
amb el missatge genèric:

> No s'ha pogut enviar. Torna-ho a provar o escriu-nos a hola@112books.eu.

## Evidència

- Registre d'Apache del servidor: 7 `POST /api/submit.php` el 23/10/2026
  entre les 16:00:36 i les 16:02:56 (hora local), tots amb estat **422** i
  65 bytes de resposta.
- Cos de la resposta: `{"ok":false,"error":"missing_fields","fields":["enllac-imatges"]}`.
- Cap entrada a la base de dades: el formulari ràpid no enviava el camp
  `enllac-imatges`, que el backend exigia per al tipus `inscripcio`.
- El formulari complet (`/legal/formulari/`) sí que té el camp i funcionava
  (hi ha inscripcions correctes del 2026-10-03 i del 2026-10-21).

## Causa

Desajust entre el formulari de la home i el backend:

- La home enviava `form=inscripcio` però només recollia `nom`, `email`,
  `portfolio`, `missatge` i `rgpd`.
- `submit.php` requereix `nom`, `email` i `enllac-imatges` per a `inscripcio`.
- `forms.js` mostrava sempre el mateix missatge genèric, sense indicar què faltava.

## Correcció (2026-10-23)

- **Home**: el formulari passa a `form=inscripcio-rapida` (només `nom` + `email`),
  amb nota que explica que és un primer contacte i enllaç al formulari complet.
  Missatge d'èxit propi: «T'hem apuntat; et contactarem perquè ens enviïs les fotografies.»
- **Backend**: nou tipus `inscripcio-rapida` (nom + email). Es desen `subject` i
  `lang`; el 422 retorna el camp o camps que falten; el 429 porta `Retry-After`.
- **forms.js**: missatges concrets per camps que falten, correu invàlid, URL
  invàlida i massa intents; marca els camps afectats; si falta un camp que no
  és a la pàgina, enllaça al formulari complet.
- **Anti-bot / límits**: `_ts` mínim 1 s i rate limit 10/hora per IP i formulari.
- **CORS**: afegits `https://112revelats.org` i `https://www.112revelats.org`.
- **Correu**: assumpte codificat amb `mb_encode_mimeheader` (accents i guions)
  i `Content-Transfer-Encoding: 8bit`.

## Verificació

- Endpoint de proves aïllat al servidor (PHP 8.5) amb BD temporal: formulari
  ràpid, inscripció sense imatges (422), URL dolenta (422), contacte sense nom
  (422), honeypot, massa ràpid, idioma invàlid i preflight CORS.
- Producció: `POST` amb `form=inscripcio-rapida` →
  `{"ok":true,"stored":true,"mailed":true}`; fila creada i esborrada després.
- Home generada (CA/ES/EN): `data-form=inscripcio-rapida`, nota i enllaç al
  formulari complet presents.

## Pendent

- El participant ha de tornar a enviar les dades: no es va desar res. Si torna
  a fallar, que escrigui a `hola@112books.eu` (o que faci servir el formulari
  complet `/legal/formulari/`).
- Revisar periòdicament els logs per detectar altres 422/429 de formularis.
