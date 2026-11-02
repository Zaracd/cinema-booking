<?php use App\Core\Auth; use App\Core\Csrf; $user = Auth::user(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title><?= e($pageTitle ?? 'Admin') ?> · Admin · <?= e(config('app.name')) ?></title>
    <link rel="icon" href="<?= asset('assets/img/logo.svg') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
</head>
<body class="admin-body">
<aside class="admin-side" id="admin-side">
    <a class="logo" href="<?= url('/admin') ?>"><?= icon('film', 24) ?> <span><?= e(config('app.name')) ?></span></a>
    <nav>
        <a class="<?= is_active('/admin') && !preg_match('#/admin/.#', $_SERVER['REQUEST_URI']) ? 'active' : '' ?>" href="<?= url('/admin') ?>"><?= icon('chart') ?> Dashboard</a>
        <a class="<?= is_active('/admin/movies') ?>" href="<?= url('/admin/movies') ?>"><?= icon('film') ?> Movies</a>
        <a class="<?= is_active('/admin/halls') ?>" href="<?= url('/admin/halls') ?>"><?= icon('door') ?> Halls &amp; seats</a>
        <a class="<?= is_active('/admin/showtimes') ?>" href="<?= url('/admin/showtimes') ?>"><?= icon('calendar') ?> Screenings</a>
        <a class="<?= is_active('/admin/bookings') ?>" href="<?= url('/admin/bookings') ?>"><?= icon('ticket') ?> Bookings</a>
        <a class="<?= is_active('/admin/users') ?>" href="<?= url('/admin/users') ?>"><?= icon('users') ?> Users</a>
    </nav>
    <div class="side-foot">
        <a class="btn btn-ghost btn-sm" href="<?= url('/') ?>"><?= icon('home', 16) ?> View website</a>
        <form method="post" action="<?= url('/logout') ?>">
            <?= Csrf::field() ?>
            <button class="btn btn-ghost btn-sm btn-block" type="submit"><?= icon('logout', 16) ?> Log out</button>
        </form>
    </div>
</aside>
<div class="admin-main">
    <header class="admin-top">
        <div style="display:flex;align-items:center;gap:10px">
            <button class="icon-btn admin-toggle" type="button" onclick="document.body.classList.toggle('admin-open')" aria-label="Menu" data-tip="Menu"><?= icon('menu') ?></button>
            <h1><?= e($pageTitle ?? 'Admin') ?></h1>
        </div>
        <span class="muted small"><?= icon('user', 16) ?> <?= e($user['full_name']) ?></span>
    </header>
    <div class="admin-content">
        <?php require ROOT . '/app/Views/partials/flash.php'; ?>
        <?= $content ?>
    </div>
</div>
<script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>
