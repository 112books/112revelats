# Claude Context — 112 Revelats

> Estat actual del projecte. Llegeix-lo abans de tocar res.
> Actualitza'l quan canviï una decisió d'arquitectura.
> ⚠️ **Primer llegeix AGENTS.md** per a la descripció executiva.

---

## 1. Descripció del projecte

**112 Revelats** — projecte editorial col·lectiu de fotollibres. Cada edició proposa un repte temàtic, els participants hi aporten la seva mirada i construeixen un fotollibre únic.

- **Domini:** `112revelats.112books.eu`
- **Repositori GitHub:** `github.com/112books/112revelats` (main → font, gh-pages → build)
- **Responsable:** Joan Martínez (LinuxBCN) per a 112Books.eu
- **Idiomes:** Català (ca, principal) · Español (es) · English (en)
- **Públic:** Fotògrafs, artistes visuals, editors, mecenes

---

## 2. Arquitectura tècnica

### Generador
- **Hugo** v0.159+, tema propi des de zero
- Format: **TOML**
- `defaultContentLanguage = "ca"` — CA sense prefix, ES/EN amb `/es/` i `/en/`
- Multilingüe via `[languages]` + `i18n/*.toml` + `AllTranslations`

### Allotjament
- **GitHub Pages** des de la branca `gh-pages`
- Deploy manual: `hugo --minify` + push `public/` a `gh-pages` (o via `sync-112revelats.sh`)
- GitHub Actions disponible (`.github/workflows/deploy.yml`) però no activat

### Formularis
- **Formspree** (`https://formspree.io/f/maqzqynz`) — contacte + inscripció repte + col·laboració
- File upload al formulari de participació amb `enctype="multipart/form-data"`

### Password (ELIMINAT)
- El site **ja no té password gate**. Era un `baseof.html` amb SHA256 + `sessionStorage`. Es va eliminar el 2026-06-05.

---

## 3. Estructura del site

### Pàgina principal (single-page)
| Secció | ID | Contingut |
|---|---|---|
| Hero | `#inici` | Títol, subtítol, CTA PDF + Participa |
| Objectius | `#objectius` | Què és 112 Revelats (des de `_index.{lang}.md`) |
| Patrocini | `#patrocini` | Targetes: Participa + Mecenes |
| Reptes | `#reptes` | Challenge actual + completat, fase 3-step, formulari |
| Fases | `#fases` | Timeline 6-step del procés editorial |
| Fotollibres | `#fotollibres` | Descàrrega Arrencant el dia |
| FAQ | `#faq` | Preguntes freqüents (markdownify a faq_5_r) |
| Premsa | `#premsa` | Dossier de premsa |

### Pàgines internes
| Secció | Ruta (CA) | Ruta (ES) | Ruta (EN) |
|---|---|---|---|
| Retrats Lents | `/repte/retrats-lents/` | `/es/repte/retrats-lents/` | `/en/repte/retrats-lents/` |
| Arrencant el dia | `/repte/arrencant-el-dia/` | `/es/repte/arrencant-el-dia/` | `/en/repte/arrencant-el-dia/` |
| Bases | `/legal/bases/` | `/es/legal/bases/` | `/en/legal/bases/` |
| Formulari | `/legal/formulari/` | `/es/legal/formulari/` | `/en/legal/formulari/` |
| Col·labora | `/legal/col-labora/` | `/es/legal/col-labora/` | `/en/legal/col-labora/` |
| Avís legal | `/avis-legal/` | `/es/avis-legal/` | `/en/avis-legal/` |
| Privacitat | `/privacitat/` | `/es/privacitat/` | `/en/privacitat/` |
| Cookies | `/cookies/` | `/es/cookies/` | `/en/cookies/` |
| Exempció | `/exempcio/` | `/es/exempcio/` | `/en/exempcio/` |
| Patrocini | `/patrocini/` | `/es/patrocini/` | `/en/patrocini/` |

### Layouts
- `layouts/index.html` — home single-page amb totes les seccions
- `layouts/repte/single.html` — pàgina de repte (hero + contingut + fases + autors si completat)
- `layouts/legal/single.html` — pàgina legal simple (prose)
- `layouts/_default/baseof.html` — header fix 100px, nav, footer, canonical/OG, Schema.org
- `layouts/404.html` — pàgina 404 amb foto i missatges per idioma

---

## 4. Decisions d'arquitectura

| Decisió | Raó |
|---|---|
| Hugo single-page + pàgines internes | Home és landing; reptes/legal són pàgines profundes |
| GitHub Pages | Gratuït, HTTPS automàtic, custom domain |
| Formspree | Sense backend propi, suporta file upload |
| `defaultContentLanguageInSubdir = false` | CA sense prefix, més net |
| `AllTranslations` per lang switch | Modern, correcte amb Hugo multilingüe |
| Sense taxonomies | No calen etiquetes ni categories |

---

## 5. Estil i branding

- **Tipografia:** Beiruti (display), Literata (cos) — carregades des de Google Fonts
- **Paleta:** `--ink: #111110`, `--accent: rgb(254,33,33)` (vermell 112Books)
- **Header fix:** 100px, fons blanc semitransparent amb `backdrop-filter`
- **`.page-content`**: `padding-top: calc(var(--header-h) + 3rem)` per evitar solapament
- **`.prose h2/h3`**: més espai a dalt que a baix

---

## 6. Munt de feina pendent

### Per fer

- [ ] **Posar imatges a `static/img/retrats-lents/`** — la galeria ja està preparada (CSS + JS randomizer + secció al template). Quan hi hagi imatges al directori, es mostraran automàticament (fins a 6 aleatòries cada visita).
- [ ] **Confirmar Formspree** — el primer submit del formulari envia un email de confirmació al teu compte. Cal obrir-lo i confirmar.
- [ ] **Self-host Google Fonts** (evitar dependència externa)
- [ ] **PWA support** (`site.webmanifest`, service worker)

### Resolt

- ✅ Password gate eliminat (2026-06-05)
- ✅ Dates de fases de Retrats Lents eliminades
- ✅ `robots.txt` creat (permet tots els crawlers)
- ✅ `humans.txt` creat
- ✅ `llms.txt` creat (informació per a crawlers d'IA)
- ✅ Galeria preparada: JS randomizer + CSS + secció al template
- ✅ `sync-112revelats.sh` ara accepta `deploy` com a argument per a ús no-interactiu: `./sync-112revelats.sh deploy`

### Notat

- Les pàgines CA **no** tenen `/ca/` prefix (és l'idioma per defecte). Si la URL es visita amb `/ca/...` dona 404 — és comportament esperat.
- `sync-112revelats.sh` té `read -p` interactiu — no es pot usar en no-interactiu. Alternativa: `git push origin main && hugo --minify && git push origin \`git subtree split --prefix public main\`:gh-pages --force`

---

*Última actualització: 2026-06-05*
*Mantenidor: Joan Martínez Serres — joan@linuxbcn.com*
