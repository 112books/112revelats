# Claude Context — 112 Revelats

> **Aquest fitxer conté tot el context del projecte.** Llegeix-lo abans de tocar res.
> Actualitza'l quan canviï una decisió d'arquitectura, no pas per a cada commit.
> ⚠️ **Primer llegeix AGENTS.md** — conté la descripció executiva del projecte.

---

## 1. Descripció del projecte

**112 Revelats** és un projecte editorial col·lectiu que impulsa fotollibres amb autors emergents i consolidats. Cada edició proposa un repte temàtic, els participants hi aporten la seva mirada i construeixen un fotollibre únic.

- **Domini de producció:** `112revelats.112books.eu` (futur: `112revelats.org`)
- **Responsable:** Joan Martínez (LinuxBCN) per a 112Books.eu
- **Idioma principal de treball (copy i contingut):** Català
- **Idiomes del lloc:** Català (ca) · Español (es) · English (en)
- **Públic:** Fotògrafs, artistes visuals, editors, mecenes

---

## 2. Arquitectura tècnica

### Generador de llocs
- **Hugo** (v0.159+, tema 100% propi des de zero)
- Format de configuració: **TOML**
- `defaultContentLanguage = "ca"`
- Single-page site — sense taxonomies, sense pàgines internes

### Tema
- Tema **propi**, sense base de tercers
- Estètica: **minimal · fotografia protagonista**
- Colors: neutres (blanc, negre, grisos) — la foto mana
- Tipografia: **IBM Plex Sans** (com la resta de projectes 112Books)
- **Cap framework CSS extern** (ni Bootstrap, ni Tailwind)
- **Cap CDN extern no auditat**

### Formularis
- Formulari de contacte: **Netlify Forms** (attribut `netlify` al `<form>`)
- No cal backend propi
- Camps: nom, email, missatge

### Allotjament
| Entorn | Descripció |
|---|---|
| **Local** | `hugo server` |
| **Producció** | GitHub Pages (branca `gh-pages`) |

### Repositori
- GitHub (`github.com/112books/112-revelats` o dins `DOCS-linuxbcn`)
- Branca `main` → font; branca `gh-pages` → build
- Deploy: `./sync-112revelats.sh` (interactiu: git sync + hugo build + subtree push)

---

## 3. Estructura del lloc (single-page)

El site és una sola pàgina (`/`) amb seccions ancorades:

| Secció | ID | Contingut |
|---|---|---|
| Hero | `#inici` | Títol, subtítol, botons CTA (PDF + Participa) |
| Objectius | `#objectius` | Què és 112 Revelats (prosa markdown des de `_index.{lang}.md`) |
| Patrocini | `#patrocini` | Targetes: Participa + Mecenes |
| Reptes | `#reptes` | Timeline de 6 fases numerades |
| Arrencant el dia | `#arrencant` | Secció de descàrrega del PDF |
| Contacte | `#contacte` | Formulari Netlify + FAQ |
| Footer | `#footer` | Xarxes socials, legal, powered-by LinuxBCN |

**Navegació:** menú horitzontal fixat a dalt, enllaços a cada secció. Commutador d'idioma (`AllTranslations`). Menú desplegable en mòbil.

---

## 4. Estructura de directoris Hugo

```
112-revelats/
├── hugo.toml                    # Configuració multilingüe
├── AGENTS.md                    # Descripció executiva per a IA/humans
├── CLAUDE.md                    # Aquest fitxer (context tècnic complet)
├── sync-112revelats.sh          # Script de deploy interactiu
├── .gitignore
├── i18n/
│   ├── ca.toml                  # Traduccions català
│   ├── es.toml                  # Traduccions castellà
│   └── en.toml                  # Traduccions anglès
├── content/
│   ├── _index.ca.md             # Contingut CA (frontmatter + body)
│   ├── _index.es.md             # Contingut ES
│   └── _index.en.md             # Contingut EN
├── assets/
│   ├── css/main.css             # Estils (IBM Plex Sans, responsive)
│   └── js/main.js               # Mobile nav toggle
├── layouts/
│   ├── _default/
│   │   └── baseof.html          # Base: header, nav, lang switcher, footer
│   └── index.html               # Single-page layout (totes les seccions)
└── static/
    ├── pdf/                     # Darrera versió dels PDFs
    │   ├── arrencant-el-dia.pdf
    │   └── dossier-premsa-112-revelats.pdf
    └── img/                     # Imatges estàtiques (favicon, etc.)
```

---

## 5. Contingut multilingüe

- **`content/_index.{lang}.md`**: Conté el cos de la secció "Objectius" (markdown). La portada del Hugo `index.html` l'insereix via `{{ .Content }}`.
- **`i18n/{lang}.toml`**: Totes les cadenes de la UI (navegació, títols de secció, botons, etiquetes, placeholder).
- Totes les seccions excepte "Objectius" s'alimenten exclusivament de `i18n/*.toml`.
- El peu de pàgina (social, legal) es llegeix de `i18n` i de `config.toml` (`[params]`).

---

## 6. Branding i estil

