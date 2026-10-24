<?php
declare(strict_types=1);

function wf_config(): array {
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/config.php';
    }
    return $cfg;
}

function wf_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $cfg = wf_config();
        $dir = dirname($cfg['db_path']);
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        $pdo = new PDO('sqlite:' . $cfg['db_path']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA busy_timeout=5000');
        $pdo->exec('CREATE TABLE IF NOT EXISTS submissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            form TEXT NOT NULL,
            name TEXT,
            email TEXT,
            ip TEXT,
            ua TEXT,
            data TEXT NOT NULL,
            mailed INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL
        )');
        $pdo->exec('CREATE TABLE IF NOT EXISTS rate (
            ip TEXT NOT NULL,
            form TEXT NOT NULL,
            ts INTEGER NOT NULL
        )');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_rate ON rate(ip, form, ts)');
    }
    return $pdo;
}

function wf_out(int $code, array $payload): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function wf_cors(): void {
    $cfg = wf_config();
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '' && in_array($origin, $cfg['allowed_origins'], true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Max-Age: 86400');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function wf_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $v = explode(',', (string) $_SERVER[$k])[0];
            return trim($v);
        }
    }
    return '0.0.0.0';
}

function wf_clean($v, int $max = 2000): string {
    $v = (string) ($v ?? '');
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    $v = strip_tags($v);
    $v = trim($v);
    return mb_substr($v, 0, $max, 'UTF-8');
}

function wf_rate_ok(string $ip, string $form): bool {
    $cfg = wf_config();
    $limit = (int) ($cfg['rate_per_hour'] ?? 6);
    $pdo = wf_db();
    $now = time();
    $pdo->prepare('DELETE FROM rate WHERE ts < ?')->execute([$now - 3600]);
    $st = $pdo->prepare('SELECT COUNT(*) FROM rate WHERE ip = ? AND form = ? AND ts >= ?');
    $st->execute([$ip, $form, $now - 3600]);
    if ((int) $st->fetchColumn() >= $limit) {
        return false;
    }
    $pdo->prepare('INSERT INTO rate(ip, form, ts) VALUES(?, ?, ?)')->execute([$ip, $form, $now]);
    return true;
}

function wf_email_image(): string {
    $cfg = wf_config();
    $path = (string) ($cfg['email_image'] ?? __DIR__ . '/assets/email-header.jpg');
    return (is_file($path) && is_readable($path)) ? $path : '';
}

function wf_text_to_html(string $text): string {
    $paras = preg_split('/\n{2,}/', trim($text));
    if (!is_array($paras)) {
        $paras = [$text];
    }
    $out = '';
    foreach ($paras as $p) {
        $p = htmlspecialchars($p, ENT_QUOTES, 'UTF-8');
        $p = preg_replace_callback('~https?://[^\s<]+~', static function (array $m): string {
            return '<a href="' . $m[0] . '" style="color:#fe2121;text-decoration:underline;">' . $m[0] . '</a>';
        }, $p) ?? $p;
        $p = nl2br($p, false);
        $out .= '<p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#444444;">' . $p . '</p>';
    }
    return $out;
}

