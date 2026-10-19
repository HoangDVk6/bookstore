<?php

define('ROOT_PATH', __DIR__);
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: text/html; charset=utf-8');
$rows = [];
$add = function (string $name, bool $ok, string $detail) use (&$rows) {
    $rows[] = [$name, $ok, $detail];
};

$add('Phiên bản PHP', version_compare(PHP_VERSION, '8.0.0', '>='), PHP_VERSION . ' (' . (PHP_ZTS ? 'Thread Safe' : 'Non Thread Safe') . ', ' . (PHP_INT_SIZE * 8) . '-bit)');
$add('Extension pdo_sqlsrv', extension_loaded('pdo_sqlsrv'), extension_loaded('pdo_sqlsrv') ? 'Đã nạp' : 'CHƯA nạp: xem mục "Cài driver" trong docs/HUONG_DAN_CAI_DAT.md');
$add('Extension mbstring', extension_loaded('mbstring'), extension_loaded('mbstring') ? 'Đã nạp' : 'Cần bật extension=mbstring trong php.ini');
$add('Extension fileinfo', extension_loaded('fileinfo'), extension_loaded('fileinfo') ? 'Đã nạp' : 'Cần bật extension=fileinfo trong php.ini (dùng khi tải ảnh bìa)');
$add('Thư mục ảnh bìa ghi được', is_writable(__DIR__ . '/assets/images/books'), 'assets/images/books');

if (extension_loaded('pdo_sqlsrv')) {
    $c = config('db');
    $dsn = 'sqlsrv:Server=' . $c['server'] . ';Database=' . $c['database'] . ';TrustServerCertificate=1';
    try {
        $pdo = new PDO($dsn, $c['username'] ?: null, $c['password'] ?: null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $add('Kết nối SQL Server', true, 'Thành công tới ' . $c['server'] . ' / ' . $c['database']);
        $ver = $pdo->query('SELECT @@VERSION')->fetchColumn();
        $add('Phiên bản SQL Server', true, strtok((string) $ver, "\n"));
        foreach (['Users', 'Categories', 'Books', 'Orders', 'OrderDetails'] as $t) {
            $n = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
            $add("Bảng $t", true, "$n dòng");
        }
    } catch (Throwable $ex) {
        $msg = str_replace((string) $c['password'], '******', $ex->getMessage());
        $add('Kết nối SQL Server', false, $msg);
    }
}
?><!DOCTYPE html>
<html lang="vi"><head><meta charset="utf-8"><title>Kiểm tra cấu hình</title>
<style>body{font-family:Segoe UI,Arial,sans-serif;background:#fbf7ef;color:#14213d;padding:30px}table{border-collapse:collapse;background:#fff;max-width:900px;width:100%}
td,th{border:1px solid #e3d9c4;padding:8px 12px;text-align:left;vertical-align:top}.ok{color:#18794e;font-weight:600}.bad{color:#b42318;font-weight:600}</style></head>
<body><h2>Kiểm tra môi trường</h2>
<table><tr><th>Mục kiểm tra</th><th>Kết quả</th><th>Chi tiết</th></tr>
<?php foreach ($rows as [$n, $ok, $d]): ?>
<tr><td><?= htmlspecialchars($n) ?></td><td class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? 'OK' : 'LỖI' ?></td><td><?= htmlspecialchars($d) ?></td></tr>
<?php endforeach; ?>
</table></body></html>
