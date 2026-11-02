<section class="container section narrow">
    <div class="page-head"><h1>My profile</h1><?= status_badge($user['role']) ?></div>
    <div class="two-col">
        <div class="card">
            <h2>Account details</h2>
            <form method="post" action="<?= url('/account/profile') ?>" class="form js-validate" novalidate>
                <?= App\Core\Csrf::field() ?>
                <label class="field <?= isset($errors['full_name']) ? 'invalid' : '' ?>">
                    <span>Full name</span>
                    <input type="text" name="full_name" value="<?= e(old('full_name', $user['full_name'])) ?>" required maxlength="100">
                    <?= field_error($errors, 'full_name') ?>
                </label>
                <label class="field">
                    <span>Email</span>
                    <input type="email" value="<?= e($user['email']) ?>" disabled>
                </label>
                <label class="field <?= isset($errors['phone']) ? 'invalid' : '' ?>">
                    <span>Phone</span>
                    <input type="tel" name="phone" value="<?= e(old('phone', $user['phone'])) ?>" required pattern="\+?[0-9 \-]{7,20}" data-msg="Enter a valid phone number.">
                    <?= field_error($errors, 'phone') ?>
                </label>
                <p class="muted small">Member since <?= e(fmt_date($user['created_at'])) ?></p>
                <button class="btn" type="submit">Save changes</button>
            </form>
        </div>
        <div class="card">
            <h2>Change password</h2>
            <form method="post" action="<?= url('/account/password') ?>" class="form js-validate" novalidate>
                <?= App\Core\Csrf::field() ?>
                <label class="field <?= isset($errors['current_password']) ? 'invalid' : '' ?>">
                    <span>Current password</span>
                    <input type="password" name="current_password" required autocomplete="current-password">
                    <?= field_error($errors, 'current_password') ?>
                </label>
                <label class="field <?= isset($errors['password']) ? 'invalid' : '' ?>">
                    <span>New password</span>
                    <input type="password" name="password" required minlength="8" autocomplete="new-password" data-strong>
                    <?= field_error($errors, 'password') ?>
                </label>
                <label class="field <?= isset($errors['password_confirm']) ? 'invalid' : '' ?>">
                    <span>Confirm new password</span>
                    <input type="password" name="password_confirm" required autocomplete="new-password" data-match="password">
                    <?= field_error($errors, 'password_confirm') ?>
                </label>
                <button class="btn" type="submit">Update password</button>
            </form>
        </div>
    </div>
</section>
