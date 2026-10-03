# Pla: formularis propis amb SMTP propi (112books.eu)

> Objectiu: **eliminar Formspree de tots els formularis** i enviar les notificacions
> amb l'**SMTP propi** del servidor Dinaserver (`vl28359.dinaserver.com`,
> 82.98.166.123, Debian 11), sota el domini `112books.eu`.
> **Cap rastre de serveis externs de formularis**, ni al web ni al DNS.
>
> Estat: **esborrany per validar** · 2026-10-03

---

## 1. Per què

| Ara (Formspree Free) | Amb backend propi + SMTP propi |
|---|---|
| Límit de **50 enviaments/mes** → errors als participants | Sense límit artificial |
| Notificacions des de domini compartit → **spam** | Enviament autenticat des de `112books.eu` |
| Sense pujada de fitxers al pla gratuït | **Fotos al teu servidor** (sense SwissTransfer) |
| Dades i dependència d'un tercer | Sobirania total (servidor a Espanya) |

## 2. Estat actual (auditoria 2026-10-03)

**Servidor**: `112books.eu` → 82.98.166.123 (WordPress, `Server: HTTPd`).
`www` i `mail` → mateix servidor. `112books.com` → Squarespace (no és aquest servidor).

**DNS** (NS: `ns*.gestiondecuenta.com`, panell Dinaserver):
- SPF: `v=spf1 a mx include:spf.sendinblue.com ~all` (Brevo).
- DMARC: `p=quarantine`, rua a Brevo i webmaster@linuxbcn.com.
- **DKIM: no configurat** (cap selector `*._domainkey.112books.eu`).
- **Cap subdomini de formularis externs** (ni Formspree, ni Typeform, ni Jotform…). Net.
- PTR de 82.98.166.123 = `vl28359.dinaserver.com` (caldrà canviar-lo).
- `112revelats.112books.eu` → CNAME `112books.github.io` (GitHub Pages; hosting, no formulari).

**Codi** (referències a Formspree a eliminar):
- `hugo.toml` → `formAction = "https://formspree.io/f/maqzqynz"`
- `layouts/index.html` → 2 formularis amb `{{ $.Site.Params.formAction }}`
- `content/legal/formulari/index.{ca,es,en}.md` → `action="https://formspree.io/f/maqzqynz"`
- `content/legal/col-labora/index.{ca,es,en}.md` → `action="https://formspree.io/f/maqzqynz"`
- `CLAUDE.md` → secció Formspree

## 3. Arquitectura proposada

```
[GitHub Pages: 112revelats.112books.eu]
      |  fetch() POST (JSON o multipart)  +  CORS restringit
      v
[Apache/nginx vhost: form.112books.eu]  (TLS Let's Encrypt)
      |
      +-- PHP 8  /submit   -> valida, antispam, desa i envia
      |      +--> SQLite   /var/lib/112forms/forms.sqlite
      |      +--> Uploads  /var/lib/112forms/uploads/   (fora del webroot)
      |      +--> SMTP PROPI: 127.0.0.1:25 (Postfix local) + OpenDKIM
      |                          -> joan@linuxbcn.com + hola@112books.eu
      |
      +-- /admin (Basic auth + IP allowlist) -> llistar/descarregar/exportar
```

**Decisions tècniques:**
- **PHP 8 + SQLite**: ja present (WordPress); cap runtime nou.
- **PHPMailer** cap a `127.0.0.1:25` (Postfix local). Sense relays externs.
- **DKIM propi** amb OpenDKIM i selector `mail` (TXT a publicar).
- **Sense JavaScript**: opció POST natiu + `303` cap a `/gracies/` (evita CORS).
  Recomanat: `fetch()` amb JSON i missatge inline, i POST natiu de reserva.

## 4. Requisits previs

1. **Accés**: SSH amb `sudo` (o panell Dinaserver) per vhost/subdomini/DNS.
2. **DNS** (panell Dinaserver): `form.112books.eu A 82.98.166.123`.
3. **TLS**: Let's Encrypt per `form.112books.eu`.
4. **SMTP propi operatiu**: Postfix al servidor + OpenDKIM.
   - **Comprovar que el port 25 de sortida no estigui bloquejat** (Dinaserver sovint el
     bloqueja). Prova: `timeout 5 bash -c '</dev/tcp/gmail-smtp-in.l.google.com/25'`.
   - Si està bloquejat: demanar a Dinaserver que l'obri, o fer servir el seu *smarthost*.
5. **PTR/rDNS**: demanar a Dinaserver que `82.98.166.123` reverteixi a `mail.112books.eu`.
6. **DKIM**: publicar `mail._domainkey.112books.eu` (TXT) i signar a l'sortida.

## 5. Pla per fases

### Fase 0 — Descoberta al servidor (read-only)
```bash
lsb_release -a; uname -a; whoami; id
sudo -n true && echo "sudo OK" || echo "sense sudo"
php -v; php -m | grep -iE 'sqlite|pdo|fileinfo|mbstring|openssl'
apache2 -v 2>/dev/null; nginx -v 2>/dev/null; apache2ctl -S 2>/dev/null
which certbot && certbot --version
systemctl is-active postfix opendkim 2>/dev/null
postconf -n 2>/dev/null | grep -iE 'myhostname|mydestination|relayhost|inet_interfaces'
timeout 5 bash -c '</dev/tcp/gmail-smtp-in.l.google.com/25' && echo "port 25 Obert" || echo "port 25 bloquejat"
which opendkim-genkey; ls /etc/opendkim 2>/dev/null
ss -tlnp; df -h /
```

