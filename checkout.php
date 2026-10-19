<?php
// ĐẶT HÀNG (thanh toán khi nhận hàng - COD)
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$user = current_user();
$pdo = db();

$cart = cart_details();
if (!$cart['items']) {
    flash('warning', 'Giỏ hàng đang trống, hãy chọn sách trước khi đặt hàng.');
    redirect('books.php');
}
if ($cart['adjusted']) {
    flash('warning', 'Giỏ hàng đã được điều chỉnh theo tồn kho. Vui lòng kiểm tra lại trước khi đặt.');
    redirect('cart.php');
}

// Điền sẵn thông tin từ tài khoản
$st = $pdo->prepare('SELECT FullName, Phone, Address FROM Users WHERE UserID = ?');
$st->execute([$user['id']]);
$me = $st->fetch();
$form = ['receiver' => $me['FullName'], 'phone' => (string) $me['Phone'], 'address' => (string) $me['Address'], 'note' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($form as $k => $_) {
        $form[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    if ($form['receiver'] === '' || mb_strlen($form['receiver']) > 100) {
        $errors[] = 'Vui lòng nhập tên người nhận (tối đa 100 ký tự).';
    }
    if (!preg_match('/^(0|\+84)\d{9,10}$/', $form['phone'])) {
        $errors[] = 'Số điện thoại không hợp lệ.';
    }
    if ($form['address'] === '' || mb_strlen($form['address']) > 255) {
        $errors[] = 'Vui lòng nhập địa chỉ giao hàng (tối đa 255 ký tự).';
    }
    if (mb_strlen($form['note']) > 500) {
        $errors[] = 'Ghi chú tối đa 500 ký tự.';
    }

    if (!$errors) {
        // Toàn bộ việc tạo đơn và trừ kho nằm trong MỘT giao dịch: hoặc thành công hết, hoặc hoàn tác hết
        $pdo->beginTransaction();
        try {
            $subtotal = 0;
            $lines = [];
            $cartItems = cart_raw();
            ksort($cartItems);   // khóa theo thứ tự BookID để tránh deadlock giữa hai đơn đặt cùng lúc
            foreach ($cartItems as $bookId => $qty) {
                // Khóa dòng sách rồi đọc giá và tồn kho mới nhất từ CSDL (không tin dữ liệu từ trình duyệt)
                $st = $pdo->prepare('SELECT Title, Price, Stock FROM Books WITH (UPDLOCK, ROWLOCK) WHERE BookID = ? AND IsActive = 1');
                $st->execute([(int) $bookId]);
                $b = $st->fetch();
                if (!$b || (int) $b['Stock'] < (int) $qty) {
                    throw new RuntimeException('Sách "' . ($b['Title'] ?? '#' . $bookId) . '" không đủ số lượng trong kho.');
                }
                $pdo->prepare('UPDATE Books SET Stock = Stock - ? WHERE BookID = ?')->execute([(int) $qty, (int) $bookId]);
                $lines[] = [(int) $bookId, (int) $qty, (int) $b['Price']];
                $subtotal += (int) $b['Price'] * (int) $qty;
            }
            $shipping = $subtotal >= (int) config('app.free_ship_from') ? 0 : (int) config('app.shipping_fee');

            $st = $pdo->prepare("INSERT INTO Orders (UserID, ReceiverName, Phone, ShippingAddress, Note, PaymentMethod, ShippingFee, TotalAmount, Status)
                                 OUTPUT INSERTED.OrderID
                                 VALUES (?, ?, ?, ?, ?, 'COD', ?, ?, 'Pending')");
            $st->execute([$user['id'], $form['receiver'], $form['phone'], $form['address'], $form['note'] ?: null, $shipping, $subtotal + $shipping]);
            $orderId = (int) $st->fetchColumn();
            $st->closeCursor();

            $ins = $pdo->prepare('INSERT INTO OrderDetails (OrderID, BookID, Quantity, UnitPrice) VALUES (?, ?, ?, ?)');
            foreach ($lines as [$bookId, $qty, $price]) {
                $ins->execute([$orderId, $bookId, $qty, $price]);
            }
            $pdo->commit();

            $_SESSION['cart'] = [];
            flash('success', 'Đặt hàng thành công! Mã đơn hàng của bạn là #' . $orderId . '.');
            redirect('orders.php?id=' . $orderId);
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            flash('error', $ex->getMessage());
            redirect('cart.php');
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log('[bookstore] checkout: ' . $ex->getMessage());
            $errors[] = 'Không thể tạo đơn hàng lúc này, vui lòng thử lại sau.' . (config('app.debug') ? ' (' . $ex->getMessage() . ')' : '');
        }
    }
}

$pageTitle = 'Đặt hàng';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-4">Thông tin đặt hàng</h1>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="panel">
            <?php if ($errors): ?>
                <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <form method="post" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-md-7 mb-3">
                        <label class="form-label" for="receiver">Người nhận *</label>
                        <input class="form-control" id="receiver" name="receiver" maxlength="100" value="<?= e($form['receiver']) ?>" required>
                        <div class="invalid-feedback">Vui lòng nhập tên người nhận.</div>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label" for="phone">Số điện thoại *</label>
                        <input class="form-control" id="phone" name="phone" pattern="(0|\+84)[0-9]{9,10}" value="<?= e($form['phone']) ?>" required>
                        <div class="invalid-feedback">Ví dụ: 0901234567.</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="address">Địa chỉ giao hàng *</label>
                    <input class="form-control" id="address" name="address" maxlength="255" value="<?= e($form['address']) ?>" required>
                    <div class="invalid-feedback">Vui lòng nhập địa chỉ.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="note">Ghi chú</label>
                    <textarea class="form-control" id="note" name="note" rows="2" maxlength="500"><?= e($form['note']) ?></textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label">Phương thức thanh toán</label>
                    <div class="form-check"><input class="form-check-input" type="radio" checked disabled id="cod"><label class="form-check-label" for="cod">Thanh toán khi nhận hàng (COD)</label></div>
                </div>
                <button class="btn btn-bronze btn-lg w-100">Xác nhận đặt hàng</button>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <h5 class="mb-3">Đơn hàng của bạn</h5>
            <?php foreach ($cart['items'] as $it): ?>
                <div class="d-flex justify-content-between mb-2"><span><?= e($it['Title']) ?> <span class="text-muted">× <?= (int) $it['qty'] ?></span></span><span><?= money($it['line']) ?></span></div>
            <?php endforeach; ?>
            <hr>
            <div class="d-flex justify-content-between mb-1"><span>Tạm tính</span><span><?= money($cart['subtotal']) ?></span></div>
            <div class="d-flex justify-content-between mb-1"><span>Phí vận chuyển</span><span><?= $cart['shipping'] ? money($cart['shipping']) : 'Miễn phí' ?></span></div>
            <div class="d-flex justify-content-between fs-5 fw-bold mt-2"><span>Tổng cộng</span><span class="text-bronze"><?= money($cart['total']) ?></span></div>
            <p class="small text-muted mt-3 mb-0">Tổng tiền được máy chủ tính lại từ giá trong cơ sở dữ liệu khi xác nhận đặt hàng.</p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
