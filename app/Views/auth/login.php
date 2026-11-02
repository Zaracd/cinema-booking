<section class="container auth-wrap">
    <div class="card auth-card">
        <h1>Welcome back</h1>
        <p class="muted">Log in to book tickets and see your bookings.</p>
        <form method="post" action="<?= url('/login') ?>" class="form js-validate" novalidate>
            <?= App\Core\Csrf::field() ?>
            <label class="field <?= isset($errors['email']) ? 'invalid' : '' ?>">
                <span>Email</span>
                <input type="email" name="email" value="<?= e(old('email')) ?>" required maxlength="150" autocomplete="email" autofocus>
                <?= field_error($errors, 'email') ?>
            </label>
            <label class="field">
                <span>Password</span>
                <span class="password-wrap">
                    <input type="password" name="password" required autocomplete="current-password">
                    <button type="button" class="icon-btn toggle-password" data-tip="Show password" aria-label="Show password"><?= icon('eye', 16) ?></button>
                </span>
            </label>
            <label class="check">
                <input type="checkbox" name="remember" value="1">
                <span>Remember me on this device</span>
            </label>
            <button class="btn btn-block" type="submit"><?= icon('login', 16) ?> Log in</button>
        </form>
        <p class="center muted">New here? <a href="<?= url('/register') ?>">Create an account</a></p>
    </div>
</section>
