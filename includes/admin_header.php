<?php
/** Khung giao diện khu quản trị (sidebar + header). Biến $pageTitle, $adminActive được đặt ở từng trang. */
require_admin();
$user = current_user();
$adminActive = $adminActive ?? '';
$menu = [
    'dashboard'  => ['admin/index.php', 'bi-speedometer2', 'Tổng quan'],
    'books'      => ['admin/books.php', 'bi-book', 'Quản lý sách'],
    'categories' => ['admin/categories.php', 'bi-tags', 'Danh mục'],
    'orders'     => ['admin/orders.php', 'bi-receipt', 'Đơn hàng'],
    'users'      => ['admin/users.php', 'bi-people', 'Khách hàng'],
];
?><!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($pageTitle ?? 'Quản trị') . ' | Quản trị ' . config('app.name')) ?></title>
    <link rel="icon" href="<?= e(url('assets/images/logo.svg')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
    <link href="<?= e(url('assets/css/admin.css')) ?>" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar">
        <a class="admin-brand" href="<?= e(url('admin/index.php')) ?>">
            <img src="<?= e(url('assets/images/logo.svg')) ?>" alt="" width="34" height="34"><span><?= e(config('app.name')) ?></span>
        </a>
        <nav class="nav flex-column">
            <?php foreach ($menu as $key => [$href, $icon, $label]): ?>
                <a class="nav-link <?= $adminActive === $key ? 'active' : '' ?>" href="<?= e(url($href)) ?>"><i class="bi <?= $icon ?>"></i> <?= e($label) ?></a>
            <?php endforeach; ?>
            <hr>
            <a class="nav-link" href="<?= e(url('index.php')) ?>"><i class="bi bi-shop"></i> Xem cửa hàng</a>
            <a class="nav-link" href="<?= e(url('logout.php')) ?>"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a>
        </nav>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="btn btn-sm btn-outline-navy d-lg-none" type="button" onclick="document.getElementById('adminSidebar').classList.toggle('open')" aria-label="Mở menu"><i class="bi bi-list"></i></button>
            <h1 class="h5 mb-0"><?= e($pageTitle ?? 'Quản trị') ?></h1>
            <span class="ms-auto small text-muted"><i class="bi bi-person-circle"></i> <?= e($user['name']) ?> (Quản trị viên)</span>
        </header>
        <div class="admin-content">
            <?php flash_render(); ?>
