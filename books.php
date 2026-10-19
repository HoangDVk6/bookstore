<?php
// DANH SÁCH SÁCH: tìm kiếm, lọc theo danh mục, sắp xếp, phân trang
require_once __DIR__ . '/includes/bootstrap.php';
$pdo = db();

$q    = trim((string) ($_GET['q'] ?? ''));
$cat  = (int) ($_GET['cat'] ?? 0);
$sort = (string) ($_GET['sort'] ?? 'new');
$page = (int) ($_GET['page'] ?? 1);

// Chỉ cho phép các cách sắp xếp nằm trong danh sách (không đưa dữ liệu người dùng vào ORDER BY)
$orderBy = [
    'new'        => 'b.CreatedAt DESC, b.BookID DESC',
    'price_asc'  => 'b.Price ASC, b.BookID',
    'price_desc' => 'b.Price DESC, b.BookID',
    'title'      => 'b.Title ASC',
][$sort] ?? 'b.CreatedAt DESC, b.BookID DESC';

$where = ['b.IsActive = 1'];
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
$pg = paginate((int) $st->fetchColumn(), $page, (int) config('app.per_page'));

// OFFSET ... FETCH là cú pháp phân trang của SQL Server (bắt buộc có ORDER BY)
$sql = "SELECT b.BookID, b.Title, b.Author, b.Price, b.Stock, b.ImagePath, c.CategoryName
        FROM Books b JOIN Categories c ON c.CategoryID = b.CategoryID
        WHERE $whereSql ORDER BY $orderBy
        OFFSET {$pg['offset']} ROWS FETCH NEXT {$pg['per']} ROWS ONLY";
$st = $pdo->prepare($sql);
$st->execute($params);
$books = $st->fetchAll();

$cats = $pdo->query('SELECT CategoryID, CategoryName FROM Categories ORDER BY CategoryName')->fetchAll();
$catName = '';
foreach ($cats as $c) {
    if ((int) $c['CategoryID'] === $cat) { $catName = $c['CategoryName']; }
}
$query = array_filter(['q' => $q, 'cat' => $cat ?: null, 'sort' => $sort !== 'new' ? $sort : null]);

$pageTitle = $catName ?: 'Danh sách sách';
require __DIR__ . '/includes/header.php';
?>
<nav aria-label="breadcrumb"><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Trang chủ</a></li>
    <li class="breadcrumb-item active">Danh sách sách<?= $catName ? ' - ' . e($catName) : '' ?></li>
</ol></nav>

<div class="row g-4">
    <aside class="col-lg-3">
        <div class="panel">
            <h5 class="mb-3">Danh mục</h5>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action <?= $cat === 0 ? 'active' : '' ?>" href="<?= e(url('books.php' . ($q !== '' ? '?q=' . urlencode($q) : ''))) ?>">Tất cả sách</a>
                <?php foreach ($cats as $c): ?>
                    <a class="list-group-item list-group-item-action <?= $cat === (int) $c['CategoryID'] ? 'active' : '' ?>"
                       href="<?= e(url('books.php?' . http_build_query(array_filter(['cat' => $c['CategoryID'], 'q' => $q])))) ?>"><?= e($c['CategoryName']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>
    <section class="col-lg-9">
        <form class="row g-2 mb-3" method="get">
            <div class="col-md-5"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Tìm theo tên sách hoặc tác giả"></div>
            <div class="col-md-3">
                <select class="form-select" name="cat">
                    <option value="0">Tất cả danh mục</option>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?= (int) $c['CategoryID'] ?>" <?= $cat === (int) $c['CategoryID'] ? 'selected' : '' ?>><?= e($c['CategoryName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="sort">
                    <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Mới nhất</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                    <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Tên A-Z</option>
                </select>
            </div>
            <div class="col-md-1 d-grid"><button class="btn btn-navy" aria-label="Lọc"><i class="bi bi-funnel"></i></button></div>
        </form>

        <p class="text-muted">Tìm thấy <strong><?= $pg['total'] ?></strong> cuốn sách<?= $q !== '' ? ' cho từ khóa "' . e($q) . '"' : '' ?>.</p>
        <?php if (!$books): ?>
            <div class="panel text-center py-5">
                <i class="bi bi-search fs-1 text-muted"></i>
                <p class="mt-3 mb-0">Không tìm thấy sách phù hợp. Hãy thử từ khóa khác hoặc <a href="<?= e(url('books.php')) ?>">xem tất cả sách</a>.</p>
            </div>
        <?php else: ?>
            <div class="row g-3"><?php foreach ($books as $b) { book_card($b); } ?></div>
            <div class="mt-4"><?php render_pagination($pg, $query); ?></div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
