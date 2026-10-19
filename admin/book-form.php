<?php
// THÊM / SỬA SÁCH (có tải ảnh bìa). Có ?id= là sửa, không có là thêm mới.
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pdo = db();

$id = (int) ($_GET['id'] ?? 0);
$book = null;
if ($id > 0) {
    $st = $pdo->prepare('SELECT * FROM Books WHERE BookID = ?');
    $st->execute([$id]);
    $book = $st->fetch();
    if (!$book) {
        flash('error', 'Không tìm thấy sách cần sửa.');
        redirect('admin/books.php');
    }
}

$cats = $pdo->query('SELECT CategoryID, CategoryName FROM Categories ORDER BY CategoryName')->fetchAll();
$form = [
    'category_id' => $book['CategoryID'] ?? '', 'title' => $book['Title'] ?? '', 'author' => $book['Author'] ?? '',
    'publisher' => $book['Publisher'] ?? '', 'publish_year' => $book['PublishYear'] ?? '',
    'price' => isset($book['Price']) ? (int) $book['Price'] : '', 'stock' => $book['Stock'] ?? 0,
    'description' => $book['Description'] ?? '', 'is_active' => $book ? (int) $book['IsActive'] : 1,
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach (['category_id', 'title', 'author', 'publisher', 'publish_year', 'price', 'stock', 'description'] as $k) {
        $form[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $form['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    $catOk = false;
    foreach ($cats as $c) {
        if ((string) $c['CategoryID'] === $form['category_id']) { $catOk = true; }
    }
    if (!$catOk) { $errors[] = 'Vui lòng chọn danh mục.'; }
    if ($form['title'] === '' || mb_strlen($form['title']) > 200) { $errors[] = 'Tên sách bắt buộc, tối đa 200 ký tự.'; }
    if ($form['author'] === '' || mb_strlen($form['author']) > 150) { $errors[] = 'Tác giả bắt buộc, tối đa 150 ký tự.'; }
    if (mb_strlen($form['publisher']) > 150) { $errors[] = 'Nhà xuất bản tối đa 150 ký tự.'; }
    if ($form['publish_year'] !== '' && (!ctype_digit($form['publish_year']) || (int) $form['publish_year'] < 1000 || (int) $form['publish_year'] > (int) date('Y') + 1)) {
        $errors[] = 'Năm xuất bản không hợp lệ.';
    }
    if (!ctype_digit($form['price']) || (int) $form['price'] <= 0 || (int) $form['price'] > 999999999) { $errors[] = 'Giá bán phải là số nguyên dương (đồng).'; }
    if (!ctype_digit($form['stock']) || (int) $form['stock'] > 1000000) { $errors[] = 'Số lượng tồn phải là số nguyên không âm.'; }

    $newImage = null;
    if (!$errors) {
        try {
            $newImage = upload_cover($_FILES['cover'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
        } catch (Exception $ex) {
            $errors[] = $ex->getMessage();
        }
    }

    if (!$errors) {
        $vals = [(int) $form['category_id'], $form['title'], $form['author'], $form['publisher'] ?: null,
                 $form['publish_year'] !== '' ? (int) $form['publish_year'] : null, (int) $form['price'], (int) $form['stock'],
                 $form['description'] ?: null, $form['is_active']];
        if ($book) {
            $image = $newImage ?: $book['ImagePath'];
            $pdo->prepare('UPDATE Books SET CategoryID = ?, Title = ?, Author = ?, Publisher = ?, PublishYear = ?, Price = ?, Stock = ?,
                           Description = ?, IsActive = ?, ImagePath = ? WHERE BookID = ?')->execute(array_merge($vals, [$image, $id]));
            if ($newImage) { delete_uploaded_cover($book['ImagePath']); }   // xóa ảnh cũ do người dùng tải lên
            flash('success', 'Đã cập nhật sách.');
        } else {
            $pdo->prepare('INSERT INTO Books (CategoryID, Title, Author, Publisher, PublishYear, Price, Stock, Description, IsActive, ImagePath)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute(array_merge($vals, [$newImage]));
            flash('success', 'Đã thêm sách mới.');
        }
        redirect('admin/books.php');
    }
}

$pageTitle = $book ? 'Sửa sách' : 'Thêm sách mới';
$adminActive = 'books';
require __DIR__ . '/../includes/admin_header.php';
?>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="panel">
                <div class="mb-3"><label class="form-label" for="title">Tên sách *</label>
                    <input class="form-control" id="title" name="title" maxlength="200" value="<?= e($form['title']) ?>" required></div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label" for="author">Tác giả *</label>
                        <input class="form-control" id="author" name="author" maxlength="150" value="<?= e($form['author']) ?>" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label" for="category_id">Danh mục *</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($cats as $c): ?><option value="<?= (int) $c['CategoryID'] ?>" <?= (string) $c['CategoryID'] === (string) $form['category_id'] ? 'selected' : '' ?>><?= e($c['CategoryName']) ?></option><?php endforeach; ?>
                        </select></div>
                </div>
                <div class="row">
                    <div class="col-md-8 mb-3"><label class="form-label" for="publisher">Nhà xuất bản</label>
                        <input class="form-control" id="publisher" name="publisher" maxlength="150" value="<?= e($form['publisher']) ?>"></div>
                    <div class="col-md-4 mb-3"><label class="form-label" for="publish_year">Năm xuất bản</label>
                        <input class="form-control" id="publish_year" name="publish_year" inputmode="numeric" pattern="[0-9]{4}" value="<?= e($form['publish_year']) ?>"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label" for="price">Giá bán (đồng) *</label>
                        <input class="form-control" id="price" name="price" type="number" min="1000" step="1000" value="<?= e($form['price']) ?>" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label" for="stock">Số lượng tồn *</label>
                        <input class="form-control" id="stock" name="stock" type="number" min="0" value="<?= e($form['stock']) ?>" required></div>
                </div>
                <div class="mb-0"><label class="form-label" for="description">Mô tả nội dung</label>
                    <textarea class="form-control" id="description" name="description" rows="5"><?= e($form['description']) ?></textarea></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel mb-3">
                <label class="form-label" for="cover">Ảnh bìa (JPG, PNG, WEBP ≤ 2MB)</label>
                <?php if ($book): ?><img class="img-fluid rounded mb-2" style="max-height:220px" src="<?= e(book_image($book['ImagePath'])) ?>" alt="Bìa hiện tại"><?php endif; ?>
                <input class="form-control" type="file" id="cover" name="cover" accept="image/jpeg,image/png,image/webp">
                <?php if ($book): ?><div class="form-text">Để trống nếu giữ ảnh bìa hiện tại.</div><?php endif; ?>
                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" <?= $form['is_active'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Hiển thị trên cửa hàng</label>
                </div>
            </div>
            <div class="d-grid gap-2">
                <button class="btn btn-bronze"><i class="bi bi-check-lg"></i> <?= $book ? 'Lưu thay đổi' : 'Thêm sách' ?></button>
                <a class="btn btn-outline-secondary" href="<?= e(url('admin/books.php')) ?>">Hủy</a>
            </div>
        </div>
    </div>
</form>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
