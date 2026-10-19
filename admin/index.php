<?php
// TRANG QUẢN TRỊ - TỔNG QUAN (Dashboard)
require_once __DIR__ . '/../includes/bootstrap.php';
$pdo = db();
require_admin();   // chặn trước khi truy vấn

$pageTitle = 'Tổng quan';
$adminActive = 'dashboard';

$stats = $pdo->query("SELECT
    (SELECT COUNT(*) FROM Books)                                  AS Books,
    (SELECT COUNT(*) FROM Categories)                             AS Categories,
    (SELECT COUNT(*) FROM Users WHERE Role = 'customer')          AS Customers,
    (SELECT COUNT(*) FROM Orders)                                 AS Orders,
    (SELECT COUNT(*) FROM Orders WHERE Status = 'Pending')        AS Pending,
    (SELECT ISNULL(SUM(TotalAmount), 0) FROM Orders WHERE Status = 'Completed') AS Revenue")->fetch();

$byStatus = $pdo->query('SELECT Status, COUNT(*) AS Cnt FROM Orders GROUP BY Status')->fetchAll(PDO::FETCH_KEY_PAIR);

$recent = $pdo->query('SELECT TOP (6) o.OrderID, o.ReceiverName, o.TotalAmount, o.Status, o.CreatedAt
                       FROM Orders o ORDER BY o.CreatedAt DESC, o.OrderID DESC')->fetchAll();

$top = $pdo->query("SELECT TOP (5) b.BookID, b.Title, SUM(d.Quantity) AS Sold
                    FROM OrderDetails d JOIN Orders o ON o.OrderID = d.OrderID AND o.Status <> 'Cancelled'
                    JOIN Books b ON b.BookID = d.BookID
                    GROUP BY b.BookID, b.Title ORDER BY Sold DESC")->fetchAll();

$low = $pdo->query('SELECT TOP (6) BookID, Title, Stock FROM Books WHERE IsActive = 1 AND Stock <= 5 ORDER BY Stock, Title')->fetchAll();

require __DIR__ . '/../includes/admin_header.php';
?>
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['bi-book', $stats['Books'], 'Đầu sách'],
        ['bi-tags', $stats['Categories'], 'Danh mục'],
        ['bi-receipt', $stats['Orders'], 'Đơn hàng (' . (int) $stats['Pending'] . ' chờ xác nhận)'],
        ['bi-cash-stack', money($stats['Revenue']), 'Doanh thu (đơn hoàn thành)'],
    ];
    foreach ($cards as [$icon, $value, $label]): ?>
        <div class="col-6 col-xl-3"><div class="stat-card"><div class="icon"><i class="bi <?= $icon ?>"></i></div>
            <div><div class="value"><?= e($value) ?></div><div class="label"><?= e($label) ?></div></div></div></div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="panel p-0 table-responsive">
            <div class="p-3 pb-2 d-flex justify-content-between"><h5 class="mb-0">Đơn hàng mới nhất</h5><a href="<?= e(url('admin/orders.php')) ?>">Xem tất cả</a></div>
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Mã</th><th>Người nhận</th><th class="text-end">Tổng tiền</th><th>Trạng thái</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $o): ?>
                    <tr>
                        <td><a href="<?= e(url('admin/order-detail.php?id=' . (int) $o['OrderID'])) ?>">#<?= (int) $o['OrderID'] ?></a></td>
                        <td><?= e($o['ReceiverName']) ?></td>
                        <td class="text-end"><?= money($o['TotalAmount']) ?></td>
                        <td><?= status_badge($o['Status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recent): ?><tr><td colspan="4" class="text-center text-muted py-4">Chưa có đơn hàng.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel mb-4">
            <h5>Đơn hàng theo trạng thái</h5>
            <?php foreach (ORDER_STATUS as $code => [$label, $cls]): ?>
                <div class="d-flex justify-content-between border-bottom py-1"><span><?= status_badge($code) ?></span><strong><?= (int) ($byStatus[$code] ?? 0) ?></strong></div>
            <?php endforeach; ?>
        </div>
        <div class="panel mb-4">
            <h5>Sách bán chạy</h5>
            <?php foreach ($top as $t): ?>
                <div class="d-flex justify-content-between border-bottom py-1"><span><?= e($t['Title']) ?></span><strong><?= (int) $t['Sold'] ?> cuốn</strong></div>
            <?php endforeach; ?>
            <?php if (!$top): ?><p class="text-muted mb-0">Chưa có dữ liệu.</p><?php endif; ?>
        </div>
        <div class="panel">
            <h5>Sắp hết hàng (tồn ≤ 5)</h5>
            <?php foreach ($low as $l): ?>
                <div class="d-flex justify-content-between border-bottom py-1"><a href="<?= e(url('admin/book-form.php?id=' . (int) $l['BookID'])) ?>"><?= e($l['Title']) ?></a>
                    <span class="badge text-bg-<?= (int) $l['Stock'] === 0 ? 'danger' : 'warning' ?>"><?= (int) $l['Stock'] ?></span></div>
            <?php endforeach; ?>
            <?php if (!$low): ?><p class="text-muted mb-0">Không có sách sắp hết.</p><?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
