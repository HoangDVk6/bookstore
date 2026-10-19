<?php
// GIỎ HÀNG: thêm, cập nhật, xóa (POST) và hiển thị (GET)
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $back = safe_back($_POST['back'] ?? null, 'cart.php');
    $cart = cart_raw();

    if ($action === 'add') {
        $bookId = (int) ($_POST['book_id'] ?? 0);
        $qty = max(1, (int) ($_POST['qty'] ?? 1));
        $st = db()->prepare('SELECT Title, Stock FROM Books WHERE BookID = ? AND IsActive = 1');
        $st->execute([$bookId]);
        $book = $st->fetch();
        if (!$book) {
            flash('error', 'Sách không tồn tại hoặc đã ngừng bán.');
        } elseif ((int) $book['Stock'] <= 0) {
            flash('error', 'Sách "' . $book['Title'] . '" đã hết hàng.');
        } else {
            $newQty = ($cart[$bookId] ?? 0) + $qty;
            if ($newQty > (int) $book['Stock']) {
                $newQty = (int) $book['Stock'];
                flash('warning', 'Số lượng đã được điều chỉnh theo tồn kho (' . $newQty . ' cuốn).');
            } else {
                flash('success', 'Đã thêm "' . $book['Title'] . '" vào giỏ hàng.');
            }
            $cart[$bookId] = $newQty;
        }
        $_SESSION['cart'] = $cart;
        header('Location: ' . $back);
        exit;
    }

    if ($action === 'update') {
        foreach ((array) ($_POST['qty'] ?? []) as $bookId => $qty) {
            $bookId = (int) $bookId;
            $qty = (int) $qty;
            if (isset($cart[$bookId])) {
                if ($qty <= 0) { unset($cart[$bookId]); } else { $cart[$bookId] = $qty; }
            }
        }
        $_SESSION['cart'] = $cart;    // cart_details() sẽ tự giảm số lượng nếu vượt tồn kho
        flash('success', 'Đã cập nhật giỏ hàng.');
    } elseif ($action === 'remove') {
        unset($cart[(int) ($_POST['book_id'] ?? 0)]);
        $_SESSION['cart'] = $cart;
        flash('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
        flash('info', 'Đã xóa toàn bộ giỏ hàng.');
    }
    redirect('cart.php');
}

$cart = cart_details();
if ($cart['adjusted']) {
    flash('warning', 'Một số sản phẩm đã được điều chỉnh hoặc loại khỏi giỏ do không đủ tồn kho.');
}
$pageTitle = 'Giỏ hàng';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-4">Giỏ hàng của bạn</h1>
<?php if (!$cart['items']): ?>
    <div class="panel text-center py-5">
        <i class="bi bi-cart-x fs-1 text-muted"></i>
        <p class="mt-3">Giỏ hàng đang trống.</p>
        <a class="btn btn-bronze" href="<?= e(url('books.php')) ?>">Tiếp tục mua sách</a>
    </div>
<?php else: ?>
<div class="row g-4">
    <div class="col-lg-8">
        <form method="post" id="cartForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <div class="panel p-0 table-responsive">
                <table class="table mb-0">
                    <thead class="table-light"><tr><th>Sách</th><th class="text-end">Đơn giá</th><th style="width:150px">Số lượng</th><th class="text-end">Thành tiền</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($cart['items'] as $it): ?>
                        <tr>
                            <td>
                                <div class="d-flex gap-3 align-items-center">
                                    <img class="cart-img" src="<?= e(book_image($it['ImagePath'])) ?>" alt="">
                                    <div><a class="fw-semibold text-decoration-none" href="<?= e(url('book-detail.php?id=' . (int) $it['BookID'])) ?>"><?= e($it['Title']) ?></a>
                                        <div class="small text-muted"><?= e($it['Author']) ?></div></div>
                                </div>
                            </td>
                            <td class="text-end"><?= money($it['Price']) ?></td>
                            <td>
                                <div class="qty-box">
                                    <button type="button" data-step="-1" aria-label="Giảm">−</button>
                                    <input type="number" name="qty[<?= (int) $it['BookID'] ?>]" value="<?= (int) $it['qty'] ?>" min="1" max="<?= (int) $it['Stock'] ?>" aria-label="Số lượng">
                                    <button type="button" data-step="1" aria-label="Tăng">+</button>
                                </div>
                            </td>
                            <td class="text-end fw-semibold"><?= money($it['line']) ?></td>
                            <td class="text-end">
                                <button type="submit" form="rm<?= (int) $it['BookID'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Xóa sách này khỏi giỏ hàng?" aria-label="Xóa"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between mt-3">
                <a class="btn btn-outline-navy" href="<?= e(url('books.php')) ?>">&larr; Tiếp tục mua sách</a>
                <button class="btn btn-navy"><i class="bi bi-arrow-repeat"></i> Cập nhật giỏ hàng</button>
            </div>
        </form>
        <?php foreach ($cart['items'] as $it): ?>
            <form method="post" id="rm<?= (int) $it['BookID'] ?>"><?= csrf_field() ?>
                <input type="hidden" name="action" value="remove"><input type="hidden" name="book_id" value="<?= (int) $it['BookID'] ?>"></form>
        <?php endforeach; ?>
    </div>
    <div class="col-lg-4">
        <div class="panel">
            <h5 class="mb-3">Tóm tắt đơn hàng</h5>
            <div class="d-flex justify-content-between mb-2"><span>Tạm tính (<?= $cart['count'] ?> cuốn)</span><span><?= money($cart['subtotal']) ?></span></div>
            <div class="d-flex justify-content-between mb-2"><span>Phí vận chuyển</span><span><?= $cart['shipping'] ? money($cart['shipping']) : 'Miễn phí' ?></span></div>
            <hr>
            <div class="d-flex justify-content-between fs-5 fw-bold mb-3"><span>Tổng cộng</span><span class="text-bronze"><?= money($cart['total']) ?></span></div>
            <a class="btn btn-bronze w-100 mb-2" href="<?= e(url('checkout.php')) ?>">Tiến hành đặt hàng</a>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="clear">
                <button class="btn btn-link text-danger w-100" data-confirm="Xóa toàn bộ giỏ hàng?">Xóa giỏ hàng</button></form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
