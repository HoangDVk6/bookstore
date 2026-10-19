<?php
// QUẢN LÝ SÁCH: danh sách, tìm kiếm, lọc, phân trang, xóa/ẩn
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['book_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'delete') {
        // Sách đã nằm trong đơn hàng thì không xóa được (khóa ngoại) -> chuyển sang ẩn để giữ lịch sử đơn hàng
        $st = $pdo->prepare('SELECT COUNT(*) FROM OrderDetails WHERE BookID = ?');
        $st->execute([$id]);
        if ((int) $st->fetchColumn() > 0) {
            $pdo->prepare('UPDATE Books SET IsActive = 0 WHERE BookID = ?')->execute([$id]);
            flash('warning', 'Sách đã có trong đơn hàng nên không thể xóa hẳn. Sách đã được chuyển sang trạng thái ẩn.');
        } else {
            $st = $pdo->prepare('SELECT ImagePath FROM Books WHERE BookID = ?');
            $st->execute([$id]);
            $img = $st->fetchColumn();
            $pdo->prepare('DELETE FROM Books WHERE BookID = ?')->execute([$id]);
            delete_uploaded_cover($img ?: null);
            flash('success', 'Đã xóa sách.');
        }
    } elseif ($action === 'toggle') {
        $pdo->prepare('UPDATE Books SET IsActive = CASE WHEN IsActive = 1 THEN 0 ELSE 1 END WHERE BookID = ?')->execute([$id]);
        flash('success', 'Đã đổi trạng thái hiển thị của sách.');
    }
    header('Location: ' . safe_back($_POST['back'] ?? null, 'admin/books.php'));
    exit;
}

$q = trim((string) ($_GET['q'] ?? ''));
$cat = (int) ($_GET['cat'] ?? 0);
$where = ['1 = 1'];
$params = [];
if ($q !== '') {
    $where[] = '(b.Title LIKE ? OR b.Author LIKE ?)';
    $params[] = $params[] = like_pattern($q);
}
if ($cat > 0) {
    $where[] = 'b.CategoryID = ?';
    $params[] = $cat;
}
$whereSql = implode(' AND ', $where);

$st = $pdo->prepare("SELECT COUNT(*) FROM Books b WHERE $whereSql");
$st->execute($params);
$pg = paginate((int) $st->fetchColumn(), (int) ($_GET['page'] ?? 1), 10);

$st = $pdo->prepare("SELECT b.BookID, b.Title, b.Author, b.Price, b.Stock, b.ImagePath, b.IsActive, c.CategoryName
                     FROM Books b JOIN Categories c ON c.CategoryID = b.CategoryID
                     WHERE $whereSql ORDER BY b.BookID DESC
                     OFFSET {$pg['offset']} ROWS FETCH NEXT {$pg['per']} ROWS ONLY");
$st->execute($params);
$books = $st->fetchAll();
$cats = $pdo->query('SELECT CategoryID, CategoryName FROM Categories ORDER BY CategoryName')->fetchAll();

$pageTitle = 'Quản lý sách';
$adminActive = 'books';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
    <form class="d-flex flex-wrap gap-2" method="get">
        <input class="form-control" style="width:240px" name="q" value="<?= e($q) ?>" placeholder="Tìm tên sách, tác giả">
        <select class="form-select" style="width:200px" name="cat">
            <option value="0">Tất cả danh mục</option>
            <?php foreach ($cats as $c): ?><option value="<?= (int) $c['CategoryID'] ?>" <?= $cat === (int) $c['CategoryID'] ? 'selected' : '' ?>><?= e($c['CategoryName']) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-navy"><i class="bi bi-search"></i> Lọc</button>
    </form>
    <a class="btn btn-bronze" href="<?= e(url('admin/book-form.php')) ?>"><i class="bi bi-plus-lg"></i> Thêm sách mới</a>
</div>

<div class="panel p-0 table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>#</th><th>Bìa</th><th>Tên sách</th><th>Danh mục</th><th class="text-end">Giá</th><th class="text-center">Tồn</th><th>Hiển thị</th><th class="text-end">Thao tác</th></tr></thead>
        <tbody>
        <?php foreach ($books as $b): ?>
            <tr>
                <td><?= (int) $b['BookID'] ?></td>
                <td><img class="thumb" src="<?= e(book_image($b['ImagePath'])) ?>" alt=""></td>
                <td><strong><?= e($b['Title']) ?></strong><div class="small text-muted"><?= e($b['Author']) ?></div></td>
                <td><?= e($b['CategoryName']) ?></td>
                <td class="text-end"><?= money($b['Price']) ?></td>
                <td class="text-center"><span class="badge text-bg-<?= (int) $b['Stock'] === 0 ? 'danger' : ((int) $b['Stock'] <= 5 ? 'warning' : 'light') ?>"><?= (int) $b['Stock'] ?></span></td>
                <td>
                    <form method="post" class="d-inline"><?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle"><input type="hidden" name="book_id" value="<?= (int) $b['BookID'] ?>">
                        <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                        <button class="btn btn-sm btn-<?= $b['IsActive'] ? 'success' : 'secondary' ?>"><?= $b['IsActive'] ? 'Đang bán' : 'Đang ẩn' ?></button>
                    </form>
                </td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-outline-navy" href="<?= e(url('admin/book-form.php?id=' . (int) $b['BookID'])) ?>"><i class="bi bi-pencil"></i> Sửa</a>
                    <form method="post" class="d-inline"><?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete"><input type="hidden" name="book_id" value="<?= (int) $b['BookID'] ?>">
                        <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                        <button class="btn btn-sm btn-outline-danger" data-confirm="Xóa sách &quot;<?= e($b['Title']) ?>&quot;?"><i class="bi bi-trash"></i> Xóa</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$books): ?><tr><td colspan="8" class="text-center text-muted py-4">Không có sách phù hợp.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<div class="mt-3"><?php render_pagination($pg, array_filter(['q' => $q, 'cat' => $cat ?: null])); ?></div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
