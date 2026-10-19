<?php
/**
 * CÁC HÀM DÙNG CHUNG: cấu hình, escape HTML, flash message, CSRF, đăng nhập,
 * giỏ hàng, đơn hàng, phân trang, tải ảnh bìa.
 */

/* ------------------------------------------------------------------ Cấu hình */
function config(string $key, $default = null)
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require ROOT_PATH . '/config/config.php';
    }
    $value = $cfg;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/* ------------------------------------------------------------ Tiện ích chung */
/** Escape dữ liệu trước khi in ra HTML (chống XSS). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/** Chỉ cho phép quay lại một đường dẫn nội bộ của website (chống open redirect). */
function safe_back(?string $back, string $fallback): string
{
    if ($back && $back[0] === '/' && !str_starts_with($back, '//') && !str_contains($back, "\\")
        && !preg_match('/[\r\n]/', $back) && str_starts_with($back, BASE_URL . '/')) {
        return $back;
    }
    return url($fallback);
}

function money($amount): string
{
    return number_format((float) $amount, 0, ',', '.') . ' ₫';
}

function format_date($value, bool $withTime = true): string
{
    if (!$value) {
        return '';
    }
    $ts = strtotime((string) $value);
    return date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $ts);
}

/** Chuyển chuỗi tìm kiếm thành mẫu LIKE an toàn cho SQL Server (vô hiệu hóa ký tự đặc biệt % _ [). */
function like_pattern(string $text): string
{
    $text = str_replace(['[', '%', '_'], ['[[]', '[%]', '[_]'], $text);
    return '%' . $text . '%';
}

/* ------------------------------------------------------------ Thông báo flash */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_render(): void
{
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $cls = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'][$f['type']] ?? 'info';
        echo '<div class="alert alert-' . $cls . ' alert-dismissible fade show auto-dismiss" role="alert">'
            . e($f['message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button></div>';
    }
    unset($_SESSION['flash']);
}

/* ---------------------------------------------------------------------- CSRF */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Dừng xử lý nếu yêu cầu POST không có mã CSRF hợp lệ. */
function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(400);
        exit('Yêu cầu không hợp lệ (sai mã bảo vệ CSRF). Hãy tải lại trang và thử lại.');
    }
}

/* ----------------------------------------------------------- Đăng nhập, quyền */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function require_login(): void
{
    if (!current_user()) {
        flash('warning', 'Vui lòng đăng nhập để tiếp tục.');
        redirect('login.php?redirect=' . urlencode(basename($_SERVER['SCRIPT_NAME'])));
    }
}

/** Chỉ Admin được vào khu quản trị; khách hàng và khách vãng lai bị chặn. */
function require_admin(): void
{
    $u = current_user();
    if (!$u) {
        flash('warning', 'Vui lòng đăng nhập bằng tài khoản quản trị.');
        redirect('login.php?redirect=admin/index.php');
    }
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        flash('error', 'Bạn không có quyền truy cập khu vực quản trị.');
        redirect('index.php');
    }
}

/* ------------------------------------------------------------------ Hình ảnh */
function book_image(?string $path): string
{
    if ($path && is_file(ROOT_PATH . '/' . $path)) {
        return url($path);
    }
    return url('assets/images/no-cover.svg');
}

/**
 * Tải ảnh bìa lên (jpg, png, webp, tối đa 2MB). Trả về đường dẫn tương đối hoặc null nếu không có tệp.
 * Ném Exception với thông báo tiếng Việt nếu tệp không hợp lệ.
 */
function upload_cover(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Tải ảnh lên thất bại (mã lỗi ' . (int) $file['error'] . ').');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('Ảnh bìa không được lớn hơn 2MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        throw new Exception('Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.');
    }
    $name = 'bk_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $dir = ROOT_PATH . '/assets/images/books';
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new Exception('Không lưu được ảnh vào thư mục assets/images/books.');
    }
    return 'assets/images/books/' . $name;
}

