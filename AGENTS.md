# AGENTS.md — 112 Revelats (112revelats.112books.eu)

## Projecte
Migració de WordPress a Hugo del site **112 Revelats**, un projecte col·lectiu de fotollibres impulsat per 112Books.eu.

- **Domini**: 112revelats.112books.eu (o 112revelats.org en futur)
- **Responsable**: Joan Martínez (LinuxBCN)
- **Idiomes**: Català (principal), Español, English
- **Amfitrió actual**: WordPress a Dinahosting
- **Amfitrió futur**: GitHub Pages + Hugo

## Estructura del site
Single-page amb seccions:
1. **Hero** — títol, subtítol, CTA descàrrega PDF
2. **Objectius** — què és 112 Revelats
3. **Patrocini / Mecenatge** — com participar
4. **Els reptes** — fases del repte fotogràfic
5. **Arrencant el dia** — PDF descarregable
6. **Contacte / FAQ** — formulari + preguntes freqüents
7. **Footer** — xarxes socials, legal, crèdits LinuxBCN

## Estil
- Disseny net, centrat en la fotografia
- Colors: neutres (blanc, negre, grisos) — la foto és la protagonista
- Tipografia: IBM Plex Sans (com LinuxBCN)
- Responsive, PWA-ready

## Desplegament
- Build: `hugo --minify`
- Destí: GitHub Pages (branch gh-pages)
- Sync: `./sync-112revelats.sh`

## Tecnologies
- Hugo (generador estàtic)
- Cap framework CSS extern
- Formulari de contacte: Netlify Forms o similar
- Galetes: només tècniques (avís de cookies si cal)
