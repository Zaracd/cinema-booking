<section class="container auth-wrap">
    <div class="card auth-card">
        <h1>Create your account</h1>
        <p class="muted">It takes less than a minute.</p>
        <form method="post" action="<?= url('/register') ?>" class="form js-validate" novalidate>
            <?= App\Core\Csrf::field() ?>
            <label class="field <?= isset($errors['full_name']) ? 'invalid' : '' ?>">
                <span>Full name</span>
                <input type="text" name="full_name" value="<?= e(old('full_name')) ?>" required maxlength="100" autocomplete="name">
                <?= field_error($errors, 'full_name') ?>
            </label>
            <label class="field <?= isset($errors['email']) ? 'invalid' : '' ?>">
                <span>Email</span>
                <input type="email" name="email" value="<?= e(old('email')) ?>" required maxlength="150" autocomplete="email">
                <?= field_error($errors, 'email') ?>
            </label>
            <label class="field <?= isset($errors['phone']) ? 'invalid' : '' ?>">
                <span>Phone number</span>
                <input type="tel" name="phone" value="<?= e(old('phone')) ?>" required pattern="\+?[0-9 \-]{7,20}" placeholder="+218 91 234 5678" autocomplete="tel"
                       data-msg="Enter a valid phone number (digits, spaces, + or -).">
                <?= field_error($errors, 'phone') ?>
            </label>
            <label class="field <?= isset($errors['password']) ? 'invalid' : '' ?>">
                <span>Password</span>
                <span class="password-wrap">
                    <input type="password" name="password" required minlength="8" autocomplete="new-password" data-strong>
                    <button type="button" class="icon-btn toggle-password" data-tip="Show password" aria-label="Show password"><?= icon('eye', 16) ?></button>
                </span>
                <?= field_error($errors, 'password') ?>
                <ul class="pw-rules" aria-live="polite">
                    <li data-rule="len">8+ characters</li>
                    <li data-rule="upper">Uppercase letter</li>
                    <li data-rule="lower">Lowercase letter</li>
                    <li data-rule="num">Number</li>
                    <li data-rule="sym">Symbol</li>
                </ul>
            </label>
            <label class="field <?= isset($errors['password_confirm']) ? 'invalid' : '' ?>">
                <span>Confirm password</span>
                <input type="password" name="password_confirm" required autocomplete="new-password" data-match="password">
                <?= field_error($errors, 'password_confirm') ?>
            </label>
            <button class="btn btn-block" type="submit">Create account</button>
        </form>
        <p class="center muted">Already have an account? <a href="<?= url('/login') ?>">Log in</a></p>
    </div>
</section>
