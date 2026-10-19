<?php
// QUẢN LÝ KHÁCH HÀNG: xem danh sách, tìm kiếm, khóa/mở khóa tài khoản
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pdo = db();
$me = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['user_id'] ?? 0);
    // Chỉ thao tác trên tài khoản khách hàng, không khóa nhầm Admin
    $st = $pdo->prepare("UPDATE Users SET IsActive = CASE WHEN IsActive = 1 THEN 0 ELSE 1 END WHERE UserID = ? AND Role = 'customer'");
    $st->execute([$id]);
    flash($st->rowCount() ? 'success' : 'error', $st->rowCount() ? 'Đã đổi trạng thái tài khoản.' : 'Không tìm thấy tài khoản khách hàng.');
    header('Location: ' . safe_back($_POST['back'] ?? null, 'admin/users.php'));
    exit;
}

$q = trim((string) ($_GET['q'] ?? ''));
$where = "u.Role = 'customer'";
$params = [];
if ($q !== '') {
    $where .= ' AND (u.FullName LIKE ? OR u.Email LIKE ? OR u.Phone LIKE ?)';
    $params = [like_pattern($q), like_pattern($q), like_pattern($q)];
}
$st = $pdo->prepare("SELECT COUNT(*) FROM Users u WHERE $where");
$st->execute($params);
$pg = paginate((int) $st->fetchColumn(), (int) ($_GET['page'] ?? 1), 10);

$st = $pdo->prepare("SELECT u.UserID, u.FullName, u.Email, u.Phone, u.IsActive, u.CreatedAt,
                            (SELECT COUNT(*) FROM Orders o WHERE o.UserID = u.UserID) AS OrderCount
                     FROM Users u WHERE $where ORDER BY u.UserID DESC
                     OFFSET {$pg['offset']} ROWS FETCH NEXT {$pg['per']} ROWS ONLY");
$st->execute($params);
$users = $st->fetchAll();

$pageTitle = 'Quản lý khách hàng';
$adminActive = 'users';
require __DIR__ . '/../includes/admin_header.php';
?>
<form class="d-flex gap-2 mb-3" method="get">
    <input class="form-control" style="max-width:320px" name="q" value="<?= e($q) ?>" placeholder="Tìm theo tên, email, số điện thoại">
    <button class="btn btn-navy"><i class="bi bi-search"></i> Tìm</button>
</form>
<div class="panel p-0 table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>#</th><th>Họ tên</th><th>Email</th><th>Điện thoại</th><th class="text-center">Đơn hàng</th><th>Ngày đăng ký</th><th>Trạng thái</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int) $u['UserID'] ?></td>
                <td><?= e($u['FullName']) ?></td>
                <td><?= e($u['Email']) ?></td>
                <td><?= e($u['Phone']) ?></td>
                <td class="text-center"><?= (int) $u['OrderCount'] ?></td>
                <td><?= e(format_date($u['CreatedAt'], false)) ?></td>
                <td>
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int) $u['UserID'] ?>">
                        <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                        <button class="btn btn-sm btn-<?= $u['IsActive'] ? 'success' : 'danger' ?>"
                                data-confirm="<?= $u['IsActive'] ? 'Khóa tài khoản này?' : 'Mở khóa tài khoản này?' ?>"><?= $u['IsActive'] ? 'Hoạt động' : 'Đã khóa' ?></button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$users): ?><tr><td colspan="7" class="text-center text-muted py-4">Không có khách hàng phù hợp.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<div class="mt-3"><?php render_pagination($pg, array_filter(['q' => $q])); ?></div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
