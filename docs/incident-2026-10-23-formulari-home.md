# Incident — formulari de la home (2026-10-23)

## Simptoma

Un participant va intentar inscriure's des del formulari de la home
(`#reptes` → «Inscriu-te al repte»). En enviar-lo, la pàgina responia sempre
amb el missatge genèric:

> No s'ha pogut enviar. Torna-ho a provar o escriu-nos a hola@112books.eu.

## Evidència

- Registre d'Apache del servidor: 7 `POST /api/submit.php` el 23/10/2026
  entre les 16:00:36 i les 16:02:56 (hora local), tots amb estat **422** i
  65 bytes de resposta.
- Cos de la resposta: `{"ok":false,"error":"missing_fields","fields":["enllac-imatges"]}`.
- Cap entrada a la base de dades: el formulari de la home no enviava el camp
  `enllac-imatges`, que el backend exigia per al tipus `inscripcio`.
- El formulari complet (`/legal/formulari/`) sí que té el camp i funcionava.

## Causa

Desajust entre el formulari de la home i el backend:

- La home enviava `form=inscripcio` però només recollia `nom`, `email`,
  `portfolio`, `missatge` i `rgpd`.
- `submit.php` requereix `nom`, `email` i `enllac-imatges` per a `inscripcio`.
- `forms.js` mostrava sempre el mateix missatge genèric, sense indicar què faltava.

## Segon error detectat durant la verificació: la marca de temps `_ts`

Prova amb navegador headless contra la web en producció: el formulari mostrava
el missatge d'èxit, però **no hi havia cap fila nova a la BD**. El log del
servidor ho confirmava: `200` amb **11 bytes** (`{"ok":true}`), no els 39 bytes
de l'enviament desat.

El motiu era el filtre anti-bot per `_ts`:

    $nowMs - $ts < min_fill_seconds

`$ts` el posa el navegador (`Date.now()`) i `$nowMs` és el rellotge del
servidor. La màquina de la prova anava **24 segons avançada** respecte del
servidor, així que la diferència era negativa i el backend descartava
l'enviament en silenci, responent `ok`.

Això podia afectar usuaris reals amb el rellotge desfasat: veien «Gràcies!» i
les seves dades es perdien. **S'ha eliminat el filtre**; queden el honeypot
`_gotcha` i el rate limit.

## Correcció (2026-10-23)

- **Home**: s'ha tret el formulari. A `#reptes` hi ha un CTA
  («Omple el formulari d'inscripció →») que porta a `/legal/formulari/`.
  Així hi ha un sol circuit d'inscripció i ningú no es pensa que ha enviat les
  fotografies quan només ha deixat les dades.
- **Backend**: es manté el tipus `inscripcio-rapida` per a pàgines antigues
  cachejades; el 422 retorna el camp o camps que falten; el 429 porta
  `Retry-After`; es desen `subject` i `lang`; l'assumpte del correu es codifica
  amb `mb_encode_mimeheader`.
- **forms.js**: missatges concrets per camps que falten, correu invàlid, URL
  invàlida i massa intents; marca els camps afectats i, si falta un camp que
  no és a la pàgina, enllaça al formulari complet.
- **Anti-bot**: honeypot `_gotcha` + rate limit 10/hora per IP i formulari.
  Sense marques de temps del client.
- **CORS**: afegits `https://112revelats.org` i `https://www.112revelats.org`.
- **`wf_clean`**: normalització de finals de línia corregida (`\r\n` i `\r` → `\n`).

## Verificació

- Endpoint de proves aïllat al servidor (PHP 8.5) amb BD temporal: inscripció
  sense imatges (422), URL dolenta (422), contacte sense nom (422), honeypot,
  idioma invàlid i preflight CORS.
- Producció: el participant que es va queixar va poder enviar la inscripció
  completa i es va desar amb correu enviat (23/10, 15:22 UTC). El test de Joan
  també es va desar (15:24 UTC).
- Navegador headless: el formulari complet i el de contacte desen la fila i
  mostren el missatge d'èxit després de treure el filtre `_ts`.
- Home generada (CA/ES/EN): CTA cap a `/legal/formulari/` present.

## Pendent

- Vigilar els logs per si apareixen 422/429 repetits.
- La fila de prova de Joan (`joan`, 2026-10-23 15:24 UTC) es pot esborrar des
  del panell.
- Si algú altre es va quedar sense enviar abans d'aquest arreglament, caldria
  demanar-li que torni a provar-ho.
