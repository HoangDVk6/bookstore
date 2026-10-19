<?php
// QUẢN LÝ ĐƠN HÀNG: danh sách, lọc theo trạng thái, tìm theo mã/tên/số điện thoại
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pdo = db();

$status = (string) ($_GET['status'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));
$where = ['1 = 1'];
$params = [];
if (isset(ORDER_STATUS[$status])) {
    $where[] = 'o.Status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $where[] = '(o.ReceiverName LIKE ? OR o.Phone LIKE ? OR CAST(o.OrderID AS NVARCHAR(20)) = ?)';
    $params[] = like_pattern($q);
    $params[] = like_pattern($q);
    $params[] = ltrim($q, '#');
}
$whereSql = implode(' AND ', $where);

$st = $pdo->prepare("SELECT COUNT(*) FROM Orders o WHERE $whereSql");
$st->execute($params);
$pg = paginate((int) $st->fetchColumn(), (int) ($_GET['page'] ?? 1), 10);

$st = $pdo->prepare("SELECT o.OrderID, o.ReceiverName, o.Phone, o.TotalAmount, o.Status, o.CreatedAt
                     FROM Orders o WHERE $whereSql ORDER BY o.CreatedAt DESC, o.OrderID DESC
                     OFFSET {$pg['offset']} ROWS FETCH NEXT {$pg['per']} ROWS ONLY");
$st->execute($params);
$orders = $st->fetchAll();

$pageTitle = 'Quản lý đơn hàng';
$adminActive = 'orders';
require __DIR__ . '/../includes/admin_header.php';
?>
<form class="d-flex flex-wrap gap-2 mb-3" method="get">
    <input class="form-control" style="max-width:280px" name="q" value="<?= e($q) ?>" placeholder="Mã đơn, tên hoặc số điện thoại">
    <select class="form-select" style="max-width:200px" name="status">
        <option value="">Tất cả trạng thái</option>
        <?php foreach (ORDER_STATUS as $code => [$label]): ?><option value="<?= e($code) ?>" <?= $status === $code ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-navy"><i class="bi bi-funnel"></i> Lọc</button>
</form>
<div class="panel p-0 table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Mã đơn</th><th>Người nhận</th><th>Điện thoại</th><th>Ngày đặt</th><th class="text-end">Tổng tiền</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td>#<?= (int) $o['OrderID'] ?></td>
                <td><?= e($o['ReceiverName']) ?></td>
                <td><?= e($o['Phone']) ?></td>
                <td><?= e(format_date($o['CreatedAt'])) ?></td>
                <td class="text-end"><?= money($o['TotalAmount']) ?></td>
                <td><?= status_badge($o['Status']) ?></td>
                <td class="text-end"><a class="btn btn-sm btn-outline-navy" href="<?= e(url('admin/order-detail.php?id=' . (int) $o['OrderID'])) ?>">Chi tiết</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?><tr><td colspan="7" class="text-center text-muted py-4">Không có đơn hàng phù hợp.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<div class="mt-3"><?php render_pagination($pg, array_filter(['q' => $q, 'status' => $status])); ?></div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
