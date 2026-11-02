<?php use App\Core\Auth; use App\Core\Csrf;
try { $user = Auth::user(); } catch (Throwable $ex) { $user = null; } // the page still shows if the database is down
$isAdmin = ($user['role'] ?? '') === 'Admin'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title><?= e(($pageTitle ?? '') ? $pageTitle . ' · ' : '') ?><?= e(config('app.name')) ?></title>
    <link rel="icon" href="<?= asset('assets/img/logo.svg') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="<?= url('/') ?>"><?= icon('film', 26) ?> <span><?= e(config('app.name')) ?></span></a>
        <button class="nav-toggle" type="button" data-tip="Menu" aria-label="Open menu" aria-expanded="false"><?= icon('menu', 24) ?></button>
        <nav class="main-nav" id="main-nav">
            <a class="<?= is_active('/') ?>" href="<?= url('/') ?>">Home</a>
            <a class="<?= is_active('/movies') ?>" href="<?= url('/movies') ?>">Movies</a>
            <?php if ($user): ?>
                <a class="<?= is_active('/account/bookings') ?>" href="<?= url('/account/bookings') ?>">My bookings</a>
                <?php if ($isAdmin): ?>
                    <a class="nav-admin" href="<?= url('/admin') ?>" data-tip="Administration dashboard"><?= icon('grid', 16) ?> Admin</a>
                <?php endif; ?>
                <a class="nav-user <?= is_active('/account/profile') ?>" href="<?= url('/account/profile') ?>" data-tip="My profile"><?= icon('user', 16) ?> <?= e(explode(' ', $user['full_name'])[0]) ?></a>
                <form method="post" action="<?= url('/logout') ?>" class="inline">
                    <?= Csrf::field() ?>
                    <button class="icon-btn" type="submit" data-tip="Log out" aria-label="Log out"><?= icon('logout') ?></button>
                </form>
            <?php else: ?>
                <a class="<?= is_active('/login') ?>" href="<?= url('/login') ?>"><?= icon('login', 16) ?> Log in</a>
                <a class="btn btn-sm" href="<?= url('/register') ?>">Sign up</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<?php require ROOT . '/app/Views/partials/flash.php'; ?>

<main>
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <div>
            <a class="logo" href="<?= url('/') ?>"><?= icon('film', 22) ?> <span><?= e(config('app.name')) ?></span></a>
            <p class="muted">Browse movies, pick your seats and book online in a few clicks.</p>
        </div>
        <div class="footer-links">
            <a href="<?= url('/movies', ['status' => 'Now Showing']) ?>">Now showing</a>
            <a href="<?= url('/movies', ['status' => 'Coming Soon']) ?>">Coming soon</a>
            <a href="<?= url('/account/bookings') ?>">My bookings</a>
        </div>
        <p class="muted small">Cancellation is free up to <?= (int) config('app.cancel_hours') ?> hours before the screening.<br>&copy; <?= date('Y') ?> <?= e(config('app.name')) ?></p>
    </div>
</footer>
<script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>