function wf_html_email(string $heading, string $contentHtml, string $ctaUrl = '', string $ctaLabel = '', string $preheader = '', string $footerNote = '', string $imageCid = '', string $lang = 'ca'): string {
    $e = static function (string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    };
    $bg = '#f5f5f5';
    $ink = '#111110';
    $accent = '#fe2121';
    $serif = "Georgia,'Times New Roman',serif";
    $sans = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";

    $header = $imageCid !== ''
        ? '<img src="cid:' . $e($imageCid) . '" width="600" alt="112 Revelats — Projecte de fotollibres col·lectius" style="display:block;width:100%;max-width:600px;height:auto;border:0;">'
        : '<div style="padding:34px 32px;background:' . $ink . ';font-family:' . $serif . ';font-size:30px;line-height:1.2;color:#ffffff;">112 Revelats</div>';

    $pre = $preheader !== ''
        ? '<div style="display:none;font-size:1px;color:' . $bg . ';line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">' . $e($preheader) . '</div>'
        : '';

    $cta = '';
    if ($ctaUrl !== '' && $ctaLabel !== '') {
        $cta = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:10px 0 0;"><tr><td style="border-radius:6px;background:' . $ink . ';">'
            . '<a href="' . $e($ctaUrl) . '" style="display:inline-block;padding:12px 22px;font-family:' . $sans . ';font-size:15px;font-weight:600;line-height:1.2;color:#ffffff;text-decoration:none;border-radius:6px;">' . $e($ctaLabel) . '</a>'
            . '</td></tr></table>';
    }

    $note = $footerNote !== ''
        ? '<p style="margin:10px 0 0;font-size:12px;line-height:1.5;color:#999999;">' . $e($footerNote) . '</p>'
        : '';

    return '<!doctype html>'
        . '<html lang="' . $e($lang) . '"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<meta name="x-apple-disable-message-reformatting">'
        . '<title>' . $e($heading) . '</title></head>'
        . '<body style="margin:0;padding:0;background:' . $bg . ';">' . $pre
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:' . $bg . ';"><tr><td align="center" style="padding:24px 12px;">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;">'
        . '<tr><td style="background:' . $ink . ';font-size:0;line-height:0;">' . $header . '</td></tr>'
        . '<tr><td style="height:4px;background:' . $accent . ';font-size:0;line-height:0;">&nbsp;</td></tr>'
        . '<tr><td style="padding:28px 32px 6px;font-family:' . $serif . ';font-size:24px;line-height:1.25;color:' . $ink . ';">' . $e($heading) . '</td></tr>'
        . '<tr><td style="padding:0 32px 10px;font-family:' . $sans . ';font-size:15px;line-height:1.65;color:#444444;">' . $contentHtml . $cta . '</td></tr>'
        . '<tr><td style="padding:20px 32px;background:' . $bg . ';font-family:' . $sans . ';font-size:12px;line-height:1.5;color:#999999;">'
        . '<a href="https://112revelats.112books.eu/" style="color:#666666;text-decoration:none;">112 Revelats</a> · '
        . '<a href="https://112books.eu/" style="color:#666666;text-decoration:none;">112Books.eu</a> · '
        . '<a href="mailto:hola@112books.eu" style="color:#666666;text-decoration:none;">hola@112books.eu</a>'
        . $note . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

function wf_build_mime(string $plain, string $html, ?string $image, string $imageCid, ?array $attachment): array {
    $eol = "\r\n";
    $norm = static function (string $s) use ($eol): string {
        return preg_replace('/\r\n|\r|\n/', $eol, $s) ?? $s;
    };
    $plain = $norm($plain);
    $html = $norm($html);

    $alt = 'alt-' . bin2hex(random_bytes(8));
    $rel = 'rel-' . bin2hex(random_bytes(8));
    $mix = 'mix-' . bin2hex(random_bytes(8));

    $hasImage = $image !== null && $image !== '';
    $hasAttach = $attachment !== null && isset($attachment['data'], $attachment['filename']);
    $body = '';

    if ($hasAttach) {
        $body .= '--' . $mix . $eol;
        $body .= $hasImage
            ? 'Content-Type: multipart/related; boundary="' . $rel . '"; type="multipart/alternative"' . $eol . $eol
            : 'Content-Type: multipart/alternative; boundary="' . $alt . '"' . $eol . $eol;
    }

    $body .= '--' . $alt . $eol;
    $body .= 'Content-Type: text/plain; charset=UTF-8' . $eol;
    $body .= 'Content-Transfer-Encoding: 8bit' . $eol . $eol;
    $body .= $plain . $eol;

    $body .= '--' . $alt . $eol;
    $body .= 'Content-Type: text/html; charset=UTF-8' . $eol;
    $body .= 'Content-Transfer-Encoding: 8bit' . $eol . $eol;
    $body .= $html . $eol;
    $body .= '--' . $alt . '--' . $eol;

    if ($hasImage) {
        $body .= '--' . $rel . $eol;
        $body .= 'Content-Type: image/jpeg; name="email-header.jpg"' . $eol;
        $body .= 'Content-Transfer-Encoding: base64' . $eol;
        $body .= 'Content-ID: <' . $imageCid . '>' . $eol;
        $body .= 'Content-Disposition: inline; filename="email-header.jpg"' . $eol . $eol;
        $body .= chunk_split(base64_encode((string) $image), 76, $eol) . $eol;
        if ($hasAttach) {
            $body .= '--' . $rel . '--' . $eol;
        }
    }

    if ($hasAttach) {
        $filename = str_replace(['"', "\r", "\n"], '', (string) $attachment['filename']);
        $mime = (string) ($attachment['mime'] ?? 'application/octet-stream');
        $body .= '--' . $mix . $eol;
        $body .= 'Content-Type: ' . $mime . '; name="' . $filename . '"' . $eol;
        $body .= 'Content-Transfer-Encoding: base64' . $eol;
        $body .= 'Content-Disposition: attachment; filename="' . $filename . '"' . $eol . $eol;
        $body .= chunk_split(base64_encode((string) $attachment['data']), 76, $eol) . $eol;
        $body .= '--' . $mix . '--' . $eol;
        $contentType = 'Content-Type: multipart/mixed; boundary="' . $mix . '"';
    } elseif ($hasImage) {
        $body .= '--' . $rel . '--' . $eol;
        $contentType = 'Content-Type: multipart/related; boundary="' . $rel . '"; type="multipart/alternative"';
    } else {
        $contentType = 'Content-Type: multipart/alternative; boundary="' . $alt . '"';
    }

    return ['content_type' => $contentType, 'body' => $body];
}

function wf_mail(string $to, string $subject, string $body, string $replyTo = '', ?string $from = null, ?string $fromName = null, ?string $html = null, ?string $imagePath = null, string $imageCid = 'header112', ?array $attachment = null): bool {
    $cfg = wf_config();
    $from = $from ?? (string) $cfg['sender'];
    $fromName = $fromName ?? (string) $cfg['sender_name'];
    $headers = [];
    $headers[] = 'From: ' . mb_encode_mimeheader($fromName, 'UTF-8') . ' <' . $from . '>';
    if (filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'X-Mailer: 112forms';

    if ($html !== null) {
        $image = null;
        if ($imagePath !== null && $imagePath !== '' && is_file($imagePath) && is_readable($imagePath)) {
            $image = (string) file_get_contents($imagePath);
        }
        $mime = wf_build_mime($body, $html, $image, $imageCid, $attachment);
        $headers[] = $mime['content_type'];
        $body = $mime['body'];
    } else {
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
    }

    // Capçalera Subject codificada (noms i temes amb accents o símbols).
    $subject = mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n", 0);
    return @mail($to, $subject, $body, implode("\r\n", $headers), '-f' . $from);
}

function wf_mail_html(string $to, string $subject, string $plain, string $replyTo, string $heading, string $contentHtml, string $ctaUrl = '', string $ctaLabel = '', ?array $attachment = null, ?string $fromName = null, string $lang = 'ca', string $footerNote = ''): bool {
    $image = wf_email_image();
    $cid = $image !== '' ? 'header112' : '';
    $html = wf_html_email($heading, $contentHtml, $ctaUrl, $ctaLabel, $heading, $footerNote, $cid, $lang);
    return wf_mail($to, $subject, $plain, $replyTo, null, $fromName, $html, $image !== '' ? $image : null, 'header112', $attachment);
}

function wf_send_mail(string $subject, string $body, string $replyTo): bool {
    $cfg = wf_config();
    return wf_mail((string) $cfg['recipient'], $subject, $body, $replyTo);
}

// Correu automatic de confirmacio al participant, en el seu idioma.
function wf_confirmation_mail(string $form, array $fields, string $name, string $email): bool {
    $lang = (string) ($fields['lang'] ?? 'ca');
    if (!in_array($lang, ['ca', 'es', 'en'], true)) {
        $lang = 'ca';
    }
    $site = 'https://112revelats.112books.eu';
    $paths = [
        'ca' => ['bases' => '/legal/bases/', 'privacy' => '/privacitat/', 'form' => '/legal/formulari/', 'deadline' => '1 de novembre de 2026', 'challenge' => '/repte/retrats-lents/', 'patrocini' => '/patrocini/', 'home' => '/'],
        'es' => ['bases' => '/es/legal/bases/', 'privacy' => '/es/privacitat/', 'form' => '/es/legal/formulari/', 'deadline' => '1 de noviembre de 2026', 'challenge' => '/es/repte/retrats-lents/', 'patrocini' => '/es/patrocini/', 'home' => '/es/'],
        'en' => ['bases' => '/en/legal/bases/', 'privacy' => '/en/privacitat/', 'form' => '/en/legal/formulari/', 'deadline' => '1 November 2026', 'challenge' => '/en/repte/retrats-lents/', 'patrocini' => '/en/patrocini/', 'home' => '/en/'],
    ];
    $p = $paths[$lang];
    $hello = ['ca' => 'Hola', 'es' => 'Hola', 'en' => 'Hello'][$lang];
    $basesLabel = ['ca' => 'Bases', 'es' => 'Bases', 'en' => 'Terms'][$lang];
    $privacyLabel = ['ca' => 'Privacitat', 'es' => 'Privacidad', 'en' => 'Privacy'][$lang];
    $ignore = [
        'ca' => 'Si no has enviat cap formulari, pots ignorar aquest correu.',
        'es' => 'Si no has enviado ningún formulario, puedes ignorar este correo.',
        'en' => "If you didn't submit a form, you can ignore this email.",
    ][$lang];
    $sign = '112 Revelats — 112Books.eu';
    $greeting = $hello . ($name !== '' ? ' ' . $name : '') . ',';

    if ($form === 'inscripcio') {
        $subject = [
            'ca' => 'Hem rebut la teva inscripció — Retrats Lents',
            'es' => 'Hemos recibido tu inscripción — Retratos Lentos',
            'en' => 'We have received your submission — Slow Portraits',
        ][$lang];
        $intro = [
            'ca' => 'Hem rebut la teva proposta per al repte Retrats Lents.',
            'es' => 'Hemos recibido tu propuesta para el reto Retratos Lentos.',
            'en' => 'We have received your proposal for the Slow Portraits challenge.',
        ][$lang];
        $next = [
            'ca' => "Termini d'enviament: " . $p['deadline'] . ".\nSi la teva proposta és seleccionada, et contactarem per treballar el fotollibre.",
            'es' => "Plazo de envío: " . $p['deadline'] . ".\nSi tu propuesta es seleccionada, te contactaremos para trabajar el fotolibro.",
            'en' => "Submission deadline: " . $p['deadline'] . ".\nIf your proposal is selected, we will contact you to work on the photobook.",
        ][$lang];
        $body = $greeting . "\n\n" . $intro . "\n\n" . $next . "\n\n"
            . $basesLabel . ': ' . $site . $p['bases'] . "\n"
            . $privacyLabel . ': ' . $site . $p['privacy'];
    } elseif ($form === 'inscripcio-rapida') {
        $subject = [
            'ca' => 'Hem rebut les teves dades — Retrats Lents',
            'es' => 'Hemos recibido tus datos — Retratos Lentos',
            'en' => 'We have received your details — Slow Portraits',
        ][$lang];
        $msg = [
            'ca' => "Hem rebut les teves dades. Per completar la inscripció al repte Retrats Lents, envia'ns la proposta amb el formulari complet:",
            'es' => 'Hemos recibido tus datos. Para completar la inscripción al reto Retratos Lentos, envíanos la propuesta con el formulario completo:',
            'en' => 'We have received your details. To complete your registration for the Slow Portraits challenge, send us your proposal using the full form:',
        ][$lang];
        $deadlineLine = [
            'ca' => 'Termini: ' . $p['deadline'] . '.',
            'es' => 'Plazo: ' . $p['deadline'] . '.',
            'en' => 'Deadline: ' . $p['deadline'] . '.',
        ][$lang];
        $body = $greeting . "\n\n" . $msg . "\n" . $site . $p['form'] . "\n\n" . $deadlineLine;
    } elseif ($form === 'collaboracio') {
        $subject = [
            'ca' => 'Hem rebut la teva proposta de col·laboració — 112 Revelats',
            'es' => 'Hemos recibido tu propuesta de colaboración — 112 Revelats',
            'en' => 'We have received your collaboration proposal — 112 Revelats',
        ][$lang];
        $msg = [
            'ca' => "Hem rebut la teva proposta de col·laboració amb 112 Revelats.\nEt contactarem per concretar-ne els detalls.",
            'es' => "Hemos recibido tu propuesta de colaboración con 112 Revelats.\nTe contactaremos para concretar los detalles.",
            'en' => "We have received your collaboration proposal for 112 Revelats.\nWe will contact you to work out the details.",
        ][$lang];
        $body = $greeting . "\n\n" . $msg . "\n\n" . $privacyLabel . ': ' . $site . $p['privacy'];
    } else {
        $subject = [
            'ca' => 'Hem rebut el teu missatge — 112 Revelats',
            'es' => 'Hemos recibido tu mensaje — 112 Revelats',
            'en' => 'We have received your message — 112 Revelats',
        ][$lang];
        $msg = [
            'ca' => "Hem rebut el teu missatge i et respondrem tan aviat com puguem.\nSi vols afegir informació, respon a aquest correu.",
            'es' => "Hemos recibido tu mensaje y te responderemos lo antes posible.\nSi quieres añadir información, responde a este correo.",
            'en' => "We have received your message and will reply as soon as we can.\nIf you want to add information, just reply to this email.",
        ][$lang];
        $body = $greeting . "\n\n" . $msg . "\n\n" . $privacyLabel . ': ' . $site . $p['privacy'];
    }

    $core = $body;
    $body .= "\n\n" . $sign . "\n\n" . $ignore;

    $headings = [
        'inscripcio'        => ['ca' => 'Inscripció rebuda', 'es' => 'Inscripción recibida', 'en' => 'Submission received'],
        'inscripcio-rapida' => ['ca' => 'Dades rebudes', 'es' => 'Datos recibidos', 'en' => 'Details received'],
        'collaboracio'      => ['ca' => 'Proposta rebuda', 'es' => 'Propuesta recibida', 'en' => 'Proposal received'],
        'contacte'          => ['ca' => 'Missatge rebut', 'es' => 'Mensaje recibido', 'en' => 'Message received'],
    ];
    $heading = $headings[$form][$lang] ?? $headings['contacte'][$lang];

    $ctaUrl = $site . $p['home'];
    $ctaLabel = ['ca' => 'Visitar el web', 'es' => 'Visitar el sitio', 'en' => 'Visit the site'][$lang];
    if ($form === 'inscripcio') {
        $ctaUrl = $site . $p['challenge'];
        $ctaLabel = ['ca' => 'Veure el repte', 'es' => 'Ver el reto', 'en' => 'View the challenge'][$lang];
    } elseif ($form === 'inscripcio-rapida') {
        $ctaUrl = $site . $p['form'];
        $ctaLabel = ['ca' => 'Completar la inscripció', 'es' => 'Completar la inscripción', 'en' => 'Complete your registration'][$lang];
    } elseif ($form === 'collaboracio') {
        $ctaUrl = $site . $p['patrocini'];
        $ctaLabel = ['ca' => 'Veure el patrocini', 'es' => 'Ver el patrocinio', 'en' => 'See sponsorship'][$lang];
    }

    return wf_mail_html($email, $subject, $body, 'hola@112books.eu', $heading, wf_text_to_html($core), $ctaUrl, $ctaLabel, null, '112 Revelats', $lang, $ignore);
}
function wf_convocatoria(array $row, array $fields): string {
    $conv = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($fields['convocatoria'] ?? '')));
    if ($conv === '' || $conv === null) {
        $map = [
            'inscripcio'        => 'retrats-lents',
            'inscripcio-rapida' => 'retrats-lents',
            'collaboracio'      => 'col-laboracio',
            'contacte'          => 'contacte',
        ];
        $conv = $map[(string) ($row['form'] ?? '')] ?? 'general';
    }
    return $conv;
}

