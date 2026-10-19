<?php
/**
 * Tệp khởi tạo: được include ở đầu mỗi trang.
 * - nạp cấu hình, bật session, nạp kết nối CSDL và các hàm dùng chung.
 */
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/includes/functions.php';

define('BASE_URL', rtrim((string) config('app.base_url', ''), '/'));
mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Ho_Chi_Minh');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

require_once ROOT_PATH . '/includes/db.php';
