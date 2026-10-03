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
    $v = str_replace(["
", ""], "
", $v);
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

function wf_send_mail(string $subject, string $body, string $replyTo): bool {
    $cfg = wf_config();
    $from = (string) $cfg['sender'];
    $name = (string) $cfg['sender_name'];
    $headers = [];
    $headers[] = 'From: ' . mb_encode_mimeheader($name, 'UTF-8') . ' <' . $from . '>';
    if (filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'X-Mailer: 112forms';
    return @mail((string) $cfg['recipient'], $subject, $body, implode("\r\n", $headers), '-f' . $from);
}