### Identitat visual
- **Paleta:** Blanc (#fff), negre (#111110), gris fosc (#444), gris clar (#999, #ddd, #f5f5f5)
- **Tipografia:** IBM Plex Sans (self-hosted o system stack)
- **Fotografia:** La protagonista absoluta — estil documental, en blanc i negre o color natural
- **Disseny:** Net, generós en espai en blanc, centrat. Res distreu de les imatges.

### CSS
- Sense variables CSS prefixades (projecte petit, estructura plana)
- Classes semàntiques: `.hero`, `.section`, `.card`, `.step`, `.timeline`, `.contact-form`
- Responsive: breakpoint a 640px (mòbil)

### To de veu
- **Llengua principal:** Català
- **To:** Entusiasta, obert, col·laboratiu — som una comunitat de fotògrafs
- **Públic:** Artistes, no tècnics — llenguatge clar, sense jargon
- **Inclusiu:** tracte de "tu" (informal però respectuós), llenguatge neutre
- **Exemples de frases:** "Participa amb el teu projecte", "Converteix-te en mecene", "Descobreix el matí des d'una mirada única"

---

## 7. Decisions d'arquitectura

| Decisió | Raó |
|---|---|
| Hugo single-page | El site és una landing page, no necessita CMS ni pàgines internes |
| GitHub Pages | Gratuït, HTTPS automàtic, sense manteniment de servidor |
| Netlify Forms | Sense backend propi, gratis per a volum baix de formularis |
| IBM Plex Sans | Coherència amb la família 112Books (linuxbcn.com, etc.) |
| `AllTranslations` per lang switch | Modern, no-deprecated, correcte amb multilingüe Hugo |
| Sense taxonomies | Single-page, no cal |

### Descartat (vs. WordPress original)
- **Contact Form 7** → Netlify Forms (més senzill, sense PHP)
- **Galetes de consentiment (Cookie Notice)** → només galetes tècniques (no cal avís si no n'hi ha de tercers)
- **Plugins (YARPP, iLightbox, Event Post, etc.)** → no calen en HTML estàtic
- **Cerca** → no cal (single-page)

---

## 8. Estàndards de qualitat

### HTML
- HTML5 semàntic: `<main>`, `<nav>`, `<section>`, `<footer>`
- `lang` per idioma en cada pàgina
- Meta viewport per a mòbil
- Open Graph: `og:title`, `og:description`, `og:image` (pendent d'afegir al `<head>`)

### Performance
- Build minificat (`hugo --minify`)
- CSS i JS amb fingerprinter (`resources.Minify | resources.Fingerprint`)
- Sense JS bloquejant al `<head>`
- Imatges: format modern (WebP si escau), `loading="lazy"`

### SEO tècnic
- `<meta name="description">` per pàgina
- Sitemap XML generat per Hugo
- `robots.txt` permís total (pendent de crear)
- Schema.org: pendent (LocalBusiness/Organization de 112Books)

### Accessibilitat
- Contrast suficient (blanc/negre)
- Navegació per teclat
- `aria-label` al botó de menú mòbil
- Skip-to-content link (pendent d'afegir)

---

## 9. Flux de treball

```bash
# Desenvolupament local
hugo server

# Build de producció
hugo --minify

# Deploy complet (git sync + build + GitHub Pages)
./sync-112revelats.sh
```

El script `sync-112revelats.sh` és interactiu:
1. Opció 1: `git status`
2. Opció 2: git sync (add → commit → pull --rebase → push)
3. Opció 3: build local (`hugo --minify`)
4. Opció 4: deploy complet (sync + build + subtree push a `gh-pages`)

---

## 10. Nomenclatura i convencions

### Fitxers
- Contingut per idioma: `_index.{lang}.md` (ca, es, en)
- Traduccions: `i18n/{lang}.toml` amb format `[key]` → `other = "valor"`

### IDs d'ancoratge
- Lowercase sense accents: `#inici`, `#objectius`, `#patrocini`, `#reptes`, `#arrencant`, `#contacte`

### Branques Git
- `main` — font del projecte
- `gh-pages` — build de producció (generada automàticament)

### Commits
```
tipus: descripció breu en català

Tipus: feat · fix · style · content · config · docs · refactor
Exemple: "content: afegeix textos de la seccio objectius"
         "style: ajusta espaiat del hero en mobil"
         "fix: menu mobil no tancava al clicar enllaç"
```

---

## 11. Lliçons apreses (per al mantenidor IA)

- 🔴 **`i18n` en Hugo requereix format `[key]\n  other = "valor"`** — el format pla no funciona.
- 🔴 **`with .Site.GetPage "/"` canvia el context** — el `{{ i18n }}` dins seu perd l'idioma actual. Millor usar `.` directament (la home page ja té el context correcte).
- 🔴 **`.Site.Languages` està deprecated des de Hugo v0.156** — usar `.AllTranslations` + `.Language.LanguageName` per al commutador d'idioma.
- 🟢 **`disableKinds = ["taxonomy", "term"]`** s'ha de posar a nivell arrel i repetir a cada `[languages.XX]` per eliminar completament les taxonomies en multilingüe.
- 🟢 **Hugo single-page**: el `.Content` de la home page funciona si hi ha un `_index.md`. El body del markdown es renderitza via `{{ .Content }}`.

---

## 12. Pendents / Millores futures

- [ ] Afegir `robots.txt` a `static/` (permetre tots els crawlers)
- [ ] Afegir Open Graph meta tags al `<head>` (og:title, og:description, og:image)
- [ ] Afegir Schema.org JSON-LD `Organization` (112Books)
- [ ] Afegir skip-to-content link per accessibilitat
- [ ] Self-host IBM Plex Sans (evitar Google Fonts)
- [ ] Afegir PWA support (`site.webmanifest`, service worker)
- [ ] Afegir `humans.txt`
- [ ] Afegir `llms.txt` per a crawlers d'IA
- [ ] Publicar a GitHub i configurar GitHub Pages

---

*Última actualització: 2026-06-05*
*Mantenidor: Joan Martínez Serres — joan@linuxbcn.com*
