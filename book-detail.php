<?php
// CHI TIẾT SÁCH
require_once __DIR__ . '/includes/bootstrap.php';
$pdo = db();

$id = (int) ($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT b.*, c.CategoryName FROM Books b JOIN Categories c ON c.CategoryID = b.CategoryID
                     WHERE b.BookID = ? AND b.IsActive = 1');
$st->execute([$id]);
$book = $st->fetch();
if (!$book) {
    http_response_code(404);
    flash('error', 'Không tìm thấy cuốn sách bạn yêu cầu.');
    redirect('books.php');
}

// Sách cùng danh mục
$st = $pdo->prepare('SELECT TOP (4) b.BookID, b.Title, b.Author, b.Price, b.Stock, b.ImagePath, c.CategoryName
                     FROM Books b JOIN Categories c ON c.CategoryID = b.CategoryID
                     WHERE b.CategoryID = ? AND b.BookID <> ? AND b.IsActive = 1 ORDER BY b.BookID DESC');
$st->execute([$book['CategoryID'], $id]);
$related = $st->fetchAll();

$stock = (int) $book['Stock'];
$pageTitle = $book['Title'];
require __DIR__ . '/includes/header.php';
?>
<nav aria-label="breadcrumb"><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Trang chủ</a></li>
    <li class="breadcrumb-item"><a href="<?= e(url('books.php?cat=' . (int) $book['CategoryID'])) ?>"><?= e($book['CategoryName']) ?></a></li>
    <li class="breadcrumb-item active"><?= e($book['Title']) ?></li>
</ol></nav>

<div class="panel">
    <div class="row g-4">
        <div class="col-md-4 text-center">
            <img class="detail-cover" src="<?= e(book_image($book['ImagePath'])) ?>" alt="Bìa sách <?= e($book['Title']) ?>">
        </div>
        <div class="col-md-8">
            <div class="book-cat"><?= e($book['CategoryName']) ?></div>
            <h1 class="h2 mb-2"><?= e($book['Title']) ?></h1>
            <p class="mb-1">Tác giả: <strong><?= e($book['Author']) ?></strong></p>
            <?php if ($book['Publisher']): ?><p class="mb-1">Nhà xuất bản: <?= e($book['Publisher']) ?><?= $book['PublishYear'] ? ' (' . (int) $book['PublishYear'] . ')' : '' ?></p><?php endif; ?>
            <p class="mb-3">Tình trạng:
                <?php if ($stock <= 0): ?><span class="badge text-bg-danger">Hết hàng</span>
                <?php elseif ($stock <= 5): ?><span class="badge text-bg-warning">Chỉ còn <?= $stock ?> cuốn</span>
                <?php else: ?><span class="badge text-bg-success">Còn hàng (<?= $stock ?>)</span><?php endif; ?>
            </p>
            <div class="detail-price mb-3"><?= money($book['Price']) ?></div>

            <form method="post" action="<?= e(url('cart.php')) ?>" class="d-flex flex-wrap gap-3 align-items-center mb-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="book_id" value="<?= (int) $book['BookID'] ?>">
                <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
                <div class="qty-box">
                    <button type="button" data-step="-1" aria-label="Giảm">−</button>
                    <input type="number" name="qty" value="1" min="1" max="<?= max(1, $stock) ?>" <?= $stock <= 0 ? 'disabled' : '' ?> aria-label="Số lượng">
                    <button type="button" data-step="1" aria-label="Tăng">+</button>
                </div>
                <button class="btn btn-bronze btn-lg" <?= $stock <= 0 ? 'disabled' : '' ?>><i class="bi bi-cart-plus"></i> Thêm vào giỏ hàng</button>
            </form>

            <h5>Giới thiệu nội dung</h5>
            <p style="white-space: pre-line"><?= e($book['Description'] ?: 'Chưa có mô tả cho cuốn sách này.') ?></p>
        </div>
    </div>
</div>

<?php if ($related): ?>
    <div class="section-head"><h2 class="section-title">Sách cùng danh mục</h2></div>
    <div class="row g-3"><?php foreach ($related as $b) { book_card($b); } ?></div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