/** Xóa ảnh bìa do người dùng tải lên (chỉ xóa tệp bắt đầu bằng "bk_", không đụng tới ảnh mẫu). */
function delete_uploaded_cover(?string $path): void
{
    if ($path && preg_match('#^assets/images/books/bk_[a-f0-9]+\.(jpg|png|webp)$#', $path)) {
        @unlink(ROOT_PATH . '/' . $path);
    }
}

/* ------------------------------------------------------------------ Phân trang */
function paginate(int $total, int $page, int $perPage): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage, 'per' => $perPage, 'total' => $total];
}

/** Vẽ thanh phân trang, giữ nguyên các tham số lọc hiện có. */
function render_pagination(array $p, array $query = []): void
{
    if ($p['pages'] <= 1) {
        return;
    }
    echo '<nav aria-label="Phân trang"><ul class="pagination justify-content-center flex-wrap">';
    for ($i = 1; $i <= $p['pages']; $i++) {
        $q = http_build_query(array_merge($query, ['page' => $i]));
        echo '<li class="page-item' . ($i === $p['page'] ? ' active' : '') . '"><a class="page-link" href="?' . e($q) . '">' . $i . '</a></li>';
    }
    echo '</ul></nav>';
}

/* ------------------------------------------------------------------ Đơn hàng */
const ORDER_STATUS = [
    'Pending'   => ['Chờ xác nhận', 'warning'],
    'Confirmed' => ['Đã xác nhận', 'info'],
    'Shipping'  => ['Đang giao', 'primary'],
    'Completed' => ['Hoàn thành', 'success'],
    'Cancelled' => ['Đã hủy', 'secondary'],
];

/** Trạng thái tiếp theo mà Admin được phép chuyển tới. */
const ORDER_FLOW = [
    'Pending'   => ['Confirmed', 'Cancelled'],
    'Confirmed' => ['Shipping', 'Cancelled'],
    'Shipping'  => ['Completed', 'Cancelled'],
    'Completed' => [],
    'Cancelled' => [],
];

function status_badge(string $status): string
{
    [$label, $cls] = ORDER_STATUS[$status] ?? [$status, 'secondary'];
    return '<span class="badge text-bg-' . $cls . '">' . e($label) . '</span>';
}

/**
 * Đổi trạng thái đơn hàng. Nếu hủy đơn thì hoàn lại tồn kho trong cùng một giao dịch.
 * Trả về true nếu thành công, false nếu chuyển trạng thái không hợp lệ.
 */
function change_order_status(int $orderId, string $newStatus, ?int $onlyUserId = null): bool
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Khóa dòng đơn hàng để tránh hai người cùng đổi trạng thái một lúc
        $sql = 'SELECT Status, UserID FROM Orders WITH (UPDLOCK, ROWLOCK) WHERE OrderID = ?';
        $st = $pdo->prepare($sql);
        $st->execute([$orderId]);
        $order = $st->fetch();
        // Khách hàng (có $onlyUserId) chỉ được hủy đơn của chính mình và chỉ khi đơn còn "Chờ xác nhận"
        $customerNotAllowed = $onlyUserId !== null
            && ((int) $order['UserID'] !== $onlyUserId || $order['Status'] !== 'Pending');
        if (!$order || $customerNotAllowed
            || !in_array($newStatus, ORDER_FLOW[$order['Status']] ?? [], true)) {
            $pdo->rollBack();
            return false;
        }
        if ($newStatus === 'Cancelled') {
            // Hoàn lại số lượng sách vào kho
            $pdo->prepare('UPDATE b SET b.Stock = b.Stock + d.Quantity
                           FROM Books b JOIN OrderDetails d ON d.BookID = b.BookID
                           WHERE d.OrderID = ?')->execute([$orderId]);
        }
        $pdo->prepare('UPDATE Orders SET Status = ?, UpdatedAt = SYSDATETIME() WHERE OrderID = ?')
            ->execute([$newStatus, $orderId]);
        $pdo->commit();
        return true;
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $ex;
    }
}

