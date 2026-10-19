<?php
/** Phần đầu trang dành cho khách hàng. Biến $pageTitle (tùy chọn) đặt tiêu đề trang. */
$user = current_user();
$cartCount = cart_count();
$appName = config('app.name');
$navCats = db()->query('SELECT CategoryID, CategoryName FROM Categories ORDER BY CategoryName')->fetchAll();
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?><!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($pageTitle ?? 'Trang chủ') . ' | ' . $appName) ?></title>
    <link rel="icon" href="<?= e(url('assets/images/logo.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<header class="site-header">
    <div class="topbar d-none d-md-block">
        <div class="container d-flex justify-content-between">
            <span><i class="bi bi-truck"></i> Miễn phí vận chuyển cho đơn từ <?= money(config('app.free_ship_from')) ?></span>
            <span><i class="bi bi-cash-coin"></i> Thanh toán khi nhận hàng (COD)</span>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg main-nav">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(url('index.php')) ?>">
                <img src="<?= e(url('assets/images/logo.svg')) ?>" alt="" width="38" height="38">
                <span><?= e($appName) ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainMenu" aria-label="Mở menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainMenu">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= e(url('index.php')) ?>">Trang chủ</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= $currentPage === 'books.php' ? 'active' : '' ?>" href="#" data-bs-toggle="dropdown">Danh mục sách</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= e(url('books.php')) ?>">Tất cả sách</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php foreach ($navCats as $nc): ?>
                                <li><a class="dropdown-item" href="<?= e(url('books.php?cat=' . (int) $nc['CategoryID'])) ?>"><?= e($nc['CategoryName']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                </ul>
                <form class="search-form d-flex me-lg-3 my-2 my-lg-0" action="<?= e(url('books.php')) ?>" method="get" role="search">
                    <input class="form-control" type="search" name="q" placeholder="Tìm tên sách, tác giả..." value="<?= e($_GET['q'] ?? '') ?>" aria-label="Tìm kiếm">
                    <button class="btn btn-bronze" type="submit" aria-label="Tìm"><i class="bi bi-search"></i></button>
                </form>
                <ul class="navbar-nav align-items-lg-center gap-lg-2">
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?= e(url('cart.php')) ?>">
                            <i class="bi bi-cart3 fs-5"></i> Giỏ hàng
                            <?php if ($cartCount > 0): ?><span class="badge rounded-pill cart-badge"><?= $cartCount ?></span><?php endif; ?>
                        </a>
                    </li>
                    <?php if ($user): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-person-circle"></i> <?= e($user['name']) ?></a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($user['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="<?= e(url('admin/index.php')) ?>"><i class="bi bi-speedometer2"></i> Trang quản trị</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="<?= e(url('profile.php')) ?>"><i class="bi bi-person"></i> Tài khoản</a></li>
                                <li><a class="dropdown-item" href="<?= e(url('orders.php')) ?>"><i class="bi bi-receipt"></i> Đơn hàng của tôi</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= e(url('logout.php')) ?>"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('login.php')) ?>">Đăng nhập</a></li>
                        <li class="nav-item"><a class="btn btn-sm btn-bronze" href="<?= e(url('register.php')) ?>">Đăng ký</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>
<main class="container py-4">
<?php flash_render(); ?>
