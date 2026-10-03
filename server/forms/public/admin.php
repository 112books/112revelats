<?php
declare(strict_types=1);

require __DIR__ . '/../../webforms/lib.php';

$cfg = wf_config();
$user = (string) ($_SERVER['PHP_AUTH_USER'] ?? '');
$pass = (string) ($_SERVER['PHP_AUTH_PW'] ?? '');
$ok = ($user === (string) $cfg['admin_user']) && hash_equals((string) $cfg['admin_password'], $pass);
if (!$ok) {
    header('WWW-Authenticate: Basic realm="112forms"');
    http_response_code(401);
    echo 'Autenticacio requerida';
    exit;
}

$pdo = wf_db();

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="formularis-112revelats.csv"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    $rows = $pdo->query('SELECT id, form, name, email, ip, mailed, created_at, data FROM submissions ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
    $headerDone = false;
    foreach ($rows as $row) {
        $d = json_decode((string) $row['data'], true);
        if (!is_array($d)) {
            $d = [];
        }
        if (!$headerDone) {
            fputcsv($out, array_merge(['id', 'form', 'name', 'email', 'ip', 'mailed', 'created_at'], array_keys($d)));
            $headerDone = true;
        }
        fputcsv($out, array_merge([$row['id'], $row['form'], $row['name'], $row['email'], $row['ip'], $row['mailed'], $row['created_at']], array_values($d)));
    }
    fclose($out);
    exit;
}

$rows = $pdo->query('SELECT id, form, name, email, ip, mailed, created_at, data FROM submissions ORDER BY id DESC LIMIT 300')->fetchAll(PDO::FETCH_ASSOC);
?><!doctype html>
<html lang="ca"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Formularis 112 Revelats</title>
<style>body{font-family:system-ui,sans-serif;margin:2rem;color:#111}h1{font-size:1.4rem}table{border-collapse:collapse;width:100%;font-size:.85rem}th,td{border:1px solid #ddd;padding:.4rem .5rem;text-align:left;vertical-align:top}th{background:#f4f4f4}.f{white-space:pre-wrap;max-width:460px}.m0{color:#b00}.m1{color:#070}</style></head>
<body><h1>Formularis 112 Revelats</h1>
<p><a href="?export=csv">Exporta CSV</a> - <?= count($rows) ?> entrades (maxim 300)</p>
<table><tr><th>#</th><th>Form</th><th>Nom</th><th>Email</th><th>Data</th><th>Correu</th><th>Dades</th></tr>
<?php foreach ($rows as $row): $d = json_decode((string) $row['data'], true); if (!is_array($d)) { $d = []; } ?>
<tr>
<td><?= (int) $row['id'] ?></td>
<td><?= htmlspecialchars((string) $row['form']) ?></td>
<td><?= htmlspecialchars((string) $row['name']) ?></td>
<td><?= htmlspecialchars((string) $row['email']) ?></td>
<td><?= htmlspecialchars((string) $row['created_at']) ?></td>
<td class="m<?= (int) $row['mailed'] ?>"><?= ((int) $row['mailed']) ? 'si' : 'no' ?></td>
<td class="f"><?php foreach ($d as $k => $v): ?><b><?= htmlspecialchars((string) $k) ?>:</b> <?= htmlspecialchars((string) $v) ?><br><?php endforeach; ?></td>
</tr>
<?php endforeach; ?>
</table></body></html>
