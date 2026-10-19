<?php
// ĐĂNG KÝ TÀI KHOẢN KHÁCH HÀNG
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('index.php');
}

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => '', 'address' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($old as $k => $_) {
        $old[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');

    if ($old['full_name'] === '' || mb_strlen($old['full_name']) > 100) {
        $errors[] = 'Họ tên không được để trống và tối đa 100 ký tự.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['email']) > 150) {
        $errors[] = 'Email không hợp lệ.';
    }
    if (!preg_match('/^(0|\+84)\d{9,10}$/', $old['phone'])) {
        $errors[] = 'Số điện thoại không hợp lệ (ví dụ 0901234567).';
    }
    if (mb_strlen($old['address']) > 255) {
        $errors[] = 'Địa chỉ tối đa 255 ký tự.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = 'Mật khẩu tối thiểu 8 ký tự, gồm cả chữ và số.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Mật khẩu nhập lại không khớp.';
    }

    if (!$errors) {
        $pdo = db();
        $st = $pdo->prepare('SELECT COUNT(*) FROM Users WHERE Email = ?');
        $st->execute([$old['email']]);
        if ((int) $st->fetchColumn() > 0) {
            $errors[] = 'Email này đã được đăng ký.';
        } else {
            // Mật khẩu được băm (bcrypt) trước khi lưu, không lưu văn bản thuần
            $st = $pdo->prepare("INSERT INTO Users (FullName, Email, PasswordHash, Phone, Address, Role)
                                 VALUES (?, ?, ?, ?, ?, 'customer')");
            $st->execute([$old['full_name'], $old['email'], password_hash($password, PASSWORD_DEFAULT), $old['phone'], $old['address'] ?: null]);
            flash('success', 'Đăng ký thành công! Hãy đăng nhập để mua sách.');
            redirect('login.php');
        }
    }
}

$pageTitle = 'Đăng ký';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap" style="max-width:560px">
    <div class="panel">
        <h1 class="h3 text-center mb-4">Đăng ký tài khoản</h1>
        <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" class="needs-validation" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="full_name">Họ và tên *</label>
                <input class="form-control" id="full_name" name="full_name" maxlength="100" value="<?= e($old['full_name']) ?>" required>
                <div class="invalid-feedback">Vui lòng nhập họ tên.</div>
            </div>
            <div class="row">
                <div class="col-md-7 mb-3">
                    <label class="form-label" for="email">Email *</label>
                    <input class="form-control" type="email" id="email" name="email" maxlength="150" value="<?= e($old['email']) ?>" required>
                    <div class="invalid-feedback">Email chưa hợp lệ.</div>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label" for="phone">Số điện thoại *</label>
                    <input class="form-control" id="phone" name="phone" pattern="(0|\+84)[0-9]{9,10}" value="<?= e($old['phone']) ?>" required>
                    <div class="invalid-feedback">Ví dụ: 0901234567.</div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="address">Địa chỉ</label>
                <input class="form-control" id="address" name="address" maxlength="255" value="<?= e($old['address']) ?>">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="password">Mật khẩu *</label>
                    <input class="form-control" type="password" id="password" name="password" minlength="8" required>
                    <div class="form-text">Tối thiểu 8 ký tự, gồm chữ và số.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="password_confirm">Nhập lại mật khẩu *</label>
                    <input class="form-control" type="password" id="password_confirm" name="password_confirm" required>
                    <div class="invalid-feedback">Mật khẩu nhập lại không khớp.</div>
                </div>
            </div>
            <button class="btn btn-bronze w-100">Tạo tài khoản</button>
        </form>
        <p class="text-center mt-3 mb-0">Đã có tài khoản? <a href="<?= e(url('login.php')) ?>">Đăng nhập</a></p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