function wf_slug(string $s): string {
    $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    if ($s === false) {
        $s = '';
    }
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    $s = trim($s, '-');
    return $s !== '' ? substr($s, 0, 60) : 'sense-nom';
}

function wf_yaml_quote($v): string {
    $v = (string) $v;
    $v = str_replace(['\\', '"', "\r", "\n", "\t"], ['\\\\', '\\"', '', '\\n', '\\t'], $v);
    return '"' . $v . '"';
}

function wf_submission_markdown(array $row, array $fields, bool $confirmation): string {
    $form = (string) ($row['form'] ?? 'contacte');
    $conv = wf_convocatoria($row, $fields);

    $title = 'Inscripció';
    if ($form === 'collaboracio') {
        $title = 'Col·laboració';
    } elseif ($form === 'contacte') {
        $title = 'Contacte';
    }
    if ($conv === 'retrats-lents' && ($form === 'inscripcio' || $form === 'inscripcio-rapida')) {
        $title .= ' — Retrats Lents';
    }

    $labels = [
        'nom' => 'Nom',
        'nom-contacte' => 'Nom de contacte',
        'email' => 'Correu electrònic',
        'telefon' => 'Telèfon',
        'web' => 'Web',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'biografia' => 'Biografia',
        'titol-projecte' => 'Títol del projecte',
        'descripcio' => 'Descripció',
        'enllac-imatges' => 'Enllaç de les imatges',
        'accepta-condicions' => 'Accepta les condicions',
        'autoritzacio-us' => "Autoritza l'ús",
        'rgpd' => 'Accepta la privacitat',
        'empresa' => 'Empresa / entitat',
        'cif' => 'CIF/NIF',
        'adreca-fiscal' => 'Adreça fiscal',
        'tipus' => 'Tipus de col·laboració',
        'import' => 'Import',
        'altres' => 'Altres col·laboracions',
        'visibilitat-web' => 'Visibilitat al web',
        'visibilitat-publicacio' => 'Visibilitat a la publicació',
        'comentaris' => 'Comentaris',
        'missatge' => 'Missatge',
        'portfolio' => 'Portfolio',
        'llistacorreu' => 'Vol rebre el butlletí',
    ];

    $md = "---\n";
    $md .= 'id: ' . (int) ($row['id'] ?? 0) . "\n";
    $md .= 'formulari: ' . wf_yaml_quote($form) . "\n";
    $md .= 'convocatoria: ' . wf_yaml_quote($conv) . "\n";
    $md .= 'data: ' . wf_yaml_quote((string) ($row['created_at'] ?? '')) . "\n";
    $md .= 'nom: ' . wf_yaml_quote((string) ($row['name'] ?? '')) . "\n";
    $md .= 'email: ' . wf_yaml_quote((string) ($row['email'] ?? '')) . "\n";
    $md .= 'ip: ' . wf_yaml_quote((string) ($row['ip'] ?? '')) . "\n";
    $md .= 'confirmacio: ' . ($confirmation ? 'true' : 'false') . "\n";
    $md .= 'tags: [112revelats, formulari, ' . $conv . "]\n";
    $md .= "---\n\n";

    $md .= '# ' . $title . "\n\n";
    $md .= 'Dades rebudes el ' . (string) ($row['created_at'] ?? '') . ".\n\n";

    if (($form === 'inscripcio' || $form === 'inscripcio-rapida') && !empty($fields['enllac-imatges'])) {
        $url = (string) $fields['enllac-imatges'];
        $md .= "## Enllaç de les imatges\n\n[" . $url . '](' . $url . ")\n\n";
    }

    $md .= "## Camps del formulari\n\n";
    foreach ($fields as $k => $v) {
        if (in_array($k, ['convocatoria', 'subject', 'lang'], true)) {
            continue;
        }
        $label = $labels[$k] ?? $k;
        $val = trim((string) $v);
        if ($val === '') {
            $val = '—';
        } else {
            if (in_array($k, ['enllac-imatges', 'web', 'portfolio'], true) && filter_var($val, FILTER_VALIDATE_URL)) {
                $val = '[' . $val . '](' . $val . ')';
            }
            if (in_array($k, ['rgpd', 'accepta-condicions', 'autoritzacio-us', 'llistacorreu'], true)) {
                $val = in_array(strtolower($val), ['si', 'sí', 's', 'yes'], true) ? 'sí' : 'no';
            }
            $val = str_replace("\n", "\n  ", $val);
        }
        $md .= '- **' . $label . ':** ' . $val . "\n";
    }

    $md .= "\n## Metadades\n\n";
    $md .= '- **Formulari:** ' . $form . "\n";
    $md .= '- **Convocatòria:** ' . $conv . "\n";
    if (!empty($fields['lang'])) {
        $md .= '- **Idioma:** ' . $fields['lang'] . "\n";
    }
    if (!empty($fields['subject'])) {
        $md .= '- **Assumpte:** ' . $fields['subject'] . "\n";
    }
    $md .= '- **IP:** ' . (string) ($row['ip'] ?? '') . "\n";
    $md .= '- **Confirmació enviada:** ' . ($confirmation ? 'sí' : 'no') . "\n";
    $md .= "\n---\n\nGenerat automàticament per 112 Revelats · https://112books.eu/api/admin.php\n";

    return $md;
}

function wf_store_submission_md(array $row, array $fields, bool $confirmation): ?array {
    $cfg = wf_config();
    $base = rtrim((string) ($cfg['submissions_path'] ?? __DIR__ . '/submissions'), '/');
    $conv = wf_convocatoria($row, $fields);
    $dir = $base . '/' . $conv;
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        return null;
    }
    $ts = strtotime((string) ($row['created_at'] ?? '')) ?: time();
    $stamp = gmdate('Y-m-d_His', $ts);
    $file = $stamp . '_id' . (int) ($row['id'] ?? 0) . '_' . wf_slug((string) ($row['name'] ?? '')) . '.md';
    $path = $dir . '/' . $file;
    $md = wf_submission_markdown($row, $fields, $confirmation);
    if (@file_put_contents($path, $md, LOCK_EX) === false) {
        return null;
    }
    @chmod($path, 0600);
    return ['path' => $path, 'filename' => $file, 'content' => $md];
}
