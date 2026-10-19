<?php
// ĐĂNG NHẬP
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('index.php');
}

// Chỉ cho phép chuyển hướng sau đăng nhập tới các trang trong danh sách này
$allowed = ['checkout.php', 'orders.php', 'profile.php', 'cart.php', 'admin/index.php'];
$redirectTo = in_array($_GET['redirect'] ?? '', $allowed, true) ? $_GET['redirect'] : '';

$errors = [];
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errors[] = 'Vui lòng nhập email và mật khẩu.';
    } else {
        $st = db()->prepare('SELECT UserID, FullName, Email, PasswordHash, Role, IsActive FROM Users WHERE Email = ?');
        $st->execute([$email]);
        $u = $st->fetch();
        // password_verify so sánh mật khẩu nhập vào với chuỗi băm đã lưu
        if (!$u || !password_verify($password, $u['PasswordHash'])) {
            $errors[] = 'Email hoặc mật khẩu không đúng.';
        } elseif (!(int) $u['IsActive']) {
            $errors[] = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.';
        } else {
            session_regenerate_id(true);   // chống session fixation
            $_SESSION['user'] = ['id' => (int) $u['UserID'], 'name' => $u['FullName'], 'email' => $u['Email'], 'role' => $u['Role']];
            flash('success', 'Đăng nhập thành công. Xin chào ' . $u['FullName'] . '!');
            if ($redirectTo !== '') {
                redirect($redirectTo === 'admin/index.php' && $u['Role'] !== 'admin' ? 'index.php' : $redirectTo);
            }
            redirect($u['Role'] === 'admin' ? 'admin/index.php' : 'index.php');
        }
    }
}

$pageTitle = 'Đăng nhập';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="panel">
        <h1 class="h3 text-center mb-4">Đăng nhập</h1>
        <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
        <form method="post" class="needs-validation" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus>
                <div class="invalid-feedback">Vui lòng nhập email hợp lệ.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Mật khẩu</label>
                <input class="form-control" type="password" id="password" name="password" required>
                <div class="invalid-feedback">Vui lòng nhập mật khẩu.</div>
            </div>
            <button class="btn btn-navy w-100">Đăng nhập</button>
        </form>
        <p class="text-center mt-3 mb-0">Chưa có tài khoản? <a href="<?= e(url('register.php')) ?>">Đăng ký ngay</a></p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
