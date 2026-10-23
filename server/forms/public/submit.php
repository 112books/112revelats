<?php
declare(strict_types=1);

require __DIR__ . '/../../webforms/lib.php';

wf_cors();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    wf_out(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$raw = (string) file_get_contents('php://input');
$data = [];
$ct = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
if (stripos($ct, 'application/json') !== false) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $data = $decoded;
    }
} else {
    $data = $_POST;
}

// Honeypot: si ve farcit, descartem en silenci.
if (!empty($data['_gotcha'])) {
    wf_out(200, ['ok' => true]);
}

// Anti-bot: honeypot (_gotcha) + rate limit. No usem cap marca de temps del
// client: els rellotges dels dispositius poden anar desfasats i fariem perdre
// enviaments reals amb un fals "ok".

$form = preg_replace('/[^a-z_-]/', '', strtolower((string) ($data['form'] ?? 'contacte')));
if ($form === '' || $form === null) {
    $form = 'contacte';
}

$allowedFields = [
    'nom', 'nom-contacte', 'email', 'telefon', 'web', 'instagram', 'facebook',
    'biografia', 'titol-projecte', 'descripcio', 'enllac-imatges',
    'accepta-condicions', 'autoritzacio-us', 'rgpd', 'empresa', 'cif',
    'adreca-fiscal', 'tipus', 'import', 'altres', 'visibilitat-web',
    'visibilitat-publicacio', 'comentaris', 'missatge', 'portfolio', 'llistacorreu',
    'subject', 'lang',
];

$fields = [];
foreach ($allowedFields as $key) {
    if (array_key_exists($key, $data)) {
        $fields[$key] = wf_clean($data[$key]);
    }
}

// Idioma del formulari (ens ajuda a respondre en la llengua correcta).
if (!empty($fields['lang'])) {
    $fields['lang'] = in_array($fields['lang'], ['ca', 'es', 'en'], true) ? $fields['lang'] : '';
    if ($fields['lang'] === '') {
        unset($fields['lang']);
    }
}

$required = [
    'inscripcio'        => ['nom', 'email', 'enllac-imatges'],
    'inscripcio-rapida' => ['nom', 'email'],
    'collaboracio'      => ['nom-contacte', 'email', 'cif', 'adreca-fiscal', 'tipus', 'visibilitat-web', 'visibilitat-publicacio', 'rgpd'],
    'contacte'          => ['nom', 'email'],
];
$need = $required[$form] ?? ['nom', 'email'];
$missing = [];
foreach ($need as $key) {
    if (($fields[$key] ?? '') === '') {
        $missing[] = $key;
    }
}
if ($missing) {
    wf_out(422, ['ok' => false, 'error' => 'missing_fields', 'fields' => $missing]);
}

if (!filter_var((string) ($fields['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
    wf_out(422, ['ok' => false, 'error' => 'invalid_email', 'fields' => ['email']]);
}
if (!empty($fields['enllac-imatges']) && !filter_var((string) $fields['enllac-imatges'], FILTER_VALIDATE_URL)) {
    wf_out(422, ['ok' => false, 'error' => 'invalid_url', 'fields' => ['enllac-imatges']]);
}

$ip = wf_ip();
if (!wf_rate_ok($ip, $form)) {
    header('Retry-After: 900');
    wf_out(429, ['ok' => false, 'error' => 'rate_limited']);
}

$name = (string) ($fields['nom'] ?? ($fields['nom-contacte'] ?? ''));
$email = (string) ($fields['email'] ?? '');
$ua = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300, 'UTF-8');
$createdAt = gmdate('Y-m-d H:i:s') . ' UTC';

$pdo = wf_db();
$st = $pdo->prepare('INSERT INTO submissions(form, name, email, ip, ua, data, created_at) VALUES(?, ?, ?, ?, ?, ?, ?)');
$st->execute([$form, $name, $email, $ip, $ua, json_encode($fields, JSON_UNESCAPED_UNICODE), $createdAt]);
$id = (int) $pdo->lastInsertId();

$lines = [];
$lines[] = 'Nova entrada de formulari: ' . strtoupper($form);
$lines[] = 'Data: ' . $createdAt;
$lines[] = 'IP: ' . $ip;
$lines[] = str_repeat('-', 40);
foreach ($fields as $k => $v) {
    $lines[] = $k . ': ' . str_replace("\n", ' | ', (string) $v);
}
$lines[] = str_repeat('-', 40);
if ($form === 'inscripcio-rapida') {
    $lines[] = 'Nota: formulari rapid de la home; pot faltar l\'enllac de les imatges.';
    $lines[] = 'Per completar la inscripcio: https://112revelats.112books.eu/legal/formulari/';
    $lines[] = str_repeat('-', 40);
}
$lines[] = 'Panell: https://112books.eu/api/admin.php';
$body = implode("\n", $lines);

$labels = [
    'inscripcio'        => 'Inscripcio Retrats Lents',
    'inscripcio-rapida' => 'Inscripcio rapida Retrats Lents',
    'collaboracio'      => 'Col·laboracio',
    'contacte'          => 'Contacte',
];
$label = $labels[$form] ?? 'Contacte';

$subjectExtra = trim((string) ($fields['subject'] ?? ''));
if ($subjectExtra !== '') {
    $subjectExtra = mb_substr($subjectExtra, 0, 120, 'UTF-8');
}
$subject = ($subjectExtra !== '' ? $subjectExtra : '[' . $label . ']')
    . ' — ' . ($name !== '' ? $name : 'sense nom');

$mailed = wf_send_mail($subject, $body, $email) ? 1 : 0;

$pdo->prepare('UPDATE submissions SET mailed = ? WHERE id = ?')->execute([$mailed, $id]);

wf_out(200, ['ok' => true, 'stored' => true, 'mailed' => (bool) $mailed]);