/* ------------------------------------------------------------------ Giỏ hàng */
/** Giỏ hàng lưu trong session dưới dạng [BookID => số lượng]. Giá luôn lấy lại từ CSDL. */
function cart_raw(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return (int) array_sum(cart_raw());
}

/**
 * Đọc chi tiết giỏ hàng từ CSDL: giá, tồn kho, tổng tiền (tính ở phía máy chủ).
 * Tự loại sách đã ẩn/hết hàng và giảm số lượng vượt tồn kho; đặt cờ 'adjusted' nếu có thay đổi.
 */
function cart_details(): array
{
    $cart = cart_raw();
    $result = ['items' => [], 'subtotal' => 0, 'shipping' => 0, 'total' => 0, 'count' => 0, 'adjusted' => false];
    if (!$cart) {
        return $result;
    }
    $ids = array_map('intval', array_keys($cart));
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $st = db()->prepare("SELECT BookID, Title, Author, Price, Stock, ImagePath
                         FROM Books WHERE IsActive = 1 AND BookID IN ($marks)");
    $st->execute($ids);
    $newCart = [];
    foreach ($st->fetchAll() as $b) {
        $id = (int) $b['BookID'];
        $stock = (int) $b['Stock'];
        $qty = min((int) $cart[$id], $stock);
        if ($qty < (int) $cart[$id]) {
            $result['adjusted'] = true;
        }
        if ($qty <= 0) {
            continue;
        }
        $b['qty'] = $qty;
        $b['line'] = (int) $b['Price'] * $qty;
        $result['items'][] = $b;
        $result['subtotal'] += $b['line'];
        $result['count'] += $qty;
        $newCart[$id] = $qty;
    }
    if (count($newCart) !== count($cart)) {
        $result['adjusted'] = true;
    }
    $_SESSION['cart'] = $newCart;
    if ($result['subtotal'] > 0) {
        $result['shipping'] = $result['subtotal'] >= (int) config('app.free_ship_from') ? 0 : (int) config('app.shipping_fee');
    }
    $result['total'] = $result['subtotal'] + $result['shipping'];
    return $result;
}

/* --------------------------------------------------------------- Giao diện */
/** In một thẻ sách (dùng ở trang chủ và trang danh sách). */
function book_card(array $b): void
{
    $out = (int) $b['Stock'] <= 0;
    ?>
    <div class="col-6 col-md-4 col-lg-3">
        <div class="book-card h-100">
            <a href="<?= e(url('book-detail.php?id=' . (int) $b['BookID'])) ?>" class="book-cover-wrap">
                <img src="<?= e(book_image($b['ImagePath'])) ?>" alt="Bìa sách <?= e($b['Title']) ?>" class="book-cover" loading="lazy">
                <?php if ($out): ?><span class="ribbon">Hết hàng</span><?php endif; ?>
            </a>
            <div class="book-body">
                <div class="book-cat"><?= e($b['CategoryName'] ?? '') ?></div>
                <h3 class="book-title"><a href="<?= e(url('book-detail.php?id=' . (int) $b['BookID'])) ?>"><?= e($b['Title']) ?></a></h3>
                <div class="book-author"><?= e($b['Author']) ?></div>
                <div class="book-price"><?= money($b['Price']) ?></div>
                <div class="d-flex gap-2 mt-2">
                    <a class="btn btn-sm btn-outline-navy flex-fill" href="<?= e(url('book-detail.php?id=' . (int) $b['BookID'])) ?>">Chi tiết</a>
                    <form method="post" action="<?= e(url('cart.php')) ?>" class="flex-fill">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="book_id" value="<?= (int) $b['BookID'] ?>">
                        <input type="hidden" name="qty" value="1">
                        <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
                        <button class="btn btn-sm btn-bronze w-100" <?= $out ? 'disabled' : '' ?>>Thêm vào giỏ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
}
