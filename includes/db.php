<?php
/**
 * Kết nối SQL Server bằng PDO_SQLSRV (Microsoft Drivers for PHP for SQL Server).
 * Hàm db() trả về cùng một đối tượng PDO trong suốt một lần xử lý yêu cầu.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $c = config('db');
    // TrustServerCertificate=1: cho phép chứng chỉ tự ký của SQL Server cục bộ (ODBC Driver 18 mặc định bật mã hóa).
    $dsn = 'sqlsrv:Server=' . $c['server'] . ';Database=' . $c['database'] . ';TrustServerCertificate=1';

    try {
        $pdo = new PDO($dsn, $c['username'] ?: null, $c['password'] ?: null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // lỗi SQL ném ra ngoại lệ
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable $ex) {
        fail_page('Không kết nối được cơ sở dữ liệu SQL Server.', $ex);
    }
    return $pdo;
}

/** Hiển thị trang lỗi thân thiện; chi tiết lỗi chỉ hiện khi 'debug' = true (không lộ mật khẩu kết nối). */
function fail_page(string $message, ?Throwable $ex = null): void
{
    http_response_code(500);
    if ($ex) {
        error_log('[bookstore] ' . $ex->getMessage());
    }
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><title>Lỗi hệ thống</title>'
        . '<style>body{font-family:Segoe UI,Arial,sans-serif;background:#fbf7ef;color:#14213d;padding:40px}'
        . '.box{max-width:720px;margin:auto;background:#fff;border:1px solid #e3d9c4;border-radius:12px;padding:28px}'
        . 'code{display:block;background:#f4efe3;padding:10px;border-radius:6px;white-space:pre-wrap;font-size:13px}</style></head><body>'
        . '<div class="box"><h2>Đã xảy ra lỗi</h2><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    if ($ex && config('app.debug')) {
        // Che mật khẩu nếu có xuất hiện trong thông báo
        $msg = str_replace((string) config('db.password'), '******', $ex->getMessage());
        echo '<p>Chi tiết (chế độ debug):</p><code>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</code>'
            . '<p>Hãy chạy <b>check-connection.php</b> để kiểm tra từng bước cấu hình.</p>';
    }
    echo '</div></body></html>';
    exit;
}
