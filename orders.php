<?php
// LỊCH SỬ ĐƠN HÀNG (danh sách) và CHI TIẾT ĐƠN HÀNG (?id=) của khách đang đăng nhập
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$user = current_user();
$pdo = db();

// Khách tự hủy đơn khi đơn còn "Chờ xác nhận"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    csrf_check();
    $oid = (int) ($_POST['order_id'] ?? 0);
    if (change_order_status($oid, 'Cancelled', (int) $user['id'])) {
        flash('success', 'Đã hủy đơn hàng #' . $oid . '.');
    } else {
        flash('error', 'Không thể hủy đơn hàng này.');
    }
    redirect('orders.php?id=' . $oid);
}

$id = (int) ($_GET['id'] ?? 0);
if ($id > 0) {
    $st = $pdo->prepare('SELECT * FROM Orders WHERE OrderID = ? AND UserID = ?');
    $st->execute([$id, $user['id']]);
    $order = $st->fetch();
    if (!$order) {
        flash('error', 'Không tìm thấy đơn hàng.');
        redirect('orders.php');
    }
    $st = $pdo->prepare('SELECT d.Quantity, d.UnitPrice, b.BookID, b.Title, b.Author, b.ImagePath
                         FROM OrderDetails d JOIN Books b ON b.BookID = d.BookID WHERE d.OrderID = ?');
    $st->execute([$id]);
    $details = $st->fetchAll();
} else {
    $st = $pdo->prepare('SELECT OrderID, CreatedAt, TotalAmount, Status FROM Orders WHERE UserID = ? ORDER BY CreatedAt DESC, OrderID DESC');
    $st->execute([$user['id']]);
    $orders = $st->fetchAll();
}

$pageTitle = $id > 0 ? 'Đơn hàng #' . $id : 'Đơn hàng của tôi';
require __DIR__ . '/includes/header.php';
?>
<?php if ($id > 0): ?>
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(url('orders.php')) ?>">Đơn hàng của tôi</a></li>
        <li class="breadcrumb-item active">Đơn #<?= $id ?></li>
    </ol></nav>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="panel p-0 table-responsive">
                <table class="table mb-0">
                    <thead class="table-light"><tr><th>Sách</th><th class="text-end">Đơn giá</th><th class="text-center">SL</th><th class="text-end">Thành tiền</th></tr></thead>
                    <tbody>
                    <?php $sub = 0; foreach ($details as $d): $line = (int) $d['UnitPrice'] * (int) $d['Quantity']; $sub += $line; ?>
                        <tr>
                            <td><div class="d-flex gap-3 align-items-center"><img class="cart-img" src="<?= e(book_image($d['ImagePath'])) ?>" alt="">
                                <div><a class="fw-semibold text-decoration-none" href="<?= e(url('book-detail.php?id=' . (int) $d['BookID'])) ?>"><?= e($d['Title']) ?></a><div class="small text-muted"><?= e($d['Author']) ?></div></div></div></td>
                            <td class="text-end"><?= money($d['UnitPrice']) ?></td>
                            <td class="text-center"><?= (int) $d['Quantity'] ?></td>
                            <td class="text-end"><?= money($line) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel">
                <h5>Đơn hàng #<?= $id ?> <?= status_badge($order['Status']) ?></h5>
                <p class="small text-muted mb-3">Đặt lúc <?= e(format_date($order['CreatedAt'])) ?></p>
                <p class="mb-1"><strong><?= e($order['ReceiverName']) ?></strong> - <?= e($order['Phone']) ?></p>
                <p class="mb-1"><?= e($order['ShippingAddress']) ?></p>
                <?php if ($order['Note']): ?><p class="mb-1 fst-italic">Ghi chú: <?= e($order['Note']) ?></p><?php endif; ?>
                <p class="mb-3">Thanh toán: Khi nhận hàng (COD)</p>
                <div class="d-flex justify-content-between"><span>Tạm tính</span><span><?= money($sub) ?></span></div>
                <div class="d-flex justify-content-between"><span>Phí vận chuyển</span><span><?= (int) $order['ShippingFee'] ? money($order['ShippingFee']) : 'Miễn phí' ?></span></div>
                <div class="d-flex justify-content-between fw-bold fs-5 mt-2"><span>Tổng cộng</span><span class="text-bronze"><?= money($order['TotalAmount']) ?></span></div>
                <?php if ($order['Status'] === 'Pending'): ?>
                    <form method="post" class="mt-3"><?= csrf_field() ?>
                        <input type="hidden" name="action" value="cancel"><input type="hidden" name="order_id" value="<?= $id ?>">
                        <button class="btn btn-outline-danger w-100" data-confirm="Bạn chắc chắn muốn hủy đơn hàng này?">Hủy đơn hàng</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <h1 class="h3 mb-4">Đơn hàng của tôi</h1>
    <?php if (!$orders): ?>
        <div class="panel text-center py-5"><i class="bi bi-receipt fs-1 text-muted"></i><p class="mt-3">Bạn chưa có đơn hàng nào.</p>
            <a class="btn btn-bronze" href="<?= e(url('books.php')) ?>">Mua sách ngay</a></div>
    <?php else: ?>
        <div class="panel p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Mã đơn</th><th>Ngày đặt</th><th class="text-end">Tổng tiền</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td>#<?= (int) $o['OrderID'] ?></td>
                        <td><?= e(format_date($o['CreatedAt'])) ?></td>
                        <td class="text-end"><?= money($o['TotalAmount']) ?></td>
                        <td><?= status_badge($o['Status']) ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-navy" href="<?= e(url('orders.php?id=' . (int) $o['OrderID'])) ?>">Xem chi tiết</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
