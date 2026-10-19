<?php
// QUẢN LÝ THÔNG TIN TÀI KHOẢN: sửa hồ sơ và đổi mật khẩu
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$user = current_user();
$pdo = db();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'profile') {
        $name = trim((string) ($_POST['full_name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) { $errors[] = 'Họ tên không hợp lệ.'; }
        if (!preg_match('/^(0|\+84)\d{9,10}$/', $phone)) { $errors[] = 'Số điện thoại không hợp lệ.'; }
        if (mb_strlen($address) > 255) { $errors[] = 'Địa chỉ tối đa 255 ký tự.'; }
        if (!$errors) {
            $pdo->prepare('UPDATE Users SET FullName = ?, Phone = ?, Address = ? WHERE UserID = ?')
                ->execute([$name, $phone, $address ?: null, $user['id']]);
            $_SESSION['user']['name'] = $name;
            flash('success', 'Đã cập nhật thông tin tài khoản.');
            redirect('profile.php');
        }
    } elseif (($_POST['action'] ?? '') === 'password') {
        $cur = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $st = $pdo->prepare('SELECT PasswordHash FROM Users WHERE UserID = ?');
        $st->execute([$user['id']]);
        if (!password_verify($cur, (string) $st->fetchColumn())) {
            $errors[] = 'Mật khẩu hiện tại không đúng.';
        } elseif (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
            $errors[] = 'Mật khẩu mới tối thiểu 8 ký tự, gồm cả chữ và số.';
        } elseif ($new !== (string) ($_POST['new_password_confirm'] ?? '')) {
            $errors[] = 'Mật khẩu nhập lại không khớp.';
        } else {
            $pdo->prepare('UPDATE Users SET PasswordHash = ? WHERE UserID = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            flash('success', 'Đã đổi mật khẩu.');
            redirect('profile.php');
        }
    }
}

$st = $pdo->prepare('SELECT FullName, Email, Phone, Address, CreatedAt FROM Users WHERE UserID = ?');
$st->execute([$user['id']]);
$me = $st->fetch();

$pageTitle = 'Tài khoản';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-4">Tài khoản của tôi</h1>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="panel">
            <h5 class="mb-3">Thông tin cá nhân</h5>
            <form method="post" class="needs-validation" novalidate>
                <?= csrf_field() ?><input type="hidden" name="action" value="profile">
                <div class="mb-3"><label class="form-label">Email</label><input class="form-control" value="<?= e($me['Email']) ?>" disabled></div>
                <div class="mb-3"><label class="form-label" for="full_name">Họ và tên</label><input class="form-control" id="full_name" name="full_name" maxlength="100" value="<?= e($me['FullName']) ?>" required></div>
                <div class="mb-3"><label class="form-label" for="phone">Số điện thoại</label><input class="form-control" id="phone" name="phone" pattern="(0|\+84)[0-9]{9,10}" value="<?= e($me['Phone']) ?>" required></div>
                <div class="mb-3"><label class="form-label" for="address">Địa chỉ</label><input class="form-control" id="address" name="address" maxlength="255" value="<?= e($me['Address']) ?>"></div>
                <button class="btn btn-navy">Lưu thay đổi</button>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <h5 class="mb-3">Đổi mật khẩu</h5>
            <form method="post" class="needs-validation" novalidate>
                <?= csrf_field() ?><input type="hidden" name="action" value="password">
                <div class="mb-3"><label class="form-label" for="cp">Mật khẩu hiện tại</label><input class="form-control" type="password" id="cp" name="current_password" required></div>
                <div class="mb-3"><label class="form-label" for="np">Mật khẩu mới</label><input class="form-control" type="password" id="np" name="new_password" minlength="8" required></div>
                <div class="mb-3"><label class="form-label" for="np2">Nhập lại mật khẩu mới</label><input class="form-control" type="password" id="np2" name="new_password_confirm" required></div>
                <button class="btn btn-bronze">Đổi mật khẩu</button>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
