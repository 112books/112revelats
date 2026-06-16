# Design Spec: Document de Difusió Retrats Lents

**Data:** 2026-06-13  
**Projecte:** 112 Revelats  
**Autor:** Joan Martínez

---

## Objectiu

Crear un document operatiu per gestionar la campanya de difusió del segon repte fotogràfic "Retrats Lents" (21 juny – 6 setembre 2026). El document centralitza les plantilles d'email trilingües i la llista de contactes públics d'entitats fotogràfiques.

## Abast

- **Tipus d'entitats:** Clubs/associacions, escoles/centres, revistes/mitjans, comunitats online
- **Àmbit geogràfic:** Catalunya/PPCC (prioritat) → Espanya → Internacional
- **Idiomes:** Català, Español, English

## Decisions de disseny

| Decisió | Raó |
|---|---|
| Document únic (plantilles + contactes) | Ús pràctic: tot a mà per a enviaments manuals |
| To proper i directe | Comunitat fotogràfica — no institucional |
| Links al web, no PDF adjunt | Més lleuger, millor deliverability, el PDF és descarregable des del web |
| Taules amb columna "Enviat" | Seguiment manual dels enviaments |
| Format Markdown + PDF | Editable al repo + compartible externament |

## Fitxers

- `docs/difusio/retrats-lents-difusio.md` — document operatiu principal
- `docs/superpowers/specs/2026-06-13-difusio-retrats-lents-design.md` — aquest spec

## Evolució prevista

El document de contactes s'ampliarà progressivament amb:
- Clubs FCF (llista completa)
- Comunitats internacionals analògiques (APUG/Photrio, Pinhole Visions)
- Revistes internacionals (BJP, LensCulture, Fraction)