### Fase 1 — DNS + TLS
- Afegir l'A de `form.112books.eu` al panell Dinaserver.
- Vhost nou (Apache `/etc/apache2/sites-available/` o nginx `/etc/nginx/sites-available/`).
- `certbot --apache -d form.112books.eu` (o `--nginx`).

### Fase 2 — API
```
/var/www/112forms/
  public/index.php      # router /submit /health
  src/Store.php         # SQLite (prepared statements)
  src/Mailer.php        # PHPMailer -> 127.0.0.1:25
  src/RateLimit.php     # 10/h i 3/min per IP
  src/Upload.php        # MIME, mida, nom aleatori
  admin/index.php       # llistat + descàrrega + CSV
/etc/112forms/config.php  # secrets (600, root:www-data)
/var/lib/112forms/        # forms.sqlite + uploads/
```
- `POST /submit`: valida, honeypot `_gotcha`, temps mínim, rate limit; desa a SQLite;
  envia correu; retorna `{ok:true}` o error JSON.
- CORS: `Access-Control-Allow-Origin: https://112revelats.112books.eu` + `OPTIONS`.

### Fase 3 — SMTP PROPI i entregabilitat
1. Postfix: `myhostname = mail.112books.eu`, `inet_interfaces = loopback-only` (o el que calgui),
   TLS, i relé només per a locals (no open relay).
2. **OpenDKIM**: generar clau, signar tot el que surti de 112books.eu.
3. Publicar al DNS: `mail._domainkey.112books.eu` (TXT de DKIM).
4. SPF: ja inclou el servidor via `a mx` (verificar que `a`/`mx` resols a 82.98.166.123).
5. PTR → `mail.112books.eu` (Dinaserver).
6. Provar DMARC a `check-auth@verifier.port25.com` i a Gmail/linuxbcn.com.
7. Enviament des de `formulari@112books.eu` (o `no-reply@`) cap a joan + hola.

### Fase 4 — Pujada de fotos (substitueix SwissTransfer)
- `<input type="file" multiple accept="image/jpeg,image/png,image/tiff">` (max 4, 10 MB).
- Validar amb `finfo`, desar fora del webroot amb nom aleatori, registrar hash.
- Pujar límits: PHP `upload_max_filesize=12M`, `post_max_size=50M`;
  Apache `LimitRequestBody` / nginx `client_max_body_size 50m`.

### Fase 5 — Admin
- `/admin` amb Basic auth (hash fort) + IP allowlist + HTTPS.
- Llistar, veure detall, descarregar adjunts, exportar CSV, esborrar (GDPR).

### Fase 6 — Integració amb Hugo
- `hugo.toml`: **treure `formAction`** i posar `formEndpoint = "https://form.112books.eu/submit"`.
- `static/js/forms.js`: intercepta `submit`, fa `fetch`, mostra èxit/error inline.
- Actualitzar `layouts/index.html` i `content/legal/{formulari,col-labora}` (ca/es/en).
- i18n per als missatges.

### Fase 7 — Retirada TOTAL de Formspree
- [ ] `hugo.toml`: eliminat `formAction` de Formspree.
- [ ] `action` de Formspree fora dels 6 fitxers de contingut.
- [ ] `layouts/index.html` sense `Site.Params.formAction` de Formspree.
- [ ] `CLAUDE.md` actualitzat.
- [ ] `grep -rIn formspree` al repo → **només** a la documentació històrica (si es vol, zero).
- [ ] DNS: confirmar que no hi ha cap registre cap a serveis de formularis externs (ja està net).
- [ ] Desactivar/esborrar el formulari a Formspree i, si es vol, el compte.

### Fase 8 — Enduriment, còpies i GDPR
- `unattended-upgrades`, `ufw`, `fail2ban`, logs rotats.
- Còpia diària: `sqlite3 .backup` + `rsync` dels uploads, fora del servidor.
- Retenció (p. ex. 12 mesos) i actualitzar la política de privacitat.
- **Debian 11 és EOL** (LTS fins 2026-08-31): planificar Debian 12/13.
- WordPress viu al mateix servidor: mantenir-lo actualitzat.

## 6. Fora d'abast (altres serveis externs detectats)

No són formularis, però si la idea és sobirania total, caldria abordar-los:
- **Google Fonts** (`fonts.googleapis.com`, `fonts.gstatic.com`) → autoallotjar.
- **GoatCounter** (`112revelats.goatcounter.com`) → propi o fora.
- **DuckDuckGo** (cercador del footer) → propi o fora.
- **Brevo** a l'SPF: només si WordPress l'usa per correu; si no, treure'l de l'SPF.
- **jsdelivr** (CDN) → revisar on s'usa.

## 7. Decisions pendents

1. `112books.eu` confirmat com a domini (.com és Squarespace)?
2. Tens `sudo`/root o només panell? Postfix ja està instal·lat i actiu?
3. Servidor web: Apache o nginx?
4. Subdomini: `form.112books.eu`?
5. Vols **pujada de fotos nativa**? (sí/no)
6. Destinataris: `joan@linuxbcn.com` + `hola@112books.eu`?
7. El port 25 de sortida està obert? (ho comprovem a la Fase 0)
8. Com treballem: em passes sortides de les comandes o accés SSH controlat?

## 8. Migració i marxa enrere

1. Aixecar l'API + TLS i provar amb `curl`.
2. Passar-hi el formulari de contacte; verificar correu + BD.
3. Activar uploads i passar-hi el formulari del concurs.
4. Conviure amb Formspree uns dies (opcional).
5. Retirar Formspree i netejar el codi (Fase 7).
