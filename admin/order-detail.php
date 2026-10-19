<?php
// CHI TIẾT ĐƠN HÀNG VÀ CẬP NHẬT TRẠNG THÁI (Admin)
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pdo = db();

$id = (int) ($_GET['id'] ?? $_POST['order_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $new = (string) ($_POST['status'] ?? '');
    // change_order_status chỉ chấp nhận chuyển trạng thái đúng quy trình và tự hoàn kho khi hủy
    if (isset(ORDER_STATUS[$new]) && change_order_status($id, $new)) {
        flash('success', 'Đã cập nhật trạng thái đơn hàng #' . $id . '.');
    } else {
        flash('error', 'Không thể chuyển sang trạng thái này.');
    }
    redirect('admin/order-detail.php?id=' . $id);
}

$st = $pdo->prepare('SELECT o.*, u.Email FROM Orders o JOIN Users u ON u.UserID = o.UserID WHERE o.OrderID = ?');
$st->execute([$id]);
$order = $st->fetch();
if (!$order) {
    flash('error', 'Không tìm thấy đơn hàng.');
    redirect('admin/orders.php');
}
$st = $pdo->prepare('SELECT d.Quantity, d.UnitPrice, b.Title, b.Author, b.ImagePath
                     FROM OrderDetails d JOIN Books b ON b.BookID = d.BookID WHERE d.OrderID = ?');
$st->execute([$id]);
$details = $st->fetchAll();
$next = ORDER_FLOW[$order['Status']] ?? [];

$pageTitle = 'Đơn hàng #' . $id;
$adminActive = 'orders';
require __DIR__ . '/../includes/admin_header.php';
?>
<a class="d-inline-block mb-3" href="<?= e(url('admin/orders.php')) ?>">&larr; Danh sách đơn hàng</a>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="panel p-0 table-responsive">
            <table class="table mb-0">
                <thead class="table-light"><tr><th>Sách</th><th class="text-end">Đơn giá</th><th class="text-center">SL</th><th class="text-end">Thành tiền</th></tr></thead>
                <tbody>
                <?php $sub = 0; foreach ($details as $d): $line = (int) $d['UnitPrice'] * (int) $d['Quantity']; $sub += $line; ?>
                    <tr>
                        <td><div class="d-flex gap-3 align-items-center"><img class="thumb" src="<?= e(book_image($d['ImagePath'])) ?>" alt="">
                            <div><strong><?= e($d['Title']) ?></strong><div class="small text-muted"><?= e($d['Author']) ?></div></div></div></td>
                        <td class="text-end"><?= money($d['UnitPrice']) ?></td>
                        <td class="text-center"><?= (int) $d['Quantity'] ?></td>
                        <td class="text-end"><?= money($line) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><td colspan="3" class="text-end">Tạm tính</td><td class="text-end"><?= money($sub) ?></td></tr>
                    <tr><td colspan="3" class="text-end">Phí vận chuyển</td><td class="text-end"><?= (int) $order['ShippingFee'] ? money($order['ShippingFee']) : 'Miễn phí' ?></td></tr>
                    <tr><td colspan="3" class="text-end fw-bold">Tổng cộng</td><td class="text-end fw-bold text-bronze"><?= money($order['TotalAmount']) ?></td></tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel mb-3">
            <h5>Đơn #<?= $id ?> <?= status_badge($order['Status']) ?></h5>
            <p class="small text-muted">Đặt lúc <?= e(format_date($order['CreatedAt'])) ?><?= $order['UpdatedAt'] ? ' · cập nhật ' . e(format_date($order['UpdatedAt'])) : '' ?></p>
            <p class="mb-1"><strong><?= e($order['ReceiverName']) ?></strong> - <?= e($order['Phone']) ?></p>
            <p class="mb-1"><?= e($order['Email']) ?></p>
            <p class="mb-1"><?= e($order['ShippingAddress']) ?></p>
            <?php if ($order['Note']): ?><p class="fst-italic mb-1">Ghi chú: <?= e($order['Note']) ?></p><?php endif; ?>
            <p class="mb-0">Thanh toán: Khi nhận hàng (COD)</p>
        </div>
        <div class="panel">
            <h6>Cập nhật trạng thái</h6>
            <?php if (!$next): ?>
                <p class="text-muted mb-0">Đơn hàng đã ở trạng thái cuối, không thể thay đổi.</p>
            <?php else: ?>
                <form method="post" class="d-flex gap-2"><?= csrf_field() ?><input type="hidden" name="order_id" value="<?= $id ?>">
                    <select class="form-select" name="status">
                        <?php foreach ($next as $s): ?><option value="<?= e($s) ?>"><?= e(ORDER_STATUS[$s][0]) ?></option><?php endforeach; ?>
                    </select>
                    <button class="btn btn-bronze" data-confirm="Xác nhận cập nhật trạng thái đơn hàng?">Cập nhật</button>
                </form>
                <div class="form-text">Hủy đơn sẽ hoàn lại số lượng sách vào kho.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
