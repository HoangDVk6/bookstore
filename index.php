<?php
// TRANG CHỦ: banner, danh mục nổi bật, sách bán chạy, sách mới cập nhật
require_once __DIR__ . '/includes/bootstrap.php';
$pdo = db();

// Danh mục kèm số sách đang bán
$cats = $pdo->query("SELECT c.CategoryID, c.CategoryName,
                            (SELECT COUNT(*) FROM Books b WHERE b.CategoryID = c.CategoryID AND b.IsActive = 1) AS BookCount
                     FROM Categories c ORDER BY c.CategoryName")->fetchAll();
$icons = ['bi-feather', 'bi-graph-up-arrow', 'bi-cpu', 'bi-bank', 'bi-balloon-heart', 'bi-translate'];

// Sách bán chạy: tổng số lượng trong các đơn chưa bị hủy
$best = $pdo->query("SELECT TOP (4) b.BookID, b.Title, b.Author, b.Price, b.Stock, b.ImagePath, c.CategoryName,
        (SELECT ISNULL(SUM(od.Quantity), 0) FROM OrderDetails od JOIN Orders o ON o.OrderID = od.OrderID
          WHERE od.BookID = b.BookID AND o.Status <> 'Cancelled') AS Sold
    FROM Books b JOIN Categories c ON c.CategoryID = b.CategoryID
    WHERE b.IsActive = 1
    ORDER BY Sold DESC, b.BookID")->fetchAll();

// Sách mới cập nhật
$new = $pdo->query("SELECT TOP (8) b.BookID, b.Title, b.Author, b.Price, b.Stock, b.ImagePath, c.CategoryName
    FROM Books b JOIN Categories c ON c.CategoryID = b.CategoryID
    WHERE b.IsActive = 1
    ORDER BY b.CreatedAt DESC, b.BookID DESC")->fetchAll();

$pageTitle = 'Trang chủ';
require __DIR__ . '/includes/header.php';
?>
<section class="hero mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <span class="badge text-bg-warning mb-3">Nhà sách trực tuyến</span>
            <h1>Tri thức trong tầm tay,<br>sách hay đến tận nhà</h1>
            <p class="my-3">Hàng trăm đầu sách văn học, kinh tế, khoa học, lịch sử và thiếu nhi. Tìm sách nhanh, đặt hàng dễ dàng, thanh toán khi nhận hàng.</p>
            <a href="<?= e(url('books.php')) ?>" class="btn btn-bronze btn-lg">Khám phá ngay</a>
        </div>
        <div class="col-lg-5 d-none d-lg-block">
            <div class="hero-books">
                <?php foreach (array_slice($best, 0, 3) as $b): ?>
                    <img src="<?= e(book_image($b['ImagePath'])) ?>" alt="Bìa sách <?= e($b['Title']) ?>">
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<div class="section-head"><h2 class="section-title">Danh mục nổi bật</h2></div>
<div class="row g-3">
    <?php foreach ($cats as $i => $c): ?>
        <div class="col-6 col-md-4 col-lg-2">
            <a class="cat-tile" href="<?= e(url('books.php?cat=' . (int) $c['CategoryID'])) ?>">
                <i class="bi <?= e($icons[$i % count($icons)]) ?>"></i>
                <div class="name"><?= e($c['CategoryName']) ?></div>
                <div class="count"><?= (int) $c['BookCount'] ?> đầu sách</div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="section-head">
    <h2 class="section-title">Sách bán chạy</h2>
    <a href="<?= e(url('books.php')) ?>">Xem tất cả &rarr;</a>
</div>
<div class="row g-3"><?php foreach ($best as $b) { book_card($b); } ?></div>

<div class="section-head">
    <h2 class="section-title">Sách mới cập nhật</h2>
    <a href="<?= e(url('books.php?sort=new')) ?>">Xem tất cả &rarr;</a>
</div>
<div class="row g-3"><?php foreach ($new as $b) { book_card($b); } ?></div>

<section class="panel mt-5">
    <div class="row g-4 text-center">
        <div class="col-md-4"><i class="bi bi-book fs-2 text-warning"></i><h5 class="mt-2">Sách chọn lọc</h5><p class="mb-0 text-muted">Thông tin rõ ràng: tác giả, nhà xuất bản, năm xuất bản.</p></div>
        <div class="col-md-4"><i class="bi bi-truck fs-2 text-warning"></i><h5 class="mt-2">Giao hàng tận nơi</h5><p class="mb-0 text-muted">Miễn phí vận chuyển cho đơn từ <?= money(config('app.free_ship_from')) ?>.</p></div>
        <div class="col-md-4"><i class="bi bi-shield-check fs-2 text-warning"></i><h5 class="mt-2">Thanh toán an tâm</h5><p class="mb-0 text-muted">Thanh toán khi nhận hàng (COD).</p></div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
