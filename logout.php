<?php
// ĐĂNG XUẤT: xóa phiên làm việc rồi quay về trang chủ
require_once __DIR__ . '/includes/bootstrap.php';
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
session_start();           // mở phiên mới để hiển thị thông báo
flash('info', 'Bạn đã đăng xuất.');
redirect('index.php');
