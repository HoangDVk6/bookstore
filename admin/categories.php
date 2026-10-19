<?php
// QUẢN LÝ DANH MỤC: thêm, sửa, xóa (trên cùng một trang)
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pdo = db();

$editId = (int) ($_GET['edit'] ?? 0);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['category_id'] ?? 0);

    if ($action === 'delete') {
        $st = $pdo->prepare('SELECT COUNT(*) FROM Books WHERE CategoryID = ?');
        $st->execute([$id]);
        if ((int) $st->fetchColumn() > 0) {
            flash('error', 'Không thể xóa danh mục đang có sách. Hãy chuyển sách sang danh mục khác trước.');
        } else {
            $pdo->prepare('DELETE FROM Categories WHERE CategoryID = ?')->execute([$id]);
            flash('success', 'Đã xóa danh mục.');
        }
        redirect('admin/categories.php');
    }

    if ($action === 'save') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $desc = trim((string) ($_POST['description'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) { $errors[] = 'Tên danh mục bắt buộc, tối đa 100 ký tự.'; }
        if (mb_strlen($desc) > 500) { $errors[] = 'Mô tả tối đa 500 ký tự.'; }
        if (!$errors) {
            // Kiểm tra trùng tên (trừ chính danh mục đang sửa)
            $st = $pdo->prepare('SELECT COUNT(*) FROM Categories WHERE CategoryName = ? AND CategoryID <> ?');
            $st->execute([$name, $id]);
            if ((int) $st->fetchColumn() > 0) {
                $errors[] = 'Tên danh mục đã tồn tại.';
            } elseif ($id > 0) {
                $pdo->prepare('UPDATE Categories SET CategoryName = ?, Description = ? WHERE CategoryID = ?')->execute([$name, $desc ?: null, $id]);
                flash('success', 'Đã cập nhật danh mục.');
                redirect('admin/categories.php');
            } else {
                $pdo->prepare('INSERT INTO Categories (CategoryName, Description) VALUES (?, ?)')->execute([$name, $desc ?: null]);
                flash('success', 'Đã thêm danh mục.');
                redirect('admin/categories.php');
            }
        }
        $editId = $id;   // giữ form đang sửa nếu có lỗi
    }
}

$cats = $pdo->query('SELECT c.CategoryID, c.CategoryName, c.Description,
                            (SELECT COUNT(*) FROM Books b WHERE b.CategoryID = c.CategoryID) AS BookCount
                     FROM Categories c ORDER BY c.CategoryName')->fetchAll();
$edit = ['CategoryID' => 0, 'CategoryName' => '', 'Description' => ''];
foreach ($cats as $c) {
    if ((int) $c['CategoryID'] === $editId) { $edit = $c; }
}
if ($errors && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit['CategoryName'] = trim((string) ($_POST['name'] ?? ''));
    $edit['Description'] = trim((string) ($_POST['description'] ?? ''));
}

$pageTitle = 'Quản lý danh mục';
$adminActive = 'categories';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="panel">
            <h5 class="mb-3"><?= $edit['CategoryID'] ? 'Sửa danh mục' : 'Thêm danh mục' ?></h5>
            <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form method="post" class="needs-validation" novalidate>
                <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="category_id" value="<?= (int) $edit['CategoryID'] ?>">
                <div class="mb-3"><label class="form-label" for="name">Tên danh mục *</label>
                    <input class="form-control" id="name" name="name" maxlength="100" value="<?= e($edit['CategoryName']) ?>" required></div>
                <div class="mb-3"><label class="form-label" for="description">Mô tả</label>
                    <textarea class="form-control" id="description" name="description" rows="3" maxlength="500"><?= e($edit['Description']) ?></textarea></div>
                <button class="btn btn-bronze"><?= $edit['CategoryID'] ? 'Lưu thay đổi' : 'Thêm danh mục' ?></button>
                <?php if ($edit['CategoryID']): ?><a class="btn btn-outline-secondary" href="<?= e(url('admin/categories.php')) ?>">Hủy</a><?php endif; ?>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>#</th><th>Tên danh mục</th><th>Mô tả</th><th class="text-center">Số sách</th><th class="text-end">Thao tác</th></tr></thead>
                <tbody>
                <?php foreach ($cats as $c): ?>
                    <tr>
                        <td><?= (int) $c['CategoryID'] ?></td>
                        <td><strong><?= e($c['CategoryName']) ?></strong></td>
                        <td class="small text-muted"><?= e($c['Description']) ?></td>
                        <td class="text-center"><?= (int) $c['BookCount'] ?></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-navy" href="<?= e(url('admin/categories.php?edit=' . (int) $c['CategoryID'])) ?>"><i class="bi bi-pencil"></i> Sửa</a>
                            <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="category_id" value="<?= (int) $c['CategoryID'] ?>">
                                <button class="btn btn-sm btn-outline-danger" data-confirm="Xóa danh mục &quot;<?= e($c['CategoryName']) ?>&quot;?"><i class="bi bi-trash"></i> Xóa</button></form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
